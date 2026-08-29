<?php

namespace App\Services;

use App\Services\Concerns\InteractsWithShopifyApi;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyBlogService
{
    use InteractsWithShopifyApi;

    protected array $requiredScopes = [
        'blogs'    => 'read_content',
        'articles' => 'read_content',
    ];

    /**
     * Blog containers (e.g. "News", "Guides") — deliberately not
     * paginated, since shops essentially never have more than a
     * handful of these.
     */
    public function getBlogs(): array
    {
        $this->ensureScope('blogs');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/blogs.json");

        if ($response->failed()) {
            throw new RuntimeException('Shopify blogs request failed: ' . $response->body());
        }

        return $response->json('blogs', []);
    }

    public function blogsCount(): int
    {
        $this->ensureScope('blogs');
        return count($this->getBlogs());
    }

    /**
     * Articles within ONE blog, bounded to $limit (default 250, which
     * is also Shopify's own per-call max) — deliberately NOT cursor-
     * paginated, matching the "reference-only, single bounded call"
     * pattern used for pages/recent orders. If a single blog genuinely
     * has more than $limit articles (rare), only the first $limit are
     * shown — an accepted limitation, not an oversight.
     */
    public function getArticles(int $blogId, int $limit = 250): array
    {
        $this->ensureScope('articles');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/blogs/{$blogId}/articles.json",
            ['limit' => min($limit, 250)]
        );

        if ($response->failed()) {
            throw new RuntimeException('Shopify articles request failed: ' . $response->body());
        }

        return $response->json('articles', []);
    }

    public function articlesCount(int $blogId): int
    {
        $this->ensureScope('articles');
        return $this->fetchCount("blogs/{$blogId}/articles/count.json");
    }

    /**
     * Fetch one specific article by ID — used when a superadmin
     * explicitly clicks "Create" on an article from the live view.
     * Re-fetches authoritatively rather than trusting client-relayed data.
     */
    public function getArticleById(int $blogId, string $articleId): array
    {
        $this->ensureScope('articles');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/blogs/{$blogId}/articles/{$articleId}.json");

        if ($response->failed()) {
            throw new RuntimeException('Shopify article fetch failed: ' . $response->body());
        }

        return $response->json('article', []);
    }

    /**
     * Batch SEO fetch for articles, 100 per call, same as
     * ShopifyPageService::getPagesSeo(). Article has no native
     * `seo { title description }` field either — confirmed this reads
     * the title_tag/description_tag metafields directly (namespace
     * "global"), same underlying storage as pages/products.
     *
     * @param  array<int>  $shopifyArticleIds
     * @return array<int, array{title: ?string, description: ?string}>
     */
    public function getArticlesSeo(array $shopifyArticleIds): array
    {
        $this->ensureScope('articles');

        $token = $this->getAccessToken();
        $results = [];

        foreach (array_chunk($shopifyArticleIds, 100) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/Article/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = <<<GRAPHQL
            query {
              nodes(ids: [{$gidList}]) {
                ... on Article {
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
                throw new RuntimeException('Shopify article SEO GraphQL request failed: ' . $response->body());
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
