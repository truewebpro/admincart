<?php

namespace App\Payments\DTO;

class GatewayResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $transactionId = null,
        /** pending | succeeded | failed — providers differ on when money actually moves. */
        public readonly string $status = 'succeeded',
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
        public readonly array $meta = [],
    ) {
    }

    public static function success(?string $transactionId, array $raw = [], string $status = 'succeeded', array $meta = []): self
    {
        return new self(true, $transactionId, $status, null, null, $raw, $meta);
    }

    public static function failure(?string $code, ?string $message, array $raw = []): self
    {
        return new self(false, null, 'failed', $code, $message, $raw);
    }
}
