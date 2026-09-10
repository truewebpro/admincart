<?php

namespace App\Payments\Gateways;

use App\Models\ShopPaymentGateway;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTO\GatewayResponse;
use App\Payments\DTO\RefundRequest;
use App\Payments\Exceptions\GatewayException;
use App\Payments\GatewaySchema;
use Illuminate\Support\Facades\Http;

/**
 * Viva Wallet exposes one endpoint for both operations:
 *
 *   DELETE /api/transactions/{transactionId}/?amount=&sourceCode=&currencyCode=
 *
 * Viva decides whether that becomes a cancellation or a refund based on
 * whether the transaction has cleared yet, so cancel() delegates to refund().
 * Auth is basic: merchant ID as user, API key as password.
 */
class VivaWalletGateway implements PaymentGatewayInterface
{
    /**
     * Viva wants ISO 4217 *numeric* currency codes, not the alpha ones.
     * Add rows as needed.
     */
    private const CURRENCY_CODES = [
        'GBP' => '826',
        'EUR' => '978',
        'USD' => '840',
        'CHF' => '756',
        'SEK' => '752',
        'DKK' => '208',
        'NOK' => '578',
        'PLN' => '985',
        'RON' => '946',
        'BGN' => '975',
        'CZK' => '203',
        'HUF' => '348',
    ];

    public function __construct(private readonly ShopPaymentGateway $config)
    {
    }

    public function provider(): string
    {
        return GatewaySchema::VIVA_WALLET;
    }

    public function supportsPartialCancel(): bool
    {
        return true;
    }

    public function cancel(RefundRequest $request): GatewayResponse
    {
        return $this->refund($request);
    }

    public function refund(RefundRequest $request): GatewayResponse
    {
        $merchantId = $this->config->credential('merchant_id');
        $apiKey     = $this->config->credential('api_key');

        if (! $merchantId || ! $apiKey) {
            throw GatewayException::misconfigured('Viva Wallet', 'merchant ID and API key are both required.');
        }

        if (blank($request->gatewayTransactionId)) {
            return GatewayResponse::failure(
                'missing_transaction_id',
                'No Viva transaction id is stored for this payment, so there is nothing to refund.'
            );
        }

        $query = ['amount' => $request->amountMinor];

        if ($source = $this->config->credential('source_code')) {
            $query['sourceCode'] = $source;
        }

        if ($currency = $this->currencyCode($request->currency)) {
            $query['currencyCode'] = $currency;
        }

        // Viva documents this path with a trailing slash before the query
        // string. Without it the request routes nowhere and comes back as a
        // 404 with an empty body.
        $url = $this->baseUrl() . '/api/transactions/' . rawurlencode($request->gatewayTransactionId) . '/';

        $response = Http::withBasicAuth($merchantId, $apiKey)
            ->acceptJson()
            ->timeout(config('payments.timeout', 30))
            ->retry(2, 500, throw: false)
            ->delete($url, $query);

        $body = $response->json() ?? [];

        // Viva returns HTTP 200 with ErrorCode 0 on success; anything else is a failure.
        $errorCode = $body['ErrorCode'] ?? null;

        if ($response->successful() && (int) $errorCode === 0) {
            return GatewayResponse::success(
                transactionId: $body['TransactionId'] ?? null,
                raw: $body,
                // 'F' (finished) means settled; everything else is still moving.
                status: ($body['StatusId'] ?? null) === 'F' ? 'succeeded' : 'pending',
            );
        }

        return GatewayResponse::failure(
            code: $errorCode !== null ? (string) $errorCode : (string) $response->status(),
            message: $body['ErrorText'] ?? $body['message'] ?? 'Viva Wallet rejected the refund.',
            // Keep the URL and query on failures so a 404 can be diagnosed from
            // the log screen. No credentials here — auth travels in the header.
            raw: array_merge($body ?: [], [
                'http_status'   => $response->status(),
                'request_url'   => $url,
                'request_query' => $query,
                'raw_body'      => $body ? null : $response->body(),
            ]),
        );
    }

    /**
     * Read-only. Looks up a transaction id that cannot exist: 401 means the
     * merchant ID / API key pair is wrong, anything else means we authenticated.
     */
    public function verifyCredentials(): GatewayResponse
    {
        $merchantId = $this->config->credential('merchant_id');
        $apiKey     = $this->config->credential('api_key');

        if (! $merchantId || ! $apiKey) {
            return GatewayResponse::failure('gateway_misconfigured', 'Enter a merchant ID and API key first.');
        }

        $probe = '00000000-0000-0000-0000-000000000000';

        $response = Http::withBasicAuth($merchantId, $apiKey)
            ->acceptJson()
            ->timeout(config('payments.timeout', 30))
            ->get($this->baseUrl() . '/api/transactions/' . $probe . '/');

        if (in_array($response->status(), [401, 403], true)) {
            return GatewayResponse::failure(
                (string) $response->status(),
                'Viva Wallet rejected these credentials. Check the Merchant ID and API Key under '
                . 'Settings → API access → Access Credentials — not the Client ID and Secret.',
                ['http_status' => $response->status()],
            );
        }

        return GatewayResponse::success(null, [
            'http_status' => $response->status(),
            'environment' => $this->config->environment,
        ]);
    }

    private function currencyCode(string $currency): ?string
    {
        return self::CURRENCY_CODES[strtoupper($currency)] ?? null;
    }

    private function baseUrl(): string
    {
        return $this->config->isSandbox()
            ? 'https://demo-api.vivapayments.com'
            : 'https://api.vivapayments.com';
    }
}
