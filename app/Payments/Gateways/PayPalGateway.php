<?php

namespace App\Payments\Gateways;

use App\Models\ShopPaymentGateway;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTO\GatewayResponse;
use App\Payments\DTO\RefundRequest;
use App\Payments\Exceptions\GatewayException;
use App\Payments\GatewaySchema;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * PayPal REST v2.
 *
 *   refund -> POST /v2/payments/captures/{captureId}/refund
 *   cancel -> POST /v2/payments/authorizations/{authId}/void
 *
 * Neither takes the order id that checkout usually stores, so the driver reads
 * the order first to find the capture (or authorization) inside it. Orders that
 * were captured immediately have no authorization, in which case a cancel
 * becomes a full refund.
 */
class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly ShopPaymentGateway $config)
    {
    }

    public function provider(): string
    {
        return GatewaySchema::PAYPAL;
    }

    public function supportsPartialCancel(): bool
    {
        return false; // Voiding an authorization always releases the whole amount.
    }

    public function refund(RefundRequest $request): GatewayResponse
    {
        $captureId = data_get($request->meta, 'capture_id') ?: $this->captureId($request->gatewayTransactionId);

        if (! $captureId) {
            return GatewayResponse::failure(
                'paypal_no_capture',
                'No captured payment was found for this PayPal order, so there is nothing to refund.'
            );
        }

        $payload = [
            'amount' => [
                'value'         => $request->amountDecimal(),
                'currency_code' => strtoupper($request->currency),
            ],
        ];

        if ($request->reason) {
            $payload['note_to_payer'] = mb_substr($request->reason, 0, 255);
        }

        if ($request->orderReference) {
            $payload['invoice_id'] = mb_substr('refund-' . $request->orderReference, 0, 127);
        }

        $response = $this->client($request->idempotencyKey)
            ->post($this->baseUrl() . "/v2/payments/captures/{$captureId}/refund", $payload);

        $body = $response->json() ?? [];

        if ($response->successful()) {
            $status = data_get($body, 'status');

            return GatewayResponse::success(
                transactionId: data_get($body, 'id'),
                raw: $body,
                // PENDING happens when PayPal holds the refund for review.
                status: $status === 'COMPLETED' ? 'succeeded' : 'pending',
                meta: ['capture_id' => $captureId],
            );
        }

        return $this->failure($response->status(), $body);
    }

    public function cancel(RefundRequest $request): GatewayResponse
    {
        $authorizationId = data_get($request->meta, 'authorization_id')
            ?: $this->authorizationId($request->gatewayTransactionId);

        // Intent=CAPTURE orders never hold an authorization, so there is
        // nothing to void — returning the money is the only reversal available.
        if (! $authorizationId) {
            return $this->refund($request);
        }

        $response = $this->client($request->idempotencyKey)
            ->post($this->baseUrl() . "/v2/payments/authorizations/{$authorizationId}/void");

        // A successful void returns 204 with no body.
        if ($response->successful()) {
            return GatewayResponse::success(
                transactionId: $authorizationId,
                raw: $response->json() ?? ['http_status' => $response->status()],
                status: 'succeeded',
                meta: ['authorization_id' => $authorizationId],
            );
        }

        return $this->failure($response->status(), $response->json() ?? []);
    }

    /** Read-only: asking for a token proves the credentials without touching money. */
    public function verifyCredentials(): GatewayResponse
    {
        $clientId = $this->config->credential('client_id');
        $secret   = $this->config->credential('client_secret');

        if (! $clientId || ! $secret) {
            return GatewayResponse::failure('gateway_misconfigured', 'Enter a client ID and client secret first.');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->timeout(config('payments.timeout', 30))
            ->post($this->baseUrl() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->successful()) {
            return GatewayResponse::success(null, [
                'http_status' => $response->status(),
                'mode'        => $this->mode(),
                'scope_count' => count(explode(' ', (string) data_get($response->json(), 'scope'))),
            ]);
        }

        return GatewayResponse::failure(
            (string) $response->status(),
            'PayPal rejected these credentials. Check the client ID and secret, and that they belong to the '
            . $this->mode() . ' app — sandbox and live credentials are not interchangeable.',
            ['http_status' => $response->status()],
        );
    }

    // ------------------------------------------------------------- resolution

    /** The capture inside a PayPal order. Falls back to treating the id as a capture. */
    private function captureId(string $identifier): ?string
    {
        return $this->fromOrder($identifier, 'captures') ?? $identifier;
    }

    private function authorizationId(string $identifier): ?string
    {
        return $this->fromOrder($identifier, 'authorizations');
    }

    private function fromOrder(string $orderId, string $type): ?string
    {
        return Cache::remember(
            "paypal:{$type}:" . md5($this->config->id . '|' . $orderId),
            now()->addMinutes(30),
            function () use ($orderId, $type) {
                $response = $this->client()->get($this->baseUrl() . "/v2/checkout/orders/{$orderId}");

                // A 404 means the stored id is not an order id — most likely it
                // is already the capture id, which the caller handles.
                if (! $response->successful()) {
                    return null;
                }

                return data_get($response->json(), "purchase_units.0.payments.{$type}.0.id");
            }
        );
    }

    // ------------------------------------------------------------------ http

    private function client(?string $idempotencyKey = null): PendingRequest
    {
        $headers = ['Content-Type' => 'application/json'];

        if ($idempotencyKey) {
            // PayPal replays the original result for a repeated request id.
            $headers['PayPal-Request-Id'] = substr($idempotencyKey, 0, 108);
        }

        return Http::withToken($this->accessToken())
            ->withHeaders($headers)
            ->acceptJson()
            ->timeout(config('payments.timeout', 30));
    }

    private function accessToken(): string
    {
        $clientId = $this->config->credential('client_id');
        $secret   = $this->config->credential('client_secret');

        if (! $clientId || ! $secret) {
            throw GatewayException::misconfigured('PayPal', 'a client ID and client secret are required.');
        }

        return Cache::remember(
            'paypal:token:' . md5($this->config->id . '|' . $clientId . '|' . $this->mode()),
            now()->addMinutes(50), // Tokens last ~9 hours; this just bounds staleness.
            function () use ($clientId, $secret) {
                $response = Http::asForm()
                    ->withBasicAuth($clientId, $secret)
                    ->timeout(config('payments.timeout', 30))
                    ->post($this->baseUrl() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

                $token = data_get($response->json(), 'access_token');

                if (! $response->successful() || ! $token) {
                    throw new GatewayException(
                        'PayPal would not issue an access token. Check the client ID and secret for '
                        . $this->mode() . '.',
                        'paypal_auth_failed'
                    );
                }

                return $token;
            }
        );
    }

    private function failure(int $status, array $body): GatewayResponse
    {
        return GatewayResponse::failure(
            code: data_get($body, 'name', (string) $status),
            message: data_get($body, 'details.0.description')
            ?? data_get($body, 'message')
            ?? 'PayPal rejected the request.',
            raw: $body ?: ['http_status' => $status],
        );
    }

    /** `mode` wins when set; otherwise fall back to the environment radio. */
    private function mode(): string
    {
        $mode = $this->config->credential('mode');

        if (in_array($mode, ['sandbox', 'live'], true)) {
            return $mode;
        }

        return $this->config->isSandbox() ? 'sandbox' : 'live';
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
}
