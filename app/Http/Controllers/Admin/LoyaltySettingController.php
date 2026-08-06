<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesShopContext;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltySetting;
use Illuminate\Http\Request;

class LoyaltySettingController extends Controller
{
    use ResolvesShopContext;

    // GET /admin/loyalty/settings
    public function show()
    {
        $shopId = $this->currentShopId();

        $settings = LoyaltySetting::firstOrCreate(['shop_id' => $shopId]);
        $rules = LoyaltyRedeemRule::where('shop_id', $shopId)->orderBy('sort_order')->get();

        return response()->json(compact('settings', 'rules'));
    }

    // PUT /admin/loyalty/settings
    public function update(Request $request)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
            'spend_amount' => 'required|numeric|min:0.01',
            'points_per_spend' => 'required|integer|min:1',
            'min_order_amount_to_earn' => 'nullable|numeric|min:0',
            'max_points_per_order' => 'nullable|integer|min:1',
        ]);

        $settings = LoyaltySetting::updateOrCreate(
            ['shop_id' => $this->currentShopId()],
            $data
        );

        return response()->json($settings);
    }

    // POST /admin/loyalty/redeem-rules
    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'credit_value' => 'required|numeric|min:0.01',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['shop_id'] = $this->currentShopId();

        return response()->json(LoyaltyRedeemRule::create($data), 201);
    }

    // PUT /admin/loyalty/redeem-rules/{rule}
    public function updateRule(Request $request, LoyaltyRedeemRule $rule)
    {
        abort_unless($rule->shop_id === $this->currentShopId(), 403);

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'credit_value' => 'required|numeric|min:0.01',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $rule->update($data);

        return response()->json($rule);
    }

    // DELETE /admin/loyalty/redeem-rules/{rule}
    public function destroyRule(LoyaltyRedeemRule $rule)
    {
        abort_unless($rule->shop_id === $this->currentShopId(), 403);
        $rule->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
