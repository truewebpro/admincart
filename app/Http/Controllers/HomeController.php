<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Mail\OrderPlacedMail;
use App\Models\Announcement;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\BusinessShop;
use App\Models\Cart;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Catpro;
use App\Models\Coupon;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerShop;
use App\Models\Feature;
use App\Models\Featuredcat;
use App\Models\Featuredgrid;
use App\Models\Footer;
use App\Models\Highlight;
use App\Models\Homebanner;
use App\Models\Homepage;
use App\Models\Location;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Page;
use App\Models\Policy;
use App\Models\Poptions;
use App\Models\Preference;
use App\Models\Product;
use App\Models\Productgrid;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\RelatedCat;
use App\Models\Reviewer;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\Setting;
use App\Models\ShipMethod;
use App\Models\Shop;
use App\Models\Shopbycat;
use App\Models\ShopPaymentMethod;
use App\Models\ShopUser;
use App\Models\Specific;
use App\Models\Stock;
use App\Models\Stype;
use App\Models\SubscribeSection;
use App\Models\Tag;
use App\Models\User;
use App\Models\Variant;
use App\Services\SmartCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'resolve.admin.shop']);
    }

    public function index()
    {
        return view('sadmin');
    }

    public function getShopBusiness()
    {
        $shopId = session('shop_id');
        $business = BusinessShop::with('business','shops')->where('shop_id', $shopId)
            ->first();
        if($business){
            $setting = Setting::firstOrCreate(
                ['shop_id' => $shopId],
                [
                    'shop_id' => $shopId,
                    'min_checkout_price' => 0.99,
                    'vat_included' => true,
                    'hide_price' => false,
                ]
            );
            return response()->json([
                'success' => true,
                'data' => $business,
                'business' => $business['business'],
                'shops' => $business['shops'],
                'shop_id'=> $shopId,
                'setting' => $setting,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'data' => null,
                'business' => null,
                'shops' => Shop::where('shop_id', $shopId)->get(),
                'shop_id'=> $shopId,
            ]);
        }

    }

    public function updateShopBusiness(Request $request)
    {
        $shopId = session('shop_id');
        if($request->business_id){
            $business = Business::findOrFail($request->business_id);
            $business->business_name = $request->business_name;
            $business->address_line1 = $request->address_line1;
            $business->address_line2 = $request->address_line2;
            $business->region = $request->region;
            $business->postcode = $request->postcode;
            $business->country = $request->country;
            $business->reg_no = $request->reg_no;
            $business->vat_no = $request->vat_no;
            $business->email = $request->email;
            $business->phone = $request->phone;
            $business->whatsapp = $request->whatsapp;
            $business->save();

            return response()->json([
                'success' => true,
                'data' => $business,
                'message' => 'Business updated successfully',
            ]);
        } else {
            $business = new Business();
            $business->business_name = $request->business_name;
            $business->address_line1 = $request->address_line1;
            $business->address_line2 = $request->address_line2;
            $business->region = $request->region;
            $business->postcode = $request->postcode;
            $business->country = $request->country;
            $business->reg_no = $request->reg_no;
            $business->vat_no = $request->vat_no;
            $business->email = $request->email;
            $business->phone = $request->phone;
            $business->whatsapp = $request->whatsapp;
            $business->save();

            $business_shop = BusinessShop::firstOrCreate(
                ['business_id' => $business->business_id,'shop_id' => $shopId],
                ['business_id' => $business->business_id,'shop_id' => $shopId],
            );

            return response()->json([
                'success' => false,
                'message' => 'Business updated',
            ]);
        }

    }

    public function updateShopSetting(Request $request)
    {
        $shopId = session('shop_id');
        $setting = Setting::find($request->setting_id);
        $setting->shop_id = $shopId;
        $setting->min_checkout_price = $request->min_checkout_price;
        $setting->vat_included = $request->vat_included;
        $setting->hide_price = $request->hide_price;
        $setting->update();
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    public function shopProductExport(Request $request)
    {
        $shopId = session('shop_id');
        return Excel::download(new ProductsExport($shopId), 'products.csv');
    }

    public function shopProductImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);
        $shopId = session('shop_id');

        Excel::import(new ProductsImport($shopId), $request->file('file'));

        return response()->json(['message' => 'Products imported successfully']);
    }

    public function getShopDetail()
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();
        if($shop){
            return response()->json([
                'success' => true,
                'message' => 'Shop detail',
                'shop' => $shop,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found',
                'shop' => null,
            ]);
        }

    }

    public function allUsers()
    {
        $users = User::get();
        return response()->json([
            'users' => $users,
        ],200);
    }

    public function getHimageUploadUrl(Request $request)
    {
        $file = $request->file('image');
        $mfile = $request->file('mimage');
        $filename = ($request->stype ?? 'section')."_".uniqid().'.png';
        if($request->stype === 'browse_collection'){
            $img = Image::make($file->getRealPath())->resize(750, 300, function ($constraint) {
                $constraint->aspectRatio();
            });
        } elseif ($request->stype === 'slideshow'){
            if($file){
                $img = Image::make($file->getRealPath())->resize(1800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            if($mfile){
                $img = Image::make($mfile)->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

        } elseif ($request->stype === 'featured_collections'){
            $img = Image::make($file->getRealPath())->resize(800, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });
        } elseif ($request->stype === 'popular_range'){
            $img = Image::make($file->getRealPath())->resize(100, 100, function ($constraint) {
                $constraint->aspectRatio();
            });
        } elseif ($request->stype === 'image_with_text'){
            $img = Image::make($file->getRealPath())->resize(700, 500, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            $img = Image::make($file->getRealPath())->resize(1200, 1200, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        $path = 'sections/'.$filename;
        Storage::disk('s3')->put($path,(string)$img->encode());

//            $mpath = 'sections/'.$filename;
//            Storage::disk('s3')->put($mpath,(string)$mimg->encode());
        return response()->json([
            'success' => true,
            'url' => $path,
        ]);
    }

    public function getVideoUploadUrl(Request $request)
    {
        $file = $request->file('video');
        $extension = $file->getClientOriginalExtension();
        $filename = 'section_video_'.uniqid().'.'.$extension;
        $vpath = 'sections/'.$filename;
        Storage::disk('s3')->put($vpath,file_get_contents($file->getRealPath()));
        return response()->json([
            'success' => true,
            'url' => $vpath,
        ]);
    }

    public function getHomePage()
    {
        $shopId = session('shop_id');
        $homepage = Homepage::with('sections')->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'title' => "Homepage",
                'slug' => "/",
                'status' => 'draft',
                'shop_id' => $shopId,
            ]
        );
        $categories = Cat::where('shop_id','=',$shopId)
            ->select('cat_id','cat_name','cat_slug','cat_image')
            ->get();
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_id','brand_name','brand_slug','brand_image')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'Home page',
            'homepage' => $homepage,
            'stypes'=> Stype::all(),
            'categories'=>$categories,
            'brands'=>$brands,
        ]);
    }

    public function addNewHomeSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'homepage_id' => 'required|exists:homepages,homepage_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Homepage::class,
            'sectionable_id' => $validatedData['homepage_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Homepage::class)
                    ->where('sectionable_id', $request->homepage_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function updateAddedSection(Request $request,$section_id)
    {
        $validated = $request->validate([
            'section_json'   => 'required|array',
            'sort_order'     => 'nullable|integer',
            'section_status' => 'nullable|in:show,hide',
        ]);
        $section = Section::findOrFail($section_id);
        $section->section_json   = $validated['section_json'];
        $section->sort_order     = $validated['sort_order'] ?? $section->sort_order;
        $section->section_status = $validated['section_status'] ?? $section->section_status;
        $section->save();
        return response()->json([
            'success' => true,
            'message' => "Updated Successfully",
            'section' => $section->fresh()
        ]);
    }

    public function moveSectionUp($section_id)
    {
        $section = Section::findOrFail($section_id);
        $above = Section::where('sectionable_id',$section->sectionable_id)
            ->where('sectionable_type',$section->sectionable_type)
            ->where('sort_order', $section->sort_order - 1)
            ->first();
        if($above){
            $above->sort_order = $section->sort_order;
            $above->save();
            $section->sort_order = $section->sort_order - 1;
            $section->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Section moved up successfully',
            'section' => $section->fresh()
        ]);
    }

    public function moveSectionDown($section_id)
    {
        $section = Section::findOrFail($section_id);
        $below = Section::where('sectionable_id',$section->sectionable_id)
            ->where('sectionable_type',$section->sectionable_type)
            ->where('sort_order', $section->sort_order + 1)
            ->first();
        if($below){
            $below->sort_order = $section->sort_order;
            $below->save();
            $section->sort_order = $section->sort_order + 1;
            $section->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Section moved down successfully',
            'section' => $section->fresh()
        ]);
    }

    public function hideOrShowSection($section_id)
    {
        $section = Section::findOrFail($section_id);
        $existingStatus = Section::where('section_status','=',$section->section_status)->first();
        if($existingStatus->section_status == "show"){
            $section->section_status = "hide";
            $section->save();
            return response()->json([
                'success' => true,
                'message' => 'Section hide successfully',
                'section' => $section->fresh()
            ]);
        } elseif ($existingStatus->section_status == "hide"){
            $section->section_status = "show";
            $section->save();
            return response()->json([
                'success' => true,
                'message' => 'Section shown successfully',
                'section' => $section->fresh()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Section notupdate',
                'section' => $section->fresh()
            ]);
        }

    }

    public function deleteAddedSection($section_id)
    {
        $section = Section::findOrFail($section_id);
        $sectionable_id = $section->sectionable_id;
        $sectionable_type = $section->sectionable_type;
        $deletedSortOrder = $section->sort_order;
        $section->delete();

        Section::where('sectionable_id', $sectionable_id)
            ->where('sectionable_type', $sectionable_type)
            ->where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        return response()->json([
            'success' => true,
            'message' => "Deleted Successfully",
        ]);
    }

    public function dashboard(Request $request)
    {
        $shop = session('shop');
        $shopId = session('shop_id');
//        $shopId = $request->get('shop_id');
        $shops = Shop::where('shop_id','=',$shopId)
            ->get();
        $orders = Order::withCount('orderItems')
            ->where('shop_id','=',$shopId)
            ->orderByDesc('created_at')
            ->get();
        $paidCount = $orders->where('payment_status','paid')->sum('order_total');
        $pendingCount = $orders->where('payment_status','pending')->sum('order_total');
        $products = Product::where('shop_id','=',$shopId)->get();
        $variants = Variant::where('shop_id','=',$shopId)->get();
        $shopusers = ShopUser::where('shop_id','=',$shopId)->get();
        $customers = Customer::orderByDesc('customers.created_at')
            ->join('customer_shops', 'customer_shops.customer_id', '=', 'customers.customer_id')
            ->where('customer_shops.shop_id','=',$shopId)
            ->get();

        $tpros = OrderItem::join('products','products.product_id','=','order_items.product_id')
            ->select('order_items.*','products.shop_id','products.title','products.featured_image')
            ->where('products.shop_id','=',$shopId)->limit(15)->get();
        $users = User::get();
        return response()->json([
            'success' => true,
            'users' => auth()->user(),
            'shopId'=> $shopId,
            'shops' => $shops,
            'orders' => $orders,
            'pendingCount' => $pendingCount,
            'paidCount' => $paidCount,
            'shippedOrd' => $orders->where('payment_status','=','shipped')->count(),
            'pendingOrd' => $orders->where('payment_status','=','pending')->count(),
            'ordersCount' => $orders->count(),
            'productsCount' => $products->count(),
            'variantsCount' => $variants->count(),
            'shopUsers' => $shopusers->count(),
            'tpros' => $tpros,
            'customersNew' => $customers->count(),
        ]);
    }

    public function allOrders()
    {
        $shop = session('shop');
        $shopId = session('shop_id');
        $orders = Order::withTrashed()->withCount('orderItems')
            ->where('shop_id','=',$shopId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function getOrderById($order_id)
    {
        $shop = session('shop');
        $shopId = session('shop_id');
        $order = Order::withTrashed()->with('orderItems.product','orderItems.variant','customer')
            ->withCount('orderItems')
            ->where('order_id','=',$order_id)
            ->where('shop_id','=',$shopId)
            ->first();
        if($order){
            $logs = OrderLog::where('order_id','=',$order_id)
                ->latest()->get();
            return response()->json([
                'success' => true,
                'order' => $order,
                'logs' => $logs
            ]);
        } else {
            return response()->json([
                'success' => false,
                'order' => null,
                'logs' => null
            ]);
        }
    }

    public function updateAdminOrder(Request $request)
    {
        $order = Order::withTrashed()->with('orderItems')->find($request['order_id']);
        if($order){
            if($request->mname === 'mark_as_paid'){
                $order->update(['payment_status' => 'paid','order_status' => 'processing']);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'payment_updated',
                    'description' => 'Payment status changed',
                    'meta' => [
                        'from' => 'unpaid',
                        'to' => 'paid'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order marked as paid",]);
            }
            if($request->mname === 'mark_as_picked'){
                $order->update(['fulfillment_status' => 'picked','order_status' => 'processing']);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Fulfillment status changed to picked',
                    'meta' => [
                        'from' => 'unfulfilled',
                        'to' => 'picked'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Items Picked",]);
            }
            if($request->mname === 'mark_as_packed'){
                $order->update(['fulfillment_status' => 'packed','order_status' => 'processing']);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Fulfillment status changed to packed',
                    'meta' => [
                        'from' => 'unfulfilled',
                        'to' => 'packed'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Items Packed",]);
            }
            if($request->mname === 'mark_as_archived'){
                $order->delete();
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Order status changed to archived',
                    'meta' => [
                        'from' => 'open',
                        'to' => 'archived'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order Archived",]);
            }
            if($request->mname === 'mark_as_restore'){
                $order->restore();
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Order status changed to restored',
                    'meta' => [
                        'from' => 'archived',
                        'to' => 'restored'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order Restored",]);
            }
            if($request->mname === 'send_invoice'){
                $customer = Customer::where('customer_id',$order->customer_id)->first();
                Mail::to($customer->email)
                    ->send(new OrderPlacedMail($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'invoice_sent',
                    'description' => 'Invoice email sent to customer',
                ]);
                return response()->json(['success' => true, 'message' => "Invoice Sent",]);
            }
        }
        if(!$order){
            return response()->json(['success'=>false,'message'=>'Order not found']);
        }
        return response()->json(['success'=>true,'order'=>$order]);
    }

    public function sendToSendCloudSingle(Request $request)
    {
//        $shop = session('shop');
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id','=',$shopId)->first();
        if($shop->shop_slug === 'vapecraze'){
            $publicKey = '09411732-d74b-4dde-8fb6-efa662ce7d8c';
            $secretKey = 'f7a2bdf1f81e4be2b53c888ea12ffc8c';
        } elseif ($shop->shop_slug === 'vapeportwholesale'){
            $publicKey = 'e45a1551-5849-40db-b82a-6a1a55c2596f';
            $secretKey = 'c81db58e751b466ebe4c9e08c6c45bbf';
        } else {
            $publicKey = '1636f116-3e02-4da5-a455-492b573b9976';
            $secretKey = '613b5889a7594244a1f4792d7cd62229';
        }

        $payload = $request->input();
        $response = Http::withBasicAuth($publicKey, $secretKey)
            ->post('https://panel.sendcloud.sc/api/v2/parcels', $payload);
        if($response->successful()){
            $parcelData = $response->json();
            $parcel = $parcelData['parcel'];
            $order = Order::where('order_number','=', $parcel['order_number'])->first();
            if($order){
                $order->update([
                    'parcel_id' => $parcel['id'],
                    'tracking_number' => $parcel['tracking_number'] ?? null,
                    'shipment_id' => $parcel['shipping_method'],
                    'shipment_name' => $parcel['shipment']['name'] ?? null,
                    'label_status' => 'pending',
                ]);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Order Label Staus updated in SendCloud Account',
                    'meta' => [
                        'from'=>'no_label',
                        'to'=>'pending',
                    ]
                ]);
                return $response->json([
                    'success' => true,
                    'message' => 'Order Created in send Cloud'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Sendcloud API error',
                'error' => $response->body()
            ], $response->status());
        }
    }

    public function allCarts(){
        $shop = session('shop');
        $shopId = session('shop_id');
        $carts = Cart::with('order')->withCount('cartItems')
            ->where('shop_id','=',$shopId)
            ->orderByDesc('created_at')
            ->get();
        return response()->json([
            'success' => true,
            'carts' => $carts
        ]);
    }

    public function getCartById($cart_id)
    {
        $shop = session('shop');
        $shopId = session('shop_id');
        $cart = Cart::with('order','cartItems.product','cartItems.variant','customer')->withCount('cartItems')
            ->where('cart_id','=',$cart_id)
            ->where('shop_id','=',$shopId)
            ->first();
        if($cart){
            return response()->json([
                'success' => true,
                'cart' => $cart,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'cart' => null,
            ]);
        }
    }

    public function allProducts()
    {
        $shopId = session('shop_id');
        $products = Product::withTrashed()->with('variants','brand','ptype')
            ->withCount('astock')
            ->withSum('astock','quantity')
            ->where('shop_id','=',$shopId)
            ->orderBy('created_at','desc')->get();
        foreach ($products as $pros){
            foreach ($pros['variants'] as $variant){
                if($variant->isdefault == 1){
                    $variant['isvariant'] = true;
                } else { $variant['isvariant'] = false;}
            }
        }
        $alltags = Tag::where('shop_id','=',$shopId)
            ->orderBy('tag_name','asc')->get();
        return response()->json([
            'users' => auth()->user(),
            'pcount'=> $products->count(),
            'products' => $products,
            'alltags'=> $alltags,
        ],200);
    }

    public function getProductbyId($product_id)
    {
        $shopId = session('shop_id');
        $product = Product::withTrashed()->where('product_id','=', $product_id)
            ->with('brand','ptype','variants.astock','sections','highs','specifics')
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
        $poptions = Poptions::where('shop_id','=',$shopId)->get();
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        return response()->json([
            'poptions' => $poptions,
            'location' => $location,
        ]);
    }

    public function addNewProductSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'product_id' => 'required|exists:products,product_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Product::class,
            'sectionable_id' => $validatedData['product_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Product::class)
                    ->where('sectionable_id', $request->product_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')
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

    public function allCats()
    {
        $shopId = session('shop_id');
        $cats = Cat::with('rules')->withCount('catpros')
            ->where('shop_id','=',$shopId)
            ->orderByDesc('updated_at')
            ->get();
        foreach ($cats as $cat){
            foreach ($cat->rules as $rule){
                $coltype = $rule->column;
                if($coltype === 'type'){
                    $rule->condition = ProductType::where('shop_id','=',$shopId)
                        ->where('product_type_id','=',$rule->condition)
                        ->first()->product_type_name;
                }
                if($coltype === 'vendor'){
                    $rule->condition = Brand::where('shop_id','=',$shopId)
                        ->where('brand_id','=',$rule->condition)
                        ->first()->brand_name;
                }
            }
        }

        return response()->json([
            'cats' => $cats
        ],200);
    }

    public function addCatView(Request $request)
    {
        $shopId = session('shop_id');
        $pros = Product::where('shop_id','=',$shopId)
            ->select('products.product_id','products.featured_image','products.title','products.product_status')
            ->get();
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        return response()->json([
            'pros' => $pros,
            'location' => $location,
        ]);
    }

    public function addCatNew(Request $request)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'nullable|string|max:255',
            'cat_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:manual,smart',
            'cat_rule' => 'nullable|in:and,or',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'rules' => 'nullable|array',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            if($request->hasFile('cat_image')){
                $file = $request->file('cat_image');
                $filename = 'category_image_'.uniqid().'.png';
                $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $cpath = 'category/'.$filename;
                Storage::disk('s3')->put($cpath,(string)$img->encode());
                $validated['cat_image'] = $cpath;
            }
            $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Cat::where('shop_id','=',$shopId)->where('cat_slug', '=', $slug)->exists()){
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $validated['cat_slug'] = $slug;

            $cat = Cat::create([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'cat_image' => $validated['cat_image'] ?? null,
                'cat_type' => $validated['cat_type'],
                'cat_rule' => $validated['cat_rule'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'shop_id' => $shopId,
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
            ]);

            if ($validated['cat_type'] === 'smart' && isset($validated['rules'])) {
                foreach ($validated['rules'] as $rule) {
                    $cat->rules()->create([
                        'column' => $rule['column'],
                        'relation' => $rule['relation'],
                        'condition' => $rule['condition'],
                        'cat_id' => $cat->cat_id,
                        'shop_id' => $shopId,
                    ]);
                }
            }
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                $validated['product_ids'] = array_unique($validated['product_ids']);
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => 99,
                        'shop_id' => $shopId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $catpro = Catpro::insert($insertData);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category added successfully',
                'cat_id' => $cat->cat_id,
                'cat' => $cat,
                'catpro' => $catpro,
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

    public function addNewCatSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'cat_id' => 'required|exists:cats,cat_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Cat::class,
            'sectionable_id' => $validatedData['cat_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Cat::class)
                    ->where('sectionable_id', $request->cat_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function filterProductIds(Request $request)
    {
        $shopId = session('shop_id');
//        $request->validate([
//            'rule.column' => 'required|string',
//            'rule.relation' => 'required|string',
//            'rule.condition' => 'required',
//        ]);
//        $column = $request->input('rule.column');
//        $relation = $request->input('rule.relation');
//        $condition = $request->input('rule.condition');
        $rules = $request->input('rules', []);
        $catRule = strtolower($request->input('cat_rule', 'and'));
        $query = Product::query()->where('shop_id','=',$shopId);
//        $method = $catRule === 'or' ? 'orWhere' : 'where';
        $query->where(function ($outer) use ($rules, $catRule) {
            foreach($rules as $rule){
                $outerQuery = function ($q) use ($rule) {
                    $column = $rule['column'];
                    $relation = $rule['relation'];
                    $condition = $rule['condition'];
                    switch ($column) {
                        case 'tag':
                            if ($relation === 'equals') {
                                $q->whereJsonContains('tags', $condition);
                            } elseif ($relation === 'not_equals') {
                                $q->where(function ($sub) use ($condition) {
                                    $sub->whereNull('tags')
                                        ->orWhereJsonDoesntContain('tags', $condition);
                                });
                            }
                            break;
                        case 'type':
                            $q->where('product_type_id',$relation === 'not_equals' ? '!=' : '=' , $condition);
                            break;
                        case 'vendor':
                            $q->where('brand_id',$relation === 'not_equals' ? '!=' : '=' , $condition);
                            break;
                        case 'title':
                            if ($relation === 'contains') {
                                $q->where('title', 'like', "%{$condition}%");
                            } elseif ($relation === 'not_contains') {
                                $q->where('title', 'not like', "%{$condition}%");
                            }
                            break;
                        case 'price':
                            $this->applyNumericFilter($q, 'product_price', $relation, $condition);
                            break;
                        case 'stock':
                            $this->applyNumericFilter($q, 'product_stock', $relation, $condition);
                            break;
                    }
                };
                if ($catRule === 'or') {
                    $outer->orWhere($outerQuery);
                } else {
                    $outer->where($outerQuery);
                }
            }
        });

        $productIds = $query->pluck('product_id');
        return response()->json([
            'productIds' => $productIds
        ]);
    }

    private function applyNumericFilter($query, $column, $relation, $value)
    {
        switch ($relation) {
            case 'equals':
                $query->where($column, '=', $value);
                break;
            case 'greater_than':
                $query->where($column, '>', $value);
                break;
            case 'less_than':
                $query->where($column, '<', $value);
                break;
            case 'not_equals':
                $query->where($column, '!=', $value);
                break;
        }
    }

    public function getCat($cat_id)
    {
        $shopId = session('shop_id');
        $cat = Cat::with(['rules','catpros', 'products','sections','rcats'])
            ->Where('cat_id','=',$cat_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $cat['productIds'] = $cat->catpros->pluck('product_id')->toArray();
        $cat['epros'] = $cat->products;
        $pros = Product::where('shop_id','=',$shopId)
            ->select('products.product_id','products.featured_image','products.title','products.product_status')
            ->get();
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        return response()->json([
            'cat' => $cat,
            'pros' => $pros,
            'location' => $location,
            'stypes' => $stypes,
        ],200);
    }

    public function addRelatedCat(Request $request)
    {
        $shopId = session('shop_id');
        if($request->memname === 'delete'){
            RelatedCat::where('related_cat_id','=',$request->related_cat_id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Related Category deleted successfully',
            ]);
        }
        if($request->hasFile('related_image')){
            $file = $request->file('related_image');
            $filename = 'related_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(400, 200, function ($constraint) {
                $constraint->aspectRatio();
            });
            $rpath = 'category/'.$filename;
            Storage::disk('s3')->put($rpath,(string)$img->encode());
        }
        RelatedCat::updateOrCreate(
            [
                'related_cat_id' => $request->related_cat_id,
                'cat_parent_id' => $request->cat_parent_id,
            ],
            [
                'cat_parent_id' => $request->cat_parent_id,
                'related_cat_title' => $request->related_cat_title,
                'cat_child_id' => $request->cat_child_id,
                'shop_id' => $shopId,
                'related_image' => $rpath ?? null,
            ]
        );
        return response()->json([
            'success' => true,
            'message' => 'Related Category added successfully',
        ]);
    }

    public function updateManualCatbyId(Request $request, $cat_id)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'required|string|max:255',
            'cat_desc' => 'nullable|string',
            'short_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:manual',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        if($request->hasFile('cat_image')){
            $file = $request->file('cat_image');
            $filename = 'category_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                $constraint->aspectRatio();
            });
            $cpath = 'category/'.$filename;
            Storage::disk('s3')->put($cpath,(string)$img->encode());
            $validated['cat_image'] = $cpath;
        }
        $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Cat::where('shop_id','=',$shopId)
            ->where('cat_slug', '=', $slug)
            ->where('cat_id', '!=', $cat_id)
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $validated['cat_slug'] = $slug;
        DB::transaction(function () use ($cat, $validated) {
            $cat->update([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'short_desc' => $validated['short_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
                'cat_image' => $validated['cat_image'] ?? $cat->cat_image,
            ]);
            Catpro::where('cat_id', $cat->cat_id)->delete();
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => 99,
                        'shop_id' => session('shop_id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Catpro::insert($insertData);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'cat_id' => $cat->cat_id,
            'cat' => $cat,
        ]);
    }

    public function updateSmartCatbyId(Request $request, $cat_id)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'required|string|max:255',
            'cat_desc' => 'nullable|string',
            'short_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:smart',
            'cat_rule' => 'nullable|in:and,or',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'rules' => 'nullable|array',
            'rules.*.column' => 'required|string',
            'rules.*.relation' => 'required|string',
            'rules.*.condition' => 'required',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        if($request->hasFile('cat_image')){
            $file = $request->file('cat_image');
            $filename = 'category_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                $constraint->aspectRatio();
            });
            $cpath = 'category/'.$filename;
            Storage::disk('s3')->put($cpath,(string)$img->encode());
            $validated['cat_image'] = $cpath;
        }
        $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Cat::where('shop_id','=',$shopId)
            ->where('cat_slug', '=', $slug)
            ->where('cat_id', '!=', $cat_id)
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $validated['cat_slug'] = $slug;
        DB::transaction(function () use ($cat, $validated) {
            $cat->update([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'short_desc' => $validated['short_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'cat_type' => $validated['cat_type'] ?? 'smart',
                'cat_rule' => $validated['cat_rule'] ?? 'and',
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
                'cat_image' => $validated['cat_image'] ?? $cat->cat_image,
            ]);
            Catpro::where('cat_id', $cat->cat_id)->delete();
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => 99,
                        'shop_id' => session('shop_id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Catpro::insert($insertData);
            }
            $cat->rules()->delete();
            if (!empty($validated['rules'])) {
                foreach ($validated['rules'] as $rule) {
                    $cat->rules()->create([
                        'column' => $rule['column'],
                        'relation' => $rule['relation'],
                        'condition' => $rule['condition'],
                        'cat_id' => $cat->cat_id,
                        'shop_id' => session('shop_id'),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'cat_id' => $cat->cat_id,
            'cat' => $cat,
        ]);

    }

    public function deleteCategorybyId($cat_id)
    {
        $shopId = session('shop_id');
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        $cat->delete();
        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully',
        ]);
    }

    public function allInventory()
    {
        $shopId = session('shop_id');
        $variants = Variant::Join('products','products.product_id','=','variants.product_id')
            ->join('stocks','stocks.variant_id','=','variants.variant_id')
            ->Join('product_types', 'product_types.product_type_id', '=', 'products.product_type_id')
            ->Join('brands', 'brands.brand_id', '=', 'products.brand_id')
            ->leftJoin(DB::raw("
            (
                SELECT variant_id,
                       SUM(allocated_quantity - shipped_quantity) as committed,
                       SUM(backorder_quantity) as backorders
                FROM order_items
                GROUP BY variant_id
            ) as oi
            "),'oi.variant_id','=','variants.variant_id')
            ->select('variants.variant_id','variants.sku','variants.variant_id','variants.variant_image','variants.option_values',
                'products.title','products.handle','products.product_status','products.featured_image','products.tags',
                'stocks.quantity','product_types.product_type_name','brands.brand_name',
                DB::raw('COALESCE(oi.backorders,0) as backorder_qty'),
                DB::raw('COALESCE(oi.committed,0) as committed'),
                DB::raw('(stocks.quantity - COALESCE(oi.committed,0)) as available'),
                'stocks.stock_id','stocks.location_id','stocks.product_id','stocks.shop_id')
            ->where('variants.shop_id','=',$shopId)->get();
        foreach ($variants as $variant) {
            $variant['tags'] = json_decode($variant->tags);
        }
        return response()->json([
            'variants' => $variants,
        ],200);
    }

    public function updateInventory(Request $request)
    {
        $stock = Stock::Find($request['stock_id']);
        if($stock){
            if($request->action === 'set'){
                $stock->quantity = $request['set_qty'];
            } else {
                $stock->quantity += (int)$request->adjust_qty;
            }
            $stock->save();
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'stock' => $stock,
                'requests' => $request->all(),
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Stock Not Updated'
            ]);
        }
    }

    public function allCustomers()
    {
        $shopId = session('shop_id');
        $shopusers = ShopUser::join('users','users.id','=','shop_users.user_id')
            ->whereNot('shop_users.role' ,'=','superadmin')
            ->select('shop_users.*','users.name','users.email','users.email_verified_at')
            ->where('shop_id','=',$shopId)->get();
        $customers = Customer::join('customer_shops','customer_shops.customer_id','=','customers.customer_id')
            ->where('customer_shops.shop_id','=',$shopId)
            ->select('customers.*','customer_shops.ctags','customer_shops.shop_id','customer_shops.status as cstatus')
            ->get();
        foreach($customers as $customer){
            $customer['ctags'] = json_decode($customer->ctags,true);
            $orders = Order::where('customer_id','=',$customer->customer_id)
                ->where('shop_id','=',$shopId)->get();
            $customer['ordercount'] = count($orders);
            $customer['amount_spent'] = $orders->sum('order_total');
            $defaultAddress = CustomerAddress::where('customer_id','=',$customer->customer_id)
                ->where('is_default','=',true)->first();
            $customer['defaultaddress'] = $defaultAddress;
        }
        return response()->json([
            'shopusers' => $shopusers,
            'customers' => $customers
        ],200);
    }

    public function getCustomerByID($customer_id)
    {
        $shopId = session('shop_id');
        if($customer_id){
            $customer = Customer::where('customer_id','=',$customer_id)->first();
            $cshops = CustomerShop::where('customer_id','=',$customer_id)->where('shop_id','=',$shopId)->first();
            $defaultAddress = CustomerAddress::where('customer_id','=',$customer->customer_id)
                ->where('is_default','=',true)->first();
            $orders = Order::
            with('orderItems.product','orderItems.variant','customer')
                ->where('customer_id','=',$customer_id)
                ->where('shop_id','=',$shopId)->get();
            $last_order = Order::with('orderItems.product','orderItems.variant','customer')->where('customer_id','=',$customer_id)
                ->where('shop_id','=',$shopId)->latest()->first();
            $customer['defaultaddress'] = $defaultAddress;
            $customer['ctags'] = $cshops->ctags;
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'orders' => $orders,
                'amount_spent' => $orders->sum('order_total'),
                'last_order' => $last_order,
            ]);
        }

    }

    public function allPtypes()
    {
        $shopId = session('shop_id');
        $ptypes = ProductType::where('shop_id','=',$shopId)->get();
        foreach($ptypes as $ptype){
            $pcount = Product::where('product_type_id','=',$ptype->product_type_id)->count();
            $ptype['pcount'] = $pcount;
        }
        return response()->json([
            'ptypes' => $ptypes
        ],200);
    }

    public function updatePtype(Request $request)
    {
        $shopId = session('shop_id');
        $ptype = ProductType::updateOrCreate(
            [
                'shop_id' => $shopId,
                'product_type_id' => $request['product_type_id']
            ],
            [
                'product_type_name' => $request['product_type_name'],
                'product_type_status' => $request['product_type_status'],
                'shop_id' => $shopId,
            ]

        );
        if($ptype){
            return response()->json([
                'success' => true,
                'message' => 'Product type updated successfully',
                'ptype' => $ptype,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product Type Not Updated'
            ],401);
        }
    }

    public function deletePtypebyId(Request $request)
    {
        $shopId = session('shop_id');
        $ptype = ProductType::where('product_type_id', $request['product_type_id'])
            ->where('shop_id', $shopId)
            ->firstOrFail();
        if($ptype){
            $ptype->delete();
            return response()->json([
                'success' => true,
                'message' => 'Product type deleted successfully',
            ]);
        }
    }

    public function allBrands()
    {
        $shopId = session('shop_id');
        $brands = Brand::where('shop_id','=',$shopId)->get();
        foreach ($brands as $brand) {
            $pcount = Product::where('brand_id','=',$brand->brand_id)->count();
            $brand['pcount'] = $pcount;
        }
        return response()->json([
            'brands' => $brands
        ],200);
    }

    public function brandById($brand_id)
    {
        $shopId = session('shop_id');
        $brand = Brand::find($brand_id);
        return response()->json([
            'status' => 200,
            'brand' => $brand,
            'message' => "Brand Detail",
        ]);
    }

    public function updateBrand(Request $request)
    {
        $shopId = session('shop_id');
        $existingBrand  = Brand::where('shop_id','=',$shopId)
            ->where('brand_id',$request['brand_id'])
            ->first();
        $bpath = $existingBrand?->brand_image ?? null;
        if($request->hasFile('brand_image')){
            if ($existingBrand && $existingBrand->brand_image) {
                Storage::disk('s3')->delete($existingBrand->brand_image);
            }
            $Image = $request->file('brand_image');
            $filename = 'brand_'.uniqid().'.png';
            $img = Image::make($Image->getRealPath())->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $bpath = 'images/brand/'.$filename;
            Storage::disk('s3')->put($bpath, (string) $img->encode());
        }
        $baseSlug = Str::slug($request['brand_slug'] ?? $request['brand_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Brand::where('shop_id','=',$shopId)
            ->where('brand_slug', '=', $slug)
            ->where('brand_id', '!=', $request['brand_id'])
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $request['brand_slug'] = $slug;
        $brand = Brand::updateOrCreate(
            [
                'shop_id' => $shopId,
                'brand_id' => $request['brand_id'],
            ],
            [
                'brand_name'=>$request['brand_name'],
                'brand_slug' => $request['brand_slug'],
                'brand_desc' => $request['brand_desc'],
                'brand_status' => $request['brand_status'] ?? "Active",
                'brand_image' => $bpath,
                'shop_id' => $shopId,
                'meta_title' => $request['meta_title'],
                'meta_desc' => $request['meta_desc'],
            ]
        );
        if($brand){
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully',
                'brand' => $brand,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Brand Not Updated'
            ],401);
        }
    }

    public function deleteBrand(Request $request)
    {
        $brand = Brand::findOrFail($request['brand_id']);
        $brand->delete();
        return response()->json(['success'=>true,'message' => "Brand Deleted Success"]);
    }

    public function featuresList()
    {
        $shopId = session('shop_id');
        $features = Feature::where('shop_id',$shopId)->get();
        foreach ($features as $feature){
            $pcount = Highlight::where('feature_id','=',$feature->feature_id)->count();
            $feature['pcount'] = $pcount;
        }
        if($features->isEmpty()){
            $defaultFeatures = [
                ['ftitle' => 'Vaping Style'],
                ['ftitle' => 'Bottle Size'],
                ['ftitle' => 'Battery Capacity'],
                ['ftitle' => 'E-Liquid Capacity'],
                ['ftitle' => 'Function'],
                ['ftitle' => 'Nicotine Type'],
                ['ftitle' => 'Nicotine Strength'],
                ['ftitle' => 'Pack Size'],
                ['ftitle' => 'Pod Style'],
                ['ftitle' => 'Power Supply'],
                ['ftitle' => 'Puff Count'],
                ['ftitle' => 'VG Ratio'],
            ];
            foreach ($defaultFeatures as &$feature) {
                $feature['fimage'] = 'images/features/'.Str::slug(strtolower($feature['ftitle']),'_').'.png';
                $feature['shop_id'] = $shopId;
                $feature['created_at'] = now();
                $feature['updated_at'] = now();
            }
            Feature::insert($defaultFeatures);
            $features = Feature::where('shop_id', $shopId)->get();
            return response()->json([
                'success' => true,
                'features' => $features,
            ]);
        }
        return response()->json([
            'success' => true,
            'features' => $features,
        ]);
    }

    public function updateFeature(Request $request)
    {
        $shopId = session('shop_id');
        $existingFeature  = Feature::where('shop_id','=',$shopId)
            ->where('feature_id',$request['feature_id'])
            ->first();
        $defaultimage = Str::slug(strtolower($request->ftitle),'_').'.png';
        $fpath = $existingFeature?->fimage ?? $defaultimage;
        if($request->hasFile('fimage')){
            $Image = $request->file('fimage');
            $filename = 'feature_'.uniqid().'.png';
            $img = Image::make($Image->getRealPath())->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $fpath = 'images/features/'.$filename;
            Storage::disk('s3')->put($fpath, (string) $img->encode());
        }
        $feature = Feature::updateOrCreate(
            [
                'shop_id' => $shopId,
                'feature_id' => $request['feature_id'],
            ],
            [
                'ftitle'=>$request['ftitle'],
                'fimage' => $fpath,
                'shop_id' => $shopId,
            ]
        );
        if($feature){
            return response()->json([
                'success' => true,
                'message' => 'Feature updated successfully',
                'feature' => $feature,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Feature Not Updated'
            ],401);
        }
    }

    public function deleteFeature(Request $request)
    {
        $feature = Feature::findOrFail($request['feature_id']);
        $feature->delete();
        return response()->json(['success'=>true,'message' => "Feature Deleted Success"]);
    }

    public function updateSpecific(Request $request)
    {
        $shopId = session('shop_id');
        $specific = Specific::updateOrCreate(
            ['specific_id'=>$request['specific_id']],
            [
                'stitle'=>$request['stitle'],
                'svalue'=>$request['svalue'],
                'product_id'=>$request['product_id'],
                'shop_id'=>$shopId,
            ],
        );
        if($specific){
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

    public function deleteSpecific(Request $request)
    {
        $specific = Specific::findOrFail($request['specific_id']);
        $specific->delete();
        return response()->json(['success'=>true,'message' => "Specific Deleted Success"]);
    }

    public function allTags()
    {
        $shopId = session('shop_id');
        $tags = Tag::where('shop_id','=',$shopId)
            ->orderBy('tag_name','asc')
            ->get();
        foreach ($tags as $tag) {
            $pcount = Product::whereJsonContains('tags',$tag->tag_name)
                ->where('shop_id','=',$shopId)
                ->count();
            $tag['pcount'] = $pcount;
        }
        return response()->json([
            'tags' => $tags
        ],200);
    }

    public function updateTag(Request $request)
    {
        $shopId = session('shop_id');
        $tag = Tag::updateOrCreate(
            [
                'shop_id' => $shopId,
                'tag_id' => $request['tag_id'],
            ],
            [
                'tag_name' => $request['tag_name'],
                'shop_id' => $shopId,
                'tag_status' => $request['tag_status']
            ]
        );
        if($tag){
            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'tag' => $tag
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tag Not Updated'
            ]);
        }
    }

    public function deleteTag(Request $request)
    {
        $tag = Tag::findOrFail($request['tag_id']);
        $tag->delete();
        return response()->json(['success'=>true,'message' => "Tag Deleted Success"]);
    }

    public function allPoptions()
    {
        $shopId = session('shop_id');
        $uniqueOptions = Variant::where('shop_id','=',$shopId)
            ->pluck('option_values')->unique()->values();
        $opnames = Variant::where('shop_id','=',$shopId)
            ->pluck('options')->values();
        $doptions = Variant::whereJsonContains('options','Nicotine Stength')->get();
        $poptions = Poptions::where('shop_id','=',$shopId)->get();
        foreach($poptions as $poption){
            $opname = $poption->option_name;
            $poption['usedcount'] = Variant::whereJsonContains('options',$opname)
                ->where('shop_id','=',$shopId)->count();
        }
        return response()->json([
            'doptions' => $doptions,
            'opnames' => $opnames,
            'uniqueOptions' => $uniqueOptions,
            'poptions' => $poptions
        ],200);
    }

    public function updatePoption(Request $request)
    {
        $shopId = session('shop_id');
        $poption = Poptions::updateOrCreate(
            [
                'shop_id' => $shopId,
                'option_id' => $request['option_id'],
            ],
            [
                'option_name' => $request['option_name'],
                'shop_id' => $shopId,
            ],
        );
        if($poption){
            return response()->json([
                'success' => true,
                'poption' => $poption,
                'message' => 'Option updated successfully',
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Option Not Updated'
            ]);
        }
    }

    public function themeSettings()
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id','=',$shopId)->first();
        if($shop){
            return response()->json([
                'shop' => $shop,
            ],200);
        } else {
            return response()->json([
                'shop' => null,
            ]);
        }

    }

    public function homeBanners()
    {
        $shopId = session('shop_id');
        $homebanners = Homebanner::where('shop_id','=',$shopId)->get();
        $featuredgrids = Featuredgrid::where('shop_id','=',$shopId)->get();
        $featuredcats = Featuredcat::Join('cats','cats.cat_id','=','featuredcats.cat_id')
            ->select('featuredcats.*','cats.cat_slug','cats.cat_name')
            ->where('featuredcats.shop_id','=',$shopId)
            ->get();
        $shopbycats = Shopbycat::Join('cats','cats.cat_id','=','shopbycats.cat_id')
            ->select('shopbycats.*','cats.cat_slug','cats.cat_name')
            ->where('shopbycats.shop_id','=',$shopId)
            ->get();
        $productgrids = Productgrid::where('shop_id','=',$shopId)->get();
        foreach ($productgrids as $productgrid) {
            $catId = $productgrid->cat_id;
            $products = Product::with(['variants.astock', 'brand', 'ptype'])
                ->whereIn('product_id', function ($query) use ($catId) {
                    $query->select('product_id')
                        ->from('catpros')
                        ->where('cat_id', $catId);
                })
                ->limit($productgrid->limit)
                ->get();
            $productgrid['allpros'] = $products;
        }
        return response()->json([
            'homebanners' => $homebanners,
            'featuredgrids' => $featuredgrids,
            'featuredcats' => $featuredcats,
            'shopbycats' => $shopbycats,
            'productgrids' => $productgrids
        ],200);
    }

    public function shippingSettings()
    {
        $shopId = session('shop_id');
        $location = Location::where('shop_id','=',$shopId)->first();
        if($location){
            $ship_methods = ShipMethod::with('courier')
                ->where('shop_id','=',$shopId)
                ->get();
            return response()->json([
                'status'=> true,
                'couriers' => Courier::all(),
                'ship_methods' => $ship_methods,
                'location' => $location,
            ]);
        } else {
            return response()->json([
                'status'=> false,
                'couriers' => Courier::all(),
                'ship_methods' => [],
                'location' => null,
            ]);
        }
    }

    public function updateOrAddAdminShipMethod(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $shipMethod = ShipMethod::updateOrCreate(
                [
                    'ship_method_id' => $request->ship_method_id,
                    'shop_id' => $shopId,
                ],
                [
                    'method' => $request['method'],
                    'price' => $request['price'] ?? 0.00,
                    'zone' => $request['zone'],
                    'courier_id' => $request['courier_id'],
                    'shop_id' => $shopId,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'shipMethod' => $shipMethod,
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

    public function getAdminShopPaymentMethods()
    {
        $shopId = session('shop_id');
        $spmethods = ShopPaymentMethod::where('shop_id', $shopId)->get();
        if($spmethods->isEmpty()){
            return response()->json([
                'success' => false,
                'spmethods' => null,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'spmethods' => $spmethods,
            ]);
        }
    }

    public function updateAdminPaymentMethod(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'payment_name' => 'required|string',
            'payment_method' => 'required|string',
            'payment_icon' => 'required|string',
            'payment_options' => 'required|array',
            'payment_status' => 'required|in:active,inactive',
            'sort_order' => 'required|integer',
            'shop_id' => 'required|integer',
        ]);
//        dd($validatedData);
        DB::beginTransaction();
        try {
            $smethod = ShopPaymentMethod::updateOrCreate(
                [
                    'shop_id' => $request->shop_id,
                    'shop_payment_method_id' => $request->shop_payment_method_id,
                    'payment_name' => $request->payment_name,
                    'payment_method' => $request->payment_method,
                ],
                [
                    'payment_name' => $request->payment_name,
                    'payment_method' => $request->payment_method,
                    'payment_icon' => $request->payment_icon,
                    'payment_options' => $request->payment_options,
                    'payment_status' => $request->payment_status,
                    'sort_order' => $request->sort_order,
                    'shop_id' => $request->shop_id,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'smethod' => $smethod,
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

    public function shopPreferences()
    {
        $shopId = session('shop_id');
        $preferences = Preference::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'home_title' => 'Home Page',
                'home_desc' => 'Home Page Description',
                'home_image' => null,
                'shop_logo' => null,
                'social_links' => [],
                'shop_id' => $shopId,
            ]
        );
        return response()->json([
            'success' => true,
            'preferences' => $preferences,
            'shop' => $this->shop,
            'shopdetail'=> Shop::find($shopId),
        ]);
    }

    public function shopPreferenceUpdate(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();
        $shopslug = $shop->shop_slug;
        $preference = Preference::Find($request['preference_id']);
        $preference->home_title = $request['home_title'];
        if($request->hasFile('home_image')){
            $Image = $request->file('home_image');
            $filename = $shopslug.'_'.time().'.png';
            $img = Image::make($Image->getRealPath())->resize(1200, 630, function ($constraint) {
                $constraint->aspectRatio();
            });
            $fpath = $shopslug.'/ogimage/'.$filename;
            Storage::disk('s3')->put($fpath, (string) $img->encode());
            $preference->home_image = $fpath;
        }
        if($request->hasFile('shop_logo')){
            $Image = $request->file('shop_logo');
            $filename = $shopslug.'_'.time().'.png';
            $img = Image::make($Image->getRealPath())->resize(400, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $flogo = $shopslug.'/logo/'.$filename;
            Storage::disk('s3')->put($flogo, (string) $img->encode());
            $preference->shop_logo = $flogo;
        }
        $preference->home_description = $request['home_description'];
        $preference->save();
        if($preference){
            return response()->json([
                'success' => true,
                'message' => 'Preference updated successfully',
                'preference' => $preference,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Preference Not updated',
                'preference' => null,
            ]);
        }

    }

    public function shopSocialUpdate(Request $request)
    {
        $slink = Preference::Find($request['preference_id']);
        $slink->social_links = $request['slinks'];
        $slink->save();
        if($slink){
            return response()->json([
                'success' => true,
                'message' => 'Preference updated successfully',
                'slinks' => $slink,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Preference Not updated',
                'slinks' => null,
            ]);
        }
    }

    public function blogsList()
    {
        $shopId = session('shop_id');
        $blogs = Blog::with('user')->where('shop_id','=',$shopId)->latest()->get();
        return response()->json([
            'status'=> true,
            'blogs' => $blogs,
        ]);
    }

    public function getBlogById($blog_id)
    {
        $shopId = session('shop_id');
        $blog = Blog::with('sections')->where('blog_id','=',$blog_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        $categories = Cat::where('shop_id','=',$shopId)
            ->select('cat_id','cat_name','cat_slug','cat_image')
            ->get();
        if($blog){
            return response()->json([
                'status'=> true,
                'blog' => $blog,
                'stypes'=> $stypes,
                'categories'=>$categories
            ]);
        } else {
            return response()->json([
                'status'=> false,
                'blog' => null,
            ]);
        }
    }

    public function addBlog(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $baseSlug = Str::slug($request['blog_slug'] ?? $request['blog_title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Blog::where('shop_id','=',$shopId)->where('blog_slug', '=', $slug)->exists()){
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $blogSlug = $slug;
            if($request->hasFile('blog_image')){
                $file = $request->file('blog_image');
                $filename = 'blog_image_'.uniqid().'.png';
                $img = Image::make($file->getRealPath())->resize(1200, 800, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $bpath = 'blog/'.$filename;
                Storage::disk('s3')->put($bpath,(string)$img->encode());
                $blogImage = $bpath;
            }
            $blog = Blog::create(
                [
                    'blog_title' => $request->blog_title,
                    'blog_slug' => $blogSlug,
                    'blog_description' => $request->blog_description,
                    'blog_excerpt' => $request->blog_excerpt,
                    'blog_image' => $blogImage ?? $request['blog_image'],
                    'btags' => $request->btags ?? null,
                    'blog_status' => $request->blog_status ?? 'active',
                    'meta_title' => $request->meta_title,
                    'meta_desc' => $request->meta_desc,
                    'user_id' => auth()->user(),
                    'shop_id' => $shopId,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'blog' => $blog,
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

    public function updateBlog(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $blog = Blog::where('shop_id', $shopId)
                ->where('blog_id', $request->blog_id)
                ->firstOrFail();

            if (!empty($request->blog_slug) && $request->blog_slug !== $blog->blog_slug) {
                $baseSlug = Str::slug($request->blog_slug);
                $slug = $baseSlug;
                $counter = 1;

                while (Blog::where('shop_id', $shopId)
                    ->where('blog_id', '!=', $blog->blog_id)
                    ->where('blog_slug', $slug)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $blogSlug = $slug;
            } else {

                $blogSlug = $blog->blog_slug;
            }
            if ($request->hasFile('blog_image')) {
                $file = $request->file('blog_image');
                $filename = 'blog_image_' . uniqid() . '.png';
                $img = Image::make($file->getRealPath())->resize(1200, 800, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $bpath = 'blog/' . $filename;
                Storage::disk('s3')->put($bpath, (string) $img->encode());
                $blogImage = $bpath;
            } else {
                $blogImage = $blog->blog_image;
            }
            $blog = $blog->update(
                [
                    'blog_title' => $request->blog_title,
                    'blog_slug' => $blogSlug,
                    'blog_description' => $request->blog_description,
                    'blog_excerpt' => $request->blog_excerpt,
                    'blog_image' => $blogImage,
                    'btags' => $request->btags ?? null,
                    'blog_status' => $request->blog_status ?? 'active',
                    'meta_title' => $request->meta_title,
                    'meta_desc' => $request->meta_desc,
                    'user_id' => auth()->user(),
                    'shop_id' => $shopId,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'blog' => $blog,
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

    public function addNewBlogSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'blog_id' => 'required|exists:blogs,blog_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Blog::class,
            'sectionable_id' => $validatedData['blog_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Blog::class)
                    ->where('sectionable_id', $request->blog_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

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
        $page = Page::with('sections')->where('shop_id', $shopId)
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

    public function addNewPageSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'page_id' => 'required|exists:pages,page_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Page::class,
            'sectionable_id' => $validatedData['page_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Page::class)
                    ->where('sectionable_id', $request->page_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function getPolicyList()
    {
        $shopId = session('shop_id');
        $policies = Policy::where('shop_id', $shopId)->get();
        if($policies->isEmpty()){
            $defaultPolicies = [
                [
                    'policy_name' => 'Terms and Conditions',
                    'policy_slug'  => 'terms',
                    'policy_description' => 'By using this site, you agree to the terms...',
                ],
                [
                    'policy_name' => 'Privacy Policy',
                    'policy_slug'  => 'privacy',
                    'policy_description' => 'Your privacy is important to us...',
                ],
                [
                    'policy_name' => 'Refund Policy',
                    'policy_slug'  => 'refund-policy',
                    'policy_description' => 'We offer refunds within 30 days...',
                ],
                [
                    'policy_name' => 'Shipping Policy',
                    'policy_slug'  => 'shipping-policy',
                    'policy_description' => 'We ship products within 3-5 business days...',
                ],
            ];
            foreach ($defaultPolicies as &$policy) {
                $policy['policy_status'] = 'active';
                $policy['shop_id'] = $shopId;
            }

            Policy::insert($defaultPolicies);
            $policies = Policy::where('shop_id', $shopId)->get();
            return response()->json([
                'success' => false,
                'policies' => $policies,
            ]);
        }
        return response()->json([
            'success' => true,
            'policies' => $policies,
        ]);

    }

    public function getPolicyById($policy_id)
    {
        $shopId = session('shop_id');
        $policy = Policy::where('shop_id', $shopId)
            ->where('policy_id', $policy_id)
            ->first();
        if($policy){
            return response()->json([
                'success' => true,
                'policy' => $policy,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'policy' => 'null',
            ]);
        }
    }

    public function updatePolicy(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $policy = Policy::where('shop_id', $shopId)->where('policy_id', $request->policy_id)->firstOrFail();

            $policy->policy_name = $request->policy_name;
            $policy->policy_description = $request->policy_description;
            $policy->update();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Policy has been updated.',
                'policy' => $policy,
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

    public function searchAll(Request $request)
    {
        $shopId = session('shop_id');
        $query = $request->input('q');
        $brands = Brand::where('shop_id','=',$shopId)
            ->where('brand_name', 'LIKE', "%{$query}%")
            ->get(['brand_id','brand_name','brand_slug']);
        $allbrands = ['brand_id'=>1,'brand_name'=>'All Brands','brand_slug'=>'/brands'];
        $collections =  Cat::where('shop_id','=',$shopId)
            ->where('cat_name', 'LIKE', "%{$query}%")
            ->get(['cat_id','cat_name','cat_slug']);
        $allcollections = ['cat_id'=>1,'cat_name'=>'All Collections','cat_slug'=>'/collections'];
        $pages = Page::where('shop_id','=',$shopId)
            ->where('page_title', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get(['page_id','page_title','page_slug']);
        $allpages = ['page_id'=>1,'page_title'=>'Home','page_slug'=>'/'];
        $products = Product::where('shop_id','=',$shopId)
            ->where('title', 'LIKE', "%{$query}%")
            ->limit(50)
            ->get(['product_id','title','handle']);
        $allpros = ['product_id'=>1,'title'=>'All Products','handle'=>'/products'];
        $blogs = Blog::where('shop_id','=',$shopId)
            ->where('blog_title', 'LIKE', "%{$query}%")
            ->limit(50)
            ->get(['blog_id','blog_title','blog_slug']);
        $allblogs = ['blog_id'=>1,'blog_title'=>'All Blogs','blog_slug'=>'/blogs'];
        $policies = Policy::where('shop_id','=',$shopId)
            ->where('policy_name', 'LIKE', "%{$query}%")
            ->limit(50)
            ->get(['policy_id','policy_name','policy_slug']);
        return response()->json([
            'brands' => [
                'count' => $brands->count(),
                'items' => [$allbrands,...$brands],
            ],
            'collections' => [
                'count' => $collections->count(),
                'items' => [$allcollections,...$collections]
            ],
            'pages' => [
                'count' => $pages->count()+1,
                'items' => [$allpages,...$pages]
            ],
            'products' => [
                'count' => $products->count(),
                'items' => [$allpros,...$products]
            ],
            'blogs' => [
                'count' => $blogs->count(),
                'items' => [$allblogs,...$blogs]
            ],
            'policies' => [
                'count' => $policies->count(),
                'items' => $policies
            ]
        ]);
    }

    public function getAdminMenusList()
    {
        $shopId = session('shop_id');
        $menus = Menu::where('shop_id', $shopId)
            ->get();
        return response()->json([
            'success' => !$menus->isEmpty(),
            'menus' => $menus->isEmpty() ? null : $menus,
        ]);
    }

    public function addAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_name' => 'required|string',
            'menu_slug' => 'required|string',
            'menu_status' => 'required|in:active,inactive',
            'menu_items' => 'required|array',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::create([
                'menu_name' => $validatedData['menu_name'],
                'menu_slug' => $validatedData['menu_slug'],
                'menu_status' => $validatedData['menu_status'],
                'mitems'=> $validatedData['menu_items'],
                'shop_id' => $shopId,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Successfully created menu.',
                'menu' => $menu,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAdminMenuById($menu_id)
    {
        $shopId = session('shop_id');
        $menu = Menu::where('shop_id', $shopId)
            ->where('menus.menu_id', $menu_id)
            ->first();
        if($menu){
            return response()->json([
                'success' => true,
                'message' => 'Menu with its Items',
                'menu' => $menu,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
                'menu' => null,
            ]);
        }

    }

    public function updateAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_id' => 'required|integer',
            'menu_name' => 'required|string',
            'menu_slug' => 'required|string',
            'menu_status' => 'required|in:active,inactive',
            'menu_items' => 'required|array',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::where('shop_id', $shopId)
                ->findOrFail($validatedData['menu_id']);

            $menu->update([
                'menu_name' => $validatedData['menu_name'],
                'menu_slug' => $validatedData['menu_slug'],
                'menu_status' => $validatedData['menu_status'],
                'mitems'=> $validatedData['menu_items'],
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully.',
                'menu' => $menu,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_id' => 'required|integer',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::where('shop_id', $shopId)
                ->findOrFail($validatedData['menu_id']);
            $menu->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCartPage()
    {
        $shopId = session('shop_id');
        $cartpage = Cartpage::with('sections')->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'cart_slug' => "cart",
                'shop_id' => $shopId,
            ]
        );
//        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',['review_slider','blog_slider'])->get();

        $categories = Cat::where('shop_id','=',$shopId)
            ->select('cat_id','cat_name','cat_slug','cat_image')
            ->get();
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_id','brand_name','brand_slug','brand_image')
            ->get();
        return response()->json([
            'success' => true,
            'message' => 'Home page',
            'cartpage' => $cartpage,
            'stypes'=> $stypes,
            'categories'=>$categories,
            'brands'=>$brands,
        ]);
    }

    public function addNewCartSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'cartpage_id' => 'required|exists:cartpages,cartpage_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Cartpage::class,
            'sectionable_id' => $validatedData['cartpage_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Cartpage::class)
                    ->where('sectionable_id', $request->cartpage_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function getShopAnnouncements()
    {
        $shopId = session('shop_id');
        $announcements = Announcement::where('shop_id', $shopId)->get();
        return response()->json([
            'success' => true,
            'announcements' => $announcements,
        ]);
    }

    public function updateAnnouncement(Request $request)
    {
        $shopId = session('shop_id');
        if($request->mname === 'delete'){
            $announcement = Announcement::findOrFail($request->announcement_id);
            $announcement->delete();
            return response()->json(['success' => true,'message' => 'Announcement deleted successfully']);
        }
        $announcement = Announcement::updateOrCreate(
            [
                'shop_id' => $shopId,
                'announcement_id' => $request->announcement_id
            ],
            [
                'title' => $request->title,
                'setting' => $request->setting,
                'status' => $request->status ?? 'active',
                'shop_id' => $shopId,
            ],
        );
        if($announcement){
            return response()->json([
                'success' => true,
                'announcement' => $announcement,
            ]);
        }
    }

    public function getShopSearchTags()
    {
        $shopId = session('shop_id');
        $searchtags = Searchtag::where('shop_id', $shopId)->get();
        return response()->json([
            'success' => true,
            'searchtags' => $searchtags,
        ]);
    }

    public function updateSearchTag(Request $request)
    {
        $shopId = session('shop_id');
        if($request->mname === 'delete'){
            $searchtag = Searchtag::findOrFail($request->stag_id);
            $searchtag->delete();
            return response()->json(['success' => true,'message' => 'Tag deleted successfully']);
        } else {
            $searchtag = Searchtag::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'stag_id' => $request->stag_id,
                ],
                [
                    'title' => $request->title,
                    'link' => $request->link,
                    'status' => $request->status ?? 'active',
                    'shop_id' => $shopId,
                ],
            );
            if($searchtag){
                return response()->json([
                    'success' => true,
                    'searchtag' => $searchtag,
                ]);
            }
        }

    }

    public function getShopFooter()
    {
        $shopId = session('shop_id');
        $footer = Footer::where('shop_id', $shopId)->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'style' => 'style1',
                'fsections' => null,
                'settings' => null,
                'shop_id' => $shopId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $menus = Menu::where('shop_id', $shopId)
            ->whereIn('menu_slug',['quick_links','information','our_policies'])
            ->get();
        $business = BusinessShop::with('business','shops')->where('shop_id', $shopId)
            ->first();
        return response()->json([
            'success' => true,
            'footer' => $footer,
            'menus' => $menus,
            'business' => $business['business'],
            'quick_links' => Menu::where('menu_slug','quick_links')->where('shop_id', $shopId)->first() ?? null,
            'information' => Menu::where('menu_slug','information')->where('shop_id', $shopId)->first() ?? null,
            'our_policies' => Menu::where('menu_slug','our_policies')->where('shop_id', $shopId)->first() ?? null,
        ]);
    }

    public function updateFooter(Request $request)
    {
        $shopId = session('shop_id');
        $footer = Footer::findOrFail($request->footer_id);
        if($footer){
            $footer->update([
                'style' => $request['style'] ?? 'style1',
                'fsections' => $request['fsections'],
                'settings' => $request['settings'],
                'shop_id' => $shopId,
            ]);

            return response()->json([
                'success' => true,
                'footer' => $footer,
                'message' => 'Footer updated successfully',
            ]);
        }
    }

    public function getShopSubscribeSection()
    {
        $shopId = session('shop_id');
        $settings = array('background_color' => "#212529", 'text_color' => "#ffffff");
        $extras = array(
            [
                'icon' => 'mdi-envelope',
                'title' => 'New Arrivals',
                'detail' => 'Get updates on the latest products & innovations.'
            ],
            [
                'icon' => 'mdi-calendar',
                'title' => 'Sent weekly',
                'detail' => 'We send weekly emails, directly to your inbox.'
            ],
            [
                'icon' => 'mdi-lock',
                'title' => 'Safe & secure',
                'detail' => 'We respect your privacy, so we’ll keep your details safe.'
            ]
        );
        $subscribe_section = SubscribeSection::where('shop_id', $shopId)->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'shop_id'=> $shopId,
                'style'=> "style1",
                'headline'=> "Subscribe to our newsletter",
                'subheadline'=> "Start and grow your business",
                'promo_text'=> "Be the first to hear about new products, fantastic special offers, and news.",
                'privacy_text'=> "We value your privacy and promise to keep your details safe.",
                'settings'=> $settings,
                'extras'=>$extras,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        );
        return response()->json([
            'success'=>true,
            'subscribe_section' => $subscribe_section,
        ]);
    }

    public function updateShopSubscribeSection(Request $request)
    {
        $shopId = session('shop_id');
        $subscribe_section = SubscribeSection::findOrFail($request->id);
        if($subscribe_section){
            $subscribe_section->update([
                'style'=> $request->style,
                'headline'=> $request->headline,
                'subheadline'=> $request->subheadline,
                'promo_text'=> $request->promo_text,
                'privacy_text'=> $request->privacy_text,
                'settings'=> $request->settings,
                'extras'=>$request->extras,
                'shop_id'=> $shopId,
            ]);
            return response()->json([
                'success' => true,
                'message' => "Section Updated successfully",
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Section Not updated",
            ]);
        }
    }

    public function getAllLinks()
    {
        $shopId = session('shop_id');
        $acats = Cat::where('shop_id', $shopId)->get(['cat_name','cat_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'collections',
                    'name'=> $item->cat_name,
                    'link'=> 'collections/'.$item->cat_slug,
                ];
            })->toArray();
        $allcollections = [['ltype' => 'collections', 'name' => 'All Collections', 'link' => 'collections']];
        $cats = array_merge($allcollections, $acats);
        $abrands = Brand::where('shop_id', $shopId)->get(['brand_name','brand_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'brands',
                    'name'=> $item->brand_name,
                    'link'=> 'brands/'.$item->brand_slug,
                ];
            })->toArray();
        $allbrands = [['ltype' => 'brands', 'name' => 'All Collections', 'link' => 'brands']];
        $brands = array_merge($allbrands, $abrands);
        $apages = Page::where('shop_id', $shopId)->get(['page_id','page_title','page_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'pages',
                    'name'=> $item->page_title,
                    'link'=> 'pages/'.$item->page_slug,
                ];
            })->toArray();
        $allpages = [['ltype' => 'pages', 'name' => 'Home', 'link' => ' ']];
        $pages = array_merge($allpages, $apages);
        $aproducts = Product::where('shop_id', $shopId)->get(['product_id','title','handle'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'products',
                    'name'=> $item->title,
                    'link'=> 'products/'.$item->handle,
                ];
            })->toArray();
        $allpros = [['ltype' => 'products', 'name' => 'All Products', 'link' => 'products']];
        $products = array_merge($allpros, $aproducts);
        $ablogs = Blog::where('shop_id', $shopId)->get(['blog_id','blog_title','blog_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'blogs',
                    'name'=> $item->blog_title,
                    'link'=> 'blogs/'.$item->blog_slug,
                ];
            })->toArray();
        $allblogs = [['ltype' => 'blogs', 'name' => 'All Blogs', 'link' => 'blogs']];
        $blogs = array_merge($allblogs, $ablogs);
        $policies = Policy::where('shop_id', $shopId)->get(['policy_id','policy_name','policy_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'policies',
                    'name'=> $item->policy_name,
                    'link'=> 'policies/'.$item->policy_slug,
                ];
            })->toArray();;
        $alinks = [...$cats,...$brands,...$pages,...$products,...$blogs,...$policies];
        return response([
            'success' => true,
            'alinks' => $alinks
        ]);

    }

    public function getAdminCouponsList()
    {
        $shopId = session('shop_id');
        $coupons = Coupon::where('shop_id', $shopId)->get();
        return response()->json([
            'success' => true,
            'coupons' => $coupons,
        ]);
    }

    public function getAdminReviewsList()
    {
        $shopId = session('shop_id');
        $reviews = Proreview::where('proreviews.shop_id', $shopId)
            ->join('products','products.product_id','=','proreviews.product_id')
            ->select('proreviews.*','products.title','products.featured_image')
            ->get();
        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }

    public function updateAdminProductReview(Request $request)
    {
        $shopId = session('shop_id');
        $proreview = Proreview::Find($request->proreview_id)->update([
            'review_title'=>$request->review_title,
            'review_text'=>$request->review_text,
            'rating'=>$request->rating ?? 5,
            'review_status'=> $request->review_status ?? "verified",
            'product_id'=>$request->product_id,
            'reviewer_id'=>$request->reviewer_id,
            'shop_id'=> $request->shop_id ?? $shopId,
        ]);
        return response()->json(['success' => true,'message' => 'Review Updated successfully']);
    }

    public function allContentFromAi(Request $request)
    {
        $productTitle = $request->input('productTitle');
        $keywords = $request->input('keywords');
        $type = $request->input('type');
        $client = new \GuzzleHttp\Client();
        $apiurl = "https://truewebproai.onrender.com/generate";
        try {
            $response = $client->post($apiurl, [
                'json' => [
                    'productTitle' => $productTitle,
                    'keywords' => $keywords,
                    'type' => $type,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
            $aicontent = json_decode($response->getBody()->getContents(), true);
            return response()->json([
                'success' => true,
                'result' => $aicontent['result'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
