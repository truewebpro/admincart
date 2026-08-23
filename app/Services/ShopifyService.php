<?php

namespace App\Services;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\ShopifyShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use RuntimeException;

class ShopifyService
{
    protected string $apiVersion = '2026-07';

    public function __construct(protected ShopifyShop $shop)
    {
    }

    /**
     * Returns a valid access token, fetching a fresh one if expired.
     */
    public function getAccessToken(): string
    {
        if (! $this->shop->isTokenExpired()) {
            return $this->shop->access_token;
        }

        return $this->connect();
    }

    /**
     * Fetches a fresh access token from Shopify and saves it (plus the
     * granted scope) on the shop record. Called automatically when the
     * cached token is expired, but can also be called directly right
     * after a superadmin saves new credentials, so the connection is
     * verified immediately instead of waiting for the first import run.
     */
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
            // Shopify tokens last 24h (86400s); refresh a bit early to be safe
            'token_expires_at' => Carbon::now()->addSeconds(($data['expires_in'] ?? 86400) - 60),
        ]);

        return $this->shop->access_token;
    }

    /**
     * Example: fetch products from the Admin API (REST).
     */
    public function getProducts(int $limit = 50): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/products.json",
            ['limit' => $limit]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify products request failed: ' . $response->body()
            );
        }

        return $response->json('products', []);
    }

    public function importProductsSinceId(callable $onBatch, int $limit = 250): void
    {
        $this->ensureScope('products');

        $token = $this->getAccessToken();
        $sinceId = 0;

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get(
                "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/products.json",
                ['limit' => $limit, 'since_id' => $sinceId]
            );

            if ($response->failed()) {
                throw new RuntimeException(
                    'Shopify products request failed: ' . $response->body()
                );
            }

            $products = $response->json('products', []);
            $count = count($products);

            if ($count > 0) {
                $onBatch($products);
                $sinceId = end($products)['id'];
            }

            // Explicitly release this batch before requesting the next
            // one so memory doesn't creep up over thousands of products.
            unset($products, $response);
            gc_collect_cycles();

        } while ($count === $limit);
    }

    public function getCustomCollections(int $limit = 50): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/custom_collections.json",
            ['limit' => $limit]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify products request failed: ' . $response->body()
            );
        }

        return $response->json('custom_collections', []);
    }

    public function getSmartCollections(int $limit = 250): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/smart_collections.json",
            ['limit' => $limit]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify products request failed: ' . $response->body()
            );
        }

        return $response->json('smart_collections', []);
    }


    /**
     * Fetch ALL products (REST), 250 per page, following Shopify's
     * cursor-based Link header pagination until there's no next page.
     *
     * @param  callable|null  $onPage  Optional callback invoked with each
     *                                 page's product array, e.g. for streaming
     *                                 import instead of holding everything in memory.
     */
    public function getAllProducts(?callable $onPage = null): array
    {
        $this->ensureScope('products');

        $token = $this->getAccessToken();
        $all = [];

        $url = "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/products.json";
        $query = ['limit' => 250];

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get($url, $query);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Shopify products request failed: ' . $response->body()
                );
            }

            $products = $response->json('products', []);

            if ($onPage) {
                $onPage($products);
            } else {
                $all = array_merge($all, $products);
            }

            // Parse the Link header for the "next" page cursor.
            // Format: <https://...&page_info=xyz>; rel="next", <...>; rel="previous"
            $url = null;
            $query = []; // params are baked into the next URL already

            $linkHeader = $response->header('Link');

            if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $url = $matches[1];
            }
        } while ($url);

        return $all;
    }

    /**
     * Fetch all blogs (a store can have multiple, e.g. "News", "Guides").
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

    public function getArticles(int $limit = 100): array
    {
        $this->ensureScope('articles');

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/articles.json",
            ['limit' => $limit]);

        if ($response->failed()) {
            throw new RuntimeException('Shopify blogs request failed: ' . $response->body());
        }

        return $response->json('articles', []);
    }

    public function getOrders(int $limit = 100): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/orders.json",
            ['limit' => $limit,'status'=>'any']
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify orders request failed: ' . $response->body()
            );
        }

        return $response->json('orders', []);
    }

    /**
     * Fetch ALL articles for a given blog, 250 per page, same cursor
     * pagination pattern as getAllProducts().
     */
    public function getAllArticles(int $blogId, ?callable $onPage = null): array
    {
        $this->ensureScope('articles');

        $token = $this->getAccessToken();
        $all = [];

        $url = "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/blogs/{$blogId}/articles.json";
        $query = ['limit' => 250];

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get($url, $query);

            if ($response->failed()) {
                throw new RuntimeException('Shopify articles request failed: ' . $response->body());
            }

            $articles = $response->json('articles', []);

            if ($onPage) {
                $onPage($articles);
            } else {
                $all = array_merge($all, $articles);
            }

            $url = null;
            $query = [];
            $linkHeader = $response->header('Link');

            if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $url = $matches[1];
            }
        } while ($url);

        return $all;
    }

    /**
     * Maps each importable resource to the Admin API scope required to
     * read it. Used to check access before making the request.
     */
    protected array $requiredScopes = [
        'products'           => 'read_products',
        'custom_collections' => 'read_products',
        'smart_collections'  => 'read_products',
        'pages'              => 'read_content',
        'blogs'              => 'read_content',
        'articles'           => 'read_content',
        'customers'          => 'read_customers',
        'orders'          => 'read_orders',
    ];

    /**
     * Fetch counts for all importable resource types the connection has
     * scope for. Resources without the required scope are reported as
     * unavailable instead of throwing, so a confirmation screen can show
     * "Customers: not authorized" rather than failing the whole request.
     */
    public function getImportCounts(): array
    {
        $counts = [];

        foreach (['products', 'custom_collections', 'smart_collections', 'pages', 'blogs','articles','customers','orders'] as $resource) {
            if (! $this->shop->hasScope($this->requiredScopes[$resource])) {
                $counts[$resource] = [
                    'count'     => null,
                    'available' => false,
                    'reason'    => "Missing scope: {$this->requiredScopes[$resource]}",
                ];
                continue;
            }

            $counts[$resource] = [
                'count'     => match ($resource) {
                    'products'           => $this->productsCount(),
                    'custom_collections' => $this->customCollectionsCount(),
                    'smart_collections'  => $this->smartCollectionsCount(),
                    'pages'              => $this->pagesCount(),
                    'blogs'              => $this->blogsCount(),
                    'articles'           => $this->articlesCount(),
                    'customers'           => $this->customersCount(),
                    'orders'              => $this->ordersCount(),
                },
                'available' => true,
                'reason'    => null,
            ];
        }

        return $counts;
    }

    /**
     * Compares granted scopes against everything this integration needs.
     * Since this app uses the client_credentials grant (own-org app on
     * an own-org store), there's no separate merchant consent step —
     * if scopes are missing, updating the app's Configuration in the
     * Dev Dashboard and calling connect() again is enough to pick up
     * the new scopes on the next token response.
     */
    public function missingScopes(): array
    {
        $required = array_unique(array_values($this->requiredScopes));

        return array_values(array_filter(
            $required,
            fn ($scope) => ! $this->shop->hasScope($scope)
        ));
    }

    protected function ensureScope(string $resource): void
    {
        $scope = $this->requiredScopes[$resource] ?? null;

        if ($scope && ! $this->shop->hasScope($scope)) {
            throw new MissingShopifyScopeException($scope, $resource);
        }
    }

    public function productsCount(): int
    {
        $this->ensureScope('products');
        return $this->fetchCount('products/count.json');
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

    public function pagesCount(): int
    {
        $this->ensureScope('pages');
        return $this->fetchCount('pages/count.json');
    }

    public function blogsCount(): int
    {
        $this->ensureScope('blogs');
        return count($this->getBlogs());
    }

    public function articlesCount(): int
    {
        $this->ensureScope('articles');
        return count($this->getArticles());
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

    /**
     * Starts an async bulk export of ALL customers (with addresses and
     * tags) via Shopify's Bulk Operations API. Runs server-side on
     * Shopify's infrastructure — no rate-limit cost to your app at all.
     * Returns the bulk operation's id immediately; the actual export
     * runs in the background.
     */
    public function startCustomersBulkExport(): string
    {
        $this->ensureScope('customers');

        $token = $this->getAccessToken();

        $bulkQuery = <<<'GRAPHQL'
        {
          customers {
            edges {
              node {
                id
                email
                firstName
                lastName
                phone
                state
                tags
                createdAt
                addresses {
                  id
                  firstName
                  lastName
                  address1
                  address2
                  city
                  zip
                  countryCodeV2
                  phone
                }
                defaultAddress {
                  id
                }
              }
            }
          }
        }
        GRAPHQL;

        $mutation = <<<GRAPHQL
        mutation {
          bulkOperationRunQuery(query: {$this->graphqlStringLiteral($bulkQuery)}) {
            bulkOperation { id status }
            userErrors { field message }
          }
        }
        GRAPHQL;

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->post(
            "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/graphql.json",
            ['query' => $mutation]
        );

        if ($response->failed()) {
            throw new RuntimeException('Failed to start bulk export: ' . $response->body());
        }

        $errors = $response->json('data.bulkOperationRunQuery.userErrors', []);
        if (! empty($errors)) {
            throw new RuntimeException('Bulk export rejected: ' . json_encode($errors));
        }

        return $response->json('data.bulkOperationRunQuery.bulkOperation.id');
    }

    /**
     * Checks the status of the most recent bulk QUERY operation for this
     * app+shop. Poll this every few seconds until status is COMPLETED
     * (or FAILED/CANCELED).
     *
     * @return array{status: string, url: ?string, errorCode: ?string}
     */
    public function checkBulkOperationStatus(): array
    {
        $token = $this->getAccessToken();

        $query = <<<'GRAPHQL'
        query {
          currentBulkOperation(type: QUERY) {
            id
            status
            errorCode
            objectCount
            url
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
            throw new RuntimeException('Failed to check bulk operation status: ' . $response->body());
        }

        return $response->json('data.currentBulkOperation', []);
    }

    /**
     * Downloads and parses the JSONL result file from a completed bulk
     * operation, yielding one decoded customer array at a time — memory
     * safe even for very large exports, same principle as the since_id
     * batching but even cheaper since there's no per-page HTTP round trip.
     */
    public function streamBulkExportResults(string $url, callable $onCustomer): void
    {
        $stream = fopen($url, 'r');

        if (! $stream) {
            throw new RuntimeException("Failed to open bulk export result stream: {$url}");
        }

        while (($line = fgets($stream)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $onCustomer(json_decode($line, true));
        }

        fclose($stream);
    }

    protected function graphqlStringLiteral(string $value): string
    {
        return json_encode($value);
    }

    public function getProductsSeo(array $shopifyProductIds): array
    {
        $this->ensureScope('products');

        $token = $this->getAccessToken();
        $results = [];

        foreach (array_chunk($shopifyProductIds, 100) as $chunk) {
            $gids = array_map(fn ($id) => "gid://shopify/Product/{$id}", $chunk);
            $gidList = implode(',', array_map(fn ($gid) => "\"{$gid}\"", $gids));

            $query = <<<GRAPHQL
            query {
              nodes(ids: [{$gidList}]) {
                ... on Product {
                  id
                  seo {
                    title
                    description
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
                throw new RuntimeException('Shopify SEO GraphQL request failed: ' . $response->body());
            }

            foreach ($response->json('data.nodes', []) as $node) {
                if (! $node) {
                    continue; // product no longer exists / was deleted on Shopify
                }

                $numericId = (int) basename($node['id']); // "gid://shopify/Product/123" -> 123

                $results[$numericId] = [
                    'title'       => $node['seo']['title'] ?? null,
                    'description' => $node['seo']['description'] ?? null,
                ];
            }
        }

        return $results;
    }

    public function importCustomersSinceId(callable $onBatch, int $limit = 250, int $startingSinceId = 0): void
    {
        $this->ensureScope('customers');

        $token = $this->getAccessToken();
        $sinceId = $startingSinceId;

        do {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get(
                "https://{$this->shop->shop_domain}/admin/api/{$this->apiVersion}/customers.json",
                ['limit' => $limit, 'since_id' => $sinceId]
            );

            if ($response->failed()) {
                throw new RuntimeException(
                    'Shopify customers request failed: ' . $response->body()
                );
            }

            $customers = $response->json('customers', []);
            $count = count($customers);

            if ($count > 0) {
                $onBatch($customers);
                $sinceId = end($customers)['id'];
            }

            unset($customers, $response);
            gc_collect_cycles();

        } while ($count === $limit);
    }

    public function customersCount(): int
    {
        $this->ensureScope('customers');
        return $this->fetchCount('customers/count.json');
    }

    public function ordersCount(): int
    {
        $this->ensureScope('orders');
        return $this->fetchCount('orders/count.json?status=any');
    }


    public function getProductsGraphQl(int $first = 50): array
    {
        $token = $this->getAccessToken();

        $query = <<<'GRAPHQL'
        query ($first: Int!) {
          products(first: $first) {
            edges {
              node {
                id
                title
                handle
                status
                variants(first: 5) {
                  edges {
                    node {
                      id
                      price
                      sku
                    }
                  }
                }
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
            [
                'query'     => $query,
                'variables' => ['first' => $first],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Shopify GraphQL request failed: ' . $response->body()
            );
        }

        return $response->json('data.products.edges', []);
    }
}
