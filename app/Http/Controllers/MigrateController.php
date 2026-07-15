<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\BusinessShop;
use App\Models\Cat;
use App\Models\Page;
use App\Models\Policy;
use App\Models\Poptions;
use App\Models\Preference;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Shop;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class MigrateController extends Controller
{
    public function migrateShops(Request $request)
    {
        $shops = Shop::get();
        foreach ($shops as $shop) {
            $preference = Preference::where('shop_id', $shop->shop_id)->first();
            $shop['logo_url'] = $preference?->shop_logo ?? null;
            $shop['favicon_url'] = $preference?->favicon ?? null;
            $bShop = BusinessShop::where('shop_id', $shop->shop_id)->first();
            $business = Business::find($bShop?->business_id);
            $shop['business'] = $business ?? null;
        }
        return response()->json($shops);

    }

    public function migrateUsers(Request $request)
    {
        $users = User::with('shopUsers')
            ->where('users.name', '!=','superadmin')
            ->select('users.id','users.name','users.email','users.password','users.created_at','users.updated_at')
            ->get()
            ->makeVisible('password');
        ;
        return response()->json($users);
    }

    public function migrateBrands(Request $request)
    {
        $brands = Brand::join('shops','shops.shop_id','=','brands.shop_id')
            ->select('brands.*','shops.shop_slug')
            ->get();
        return response()->json($brands);
    }

    public function migrateTags(Request $request)
    {
        $tags = Tag::join('shops','shops.shop_id','=','tags.shop_id')
            ->select('tags.*','shops.shop_slug')
            ->get();
        return response()->json($tags);
    }

    public function migrateProductTypes(Request $request)
    {
        $productTypes = ProductType::join('shops','shops.shop_id','=','product_types.shop_id')
            ->select('product_types.*','shops.shop_slug')
            ->get();
        return response()->json($productTypes);
    }

    public function migratePoptions(Request $request)
    {
        $poptions = Poptions::join('shops','shops.shop_id','=','poptions.shop_id')
            ->select('poptions.*','shops.shop_slug')
            ->get();
        return response()->json($poptions);

    }

    public function migrateBlogs(Request $request)
    {
        $blogs = Blog::join('shops','shops.shop_id','=','blogs.shop_id')
            ->join('users','users.id','=','blogs.user_id')
            ->select('blogs.*','shops.shop_slug','users.email as author_email')
            ->get();
        return response()->json($blogs);
    }

    public function migratePages(Request $request)
    {
        $pages = Page::join('shops','shops.shop_id','=','pages.shop_id')
            ->select('pages.*','shops.shop_slug')
            ->get();
        return response()->json($pages);
    }

    public function migratePolicies(Request $request)
    {
        $policies = Policy::join('shops','shops.shop_id','=','policies.shop_id')
            ->select('policies.*','shops.shop_slug')
            ->get();
        return response()->json($policies);
    }

    public function migrateCats(Request $request)
    {
        $cats = Cat::join('shops','shops.shop_id','=','cats.shop_id')
            ->select('cats.*','shops.shop_slug')
            ->get();
        return response()->json($cats);
    }

    public function migrateProducts(Request $request)
    {
        $products = Product::join('shops','shops.shop_id','=','products.shop_id')
            ->select('products.*','shops.shop_slug')
            ->get();
        return response()->json($products);
    }
}
