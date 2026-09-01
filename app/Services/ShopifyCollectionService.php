<?php

namespace App\Services;

use App\Services\Concerns\InteractsWithShopifyApi;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyCollectionService
{
    use InteractsWithShopifyApi;

    protected array $requiredScopes = [
        'custom_collections' => 'read_products',
        'smart_collections'  => 'read_products',
    ];

    /**
     * Deliberately NOT cursor-paginated — collections are bounded to a
     * single call (up to 250, Shopify's own max), same reasoning as
     * pages/recent orders. A shop with more than 250 collections of one
     * type is an edge case not handled here.
     */
    public function getCustomCollections(int $limit = 250): array
    {
        $this->ensureScope('custom_collections');
        return $this->fetchList('custom_collections.json', 'custom_collections', $limit);
    }

    public function getSmartCollections(int $limit = 250): array
    {
        $this->ensureScope('smart_collections');
        return $this->fetchList('smart_collections.json', 'smart_collections', $limit);
    }

    protected function fetchList(string $endpoint, string $jsonKey, int $limit): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/{$endpoint}",
            ['limit' => min($limit, 250)]
        );

        if ($response->failed()) {
            throw new RuntimeException("Shopify {$jsonKey} request failed: " . $response->body());
        }

        return $response->json($jsonKey, []);
    }

    public function customCollectionsCount(): int
    {
        $this->ensureScope('custom_collections');
        return $this->fetchCount('custom_collections/count.json');
    }

    public function smartCollectionsCount(): int
    {
        $this->ensureScope('smart_collections');
        return $this->fetchCount('smart_collections/count.json');
    }

    /**
     * Fetch one specific collection by ID — used when a superadmin
     * clicks "Create". $type must be 'manual' (custom_collections) or
     * 'smart' (smart_collections), since Shopify uses different
     * endpoints for each and there's no unified "get collection by id"
     * across both.
     */
    public function getCollectionById(string $type, string $collectionId): array
    {
        $endpoint = $type === 'smart' ? 'smart_collections' : 'custom_collections';
        $this->ensureScope($type === 'smart' ? 'smart_collections' : 'custom_collections');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/{$endpoint}/{$collectionId}.json");

        if ($response->failed()) {
            throw new RuntimeException('Shopify collection fetch failed: ' . $response->body());
        }

        $jsonKey = $type === 'smart' ? 'smart_collection' : 'custom_collection';
        return $response->json($jsonKey, []);
    }

    /**
     * Batch SEO fetch, 100 per call, same pattern as pages/articles.
     * Confirmed Collection has no native `seo { title description }`
     * field either — reads title_tag/description_tag metafields
     * directly. GraphQL's Collection type is unified regardless of
     * custom vs. smart, so this works for both without needing to
     * know which type each ID is.
     *
     * @param  array<int>  $shopifyCollectionIds
     * @return array<int, array{title: ?string, description: ?string}>
     */
    public function getCollectionsSeo(array $shopifyCollectionIds): array
    {
        $this->ensureScope('custom_collections'); // either scope covers reading collections generally

        $token = $this->getAccessToken();
        $results = [];

        foreach (array_chunk($shopifyCollectionIds, 100) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/Collection/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = <<<GRAPHQL
            query {
              nodes(ids: [{$gidList}]) {
                ... on Collection {
                  id
                  metaTitle: metafield(namespace: "global", key: "title_tag") {
                    value
                  }
                  metaDescription: metafield(namespace: "global", key: "description_tag") {
                    value
                  }
                }
              }
            }
            GRAPHQL;

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type'           => 'application/json',
            ])->post(
                "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $query]
            );

            if ($response->failed()) {
                throw new RuntimeException('Shopify collection SEO GraphQL request failed: ' . $response->body());
            }

            foreach ($response->json('data.nodes', []) as $node) {
                if (! $node) {
                    continue;
                }

                $numericId = (int) basename($node['id']);

                $results[$numericId] = [
                    'title'       => $node['metaTitle']['value'] ?? null,
                    'description' => $node['metaDescription']['value'] ?? null,
                ];
            }
        }

        return $results;
    }

    public function getCollectionsProductCounts(array $shopifyCollectionIds): array
    {
        $this->ensureScope('custom_collections');

        $token = $this->getAccessToken();
        $results = [];

        foreach (array_chunk($shopifyCollectionIds, 100) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/Collection/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = <<<GRAPHQL
        query {
          nodes(ids: [{$gidList}]) {
            ... on Collection {
              id
              productsCount {
                count
              }
            }
          }
        }
        GRAPHQL;

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Content-Type'           => 'application/json',
            ])->post(
                "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/graphql.json",
                ['query' => $query]
            );

            if ($response->failed()) {
                throw new RuntimeException('Shopify collection product-count GraphQL request failed: ' . $response->body());
            }

            foreach ($response->json('data.nodes', []) as $node) {
                if (! $node) {
                    continue;
                }

                $numericId = (int) basename($node['id']);

                // Handles both {count: N} object shape AND a bare int,
                // whichever this API version actually returns.
                $raw = $node['productsCount'] ?? null;
                $results[$numericId] = is_array($raw) ? (int) ($raw['count'] ?? 0) : (int) ($raw ?? 0);
            }
        }

        return $results;
    }

}
