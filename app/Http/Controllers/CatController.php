<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Catpro;
use App\Models\Product;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatController extends Controller
{
    public function allCats(Request $request)
    {
        $shopId = $request->shop_id;
        $shopcats = Cache::remember(
            CacheKeys::cats($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $acats = Cat::where('shop_id','=',$shopId)
                    ->where('cat_status','=','Active')
                    ->get();
                foreach ($acats as $shopcat){
                    $productId = Catpro::where('cat_id', $shopcat->cat_id)->value('product_id');
                    $shopcat['proimage'] = $productId
                        ? Product::where(
                            'product_id',
                            $productId
                        )->value('featured_image')
                        : null;
                }
                return $acats;
            }
        );
            return response()->json([
                'status' => true,
                'cats' => $shopcats,
            ],200);

    }

    public function getAllCats(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $shopcats = Cache::remember(
            CacheKeys::cats($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $acats = Cat::where('shop_id','=',$shopId)
                    ->where('cat_status','=','Active')
                    ->select('cat_id','cat_slug','cat_name','cat_status','cat_image','shop_id')
                    ->get();
                foreach ($acats as $acat){
                    $proimage = null;
                    $productId = Catpro::where('cat_id', '=', $acat->cat_id)->value('product_id');
                    $acat['proimage'] = $productId
                        ? Product::where(
                            'product_id',
                            $productId
                        )->value('featured_image')
                        : null;
                }
                return $acats;
            }
        );
        return response()->json([
            'status' => true,
            'cats' => $shopcats,
        ],200);

    }

    public function getCatBySlug(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat  = Cache::remember(
            CacheKeys::cat($shopId,$slug),
            now()->addHours(12),
            function() use ($shopId,$slug){
                return Cat::with('rcats','faqs')
                    ->where('shop_id','=',$shopId)
                    ->where('cat_slug','=',$slug)
                    ->first();
            }
        );
        if (!$cat) {
            return response()->json([
                'status' => false,
                'type' => null,
                'slug' => $slug,
                'cat' => null,
            ]);
        }

        $catId = $cat->cat_id;
        $alpros = Product::query()
            ->select('product_id','title','handle','featured_image','product_status','product_type_id',
                'brand_id','tags')->with(['variants.astock', 'brand', 'ptype'])
            ->where('shop_id','=',$shopId)
            ->withCount('reviews')->withAvg('reviews','rating')
            ->whereIn('product_id', function ($query) use ($catId) {
                $query->select('product_id')
                    ->from('catpros')
                    ->where('cat_id', $catId);
            })
            ->paginate(24);

        $cat['catpros'] = $alpros;
        return response()->json([
            'status' => true,
            'type' => 'Category',
            'slug' => $slug,
            'cat' => $cat,
        ]);
    }

    public function getCatSections(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat  = Cache::remember(
            CacheKeys::catSectionsData($shopId,$slug),
            now()->addHours(12),
            function() use ($shopId,$slug){
                return Cat::with('csections')
                    ->where('shop_id','=',$shopId)
                    ->where('cat_slug','=',$slug)
                    ->first();
            }
        );

        if (!$cat) {
            return response()->json([
                'status' => false,
                'sections' => null,
                'slug' => $slug,
            ]);
        }
        $sectionsWithExtras = Cache::remember(
            CacheKeys::catSections($shopId,$slug),
            now()->addHours(12),
            function () use ($shopId,$cat){
                $sectionsWithExtras = [];
                foreach ($cat->csections as $section){
                    $sectionArray = $section->toArray();
                    if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                        $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                        $catSlug = Cat::where('cat_id', $catId)->value('cat_slug');
                        $products = Product::query()
                            ->select('product_id','title','handle','featured_image','product_status','product_type_id',
                                'brand_id','tags')->with(['variants.astock', 'brand', 'ptype'])
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
                return $sectionsWithExtras;
            }
        );

        return response()->json([
            'status' => true,
            'sections' => $sectionsWithExtras,
            'slug' => $slug,
        ]);
    }

    public function getCategory(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat = Cat::with('rcats','csections','faqs')
            ->where('shop_id','=',$shopId)
            ->where('cat_slug','=',$slug)
            ->first();
        if($cat != null){
            $catId = $cat->cat_id;
            $alpros = Product::with(['variants.astock', 'brand', 'ptype'])
                ->withCount('reviews')->withAvg('reviews','rating')
                ->whereIn('product_id', function ($query) use ($catId) {
                    $query->select('product_id')
                        ->from('catpros')
                        ->where('cat_id', $catId);
                })->inRandomOrder()
                ->get();
            $cat['catpros'] = $alpros;
            $sectionsWithExtras = [];
            foreach ($cat->csections as $section){
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
            $cat->asections = $sectionsWithExtras;
            return response()->json([
                'status' => true,
                'type' => "Category",
                'slug'=> $slug,
                'cat' => $cat,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'type' => null,
                'slug'=> $slug,
                'cat' => null,
            ]);
        }
    }
}
