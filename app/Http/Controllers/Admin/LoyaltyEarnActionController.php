<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesShopContext;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyEarnAction;
use Illuminate\Http\Request;

class LoyaltyEarnActionController extends Controller
{
    use ResolvesShopContext;

    // GET /admin/loyalty/earn-actions
    public function index()
    {
        return response()->json(
            LoyaltyEarnAction::where('shop_id', $this->currentShopId())
                ->orderBy('sort_order')
                ->get()
        );
    }

    // POST /admin/loyalty/earn-actions
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['shop_id'] = $this->currentShopId();

        return response()->json(LoyaltyEarnAction::create($data), 201);
    }

    // PUT /admin/loyalty/earn-actions/{action}
    public function update(Request $request, LoyaltyEarnAction $action)
    {
        abort_unless($action->shop_id === $this->currentShopId(), 403);

        $action->update($this->validated($request));

        return response()->json($action);
    }

    // DELETE /admin/loyalty/earn-actions/{action}
    public function destroy(LoyaltyEarnAction $action)
    {
        abort_unless($action->shop_id === $this->currentShopId(), 403);
        $action->delete();

        return response()->json(['message' => 'Earn action deleted.']);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'category' => 'required|in:review,social_follow,social_share,order,custom',
            'platform' => 'nullable|string|max:50',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'action_url' => 'nullable|url|max:500',
            'points' => 'required|integer|min:0',
            'verification' => 'required|in:automatic,manual_admin',
            'repeat_scope' => 'required|in:once_per_customer,once_per_reference,unlimited',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);
    }
}
