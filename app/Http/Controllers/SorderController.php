<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\ShopifyShop;
use App\Models\Sorder;
use App\Services\ShopifyService;
use Illuminate\Http\Request;

class SorderController extends Controller
{
    /**
     * Refresh the last 30 days of orders from Shopify, capped at 250
     * (Shopify's own per-call max). Reference-only — these never become
     * real Order/OrderItem records, this table exists purely so the
     * client can browse a recent Shopify order snapshot inside the
     * admin without hitting Shopify's API on every page view.
     *
     * Since this is a bounded, repeatedly-refreshed snapshot (not a
     * historical import), old rows outside the fetched set are cleared
     * first — this always reflects "the last 30 days, right now," not
     * an ever-growing accumulation.
     */
    public function sync(Request $request, int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $days = (int) $request->input('days', 30);
        $limit = (int) $request->input('limit', 250);

        $orders = $service->getRecentOrders($days, $limit);

        $seenIds = [];

        foreach ($orders as $o) {
            $thirdpartyId = (string) $o['id'];
            $seenIds[] = $thirdpartyId;

            Sorder::updateOrCreate(
                ['shop_id' => $shopId, 'thirdparty_id' => $thirdpartyId],
                [
                    'order_number'           => $o['order_number'] ?? $o['name'] ?? null,
                    'email'                  => $o['email'] ?? null,
                    'financial_status'       => $o['financial_status'] ?? null,
                    'fulfillment_status'     => $o['fulfillment_status'] ?? null,
                    'subtotal_price'         => $o['subtotal_price'] ?? 0,
                    'total_discounts'        => $o['total_discounts'] ?? 0,
                    'total_tax'              => $o['total_tax'] ?? 0,
                    'total_shipping'         => $o['total_shipping_price_set']['shop_money']['amount'] ?? 0,
                    'total_price'            => $o['total_price'] ?? 0,
                    'currency'               => $o['currency'] ?? null,
                    'customer_thirdparty_id' => isset($o['customer']['id']) ? (string) $o['customer']['id'] : null,
                    'customer_name'          => trim(($o['customer']['first_name'] ?? '') . ' ' . ($o['customer']['last_name'] ?? '')) ?: ($o['email'] ?? 'Guest'),
                    'line_items'             => $o['line_items'] ?? [],
                    'shipping_address'       => $o['shipping_address'] ?? null,
                    'shopify_created_at'     => $o['created_at'] ?? null,
                ]
            );
        }

        // Drop anything for this shop that's now outside the fetched
        // window — keeps the table an accurate reflection of "last N
        // days" rather than accumulating orders forever.
        $deleted = Sorder::where('shop_id', $shopId)
            ->where('is_pinned', false) // never auto-delete manually-pinned orders, regardless of whether they're in the current 30-day fetch
            ->whereNotIn('thirdparty_id', $seenIds ?: ['__none__'])
            ->delete();

        return response()->json([
            'success' => true,
            'synced'  => count($orders),
            'removed_stale' => $deleted,
        ]);
    }

    /**
     * Vuetify table data — local only, real page-number pagination.
     * Given the dataset is capped at 250 rows by design, this is
     * cheap regardless of page size chosen.
     */
    public function index(Request $request, int $shopId)
    {
        $query = Sorder::where('shop_id', $shopId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('financial_status')) {
            $query->where('financial_status', $status);
        }

        $allowedSorts = ['id', 'order_number', 'email', 'total_price', 'financial_status', 'shopify_created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts, true) ? $request->sort_by : 'shopify_created_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 50);
        $page = $query->paginate($perPage);

        return response()->json([
            'items' => $page,
            'total' => Sorder::where('shop_id', $shopId)->count(),
        ]);
    }

    /**
     * Live, real-time order browsing — no caching, no sync step, hits
     * Shopify directly on every call. Next/Previous only, since that's
     * all Shopify's cursor pagination supports (no page-number jumping
     * possible against a live source). Selection of specific orders
     * (e.g. checkboxes in the UI) is purely client-side state over
     * whatever page is currently displayed — this endpoint doesn't
     * need to know about selection at all.
     */
    public function live(Request $request, int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $pageInfo = $request->input('page_info');
        $limit = (int) $request->input('limit', 50);

        try {
            $result = $service->getOrdersPage($pageInfo, $limit);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shopify request failed: ' . $e->getMessage(),
            ], 502);
        }


        return response()->json([
            'success' => true,
            'orders' => $result['orders'],
            'next_page_info' => $result['next_page_info'],
            'previous_page_info' => $result['previous_page_info'],
        ]);
    }

    /**
     * Client explicitly picks ONE order from the live view to save into
     * the sorders reference snapshot. Re-fetches from Shopify by ID
     * first, so the saved snapshot reflects the order's current state,
     * not stale data the browser happened to have.
     *
     * This does NOT touch the real orders/order_items tables — sorders
     * is purely a reference cache, by design (see earlier discussion).
     */
    public function createOrder(Request $request, int $shopId, string $orderId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $shopifyOrder = $service->getOrderById($orderId);
        $result = $this->saveToSnapshot($shopifyOrder, $shopId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Bulk version — capped at 10 per request. Same snapshot-only
     * behavior as createOrder(), just for a batch.
     */
    public function bulkCreateOrders(Request $request, int $shopId)
    {
        $validated = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1', 'max:10'],
            'order_ids.*' => ['required', 'string'],
        ]);

        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $results = [];

        foreach ($validated['order_ids'] as $orderId) {
            try {
                $shopifyOrder = $service->getOrderById($orderId);
                $results[] = array_merge(
                    ['shopify_order_id' => $orderId],
                    $this->saveToSnapshot($shopifyOrder, $shopId)
                );
            } catch (\Throwable $e) {
                $results[] = [
                    'shopify_order_id' => $orderId,
                    'success' => false,
                    'message' => 'Failed to fetch or save this order: ' . $e->getMessage(),
                ];
            }
        }

        $successCount = count(array_filter($results, fn ($r) => $r['success']));

        return response()->json([
            'success' => true,
            'created' => $successCount,
            'failed' => count($results) - $successCount,
            'results' => $results,
        ]);
    }

    /**
     * Upserts a single Shopify order into the sorders snapshot table —
     * same field mapping as sync(), just triggered for one specific
     * order picked from the live view rather than the automatic 30-day
     * batch. This is a REFERENCE SAVE ONLY. It never creates a row in
     * orders/order_items — this system does not import Shopify orders
     * into the real order-management tables.
     */
    protected function saveToSnapshot(array $o, int $shopId): array
    {
        if (empty($o['id'])) {
            return ['success' => false, 'message' => 'Invalid order data.'];
        }

        $sorder = Sorder::updateOrCreate(
            ['shop_id' => $shopId, 'thirdparty_id' => (string) $o['id']],
            [
                'is_pinned'              => true, // manually saved from the live view — protected from sync()'s 30-day cleanup
                'order_number'           => $o['order_number'] ?? $o['name'] ?? null,
                'email'                  => $o['email'] ?? null,
                'financial_status'       => $o['financial_status'] ?? null,
                'fulfillment_status'     => $o['fulfillment_status'] ?? null,
                'subtotal_price'         => $o['subtotal_price'] ?? 0,
                'total_discounts'        => $o['total_discounts'] ?? 0,
                'total_tax'              => $o['total_tax'] ?? 0,
                'total_shipping'         => $o['total_shipping_price_set']['shop_money']['amount'] ?? 0,
                'total_price'            => $o['total_price'] ?? 0,
                'currency'               => $o['currency'] ?? null,
                'customer_thirdparty_id' => isset($o['customer']['id']) ? (string) $o['customer']['id'] : null,
                'customer_name'          => trim(($o['customer']['first_name'] ?? '') . ' ' . ($o['customer']['last_name'] ?? '')) ?: ($o['email'] ?? 'Guest'),
                'line_items'             => $o['line_items'] ?? [],
                'shipping_address'       => $o['shipping_address'] ?? null,
                'shopify_created_at'     => $o['created_at'] ?? null,
            ]
        );

        return [
            'success' => true,
            'message' => 'Order saved to reference snapshot.',
            'sorder_id' => $sorder->id,
        ];
    }
}
