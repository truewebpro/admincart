<?php

namespace App\Services;

use App\Services\Concerns\InteractsWithShopifyApi;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ShopifyFileService
{
    use InteractsWithShopifyApi;

    protected array $requiredScopes = [
        'files' => 'read_files',
    ];

    protected const FIELDS = <<<'GRAPHQL'
    id
    ... on File {
      createdAt
      fileStatus
    }
    ... on GenericFile {
      url
      mimeType
      originalFileSize
      alt
    }
    ... on MediaImage {
      image {
        url
        width
        height
      }
      alt
      mimeType
    }
    GRAPHQL;


    /**
     * Live, cursor-paginated browsing — GraphQL relay-style pagination,
     * genuinely different from the Link-header cursor style used for
     * orders. Forward uses first/after, backward uses last/before —
     * both required by GraphQL's connection spec, not optional.
     */
    public function getFiles(?string $cursor = null, string $direction = 'next', bool $onlyUnused = true, int $limit = 20): array
    {
        $this->ensureScope('files');

        $token = $this->getAccessToken();

        if ($direction === 'previous' && $cursor) {
            $paginationArgs = "last: {$limit}, before: " . json_encode($cursor);
        } elseif ($cursor) {
            $paginationArgs = "first: {$limit}, after: " . json_encode($cursor);
        } else {
            $paginationArgs = "first: {$limit}";
        }

        $queryFilter = $onlyUnused ? ', query: "used_in:none"' : '';
        $fields = self::FIELDS;

        $query = <<<GRAPHQL
        query {
          files({$paginationArgs}{$queryFilter}) {
            edges {
              node {
                {$fields}
              }
            }
            pageInfo {
              hasNextPage
              hasPreviousPage
              endCursor
              startCursor
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
            throw new RuntimeException('Shopify files request failed: ' . $response->body());
        }

        $body = $response->json();

        if (! empty($body['errors'])) {
            throw new RuntimeException('Shopify files GraphQL error: ' . json_encode($body['errors']));
        }

        $edges = $body['data']['files']['edges'] ?? [];

        return [
            'files'     => array_map(fn ($edge) => $edge['node'], $edges),
            'page_info' => $body['data']['files']['pageInfo'] ?? [],
        ];
    }

    /**
     * Re-fetch one specific file by its full GID before importing —
     * authoritative, not trusting whatever the browser already
     * displayed, same principle as every other "Create" action this session.
     */
    public function getFileById(string $gid): array
    {
        $this->ensureScope('files');

        $token = $this->getAccessToken();
        $fields = self::FIELDS;
        $gidLiteral = json_encode($gid);

        $query = <<<GRAPHQL
        query {
          node(id: {$gidLiteral}) {
            {$fields}
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
            throw new RuntimeException('Shopify file fetch failed: ' . $response->body());
        }

        $body = $response->json();

        if (! empty($body['errors'])) {
            throw new RuntimeException('Shopify file GraphQL error: ' . json_encode($body['errors']));
        }

        return $body['data']['node'] ?? [];
    }
}
