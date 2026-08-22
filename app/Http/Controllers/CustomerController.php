<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerShop;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Services\MailtrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerController extends Controller
{
    public function customerLogin(Request $request)
    {
        $shopId = $request->shop_id;
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
        $customer = Customer::where('email','=',$credentials['email'])->first();
        if (!$customer || !Hash::check($credentials['password'], $customer->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $linked = CustomerShop::where('customer_id', $customer->customer_id)
            ->where('shop_id', $shopId)
            ->exists();

        if (!$linked) {
            $linkedtoShop = CustomerShop::create([
                'customer_id' => $customer->customer_id,
                'shop_id' => $shopId,
                'registered_at' => now(),
                'status' => 'active',
                'ctags' => ['b2c'],
            ]);
            if ($linkedtoShop) {
                return response()->json([
                    'success' => true,
                    'token' => JWTAuth::fromUser($customer),
                    'customer' => $customer,
                ]);
            }
            return response()->json(['error' => 'Customer not registered with this shop'], 403);
        }
        $token = JWTAuth::fromUser($customer);

        return response()->json([
            'token' => $token,
            'customer' => $customer,
        ]);
    }

    public function customerRegister(Request $request)
    {
        $shopId = $request->shop_id;
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:100',
            'lname' => 'nullable|string|max:100',
            'email' => 'required|email',
            'password' => 'required|min:5',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors'=> $validator->errors()],422);
        }
        $existingCustomer = Customer::where('email','=',$request->email)->first();
        if ($existingCustomer) {
            $alreadyLinked = CustomerShop::where('customer_id','=',$existingCustomer->customer_id)
                ->where('shop_id','=',$shopId)
                ->exists();
            if(!$alreadyLinked){
                CustomerShop::create([
                    'customer_id' => $existingCustomer->customer_id,
                    'shop_id' => $shopId,
                    'registered_at' => now(),
                    'status' => 'active',
                    'ctags' => ['b2c'],
                ]);
            }
            $token = JWTAuth::fromUser($existingCustomer);
            return response()->json(['token' => $token],200);
        }
        $customer = Customer::create([
            'fname' => $request->fname,
            'lname'    => $request->lname,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'status'   => 'active',
        ]);
        CustomerShop::create([
            'customer_id'   => $customer->customer_id,
            'shop_id'       => $shopId,
            'registered_at' => now(),
            'status'        => 'active',
            'ctags'         => ['b2c'],
        ]);
        $token = JWTAuth::fromUser($customer);
        return response()->json([
            'token' => $token,
            'customer' => $customer,
        ]);
    }

    public function registerOnCheckout(Request $request)
    {
        $shopId = $request->shop_id;
        $validatorData = Validator::make($request->all(), [
            'fname' => 'required|string|max:100',
            'lname' => 'nullable|string|max:100',
            'email' => 'required|email',
            'password' => 'nullable|required_if:create_account,true|min:6',
        ]);
        if ($validatorData->fails()) {
            return response()->json(['errors'=> $validatorData->errors()],422);
        }
        $password = $request->create_account ? Hash::make($request->password) : Hash::make('123456');
        $existingCustomer = Customer::where('email','=',$request->email)->first();
        if($existingCustomer){
            $alreadyInShop = CustomerShop::where('customer_id','=',$existingCustomer->customer_id)
                ->where('shop_id','=',$shopId)
                ->exists();
            if(!$alreadyInShop){
                CustomerShop::create([
                    'customer_id' => $existingCustomer->customer_id,
                    'shop_id' => $shopId,
                    'registered_at' => now(),
                    'status' => 'active',
                    'ctags' => ['b2c'],
                ]);
            }
            $existingAddress = CustomerAddress::where('customer_id','=',$existingCustomer->customer_id)
                ->exists();
            if(!$existingAddress){
                $address = CustomerAddress::create([
                    'customer_id'   => $existingCustomer->customer_id,
                    'address_title' => 'default',
                    'fname'         => $request->fname,
                    'lname'         => $request->lname,
                    'address_line1' => $request->address1,
                    'address_line2' => $request->address2 ?? null,
                    'city'  => $request->city,
                    'postcode' => $request->postcode,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'is_default'   => true,
                ]);
            } else {
                $address = CustomerAddress::create([
                    'customer_id'   => $existingCustomer->customer_id,
                    'address_title' => 'Shipping',
                    'fname'         => $request->fname,
                    'lname'         => $request->lname,
                    'address_line1' => $request->address1,
                    'address_line2' => $request->address2 ?? null,
                    'city'  => $request->city,
                    'postcode' => $request->postcode,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'is_default'   => false,
                ]);
            }
            $token = JWTAuth::fromUser($existingCustomer);
            return response()->json([
                'token' => $token,
                'customer' => $existingCustomer,
                'address' => $address,
            ],200);
        }
        $customer = Customer::create([
            'fname' => $request->fname,
            'lname'    => $request->lname,
            'email'    => $request->email,
            'password' => $password,
            'phone' => $request->phone ?? null,
            'status'   => 'active',
        ]);
        CustomerShop::create([
            'customer_id'   => $customer->customer_id,
            'shop_id'       => $shopId,
            'registered_at' => now(),
            'status'        => 'active',
            'ctags'         => ['b2c'],
        ]);
       $address = CustomerAddress::create([
            'customer_id'   => $customer->customer_id,
            'address_title' => 'default',
            'fname'         => $request->fname,
            'lname'         => $request->lname,
            'address_line1' => $request->address1,
            'address_line2' => $request->address2 ?? null,
            'city'  => $request->city,
            'postcode' => $request->postcode,
            'country' => $request->country,
            'phone' => $request->phone,
            'is_default'   => true,
        ]);
        $token = JWTAuth::fromUser($customer);
        return response()->json([
            'token' => $token,
            'customer' => $customer,
            'address' => $address,
        ]);
    }

    public function me(Request $request)
    {
        $shopId = $request->shop_id;
        $customer = auth()->guard('customer')->user();
        return response()->json([
            'customer' => $customer,
            'shop'=> Shop::where('shop_id',$shopId)->first(),
            'shopId' => $shopId
        ]);
    }

    public function accountUpdate(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'lname'  => 'string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->customer_id . ',customer_id',
            'phone'      => 'nullable|string|max:20',

            // Password validation only if attempting to change it
            'current_password'      => 'nullable|string',
            'new_password'          => 'nullable|string|min:8|confirmed',
        ]);

        // Update basic fields
        $customer->fname = $validated['fname'];
        $customer->lname  = $validated['lname'] ?? $customer->lname;
        $customer->email      = $validated['email'];
        $customer->phone      = $validated['phone'] ?? null;
        // If trying to change password
        if ($request->filled('current_password') || $request->filled('new_password')) {
            if (!Hash::check($request->current_password, $customer->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }

            $customer->password = bcrypt($request->new_password);
        }
        $customer->save();

        return response()->json([
            'message' => 'Account updated successfully',
            'customer' => $customer,
        ]);
    }

    public function allAddresses(Request $request)
    {
        $shopId = $request->shop_id;
        $customer = auth()->guard('customer')->user();
        $addresses = CustomerAddress::where('customer_id','=',$customer->customer_id)
            ->orderBy('is_default','desc')
            ->get();
        return response()->json([
            'success' => true,
            'addresses' => $addresses,
        ]);
    }

    public function recentOrders(Request $request)
    {
        $shopId = $request->shop_id;
        $customer = auth()->guard('customer')->user();
        $orders = Order::with('orderItems')
            ->where('customer_id','=',$customer->customer_id)
            ->where('shop_id','=',$shopId)
            ->orderByDesc('created_at')
            ->get();
        $addresses = CustomerAddress::where('customer_id','=',$customer->customer_id)
            ->orderBy('is_default','desc')
            ->get();
        return response()->json([
            'success' => true,
            'orders' => $orders,
            'addresses' => $addresses,
        ]);
    }

    public function getDefaultAddress(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $addresses = CustomerAddress::where('customer_id','=',$customer->customer_id)
            ->get();
        $defaultAddress = $addresses->where('is_default','=',true)->first();
        return response()->json($defaultAddress);
    }

    public function markAsDefaultAddress(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $existingDefault = CustomerAddress::where('customer_id','=',$customer->customer_id)
            ->where('is_default','=',true)->first();
        $requestedId = $request->address_id;
        if($existingDefault){
            if($existingDefault->address_id != $requestedId){
                CustomerAddress::where('customer_id','=',$customer->customer_id)
                    ->where('address_id','=',$request->address_id)->update(['is_default'=>true]);
                $existingDefault->update(['is_default' => false]);
            }
            $existingDefault->is_default = true;
        }
        CustomerAddress::where('customer_id','=',$customer->customer_id)
            ->where('address_id','=',$requestedId)
            ->update(['is_default'=>true]);
        $existingDefault->update(['is_default' => false]);

        return response()->json($requestedId);
    }

    public function addNewAddress(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postcode' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);
        DB::beginTransaction();
        try {
            $existingAddress = CustomerAddress::where('address_id','=',$request->address_id)->first();
            if($existingAddress){
                $existingAddress->update([
                    'fname' => $request->fname,
                    'lname' => $request->lname,
                    'address_line1' => $request->address_line1,
                    'address_line2' => $request->address_line2,
                    'city' => $request->city,
                    'postcode' => $request->postcode,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'is_default' => $request->is_default,
                    'customer_id' => $customer->customer_id,
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'address' => $existingAddress,
                ]);
            } else {
                $caddress = CustomerAddress::create([
                    'fname' => $request->fname,
                    'lname' => $request->lname,
                    'address_line1' => $request->address_line1,
                    'address_line2' => $request->address_line2,
                    'city' => $request->city,
                    'postcode' => $request->postcode,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'is_default' => $request->is_default ?? false,
                    'customer_id' => $customer->customer_id,
                ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'address' => $caddress,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAddress(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $validated = $request->validate([
            'address_id' => 'required|integer|exists:customer_addresses,address_id',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postcode' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $caddress = CustomerAddress::findOrFail($request->address_id)->update([
                'fname' => $request->fname,
                'lname' => $request->lname,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'postcode' => $request->postcode,
                'country' => $request->country,
                'phone' => $request->phone,
                'customer_id' => $customer->customer_id,
            ]);
            if($caddress){
                DB::commit();
                return response()->json([
                    'success' => true,
                    'address' => $caddress,
                    'message' => 'Address updated successfully',
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update address',
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAddress(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        $validated = $request->validate([
            'address_id' => 'required|integer|exists:customer_addresses,address_id',
        ]);
        DB::beginTransaction();
        try {
            CustomerAddress::find($request->address_id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address',
                'error' => $e->getMessage(),
            ], 500);
        }
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

    //admin Routes
    public function allCustomers(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $query = Customer::query()->with(['defaultaddress'])
            ->join('customer_shops', 'customer_shops.customer_id', '=', 'customers.customer_id')
            ->where('customer_shops.shop_id','=',$shopId)
            ->select('customers.*','customer_shops.cshop_id','customer_shops.ctags','customer_shops.shop_id',
                'customer_shops.status as cstatus','customer_shops.mailtrap_contact_id',
                'customer_shops.mailtrap_synced',
                'customer_shops.mailtrap_synced_at',
                'customer_shops.mailtrap_last_error')
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

    public function syncCustomers(Request $request,MailtrapService $mailtrapService)
    {
        $customerShops = CustomerShop::with(['customer','shop'])
            ->whereIn('cshop_id', $request->ids)
            ->get();
        $success = 0;
        $failed = 0;
        foreach ($customerShops as $customerShop) {
            $synced = $mailtrapService->syncCustomer($customerShop);
            if ($synced) {
                $success++;
            } else {
                $failed++;
            }
        }
        return response()->json([
            'success' => true,
            'synced' => $success,
            'failed' => $failed,
        ]);
    }

    public function updateCustomer($cshopId, MailtrapService $mailtrapService)
    {
        $customerShop = CustomerShop::with(['customer', 'shop'])->findOrFail($cshopId);
        $synced = $mailtrapService->syncCustomer($customerShop);
        return response()->json([
            'success' => $synced
        ]);
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
            $customer['cshop_id'] = $cshops->cshop_id;
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'orders' => $orders,
                'amount_spent' => $orders->sum('order_total'),
                'last_order' => $last_order,
            ]);
        }

    }
}
