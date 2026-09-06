<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\PricingRule;
use Illuminate\Support\Facades\Cache;

/**
 * Builds a shop-wide "what promo applies to what" map for STOREFRONT DISPLAY
 * (product card / product page badges).
 *
 * Deliberately reuses the exact same applies_to scoping (entire_order /
 * products / cats) that CouponService::getEligibleItems() and
 * PricingRuleService::getEligibleItems() use for real cart discounting.
 * This is what keeps the badge and the checkout discount from disagreeing —
 * there is exactly one definition of "does this promo apply to this
 * product", and both the label map and the pricing engine read from it.
 *
 * Scope: only auto-applied promos are labeled (is_auto coupons + all
 * pricing rules, which have no code). Manual code coupons aren't shown as
 * badges since the customer hasn't entered a code yet — showing one would
 * be misleading. Cart-quantity conditions (min_qty, buy_qty, bundle_qty)
 * are NOT evaluated here on purpose: a badge answers "is this product
 * eligible for this promo type", not "does your current cart qualify
 * right now" — that second question is answered at cart/checkout time by
 * the existing engine.
 */
class PromoLabelService
{
    private const CACHE_TTL_MINUTES = 10;

    public function getShopLabelMap(int $shopId): array
    {
        return Cache::remember(
            $this->cacheKey($shopId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->build($shopId)
        );
    }

    /**
     * Convenience lookup for a single product, e.g. when hydrating one
     * product detail page server-side instead of shipping the whole map.
     */
    public function labelsForProduct(int $shopId, int $productId, array $catIds): array
    {
        $map = $this->getShopLabelMap($shopId);

        $labels = $map['entire_order'];

        foreach ($catIds as $catId) {
            $labels = array_merge($labels, $map['by_cat'][$catId] ?? []);
        }

        $labels = array_merge($labels, $map['by_product'][$productId] ?? []);

        return $this->dedupeAndSort($labels);
    }

    /** Call this from wherever coupons/pricing rules are saved or deleted. */
    public function flush(int $shopId): void
    {
        Cache::forget($this->cacheKey($shopId));
    }

    private function cacheKey(int $shopId): string
    {
        return "shop:{$shopId}:promo-label-map";
    }

    private function build(int $shopId): array
    {
        $entireOrder = [];
        $byProduct = [];
        $byCat = [];

        // No badge assigned to a coupon/rule = hardcoded system default.
        // (There's no more "shop-wide default Badge row" concept now that
        // badges are standalone + many-to-many — if you want a
        // shop-configurable default later, that'd be a separate is_default
        // flag on Badge rather than forcing it into this schema now.)
        $defaultPosition = 'top-left';
        $defaultColor = null;

        $coupons = Coupon::with(['products', 'cats','badges'])
            ->where('shop_id', $shopId)
            ->where('is_auto', true) // manual-code coupons aren't badged
            ->active()
            ->get();

        foreach ($coupons as $coupon) {
            $badges = $coupon->badges->where('is_active', true);

            // A coupon with one or more badges gets one label entry PER
            // badge — this is what lets a merchant show the same promo at
            // two different positions/colors simultaneously if they want.
            // A coupon with none falls back to a single entry using the
            // shop-wide default.
            if ($badges->isNotEmpty()) {
                foreach ($badges as $badge) {
                    $entry = [
                        'id' => $coupon->coupon_id,
                        'kind' => 'coupon',
                        'key' => "coupon-{$coupon->coupon_id}-badge-{$badge->id}",
                        'promo_type' => $coupon->type,
                        'label' => $badge->effectiveLabel() ?? $coupon->label,
                        'priority' => $coupon->priority,
                        'position' => $badge->position->value,
                        'color' => $badge->color,
                        'bg_color' => $badge->bg_color,
                        'style' => $badge->style,
                    ];
                    $this->place($coupon->applies_to, $coupon->products, $coupon->cats, $entry, $entireOrder, $byProduct, $byCat);
                }
            } else {
                $entry = [
                    'id' => $coupon->coupon_id,
                    'kind' => 'coupon',
                    'key' => "coupon-{$coupon->coupon_id}-default",
                    'promo_type' => $coupon->type, // fixed | percentage | bogo | bundle
                    'label' => $coupon->label,
                    'priority' => $coupon->priority,
                    'position' => $defaultPosition,
                    'color' => $defaultColor,
                    'bg_color' => null,
                    'style' => null,
                ];
                $this->place($coupon->applies_to, $coupon->products, $coupon->cats, $entry, $entireOrder, $byProduct, $byCat);
            }
        }

        $rules = PricingRule::with(['products', 'cats','badges'])
            ->where('shop_id', $shopId)
            ->active()
            ->get();

        foreach ($rules as $rule) {
            $badges = $rule->badges->where('is_active', true);
            // PricingRule uses 'scope' (all/products/cats) where Coupon uses 'applies_to'
            $scope = $rule->scope === 'all' ? 'entire_order' : $rule->scope;

            if ($badges->isNotEmpty()) {
                foreach ($badges as $badge) {
                    $entry = [
                        'id' => $rule->id,
                        'kind' => 'rule',
                        'key' => "rule-{$rule->id}-badge-{$badge->id}",
                        'promo_type' => $rule->type,
                        'label' => $badge->effectiveLabel() ?? $rule->label,
                        'priority' => $rule->priority,
                        'position' => $badge->position->value,
                        'color' => $badge->color,
                        'bg_color' => $badge->bg_color,
                        'style' => $badge->style,
                    ];
                    $this->place($scope, $rule->products, $rule->cats, $entry, $entireOrder, $byProduct, $byCat);
                }
            } else {
                $entry = [
                    'id' => $rule->id,
                    'kind' => 'rule',
                    'key' => "rule-{$rule->id}-default",
                    'promo_type' => $rule->type,
                    'label' => $rule->label,
                    'priority' => $rule->priority,
                    'position' => $defaultPosition,
                    'color' => $defaultColor,
                    'bg_color' => null,
                    'style' => null,
                ];
                $this->place($scope, $rule->products, $rule->cats, $entry, $entireOrder, $byProduct, $byCat);
            }
        }

        return [
            'entire_order' => $this->dedupeAndSort($entireOrder),
            'by_product' => array_map(fn ($labels) => $this->dedupeAndSort($labels), $byProduct),
            'by_cat' => array_map(fn ($labels) => $this->dedupeAndSort($labels), $byCat),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function place(string $scope, $products, $cats, array $entry, array &$entireOrder, array &$byProduct, array &$byCat): void
    {
        if ($scope === 'entire_order') {
            $entireOrder[] = $entry;
            return;
        }

        if ($scope === 'products') {
            foreach ($products as $product) {
                $byProduct[$product->product_id][] = $entry;
            }
            return;
        }

        if ($scope === 'cats') {
            foreach ($cats as $cat) {
                $byCat[$cat->cat_id][] = $entry;
            }
        }
    }

    private function dedupeAndSort(array $labels): array
    {
        // keyed on 'key' (per-badge, or per-coupon-default), not kind:id —
        // a coupon with two badges must keep BOTH entries, only exact
        // duplicate placements (e.g. via two overlapping category matches)
        // collapse.
        $unique = collect($labels)->unique('key');
        return $unique->sortBy('priority')->values()->all();
    }
}
