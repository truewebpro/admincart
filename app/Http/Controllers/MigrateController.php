<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessShop;
use App\Models\Preference;
use App\Models\Shop;
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
}
