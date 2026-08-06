<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Every method below takes $shopname even though the body never uses it
 * (shop comes from app('currentCustomerShop')/$customerShop->shop_id instead)
 * — confirmed via testing on LoyaltyPointsPreviewController that this app's
 * routing needs every route parameter present as a method parameter to
 * resolve correctly, since these routes all sit under the parent
 * shop/{shopname} prefix.
 */
class LoyaltyController extends Controller
{
    public function __construct(protected LoyaltyService $loyaltyService)
    {
    }

    // GET /api/shop/{shopname}/loyalty/balance
    public function balance(Request $request, $shopname)
    {
        $customerShop = app('currentCustomerShop');

        return response()->json([
            'points_balance' => $customerShop->loyalty_points_balance,
        ]);
    }

    // GET /api/shop/{shopname}/loyalty/history
    public function history(Request $request, $shopname)
    {
        $customerShop = app('currentCustomerShop');

        return response()->json(
            LoyaltyTransaction::where('cshop_id', $customerShop->cshop_id)
                ->latest()
                ->paginate($request->integer('per_page', 20))
        );
    }

    // GET /api/shop/{shopname}/loyalty/rewards  -- available redeem tiers + whether customer can afford each
    public function rewards(Request $request, $shopname)
    {
        $customerShop = app('currentCustomerShop');

        return response()->json(
            $this->loyaltyService->availableRewards($customerShop->shop_id, $customerShop)
        );
    }

    // POST /api/shop/{shopname}/loyalty/redeem  { redeem_rule_id }
    public function redeem(Request $request, $shopname)
    {
        $request->validate(['redeem_rule_id' => 'required|integer|exists:loyalty_redeem_rules,id']);

        $customerShop = app('currentCustomerShop');
        $rule = LoyaltyRedeemRule::where('shop_id', $customerShop->shop_id)
            ->findOrFail($request->redeem_rule_id);

        try {
            $transaction = $this->loyaltyService->redeem($customerShop, $rule);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $fresh = $customerShop->fresh();

        return response()->json([
            'message' => 'Redeemed successfully. Store credit has been added to your account.',
            'points_balance' => $fresh->loyalty_points_balance,
            'store_credit_balance' => (float) $fresh->store_credit_balance,
            'transaction' => $transaction,
        ]);
    }
}
