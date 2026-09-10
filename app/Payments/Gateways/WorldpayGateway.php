<?php

namespace App\Payments\Gateways;

use App\Models\ShopPaymentGateway;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTO\GatewayResponse;
use App\Payments\DTO\RefundRequest;
use App\Payments\Exceptions\GatewayException;
use App\Payments\GatewaySchema;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Two Worldpay products, selected by the `api_flavour` credential:
 *
 * - "access": Worldpay Access. Refunds go to the action links Worldpay returns.
 *   If those links were never stored on the charge, they are looked up from the
 *   payment reference through the Payment Queries API (see resolveActionLink).
 * - "online": the older Online Payments REST API, where the order code is
 *   enough and auth is a single service key.
 */
class WorldpayGateway implements PaymentGatewayInterface
{
    /** Payment Queries only covers payments taken after this date. */
    private const QUERY_API_CUTOVER = '2024-06-25';

    public function __construct(private readonly ShopPaymentGateway $config)
    {
    }

    public function provider(): string
    {
        return GatewaySchema::WORLDPAY;
    }

    public function supportsPartialCancel(): bool
    {
        return false; // A cancellation always releases the whole authorisation.
    }

    public function refund(RefundRequest $request): GatewayResponse
    {
        return $this->isAccess()
            ? $this->accessAction($request, $request->isFullAmount ? 'refund' : 'partialRefund')
            : $this->onlineAction($request, 'refund');
    }

    public function cancel(RefundRequest $request): GatewayResponse
    {
        return $this->isAccess()
            ? $this->accessAction($request, 'cancel')
            : $this->onlineAction($request, 'cancel');
    }

    public function verifyCredentials(): GatewayResponse
    {
        if ($this->isAccess()) {
            $username = $this->config->credential('username');
            $password = $this->config->credential('password');

            if (! $username || ! $password) {
                return GatewayResponse::failure('gateway_misconfigured', 'Enter an API username and password first.');
            }

            $status = Http::withBasicAuth($username, $password)
                ->acceptJson()
                ->timeout(config('payments.timeout', 30))
                ->get($this->accessBaseUrl() . '/')
                ->status();

            return $this->interpretProbe($status, ['http_status' => $status, 'flavour' => 'access']);
        }

        $serviceKey = $this->config->credential('service_key');

        if (! $serviceKey) {
            return GatewayResponse::failure('gateway_misconfigured', 'Enter a service key first.');
        }

        $status = Http::withHeaders(['Authorization' => $serviceKey])
            ->acceptJson()
            ->timeout(config('payments.timeout', 30))
            ->get('https://api.worldpay.com/v1/orders/connection-check-000000')
            ->status();

        return $this->interpretProbe($status, ['http_status' => $status, 'flavour' => 'online']);
    }

    private function interpretProbe(int $status, array $context): GatewayResponse
    {
        if (in_array($status, [401, 403], true)) {
            return GatewayResponse::failure(
                (string) $status,
                'Worldpay rejected these credentials. Check the product you selected and the keys for it.',
                $context,
            );
        }

        return GatewayResponse::success(null, $context);
    }

    // ---------------------------------------------------------------- Access

    private function accessAction(RefundRequest $request, string $action): GatewayResponse
    {
        $href = $this->resolveActionLink($request, $action);

        $payload = [];

        if (in_array($action, ['refund', 'partialRefund'], true)) {
            $payload = [
                'reference' => $this->reference($request),
                'value'     => [
                    'amount'   => $request->amountMinor,
                    'currency' => strtoupper($request->currency),
                ],
            ];
        }

        $version     = $this->config->credential('api_version', 'v7');
        $contentType = "application/vnd.worldpay.payments-{$version}+json";

        $response = $this->client()
            ->withHeaders([
                'Content-Type'    => $contentType,
                'Accept'          => $contentType,
                'WP-Api-Version'  => $version,
                'Idempotency-Key' => $request->idempotencyKey,
            ])
            ->post($href, $payload);

        $body = $response->json() ?? [];

        if ($response->successful()) {
            return GatewayResponse::success(
                transactionId: data_get($body, 'commandId') ?? data_get($body, 'transactionReference'),
                raw: $body,
                status: 'pending', // Access confirms the outcome asynchronously by webhook.
                meta: ['_links' => data_get($body, '_links', [])],
            );
        }

        return GatewayResponse::failure(
            code: data_get($body, 'errorName', (string) $response->status()),
            message: data_get($body, 'message', 'Worldpay rejected the request.'),
            raw: $body ?: ['http_status' => $response->status(), 'body' => $response->body()],
        );
    }

    /**
     * Prefer links saved on the charge. When the charge only has an id — which
     * is the case for anything taken before we started storing `_links` — ask
     * Worldpay for them, then cache so repeat refunds skip the lookup.
     */
    private function resolveActionLink(RefundRequest $request, string $action): string
    {
        $links = data_get($request->meta, '_links');

        if (blank($links)) {
            $links = Cache::remember(
                'worldpay:links:' . md5($this->config->id . '|' . $request->gatewayTransactionId),
                now()->addMinutes(30),
                fn () => $this->lookupLinks($request->gatewayTransactionId),
            );
        }

        $candidates = match ($action) {
            'refund'        => ['payments:refund', 'payments:fullRefund', 'payments:partialRefund'],
            'partialRefund' => ['payments:partialRefund', 'payments:refund'],
            'cancel'        => ['payments:cancel', 'payments:reverse'],
            default         => [],
        };

        foreach ($candidates as $rel) {
            if ($href = data_get($links, "$rel.href")) {
                return $href;
            }
        }

        throw new GatewayException(
            "Worldpay returned no {$action} link for this payment, so it can't be reversed from here. "
            . 'The payment may already be fully refunded, or too old for the Payment Queries API '
            . '(it only covers payments taken after ' . self::QUERY_API_CUTOVER . ').',
            'worldpay_missing_action_link',
            ['available' => array_keys($links ?? [])],
        );
    }

    /**
     * The stored id can be either our own transactionReference or Worldpay's
     * paymentId. Worldpay's own ids are prefixed "pay", so try a direct
     * retrieve for those and a reference search otherwise, then fall back to
     * the archive and finally the recovery endpoint.
     */
    private function lookupLinks(string $identifier): array
    {
        $base = $this->accessBaseUrl();

        if (str_starts_with($identifier, 'pay')) {
            $links = $this->linksFrom($this->query("{$base}/paymentQueries/payments/" . rawurlencode($identifier)));

            if ($links !== []) {
                return $links;
            }
        }

        // Find the paymentId from our own reference, then retrieve it in full:
        // the search response carries summary data, not the action links.
        $search    = $this->query("{$base}/paymentQueries/payments", ['transactionReference' => $identifier]);
        $paymentId = data_get($search?->json(), '_embedded.payments.0.paymentId');

        if ($paymentId) {
            $links = $this->linksFrom($this->query("{$base}/paymentQueries/payments/" . rawurlencode($paymentId)));

            if ($links !== []) {
                return $links;
            }
        }

        $entity = $this->config->credential('entity_reference');

        if ($entity) {
            // Payments taken before the cutover live in the archive.
            $links = $this->linksFrom($this->query("{$base}/paymentQueries/archivedPayments", [
                'transactionReference' => $identifier,
                'entityReference'      => $entity,
            ]));

            if ($links !== []) {
                return $links;
            }

            // Last resort. Worldpay reserve this for recovery, so it is only
            // used once the query endpoints have come up empty.
            $links = $this->linksFrom($this->query("{$base}/payments/events", [
                'transactionRef' => $identifier,
                'entity'         => $entity,
            ]));

            if ($links !== []) {
                return $links;
            }
        }

        return [];
    }

    private function query(string $url, array $params = []): ?Response
    {
        $attempts = [
            'application/vnd.worldpay.payment-queries-v1.hal+json',
            'application/json',
        ];

        foreach ($attempts as $accept) {
            $response = $this->client()
                ->withHeaders(['Accept' => $accept])
                ->get($url, $params);

            // A rejected Accept header is worth retrying; anything else is the answer.
            if (! in_array($response->status(), [406, 415], true)) {
                return $response->successful() ? $response : null;
            }
        }

        return null;
    }

    private function linksFrom(?Response $response): array
    {
        return $response ? (array) data_get($response->json(), '_links', []) : [];
    }

    // ---------------------------------------------------- Online Payments

    private function onlineAction(RefundRequest $request, string $action): GatewayResponse
    {
        $serviceKey = $this->config->credential('service_key');

        if (! $serviceKey) {
            throw GatewayException::misconfigured('Worldpay', 'a service key is required for Online Payments.');
        }

        $base     = 'https://api.worldpay.com/v1/orders/' . rawurlencode($request->gatewayTransactionId);
        $endpoint = $action === 'cancel' ? $base : "$base/refund";

        $http = Http::withHeaders([
            'Authorization' => $serviceKey,
            'Content-Type'  => 'application/json',
        ])
            ->acceptJson()
            ->timeout(config('payments.timeout', 30));

        $response = $action === 'cancel'
            ? $http->delete($endpoint)
            : $http->post($endpoint, [
                'refundAmount' => $request->amountMinor,
                'description'  => $request->reason ?? 'Merchant refund',
            ]);

        $body = $response->json() ?? [];

        if ($response->successful()) {
            return GatewayResponse::success(
                transactionId: data_get($body, 'orderCode', $request->gatewayTransactionId),
                raw: $body,
                status: 'succeeded',
            );
        }

        return GatewayResponse::failure(
            code: data_get($body, 'customCode', (string) $response->status()),
            message: data_get($body, 'message', 'Worldpay rejected the request.'),
            raw: $body ?: ['http_status' => $response->status(), 'body' => $response->body()],
        );
    }

    // ---------------------------------------------------------------- shared

    private function isAccess(): bool
    {
        return $this->config->credential('api_flavour', 'access') === 'access';
    }

    private function accessBaseUrl(): string
    {
        return $this->config->isSandbox()
            ? 'https://try.access.worldpay.com'
            : 'https://access.worldpay.com';
    }

    private function client(): PendingRequest
    {
        $username = $this->config->credential('username');
        $password = $this->config->credential('password');

        if (! $username || ! $password) {
            throw GatewayException::misconfigured('Worldpay', 'API username and password are required for Access.');
        }

        return Http::withBasicAuth($username, $password)
            ->timeout(config('payments.timeout', 30));
    }

    private function reference(RefundRequest $request): string
    {
        return substr('refund-' . ($request->orderReference ?? '') . '-' . $request->idempotencyKey, 0, 64);
    }
}
