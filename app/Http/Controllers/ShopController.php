<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnrichesWithLoyaltyPoints;
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
use App\Models\HomePromo;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Policy;
use App\Models\Poptions;
use App\Models\Preference;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\Searchbrand;
use App\Models\Searchcat;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\Setting;
use App\Models\ShipMethod;
use App\Models\Shop;
use App\Models\ShopPaymentMethod;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ShopController extends Controller
{
    use EnrichesWithLoyaltyPoints;

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
        $data = Cache::remember(
            CacheKeys::homeMetas($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $homemetas = Preference::where('shop_id',$shopId)->first();
                if (!$homemetas) {
                    return null;
                }
                $shop = Shop::with('setting','subscribe_section')
                    ->where('shop_id', $shopId)
                    ->first();
                $company = BusinessShop::with('business')
                    ->where('shop_id', $shopId)
                    ->first();
                $shop['business'] = $company?->business;
                return [
                    'homemetas' => $homemetas,
                    'shop' => $shop,
                ];
            }
        );
        return response()->json([
            'status' => 200,
            'message' => 'Home Metas',
            'homemetas' => $data['homemetas'] ?? null,
            'shop' => $data['shop'] ?? null,
        ]);

    }

    public function shopReviewSummary(Request $request)
    {
        $shopId = $request->shop_id;
        $summary = Cache::remember(
            CacheKeys::shopReviewSummary($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return [
                    'reviews_count' => Proreview::where('shop_id', $shopId)->count(),
                    'reviews_avg_rating' => round(Proreview::where('shop_id', $shopId)->avg('rating') ?? 0, 1),
                ];
            }
        );
        return response()->json([
            'status' => true,
            'summary' => $summary,
        ]);
    }

    public function getShopSetting(Request $request)
    {
        $shopId = $request->shop_id;
        $setting = Cache::remember(
            CacheKeys::shopSettings($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Setting::where('shop_id', $shopId)->first();
            }
        );
        return response()->json([
            'success' => true,
            'message' => 'Shop Setting',
            'setting' => $setting,
        ]);
    }

    public function cartSections(Request $request,$shopname)
    {
        $shopId = $request->shop_id;

        $cartpage = Cache::remember(
            CacheKeys::cartPage($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $cartpage = Cartpage::where('shop_id','=',$shopId)->first();
                if (!$cartpage) {
                    return null;
                }
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
                        $allVariants = $products->flatMap(fn ($product) => $product->variants);
                        $this->attachLoyaltyPointsToMany($shopId, $allVariants);
                        $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                        $sectionArray['section_json']['stype_json']['catpros'] = $products;
                    }
                    $sectionsWithExtras[] = $sectionArray;
                }
                $cartpage->asections = $sectionsWithExtras;
                return $cartpage;
            }
        );

        return response()->json([
            'status' => !is_null($cartpage),
            'cartpage' => $cartpage,
        ]);
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
    }

    public function shippingOptions(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $smethods = Cache::remember(
            CacheKeys::shippingMethods($shopId),
            now()->addHours(12),
            function () use ($shopId){
                return ShipMethod::with('courier')
                    ->where('shop_id','=',$shopId)
                    ->get();
            }
        );
        return response()->json([
            'status' => true,
            'smethods' => $smethods,
        ]);
    }

    public function paymentOptions(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $pmethods = Cache::remember(
            CacheKeys::paymentMethods($shopId),
            now()->addHours(12),
            function () use ($shopId){
                return ShopPaymentMethod::where('shop_id','=',$shopId)
                    ->where('payment_status','=','active')
                    ->orderBy('sort_order','ASC')
                    ->get();
            }
        );
        return response()->json([
            'status' => true,
            'pmethods' => $pmethods,
        ]);
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

    public function getAnnouncements(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $announcements = Cache::remember(
            CacheKeys::announcements($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Announcement::where('shop_id','=',$shopId)
                    ->where('status','=','active')
                    ->first();
            }
        );

        return response()->json([
            'status' => $announcements !== null,
            'announcements' => $announcements,
        ]);
    }

    public function getSearchTags(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $searchtags = Cache::remember(
            CacheKeys::searchTags($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Searchtag::where('shop_id','=',$shopId)
                    ->where('status','=','active')
                    ->get();
            }
        );
        $searchbrands = Cache::remember(
            CacheKeys::searchBrands($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Searchbrand::where('shop_id','=',$shopId)
                    ->where('status','=','active')
                    ->get();
            }
        );
        $searchcats = Cache::remember(
            CacheKeys::searchCats($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Searchcat::where('shop_id','=',$shopId)
                    ->where('status','=','active')
                    ->get();
            }
        );

        return response()->json([
            'status' => $searchtags->isNotEmpty(),
            'searchtags' => $searchtags,
            'searchbrands' => $searchbrands,
            'searchcats' => $searchcats,
        ]);
    }

    public function getFooter(Request $request,$shopname)
    {
        $shopId = $request->shop_id;

        $footer = Cache::remember(
            CacheKeys::footer($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $footer = Footer::where('shop_id','=',$shopId)
                    ->first();
                if (!$footer) {
                    return null;
                }
                $company = BusinessShop::with('business')
                    ->where('shop_id','=',$shopId)
                    ->first();
                $footer['company'] = $company?->business;
                return $footer;
            }
        );

        return response()->json([
            'status' => !is_null($footer),
            'footer' => $footer,
        ]);
    }

    public function htmlSitemap(Request $request,$shopname)
    {
        $shopId = $request->shop_id;

        $data = Cache::remember(
            CacheKeys::htmlSitemap($shopId),
            now()->addDays(7),
            function () use ($shopId) {
                return [
                    "products" => Product::where('shop_id','=',$shopId)
                        ->select('products.title','handle')
                        ->orderBy('products.handle','asc')
                        ->get(),

                    "cats" => Cat::where('shop_id','=',$shopId)
                        ->select('cats.cat_name','cat_slug')
                        ->orderBy('cats.cat_slug','asc')
                        ->get(),

                    "brands" => Brand::where('shop_id','=',$shopId)
                        ->select('brands.brand_name','brand_slug')
                        ->orderBy('brands.brand_slug','asc')
                        ->get(),

                    "pages" => Page::where('shop_id','=',$shopId)
                        ->select('pages.page_title','pages.page_slug')
                        ->orderBy('page_slug','asc')
                        ->get(),
                ];
            }
        );

        return response()->json([
            'status' => true,
            'cats' => $data['cats'],
            'brands' => $data['brands'],
            'products' => $data['products'],
            'pages' => $data['pages'],
        ]);
    }
}
