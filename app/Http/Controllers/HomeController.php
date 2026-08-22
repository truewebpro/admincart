<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Exceptions\SendcloudApiException;
use App\Exports\ProductsExport;
use App\Exports\SelectedProductsExport;
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
use App\Models\Footer;
use App\Models\Highlight;
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
use App\Models\ProductPriceTier;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\RelatedCat;
use App\Models\Reviewer;
use App\Models\Searchbrand;
use App\Models\Searchcat;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\Sendcloud;
use App\Models\Setting;
use App\Models\ShipMethod;
use App\Models\Shop;
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
use App\Services\CacheKeys;
use App\Services\MailtrapService;
use App\Services\Sendcloud\SendcloudShippingOptionSyncer;
use App\Services\SmartCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                    'free_delivery_amount' => 40,
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
        $setting->free_delivery_amount = $request->free_delivery_amount ?? 40;
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

    public function shopSelectedProductsExport(Request $request)
    {
        $shopId = session('shop_id');
        $productIds = $request->product_ids;
        return Excel::download(
            new SelectedProductsExport($shopId, $productIds),
            'selected_products.csv'
        );
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

    public function getHomePage()
    {
        $shopId = session('shop_id');
        $homepage = Homepage::with('sections','faqs')->firstOrCreate(
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

    public function dashboard(Request $request)
    {
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

    public function allCarts(Request $request){
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
        $shopId = session('shop_id');
        $cart = Acart::with('order.orderItems','items.product','items.variant','customer','address','aevents')
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

    public function shippingSettings()
    {
        $shopId = session('shop_id');
        $location = Location::where('shop_id','=',$shopId)->first();
        $sendcloud = Sendcloud::firstOrNew([
            'shop_id' => session('shop_id')
        ]);
        $ship_methods = ShipMethod::with('courier')
            ->where('shop_id','=',$shopId)
            ->get();

        return response()->json([
            'status'=> true,
            'couriers' => Courier::all(),
            'ship_methods' => $ship_methods ?? [],
            'location' => $location ?? null,
            'sendcloud' => $sendcloud ?? null,
        ]);

    }

    public function updateSendCloud(Request $request)
    {
        $shopId = session('shop_id');

        $sendcloud = Sendcloud::updateOrCreate(
            ['shop_id' => $shopId],
            [
                'public_key' => $request->public_key,
                'secret_key' => $request->secret_key,
                'api_version' => $request->api_version ?? 'v3',
                'is_active' => $request->is_active,
            ]
        );

        // Hard requirement — without shipping options, the shop can't create any labels at all.
        try {
            app(SendcloudShippingOptionSyncer::class)->sync($sendcloud);
        } catch (SendcloudApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sendcloud credentials saved, but could not fetch shipping options — please verify your keys.',
                'error' => $e->getErrorBody() ?? $e->getMessage(),
            ], 422);
        }

        // Soft requirement — shop may not have finished configuring a sender address in
        // their Sendcloud panel yet. Don't block the whole save over this; just warn.
        $senderAddressWarning = null;
        try {
            $this->syncDefaultSenderAddress($sendcloud);
        } catch (SendcloudApiException $e) {
            Log::warning('Sendcloud sender address sync failed', [
                'shop_id' => $shopId,
                'sendcloud_id' => $sendcloud->id,
                'error' => $e->getErrorBody() ?? $e->getMessage(),
            ]);
            $senderAddressWarning = 'Could not fetch a sender address from Sendcloud — please add one in your Sendcloud panel before creating labels.';
        }

        return response()->json([
            'success' => true,
            'message' => 'Updated Successfully',
            'sendcloud' => $sendcloud->fresh(),
            'warning' => $senderAddressWarning,
        ]);
    }

    private function syncDefaultSenderAddress(Sendcloud $sendcloud): void
    {
        $response = Http::withBasicAuth($sendcloud->public_key, $sendcloud->secret_key)
            ->get('https://panel.sendcloud.sc/api/v2/user/addresses/sender');

        if (!$response->successful()) {
            throw new SendcloudApiException('Failed to fetch sender addresses', $response->status(), $response->json());
        }

        $addresses = $response->json('sender_addresses', []);
        $default = collect($addresses)->firstWhere('is_default', true) ?? $addresses[0] ?? null;

        if ($default) {
            $sendcloud->update(['default_sender_address_id' => $default['id']]);
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

    public function getShopAnnouncements()
    {
        $shopId = session('shop_id');
        $announcement = Announcement::where('shop_id', $shopId)->first();
        return response()->json([
            'success' => true,
            'announcement' => $announcement,
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

    public function getShopSearchBrands()
    {
        $shopId = session('shop_id');
        $searchbrands = Searchbrand::where('shop_id', $shopId)->get();
        $abrands = Brand::where('shop_id', $shopId)->get(['brand_name','brand_slug'])
            ->map(function ($item) {
                return [
                    'ltype'=> 'brands',
                    'name'=> $item->brand_name,
                    'link'=> 'brands/'.$item->brand_slug,
                ];
            })->toArray();
        $allbrands = [['ltype' => 'brands', 'name' => 'All Brands', 'link' => 'brands']];
        $brands = array_merge($allbrands, $abrands);
        return response()->json([
            'success' => true,
            'searchbrands' => $searchbrands,
            'brands' => $brands
        ]);
    }

    public function getShopSearchCats()
    {
        $shopId = session('shop_id');
        $searchcats = Searchcat::where('shop_id', $shopId)->get();
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
        return response()->json([
            'success' => true,
            'searchcats' => $searchcats,
            'cats'=> $cats
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

    public function updateSearchBrand(Request $request)
    {
        $shopId = session('shop_id');
        if($request->mname === 'delete'){
            $searchbrand = Searchbrand::findOrFail($request->sbrand_id);
            $searchbrand->delete();
            return response()->json(['success' => true,'message' => 'Brand deleted successfully']);
        } else {
            $searchbrand = Searchbrand::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'sbrand_id' => $request->sbrand_id,
                ],
                [
                    'title' => $request->title,
                    'link' => $request->link,
                    'status' => $request->status ?? 'active',
                    'shop_id' => $shopId,
                ],
            );
            if($searchbrand){
                return response()->json([
                    'success' => true,
                    'searchbrand' => $searchbrand,
                ]);
            }
        }
    }

    public function updateSearchCat(Request $request)
    {
        $shopId = session('shop_id');
        if($request->mname === 'delete'){
            $searchcat = Searchcat::findOrFail($request->sbrand_id);
            $searchcat->delete();
            return response()->json(['success' => true,'message' => 'Cat deleted successfully']);
        } else {
            $searchcat = Searchcat::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'scat_id' => $request->scat_id,
                ],
                [
                    'title' => $request->title,
                    'link' => $request->link,
                    'status' => $request->status ?? 'active',
                    'shop_id' => $shopId,
                ],
            );
            if($searchcat){
                return response()->json([
                    'success' => true,
                    'searchcat' => $searchcat,
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
}
