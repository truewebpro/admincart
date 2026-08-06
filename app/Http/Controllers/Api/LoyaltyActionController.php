<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyEarnAction;
use App\Services\LoyaltyActionService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Every method below takes $shopname even though the body never uses it
 * directly (shop comes from app('currentCustomerShop') instead) — confirmed
 * via testing on LoyaltyPointsPreviewController that this app's routing
 * needs every route parameter present as a method parameter to resolve
 * correctly, since these routes all sit under the parent shop/{shopname}
 * prefix. claim() also switches from implicit route-model-binding
 * (LoyaltyEarnAction $action) to a manual lookup for the same reason
 * implicit binding failed on Variant in LoyaltyPointsPreviewController.
 */
class LoyaltyActionController extends Controller
{
    public function __construct(protected LoyaltyActionService $actionService)
    {
    }

    // GET /api/shop/{shopname}/loyalty/earn-actions
    // "Ways to earn" list for the customer's account page.
    public function index(Request $request, $shopname)
    {
        return response()->json(
            $this->actionService->availableActions(app('currentCustomerShop'))
        );
    }

    /**
     * POST /api/shop/{shopname}/loyalty/earn-actions/{actionId}/claim
     * Body (optional, depending on action type):
     *   reference_type, reference_id  — e.g. 'product', 123 for a per-product review
     *   proof_url                     — link to the review/post, for manual verification
     */
    public function claim(Request $request, $shopname, string $actionId)
    {
        $customerShop = app('currentCustomerShop');

        $action = LoyaltyEarnAction::where('shop_id', $customerShop->shop_id)
            ->where('id', $actionId)
            ->firstOrFail();

        $request->validate([
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'proof_url' => 'nullable|url|max:500',
        ]);

        try {
            $completion = $this->actionService->claim(
                customerShop: $customerShop,
                action: $action,
                referenceType: $request->reference_type,
                referenceId: $request->reference_id,
                proofUrl: $request->proof_url,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $completion->status === 'approved'
                ? "Points awarded — {$action->points} added to your balance."
                : "Thanks — we'll review this and add your points shortly.",
            'status' => $completion->status,
            'points_balance' => $customerShop->fresh()->loyalty_points_balance,
        ]);
    }
}
