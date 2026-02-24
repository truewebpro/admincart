<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerShop;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;
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
        ]);
        if ($validatorData->fails()) {
            return response()->json(['errors'=> $validatorData->errors()],422);
        }
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
                CustomerAddress::create([
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
                CustomerAddress::create([
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
            return response()->json(['token' => $token],200);
        }
        $customer = Customer::create([
            'fname' => $request->fname,
            'lname'    => $request->lname,
            'email'    => $request->email,
            'password' => Hash::make('123456'),
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
        CustomerAddress::create([
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
}
