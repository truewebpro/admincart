<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService
{
    public function apply($cart, $shopId, $codes = [],$customerId = null): array
    {
        $codes = array_unique($codes);

        $subtotal = collect($cart)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        $coupons = Coupon::with(['products', 'cats'])
            ->where('shop_id', $shopId)
            ->where(function ($q) use ($codes) {
                $q->whereIn('code', $codes)
                    ->orWhere('is_auto', true);
            })
            ->active()
            ->orderBy('priority','asc')
            ->get();

        $stackable = [];
        $exclusive = [];

        // First pass: evaluate EVERY eligible coupon (no early break), so
        // exclusive (non-stackable) ones can be compared against each
        // other below instead of just taking whichever sorted first.
        foreach ($coupons as $coupon) {

            // ✅ validate coupon
            if (!$this->isValid($coupon, $subtotal, $customerId)) {
                continue;
            }

            if (!$this->checkConditions($coupon, $cart, $customerId)) {
                continue;
            }

            $eligibleItems = $this->getEligibleItems($cart, $coupon);

            if (empty($eligibleItems)) continue;

            $discount = $this->calculateDiscount($coupon, $eligibleItems);

            if ($discount <= 0) continue;

            $entry = ['coupon' => $coupon, 'discount' => $discount];

            if ($coupon->is_stackable) {
                $stackable[] = $entry;
            } else {
                $exclusive[] = $entry;
            }
        }

        // Among mutually-exclusive coupons, only the single BEST one
        // applies — not whichever happened to sort first by priority.
        // This keeps a customer-facing "4 for £19.99" badge honest: the
        // customer always gets whichever eligible auto-promo saves them
        // the most, never a smaller one that merely came first.
        $bestExclusive = collect($exclusive)->sortByDesc('discount')->first();

        $applied = $stackable;
        if ($bestExclusive) {
            $applied[] = $bestExclusive;
        }

        // Second pass: apply the selected set, capping against a
        // remaining-total so no single coupon's discount can push the
        // running total below zero.
        $totalDiscount = 0;
        $remainingTotal = $subtotal;
        $breakdown = [];

        foreach ($applied as $entry) {
            $coupon = $entry['coupon'];

            $eligibleItems = $this->getEligibleItems($cart, $coupon);
            $eligibleTotal = collect($eligibleItems)
                ->sum(fn($i) => $i['price'] * $i['qty']);

            $discount = min($entry['discount'], $eligibleTotal);
            $discount = min($discount, $remainingTotal);

            $totalDiscount += $discount;
            $remainingTotal -= $discount;

            $breakdown[] = [
                'coupon_id' => $coupon->coupon_id,
                'code' => $coupon->code,     // real code — always the persistence key
                'label' => $coupon->label,   // friendly customer-facing text
                'discount' => round($discount, 2),
                'type' => $coupon->type,
                'is_auto' => $coupon->is_auto,
                'title' => $coupon->display_title,
            ];
        }

        return [
            'discount' => round($totalDiscount, 2),
            'breakdown' => $breakdown
        ];
    }

    private function isValid($coupon, $subtotal, $customerId):bool
    {
        // min order — checked against the ORIGINAL cart subtotal, not a
        // running total that shrinks as other coupons apply. A coupon
        // with no min_order_amount always passes this check.
        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return false;
        }

        // global usage
        if ($coupon->usage_limit) {
            $used = CouponUsage::where('coupon_id', $coupon->coupon_id)
                ->sum('times_used');

            if ($used >= $coupon->usage_limit) {
                return false;
            }
        }

        // per customer usage
        if ($customerId && $coupon->per_customer_limit) {
            $used = CouponUsage::where('coupon_id', $coupon->coupon_id)
                ->where('customer_id', $customerId)
                ->sum('times_used');

            if ($used >= $coupon->per_customer_limit) {
                return false;
            }
        }

        return true;
    }

    private function getEligibleItems($cart, $coupon)
    {
        if ($coupon->applies_to === 'products') {
            $ids = $coupon->products->pluck('product_id')->toArray();

            return array_filter($cart, fn($i) =>
            in_array($i['product_id'], $ids)
            );
        }

        if ($coupon->applies_to === 'cats') {
            $catIds = $coupon->cats->pluck('cat_id')->toArray();

            return array_filter($cart, fn($i) =>
                count(array_intersect($i['cat_ids'], $catIds)) > 0
            );
        }

        return $cart;
    }

    private function calculateDiscount($coupon, $items): float
    {
        $total = collect($items)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        $discount = 0;

        switch ($coupon->type) {

            case 'fixed':
                $discount = min($coupon->value, $total);
                break;

            case 'percentage':
                $discount = ($total * $coupon->value) / 100;
                break;

            case 'bogo':
                $discount = $this->applyBogo($items, $coupon);
                break;

            case 'bundle':
                $discount = $this->applyBundle($items, $coupon);
                break;
        }

        return round(max(0, $discount), 2);
    }

    private function checkConditions($coupon, $cart, $customerId): bool
    {
        $cond = $coupon->conditions ?? [];
        if (empty($cond)) return true;

        $cartQty = collect($cart)->sum('qty');

        if (!empty($cond['bundle_qty'])) {
            if ($cartQty < $cond['bundle_qty']) return false;
        }

        if (!empty($cond['buy_qty'])) {
            if ($cartQty < $cond['buy_qty']) return false;
        }

        // ✅ min qty
        if (!empty($cond['min_qty']) && $cartQty < $cond['min_qty']) {
            return false;
        }

        // ✅ OR logic for product/category
        $hasProduct = false;
        $hasCategory = false;

        if (!empty($cond['product_ids'])) {
            $hasProduct = collect($cart)->contains(fn($i) =>
            in_array($i['product_id'], $cond['product_ids'])
            );
        }

        if (!empty($cond['category_ids'])) {
            $hasCategory = collect($cart)->contains(fn($i) =>
                count(array_intersect($i['cat_ids'], $cond['category_ids'])) > 0
            );
        }

        if (!empty($cond['product_ids']) || !empty($cond['category_ids'])) {
            if (!$hasProduct && !$hasCategory) {
                return false;
            }
        }

        return true;
    }

    private function applyBogo($items, $coupon): float|int
    {
        $cond = $coupon->conditions;
        $buyQty = $cond['buy_qty'] ?? 0;
        $getQty = $cond['get_qty'] ?? 0;
        if (!$buyQty || !$getQty) return 0;
        $expanded = [];
        foreach ($items as $item) {
            for ($i = 0; $i < $item['qty']; $i++) {
                $expanded[] = $item['price'];
            }
        }
        $groupSize = $buyQty + $getQty;
        if (count($expanded) < $groupSize) return 0;
        /// sort cheapest first
        $expanded = collect($expanded)->sort()->values();
        $discount = 0;
        $groups = floor($expanded->count() / $groupSize);
        for ($i = 0; $i < $groups; $i++) {

            $start = $i * $groupSize;

            // take cheapest items in each group as free
            for ($j = 0; $j < $getQty; $j++) {
                $discount += $expanded[$start + $j];
            }
        }

        $cartTotal = collect($items)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        $discount = min($discount, $cartTotal);

        return round($discount, 2);
    }

    private function applyBundle($items, $coupon): float|int
    {
        $cond = $coupon->conditions;

        $bundleQty = $cond['bundle_qty'] ?? 0;
        $bundlePrice = $cond['bundle_price'] ?? 0;

        if (!$bundleQty || !$bundlePrice) return 0;

        // expand items into unit-level prices
        $expanded = [];

        foreach ($items as $item) {
            for ($i = 0; $i < $item['qty']; $i++) {
                $expanded[] = $item['price'];
            }
        }

        $totalQty = count($expanded);

        if ($totalQty < $bundleQty) return 0;

        // sort highest first (optional but fair)
        $expanded = collect($expanded)->sortDesc()->values();

        $discount = 0;

        $bundleCount = floor($totalQty / $bundleQty);

        for ($i = 0; $i < $bundleCount; $i++) {

            $start = $i * $bundleQty;

            // original price of this bundle group
            $groupSum = 0;

            for ($j = 0; $j < $bundleQty; $j++) {
                $groupSum += $expanded[$start + $j];
            }

            // discount = original - bundle price
            $discount += max(0, $groupSum - $bundlePrice);
        }

        // safety cap
        $cartTotal = collect($items)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        return round(min($discount, $cartTotal), 2);
    }
}
