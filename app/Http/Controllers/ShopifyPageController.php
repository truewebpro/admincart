<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\Page;
use App\Models\ShopifyShop;
use App\Services\ShopifyPageService;
use Illuminate\Http\Request;

class ShopifyPageController extends Controller
{
    /**
     * Live view — direct from Shopify, no local caching. Pages are
     * low-volume by nature, so a single bounded fetch (no cursor
     * pagination) is sufficient; see getPages()'s own docblock for the
     * accepted limitation on shops with unusually many pages.
     */
    public function live(Request $request, int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyPageService($shopifyShop);

        try {
            $pages = $service->getPages((int) $request->input('limit', 100));
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json(['success' => true, 'pages' => $pages]);
    }

    /**
     * Single, explicit create — no bulk, matching the requirement.
     * Re-fetches the specific page from Shopify by ID (authoritative,
     * not trusting whatever the browser already displayed), then
     * creates a REAL row in your pages table directly — unlike orders,
     * pages don't go through a staging/snapshot step first.
     */
    public function create(Request $request, int $shopId, string $pageId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyPageService($shopifyShop);

        try {
            $shopifyPage = $service->getPageById($pageId);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        if (empty($shopifyPage['handle'])) {
            return response()->json(['success' => false, 'message' => 'Invalid page data.'], 422);
        }

        // Match by thirdparty_id now (more robust than slug — slugs can
        // theoretically be edited, Shopify's page id never changes).
        $existing = Page::where('shop_id', $shopId)
            ->where('thirdparty_id', (string) $shopifyPage['id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This page has already been created.',
                'page_id' => $existing->page_id,
            ], 422);
        }

        $page = Page::create([
            'shop_id'          => $shopId,
            'thirdparty_id'    => (string) $shopifyPage['id'],
            'page_title'       => $shopifyPage['title'] ?? 'Untitled',
            'page_slug'        => $shopifyPage['handle'],
            'page_description' => $shopifyPage['body_html'] ?? null,
            'page_status'      => ! empty($shopifyPage['published_at']) ? 'active' : 'inactive',
            'meta_title'       => $shopifyPage['title'] ?? null,
            'meta_description' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created.',
            'page_id' => $page->page_id,
        ]);
    }

    /**
     * Syncs SEO title/description for already-created pages, from
     * Shopify's title_tag/description_tag metafields. Only updates
     * pages that have a thirdparty_id (i.e. were created via this
     * import flow) — pages created manually in your own admin have no
     * Shopify counterpart to sync from.
     */
    public function syncSeo(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyPageService($shopifyShop);

        $pages = Page::where('shop_id', $shopId)
            ->whereNotNull('thirdparty_id')
            ->get(['page_id', 'thirdparty_id','shop_id','page_slug']);

        if ($pages->isEmpty()) {
            return response()->json(['success' => true, 'updated' => 0]);
        }

        $shopifyIds = $pages->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();

        try {
            $seoData = $service->getPagesSeo($shopifyIds);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $updated = 0;

        foreach ($pages as $page) {
            $seo = $seoData[(int) $page->thirdparty_id] ?? null;

            if (! $seo) {
                continue;
            }

            $updates = [];
            if (! empty($seo['title'])) {
                $updates['meta_title'] = $seo['title'];
            }
            if (! empty($seo['description'])) {
                $updates['meta_description'] = $seo['description'];
            }

            if ($updates) {
                $page->update($updates);
                $updated++;
            }
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }
}
