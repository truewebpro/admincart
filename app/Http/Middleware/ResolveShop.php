<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Models\ShopUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shopname = $request->route('shopname');

        if (!$shopname) {
            return response()->json(['success' => false,'message'=>'Shop missing'],404);
        }

        $shop = Shop::where('shop_slug',$shopname)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ],404);
        }

        $request->attributes->set('shop',$shop);
        $request->attributes->set('shop_id',$shop->shop_id);
        $request->merge(['shop_id'=>$shop->shop_id]);
        return $next($request);
    }
}
