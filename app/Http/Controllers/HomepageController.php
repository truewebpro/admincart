<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnrichesWithLoyaltyPoints;
use App\Models\Blog;
use App\Models\Cat;
use App\Models\Homepage;
use App\Models\HomePromo;
use App\Models\HomePromoItem;
use App\Models\Product;
use App\Models\Proreview;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    use EnrichesWithLoyaltyPoints;
    public function homeHeroSections(Request $request)
    {
        $shopId = $request->shop_id;
        $sections = Cache::remember(
            CacheKeys::heroSections($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $homepage = Homepage::with('herosections','faqs')
                    ->where('shop_id','=',$shopId)
                    ->first();
//                return $homepage?->herosections;
                return $homepage;
            }
        );

        return response()->json([
            'success' => true,
            'hsections' => $sections['herosections'] ?? [],
            'faqs' => $sections['faqs'] ?? [],
        ]);
    }

    public function homePromos(Request $request)
    {
        $shopId = $request->shop_id;
        $promos = HomePromo::with('items')
            ->where('shop_id','=',$shopId)
            ->where('status','=',true)
            ->get();
        return response()->json([
            'success' => true,
            'promos' => $promos,
        ]);
    }

    public function homeLazySections(Request $request)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::lazySections($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $homepage = Homepage::with('lsections')
                    ->where('shop_id','=',$shopId)
                    ->first();
                if (!$homepage) {
                    return [];
                }
                $asections = $homepage->lsections ?? collect();
                foreach ($asections as &$section) {
                    if ($section->stype_slug !== 'featured_products') {
                        continue;
                    }
                    $sectionJson = $section->section_json;
                    $stypeJson = $sectionJson['stype_json'];
                    $catId = $stypeJson['cat_id'];
                    $cat = Cat::where('cat_id', $catId)->first();
                    $products = Product::with(['variants.astock', 'brand', 'ptype'])
                        ->where('shop_id','=',$shopId)
                        ->withCount('reviews')->withAvg('reviews','rating')
                        ->whereIn('product_id', function ($query) use ($catId) {
                            $query->select('product_id')
                                ->from('catpros')
                                ->where('cat_id', $catId);
                        })
                        ->limit($stypeJson['plimit'] ?? 12)
                        ->get();
                    $allVariants = $products->flatMap(fn ($product) => $product->variants);
                    $this->attachLoyaltyPointsToMany($shopId, $allVariants);
                    $stypeJson['cat_slug'] = $cat->cat_slug ?? null;
                    $stypeJson['cat_image'] = $cat->cat_image ?? null;
                    $stypeJson['catpros'] = $products ?? [];
                    $sectionJson['stype_json'] = $stypeJson;
                    $section->section_json = $sectionJson;
                }
                return $asections;
            }
        );

        return response()->json([
            'success' => true,
            'hsections' => $data,
        ]);

    }

    public function homeSections(Request $request)
    {
        $shopId = $request->shop_id;
        $homepage = Homepage::with(['hsections'])
            ->where('shop_id','=',$shopId)->first();
        $sectionsWithExtras = [];
        foreach ($homepage->hsections as $section){
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
        $blogs = Blog::where('shop_id','=',$shopId)->where('blog_status','=','active')
            ->orderBy('created_at','DESC')->limit(6)->get();
        $reviews = Proreview::where('shop_id','=',$shopId)
            ->join('reviewers','reviewers.id','=','proreviews.reviewer_id')
            ->orderBy('proreviews.rating','desc')
            ->orderBy('proreviews.created_at','desc')
            ->select('proreviews.*','reviewers.first_name','reviewers.last_name')
            ->limit(6)->get();
        return response()->json([
            'hsections'=> $sectionsWithExtras,
            'reviews' => $reviews,
            'blogs' => $blogs ?? null,
        ],200);
    }

    //Admin Routes
    public function getHomePagePromo(Request $request)
    {
        $shopId = session('shop_id');

        $promo = HomePromo::firstOrCreate(
            [
                'shop_id' => $shopId,
                'homepage_id' => $request->homepage_id,
                'position' => $request->position,
            ],
            [
                'style' => 'style1',
            ]
        );

        if ($promo->items()->count() === 0) {
            $promo->items()->create([
                'media_type' => 'icon',
                'media_value' => 'mdi:account',
                'title' => 'Free Delivery',
                'subtext' => 'Orders over £50',
                'sort_order' => 1,
            ]);
        }

        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo added successfully.',
            'promo' => $promo,
        ]);
    }

    public function updatePromo(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'id' => 'required|integer',
            'heading' => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'style' => 'required|string|max:50',
            'bg_color' => 'required|string|max:20',
            'title_color' => 'required|string|max:20',
            'subtext_color' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        $promo = HomePromo::where('shop_id', $shopId)
            ->where('id', $data['id'])
            ->firstOrFail();

        $promo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Promo updated successfully.',
            'promo' => $promo,
        ]);
    }

    public function addPromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'home_promo_id' => 'required|integer',
            'media_type' => 'required|in:icon,svg,image',
            'media_value' => 'required|string',
            'title' => 'required|string|max:255',
            'subtext' => 'nullable|string',
        ]);

        $promo = HomePromo::where('shop_id', $shopId)
            ->where('id', $data['home_promo_id'])
            ->firstOrFail();

        $sortOrder = ($promo->items()->max('sort_order') ?? 0) + 1;
        $data['media_value'] = trim($data['media_value']);
        $promo->items()->create([
            'media_type' => $data['media_type'],
            'media_value' => $data['media_value'],
            'title' => $data['title'],
            'subtext' => $data['subtext'],
            'sort_order' => $sortOrder,
        ]);

        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item added successfully.',
            'promo' => $promo,
        ]);

    }

    public function updatePromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'id' => 'required|integer',
            'media_type' => 'required|in:icon,svg,image',
            'media_value' => 'required|string',
            'title' => 'required|string|max:255',
            'subtext' => 'nullable|string',
        ]);

        $item = HomePromoItem::whereHas('promo', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        })
            ->where('id', $data['id'])
            ->firstOrFail();

        $item->update($data);
        $promo = $item->promo;
        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item updated successfully.',
            'promo' => $promo,
        ]);

    }

    public function deletePromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate(['id' => 'required|integer',]);

        $item = HomePromoItem::whereHas('promo', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        })
            ->where('id', $data['id'])
            ->firstOrFail();

        if ($item->promo->items()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one promo item is required.'
            ], 422);
        }

        $promo = $item->promo;
        $item->delete();
        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item deleted successfully.',
            'promo' => $promo,
        ]);
    }
}
