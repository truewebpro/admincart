<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StoreCreditService;
use Illuminate\Http\Request;

/**
 * Routes here sit behind: 'resolve.shop', 'auth:customer' (JWT), and
 * 'resolve.customer-shop' (in that order — see ResolveCustomerShop middleware).
 * app('currentCustomerShop') is the customer_shops row for this customer at
 * this shop — that's what carries the balance, NOT $request->user() directly.
 *
 * Every method below takes $shopname even though the body never uses it
 * (shop comes from $request->shop_id via resolve.shop instead) — confirmed
 * via testing on LoyaltyPointsPreviewController that this app's routing needs
 * every route parameter present as a method parameter to resolve correctly,
 * since these routes all sit under the parent shop/{shopname} prefix.
 */
class StoreCreditController extends Controller
{
    public function __construct(protected StoreCreditService $storeCreditService)
    {
    }

    // GET /api/shop/{shopname}/store-credit/balance
    public function balance(Request $request, $shopname)
    {
        $customerShop = app('currentCustomerShop');

        return response()->json([
            'balance' => (float) $customerShop->store_credit_balance,
            'currency' => 'GBP',
        ]);
    }

    // GET /api/shop/{shopname}/store-credit/history
    public function history(Request $request, $shopname)
    {
        $customerShop = app('currentCustomerShop');

        return response()->json(
            $this->storeCreditService->history($customerShop, $request->integer('per_page', 20))
        );
    }

    /**
     * POST /api/shop/{shopname}/checkout/store-credit/preview
     * Called when the customer flips the "use store credit" toggle on checkout.
     * Body: { order_total: number }
     * Returns how much credit will actually be applied (capped by this shop's
     * balance and the order total) and the resulting amount due. Does NOT
     * mutate any balance — that happens on order placement.
     */
    public function previewApplication(Request $request, $shopname)
    {
        $request->validate(['order_total' => 'required|numeric|min:0']);

        $customerShop = app('currentCustomerShop');
        $applied = $this->storeCreditService->maxApplicable($customerShop, (float) $request->order_total);

        return response()->json([
            'available_balance' => (float) $customerShop->store_credit_balance,
            'applied_amount' => $applied,
            'amount_due' => round($request->order_total - $applied, 2),
        ]);
    }
}
