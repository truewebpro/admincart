<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnrichesWithLoyaltyPoints;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\Feature;
use App\Models\Highlight;
use App\Models\Location;
use App\Models\Poptions;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\Reviewer;
use App\Models\Stock;
use App\Models\Stype;
use App\Models\Tag;
use App\Models\Variant;
use App\Services\CacheKeys;
use App\Services\SmartCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    use EnrichesWithLoyaltyPoints;
    public function getProductData(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;

        $sproduct = Cache::remember(
            CacheKeys::product($shopId,$slug),
            now()->addHours(12),
            function () use ($shopId,$slug) {
                $product = Product::with('variants.astock','brand','ptype','highs','reviews','specifics','tiers','faqs')
                    ->withCount('reviews')
                    ->withSum('reviews','rating')
                    ->withAvg('reviews','rating')
                    ->where('shop_id','=',$shopId)
                    ->where('handle','=',$slug)
                    ->first();
                if (!$product) {
                    return null;
                }

                $previews = $product->reviews;
                $product['stars5'] = $previews->whereIn('rating',5)->count();
                $product['stars4'] = $previews->whereIn('rating',4)->count();
                $product['stars3'] = $previews->whereIn('rating',3)->count();
                $product['stars2'] = $previews->whereIn('rating',2)->count();
                $product['stars1'] = $previews->whereIn('rating',1)->count();

                return $product;
            }
        );

        if(!$sproduct){
            return response()->json([
                'status' => false,
                'type' => "Product",
                'slug'=> $slug,
                'sproduct' => null,
            ]);
        }

        $sproduct->variants = collect(
            $this->attachLoyaltyPointsToMany($shopId, $sproduct->variants)
        );

        return response()->json([
            'status' => true,
            'type' => "Product",
            'slug'=> $slug,
            'sproduct'=> $sproduct,
        ]);

    }

    public function getProductLazyData(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;

        $data = Cache::remember(
            CacheKeys::productSections($shopId,$slug),
            now()->addHours(12),
            function () use ($shopId,$slug) {
                $sproduct = Product::with('psections')
                    ->where('shop_id','=',$shopId)
                    ->where('handle','=',$slug)
                    ->first();
                if (!$sproduct) {
                    return null;
                }

                $addons = Product::query()
                    ->select('product_id','title','handle','featured_image','product_status','product_type_id',
                        'brand_id','tags')
                    ->with('brand','ptype','variants.astock')
                    ->withCount('reviews')->withAvg('reviews','rating')
                    ->where('shop_id','=',$shopId)->where('brand_id','=',$sproduct->brand_id)
                    ->whereNotIn('products.product_id', [$sproduct->product_id])
                    ->where('product_status','=','Active')
                    ->limit(12)->get();

                $related_products = Product::query()
                    ->select('product_id','title','handle','featured_image','product_status','product_type_id',
                        'brand_id','tags')
                    ->with('brand','ptype','variants.astock')
                    ->withCount('reviews')->withAvg('reviews','rating')
                    ->where('shop_id','=',$shopId)->where('product_type_id','=',$sproduct->product_type_id)
                    ->whereNotIn('products.product_id', [$sproduct->product_id])
                    ->where('product_status','=','Active')
                    ->inRandomOrder()->limit(12)->get();
                $sectionsWithExtras = [];
                foreach ($sproduct->psections as $section){
                    $sectionArray = $section->toArray();
                    if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                        $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                        $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                        $products = Product::with(['variants.astock', 'brand', 'ptype'])
                            ->where('shop_id','=',$shopId)
                            ->whereIn('product_id', function ($query) use ($catId) {
                                $query->select('product_id')
                                    ->from('catpros')
                                    ->where('cat_id', $catId);
                            })
                            ->limit($sectionArray['section_json']['stype_json']['plimit'] ?? 12)
                            ->get();
                        $allVariants = $products->flatMap(fn ($product) => $product->variants);
                        $this->attachLoyaltyPointsToMany($shopId, $allVariants);
                        $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                        $sectionArray['section_json']['stype_json']['catpros'] = $products;
                    }
                    $sectionsWithExtras[] = $sectionArray;
                }
                return [
                    'addons' => $addons->isNotEmpty()
                        ? $addons
                        : $related_products,

                    'related_products' => $related_products,

                    'sections' => $sectionsWithExtras,
                ];
            }
        );
        if (!$data) {
            return response()->json([
                'status' => false,
                'type' => 'Product',
                'slug' => $slug,
            ]);
        }
        $allAddons = $data['addons']->flatMap(fn ($product) => $product->variants);
        $this->attachLoyaltyPointsToMany($shopId, $allAddons);

        $allRpros = $data['related_products']->flatMap(fn ($product) => $product->variants);
        $this->attachLoyaltyPointsToMany($shopId, $allRpros);

        return response()->json([
            'status' => true,
            'type' => 'Product',
            'slug' => $slug,
            'addons' => $data['addons'],
            'related_products' => $data['related_products'],
            'sections' => $data['sections'],
        ]);
    }

    public function getProduct(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $sproduct = Product::with('variants.astock','brand','ptype','psections','highs','reviews','specifics','tiers','faqs')
            ->withCount('reviews')->withSum('reviews','rating')
            ->withAvg('reviews','rating')
            ->where('shop_id','=',$shopId)
            ->where('handle','=',$slug)->first();
        if($sproduct != null){
            $previews = $sproduct->reviews;
            $sproduct['stars5'] = $previews->whereIn('rating',5)->count();
            $sproduct['stars4'] = $previews->whereIn('rating',4)->count();
            $sproduct['stars3'] = $previews->whereIn('rating',3)->count();
            $sproduct['stars2'] = $previews->whereIn('rating',2)->count();
            $sproduct['stars1'] = $previews->whereIn('rating',1)->count();
            $addons = Product::with('brand','ptype','variants.astock')
                ->withCount('reviews')->withAvg('reviews','rating')
                ->where('shop_id','=',$shopId)
                ->where('brand_id','=',$sproduct->brand_id)
                ->whereNotIn('products.product_id', [$sproduct->product_id])
                ->where('product_status','=','Active')
                ->limit(12)->get();
            $related_products = Product::with('brand','ptype','variants.astock')
                ->withCount('reviews')->withAvg('reviews','rating')
                ->where('shop_id','=',$shopId)
                ->where('product_type_id','=',$sproduct->product_type_id)
                ->whereNotIn('products.product_id', [$sproduct->product_id])
                ->where('product_status','=','Active')
                ->inRandomOrder()->limit(12)->get();
            $sectionsWithExtras = [];
            foreach ($sproduct->psections as $section){
                $sectionArray = $section->toArray();
                if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                    $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                    $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                    $products = Product::with(['variants.astock', 'brand', 'ptype'])
                        ->where('shop_id','=',$shopId)
                        ->whereIn('product_id', function ($query) use ($catId) {
                            $query->select('product_id')
                                ->from('catpros')
                                ->where('cat_id', $catId);
                        })
                        ->limit($sectionArray['section_json']['stype_json']['plimit'] ?? 12)
                        ->get();
                    $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                    $sectionArray['section_json']['stype_json']['catpros'] = $products;
                }
                $sectionsWithExtras[] = $sectionArray;
            }
            $sproduct->asections = $sectionsWithExtras;
            return response()->json([
                'status' => true,
                'type' => "Product",
                'slug'=> $slug,
                'sproduct' => $sproduct,
                'addons'=>$addons->isNotEmpty() ? $addons : $related_products,
                'related_products'=>$related_products,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'type' => null,
                'slug'=> $slug,
                'sproduct'=> null,
                'addons'=>null,
                'related_products'=>null,
            ]);
        }
    }

    public function getAllProducts(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $rquery = $request->q;
        $query = Product::query()
            ->select('product_id','title','handle','featured_image','product_status','product_type_id', 'brand_id','tags')
            ->with('variants.astock','brand','ptype')
            ->withCount('reviews')->withAvg('reviews','rating')
            ->withMin('variants','price')
            ->withSum('astock','quantity')
            ->where('shop_id','=',$shopId)
            ->where('product_status','=', 'Active');

        if (!empty($rquery)) {
            $query->where(function ($q) use ($rquery) {
                foreach (explode(' ', trim($rquery)) as $word) {
                    $q->where('title', 'LIKE', "%{$word}%");
                }
            });
        }
        if ($request->brand) {
            $query->where('brand_id', $request->brand);
        }
        if ($request->type) {
            $query->where('product_type_id', $request->type);
        }
        switch ($request->sort) {
            case 'title-ascending':
                $query->orderBy('title', 'ASC');
                break;

            case 'title-descending':
                $query->orderBy('title', 'DESC');
                break;

            case 'created-ascending':
                $query->oldest();break;

            case 'created-descending':
                $query->latest();
                break;

            case 'price-ascending':
                $query->withMin('variants', 'price')
                    ->orderBy('variants_min_price', 'ASC');
                break;

            case 'price-descending':
                $query->withMin('variants', 'price')
                    ->orderBy('variants_min_price', 'DESC');
                break;

            default:
                $query->latest();
        }
        $products = $query->paginate(24);
        $allVariants = $products->getCollection()->flatMap(fn ($product) => $product->variants);
        $this->attachLoyaltyPointsToMany($shopId, $allVariants);

        $filters = Cache::remember(
            CacheKeys::productFilters($shopId),
            now()->addHours(12),
            function () use($shopId) {
                return [
                    'brands' => Brand::query()
                        ->select('brand_id', 'brand_name')
                        ->where('shop_id', $shopId)
                        ->get(),

                    'ptypes' => ProductType::query()
                        ->select('product_type_id', 'product_type_name')
                        ->where('shop_id', $shopId)
                        ->get(),
                ];
            }
        );

        return response()->json([
            'status' => true,
            'type' => "Product",
            'products'=>$products,
            'brands' => $filters['brands'],
            'ptypes' => $filters['ptypes'],
        ]);
    }

    public function searhProducts(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $products = Product::with('variants.astock','brand','ptype')
            ->withCount('reviews')->withAvg('reviews','rating')
            ->orderBy('products.created_at','desc')
            ->where('shop_id','=',$shopId)->get();
        if($products != null){
            return response()->json([
                'status' => true,
                'type' => "Product",
                'products' => $products,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'type' => null,
                'products'=> null,
            ]);
        }

    }

    //Admin Routes
    public function allProducts(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $type = $request->type;
        $brand = $request->brand;
        $tag = $request->tag;
        $status = $request->status;
        $query = Product::withTrashed()
            ->with('brand','ptype')
            ->withCount('astock')
            ->withSum('astock','quantity')
            ->withMin('variants','price')
            ->withCount('variants')
            ->addSelect(['first_variant_options' => Variant::select('options')
                ->whereColumn('product_id', 'products.product_id')
                ->orderBy('variant_id')
                ->limit(1)
            ])
            ->where('shop_id','=',$shopId);
        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('title', 'LIKE', "%{$term}%")
                            ->orWhereHas('brand', function ($brandQ) use ($term) {
                                $brandQ->where('brand_name', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('ptype', function ($typeQ) use ($term) {
                                $typeQ->where('product_type_name', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }
        $allowedSorts = [
            'title' => 'title',
            'quantity' => 'stocks.quantity',
            'product_status' => 'product_status',
        ];
        if ($type) {
            $query->whereHas('ptype', function ($q) use ($type) {
                $q->where('product_type_name', $type);
            });
        }
        if ($brand) {
            $query->whereHas('brand', function ($q) use ($brand) {
                $q->where('brand_name', $brand);
            });
        }
        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }
        if ($status && $status !== 'All') {

            if ($status === 'Archived') {

                $query->whereNotNull('deleted_at');

            } else {

                $query->whereNull('deleted_at')
                    ->where('product_status', $status);
            }
        }

        $filters = [
            'brands' => Brand::where('shop_id', $shopId)
                ->orderBy('brand_name')
                ->pluck('brand_name'),

            "types" => ProductType::where('shop_id', $shopId)
                ->orderBy('product_type_name')
                ->pluck('product_type_name'),

            'tags' => Product::where('shop_id', $shopId)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values()

        ];

        $sortBy = $allowedSorts[$request->sort_by] ?? 'product_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = 100;
        }
        $perPage = $perPage > 0 ? $perPage : 50;
        $products = $query->paginate($perPage);
        $aptags = Tag::where('shop_id','=',$shopId)
            ->select('tag_id','tag_name','tag_status')
            ->orderBy('tag_name')
            ->get();

        return response()->json([
            'users' => auth()->user(),
            'pcount'=> $products->count(),
            'products' => $products,
            'filters' => $filters,
            'aptags' => $aptags,
        ],200);
    }

    public function getProductbyId($product_id)
    {
        $shopId = session('shop_id');
        $product = Product::withTrashed()->where('product_id','=', $product_id)
            ->with('brand','ptype','variants.astock','sections','highs','specifics','tiers','faqs')
            ->where('shop_id','=',$shopId)
            ->first();
        $ptypes = ProductType::select('product_type_id','product_type_name')
            ->where('shop_id','=',$shopId)->get();
        $tags = Tag::where('shop_id','=',$shopId)->get('tag_name');
        $poptions = Poptions::where('shop_id','=',$shopId)->get();
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        $categories = Cat::where('shop_id','=',$shopId)
            ->select('cat_id','cat_name','cat_slug','cat_image')
            ->get();
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_id','brand_name','brand_slug','brand_image')
            ->get();
        $features = Feature::where('shop_id',$shopId)->get();
        $reviews = Proreview::where('shop_id','=',$shopId)->where('product_id','=',$product_id)->get();
        $reviews['rcount'] = $reviews->count();
        $reviews['sum'] = $reviews->sum('rating');
        $reviews['5star'] = $reviews->where('rating','=','5')->count();
        $reviews['4star'] = $reviews->where('rating','=','4')->count();
        $reviews['3star'] = $reviews->where('rating','=','3')->count();
        $reviews['2star'] = $reviews->where('rating','=','2')->count();
        $reviews['1star'] = $reviews->where('rating','=','1')->count();
        $reviews['stars'] = [
            $reviews['1star'] = $reviews->where('rating','=','1')->count(),
            $reviews['2star'] = $reviews->where('rating','=','2')->count(),
            $reviews['3star'] = $reviews->where('rating','=','3')->count(),
            $reviews['4star'] = $reviews->where('rating','=','4')->count(),
            $reviews['5star'] = $reviews->where('rating','=','5')->count(),
        ];

        $reviewers = Reviewer::limit(50)->inRandomOrder()->get();
        if($product){
            return response()->json([
                'prod' => $product,
                'ptypes' => $ptypes,
                'tags' => $tags,
                'poptions' => $poptions,
                'stypes' => $stypes,
                'brands' => $brands,
                'categories' => $categories,
                'features' => $features,
                'reviews' => $reviews,
                'reviewers' => $reviewers
            ]);
        } else {
            return response()->json([
                'product' => "",
            ],401);
        }
    }

    public function addProductView(Request $request)
    {
        $shopId = session('shop_id');
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        $brands = Brand::where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::where('shop_id','=',$shopId)
            ->orderBy('tag_name')
            ->get();
        $poptions = Poptions::where('shop_id','=',$shopId)
            ->get();
        return response()->json([
            'location' => $location,
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
            'poptions' => $poptions
        ]);
    }

    public function productUpdate(Request $request,$product_id)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $product = Product::find($product_id);
            $product->title = $request->get("title");
            $product->body_html = $request->get("body_html");
            $product->short_description = $request->get('short_description');
            $product->product_status = $request->get('product_status');
            $product->publish_status = $request->get('publish_status');
            $product->product_type_id = $request->get('product_type_id');
            $product->brand_id = $request->get('brand_id');
            $product->tags = $request->get('tags');
            $baseSlug = Str::slug(strtolower($request['handle']) ?? $request['title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('shop_id','=',$shopId)
                ->where('handle', '=', $slug)
                ->where('product_id', '!=', $request['product_id'])
                ->exists())
            {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $request['handle'] = $slug;
            $product->handle = $request['handle'];
            $product->meta_title = $request->get("meta_title") ?? $request->get("title");
            $product->meta_desc = $request->get("meta_desc") ?? $request->get("title");
            if ($request->hasFile('featured_image')) {
                $image = $request->file('featured_image');
                $filename = "product_image-" . time() . uniqid() . '.png';
                $img = Image::make($image->getRealPath())->resize(1000, 1000, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $fpath = 'products/' . $filename;
                Storage::disk('s3')->put($fpath, (string)$img->encode());
                $product->featured_image = $fpath;
            }
            $product->save();
            app(SmartCategoryService::class)->syncProduct($product);
            $submittedVariantIds = collect($request->vitems)->pluck('variant_id')->filter()->toArray();
            // Delete missing variants ONCE
            $deletedVariants = Variant::where('product_id', $product->product_id)
                ->where('shop_id', $shopId)
                ->whereNotIn('variant_id', $submittedVariantIds)
                ->pluck('variant_id');
            Stock::whereIn('variant_id', $deletedVariants)->delete();
            Variant::whereIn('variant_id', $deletedVariants)->delete();

            foreach ($request->vitems as $index => $nvar) {
                $variantImagePath = null;
                if ($request->hasFile("vitems.$index.variantImage")) {
                    $image = $request->file("vitems.$index.variantImage");
                    $filename = "variant_image-" . time() . uniqid() . '.png';
                    $img = Image::make($image)->resize(1000, 1000, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $vpath = 'products/' . $filename;
                    Storage::disk('s3')->put($vpath, (string)$img->encode());
                    $variantImagePath = $vpath;
                }
                if (!empty($nvar['variant_id'])) {
                    $isdefault = (isset($nvar['optname']) && is_array($nvar['optname']) && count($nvar['optname']) > 0);

                    $variantData = [
                        'sku' => $nvar['sku'],
                        'price' => $nvar['price'] ?? 0.00,
                        'compareprice' => $nvar['compareprice'] ?? 0.00,
                        'costprice' => $nvar['costprice'] ?? 0.00,
                        'barcode' => $nvar['barcode'] ?? null,
                        'istax' => $nvar['istax'] ?? 1,
                        'isdefault' => $isdefault ?? false,
                        'weight' => $nvar['weight'] ?? 0.50,
                        'options' => $nvar['optname'] ?? null,
                        'option_values' => $nvar['optvalue'] ?? null,
                        'product_id' => $product->product_id,
                        'shop_id' => $shopId,
                    ];
                    if ($variantImagePath) {
                        $variantData['variant_image'] = $variantImagePath;
                    }
                    $variant = Variant::updateOrCreate(
                        ['variant_id' => $nvar['variant_id']],
                        $variantData
                    );
                    if (isset($nvar['stock'])) {
                        Stock::where('variant_id', $nvar['variant_id'])->update([
                            'quantity' => $nvar['stock'],
                        ]);
                    }
                } else {
                    $variantData = [
                        'sku' => $nvar['sku'],
                        'price' => $nvar['price'] ?? 0.00,
                        'compareprice' => $nvar['compareprice'] ?? 0.00,
                        'costprice' => $nvar['costprice'] ?? 0.00,
                        'barcode' => $nvar['barcode'] ?? null,
                        'istax' => $nvar['istax'] ?? 1,
                        'isdefault' => $nvar['isdefault'] ?? false,
                        'weight' => $nvar['weight'] ?? 0.50,
                        'options' => $nvar['optname'] ?? null,
                        'option_values' => $nvar['optvalue'] ?? null,
                        'product_id' => $product->product_id,
                        'shop_id' => $shopId
                    ];
                    if ($variantImagePath) {
                        $variantData['variant_image'] = $variantImagePath;
                    }
                    $varnew = Variant::create($variantData);

                    if (isset($nvar['stock'])) {
                        $location_id = Location::where('shop_id', '=', $shopId)->first()->location_id;
                        Stock::create([
                            'quantity' => $nvar['stock'],
                            'location_id' => $location_id,
                            'variant_id' => $varnew['variant_id'],
                            'product_id' => $product->product_id,
                            'shop_id' => $shopId
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product_id' => $product_id,
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkProductUpdate(Request $request)
    {
        $selectedIds = $request->selectedIds;
        DB::beginTransaction();
        try {
            if($selectedIds){
                foreach($selectedIds as $selectedId){
                    if(isset($request->bulkprice)){
                        $variant = Variant::where('variant_id',$selectedId)->update([
                            'price' => $request->bulkprice,
                            'compareprice' => $request->bulkcompare ?? 0.0,
                            'costprice' => $request->bulkcost ?? 0.0,
                        ]);
                    }

                    if (isset($request->bulkqty)) {
                        Stock::where('variant_id', $selectedId)->update([
                            'quantity' => $request->bulkqty,
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to Update product',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function deleteProduct(Request $request,$product_id)
    {
        if($request->mtype == 'softdelete'){
            $product = Product::findOrFail($product_id);
            $product->delete(); // soft delete
            return response()->json([
                'success' => true,
                'message' => 'Product Archived successfully',
                'product_id' => $product_id,
                'product' => $product,
            ]);
        } elseif ($request->mtype == 'restore'){
            $product = Product::withTrashed()->where('product_id', $product_id)->firstOrFail();

            if ($product->trashed()) {
                $product->restore();

                return response()->json([
                    'success' => true,
                    'message' => 'Product restored successfully.',
                    'product_id' => $product_id,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Product is not archived.',
                'product_id' => $product_id,
            ]);
        } elseif ($request->mtype == 'perdelete'){
            $product = Product::withTrashed()->where('product_id', $product_id)->firstOrFail();
            $product->forceDelete(); // Permanently deletes the record
            return response()->json([
                'success' => true,
                'message' => 'Product permanently deleted.',
                'product_id' => $product_id,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ]);
        }
    }

    public function bulkDeleteProduct(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        $mtype = $request->input('mtype');
        if (!in_array($mtype, ['softdelete', 'restore', 'perdelete'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action type.',
            ], 400);
        }
        if (empty($productIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No product IDs provided.',
            ], 400);
        }
        $results = [];
        foreach ($productIds as $productId) {
            try {
                if ($mtype === 'softdelete') {
                    $product = Product::findOrFail($productId);
                    $product->delete();
                    $results[] = ['product_id' => $productId,'status' => "Archived"];
                }
                if ($mtype === 'restore') {
                    $product = Product::withTrashed()->where('product_id', $productId)->firstOrFail();
                    if ($product->trashed()) {
                        $product->restore();
                        $results[] = ['product_id' => $productId,'status' => "Restored"];
                    } else {
                        $results[] = ['product_id' => $productId,'status' => "Not Restored"];
                    }

                }
                if ($mtype === 'perdelete') {
                    $product = Product::withTrashed()->where('product_id', $productId)->firstOrFail();
                    $product->forceDelete();
                    $results[] = ['product_id' => $productId,'status' => "Permanently Deleted"];
                }
            } catch (\Exception $e) {
                $results[] = ['product_id' => $productId,'status' => 'error','error' => $e->getMessage()];
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Products Updated',
            'results' => $results,
        ]);
    }

    public function bulkAddTag(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        if($request->mtype == 'add'){
            foreach ($productIds as $productId) {
                $product = Product::withTrashed()->find($productId);
                $newTags = $request->tagstoadd ?? [];
                $existingTags = $product->tags ?? [];
                $tagsToAdd = array_filter($newTags, function ($tag) use ($existingTags) {
                    return !in_array($tag, $existingTags);
                });
                if (!empty($tagsToAdd)) {
                    $product->tags = array_merge($existingTags, $tagsToAdd);
                    $product->save();
                    app(SmartCategoryService::class)->syncProduct($product);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Product tags Updated',
            ]);
        } elseif($request->mtype == 'remove'){
            foreach ($productIds as $productId) {
                $product = Product::withTrashed()->find($productId);
                if (!$product) continue;
                $tagsToRemove = $request->tagstoremove ?? [];
                $existingTags = $product->tags ?? [];

                $updatedTags = array_filter($existingTags, function ($tag) use ($tagsToRemove) {
                    return !in_array($tag, $tagsToRemove);
                });
                $product->tags = array_values($updatedTags);
                $product->save();
                app(SmartCategoryService::class)->syncProduct($product);
            }
            return response()->json([
                'success' => true,
                'message' => 'Product tags Removed',
            ]);
        }
    }

    public function addProductNew(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $product = new Product();
            $product->title = $request->title;
            $baseSlug = Str::slug(strtolower($request->title), '-');
            $slug = $baseSlug;
            $counter = 1;
            while (Product::where('shop_id', $shopId)->where('handle', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $product->handle = $slug;
            $product->body_html = $request->body_html;
            $product->short_description = $request->short_description;
            $product->product_status = $request->product_status;
            $product->publish_status = $request->publish_status;
            $product->product_type_id = $request->product_type_id;
            $product->brand_id = $request->brand_id;
            $product->tags = $request->tags;
            if($request->hasFile('featured_image')){
                $image = $request->file('featured_image');
                $filename = "product_image-".time().uniqid().'.png';
                $img = Image::make($image->getRealPath())->resize(1000,1000,function ($constraint) {
                    $constraint->aspectRatio();
                });
                $fpath = 'products/'.$filename;
                Storage::disk('s3')->put($fpath, (string) $img->encode());
                $product->featured_image = $fpath;
            }
            $product->thirdparty_id = time().uniqid();
            $product->shop_id = $shopId;
            $product->meta_title = $request->meta_title ?? $request->title;
            $product->meta_desc = $request->meta_desc ?? $request->title;
            $product->save();
            app(SmartCategoryService::class)->syncProduct($product);
            foreach ($request->variants as $index => $nvar) {
                $variant = new Variant();
                $variant->sku = $nvar['sku'];
                $variant->price = $nvar['price'] ?? 0.00;
                $variant->compareprice = $nvar['compareprice'] ?? null;
                $variant->costprice = $nvar['costprice'] ?? null;
                $variant->barcode = $nvar['barcode'];
                if($request->hasFile("variants.$index.variantImage")){
                    $image = $request->file("variants.$index.variantImage");
                    $filename = "variant_image-".time().uniqid().'.png';
                    $img = Image::make($image->getRealPath())->resize(1000,1000,function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $vpath = 'products/'.$filename;
                    Storage::disk('s3')->put($vpath, (string) $img->encode());
                    $variant->variant_image = $vpath ?? null;
                }
                $variant->istax = $nvar['istax'] ?? 1;
                $variant->isdefault = $nvar['isdefault'] ?? 1;
                $variant->weight = $nvar['weight'] ?? 0.5;
                $variant->options = $nvar['optname'] ?? null;
                $variant->option_values = $nvar['optvalue'] ?? null;
                $variant->shop_id = $shopId;
                $variant->product_id = $product->product_id;
                $variant->save();

                $location = Location::where('shop_id','=',$shopId)->first();
                $stock = new Stock();
                $stock->quantity = $nvar['stock'];
                $stock->location_id = $location['location_id'];
                $stock->variant_id = $variant->variant_id;
                $stock->product_id = $product->product_id;
                $stock->shop_id = $shopId;
                $stock->save();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Product added successfully',
                'shop_id' => $shopId,
                'product_id' => $product->product_id,
                'product' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrCreateHighlights(Request $request)
    {
        $highs = Highlight::updateOrCreate(
            [
                'highlight_id'=>$request['highlight_id'],
                'feature_id'=>$request['feature_id'],
                'product_id' =>$request['product_id']
            ],
            [
                'fvalue' => $request['fvalue'],
                'position' => $request['position'],
                'feature_id'=>$request['feature_id'],
                'product_id' =>$request['product_id']
            ]
        );
        if($highs){
            return response()->json([
                'success' => true,
                'message' => "Added / Updated Successfully"
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Not Added or Updated"
            ]);
        }
    }

    public function deleteHighlight(Request $request)
    {
        $high = Highlight::findOrFail($request->highlight_id);
        $high->delete();
        return response()->json(['success'=>true,'message' => "Highlight Deleted Success"]);
    }

    public function addAdminProductReview(Request $request)
    {
        $shopId = session('shop_id');
        Proreview::updateOrCreate(
            [
                'product_id'=>$request->product_id,
                'reviewer_id'=>$request->reviewer_id
            ],
            [
                'review_title'=>$request->review_title,
                'review_text'=>$request->review_text,
                'rating'=>$request->rating,
                'product_id'=>$request->product_id,
                'reviewer_id'=>$request->reviewer_id,
                'review_status'=> $request->review_status ?? "verified",
                'shop_id'=>$shopId,
                'created_at'=>$request->created_at ?? now(),
            ],
        );

        return response()->json(['success' => true,'message' => 'Review added successfully']);
    }

    public function updateUnitPack(Request $request)
    {
        $shopId = session('shop_id');
        $unitPackQty = null;
        if($request->unit_pack_qty <= 0){
            $unitPackQty = null;
        } else {
            $unitPackQty = $request->unit_pack_qty;
        }
        $product = Product::where('product_id',$request->product_id)
            ->where('shop_id', $shopId)
            ->update([
                'unit_name' => $request->unit_name ?? "Unit",
                'unit_pack_qty' => $unitPackQty ?? null,
            ]);
        return response()->json([
            'success' => true,
            'message' => 'Unit Pack updated successfully',
            'product' => $product,
        ]);
    }

    public function saveTierPricing(Request $request)
    {
        $shopId = session('shop_id');
        // ✅ Basic validation
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'tiers' => 'nullable|array',
        ]);

        // ✅ Validate tiers only if present
        if (!empty($request->tiers)) {
            $request->validate([
                'tiers.*.min_qty' => 'required|integer|min:2',
                'tiers.*.price' => 'required|numeric|min:0.01',
                'tiers.*.pricing_type' => 'required|in:fixed,percentage',
            ]);
        }
        $product = Product::where('product_id', $request->product_id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$product) {return response()->json(['success' => false, 'message' => 'Product not found']);}

        // ✅ Normalize tiers (important for empty case)
        $tiers = collect($request->input('tiers', []))
            ->map(function ($t) {
                return [
                    'min_qty' => (int) $t['min_qty'],
                    'price' => (float) $t['price'],
                    'pricing_type' => $t['pricing_type'],
                ];
            })
            ->sortBy('min_qty')
            ->values()
            ->toArray(); // 👈 important (avoid collection issues in loops)

        // ✅ Backend validation (sequence + price logic)
        for ($i = 0; $i < count($tiers); $i++) {
            $current = $tiers[$i];
            if ($i > 0) {
                $prev = $tiers[$i - 1];
                if ($current['min_qty'] <= $prev['min_qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tier " . ($i + 1) . " qty must be greater than previous"
                    ]);
                }
                if ($current['price'] >= $prev['price']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tier " . ($i + 1) . " price must be less than previous"
                    ]);
                }
            }
        }

        DB::transaction(function () use ($tiers, $product, $shopId) {

            // delete old
            ProductPriceTier::where('product_id', $product->product_id)->delete();

            // ✅ If empty → just return (means remove all tiers)
            if (empty($tiers)) {
                return;
            }
            // ✅ Insert new tiers
            foreach ($tiers as $tier) {
                ProductPriceTier::create([
                    'product_id' => $product->product_id,
                    'shop_id' => $shopId,
                    'min_qty' => $tier['min_qty'],
                    'price' => $tier['price'],
                    'pricing_type' => $tier['pricing_type'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => empty($tiers)
                ? 'Tier pricing removed successfully'
                : 'Tier pricing saved successfully',
            'product'=> $product,
        ]);


    }
}
