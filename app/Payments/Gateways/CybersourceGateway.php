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
 * Cybersource REST, authenticated with HTTP Signature (no SDK dependency).
 *
 *   refund  -> POST /pts/v2/payments/{id}/refunds
 *   cancel  -> POST /pts/v2/payments/{id}/reversals   (authorisation reversal)
 *
 * If your flow captures separately, pass the capture id in meta.capture_id and
 * the refund is routed to /pts/v2/captures/{id}/refunds instead.
 */
class CybersourceGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly ShopPaymentGateway $config)
    {
    }

    public function provider(): string
    {
        return GatewaySchema::CYBERSOURCE;
    }

    public function supportsPartialCancel(): bool
    {
        return true; // Partial authorisation reversals are allowed.
    }

    public function refund(RefundRequest $request): GatewayResponse
    {
        $captureId = data_get($request->meta, 'capture_id');

        $path = $captureId
            ? '/pts/v2/captures/' . rawurlencode($captureId) . '/refunds'
            : '/pts/v2/payments/' . rawurlencode($request->gatewayTransactionId) . '/refunds';

        $payload = [
            'clientReferenceInformation' => [
                'code' => $this->reference($request),
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $request->amountDecimal(),
                    'currency'    => strtoupper($request->currency),
                ],
            ],
        ];

        if ($descriptor = $this->config->credential('merchant_descriptor')) {
            $payload['merchantInformation']['merchantDescriptor']['name'] = $descriptor;
        }

        return $this->post($path, $payload, ['PENDING', 'TRANSMITTED', 'ACCEPTED']);
    }

    public function cancel(RefundRequest $request): GatewayResponse
    {
        $path = '/pts/v2/payments/' . rawurlencode($request->gatewayTransactionId) . '/reversals';

        $payload = [
            'clientReferenceInformation' => [
                'code' => $this->reference($request),
            ],
            'reversalInformation' => [
                'amountDetails' => [
                    'totalAmount' => $request->amountDecimal(),
                ],
                'reason' => $request->reason ?? 'Order cancelled by merchant',
            ],
        ];

        return $this->post($path, $payload, ['REVERSED', 'PENDING']);
    }

    /**
     * Read-only. A signed GET for a payment id that cannot exist. A bad key,
     * bad shared secret or a clock more than ~15 minutes out gives 401; a
     * correctly signed request gets 404, which is the result we want.
     */
    public function verifyCredentials(): GatewayResponse
    {
        foreach (['merchant_id', 'key_id', 'shared_secret'] as $key) {
            if (! $this->config->credential($key)) {
                return GatewayResponse::failure(
                    'gateway_misconfigured',
                    'Enter the merchant ID, key ID and shared secret first.'
                );
            }
        }

        $host = $this->host();
        $path = '/pts/v2/payments/0000000000000000000000';
        $date = $this->httpDate();

        $response = Http::withHeaders($this->signatureHeaders('get', $path, null, $host, $date))
            ->timeout(config('payments.timeout', 30))
            ->get("https://{$host}{$path}");

        if (in_array($response->status(), [401, 403], true)) {
            return GatewayResponse::failure(
                (string) $response->status(),
                'Cybersource rejected the signature. Check the key ID and shared secret, '
                . 'and confirm this server’s clock is accurate — signatures expire quickly.',
                ['http_status' => $response->status(), 'server_time' => $date],
            );
        }

        return GatewayResponse::success(null, [
            'http_status' => $response->status(),
            'host'        => $host,
        ]);
    }

    // ---------------------------------------------------------------- request

    private function post(string $path, array $payload, array $okStatuses): GatewayResponse
    {
        $host = $this->host();
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $date = $this->httpDate();

        $response = Http::withHeaders($this->signatureHeaders('post', $path, $body, $host, $date))
            ->withBody($body, 'application/json')
            ->timeout(config('payments.timeout', 30))
            ->post("https://{$host}{$path}");

        $json   = $response->json() ?? [];
        $status = data_get($json, 'status');

        if ($response->successful() && in_array($status, $okStatuses, true)) {
            return GatewayResponse::success(
                transactionId: data_get($json, 'id'),
                raw: $json,
                status: $status === 'PENDING' ? 'pending' : 'succeeded',
            );
        }

        return GatewayResponse::failure(
            code: data_get($json, 'reason', $status ?? (string) $response->status()),
            message: data_get($json, 'message')
            ?? data_get($json, 'details.0.reason')
            ?? 'Cybersource rejected the request.',
            raw: $json ?: ['http_status' => $response->status(), 'body' => $response->body()],
        );
    }

    /**
     * Builds the Signature header Cybersource expects. The signing string is
     * newline-joined "header: value" pairs in the exact order advertised in the
     * `headers` parameter.
     *
     * Pass $body as null for a GET: there is no payload to digest, so the
     * digest header is left out of both the request and the signature.
     */
    private function signatureHeaders(string $method, string $path, ?string $body, string $host, string $date): array
    {
        $merchantId   = $this->config->credential('merchant_id');
        $keyId        = $this->config->credential('key_id');
        $sharedSecret = $this->config->credential('shared_secret');

        if (! $merchantId || ! $keyId || ! $sharedSecret) {
            throw GatewayException::misconfigured('Cybersource', 'merchant ID, key ID and shared secret are all required.');
        }

        $values = [
            'host'             => $host,
            'date'             => $date,
            '(request-target)' => strtolower($method) . ' ' . $path,
            'v-c-merchant-id'  => $merchantId,
        ];

        $signedHeaders = ['host', 'date', '(request-target)', 'v-c-merchant-id'];
        $digest        = null;

        if ($body !== null) {
            $digest            = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
            $values['digest']  = $digest;
            $signedHeaders     = ['host', 'date', '(request-target)', 'digest', 'v-c-merchant-id'];
        }

        $signingString = collect($signedHeaders)
            ->map(fn (string $h) => "{$h}: {$values[$h]}")
            ->implode("\n");

        $signature = base64_encode(
            hash_hmac('sha256', $signingString, base64_decode($sharedSecret), true)
        );

        $headers = [
            'v-c-merchant-id' => $merchantId,
            'Date'            => $date,
            'Host'            => $host,
            'Signature'       => sprintf(
                'keyid="%s", algorithm="HmacSHA256", headers="%s", signature="%s"',
                $keyId,
                implode(' ', $signedHeaders),
                $signature
            ),
            'Accept'          => 'application/hal+json;charset=utf-8',
        ];

        if ($digest !== null) {
            $headers['Digest'] = $digest;
        }

        return $headers;
    }

    private function httpDate(): string
    {
        return gmdate('D, d M Y H:i:s \G\M\T');
    }

    private function host(): string
    {
        return $this->config->isSandbox() ? 'apitest.cybersource.com' : 'api.cybersource.com';
    }

    private function reference(RefundRequest $request): string
    {
        return substr(($request->orderReference ?? 'order') . '-' . $request->idempotencyKey, 0, 50);
    }
}
