<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Mail\OrderPlacedMail;
use App\Models\Acart;
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
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Productgrid;
use App\Models\ProductPriceTier;
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
use App\Models\VivaPayment;
use App\Services\SmartCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
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
                    'shipping_protection_enabled' => false,
                    'shipping_protection_fee' => 0,
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
        $setting->shipping_protection_enabled = $request->shipping_protection_enabled ?? false;
        $setting->shipping_protection_fee = $request->shipping_protection_fee ?? 0;
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

        } elseif ($request->stype === 'featured_links'){
            $img = Image::make($file->getRealPath())->resize(800, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });
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
            $img = Image::make($file->getRealPath())->resize(1800, 1800, function ($constraint) {
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

    public function orderStats()
    {
        $shopId = session('shop_id');
        return response()->json([
            'unfulfilled' => Order::where('shop_id', $shopId)
                ->whereNull('deleted_at')
                ->where('fulfillment_status', 'unfulfilled')
                ->count(),
            'pending' => Order::where('shop_id', $shopId)
                ->whereNull('deleted_at')
                ->where('order_status', 'pending')
                ->count(),
            'processing' => Order::where('shop_id', $shopId)
                ->whereNull('deleted_at')
                ->where('order_status', 'processing')
                ->count(),
        ]);
    }

    public function allOrders(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $query = Order::withTrashed()
            ->withCount('orderItems')
            ->where('shop_id','=',$shopId);
        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('order_number', 'LIKE', "%{$term}%")
                            ->orWhere('shipping_name', 'LIKE', "%{$term}%");
                    });
                }
            });
        }
        $allowedSorts = [
            'order_number' => 'order_number',
            'placed_at' => 'placed_at',
            'shipping_name' => 'shipping_name',
            'order_total' => 'order_total',
            'payment_status' => 'payment_status',
            'fulfillment_status' => 'fulfillment_status',
            'order_items_count' => 'order_items_count',
        ];
        if ($status && $status !== 'all') {
            if ($status === 'archived') {
                $query->whereNotNull('deleted_at');
            } else {
                $query->whereNull('deleted_at')
                    ->where('order_status', $status);
            }
        } else {
            $query->whereNull('deleted_at');
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'placed_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $orders = $query->paginate($perPage);

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function getOrderById($order_id)
    {
        $shopId = session('shop_id');
        $order = Order::withTrashed()->with('orderItems.product','orderItems.variant','customer')
            ->withCount('orderItems')
            ->where('order_id','=',$order_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $previous = Order::where('order_id', '<', $order_id)
            ->where('shop_id','=',$shopId)
            ->orderBy('order_id', 'desc')
            ->first();

        $next = Order::where('order_id', '>', $order_id)
            ->where('shop_id','=',$shopId)
            ->orderBy('order_id', 'asc')
            ->first();

        $latest = Order::latest()->where('shop_id','=',$shopId)->value('order_id');
        if($order){
            $logs = OrderLog::where('order_id','=',$order_id)
                ->latest()->get();
            return response()->json([
                'success' => true,
                'order' => $order,
                'logs' => $logs,
                'previous_id' => $previous?->order_id,
                'next_id' => $next?->order_id,
                'latest_id' => $latest,
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
            if (!$this->canTransition($order, $request->mname,$request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action for current order state'
                ], 400);
            }
            if ($request->mname === 'update_shipping_address') {

                $order->update([
                    'shipping_name' => $request->shipping_name,
                    'shipping_address_line1' => $request->shipping_address_line1,
                    'shipping_address_line2' => $request->shipping_address_line2,
                    'shipping_city' => $request->shipping_city,
                    'shipping_postcode' => $request->shipping_postcode,
                    'shipping_country' => $request->shipping_country,
                    'shipping_phone' => $request->shipping_phone,
                ]);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'address_updated',
                    'description' => 'Shipping address updated',
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Address updated successfully',
                ]);
            }
            if($request->mname === 'update_fulfillment_status'){
                $from = $order->fulfillment_status;
                $to = $request->fulfillment_status;
                $order->update([
                    'fulfillment_status' => $to
                ]);
                $this->syncOrderStatus($order);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => "Fulfillment status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);
                return response()->json([
                    'success' => true,
                    'message' => "Fulfillment updated to {$to}",
                ]);
            }
            if ($request->mname === 'update_order_status') {

                $from = $order->order_status;
                $to = $request->order_status;

                $order->update([
                    'order_status' => $to
                ]);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => "Order status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Order updated to {$to}",
                ]);
            }
            if ($request->mname === 'update_payment_status') {

                $from = $order->payment_status;
                $to = $request->payment_status;

                $order->update([
                    'payment_status' => $to
                ]);
                $this->syncOrderStatus($order);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'payment_updated',
                    'description' => "Payment status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Payment updated to {$to}",
                ]);
            }
            if($request->mname === 'mark_as_paid'){
                $from = $order->payment_status;
                $order->update(['payment_status' => 'paid','order_status' => 'processing']);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'payment_updated',
                    'description' => 'Payment status changed',
                    'meta' => [
                        'from' => $from,
                        'to' => 'paid'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order marked as paid",]);
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
            if ($request->mname === 'add_tracking') {

                $order->update([
                    'tracking_number' => $request->tracking_number,
                    'shipment_name' => $request->courier ?? "Royal Mail",
                    'fulfillment_status' => 'fulfilled'
                ]);
                $this->syncOrderStatus($order);

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'tracking_added',
                    'description' => 'Tracking added manually',
                    'meta' => [
                        'tracking_number' => $request->tracking_number
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tracking added'
                ]);
            }
        }
        if(!$order){
            return response()->json(['success'=>false,'message'=>'Order not found']);
        }
        return response()->json(['success'=>true,'order'=>$order]);
    }

    private function syncOrderStatus($order)
    {
        if ($order->order_status === 'archived') {
            return;
        }

        // Completed
        if ($order->fulfillment_status === 'fulfilled') {
            $order->order_status = 'completed';
        }
        // Processing (paid but not fulfilled)
        elseif ($order->payment_status === 'paid') {
            $order->order_status = 'processing';
        }
        // Default
        else {
            $order->order_status = 'pending';
        }

        $order->save();
    }

    private function canTransition($order, $action, $request)
    {
        switch ($action) {

            case 'update_fulfillment_status':
                $to = $request->fulfillment_status;

                if ($to === 'picking') {
                    return $order->payment_status === 'paid'
                        && $order->fulfillment_status === 'unfulfilled';
                }

                if ($to === 'picked') {
                    return in_array($order->fulfillment_status, ['picking']);
                }

                if ($to === 'packed') {
                    return $order->fulfillment_status === 'picked';
                }

                if ($to === 'fulfilled') {
                    return $order->fulfillment_status === 'packed';
                }

                return false;

            case 'update_order_status':
                if ($request->order_status === 'processing') {
                    return $order->payment_status === 'paid';
                }

                if ($request->order_status === 'completed') {
                    return in_array($order->fulfillment_status, ['packed','fulfilled']);
                }
                return false;

            case 'mark_as_paid':
                return in_array($order->payment_status, ['pending','unpaid']);

            default:
                return true;
        }
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

    public function allCarts(Request $request){
        $shop = session('shop');
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $query = Acart::query()
            ->with('customer','order')
            ->withCount('items')
            ->where('shop_id','=',$shopId);
        if ($status && $status !== 'All') {
            $query->where('cart_status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('checkout_id', 'LIKE', "%{$search}%")
                    ->orWhere('acart_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('fname', 'LIKE', "%{$search}%")
                            ->orWhere('lname', 'LIKE', "%{$search}%");
                    });
            });
        }
        $acarts = $query
            ->latest()
            ->paginate(50);
        $checkoutIds = $acarts->getCollection()->pluck('checkout_id');
        $vivaPayments = VivaPayment::whereIn('order_code', $checkoutIds)
            ->get()
            ->keyBy('order_code');
        foreach ($acarts as $acart) {
            $acart->vpayment_id = $vivaPayments[$acart->checkout_id] ?? null;
        }
        $statusCounts = Acart::where('shop_id','=',$shopId)->selectRaw('cart_status, COUNT(*) as total')
            ->groupBy('cart_status')
            ->pluck('total', 'cart_status');

        return response()->json([
            'success' => true,
            'acarts' => $acarts,
            'statusCounts' => $statusCounts
        ]);
    }

    public function getCartById($cart_id)
    {
        $shop = session('shop');
        $shopId = session('shop_id');
//        $cart = Cart::with('order','cartItems.product','cartItems.variant','customer')
//            ->withCount('cartItems')
//            ->where('cart_id','=',$cart_id)
//            ->where('shop_id','=',$shopId)
//            ->first();
        $cart = Acart::with('order.orderItems','items.product','items.variant','customer','aevents')
            ->withCount('items')
            ->where('acart_id','=',$cart_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $vpay = VivaPayment::where('order_code','=',$cart->checkout_id)->first();
        $customer = $cart->customer;
        foreach ($cart->aevents as $aevent){
            $eventdata = is_array($aevent->event_data)
                ? $aevent->event_data
                : json_decode($aevent->event_data, true);
            $ordercode = $eventdata['orderCode'] ?? null;
            if($ordercode){
                $vpay = VivaPayment::where('order_code','=',$ordercode)->first();
                $aevent['vpayment'] = $vpay;
            } else {
                $aevent['vpayment'] = null;
            }
        }
        if($customer){
            $vpays = VivaPayment::where('email',$customer->email)->get();
            $cart['vpayments'] = $vpays;
        } else {
            $cart['vpayments'] = null;
        }

        if($vpay){
            $cart['vpayment_id'] = $vpay;
        } else{
            $cart['vpayment_id'] = null;
        }
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

    public function allProducts(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $type = $request->type;
        $brand = $request->brand;
        $tag = $request->tag;
        $status = $request->status;
        $query = Product::withTrashed()
            ->with('variants','brand','ptype')
            ->withCount('astock')
            ->withSum('astock','quantity')
            ->where('shop_id','=',$shopId);
//        $query->select(['products.*', DB::raw("CASE WHEN deleted_at IS NOT NULL THEN 'Archived' ELSE product_status END as display_status")]);
        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('title', 'LIKE', "%{$term}%")
                            ->orWhereHas('variants', function ($variantQ) use ($term) {
                                $variantQ->where('sku', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('brand', function ($brandQ) use ($term) {
                                $brandQ->where('brand_name', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('ptype', function ($typeQ) use ($term) {
                                $typeQ->where('product_type_name', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }
        $allowedSorts = [
            'title' => 'title',
            'quantity' => 'stocks.quantity',
            'product_status' => 'product_status',
        ];
        if ($type) {
            $query->whereHas('ptype', function ($q) use ($type) {
                $q->where('product_type_name', $type);
            });
        }
        if ($brand) {
            $query->whereHas('brand', function ($q) use ($brand) {
                $q->where('brand_name', $brand);
            });
        }
        if ($tag) {
            $query->whereJsonContains('tags', $tag);
        }
        if ($status && $status !== 'All') {

            if ($status === 'Archived') {

                $query->whereNotNull('deleted_at');

            } else {

                $query->whereNull('deleted_at')
                    ->where('product_status', $status);
            }
        }
        $filters = [
            'brands' => Brand::where('shop_id', $shopId)
                ->orderBy('brand_name')
                ->pluck('brand_name'),

            'types' => ProductType::where('shop_id', $shopId)
                ->orderBy('product_type_name')
                ->pluck('product_type_name'),

            'tags' => Product::where('shop_id', $shopId)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values(),
        ];
        $sortBy = $allowedSorts[$request->sort_by] ?? 'product_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $products = $query->paginate($perPage);
        $aptags = Tag::where('shop_id','=',$shopId)
            ->select('tag_id','tag_name','tag_status')
            ->orderBy('tag_name')
            ->get();
        return response()->json([
            'users' => auth()->user(),
            'pcount'=> $products->count(),
            'products' => $products,
            'filters' => $filters,
            'aptags' => $aptags,
        ],200);
    }

    public function getProductbyId($product_id)
    {
        $shopId = session('shop_id');
        $product = Product::withTrashed()->where('product_id','=', $product_id)
            ->with('brand','ptype','variants.astock','sections','highs','specifics','tiers')
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
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        $brands = Brand::where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::where('shop_id','=',$shopId)
            ->orderBy('tag_name')
            ->get();
        $poptions = Poptions::where('shop_id','=',$shopId)
            ->get();
        return response()->json([
            'location' => $location,
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
            'poptions' => $poptions
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

    public function updateUnitPack(Request $request)
    {
        $shopId = session('shop_id');
        $unitPackQty = null;
        if($request->unit_pack_qty <= 0){
            $unitPackQty = null;
        } else {
            $unitPackQty = $request->unit_pack_qty;
        }
        $product = Product::where('product_id',$request->product_id)
            ->where('shop_id', $shopId)
            ->update([
                'unit_name' => $request->unit_name ?? "Unit",
                'unit_pack_qty' => $unitPackQty ?? null,
            ]);
        return response()->json([
            'success' => true,
            'message' => 'Unit Pack updated successfully',
            'product' => $product,
        ]);
    }

    public function saveTierPricing(Request $request)
    {
        $shopId = session('shop_id');
        // ✅ Basic validation
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'tiers' => 'nullable|array',
        ]);

        // ✅ Validate tiers only if present
        if (!empty($request->tiers)) {
            $request->validate([
                'tiers.*.min_qty' => 'required|integer|min:2',
                'tiers.*.price' => 'required|numeric|min:0.01',
                'tiers.*.pricing_type' => 'required|in:fixed,percentage',
            ]);
        }
        $product = Product::where('product_id', $request->product_id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$product) {return response()->json(['success' => false, 'message' => 'Product not found']);}

        // ✅ Normalize tiers (important for empty case)
        $tiers = collect($request->input('tiers', []))
            ->map(function ($t) {
                return [
                    'min_qty' => (int) $t['min_qty'],
                    'price' => (float) $t['price'],
                    'pricing_type' => $t['pricing_type'],
                ];
            })
            ->sortBy('min_qty')
            ->values()
            ->toArray(); // 👈 important (avoid collection issues in loops)

        // ✅ Backend validation (sequence + price logic)
        for ($i = 0; $i < count($tiers); $i++) {
            $current = $tiers[$i];
            if ($i > 0) {
                $prev = $tiers[$i - 1];
                if ($current['min_qty'] <= $prev['min_qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tier " . ($i + 1) . " qty must be greater than previous"
                    ]);
                }
                if ($current['price'] >= $prev['price']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Tier " . ($i + 1) . " price must be less than previous"
                    ]);
                }
            }
        }

        DB::transaction(function () use ($tiers, $product, $shopId) {

            // delete old
            ProductPriceTier::where('product_id', $product->product_id)->delete();

            // ✅ If empty → just return (means remove all tiers)
            if (empty($tiers)) {
                return;
            }
            // ✅ Insert new tiers
            foreach ($tiers as $tier) {
                ProductPriceTier::create([
                    'product_id' => $product->product_id,
                    'shop_id' => $shopId,
                    'min_qty' => $tier['min_qty'],
                    'price' => $tier['price'],
                    'pricing_type' => $tier['pricing_type'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => empty($tiers)
                ? 'Tier pricing removed successfully'
                : 'Tier pricing saved successfully',
            'product'=> $product,
        ]);


    }

    public function allCats()
    {
        $shopId = session('shop_id');
        $cats = Cat::query()
            ->select('cat_id', 'cat_name','cat_image','cat_slug','cat_status','cat_type','shop_id','updated_at')
            ->with('rules')->withCount('catpros')
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
        $brands = Brand::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::query()
            ->where('shop_id','=',$shopId)
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
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
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
        $rules = $request->input('rules', []);
        $catRule = strtolower($request->input('cat_rule', 'and'));
        $query = Product::query()->where('shop_id','=',$shopId);
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
        $brands = Brand::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $cats = Cat::query()
            ->where('shop_id','=',$shopId)
            ->get();
        return response()->json([
            'cat' => $cat,
            'pros' => $pros,
            'location' => $location,
            'stypes' => $stypes,
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
            'cats'=> $cats,
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

    public function allInventory(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $type = $request->type;
        $brand = $request->brand;
        $tag = $request->tag;
        $status = $request->status;

        $query = Variant::Join('products','products.product_id','=','variants.product_id')
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
            ->where('variants.shop_id','=',$shopId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.title', 'LIKE', "%{$search}%")
                    ->orWhere('variants.sku', 'LIKE', "%{$search}%")
                    ->orWhere('brands.brand_name', 'LIKE', "%{$search}%")
                    ->orWhere('product_types.product_type_name', 'LIKE', "%{$search}%");
            });
        }
        $allowedSorts = [
            'title' => 'products.title',
            'sku' => 'variants.sku',
            'quantity' => 'stocks.quantity',
            'committed' => 'committed',
            'backorder_qty' => 'backorder_qty',
            'product_status' => 'products.product_status',
        ];
        if ($type) {
            $query->where('product_types.product_type_name', $type);
        }
        if ($brand) {
            $query->where('brands.brand_name', $brand);
        }
        if ($tag) {
            $query->whereJsonContains('products.tags', $tag);
        }
        $this->applyProductStatusFilter($query, $status);
        $filters = [
            'brands' => Brand::where('shop_id', $shopId)
                ->orderBy('brand_name')
                ->pluck('brand_name'),

            'types' => ProductType::where('shop_id', $shopId)
                ->orderBy('product_type_name')
                ->pluck('product_type_name'),

            'tags' => Product::where('shop_id', $shopId)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values(),
        ];

        $sortBy = $allowedSorts[$request->sort_by] ?? 'variants.variant_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = $query->count();
        }
        $perPage = $perPage > 0 ? $perPage : 50;
        $perPage = min($perPage, 500);
        $variants = $query->paginate($perPage);

        return response()->json([
            'variants' => $variants,
            'filters' => $filters,
        ],200);
    }

    private function applyProductStatusFilter($query, $status)
    {
        if (!$status || $status === 'All') {
            return;
        }

        if ($status === 'Archived') {
            $query->whereNotNull('products.deleted_at');
        } else {
            $query->whereNull('products.deleted_at')
                ->where('products.product_status', $status);
        }
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

    public function allCustomers(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $query = Customer::query()->with(['defaultaddress'])
            ->join('customer_shops', 'customer_shops.customer_id', '=', 'customers.customer_id')
            ->where('customer_shops.shop_id','=',$shopId)
            ->select('customers.*','customer_shops.ctags','customer_shops.shop_id','customer_shops.status as cstatus')
            ->withCount([
                'orders as ordercount' => function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            ])
            ->withSum([
                'orders as amount_spent' => function ($q) use ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            ], 'order_total');
        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('customers.fname', 'LIKE', "%{$term}%")
                            ->orWhere('customers.lname', 'LIKE', "%{$term}%")
                            ->orWhere('customers.email', 'LIKE', "%{$term}%")
                            ->orWhere('customers.phone', 'LIKE', "%{$term}%");
                    });
                }
            });
        }
        if ($status && $status !== 'All') {
            $query->where('customer_shops.status', $status);
        }
        $allowedSorts = [
            'fname' => 'customers.fname',
            'email' => 'customers.email',
            'created_at' => 'customers.created_at',
            'ordercount' => 'ordercount',
            'amount_spent' => 'amount_spent',
        ];
        $sortBy = $allowedSorts[$request->sort_by]
            ?? 'customers.created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0 ? min($perPage, 500) : 50;
        }
        $customers = $query->paginate($perPage);
        $customers->getCollection()->transform(function ($customer) {
            $customer->ctags = json_decode($customer->ctags, true);
            return $customer;
        });
        $shopusers = ShopUser::join('users','users.id','=','shop_users.user_id')
            ->whereNot('shop_users.role' ,'=','superadmin')
            ->select('shop_users.*','users.name','users.email','users.email_verified_at')
            ->where('shop_id','=',$shopId)->get();
        return response()->json([
            'shopusers' => $shopusers,
            'customers' => $customers
        ],200);
    }

    public function checkCustomerExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = strtolower(trim($request->email));
        $shopId = session('shop_id');

        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$customer) {
            return response()->json([
                'exists' => false
            ]);
        }

        $inCurrentShop = CustomerShop::where('customer_id', $customer->customer_id)
            ->where('shop_id', $shopId)
            ->exists();

        return response()->json([
            'exists' => true,
            'in_current_shop' => $inCurrentShop,
            'customer_id' => $customer->customer_id
        ]);
    }

    public function attachCustomer(Request $request)
    {
        $shopId = session('shop_id');

        CustomerShop::firstOrCreate([
            'customer_id' => $request->customer_id,
            'shop_id' => $shopId,
            'registered_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    public function addNewCustomer(Request $request)
    {
        $request->validate([
            'fname' => 'required|string|min:2|max:60',
            'lname' => 'required|string|min:2|max:60',
            'email' => 'required|email|max:80',
            'phone' => 'required|digits:10',
        ]);
        $shopId = session('shop_id');
        $email = strtolower(trim($request->email));
        DB::beginTransaction();
        try {
            // 1. Check if customer already exists globally
            $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
            if (!$customer) {
                // 2. Create new customer
                $customer = Customer::create([
                    'fname' => $request->fname,
                    'lname' => $request->lname,
                    'email' => $email,
                    'password'=>bcrypt('123456'),
                    'phone' => $request->phone,
                    'status' => 'active',
                ]);
            }

            // 3. Attach to shop (avoid duplicate)
            CustomerShop::firstOrCreate([
                'customer_id' => $customer->customer_id,
                'shop_id' => $shopId,
                'registered_at' => now()
            ]);

            // 4. Optional: create default address
            if ($request->address_line1) {
                CustomerAddress::create([
                    'address_title' => $request->address_title ?? 'Default',
                    'fname' => $customer->fname,
                    'lname' => $customer->lname,
                    'address_line1' => $request->address_line1,
                    'address_line2' => $request->address_line2,
                    'city' => $request->city,
                    'postcode' => $request->postcode,
                    'country' => $request->country ?? "United Kingdom",
                    'phone' => $customer->phone,
                    'is_default' => true,
                    'customer_id' => $customer->customer_id,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

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

    public function allPtypes(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $allowedSorts = [
            'product_type_name' => 'product_type_name',
            'product_type_status' => 'product_type_status',
            'products_count' => 'products_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $query = ProductType::query()
            ->where('shop_id', $shopId)
            ->withCount(['products']);
        if ($search) {
            $terms = explode(' ', $search);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('product_type_name', 'LIKE', "%{$term}%");
                }
            });
        }
        if ($status && $status !== 'All') {
            $query->where('product_type_status', $status);
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'product_type_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $ptypes = $query->paginate($perPage);
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

    public function allBrands(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $allowedSorts = [
            'brand_name' => 'brand_name',
            'brand_status' => 'brand_status',
            'products_count' => 'products_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $query = Brand::query()
            ->where('shop_id', $shopId)
            ->withCount(['products']);
        if ($search) {
            $terms = explode(' ', $search);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('brand_name', 'LIKE', "%{$term}%");
                }
            });
        }
        if ($status && $status !== 'All') {
            $query->where('brand_status', $status);
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'brand_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $brands = $query->paginate($perPage);
        return response()->json([
            'brands' => $brands
        ],200);
    }

    public function brandById($brand_id)
    {
        $shopId = session('shop_id');
        $brand = Brand::find($brand_id);
        if(!$brand){
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ]);
        }
        $brands = Brand::where('shop_id', $shopId)->select('brand_id','brand_name')->get();
        return response()->json([
            'status' => 200,
            'brand' => $brand,
            'brands' => $brands,
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

    public function allTags(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $allowedSorts = [
            'tag_name' => 'tag_name',
            'tag_status' => 'tag_status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $query = Tag::query()
            ->where('shop_id', $shopId);
        if ($search) {
            $terms = explode(' ', $search);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('tag_name', 'LIKE', "%{$term}%");
                }
            });
        }
        if ($status && $status !== 'All') {
            $query->where('tag_status', $status);
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'tag_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $tags = $query->paginate($perPage);
        foreach ($tags as $tag) {
            $tag['pcount'] = Product::whereJsonContains('tags', $tag->tag_name)
                ->where('shop_id', $shopId)
                ->count();
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

    public function allPoptions(Request $request)
    {
        $shopId = session('shop_id');
        $poptions = Poptions::where('shop_id','=',$shopId)->get();
        foreach($poptions as $poption){
            $opname = $poption->option_name;
            $poption['usedcount'] = Variant::whereJsonContains('options',$opname)
                ->where('shop_id','=',$shopId)->count();
        }
        return response()->json([
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

    public function deleteAdminShipMethod(Request $request)
    {
        $shopId = session('shop_id');
        $shipMethod = ShipMethod::where('ship_method_id','=',$request->ship_method_id)
            ->where('shop_id','=',$shopId)
            ->first();
        if($shipMethod){
            $shipMethod->delete();
            return response()->json([
                'success' => true,
                'message'=> "Ship Method Deleted Successfully",
                'shipMethod' => $shipMethod,
            ]);
        } else {
            return response()->json(['success' => false,'message' => "Method not Found"]);
        }
    }

    public function getAdminShopPaymentMethods()
    {
        $shopId = session('shop_id');
        $spmethods = ShopPaymentMethod::where('shop_id', $shopId)
            ->orderBy('sort_order', 'asc')
            ->get();
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
                    'handling_fee' => $request->handling_fee ?? 0,
                    'fee_type' => $request->fee_type ?? "fixed",
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
                'home_description' => 'Home Page Description',
                'home_image' => null,
                'shop_logo' => null,
                'social_links' => [],
                'shop_id' => $shopId,
            ]
        );
        return response()->json([
            'success' => true,
            'preferences' => $preferences,
            'shop' => $shopId,
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
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_id','brand_name','brand_slug','brand_image')
            ->get();
        $tags = Tag::where('shop_id','=',$shopId)
            ->select('tag_id','tag_name')
            ->get();
        $ptypes = ProductType::where('shop_id','=',$shopId)
            ->select('product_type_id','product_type_name')
            ->get();
        if($blog){
            return response()->json([
                'status'=> true,
                'blog' => $blog,
                'stypes'=> $stypes,
                'categories'=>$categories,
                'brands' => $brands,
                'tags' => $tags,
                'ptypes' => $ptypes,
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
                    'user_id' => $request->user_id ?? auth()->user()->id,
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
                    'user_id' => $request->user_id ?? auth()->user()->id,
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
        $brands = Brand::where('shop_id','=',$shopId)->get();
        $cats = Cat::where('shop_id','=',$shopId)->get();
        if($menu){
            return response()->json([
                'success' => true,
                'message' => 'Menu with its Items',
                'menu' => $menu,
                'brands' => $brands,
                'cats' => $cats,
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
            'message' => 'Cart page',
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
        $setting = [
            "background" => '#000000',
            "color"=> '#ffffff',
            "underline"=>'#bb0000',
            "columns"=>[
                "desktop" => 4,
                "tablet"=>2,
                "mobile"=>1
            ]
        ];
        $footer = Footer::where('shop_id', $shopId)->firstOrCreate(
            ['shop_id' => $shopId],
            [
                'style' => 'style1',
                'fsections' => null,
                'settings' => $setting,
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
            'business' => $business['business'] ?? null,
            'quick_links' => Menu::where('menu_slug','quick_links')->where('shop_id', $shopId)->first() ?? null,
            'information' => Menu::where('menu_slug','information')->where('shop_id', $shopId)->first() ?? null,
            'our_policies' => Menu::where('menu_slug','our_policies')->where('shop_id', $shopId)->first() ?? null,
        ]);
    }

    public function updateFooter(Request $request)
    {
        $shopId = session('shop_id');
        $footer = Footer::findOrFail($request->footer_id);
        $setting = [
            "background" => '#000000',
            "color"=> '#ffffff',
            "underline"=>'#bb0000',
            "columns"=>[
                "desktop" => 4,
                "tablet"=>2,
                "mobile"=>1
            ]
        ];
        if($footer){
            $footer->update([
                'style' => $request['style'] ?? 'style1',
                'fsections' => $request['fsections'],
                'settings' => $setting,
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

    public function getAdminPricingRules()
    {
        $shopId = session('shop_id');
        $rules = PricingRule::with(['products', 'cats'])
            ->where('shop_id',$shopId)
            ->orderBy('priority', 'asc')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'type' => $rule->type,
                    'scope' => $rule->scope,
                    'min_qty' => $rule->min_qty,
                    'price' => $rule->price,
                    'discount_percent' => $rule->discount_percent,
                    'priority' => $rule->priority,
                    'is_active' => $rule->is_active,
                    'starts_at' => $rule->starts_at,
                    'expires_at' => $rule->expires_at,

                    // 👇 important for UI editing
                    'products' => $rule->products->pluck('product_id'),
                    'cats' => $rule->cats->pluck('cat_id'),
                    'is_currently_active' => $rule->isCurrentlyActive(),
                    'label' => $rule->type === 'bundle'
                        ? "Buy {$rule->min_qty} for £{$rule->price}"
                        : "{$rule->discount_percent}% off {$rule->min_qty}+",
                ];
            });
        ;
        return response([
            'success'=>true,
            'rules'=> $rules
        ]);
    }

    public function saveAdminPricingRule(Request $request)
    {
        $shopId = session('shop_id');
        // ✅ Base validation
        $request->validate([
            'id' => 'nullable|exists:pricing_rules,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:bundle,volume',
            'scope' => 'required|in:all,products,cats',
            'min_qty' => 'required|integer|min:2',
            'price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:1|max:100',
            'products' => 'nullable|array',
            'cats' => 'nullable|array',
            'priority' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean'
        ]);

        // ✅ Type-specific validation
        if ($request->type === 'bundle' && !$request->price) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle price is required'
            ]);
        }

        if ($request->type === 'volume' && !$request->discount_percent) {
            return response()->json([
                'success' => false,
                'message' => 'Discount percent is required'
            ]);
        }

        // ✅ Auto name (fallback)
        $name = $request->name ?: (
        $request->type === 'bundle'
            ? "Buy {$request->min_qty} for £{$request->price}"
            : "{$request->discount_percent}% off {$request->min_qty}+"
        );
//        dd($name,$request->all());

        DB::transaction(function () use ($request, $shopId, $name) {

            // ✅ Create or Update
            $rule = PricingRule::updateOrCreate(
                [
                    'id' => $request->id,
                    'shop_id' => $shopId
                ],
                [
                    'name' => $name,
                    'type' => $request->type,
                    'scope' => $request->scope,
                    'min_qty' => $request->min_qty,
                    'price' => $request->price,
                    'discount_percent' => $request->discount_percent,
                    'priority' => $request->priority ?? 0,
                    'starts_at' => $request->starts_at,
                    'expires_at' => $request->expires_at,
                    'is_active' => $request->is_active ?? true,
                ]
            );
            // ✅ Sync Products
            if ($request->scope === 'products') {
                $rule->products()->sync($request->products ?? []);
            } else {
                $rule->products()->detach();
            }

            // ✅ Sync Categories
            if ($request->scope === 'cats') {
                $rule->cats()->sync($request->cats ?? []);
            } else {
                $rule->cats()->detach();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pricing rule saved successfully',
        ]);
    }

    public function deleteAdminPricingRule(Request $request)
    {
        $shopId = session('shop_id');
        $request->validate([
            'id' => 'required|exists:pricing_rules,id'
        ]);

        $rule = PricingRule::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Rule not found'
            ]);
        }

        DB::transaction(function () use ($rule) {
            // detach relations (optional but clean)
            $rule->products()->detach();
            $rule->cats()->detach();

            // delete rule
            $rule->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pricing rule deleted successfully'
        ]);
    }

    public function getAdminCouponsList()
    {
        $shopId = session('shop_id');
        $coupons = Coupon::with(['products','cats'])
            ->where('shop_id', $shopId)
            ->orderBy('priority', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'coupon_id' => $c->coupon_id,
                    'code' => $c->code,
                    'title' => $c->title,
                    'display_title' => $c->display_title ?? $this->makeLabel($c),
                    'is_auto' => $c->is_auto,
                    'is_editable_code' => !$c->is_auto,
                    'type' => $c->type,
                    'type_label' => ucfirst($c->type),
                    'value' => $c->value,
                    'applies_to' => $c->applies_to,
                    'min_order_amount' => $c->min_order_amount,
                    'usage_limit' => $c->usage_limit,
                    'per_customer_limit' => $c->per_customer_limit,
                    'is_active' => $c->is_active,
                    'is_stackable' => $c->is_stackable,
                    'priority' => $c->priority,
                    'starts_at' => $c->starts_at,
                    'expires_at' => $c->expires_at,

                    // ✅ relations (for form binding)
                    'products' => $c->products->pluck('product_id')->toArray(),
                    'cats' => $c->cats->pluck('cat_id')->toArray(),

                    // ✅ conditions (critical)
                    'conditions' => $c->conditions ?? [],
                    'has_conditions' => !empty($c->conditions),

                    // ✅ UI helpers
                    'label' => $this->makeLabel($c),
                    'is_currently_active' => $this->isActiveNow($c),

                ];
            });
        $cats = Cat::where('shop_id','=',$shopId)->get();
        $products = Product::where('shop_id','=',$shopId)->get();
        return response()->json([
            'success' => true,
            'coupons' => $coupons,
            'cats' => $cats,
            'products' => $products,
        ]);
    }

    private function makeLabel($c)
    {
        if ($c->type === 'fixed') {
            return "£{$c->value} off";
        }

        if ($c->type === 'percentage') {
            return "{$c->value}% off";
        }

        if ($c->type === 'bogo') {
            return "Buy One Get One";
        }

        if ($c->type === 'bundle') {
            return "Bundle Offer";
        }

        return '';
    }

    private function isActiveNow($c)
    {
        $now = now();

        return $c->is_active &&
            (!$c->starts_at || $c->starts_at <= $now) &&
            (!$c->expires_at || $c->expires_at >= $now);
    }

    public function saveAdminCoupon(Request $request)
    {
        $shopId = session('shop_id');
        // ✅ validation
        $request->validate([
            'coupon_id' => 'nullable|exists:coupons,coupon_id',
            'code' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'display_title' => 'nullable|string|max:255',
            'is_auto' => 'boolean',
            'type' => 'required|in:fixed,percentage,bogo,bundle',
            'value' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:entire_order,products,cats',
            'products' => 'nullable|array',
            'cats' => 'nullable|array',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
            'conditions' => 'nullable|array',
            'conditions.min_qty' => 'nullable|integer|min:1',
            'conditions.product_ids' => 'nullable|array',
            'conditions.category_ids' => 'nullable|array',
            'conditions.buy_qty' => 'nullable|integer|min:1',
            'conditions.get_qty' => 'nullable|integer|min:1',
            'conditions.bundle_qty' => 'nullable|integer|min:1',
            'conditions.bundle_price' => 'nullable|numeric|min:0.01',
        ]);
        $isAuto = $request->is_auto ?? false;
        $code = $request->code
            ? strtoupper(preg_replace('/\s+/', '', $request->code))
            : null;
        if ($isAuto && !$code) {
            if($request->type === 'bogo'){
                $code = 'BOGO_' . strtoupper(substr(md5(uniqid()), 0, 8));
            } elseif ($request->type === 'bundle') {
                $code = 'BUNDLE_' . strtoupper(substr(md5(uniqid()), 0, 8));
            } else {
                $code = 'AUTO_' . strtoupper(substr(md5(uniqid()), 0, 8));
            }
        }
        if ($code) {
            $exists = Coupon::where('shop_id', $shopId)
                ->where('code', $code)
                ->when($request->coupon_id, function ($q) use ($request) {
                    $q->where('coupon_id', '!=', $request->coupon_id);
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coupon code already exists'
                ]);
            }
        }
        $conditions = $request->conditions ?? [];
        if ($request->type === 'bogo') {
            if (empty($conditions['buy_qty']) || empty($conditions['get_qty'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'BOGO requires buy & get qty'
                ]);
            }
        }

        if ($request->type === 'bundle') {
            if (empty($conditions['bundle_qty']) || empty($conditions['bundle_price'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bundle requires qty & price'
                ]);
            }
        }


        // ✅ type-specific validation
        if (in_array($request->type, ['fixed', 'percentage']) && !$request->value) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon value is required'
            ]);
        }
        if ($request->applies_to === 'products' && empty($request->products)) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one product'
            ]);
        }

        if ($request->applies_to === 'cats' && empty($request->cats)) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one category'
            ]);
        }

        DB::transaction(function () use ($request, $shopId, $code, $isAuto) {

            $conditions = $request->conditions ?? null;

            if ($conditions) {
                $conditions = collect($conditions)
                    ->filter(function ($v) {
                        return !is_null($v) && $v !== '' && $v !== [];
                    })
                    ->toArray();
            }
            // clean by type
            if ($request->type !== 'bogo') {
                unset($conditions['buy_qty'], $conditions['get_qty']);
            }

            if ($request->type !== 'bundle') {
                unset($conditions['bundle_qty'], $conditions['bundle_price']);
            }

            $value = $request->value ?? 0;
            if (in_array($request->type, ['bogo', 'bundle'])) {
                $value = 0;
            }
            if($request->coupon_id){
                $coupon = Coupon::where('coupon_id', $request->coupon_id)
                    ->where('shop_id', $shopId)
                    ->firstOrFail();
                $coupon->update([
                    'code' => $code,
                    'title' => $request->title,
                    'display_title' => $request->display_title,
                    'is_auto' => $isAuto,
                    'type' => $request->type,
                    'value' => $value,
                    'applies_to' => $request->applies_to,
                    'min_order_amount' => $request->min_order_amount,
                    'usage_limit' => $request->usage_limit,
                    'per_customer_limit' => $request->per_customer_limit,
                    'is_active' => $request->is_active ?? true,
                    'is_stackable' => $request->is_stackable ?? false,
                    'priority' => $request->priority ?? 0,
                    'starts_at' => $request->starts_at,
                    'expires_at' => $request->expires_at,
                    'conditions' => !empty($conditions) ? $conditions : null,
                ]);
            } else {
                $coupon = Coupon::create([
                    'shop_id' => $shopId,
                    'code' => $code,
                    'title' => $request->title,
                    'display_title' => $request->display_title,
                    'is_auto' => $isAuto,
                    'type' => $request->type,
                    'value' => $value,
                    'applies_to' => $request->applies_to,
                    'min_order_amount' => $request->min_order_amount,
                    'usage_limit' => $request->usage_limit,
                    'per_customer_limit' => $request->per_customer_limit,
                    'is_active' => $request->is_active ?? true,
                    'is_stackable' => $request->is_stackable ?? false,
                    'priority' => $request->priority ?? 0,
                    'starts_at' => $request->starts_at,
                    'expires_at' => $request->expires_at,
                    'conditions' => !empty($conditions) ? $conditions : null,
                ]);
            }

            // ✅ sync products
            if ($request->applies_to === 'products') {
                $coupon->products()->sync($request->products ?? []);
            } else {
                $coupon->products()->detach();
            }

            // ✅ sync categories
            if ($request->applies_to === 'cats') {
                $coupon->cats()->sync($request->cats ?? []);
            } else {
                $coupon->cats()->detach();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Coupon saved successfully'
        ]);
    }

    public function deleteAdminCoupon(Request $request)
    {
        $shopId = session('shop_id');
        $request->validate([
            'coupon_id' => 'required|exists:coupons,coupon_id'
        ]);
        $coupon = Coupon::where('coupon_id', $request->coupon_id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ]);
        }

        DB::transaction(function () use ($coupon) {

            // ✅ detach relations
            $coupon->products()->detach();
            $coupon->cats()->detach();

            // optional: clear usages (depends on your strategy)
            // CouponUsage::where('coupon_id', $coupon->coupon_id)->delete();

            // delete coupon
            $coupon->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully'
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

    public function invoice($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $shop = Shop::find($order->shop_id);
        $preference = Preference::find($order->shop_id);
        $business = BusinessShop::leftjoin('businesses','businesses.business_id','=','business_shops.business_id')
            ->where('business_shops.shop_id','=',$order->shop_id)
            ->first();
        $pdf = Pdf::loadView('pdf.invoice', compact('order','shop','preference','business'));

        return $pdf->download("invoice-{$order->order_id}.pdf");
    }

    public function label($id)
    {
        $order = Order::findOrFail($id);

        $pdf = Pdf::loadView('pdf.label', compact('order'))
            ->setPaper([0, 0, 288, 432]); // 4x6 inch

        return $pdf->download("label-{$order->order_id}.pdf");
    }
}
