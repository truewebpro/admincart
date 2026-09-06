<?php

namespace App\Http\Controllers;

use App\Enums\BadgePosition;
use App\Models\Badge;
use App\Models\Coupon;
use App\Models\PricingRule;
use App\Services\PromoLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    public function list()
    {
        $shopId = session('shop_id');

        $badges = Badge::withCount(['coupons', 'pricingRules'])
            ->with(['coupons:coupon_id', 'pricingRules:id'])
            ->where('shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'label' => $b->label,
                    'use_label' => $b->use_label,
                    'color' => $b->color,
                    'bg_color' => $b->bg_color,
                    'style' => $b->style,
                    'position' => $b->position->value,
                    'is_active' => $b->is_active,
                    'coupons_count' => $b->coupons_count,
                    'rules_count' => $b->pricing_rules_count, // withCount('pricingRules') -> pricing_rules_count
                    'coupon_ids' => $b->coupons->pluck('coupon_id'),   // for pre-selecting the assign dialog
                    'rule_ids' => $b->pricingRules->pluck('id'),
                ];
            });

        // Only is_auto coupons can ever actually display a badge —
        // PromoLabelService never loads manual-code coupons for labeling.
        $coupons = Coupon::where('shop_id', $shopId)
            ->where('is_auto', true)
            ->get(['coupon_id', 'code', 'display_title', 'title', 'type']);

        $rules = PricingRule::where('shop_id', $shopId)
            ->get(['id', 'name', 'type']);

        return response()->json([
            'success' => true,
            'badges' => $badges,
            'coupons' => $coupons,
            'rules' => $rules,
            'positions' => collect(BadgePosition::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
        ]);
    }

    public function save(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'nullable|exists:badges,id',
            'label' => 'required|string|max:255',
            'use_label' => 'boolean',
            'color' => 'nullable|string|max:7',
            'bg_color' => 'nullable|string|max:7',
            'style' => 'nullable|string|max:100',
            'position' => ['required', Rule::enum(BadgePosition::class)],
            'is_active' => 'boolean',
        ]);

        if ($request->id) {
            $badge = Badge::where('id', $request->id)
                ->where('shop_id', $shopId)
                ->firstOrFail();
            $badge->update([
                'label' => $request->label,
                'use_label' => $request->use_label ?? true,
                'color' => $request->color,
                'bg_color' => $request->bg_color,
                'style' => $request->style,
                'position' => $request->position,
                'is_active' => $request->is_active ?? true,
            ]);
        } else {
            $badge = Badge::create([
                'shop_id' => $shopId,
                'label' => $request->label,
                'use_label' => $request->use_label ?? true,
                'color' => $request->color,
                'bg_color' => $request->bg_color,
                'style' => $request->style,
                'position' => $request->position,
                'is_active' => $request->is_active ?? true,
            ]);
        }
        // BadgeObserver::saved() fires here automatically — flush() already
        // covered, no manual call needed for the badge's own fields.

        return response()->json([
            'success' => true,
            'message' => 'Badge saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'required|exists:badges,id',
        ]);

        $badge = Badge::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$badge) {
            return response()->json([
                'success' => false,
                'message' => 'Badge not found',
            ]);
        }

        $badge->delete(); // BadgeObserver::deleted() fires, flushes cache
        // pivot rows in badge_coupons/badge_rules cascade-delete via the
        // migration's ->cascadeOnDelete()

        return response()->json([
            'success' => true,
            'message' => 'Badge deleted successfully',
        ]);
    }

    /**
     * Sync which coupons and pricing rules this badge is attached to.
     * sync() does NOT fire BadgeObserver (pivot writes don't trigger the
     * parent model's save event), so the cache flush here is explicit.
     */
    public function assign(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'required|exists:badges,id',
            'coupon_ids' => 'nullable|array',
            'coupon_ids.*' => 'integer|exists:coupons,coupon_id',
            'rule_ids' => 'nullable|array',
            'rule_ids.*' => 'integer|exists:pricing_rules,id',
        ]);

        $badge = Badge::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $badge, $shopId) {
            // sync() only writes the two FK columns by default — badge_coupons/
            // badge_rules also require shop_id (NOT NULL), so pivot values
            // need to be passed explicitly or the insert fails.
            $couponSync = collect($request->coupon_ids ?? [])
                ->mapWithKeys(fn ($id) => [$id => ['shop_id' => $shopId]]);
            $badge->coupons()->sync($couponSync);

            $ruleSync = collect($request->rule_ids ?? [])
                ->mapWithKeys(fn ($id) => [$id => ['shop_id' => $shopId]]);
            $badge->pricingRules()->sync($ruleSync);
        });

        app(PromoLabelService::class)->flush($shopId); // explicit — see docblock

        return response()->json([
            'success' => true,
            'message' => 'Badge assignments updated',
        ]);
    }
}
