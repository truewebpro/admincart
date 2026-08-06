<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesShopContext;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyActionCompletion;
use App\Services\LoyaltyActionService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The review queue for actions that can't be auto-verified — Google/Trustpilot
 * reviews, social follows, anything with verification=manual_admin. Customer
 * claims sit here as 'pending' until an admin approves or rejects them.
 *
 * Note: loyalty_action_completions already carries shop_id directly (denormalized
 * from customer_shops at claim time), so this controller's shop-scoping doesn't
 * need to go through CustomerShop — it's already scoped correctly below.
 */
class LoyaltyActionApprovalController extends Controller
{
    use ResolvesShopContext;

    public function __construct(protected LoyaltyActionService $actionService)
    {
    }

    // GET /admin/loyalty/action-completions?status=pending
    public function index(Request $request)
    {
        $query = LoyaltyActionCompletion::with(['action', 'customer', 'customerShop'])
            ->where('shop_id', $this->currentShopId());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(25));
    }

    // POST /admin/loyalty/action-completions/{completion}/approve
    // Body: { notes?: string }
    public function approve(Request $request, LoyaltyActionCompletion $completion)
    {
        abort_unless($completion->shop_id === $this->currentShopId(), 403);

        try {
            $result = $this->actionService->approve($completion, auth()->id(), $request->input('notes'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Approved — {$result->points_awarded} points awarded to {$completion->customer->name}.",
            'completion' => $result,
        ]);
    }

    // POST /admin/loyalty/action-completions/{completion}/reject
    // Body: { notes: string } — reason is required so the customer/team has a record
    public function reject(Request $request, LoyaltyActionCompletion $completion)
    {
        abort_unless($completion->shop_id === $this->currentShopId(), 403);

        $request->validate(['notes' => 'required|string|max:1000']);

        try {
            $result = $this->actionService->reject($completion, auth()->id(), $request->notes);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Claim rejected.', 'completion' => $result]);
    }
}
