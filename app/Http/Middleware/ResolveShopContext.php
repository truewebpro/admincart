<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;

/**
 * Admin's session-based equivalent of ResolveShop (which is URL-slug based, for
 * customers). session('shop_id') is always set by your existing login / shop-switch
 * flow, so this middleware just resolves the Shop model for it and binds
 * app('currentShop') — the same target every controller already reads from.
 *
 * Register in app/Http/Kernel.php:
 *   'resolve.shop-context' => \App\Http\Middleware\ResolveShopContext::class,
 *
 * Apply to the admin route group AFTER your session auth middleware:
 *   ->middleware(['auth:web', 'resolve.shop-context'])
 */
class ResolveShopContext
{
    public function handle(Request $request, Closure $next)
    {
        $shop = Shop::where('shop_id', session('shop_id'))->firstOrFail();

        app()->instance('currentShop', $shop);
        $request->attributes->set('currentShop', $shop);

        return $next($request);
    }
}
