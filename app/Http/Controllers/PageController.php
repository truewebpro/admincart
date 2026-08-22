<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Cat;
use App\Models\Page;
use App\Models\Product;
use App\Models\Section;
use App\Models\Stype;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function getPageBySlug(Request $request,$shopname,$page_slug)
    {
        $shopId = $request->shop_id;
        $page = Cache::remember(
            CacheKeys::page($shopId,$page_slug),
            now()->addHours(12),
            function () use ($shopId, $page_slug) {
                $page = Page::with('faqs')->where('shop_id','=',$shopId)
                    ->where('page_slug','=',$page_slug)
                    ->first();
                if (!$page) {
                    return null;
                }
                $pagesections = Section::where('sectionable_id','=',$page->page_id)
                    ->where('sectionable_type',Page::class)
                    ->join('stypes','stypes.stype_id','=','sections.stype_id')
                    ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                        'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
                    ->where('sections.section_status','=','show')
                    ->orderBy('sections.sort_order', 'ASC')
                    ->get();
                $sectionsWithExtras = [];
                foreach ($pagesections as $section){
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
                $page->asections = $sectionsWithExtras;
                return $page;
            }
        );
        return response()->json([
            'status' => !is_null($page),
            'page' => $page,
        ]);

    }

    //Admin Routes
    public function getAdminPagesList()
    {
        $shopId = session('shop_id');
        $pages = Page::where('shop_id', $shopId)->get();
        if($pages->isEmpty()){
            $defaultPages = [
                [
                    'page_title' => 'Contact Us',
                    'page_slug'  => 'contact',
                    'page_description' => 'Contact Shop Description...',
                ],
                [
                    'page_title' => 'About Us',
                    'page_slug'  => 'about',
                    'page_description' => 'About Shop Description ...',
                ],
            ];
            foreach ($defaultPages as &$page) {
                $page['page_status'] = 'active';
                $page['shop_id'] = $shopId;
                $page['created_at'] = now();
                $page['updated_at'] = now();
            }

            Page::insert($defaultPages);
            $pages = Page::where('shop_id', $shopId)->get();
            return response()->json([
                'success' => true,
                'pages' => $pages,
            ]);
        }
        return response()->json([
            'success' => true,
            'pages' => $pages,
        ]);
    }

    public function getAdminPageById($page_id)
    {
        $shopId = session('shop_id');
        $page = Page::with('sections','faqs')->where('shop_id', $shopId)
            ->where('page_id',$page_id)
            ->first();
        if($page){
            $categories = Cat::where('shop_id','=',$shopId)
                ->select('cat_id','cat_name','cat_slug','cat_image')
                ->get();
            $brands = Brand::where('shop_id','=',$shopId)
                ->select('brand_id','brand_name','brand_slug','brand_image')
                ->get();
            $ctypes = ['review_slider','blog_slider'];
            $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
            return response()->json([
                'success' => true,
                'page' => $page,
                'stypes'=> $stypes,
                'categories'=>$categories,
                'brands'=>$brands,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'page' => null,
            ]);
        }
    }

    public function addPage(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $baseSlug = Str::slug($request['page_slug'] ?? $request['page_title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Page::where('shop_id','=',$shopId)->where('page_slug', '=', $slug)->exists()){
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $pageSlug = $slug;
            $page = new Page();
            $page->page_title = $request->page_title;
            $page->page_description = $request->page_description;
            $page->page_status = $request->page_status;
            $page->meta_title = $request->meta_title;
            $page->meta_description = $request->meta_description;
            $page->page_slug = $pageSlug;
            $page->shop_id = $shopId;
            $page->save();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Page added Successfully.',
                'page' => $page,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAdminPage(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $page = Page::where('shop_id', $shopId)->where('page_id', $request->page_id)->firstOrFail();
            if (!empty($request->page_slug) && $request->page_slug !== $page->page_slug) {
                $baseSlug = Str::slug($request->page_slug);
                $slug = $baseSlug;
                $counter = 1;

                while (Page::where('shop_id', $shopId)
                    ->where('page_id', '!=', $page->page_id)
                    ->where('page_slug', $slug)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $pageSlug = $slug;
            } else {

                $pageSlug = $page->page_slug;
            }
            $page->page_title = $request->page_title;
            $page->page_description = $request->page_description;
            $page->page_status = $request->page_status;
            $page->meta_title = $request->meta_title;
            $page->meta_description = $request->meta_description;
            $page->page_slug = $pageSlug;
            $page->shop_id = $shopId;
            $page->update();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Page has been updated.',
                'page' => $page,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAdminPage(Request $request)
    {
        $shopId = session('shop_id');
        $page = Page::where('shop_id','=',$shopId)->where('page_id', $request->page_id)->firstOrFail();
        $page->delete();
        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully.',
        ]);
    }
}
