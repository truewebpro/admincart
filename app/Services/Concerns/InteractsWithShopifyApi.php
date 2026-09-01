<?php

namespace App\Services\Concerns;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\ShopifyShop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Shared by every Shopify*Service class (ShopifyService, ShopifyPageService,
 * and any future ones — ShopifyCollectionService, etc.). Handles token
 * fetch/refresh, scope checking, and the couple of small request helpers
 * every resource service needs, so none of that gets reimplemented
 * per-resource as this grows.
 */
trait InteractsWithShopifyApi
{
    protected string $apiVersion = '2026-07';

    public function __construct(protected ShopifyShop $shop)
    {
    }

    public function getAccessToken(): string
    {
        if (! $this->shop->isTokenExpired()) {
            return $this->shop->access_token;
        }

        return $this->connect();
    }

    public function connect(): string
    {
        $response = Http::asForm()->post(
            "https://{$this->shop->shop_domain}/admin/oauth/access_token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->shop->client_id,
                'client_secret' => $this->shop->client_secret,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify token request failed: ' . $response->body()
            );
        }

        $data = $response->json();

        $this->shop->update([
            'access_token'     => $data['access_token'],
            'scope'            => $data['scope'] ?? null,
            'token_expires_at' => Carbon::now()->addSeconds(($data['expires_in'] ?? 86400) - 60),
        ]);

        return $this->shop->access_token;
    }

    /**
     * Throws if the connection doesn't have the given scope. Each
     * using class defines its own $requiredScopes map (only the
     * resources IT deals with — a page service doesn't need to know
     * about the customers scope, for example).
     */
    protected function ensureScope(string $resource): void
    {
        $scope = $this->requiredScopes[$resource] ?? null;

        if ($scope && ! $this->shop->hasScope($scope)) {
            throw new MissingShopifyScopeException($scope, $resource);
        }
    }

    public function missingScopes(): array
    {
        $required = array_unique(array_values($this->requiredScopes));

        return array_values(array_filter(
            $required,
            fn ($scope) => ! $this->shop->hasScope($scope)
        ));
    }

    /**
     * Shared helper for the *_count.json endpoints — all of them return
     * { "count": N } in the same shape.
     */
    protected function fetchCount(string $endpoint): int
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/{$endpoint}");

        if ($response->failed()) {
            throw new RuntimeException(
                "Shopify count request failed ({$endpoint}): " . $response->body()
            );
        }

        return $response->json('count', 0);
    }

    protected function graphqlStringLiteral(string $value): string
    {
        return json_encode($value);
    }

    protected function batchFetchNodes(string $gidType, array $ids, string $fieldsFragment, int $chunkSize = 100, int $maxRetries = 3): array
    {
        $token = $this->getAccessToken();
        $allNodes = [];

        // Filter invalid ids FIRST — this is the fix for bug #1. Without
        // this, one bad id anywhere in a 100-id chunk fails the whole chunk.
        $validIds = array_values(array_filter(
            $ids,
            fn ($id) => is_numeric($id) && (int) $id > 0
        ));

        foreach (array_chunk($validIds, $chunkSize) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/{$gidType}/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = "query { nodes(ids: [{$gidList}]) { {$fieldsFragment} } }";

            $attempt = 0;

            while (true) {
                $attempt++;

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                    'Content-Type'           => 'application/json',
                ])->post(
                    "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/graphql.json",
                    ['query' => $query]
                );

                $body = $response->json();

                // Fix for bug #2: check the response BODY for GraphQL-level
                // errors, not just the HTTP status.
                if (! empty($body['errors'])) {
                    $isThrottled = collect($body['errors'])->contains(
                        fn ($e) => str_contains(strtolower($e['message'] ?? ''), 'throttle')
                            || str_contains(strtolower($e['message'] ?? ''), 'cost')
                    );

                    if ($isThrottled && $attempt <= $maxRetries) {
                        sleep($attempt * 2); // simple linear backoff: 2s, 4s, 6s
                        continue;
                    }

                    throw new RuntimeException(
                        'Shopify GraphQL request failed: ' . json_encode($body['errors'])
                    );
                }

                if ($response->failed()) {
                    throw new RuntimeException('Shopify GraphQL request failed: ' . $response->body());
                }

                foreach (($body['data']['nodes'] ?? []) as $node) {
                    if ($node) { // still correctly skip genuinely deleted/missing resources
                        $allNodes[] = $node;
                    }
                }

                break; // success — exit retry loop, move to next chunk
            }
        }

        return $allNodes;
    }

}
