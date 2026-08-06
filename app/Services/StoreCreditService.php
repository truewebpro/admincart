<?php

namespace App\Services;

use App\Models\CustomerShop;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\StoreCreditTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StoreCreditService
{
    /**
     * Add credit to a customer's balance AT THIS SHOP. Used for: admin manual
     * top-up, partial/full refund-as-credit, and loyalty point redemption payouts.
     */
    public function credit(
        CustomerShop $customerShop,
        float $amount,
        string $source,
        ?string $notes = null,
        ?Order $order = null,
        ?LoyaltyTransaction $loyaltyTransaction = null,
        string $createdByType = 'admin',
        ?int $createdByAdminId = null,
    ): StoreCreditTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use (
            $customerShop, $amount, $source, $notes, $order,
            $loyaltyTransaction, $createdByType, $createdByAdminId
        ) {
            // Lock the customer_shops row to prevent race conditions on concurrent
            // balance changes (e.g. a redemption and a checkout debit landing at once).
            $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

            $newBalance = round($locked->store_credit_balance + $amount, 2);
            $locked->update(['store_credit_balance' => $newBalance]);

            return StoreCreditTransaction::create([
                'shop_id' => $locked->shop_id,
                'cshop_id' => $locked->cshop_id,
                'customer_id' => $locked->customer_id,
                'type' => 'credit',
                'source' => $source,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'order_id' => $order?->id,
                'loyalty_transaction_id' => $loyaltyTransaction?->id,
                'notes' => $notes,
                'created_by_type' => $createdByType,
                'created_by_admin_id' => $createdByAdminId,
            ]);
        });
    }

    /**
     * Deduct credit from a customer's balance AT THIS SHOP. Used for: admin manual
     * deduction, and customer applying store credit at checkout.
     */
    public function debit(
        CustomerShop $customerShop,
        float $amount,
        string $source,
        ?string $notes = null,
        ?Order $order = null,
        string $createdByType = 'customer',
        ?int $createdByAdminId = null,
    ): StoreCreditTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        return DB::transaction(function () use (
            $customerShop, $amount, $source, $notes, $order, $createdByType, $createdByAdminId
        ) {
            $locked = CustomerShop::where('cshop_id', $customerShop->cshop_id)->lockForUpdate()->firstOrFail();

            if ($locked->store_credit_balance < $amount) {
                throw new RuntimeException('Insufficient store credit balance.');
            }

            $newBalance = round($locked->store_credit_balance - $amount, 2);

            // Hard invariant: balance must never go negative, regardless of caller.
            if ($newBalance < 0) {
                throw new RuntimeException('This action would result in a negative store credit balance.');
            }

            $locked->update(['store_credit_balance' => $newBalance]);

            return StoreCreditTransaction::create([
                'shop_id' => $locked->shop_id,
                'cshop_id' => $locked->cshop_id,
                'customer_id' => $locked->customer_id,
                'type' => 'debit',
                'source' => $source,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'order_id' => $order?->id,
                'notes' => $notes,
                'created_by_type' => $createdByType,
                'created_by_admin_id' => $createdByAdminId,
            ]);
        });
    }

    /**
     * How much of an order total the customer *can* cover with credit at this
     * shop (used by checkout to cap the toggle amount).
     */
    public function maxApplicable(CustomerShop $customerShop, float $orderTotal): float
    {
        return round(min($customerShop->store_credit_balance, $orderTotal), 2);
    }

    public function history(CustomerShop $customerShop, int $perPage = 20)
    {
        return StoreCreditTransaction::where('cshop_id', $customerShop->cshop_id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Sum of credit already issued against an order via refund-as-credit
     * (partial or full). Used to stop an order being refunded more than once
     * / beyond what was actually paid.
     */
    public function totalRefundedAsCredit(Order $order): float
    {
        return (float) StoreCreditTransaction::where('order_id', $order->id)
            ->whereIn('source', ['order_refund_partial', 'order_refund_full'])
            ->where('type', 'credit')
            ->sum('amount');
    }

    /**
     * How much of this order can still be refunded as credit. Since store credit
     * is the only refund path (no cash refunds), this is simply the order total
     * minus whatever's already been credited against it.
     */
    public function remainingRefundable(Order $order): float
    {
        $alreadyCredited = $this->totalRefundedAsCredit($order);
        $remaining = (float) $order->total - $alreadyCredited;

        return max(round($remaining, 2), 0);
    }
}
