<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\ProductType;
use App\Models\ShopifyShop;
use App\Models\ShopUser;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class ShopifyController extends Controller
{
    public function getShopifyShop(Request $request)
    {
        $shopId = session('shop_id');
        $shopifyDetail = ShopifyShop::where('shop_id', $shopId)
            ->first();

        $service = new ShopifyService($shopifyDetail);
        $counts = $service->getImportCounts();
        $blogs_count = Blog::where('shop_id', $shopId)->count();
        $ccats_count = Cat::where('shop_id', $shopId)->where('cat_type','=','manual')->count();
        $scats_count = Cat::where('shop_id', $shopId)->where('cat_type','=','smart')->count();
        return response()->json([
            'success' => true,
            'shopifyDetail' => $shopifyDetail ?? null,
            'counts' => $counts,
            'blogs_count' => $blogs_count ?? null,
            'ccats_count' => $ccats_count ?? null,
            'scats_count' => $scats_count ?? null,
        ]);
    }

    public function addShopDetails(Request $request)
    {
        $shopId = session('shop_id');
        $existing = ShopifyShop::where('shop_id', $shopId)->first();
        $validatedData = $request->validate([
            'shop_domain' => [
                'required',
                'string',
                'regex:/^[a-z0-9\-]+\.myshopify\.com$/i',
                Rule::unique('shopify_shops', 'shop_domain')->ignore($existing?->id),
            ],
            'client_id' => ['required', 'string'],
            // required only when there's no existing row (first-time save)
            'client_secret' => [$existing ? 'nullable' : 'required', 'string'],

        ]);
        $shopifyShop = ShopifyShop::updateOrCreate(
            ['shop_id' => $shopId],
            [
                'shop_domain'   => $validatedData['shop_domain'],
                'client_id'     => $validatedData['client_id'],
                'client_secret' => $validatedData['client_secret'] ?? $existing?->client_secret,
            ]
        );

        try {
            app(ShopifyService::class,['shop' => $shopifyShop])->connect();
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'shopify' => 'Saved, but connecting to Shopify failed: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shopify store connected successfully.',
            'shopify_shop' => $shopifyShop,
        ]);
    }

    public function fetchToken()
    {
        $shopId = session('shop_id');
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();

        try {
            app(ShopifyService::class, ['shop' => $shopifyShop])->connect();
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'shopify' => 'Connecting to Shopify failed: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shopify token Generated successfully.',
            'token' => $shopifyShop->token,
        ]);
    }

    public function fetchProducts(Request $request)
    {
        $shopifyShop = ShopifyShop::where('shop_id','=',session('shop_id'))->first();
        $service = new ShopifyService($shopifyShop);
        $pros = $service->getProducts(25);
//        dd($pros);

//        $epros = [];
//        foreach ($pros as $pro) {
//            $npro['title'] = $pro['title'];
//            $npro['brand'] = $pro['vendor'];
//            $npro['publish_status'] = 'online';
//            $npro['body_html'] = $pro['body_html'];
//            $npro['handle'] = $pro['handle'];
//            $npro['product_type'] = $pro['product_type'];
//            $npro['status'] = $pro['status'];
//            $npro['tags'] = !empty($pro['tags'])
//                ? array_map('trim', explode(',', $pro['tags']))
//                : [];
//            $npro['thirdparty_id'] = $pro['id'];
//            $npro['image'] = $pro['image']['src'] ?? null;
//
//            $nvariants = [];
//            foreach ($pro['variants'] as $variant) {
//                $nops = [];
//                $nvals = null;
//                foreach ($pro['options'] as $noption) {
//                    $nops[] = $noption['name'];
////                    $nvals = [$noption['values'][0],$noption[1]];
////                    foreach ($noption['values'] as $noptionValue) {
////                        $nvals = [$noptionValue[0],$noptionValue[1]];
////                    }
//                }
//                $nvars['price'] = $variant['price'];
//                $nvars['barcode'] = $variant['barcode'];
//                $nvars['compareprice'] = $variant['compare_at_price'];
//                $nvars['sku'] = $variant['sku'];
//                $nvars['qty'] = $variant['inventory_quantity'];
//                $nvars['variant_image'] = $variant['image_id'];
//                $nvars['options'] = $nops;
//                $nvars['option_values'] = $nvals;
//                $nvariants[] = $nvars;
//            }
//            $npro['variants'] = $nvars;
//            $epros[] = $npro;
//        }



        return response()->json([
            'success' => true,
            'epros' => $epros ?? null,
            'pros' => $pros,

        ]);
    }

    public function importArticles(Request $request)
    {
        $shopifyShop = ShopifyShop::where('shop_id','=',session('shop_id'))->first();
        $service = new ShopifyService($shopifyShop);
        $articles = $service->getArticles(100);

        $blogs = [];
        $shopUser = ShopUser::where('shop_id','=',session('shop_id'))->where('role','!=','superadmin')->first();
        $userId = $shopUser?->user_id ?? 1;
        $existingBlogs = Blog::where('shop_id', session('shop_id'))->pluck('blog_image', 'blog_slug');
        foreach ($articles as $article) {
            $bpath = $existingBlogs->get($article['handle']);
            if(!$bpath){
                $bImage = $article['image'] ?? null;
                if(! empty($bImage['src'])) {
                    $imageUrl = $bImage['src'];
                    $response = Http::timeout(30)->get($imageUrl);
                    if($response->successful()) {
                        $filename = 'blog_image_'.uniqid().'.png';
                        $img = Image::make($response->body())->resize(1200, 800, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $bpath = 'blogs/'.$filename;
                        Storage::disk('s3')->put($bpath,(string)$img->encode());
                    }
                }
            }

            $articleImage = $bpath;
            $tags = !empty($article['tags']) ? array_map('trim', explode(',', $article['tags'])) : null;
            $blog = Blog::updateOrCreate(
                [
                    'shop_id' => session('shop_id'),
                    'blog_slug' =>  $article['handle'],
                ],
                [
                    'blog_title' => $article['title'],
                    'blog_description' => $article['body_html'],
                    'blog_excerpt' => $article['summary_html'],
                    'blog_image' => $articleImage,
                    'btags' => $tags,
                    'blog_status' => 'active',
                    'meta_title' => $article['title'],
                    'meta_desc' => $article['title'],
                    'user_id' => $userId,
                    'shop_id' => session('shop_id'),
                ]
            );
            $blogs[] = $blog;
        }

        return response()->json([
            'success' => true,
            'shop_user' => $shopUser ?? null,
            'articles' => $articles,
            'blogs' => $blogs,
        ]);
    }

    public function importCustomCollections(Request $request)
    {
        $shopId = session('shop_id');
        $shopifyShop = ShopifyShop::where('shop_id','=',$shopId)->first();
        $service = new ShopifyService($shopifyShop);
        $customCollections = $service->getCustomCollections();

        $ccats = [];
        $existingCats = Cat::where('shop_id','=',$shopId)->pluck('cat_image', 'cat_slug');
        foreach ($customCollections as $customCollection) {
            $cpath = $existingCats->get($customCollection['handle']);
            if(!$cpath){
                $cImage = $customCollection['image'] ?? null;
                if(! empty($cImage['src'])) {
                    $imageUrl = $cImage['src'];
                    $response = Http::timeout(30)->get($imageUrl);
                    if($response->successful()) {
                        $filename = 'category_image_'.uniqid().'.png';
                        $img = Image::make($response->body())->resize(600, 600, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $cpath = 'category/'.$filename;
                        Storage::disk('s3')->put($cpath,(string)$img->encode());
                    }
                }
            }
            $catImage = $cpath;
            $cat =  Cat::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'cat_slug' => $customCollection['handle']],
                [
                    'cat_name' => $customCollection['title'],
                    'cat_slug' => $customCollection['handle'],
                    'cat_desc' => $customCollection['body_html'],
                    'short_desc' => "",
                    'cat_status' => 'Active',
                    'cat_image' => $catImage,
                    'cat_type' => 'manual',
                    'cat_rule' => 'or',
                    'sort_order' => 'title_asc',
                    'shop_id' => $shopId,
                    'meta_title' => $customCollection['title'],
                    'meta_desc' => $customCollection['title'],
                ]
            );

            $ccats[] = $cat;
        }
        return response()->json([
            'success' => true,
            'shopifycats' => $customCollections,
            'ccats' => $ccats,
        ]);
    }

    public function importSmartCollections(Request $request)
    {
        $shopId = session('shop_id');
        $shopifyShop = ShopifyShop::where('shop_id','=',$shopId)->first();
        $service = new ShopifyService($shopifyShop);
        $smartCollections = $service->getSmartCollections();

        $scats = [];
        $existingCats = Cat::where('shop_id','=',$shopId)->pluck('cat_image', 'cat_slug');
        foreach ($smartCollections as $smartCollection) {
            $cpath = $existingCats->get($smartCollection['handle']);
            if(!$cpath){
                $cImage = $smartCollection['image'] ?? null;
                if(! empty($cImage['src'])) {
                    $imageUrl = $cImage['src'];
                    $response = Http::timeout(30)->get($imageUrl);
                    if($response->successful()) {
                        $filename = 'category_image_'.uniqid().'.png';
                        $img = Image::make($response->body())->resize(600, 600, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $cpath = 'category/'.$filename;
                        Storage::disk('s3')->put($cpath,(string)$img->encode());
                    }
                }
            }
            $catImage = $cpath;
            $arules = [];
            foreach ($smartCollection['rules'] as $rule) {
                $arule = [];
                switch ($rule['column']) {
                    case 'vendor':
                        $arule['column']    = 'vendor';
                        $arule['relation'] = $rule['relation'];
                        $arule['condition'] = $this->getBrandId($rule['condition']);
                        break;

                    case 'type':
                        $arule['column']    = 'type';
                        $arule['relation'] = $rule['relation'];
                        $arule['condition'] = $this->getTypeId($rule['condition']);
                        break;

                    case 'tag':
                        $arule['column']    = 'tag';
                        $arule['relation'] = $rule['relation'];
                        $arule['condition'] = $rule['condition']; // tag name string, as-is
                        break;

                    default:
                        $arule['column']    = 'title';
                        $arule['relation'] = 'contains';
                        $arule['condition'] = $rule['condition'];
                        break;
                }
                $arules[] = $arule;
            }

            $cat =  Cat::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'cat_slug' => $smartCollection['handle']],
                [
                    'cat_name' => $smartCollection['title'],
                    'cat_slug' => $smartCollection['handle'],
                    'cat_desc' => $smartCollection['body_html'],
                    'short_desc' => "",
                    'cat_status' => 'Active',
                    'cat_image' => $catImage,
                    'cat_type' => 'smart',
                    'cat_rule' => 'and',
                    'sort_order' => 'title_asc',
                    'shop_id' => $shopId,
                    'meta_title' => $smartCollection['title'],
                    'meta_desc' => $smartCollection['title'],
                ]
            );
            \App\Models\Rule::where('shop_id', $shopId)->where('cat_id', $cat['cat_id'])->delete();
            foreach ($arules as $arule) {
                $rule = \App\Models\Rule::create([
                    'shop_id'   => $shopId,
                    'cat_id'    => $cat['cat_id'],
                    'column'    => $arule['column'],
                    'relation'  => $arule['relation'],
                    'condition' => $arule['condition'],
                ]);
            }

            $scats[] = $cat;
        }
        return response()->json([
            'success' => true,
            'shopifycats' => $smartCollections,
            'scats' => $scats,
        ]);
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

}
