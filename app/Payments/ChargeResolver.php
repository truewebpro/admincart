<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bridges orders that were paid before this module existed.
 *
 * Nothing in the old schema knows about payment_transactions, so a charge row
 * is derived from orders (plus viva_payments for Viva) and written on demand.
 * From then on the ledger is authoritative — partial refunds and the
 * double-refund guard all key off that row.
 *
 * - describe() reads, writes nothing. For rendering the dialog.
 * - sync()     creates or updates, never throws. For opening an order.
 * - resolve()  guarantees a row or throws. For refunding.
 */
class ChargeResolver
{
    /**
     * orders.payment_method holds a display name, and the wording varies by
     * shop and by vintage: "World Pay" and "WorldPay", "Pay Pal" and "PayPal".
     * Names are reduced to lowercase letters and digits before matching, so
     * spacing, casing and punctuation stop mattering. Add new spellings here.
     */
    private const PROVIDER_ALIASES = [
        GatewaySchema::VIVA_WALLET => ['vivasmart', 'vivawallet', 'vivapayments', 'viva'],
        GatewaySchema::WORLDPAY    => ['worldpay'],
        GatewaySchema::CYBERSOURCE => ['cybersource', 'creditcard', 'cybs'],
        GatewaySchema::PAYPAL      => ['paypal'],
    ];

    /** Read-only view of what a refund would act on. */
    public function describe(int $shopId, Order $order): array
    {
        if ($charge = $this->find($shopId, $order)) {
            return [
                'charge'      => $charge,
                'provider'    => $charge->provider,
                'reference'   => $charge->gateway_transaction_id,
                'amountMinor' => $charge->amount_minor,
                'currency'    => $charge->currency,
                'adopted'     => false,
            ];
        }

        return $this->derive($order, $shopId) + ['charge' => null, 'adopted' => true];
    }

    /**
     * Create the charge row, or refresh it if the order has changed since.
     * Safe to call on every order view: idempotent, and silent when the order
     * was paid by something this module cannot refund.
     */
    public function sync(int $shopId, Order $order): ?PaymentTransaction
    {
        try {
            $derived = $this->derive($order, $shopId);
        } catch (ValidationException) {
            return null; // Not a supported gateway, or nothing to work from.
        }

        if ($derived['amountMinor'] <= 0) {
            return null;
        }

        $charge = $this->find($shopId, $order);

        if (! $charge) {
            return $this->create($shopId, $order, $derived);
        }

        // Once money has moved against this row, its amount is load-bearing for
        // the refundable calculation. Leave it alone.
        if ($charge->children()->exists()) {
            return $charge;
        }

        $changes = array_filter([
            'gateway_transaction_id' => $derived['reference'] !== $charge->gateway_transaction_id
                ? $derived['reference'] : null,
            'amount_minor' => $derived['amountMinor'] !== $charge->amount_minor
                ? $derived['amountMinor'] : null,
            'currency' => $derived['currency'] !== $charge->currency
                ? $derived['currency'] : null,
            'provider' => $derived['provider'] !== $charge->provider
                ? $derived['provider'] : null,
        ], fn ($value) => $value !== null);

        if ($changes !== []) {
            $charge->forceFill($changes + [
                    'meta' => array_merge($charge->meta ?? [], [
                        'synced_at'      => now()->toIso8601String(),
                        'checkout_id'    => $order->checkout_id,
                        'payment_method' => $order->payment_method,
                    ]),
                ])->save();
        }

        return $charge;
    }

    /** Returns the charge row, creating it from the old tables if needed. */
    public function resolve(int $shopId, Order $order): PaymentTransaction
    {
        if ($charge = $this->find($shopId, $order)) {
            return $charge;
        }

        $derived = $this->derive($order, $shopId);

        if ($derived['amountMinor'] <= 0) {
            throw ValidationException::withMessages([
                'order' => 'This order has no payable total, so there is nothing to refund.',
            ]);
        }

        return $this->create($shopId, $order, $derived);
    }

    // ----------------------------------------------------------------- parts

    private function find(int $shopId, Order $order): ?PaymentTransaction
    {
        return PaymentTransaction::query()
            ->forShop($shopId)
            ->where('order_id', $order->getKey())
            ->where('type', PaymentTransaction::TYPE_CHARGE)
            ->where('status', PaymentTransaction::STATUS_SUCCEEDED)
            ->latest('id')
            ->first();
    }

    private function create(int $shopId, Order $order, array $derived): PaymentTransaction
    {
        return PaymentTransaction::create([
            'shop_id'                => $shopId,
            'order_id'               => $order->getKey(),
            'provider'               => $derived['provider'],
            'type'                   => PaymentTransaction::TYPE_CHARGE,
            'status'                 => PaymentTransaction::STATUS_SUCCEEDED,
            'gateway_transaction_id' => $derived['reference'],
            'amount_minor'           => $derived['amountMinor'],
            'currency'               => $derived['currency'],
            'meta'                   => [
                // Adopted payments are assumed settled: they predate this module,
                // so an authorisation would long since have cleared or expired.
                // The operator can still force a cancellation from the dialog.
                'settled'        => true,
                'adopted_at'     => now()->toIso8601String(),
                'checkout_id'    => $order->checkout_id,
                'payment_method' => $order->payment_method,
            ],
        ]);
    }

    /** @throws ValidationException when the order cannot be refunded from here. */
    private function derive(Order $order, int $shopId): array
    {
        $provider = $this->provider($order);

        return [
            'provider'    => $provider,
            'reference'   => $this->reference($provider, $shopId, $order),
            'amountMinor' => $this->amountMinor($order),
            'currency'    => $this->currency($order),
        ];
    }

    private function provider(Order $order): string
    {
        $provider = $this->matchProvider($order->payment_method);

        if ($provider === null) {
            throw ValidationException::withMessages([
                'order' => "Orders paid by [{$order->payment_method}] can't be refunded from here. "
                    . 'Only Viva, Worldpay, Cybersource and PayPal are integrated — '
                    . 'refund this one in the provider’s own dashboard.',
            ]);
        }

        return $provider;
    }

    /** Public so the same rule can be reused when listing or filtering orders. */
    public function matchProvider(?string $paymentMethod): ?string
    {
        $normalised = preg_replace('/[^a-z0-9]/', '', strtolower((string) $paymentMethod));

        if ($normalised === '') {
            return null;
        }

        // Exact first, so "creditcard" can never be swallowed by a looser alias.
        foreach (self::PROVIDER_ALIASES as $provider => $aliases) {
            if (in_array($normalised, $aliases, true)) {
                return $provider;
            }
        }

        foreach (self::PROVIDER_ALIASES as $provider => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($normalised, $alias)) {
                    return $provider;
                }
            }
        }

        return null;
    }

    /**
     * checkout_id means something different per provider, which is why this is
     * not just a column read.
     */
    private function reference(string $provider, int $shopId, Order $order): string
    {
        $checkoutId = (string) $order->checkout_id;

        if (blank($checkoutId)) {
            throw ValidationException::withMessages([
                'order' => 'This order has no payment reference stored, so it can’t be refunded automatically.',
            ]);
        }

        return match ($provider) {
            // Viva refunds need the transactionId, not the orderCode that
            // checkout_id holds. viva_payments has the pairing.
            GatewaySchema::VIVA_WALLET => $this->vivaTransactionId($shopId, $checkoutId),

            // Worldpay: already the transactionReference.
            // PayPal: the order id, which the driver turns into a capture id.
            default => $checkoutId,
        };
    }

    private function vivaTransactionId(int $shopId, string $orderCode): string
    {
        // order_code can be a bigint in one table and a string in the other,
        // so compare as text rather than relying on the column type.
        $transactionId = DB::table('viva_payments')
            ->where('shop_id', $shopId)
            ->whereRaw('CAST(order_code AS CHAR) = ?', [$orderCode])
            ->orderByDesc('payment_id')
            ->value('transaction_id');

        if (blank($transactionId)) {
            throw ValidationException::withMessages([
                'order' => "No Viva transaction is recorded for order code {$orderCode}. "
                    . 'Viva refunds need the transaction id, which viva_payments stores when the payment completes.',
            ]);
        }

        return (string) $transactionId;
    }

    /** order_total is a float, so round rather than cast. */
    private function amountMinor(Order $order): int
    {
        return (int) round(((float) $order->order_total) * 100);
    }

    private function currency(Order $order): string
    {
        return strtoupper($order->currency_code ?? config('payments.default_currency', 'GBP'));
    }
}
