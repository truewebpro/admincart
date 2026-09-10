<?php

namespace App\Payments\Exceptions;

use RuntimeException;

class GatewayException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function misconfigured(string $provider, string $detail): self
    {
        return new self("{$provider} is not configured for this shop: {$detail}", 'gateway_misconfigured');
    }
}
