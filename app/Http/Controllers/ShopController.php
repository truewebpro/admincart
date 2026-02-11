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
use App\Models\Proreview;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\ShipMethod;
use App\Models\Shop;
use App\Models\ShopPaymentMethod;
use Illuminate\Http\Request;

class ShopController extends Controller
{
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
            ->orderBy('created_at','DESC')->get();
        $reviews = Proreview::where('shop_id','=',$shopId)
            ->join('reviewers','reviewers.id','=','proreviews.reviewer_id')
            ->orderBy('proreviews.rating','desc')
            ->orderBy('proreviews.created_at','desc')
            ->select('proreviews.*','reviewers.first_name','reviewers.last_name')
            ->limit(12)->get();
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

    public function allBrands(Request $request)
    {
        $shopId = $request->shop_id;
        $brands = Brand::withCount('product')->where('shop_id','=',$shopId)
            ->where('brand_status','=','Active')
            ->orderBy('brand_name','ASC')->get();
        return response()->json([
            'status' => true,
            'brands' => $brands
        ],200);

    }

    public function prosByBrand(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $brand = Brand::where('shop_id','=',$shopId)
            ->where('brand_slug','=',$brand_slug)
            ->first();
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
        if($brand != null){
            $brand_products = Product::with('brand','variants.astock')
                ->withCount('reviews')->withAvg('reviews','rating')
                ->where('brand_id','=',$brand->brand_id)
                ->where('product_status','=','Active')
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

    public function allCats(Request $request)
    {
        $shopId = $request->shop_id;
        $shopcats = Cat::where('shop_id','=',$shopId)
            ->where('cat_status','=','Active')->get();
        foreach ($shopcats as $shopcat){
            $productId = Catpro::where('cat_id','=',$shopcat->cat_id)->first();
            if($productId != null){
                $product = Product::where('product_id','=',$productId->product_id)->first();
                $proimage = $product->featured_image;
            }
            $shopcat['proimage'] = $proimage ?? null;

        }
        return response()->json([
            'status' => true,
            'cats' => $shopcats,
        ],200);

    }

    public function getCatorProduct(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $catpros = Cat::where('shop_id','=',$shopId)
            ->where('cat_slug','=',$slug)
            ->first();
        if($catpros != null){
            $catId = $catpros->cat_id;
            $alpros = Product::with(['variants.astock', 'brand', 'ptype'])
                ->withCount('reviews')->withAvg('reviews','rating')
                ->whereIn('product_id', function ($query) use ($catId) {
                    $query->select('product_id')
                        ->from('catpros')
                        ->where('cat_id', $catId);
                })
                ->get();
            $catpros['cat_products'] = $alpros;
            return response()->json([
                'status' => true,
                'type' => "Category",
                'catpros' => $catpros,
                'sproduct'=> null,
            ],200);
        } else {
            $sproduct = Product::where('shop_id','=',$shopId)
                ->with('brand','ptype','variants.astock','highs')
                ->withCount('reviews')->withAvg('reviews','rating')
                ->where('handle','=',$slug)
                ->first();
            if($sproduct != null){
                $related_products = Product::with('brand','ptype','variants.astock')
                    ->withCount('reviews')->withAvg('reviews','rating')
                    ->where('shop_id','=',$shopId)
                    ->where('brand_id','=',$sproduct->brand_id)
                    ->whereNotIn('products.product_id', [$sproduct->product_id])
                    ->where('product_status','=','Active')
                    ->limit(12)->get();
                $addons = Product::with('brand','ptype','variants.astock')
                    ->withCount('reviews')->withAvg('reviews','rating')
                    ->where('shop_id','=',$shopId)
                    ->where('product_type_id','=',$sproduct->product_type_id)
                    ->whereNotIn('products.product_id', [$sproduct->product_id])
                    ->where('product_status','=','Active')
                    ->inRandomOrder()->limit(12)->get();
                return response()->json([
                    'status' => true,
                    'type' => "Product",
                    'slug'=> $slug,
                    'catpros' => null,
                    'sproduct'=> [
                        'single_product'=>$sproduct,
                        'addons'=>$addons,
                        'related_products'=>$related_products,
                    ]
                ],200);
            } else {
                return response()->json([
                    'status' => false,
                    'type' => null,
                    'slug'=> $slug,
                    'catpros' => null,
                    'sproduct'=> null,
                ]);
            }
        }
    }

    public function getCategory(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat = Cat::with('rcats','csections')->where('shop_id','=',$shopId)->where('cat_slug','=',$slug)->first();
        if($cat != null){
            $catId = $cat->cat_id;
            $alpros = Product::with(['variants.astock', 'brand', 'ptype'])
                ->withCount('reviews')->withAvg('reviews','rating')
                ->whereIn('product_id', function ($query) use ($catId) {
                    $query->select('product_id')
                        ->from('catpros')
                        ->where('cat_id', $catId);
                })
                ->get();
            $cat['catpros'] = $alpros;
            $sectionsWithExtras = [];
            foreach ($cat->csections as $section){
                $sectionArray = $section->toArray();
                if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                    $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                    $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                    $products = Product::with(['variants.astock', 'brand', 'ptype'])
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

    public function getProduct(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $sproduct = Product::with('variants.astock','brand','ptype','psections','highs','reviews','specifics')
            ->withCount('reviews')->withSum('reviews','rating')
            ->withAvg('reviews','rating')
            ->where('shop_id','=',$shopId)
            ->where('handle','=',$slug)->first();
        $previews = $sproduct->reviews;
        $sproduct['stars5'] = $previews->whereIn('rating',5)->count();
        $sproduct['stars4'] = $previews->whereIn('rating',4)->count();
        $sproduct['stars3'] = $previews->whereIn('rating',3)->count();
        $sproduct['stars2'] = $previews->whereIn('rating',2)->count();
        $sproduct['stars1'] = $previews->whereIn('rating',1)->count();
        if($sproduct != null){
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
        $products = Product::where('shop_id','=',$shopId)
            ->select('handle','updated_at')->get();
        foreach ($products as $product) {
            $product['handle'] = 'products/'.$product->handle;
        }
        $cats = Cat::where('shop_id','=',$shopId)
            ->select('cat_slug as handle','updated_at')->get();
        foreach ($cats as $cat) {
            $cat['handle'] = 'collections/'.$cat->handle;
        }
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_slug as handle','updated_at')->get();
        foreach ($brands as $brand){
            $brand['handle'] = 'brands/'.$brand->handle;
        }
        return response()->json([
            'status' => true,
            'allurls' => [...$cats,...$products,...$brands],
//            'products' => $products,
//            'cats' => $cats,
//            'brands' => $brands,
        ],200);
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

    public function allBlogs(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $blogs = Blog::where('shop_id','=',$shopId)->orderBy('created_at','DESC')->get();
        return response()->json([
            'status' => true,
            'blogs' => $blogs,
        ]);
    }

    public function getBlogbySlug(Request $request,$shopname,$blog_slug)
    {
        $shopId = $request->shop_id;
        $blog = Blog::where('shop_id','=',$shopId)->where('blog_slug','=',$blog_slug)->first();
        $blogsections = Section::where('sectionable_id','=',$blog->blog_id)
            ->where('sectionable_type',Blog::class)
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->orderBy('sections.sort_order', 'ASC')
            ->get();
        $sectionsWithExtras = [];
        foreach ($blogsections as $section){
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
        $blog->asections = $sectionsWithExtras;
        $rblogs = Blog::where('shop_id','=',$shopId)
            ->whereNotIn('blogs.blog_id', [$blog->blog_id])
            ->orderBy('created_at','DESC')->get();
        if($blog != null){
            return response()->json([
                'status' => true,
                'blog' => $blog,
                'rblogs' => $rblogs,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'blog' => null,
            ]);
        }
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
