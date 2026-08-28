<?php

namespace App\Services;

use App\Services\Concerns\InteractsWithShopifyApi;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyPageService
{
    use InteractsWithShopifyApi;

    protected array $requiredScopes = [
        'pages' => 'read_content',
    ];

    /**
     * Deliberately NOT cursor-paginated. Shop pages are low-volume by
     * nature (a handful to a few dozen, rarely more), so a single
     * bounded call is sufficient. If a shop genuinely has more than
     * $limit pages (rare), only the first $limit are returned — a
     * known, accepted limitation given the use case.
     */
    public function getPages(int $limit = 100): array
    {
        $this->ensureScope('pages');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/pages.json",
            ['limit' => min($limit, 250)]
        );

        if ($response->failed()) {
            throw new RuntimeException('Shopify pages request failed: ' . $response->body());
        }

        return $response->json('pages', []);
    }

    /**
     * Fetch one specific page by ID — used when a superadmin explicitly
     * clicks "Create" on a page from the live view. Re-fetches
     * authoritatively rather than trusting client-relayed data.
     */
    public function getPageById(string $pageId): array
    {
        $this->ensureScope('pages');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/pages/{$pageId}.json");

        if ($response->failed()) {
            throw new RuntimeException('Shopify page fetch failed: ' . $response->body());
        }

        return $response->json('page', []);
    }

    public function pagesCount(): int
    {
        $this->ensureScope('pages');
        return $this->fetchCount('pages/count.json');
    }

    /**
     * Batch SEO fetch, 100 per call. IMPORTANT: unlike Product, Page has
     * no native `seo { title description }` GraphQL field — this reads
     * the title_tag/description_tag metafields directly, which is the
     * same underlying storage Shopify uses, just without the Product
     * type's convenience shortcut.
     *
     * @param  array<int>  $shopifyPageIds
     * @return array<int, array{title: ?string, description: ?string}>
     */
    public function getPagesSeo(array $shopifyPageIds): array
    {
        $this->ensureScope('pages');

        $token = $this->getAccessToken();
        $results = [];

        foreach (array_chunk($shopifyPageIds, 100) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/Page/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = <<<GRAPHQL
            query {
              nodes(ids: [{$gidList}]) {
                ... on Page {
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
                throw new RuntimeException('Shopify page SEO GraphQL request failed: ' . $response->body());
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
}
