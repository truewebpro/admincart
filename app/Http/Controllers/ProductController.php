<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Cat;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Variant;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
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
}
