<?php

namespace App\Http\Middleware;

use App\Models\CustomerShop;
use Closure;
use Illuminate\Http\Request;

/**
 * Runs AFTER your existing 'auth:customer' (Authenticate middleware on the
 * 'customer' guard) and 'resolve.shop' (merges shop_id onto the request —
 * matches how CustomerController::me()/allAddresses() etc. read $request->shop_id,
 * NOT app('currentShop')) middleware in the route group.
 *
 * Looks up the customer_shops row for this (customer_id, shop_id) pair and
 * binds it so every store-credit/loyalty controller can just do
 * `app('currentCustomerShop')` instead of re-deriving it in every method.
 *
 * Does NOT firstOrCreate. Your customerLogin/customerRegister/registerOnCheckout
 * already create the customer_shops row (with registered_at, status, ctags —
 * fields this middleware doesn't know how to fill in) at the moment a customer
 * first links to a shop. By the time an authenticated request reaches here,
 * that row is guaranteed to already exist — if it doesn't, something upstream
 * is wrong (e.g. a JWT for a customer/shop pair that never went through login),
 * and this fails loudly (403) rather than silently creating an incomplete row,
 * mirroring the same "Customer not registered with this shop" case your own
 * customerLogin already handles.
 *
 * Register in app/Http/Kernel.php under $routeMiddleware:
 *   'resolve.customer-shop' => \App\Http\Middleware\ResolveCustomerShop::class, // new
 *   // 'auth' and 'resolve.shop' already exist in your app
 *
 * Nests inside your existing resolve.shop/{shopname} group, with auth:customer
 * and this middleware wrapping just the routes that need both shop AND customer
 * context (see routes-snippet.php for the full example):
 *   Route::middleware('resolve.shop')->prefix('shop/{shopname}')->group(function () {
 *       Route::middleware(['auth:customer', 'resolve.customer-shop'])->group(function () {
 *           // store-credit / loyalty routes
 *       });
 *   });
 */
class ResolveCustomerShop
{
    public function handle(Request $request, Closure $next)
    {
        $customer = auth()->guard('customer')->user();

        $customerShop = CustomerShop::where('customer_id', $customer->customer_id)
            ->where('shop_id', $request->shop_id)
            ->first();

        if (! $customerShop) {
            return response()->json(['error' => 'Customer not registered with this shop'], 403);
        }

        app()->instance('currentCustomerShop', $customerShop);
        $request->attributes->set('currentCustomerShop', $customerShop);

        return $next($request);
    }
}
