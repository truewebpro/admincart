<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Models\ShopUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAdminShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!session()->has('shop_id')) {

            $shopUser = ShopUser::where('user_id',auth()->id())->first();

            if (!$shopUser) {
                abort(403,'No shop assigned');
            }

            $shop = Shop::find($shopUser->shop_id);

            session([
                'shop_id'=>$shop->shop_id,
                'shop'=>$shop
            ]);
        }

        return $next($request);
    }
}
