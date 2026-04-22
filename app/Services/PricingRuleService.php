<?php

namespace App\Services;

use App\Models\PricingRule;

class PricingRuleService
{
    public function apply($cart, $shopId): array
    {
        $rules = PricingRule::with(['products', 'cats'])
            ->where('shop_id', $shopId)
            ->active()
            ->orderBy('priority','asc')
            ->get();

        $bestDiscount = 0;
        $appliedRuleName = null;

        foreach ($rules as $rule) {

            $eligibleItems = $this->getEligibleItems($cart, $rule);

            $totalQty = collect($eligibleItems)->sum('qty');

            if ($totalQty < $rule->min_qty) continue;

            $discount = $rule->type === 'bundle'
                ? $this->applyBundle($eligibleItems, $rule)
                : $this->applyVolume($eligibleItems, $rule);

            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $appliedRuleName = $rule->name;
            }

            $bestDiscount = max($bestDiscount, $discount);
        }

        return [
            'discount' => $bestDiscount,
            'applied_rule' => $appliedRuleName ?? null
        ];
    }

    private function getEligibleItems($cart, $rule)
    {
        if ($rule->scope === 'products') {
            $ids = $rule->products->pluck('product_id')->toArray();

            return array_filter($cart, fn($i) =>
            in_array($i['product_id'], $ids)
            );
        }

        if ($rule->scope === 'cats') {
            $catIds = $rule->cats->pluck('cat_id')->toArray();

            return array_filter($cart, fn($i) =>
                count(array_intersect($i['cat_ids'], $catIds)) > 0
            );
        }

        return $cart;
    }

    private function applyBundle($items, $rule)
    {
        $totalQty = collect($items)->sum('qty');

        $bundleCount = floor($totalQty / $rule->min_qty);

        if ($bundleCount <= 0) return 0;

        $bundleQty = $bundleCount * $rule->min_qty;

        // calculate price of ONLY bundle qty
        $sortedItems = collect($items)
            ->sortByDesc('price') // discount expensive first (optional but better)
            ->values();

        $remainingQty = $bundleQty;
        $original = 0;

        foreach ($sortedItems as $item) {
            if ($remainingQty <= 0) break;

            $takeQty = min($item['qty'], $remainingQty);

            $original += $takeQty * $item['price'];

            $remainingQty -= $takeQty;
        }

        $bundlePrice = $bundleCount * $rule->price;

        return max(0, $original - $bundlePrice);
    }

    private function applyVolume($items, $rule): float|int
    {
        $original = collect($items)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        return ($original * $rule->discount_percent) / 100;
    }
}
