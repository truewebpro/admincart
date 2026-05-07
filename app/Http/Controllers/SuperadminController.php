<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Plan;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuperadminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'resolve.admin.shop']);
    }

    public function superadminDashboard()
    {
        $users = User::get();
        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    public function allShops()
    {
        $shops = Shop::latest()->get();

        return response()->json([
            'success' => true,
            'shops' => $shops
        ]);
    }

    public function checkSlug(Request $request)
    {
        $slug = $request->query('slug');

        if (!$slug) {
            return response()->json(['available' => false]);
        }

        $exists = Shop::where('shop_slug', $slug)->exists();

        return response()->json([
            'available' => !$exists
        ]);
    }

    public function storeShop(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|unique:shops,shop_name',
            'shop_slug' => 'required|alpha_dash|unique:shops,shop_slug',
        ]);
        $data['subdomain'] = $data['shop_slug'] . '.truewebcart.com';
        $data['maindomain'] = null;

        $shop = Shop::create($data);

        Location::create([
            'shop_id' => $shop->shop_id,
            'location_name' => "Default",
            'location_address' => 'Default Address',
            'country' => 'United Kingdom',
            'location_status' => "Active"
        ]);

        return response()->json([
            'success' => true,
            'shop' => $shop
        ]);
    }

    public function updateShop(Request $request, $shop_id)
    {
        $shop = Shop::findOrFail($shop_id);
        $data = $request->validate([
            'maindomain' => [
                'nullable',
                Rule::unique('shops', 'maindomain')->ignore($shop_id, 'shop_id'),
            ],
        ]);
        // ❌ never allow these updates
        unset($data['shop_name']);
        unset($data['shop_slug']);
        unset($data['subdomain']);
        $shop->update($data);

        return response()->json([
            'success' => true,
            'shop' => $shop
        ]);
    }

    public function toggleShopStatus($shop_id)
    {
        $shop = Shop::findOrFail($shop_id);
        $shop->shop_status = $shop->shop_status === 'Active' ? 'Inactive' : 'Active';
        $shop->save();

        return response()->json(['success' => true]);
    }

    public function allShopsList(Request $request)
    {
        $shops = Shop::get();
        return response()->json([
            'success' => true,
            'shops' => $shops,
        ]);
    }

    public function shopUsers(Request $request)
    {
        $shopId = session('shop_id');
        $user = auth()->user();
        $isSuperAdmin = ShopUser::where('user_id', $user->id)
            ->where('role', 'superadmin')
            ->exists();
        $user->load('shops');

        // If superadmin → can access all shops
        if($isSuperAdmin){
            $allShops = Shop::all();
            if (!$shopId) {
                $shopId = optional($allShops->first())->shop_id;
                session(['shop_id' => $shopId]);
            }
            $userShops = $allShops->map(function ($shop) {
                return [
                    'shop_id' => $shop->shop_id,
                    'role' => 'superadmin',
                    'shop_name' => $shop->shop_name,
                    'shop_slug' => $shop->shop_slug,
                    'subdomain' => $shop->subdomain,
                    'maindomain' => $shop->maindomain,
                    'shop_status' => $shop->shop_status,
                ];
            });
        } else {
            // Normal user shops
            $userShops = $user->shops->map(function ($shop) {
                return [
                    'shop_id' => $shop->shop_id,
                    'role' => $shop->pivot->role,
                    'shop_name' => $shop->shop_name,
                    'shop_slug' => $shop->shop_slug,
                    'subdomain' => $shop->subdomain,
                    'maindomain' => $shop->maindomain,
                    'shop_status' => $shop->shop_status,
                ];
            });
            if (!$shopId || !collect($userShops)->contains('shop_id', $shopId)) {
                $shopId = optional($userShops->first())['shop_id'];
                session(['shop_id' => $shopId]);
            }
        }
        $currentShop = collect($userShops)->firstWhere('shop_id', $shopId);

        // Shop users (only for current shop)
        $shopUsers = ShopUser::where('shop_id', $shopId)->get();

        $shopActive = ShopUser::where('shop_id', $shopId)->where('user_id', $user->id)->first();
        return response()->json([
            'success' => true,
            'shop_users' => $shopUsers,
            'shop_id' => $shopId,
            'user_role' => $isSuperAdmin
                ? 'superadmin'
                : optional($currentShop)['role'],
            'shop_active' => $shopActive,
            'user_shops' => $userShops,
        ]);
    }

    public function switchShop(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer'
        ]);

        $shopId = $request->shop_id;
        $user = auth()->user();

        // check if user is superadmin
        $isSuperAdmin = ShopUser::where('user_id', $user->id)
            ->where('role', 'superadmin')
            ->exists();

        if (!$isSuperAdmin) {
            // normal user → must belong to shop
            $hasAccess = ShopUser::where('user_id', $user->id)
                ->where('shop_id', $shopId)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized shop access'
                ], 403);
            }
        }

        // set session
        session(['shop_id' => $shopId]);

        return response()->json([
            'success' => true,
            'shop_id' => $shopId
        ]);
    }

    public function plans()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }
}
