<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Location;
use App\Models\Poptions;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Shop;
use App\Models\ShopifyShop;
use App\Models\Spro;
use App\Models\Stock;
use App\Models\Tag;
use App\Models\Variant;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SproController extends Controller
{
    public function sync()
    {
        $shopId = session('shop_id');
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $service->importProductsSinceId(function (array $batch) use ($shopId) {
            foreach ($batch as $product) {
                Spro::updateOrCreate(
                    [
                        'shop_id'             => $shopId,
                        'shopify_product_id'  => $product['id'],
                    ],
                    [
                        'title'        => $product['title'],
                        'handle'       => $product['handle'],
                        'vendor'       => $product['vendor'] ?? null,
                        'product_type' => $product['product_type'] ?? null,
                        'status'       => $product['status'] ?? null,
                        'body_html'    => $product['body_html'] ?? null,
                        'tags'         => $product['tags'] ?? null,
                        'variants'     => $product['variants'] ?? [],
                        'options'      => $product['options'] ?? [],
                        'images'       => $product['images'] ?? [],
                        'image_one'    => $product['images'][0]['src'] ?? null,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sync complete.',
            'spros_count' => Spro::where('shop_id',$shopId)->count() ?? null,
        ]);
    }

    public function index(Request $request)
    {
        $shopId = session('shop_id');
        $query = Spro::where('shop_id', $shopId);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($request->boolean('only_not_imported')) {
            $query->whereNull('product_id');
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 100);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 100)
                : 50;
        }
        $page = $query->paginate($perPage);

        $stotal = Spro::where('shop_id',$shopId)->count();
        return response()->json([
            'items'       => $page,
            'stotal'     => $stotal,
        ]);
    }

    public function createSingleProduct(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();
        $shopSlug = $shop->shop_slug ?? "Unbranded";
        $baseBrand = strtoupper(str_replace(['-', '_'], ' ', $shopSlug));
        $validated = $request->validate([
            'id'=>['integer','exists:spros,id'],
        ]);
        $spro = Spro::where('shop_id', $shopId)->where('id', $validated['id'])->whereNull('product_id')->firstOrFail();
        $tags = !empty($spro['tags']) ? array_map('trim', explode(',', $spro['tags'])) : null;
        if(!empty($tags)) {
            foreach ($tags as $tag) {
                Tag::updateOrCreate(
                    ['tag_name' => $tag,'shop_id' => $shopId],
                    ['tag_name' => $tag,'shop_id' => $shopId]
                );
            }
        }
        $options = $this->isSimpleProductOptions($spro['options']) ? null : array_column($spro['options'], 'name');
        if(!empty($options)) {
            foreach ($options as $proption) {
                Poptions::updateOrCreate(
                    ['option_name' => $proption,'shop_id' => $shopId],
                    ['option_name' => $proption,'shop_id' => $shopId]
                );
            }
        }
        if($spro['image_one']){
            $imageUrl = $spro['image_one'];
            $response = Http::timeout(30)->get($imageUrl);
            if($response->successful()) {
                $filename = "product_image-".time().uniqid().'.png';
                $img = Image::make($response->body())->resize(1000, 1000, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $fpath = 'products/'.$filename;
                Storage::disk('s3')->put($fpath, (string) $img->encode());
            }
        }
        $featured_image = $fpath ?? null;

        $slug = $spro['handle'];
        $counter = 1;
        while (Product::where('shop_id', $shopId)->where('handle', $slug)->exists()) {
            $slug = $slug . '-' . $counter;
            $counter++;
        }
        $brandName = trim($spro['vendor'] ?? '') !== '' ? trim($spro['vendor']) : $baseBrand;
        $productType = trim($spro['product_type'] ?? '') !== '' ? trim($spro['product_type']) : 'Others';

        $typeId = $this->getTypeId($productType);
        $brandId = $this->getBrandId($brandName);

        $product = Product::create([
            'title' => $spro['title'],
            'handle' => $slug,
            'publish_status' => 'online',
            'body_html' => $spro['body_html'],
            'featured_image' => $featured_image,
            'product_status' => 'Active',
            'product_type_id' => $typeId,
            'brand_id' => $brandId,
            'tags' => $tags,
            'thirdparty_id' => $spro['shopify_product_id'],
            'shop_id' => $spro['shop_id'],
        ]);

        $location = Location::where('shop_id',$shopId)->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => "Default",
                'location_address' => "Default Address",
                'country' => "United Kingdom",
                'location_status' => "Active",
                'shop_id' => $shopId,
            ]
        );

        $imageS3Map = [];     // shopify_image_id => s3 path
        $variantImageMap = []; // shopify_variant_id => s3 path

        if(!empty($spro['images'])){
            foreach ($spro['images'] as $image) {
                if(! isset($imageS3Map[$image['id']])){
                    $vpath = null;
                    $vimageUrl = $image['src'];
                    $response = Http::timeout(30)->get($vimageUrl);
                    if($response->successful()) {
                        $filename = "variant_image-".time().uniqid().'.png';
                        $img = Image::make($response->body())->resize(1000, 1000, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $vpath = 'products/'.$filename;
                        Storage::disk('s3')->put($vpath, (string) $img->encode());
                    }
                    $imageS3Map[$image['id']] = $vpath;
                }
                foreach ($image['variant_ids'] ?? [] as $variantId) {
                    $variantImageMap[$variantId] = $imageS3Map[$image['id']];
                }
            }
        }
        $pvariants = [];
        foreach ($spro['variants'] as $vindex => $svariant) {
            $avariants = [];
            $optionValue = null;
            $isDefault = false;
            if($options){
                $optionValue = [];
                $isDefault = true;
                foreach ($options as $index => $option) {
                    $key = 'option' . ($index + 1);
                    $optionValue[$option] = $svariant[$key] ?? null;
                }
            }
            $sku = 'p'.$spro['id'].'_'.$vindex;
            $avariants['sku'] = $sku;
            $avariants['price'] = (float) $svariant['price'];
            $avariants['compareprice'] = isset($svariant['compare_at_price']) ? (float) $svariant['compare_at_price'] : null;
            $avariants['barcode'] = $svariant['barcode'] ?? null;
            $avariants['variant_image'] = $variantImageMap[$svariant['id']]
                ?? $imageS3Map[$svariant['image_id'] ?? null]
                ?? null;
            $avariants['isdefault'] = $isDefault;
            $avariants['options'] = $options;
            $avariants['option_values'] = $optionValue;
            $avariants['inventory_quantity'] = $svariant['inventory_quantity'] ?? 0;

            $pvariants[] = $avariants;
        }
        foreach ($pvariants as $pvariant) {
            $variant = Variant::create([
                'sku' => $pvariant['sku'],
                'price' => $pvariant['price'],
                'compareprice' => $pvariant['compareprice'],
                'costprice' => 0,
                'barcode' => $pvariant['barcode'],
                'variant_image' => $pvariant['variant_image'],
                'istax' => true,
                'isdefault' => $pvariant['isdefault'],
                'weight' => 0.25,
                'options' => $pvariant['options'],
                'option_values' => $pvariant['option_values'],
                'product_id' => $product->product_id,
                'shop_id' => $spro['shop_id'],
            ]);
            Stock::create([
                'quantity' => $pvariant['inventory_quantity'] ?? 0,
                'location_id' => $location->location_id,
                'variant_id' => $variant->variant_id,
                'product_id' => $product->product_id,
                'shop_id' => $spro['shop_id'],
            ]);
        }

        $spro->update([
            'product_id'  => $product->product_id,
            'imported_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'pro' => $product,
        ]);
    }

    function isSimpleProductOptions(array $options): bool
    {
        return empty($options) || (
                count($options) === 1 && strtolower($options[0]['name'] ?? '') === 'title'
            );
    }

    private function getBrandId($brandName)
    {
        $shopId = session('shop_id');

        if (!$brandName) return null;
        return Brand::firstOrCreate(
            [
                'brand_name' => trim($brandName),
                'brand_slug' => Str::slug(strtolower(trim($brandName)),'-'),
                'shop_id' => $shopId,
            ]
        )->brand_id;
    }

    private function getTypeId($typeName)
    {
        $shopId = session('shop_id');
        if (!$typeName) return null;
        return ProductType::firstOrCreate(
            [
                'product_type_name' => trim($typeName),
                'shop_id' => $shopId,
            ])->product_type_id;
    }

    public function syncProductSeo(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $updated = 0;

        Product::where('shop_id', $shopId)
            ->whereNotNull('thirdparty_id')
            ->select('product_id', 'thirdparty_id','shop_id','handle')
            ->chunk(100, function ($products) use ($service, &$updated) {
                $shopifyIds = $products->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();

                $seoData = $service->getProductsSeo($shopifyIds);

                foreach ($products as $product) {
                    $seo = $seoData[(int) $product->thirdparty_id] ?? null;

                    if (! $seo) {
                        continue;
                    }

                    $updates = [];

                    if (! empty($seo['title'])) {
                        $updates['meta_title'] = $seo['title'];
                    }

                    if (! empty($seo['description'])) {
                        $updates['meta_desc'] = $seo['description'];
                    }

                    if ($updates) {
                        $product->update($updates);
                        $updated++;
                    }
                }
            });

        return response()->json(['success' => true, 'updated' => $updated]);
    }


    public function import(Request $request)
    {
        $shopId = session('shop_id');
//        $validated = $request->validate([
//            'spro_ids'   => ['nullable', 'array'],
//            'spro_ids.*' => ['integer', 'exists:spros,id'],
//            'import_all' => ['nullable', 'boolean'],
//        ]);
//
//        $query = Spro::where('shop_id', $shopId)->whereNull('product_id');
//
//        if (! ($validated['import_all'] ?? false)) {
//            $query->whereIn('id', $validated['spro_ids'] ?? []);
//        }
//
//        $imported = 0;
//
//        $query->chunk(50, function ($spros) use (&$imported) {
//            foreach ($spros as $spro) {
//                $product = Product::updateOrCreate(
//                    [
//                        'shop_id'    => $spro->shop_id,
//                        'thirdparty_id' => $spro->shopify_product_id,
//                    ],
//                    [
//                        'title'  => $spro->title,
//                        'handle' => $spro->handle,
//                        'status' => $spro->status,
//                        'body_html' => $spro->body_html,
//                         'tags'      => $spro->tags,
//                        'price'  => $spro->variants[0]['price'] ?? null,
//                        'sku'    => $spro->variants[0]['sku'] ?? null,
//                    ]
//                );
//
//                if ($spro->image_one) {
//                    $s3Path = ImageService::storeFromUrl($spro->image_one);
//                    $product->update(['featured_image' => $s3Path]);
//                }
//
//                $spro->update([
//                    'product_id'  => $product->product_id,
//                    'imported_at' => now(),
//                ]);
//
//                $imported++;
//            }
//        });
//
//        return response()->json(['success' => true, 'imported' => $imported]);
    }

}
