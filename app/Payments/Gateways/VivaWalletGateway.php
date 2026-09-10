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
 *   DELETE /api/transactions/{transactionId}?amount={minor}
 *
 * Viva decides whether that becomes a cancellation or a refund based on
 * whether the transaction has cleared yet, so cancel() delegates to refund().
 * Auth is basic: merchant ID as user, API key as password.
 */
class VivaWalletGateway implements PaymentGatewayInterface
{
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

        $query = ['amount' => $request->amountMinor];

        if ($source = $this->config->credential('source_code')) {
            $query['sourceCode'] = $source;
        }

        if ($request->reason) {
            $query['customerTrns'] = mb_substr($request->reason, 0, 255);
        }

        $url = $this->baseUrl() . '/api/transactions/' . rawurlencode($request->gatewayTransactionId);

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
            raw: $body ?: ['http_status' => $response->status(), 'body' => $response->body()],
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
            ->get($this->baseUrl() . '/api/transactions/' . $probe);

        if (in_array($response->status(), [401, 403], true)) {
            return GatewayResponse::failure(
                (string) $response->status(),
                'Viva Wallet rejected these credentials. Check the merchant ID and API key.',
                ['http_status' => $response->status()],
            );
        }

        return GatewayResponse::success(null, [
            'http_status' => $response->status(),
            'environment' => $this->config->environment,
        ]);
    }

    private function baseUrl(): string
    {
        return $this->config->isSandbox()
            ? 'https://demo-api.vivapayments.com'
            : 'https://api.vivapayments.com';
    }
}
