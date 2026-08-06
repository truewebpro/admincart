<?php

namespace App\Services;

use App\Models\CustomerShop;
use App\Models\LoyaltyProductPoint;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    public function __construct(protected StoreCreditService $storeCreditService)
    {
    }

    /**
     * The single source of truth for "how many points does this variant earn
     * per unit", checking the admin override table first and falling back to
     * the shop's global rate. Used by BOTH earnFromOrder() (actually awarding
     * points on a real order) and the product-page points preview (just
     * showing "earn 19 points" on a card/PDP, no order involved) — same
     * calculation, so what a product page promises always matches what
     * actually gets awarded at checkout. Returns null if the loyalty program
     * is inactive for this shop (caller should show nothing in that case).
     */
    public function pointsForVariant(int $shopId, int $variantId, float $price, int $quantity = 1): ?int
    {
        $settings = LoyaltySetting::where('shop_id', $shopId)->first();

        if (! $settings || ! $settings->is_active) {
            return null;
        }

        $override = LoyaltyProductPoint::where('shop_id', $shopId)
            ->where('variant_id', $variantId)
            ->active()
            ->first();

        return $override
            ? $override->points_per_unit * $quantity
            : $settings->calculateItemPoints($price, $quantity);
    }

    /**
     * Batch version for product listing pages — one settings lookup and one
     * overrides query for the whole page instead of N+1 per card. Pass a map
     * of variant_id => ['price' => float, 'quantity' => int] (quantity usually
     * 1 for a listing page). Returns variant_id => points (0 if the program is
     * inactive, so callers can just check truthiness rather than null-checking
     * every entry).
     */
    public function pointsForVariants(int $shopId, array $variantPrices): array
    {
        $settings = LoyaltySetting::where('shop_id', $shopId)->first();

        if (! $settings || ! $settings->is_active) {
            return array_fill_keys(array_keys($variantPrices), 0);
        }

        $overrides = LoyaltyProductPoint::where('shop_id', $shopId)
            ->whereIn('variant_id', array_keys($variantPrices))
            ->active()
            ->get()
            ->keyBy('variant_id');

        $result = [];

        foreach ($variantPrices as $variantId => $line) {
            $quantity = $line['quantity'] ?? 1;
            $override = $overrides->get($variantId);

            $result[$variantId] = $override
                ? $override->points_per_unit * $quantity
                : $settings->calculateItemPoints((float) $line['price'], $quantity);
        }

        return $result;
    }

    /**
     * Award points when an order is completed. Call this from your order
     * "completed" / "paid" event listener, not on order creation, so refunded
     * or cancelled orders never earn points.
     *
     * Points are calculated PER LINE ITEM using the variant's price (not the
     * order total), so shipping/tax never earn points and each item is priced
     * independently. e.g. a £1.99 variant earns 2 points per unit under the
     * default "spend £1 get 1 point" rule.
     *
     * Admin can override the points for any specific variant via
     * `loyalty_product_points` (e.g. exclude a product entirely by setting it to 0,
     * or give a flat bonus regardless of price). If no override exists for a variant,
     * the shop's global LoyaltySetting rate is used instead — see pointsForVariants()
     * above, which is what this method delegates to for the actual per-item math.
     *
     * Expects $order->items (order_items) to be a collection of line items each
     * exposing ->variant_id, ->price, ->quantity — matching the confirmed
     * variants/order_items schema.
     *
     * Resolving the CustomerShop: if your Order model already stores cshop_id
     * directly, replace the lookup below with CustomerShop::findOrFail($order->cshop_id)
     * — that's more efficient than the customer_id + shop_id lookup here, which is
     * written defensively in case Order doesn't carry cshop_id yet.
     *
     * Uses a strict lookup, not firstOrCreate: an order can only exist after
     * checkout, and checkout (customerLogin / customerRegister / registerOnCheckout)
     * always creates the customer_shops row first — so a missing row here means
     * something upstream is wrong, and silently creating an incomplete one (missing
     * registered_at/status/ctags) would hide that rather than surface it.
     */
    public function earnFromOrder(Order $order): ?LoyaltyTransaction
    {
        $customerShop = CustomerShop::where('customer_id', $order->customer_id)
            ->where('shop_id', $order->shop_id)
            ->first();

        if (! $customerShop) {
            return null; // no customer_shops row — nothing to credit points to
        }

        $settings = LoyaltySetting::where('shop_id', $order->shop_id)->first();

        if (! $settings || ! $settings->is_active) {
            return null;
        }

        if ($settings->min_order_amount_to_earn && $order->total < $settings->min_order_amount_to_earn) {
            return null;
        }

        // Grouped by variant_id in case the same variant appears as more than
        // one line item (mapWithKeys alone would let a later line silently
        // overwrite an earlier one's quantity).
        $variantPrices = $order->items
            ->groupBy('variant_id')
            ->map(fn ($lines) => [
                'price' => (float) $lines->first()->price,
                'quantity' => (int) $lines->sum('quantity'),
            ])
            ->all();

        $pointsPerVariant = $this->pointsForVariants($order->shop_id, $variantPrices);

        $breakdown = [];
        $totalPoints = 0;

        foreach ($pointsPerVariant as $variantId => $itemPoints) {
            if ($itemPoints > 0) {
                $totalPoints += $itemPoints;
                $breakdown[] = "variant #{$variantId}: {$itemPoints}pt";
            }
        }

        if ($settings->max_points_per_order) {
            $totalPoints = min($totalPoints, $settings->max_points_per_order);
        }

        if ($totalPoints <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customerShop, $totalPoints, $order, $breakdown) {
            $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

            $newBalance = $locked->loyalty_points_balance + $totalPoints;
            $locked->update(['loyalty_points_balance' => $newBalance]);

            return LoyaltyTransaction::create([
                'shop_id' => $locked->shop_id,
                'cshop_id' => $locked->cshop_id,
                'customer_id' => $locked->customer_id,
                'type' => 'earn',
                'points' => $totalPoints,
                'balance_after' => $newBalance,
                'order_id' => $order->id,
                'notes' => "Earned from order #{$order->id} (" . implode(', ', $breakdown) . ')',
                'created_by_type' => 'system',
            ]);
        });
    }

    /**
     * Redeem points for store credit according to a redeem rule — both scoped
     * to the same shop via $customerShop.
     */
    public function redeem(CustomerShop $customerShop, LoyaltyRedeemRule $rule): LoyaltyTransaction
    {
        if (! $rule->is_active) {
            throw new RuntimeException('This redemption option is no longer available.');
        }

        if ($rule->shop_id !== $customerShop->shop_id) {
            throw new RuntimeException('This redemption option is not available at this shop.');
        }

        return DB::transaction(function () use ($customerShop, $rule) {
            $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

            if ($locked->loyalty_points_balance < $rule->points_required) {
                throw new RuntimeException('Not enough loyalty points to redeem this reward.');
            }

            $newBalance = $locked->loyalty_points_balance - $rule->points_required;
            $locked->update(['loyalty_points_balance' => $newBalance]);

            $loyaltyTxn = LoyaltyTransaction::create([
                'shop_id' => $locked->shop_id,
                'cshop_id' => $locked->cshop_id,
                'customer_id' => $locked->customer_id,
                'type' => 'redeem',
                'points' => -$rule->points_required,
                'balance_after' => $newBalance,
                'loyalty_redeem_rule_id' => $rule->id,
                'notes' => "Redeemed \"{$rule->label}\" for £{$rule->credit_value} store credit",
                'created_by_type' => 'customer',
            ]);

            // Grant the store credit, linked back to this redemption.
            $creditTxn = $this->storeCreditService->credit(
                customerShop: $locked,
                amount: (float) $rule->credit_value,
                source: 'loyalty_redeem',
                notes: "Loyalty redemption: {$rule->label} ({$rule->points_required} points)",
                loyaltyTransaction: $loyaltyTxn,
                createdByType: 'customer',
            );

            $loyaltyTxn->update(['store_credit_transaction_id' => $creditTxn->id]);

            return $loyaltyTxn->fresh();
        });
    }

    /**
     * Admin manual points adjustment (correction, goodwill bonus, etc), scoped
     * to one shop.
     */
    public function adjust(CustomerShop $customerShop, int $points, string $notes, int $adminId): LoyaltyTransaction
    {
        return DB::transaction(function () use ($customerShop, $points, $notes, $adminId) {
            $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

            $newBalance = $locked->loyalty_points_balance + $points;

            if ($newBalance < 0) {
                throw new RuntimeException('This adjustment would result in a negative points balance.');
            }

            $locked->update(['loyalty_points_balance' => $newBalance]);

            return LoyaltyTransaction::create([
                'shop_id' => $locked->shop_id,
                'cshop_id' => $locked->cshop_id,
                'customer_id' => $locked->customer_id,
                'type' => 'adjustment',
                'points' => $points,
                'balance_after' => $newBalance,
                'notes' => $notes,
                'created_by_type' => 'admin',
                'created_by_admin_id' => $adminId,
            ]);
        });
    }

    public function availableRewards(int $shopId, CustomerShop $customerShop)
    {
        return LoyaltyRedeemRule::where('shop_id', $shopId)
            ->active()
            ->get()
            ->map(fn ($rule) => [
                'id' => $rule->id,
                'label' => $rule->label,
                'points_required' => $rule->points_required,
                'credit_value' => $rule->credit_value,
                'can_redeem' => $customerShop->loyalty_points_balance >= $rule->points_required,
            ]);
    }
}
