<?php

namespace App\Payments\DTO;

use App\Models\PaymentTransaction;

class RefundRequest
{
    public function __construct(
        public readonly string $gatewayTransactionId,
        public readonly int $amountMinor,
        public readonly string $currency,
        public readonly bool $isFullAmount,
        public readonly string $idempotencyKey,
        public readonly ?string $reason = null,
        public readonly ?string $orderReference = null,
        /** Provider-specific handles captured when the charge was created. */
        public readonly array $meta = [],
    ) {
    }

    public static function fromCharge(
        PaymentTransaction $charge,
        int $amountMinor,
        string $idempotencyKey,
        ?string $reason = null,
    ): self {
        return new self(
            gatewayTransactionId: (string) $charge->gateway_transaction_id,
            amountMinor: $amountMinor,
            currency: $charge->currency,
            isFullAmount: $amountMinor === $charge->amount_minor,
            idempotencyKey: $idempotencyKey,
            reason: $reason,
            orderReference: (string) $charge->order_id,
            meta: $charge->meta ?? [],
        );
    }

    /** Decimal string, e.g. 1050 -> "10.50". Zero-decimal currencies handled. */
    public function amountDecimal(): string
    {
        return number_format($this->amountMinor / (10 ** $this->exponent()), $this->exponent(), '.', '');
    }

    public function exponent(): int
    {
        $zeroDecimal  = ['JPY', 'KRW', 'VND', 'CLP', 'ISK', 'XOF', 'XAF', 'PYG', 'RWF', 'UGX', 'VUV'];
        $threeDecimal = ['BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'];

        $code = strtoupper($this->currency);

        return match (true) {
            in_array($code, $zeroDecimal, true)  => 0,
            in_array($code, $threeDecimal, true) => 3,
            default                              => 2,
        };
    }
}
