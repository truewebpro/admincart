<?php

namespace App\Services;


class PricingEngine
{
    public function calculate($acart, $couponCodes = []): array
    {
        $cartItems = $this->getCartItems($acart);

        // Step 1: Tier pricing
        $cartItems = app(TierPricingService::class)->apply($cartItems);

        // Step 2: Pricing rules
        $ruleResult = app(PricingRuleService::class)
            ->apply($cartItems, $acart->shop_id);

        // Step 3: Coupons
        $couponResult = app(CouponService::class)
            ->apply($cartItems, $acart->shop_id, $couponCodes);

        $subtotal = collect($cartItems)
            ->sum(fn($i) => $i['price'] * $i['qty']);

        return [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'rule_discount' => $ruleResult['discount'],
            'coupon_discount' => $couponResult['discount'],
            'total_discount' => $ruleResult['discount'] + $couponResult['discount'],
            'final_total' => $subtotal - $ruleResult['discount'] - $couponResult['discount'],
            'coupon_breakdown' => $couponResult['breakdown'],
            'rule_name' => $ruleResult['applied_rule'],
            'rule_id' => $ruleResult['applied_rule_id'],
            'rule_type' => $ruleResult['applied_rule_type'],
        ];
    }

    private function getCartItems($acart)
    {
        return $acart->items->map(function ($item) {

            $product = $item->product;
            $variant = $item->variant; // nullable

            return [
                'product_id' => $product->product_id,
                'variant_id' => $variant?->variant_id,
                'qty' => (int) ($item->quantity ?? 1),
                'price' => (float) $item->price,
                'base_price' => (float) $item->price,

                'cat_ids' => $product->cats->pluck('cat_id')->toArray(),
            ];
        })->toArray();
    }
}
