<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Cat;
use App\Models\Product;
use App\Models\Section;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    public function allBrands(Request $request)
    {
        $shopId = $request->shop_id;
        $brands = Cache::remember(
            CacheKeys::brands($shopId),
            now()->addHours(12),
            function() use ($shopId) {
                return Brand::query()->select(
                    'brand_id', 'brand_name', 'brand_slug', 'brand_image','brand_status','shop_id'
                )->withCount('product')->where('shop_id','=',$shopId)
                    ->where('brand_status','=','Active')
                    ->orderBy('brand_name','ASC')->get();
            });
        return response()->json([
            'status' => true,
            'brands' => $brands
        ],200);

    }

    public function getBrandBySlug(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $brand = Cache::remember(
            CacheKeys::brand($shopId, $brand_slug),
            now()->addHours(12),
            fn() => Brand::where('shop_id', $shopId)
                ->where('brand_slug', $brand_slug)
                ->first()
        );
        if(!$brand){
            return response()->json([
                'status' => false,
                'brand' => null,
            ]);
        }
        $brand_products = Product::query()
            ->select(
                'product_id',
                'title',
                'handle',
                'featured_image',
                'product_status',
                'product_type_id',
                'brand_id',
                'tags'
            )
            ->with('brand','variants.astock')
            ->withCount('reviews')
            ->withAvg('reviews','rating')
            ->where('brand_id', $brand->brand_id)
            ->where('product_status', 'Active')
            ->paginate(12);
//        $brand_products = Cache::remember(
//            CacheKeys::brandProducts($shopId, $brand_slug),
//            now()->addHours(6),
//            function () use ($brand) {
//                return Product::query()
//                    ->select(
//                        'product_id',
//                        'title',
//                        'handle',
//                        'featured_image',
//                        'product_status',
//                        'product_type_id',
//                        'brand_id',
//                        'tags'
//                    )
//                    ->with('brand','variants.astock')
//                    ->withCount('reviews')
//                    ->withAvg('reviews','rating')
//                    ->where('brand_id', $brand->brand_id)
//                    ->where('product_status', 'Active')
//                    ->paginate(12);
//            }
//        );

        return response()->json([
            'status' => true,
            'brand' => $brand,
            'brand_products' => $brand_products ?? null,
        ]);
    }

    public function getBrandSections(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::brandSections($shopId, $brand_slug),
            now()->addHours(12),
            function() use ($shopId, $brand_slug) {
                $brand = Brand::with('bsections')
                    ->where('shop_id','=',$shopId)
                    ->where('brand_slug','=',$brand_slug)
                    ->first();
                if(!$brand){
                    return response()->json([
                        'status' => false,
                        'sections' => null,
                        'brand_slug' => $brand_slug,
                    ]);
                }
                $sectionsWithExtras = [];
                return response()->json([
                    'status' => true,
                    'sections' => $sectionsWithExtras,
                    'brand_slug' => $brand_slug,
                ]);
            }
        );

        return response()->json($data);
    }

    public function prosByBrand(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::brandPage($shopId, $brand_slug),
            now()->addHours(6),
            function() use ($shopId, $brand_slug) {
                $brand = Brand::where('shop_id','=',$shopId)
                    ->where('brand_slug','=',$brand_slug)
                    ->first();

                if($brand != null){
                    $brandsections = Section::where('sectionable_id','=',$brand->brand_id)
                        ->where('sectionable_type',Brand::class)
                        ->join('stypes','stypes.stype_id','=','sections.stype_id')
                        ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                            'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
                        ->where('sections.section_status','=','show')
                        ->orderBy('sections.sort_order', 'ASC')
                        ->get();
                    $sectionsWithExtras = [];
                    foreach ($brandsections as $section){
                        $sectionArray = $section->toArray();
                        if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                            $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                            $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                            $products = Product::with(['variants.astock', 'brand', 'ptype'])
                                ->where('shop_id','=',$shopId)
                                ->withCount('reviews')->withAvg('reviews','rating')
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
                    $brand->asections = $sectionsWithExtras;
                    $brand_products = Product::with('brand','variants.astock')
                        ->withCount('reviews')->withAvg('reviews','rating')
                        ->where('brand_id','=',$brand->brand_id)
                        ->where('product_status','=','Active')
                        ->inRandomOrder()
                        ->get();
                    $total_reviews = 0;
                    $review_averages = [];
                    foreach ($brand_products as $product){
                        $productArray = $product->toArray();
                        $reviewcount = $product->reviews_count;
                        if($reviewcount < 1){ $reviewcount = 2; }
                        $total_reviews += $reviewcount;
                        $average = $product->reviews_avg_rating ?? 5;
                        $review_averages[] = $average;
                    }
                    $brand['total_reviews'] = $total_reviews;
                    $brand['total_average'] = count($review_averages) > 0
                        ? array_sum($review_averages) / count($review_averages)
                        : 5;
                    return response()->json([
                        'status' => true,
                        'brand' => $brand,
                        'brand_products' => $brand_products,
                    ],200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Brand not found'
                    ],404);
                }
            }
        );
        return response()->json($data);
    }
}
