<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\BusinessShop;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Catpro;
use App\Models\Customer;
use App\Models\Footer;
use App\Models\Homepage;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Policy;
use App\Models\Poptions;
use App\Models\Preference;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\Setting;
use App\Models\ShipMethod;
use App\Models\Shop;
use App\Models\ShopPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ShopController extends Controller
{
    public function shopSetting(Request $request)
    {
        $shopId = $request->shop_id;
        $preference = Preference::where('shop_id',$shopId)->first();
        $company = BusinessShop::with('business','shop','preference')->where('shop_id',$shopId)->first();
        $shop = Shop::where('shop_id',$shopId)->first();
        $shop['domains'] = [
            'subdomain' => $shop->subdomain,
            'maindomain' => $shop->maindomain,
            ];
        $shop['branding'] = ['logo' => "https://cdn.truewebcart.com/".($preference->shop_logo ?? 'logo.png')];
        $shop['theme'] = [
            'colors' => [
                'primary' => "#ed1b24",
                'secondary' => "#0e0e0e",
                'background' => "#ffffff",
                'foreground' => "#171717",
            ],
        ];
        $shop['settings'] = [
            'currency' => $shop->currency ?? 'GBP',
        ];
        $shop['company'] = $company ?? null;
        return response()->json($shop);
    }

    public function homeMetas(Request $request)
    {
        $shopId = $request->shop_id;
        $homemetas = Preference::where('shop_id',$shopId)->first();
        $shop = Shop::with('setting','subscribe_section')->where('shop_id', $homemetas->shop_id)->first();
        $company = BusinessShop::where('shop_id', $homemetas->shop_id)->first();
        $shop['business'] = $company->business ?? null;
        return response()->json([
            'status' => 200,
            'message' => 'Home Metas',
            'homemetas' => $homemetas,
            'shop' => $shop,
        ]);
    }

    public function getShopSetting(Request $request)
    {
        $shopId = $request->shop_id;
        $setting = Setting::where('shop_id', $shopId)->first();
        return response()->json([
            'success' => true,
            'message' => 'Shop Setting',
            'setting' => $setting,
        ]);
    }

    public function homeHeroSections(Request $request)
    {
        $shopId = $request->shop_id;
        $homepage = Homepage::with('herosections')->where('shop_id','=',$shopId)->first();
        return response()->json([
            'success' => true,
            'hsections' => $homepage->herosections ?? null,
        ]);
    }

    public function homeLazySections(Request $request)
    {
        $shopId = $request->shop_id;
        $homepage = Homepage::with('lsections')->where('shop_id','=',$shopId)->first();
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
                ->limit($sectionJson['plimit'] ?? 12)
                ->get();
            $stypeJson['cat_slug'] = $cat->cat_slug ?? null;
            $stypeJson['cat_image'] = $cat->cat_image ?? null;
            $stypeJson['catpros'] = $products ?? [];
            $sectionJson['stype_json'] = $stypeJson;
            $section->section_json = $sectionJson;
        }
        return response()->json([
            'success' => true,
            'hsections' => $homepage->lsections ?? null,
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

    public function cartSections(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $cartpage = Cartpage::where('shop_id','=',$shopId)->first();
        $cartsections = Section::where('sectionable_id','=',$cartpage->cartpage_id)
            ->where('sectionable_type',Cartpage::class)
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->orderBy('sections.sort_order', 'ASC')
            ->get();
        $sectionsWithExtras = [];
        foreach ($cartsections as $section){
            $sectionArray = $section->toArray();
            if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                $products = Product::with(['variants.astock', 'brand', 'ptype'])
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
        $cartpage->asections = $sectionsWithExtras;
        if($cartpage != null){
            return response()->json([
                'status' => true,
                'cartpage' => $cartpage,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'cartpage' => null,
            ]);
        }
    }

    public function shopPoptions(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $poptions = Poptions::where('shop_id','=',$shopId)
            ->select('option_id','option_name')
            ->get();
        return response()->json([
            'status' => true,
            'poptions' => $poptions,
        ]);
    }

    public function siteMap(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $urls = Cache::remember(
            "sitemap_urls_{$shopId}",
            now()->addHours(12),
            function () use ($shopId){
                $products = Product::where('shop_id', $shopId)
                    ->selectRaw("CONCAT('products/', handle) as handle, updated_at")
                    ->get();

                $categories = Cat::where('shop_id', $shopId)
                    ->selectRaw("CONCAT('collections/', cat_slug) as handle, updated_at")
                    ->get();

                $brands = Brand::where('shop_id', $shopId)
                    ->selectRaw("CONCAT('brands/', brand_slug) as handle, updated_at")
                    ->get();

                return $products
                    ->concat($categories)
                    ->concat($brands)
                    ->values();
            }
        );
        return response()->json([
            'status' => true,
            'allurls' => $urls,
        ]);
//        $products = Product::where('shop_id','=',$shopId)
//            ->select('handle','updated_at')->get()
//            ->map(fn ($item) => [
//                'handle' => 'products/' . $item->handle,
//                'updated_at' => $item->updated_at,
//            ]);
//        $cats = Cat::where('shop_id','=',$shopId)
//            ->select('cat_slug','updated_at')->get()
//            ->map(fn ($item) => [
//                'handle' => 'collections/' . $item->cat_slug,
//                'updated_at' => $item->updated_at,
//            ]);
//        $brands = Brand::where('shop_id','=',$shopId)
//            ->select('brand_slug','updated_at')->get()
//            ->map(fn ($item) => [
//                'handle' => 'brands/' . $item->brand_slug,
//                'updated_at' => $item->updated_at,
//            ]);
//        return response()->json([
//            'status' => true,
//            'allurls' => $cats
//                ->concat($brands)
//                ->concat($products)
//                ->values(),
////            'allurls' => [...$cats,...$products,...$brands],
//        ],200);
    }

    public function shippingOptions(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $smethods = ShipMethod::with('courier')->where('shop_id','=',$shopId)->get();
        return response()->json([
            'status' => true,
            'smethods' => $smethods,
        ]);
    }

    public function paymentOptions(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $pmethods = ShopPaymentMethod::where('shop_id','=',$shopId)
            ->where('payment_status','=','active')
            ->orderBy('sort_order','ASC')
            ->get();
        return response()->json([
            'status' => true,
            'pmethods' => $pmethods,
        ]);
    }

    public function getPolicyBySlug(Request $request,$shopname,$policy_slug)
    {
        $shopId = $request->shop_id;
        $policy = Policy::where('shop_id','=',$shopId)->where('policy_slug','=',$policy_slug)->first();
        if($policy){
            return response()->json([
                'status' => true,
                'policy' => $policy,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'policy' => null,
            ]);
        }
    }

    public function getHomeMenu(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $menu = Menu::where('shop_id','=',$shopId)
            ->where('menu_slug','=','main_menu')
            ->first();
        if($menu != null){
            return response()->json([
                'status' => true,
                'mitems' => $menu->mitems,
                'shop_id' => $shopId,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'mitems' => null,
                'shop_id' => $shopId,
            ]);
        }
    }

    public function webSearch(Request $request,$shopname)
    {
        $shopID = $request->shop_id;
        $query = $request->q;
        $products = Product::where('shop_id', $shopID)
            ->where(function($q) use ($query) {
                foreach (explode(' ', $query) as $word) {
                    $q->where('title', 'LIKE', "%{$word}%");
                }
            })
            ->limit(10)->orderBy('updated_at','DESC')
            ->get(['product_id','title','handle','featured_image']);
        foreach ($products as $product) {
            $variants = $product->variants()->first();
            $product['price'] = $variants->price;
            $product['compareprice'] = $variants->compareprice;

        }
        return response()->json([
            'success' => !$products->isEmpty(),
            'products' => $products->isEmpty() ? null : $products
        ]);
    }

    public function resultSearchPage(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $query = $request->q;
        $products = Product::with('variants.astock','brand','ptype')
            ->withCount('reviews')->withAvg('reviews','rating')
            ->where('shop_id', $shopId)
            ->where(function($q) use ($query) {
                foreach (explode(' ', $query) as $word) {
                    $q->where('title', 'LIKE', "%{$word}%");
                }
            })
            ->limit(24)->orderBy('updated_at','DESC')
            ->get();

        $brands = Brand::query()
            ->select('brand_id', 'brand_name')
            ->where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::query()
            ->select('product_type_id', 'product_type_name')
            ->where('shop_id','=',$shopId)
            ->get();
        return response()->json([
            'success' => !$products->isEmpty(),
            'products' => $products->isEmpty() ? null : $products,
            'brands' => $brands->isEmpty() ? null : $brands,
            'ptypes' => $ptypes->isEmpty() ? null : $ptypes,
        ]);
    }

    public function exitingCustomer(Request $request)
    {
        $customer = Customer::where('email','=',$request->email)->exists();
        if($customer){
            return response()->json([
                'success' => true,
                'existsCustomer' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'existsCustomer' => false,
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:customers,email',
            'password' => 'required|min:5|confirmed',
        ]);
        $customer = Customer::where('email', $request->email)->first();
        $customer->password = Hash::make($request->password);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function getPageBySlug(Request $request,$shopname,$page_slug)
    {
        $shopId = $request->shop_id;
        $page = Page::where('shop_id','=',$shopId)->where('page_slug','=',$page_slug)->first();
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
                $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                $sectionArray['section_json']['stype_json']['catpros'] = $products;
            }
            $sectionsWithExtras[] = $sectionArray;
        }
        $page->asections = $sectionsWithExtras;
        if($page != null) {
            return response()->json([
                'status' => true,
                'page' => $page,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'page' => null,
            ]);
        }

    }

    public function getAnnouncements(Request $request,$shopname)
    {
        $announcements = Announcement::where('shop_id','=',$request->shop_id)
            ->where('status','=','active')->get();
        if($announcements != null){
            return response()->json([
                'status' => true,
                'announcements' => $announcements,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'announcements' => null,
            ]);
        }
    }

    public function getSearchTags(Request $request,$shopname)
    {
        $searchtags = Searchtag::where('shop_id','=',$request->shop_id)
            ->where('status','=','active')->get();
        if($searchtags != null){
            return response()->json([
                'status' => true,
                'searchtags' => $searchtags,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'searchtags' => null,
            ]);
        }
    }

    public function getFooter(Request $request,$shopname)
    {
        $footer = Footer::where('shop_id','=',$request->shop_id)->first();
        $company = BusinessShop::with('business')
            ->where('shop_id','=',$request->shop_id)
            ->first();
        $footer['company'] = $company->business ?? null;

        return response()->json([
            'status' => true,
            'footer' => $footer,
        ]);
    }

    public function htmlSitemap(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $products = Product::where('shop_id','=',$shopId)
            ->select('products.title','handle')->orderBy('products.handle','asc')->get();
        $cats = Cat::where('shop_id','=',$shopId)->select('cats.cat_name','cat_slug')
            ->orderBy('cats.cat_slug','asc')->get();
        $brands = Brand::where('shop_id','=',$shopId)->select('brands.brand_name','brand_slug')
            ->orderBy('brands.brand_slug','asc')->get();
        $pages = Page::where('shop_id','=',$shopId)->select('pages.page_title','pages.page_slug')->orderBy('page_slug','asc')->get();
        return response()->json([
            'status' => true,
            'cats' => $cats,
            'brands' => $brands,
            'products' => $products,
            'pages' => $pages,
        ],200);
    }
}
