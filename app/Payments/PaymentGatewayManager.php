<?php

namespace App\Payments;

use App\Models\ShopPaymentGateway;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Exceptions\GatewayException;
use App\Payments\Gateways\CybersourceGateway;
use App\Payments\Gateways\PayPalGateway;
use App\Payments\Gateways\VivaWalletGateway;
use App\Payments\Gateways\WorldpayGateway;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private const DRIVERS = [
        GatewaySchema::VIVA_WALLET => VivaWalletGateway::class,
        GatewaySchema::WORLDPAY    => WorldpayGateway::class,
        GatewaySchema::CYBERSOURCE => CybersourceGateway::class,
        GatewaySchema::PAYPAL      => PayPalGateway::class,
    ];

    /** @var array<string, PaymentGatewayInterface> */
    private array $resolved = [];

    public function driver(int $shopId, string $provider): PaymentGatewayInterface
    {
        $cacheKey = "$shopId:$provider";

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        if (! isset(self::DRIVERS[$provider])) {
            throw new GatewayException("No driver registered for [{$provider}].", 'unknown_provider');
        }

        $config = ShopPaymentGateway::query()
            ->forShop($shopId)
            ->active()
            ->where('provider', $provider)
            ->first();

        if (! $config) {
            throw new GatewayException(
                "This shop has no active {$provider} configuration.",
                'gateway_not_configured'
            );
        }

        $driver = self::DRIVERS[$provider];

        return $this->resolved[$cacheKey] = new $driver($config);
    }
}
