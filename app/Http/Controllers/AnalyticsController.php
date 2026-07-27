<?php

namespace App\Http\Controllers;

use App\Models\Acart;
use App\Models\AcartEvent;
use App\Models\ActiveVisitor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PageView;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Resolves ?from=YYYY-MM-DD&to=YYYY-MM-DD from the request, defaulting
     * to the last 30 days. Shared by every method below so every chart/stat
     * on the dashboard respects the same selected date range.
     */
    protected function resolveDateRange(Request $request): array
    {
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }

    /**
     * Top-line stats: revenue, order count, average order value — plus
     * percentage change vs. the immediately preceding period of equal
     * length, so the dashboard can show "+12% vs last period" style deltas.
     */
    public function overview(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $periodLength = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($periodLength);

        $current = $this->periodStats($shopId, $from, $to);
        $previous = $this->periodStats($shopId, $prevFrom, $prevTo);

        return response()->json([
            'success' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'current' => $current,
            'previous' => $previous,
            'change' => [
                'revenue' => $this->percentChange($previous['revenue'], $current['revenue']),
                'orders' => $this->percentChange($previous['order_count'], $current['order_count']),
                'aov' => $this->percentChange($previous['avg_order_value'], $current['avg_order_value']),
            ],
        ]);
    }

    protected function periodStats(int $shopId, Carbon $from, Carbon $to): array
    {
        // "Revenue" counts paid orders only — pending/failed payments
        // aren't real revenue yet, and cancelled/refunded orders shouldn't
        // count regardless of payment_status.
        $row = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as revenue')
            ->first();

        $orderCount = (int) $row->order_count;
        $revenue = (float) $row->revenue;

        return [
            'revenue' => round($revenue, 2),
            'order_count' => $orderCount,
            'avg_order_value' => $orderCount > 0 ? round($revenue / $orderCount, 2) : 0,
        ];
    }

    protected function percentChange(float $previous, float $current): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Day-by-day revenue + order count for charting. Fills in zero-value
     * days that had no orders, so the chart doesn't show gaps.
     */
    public function salesTrend(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $rows = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $series = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dateKey = $cursor->toDateString();
            $row = $rows->get($dateKey);

            $series[] = [
                'date' => $dateKey,
                'revenue' => $row ? round((float) $row->revenue, 2) : 0,
                'order_count' => $row ? (int) $row->order_count : 0,
            ];

            $cursor->addDay();
        }

        return response()->json(['success' => true, 'series' => $series]);
    }

    /**
     * Top products by revenue within the date range, joined through
     * order_items -> orders to respect shop_id and the same paid/not-
     * cancelled filter as everything else.
     */
    public function topProducts(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        $limit = min((int) $request->input('limit', 10), 50);
        $sortBy = $request->input('sort_by', 'revenue') === 'quantity' ? 'total_quantity' : 'total_revenue';

        $products = OrderItem::query()
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.order_status', ['cancelled', 'refunded'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->groupBy('order_items.product_id', 'order_items.title')
            ->selectRaw('
                order_items.product_id,
                order_items.title,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.total) as total_revenue,
                COUNT(DISTINCT order_items.order_id) as order_count
            ')
            ->orderByDesc($sortBy)
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'products' => $products]);
    }

    /**
     * Generic groupBy breakdown — covers order_status, payment_method,
     * and shipping_method with one method rather than three near-
     * duplicates, since they're all "count + revenue grouped by column X".
     *
     * Usage: /analytics/breakdown?by=order_status
     *        /analytics/breakdown?by=payment_method
     *        /analytics/breakdown?by=shipping_method
     */
    public function breakdown(Request $request)
    {
        $allowedColumns = ['order_status', 'payment_method', 'shipping_method', 'fulfillment_status'];
        $by = $request->input('by');

        if (! in_array($by, $allowedColumns, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid breakdown column'], 422);
        }

        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        // Note: this one does NOT filter by payment_status='paid', unlike
        // the revenue-focused endpoints above — a status/payment-method
        // breakdown needs to show pending/failed orders too, otherwise
        // "order_status breakdown" would never show pending orders at all.
        $rows = Order::where('shop_id', $shopId)
            ->whereBetween('placed_at', [$from, $to])
            ->select($by)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as revenue')
            ->groupBy($by)
            ->orderByDesc('order_count')
            ->get();

        return response()->json(['success' => true, 'by' => $by, 'breakdown' => $rows]);
    }

    /**
     * New vs. returning customers within the period, based on whether
     * their FIRST ever order (for this shop) falls inside the selected
     * range or predates it.
     */
    public function customerSplit(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        // First order date per customer, for this shop, across ALL time
        // (not just the selected range) — needed to know if a customer
        // placing an order in-range is actually new or returning.
        $firstOrderDates = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->selectRaw('customer_id, MIN(placed_at) as first_order_at')
            ->groupBy('customer_id');

        $customersInRange = Order::where('orders.shop_id', $shopId)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.order_status', ['cancelled', 'refunded'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->joinSub($firstOrderDates, 'first_orders', function ($join) {
                $join->on('orders.customer_id', '=', 'first_orders.customer_id');
            })
            ->selectRaw('
                orders.customer_id,
                CASE WHEN first_orders.first_order_at >= ? THEN "new" ELSE "returning" END as customer_type,
                COUNT(*) as order_count,
                SUM(orders.order_total) as revenue
            ', [$from])
            ->groupBy('orders.customer_id', 'customer_type')
            ->get();

        $summary = $customersInRange->groupBy('customer_type')->map(function ($group) {
            return [
                'customer_count' => $group->count(),
                'order_count' => $group->sum('order_count'),
                'revenue' => round($group->sum('revenue'), 2),
            ];
        });

        return response()->json([
            'success' => true,
            'new' => $summary->get('new', ['customer_count' => 0, 'order_count' => 0, 'revenue' => 0]),
            'returning' => $summary->get('returning', ['customer_count' => 0, 'order_count' => 0, 'revenue' => 0]),
        ]);
    }

    /**
     * Cart-to-order conversion: how many carts that had actual items in
     * them ended up converting to a real order, vs. sitting abandoned.
     * order_id IS NOT NULL is used as "converted" since it's a direct
     * link to a real order — more reliable than trusting cart_status
     * strings staying perfectly in sync.
     */
    public function cartConversion(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $carts = Acart::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->where('items_count', '>', 0) // ignore empty/never-used carts entirely
            ->selectRaw('
                COUNT(*) as total_carts,
                SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) as converted_carts,
                SUM(CASE WHEN order_id IS NULL THEN cart_total ELSE 0 END) as abandoned_value,
                SUM(CASE WHEN order_id IS NOT NULL THEN cart_total ELSE 0 END) as converted_value
            ')
            ->first();

        $totalCarts = (int) $carts->total_carts;
        $convertedCarts = (int) $carts->converted_carts;

        return response()->json([
            'success' => true,
            'total_carts' => $totalCarts,
            'converted_carts' => $convertedCarts,
            'abandoned_carts' => $totalCarts - $convertedCarts,
            'conversion_rate' => $totalCarts > 0 ? round(($convertedCarts / $totalCarts) * 100, 1) : 0,
            'abandoned_value' => round((float) $carts->abandoned_value, 2),
            'converted_value' => round((float) $carts->converted_value, 2),
        ]);
    }

    /**
     * The natural funnel order based on your actual event_type values.
     * item_removed/quantity_updated are cart-editing events, not forward
     * funnel progress, so they're intentionally excluded here.
     */
    protected const FUNNEL_STAGES = [
        'item_added',
        'checkout_started',
        'customer_attached',
        'payment_selected',
        'shipping_selected',
        'order_created',
    ];

    /**
     * Funnel breakdown: how many DISTINCT carts reached each stage
     * within the period, in the correct funnel order, with drop-off %
     * calculated between each consecutive stage.
     */
    public function cartFunnel(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $counts = AcartEvent::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('event_type', self::FUNNEL_STAGES)
            ->select('event_type')
            ->selectRaw('COUNT(DISTINCT acart_id) as cart_count')
            ->groupBy('event_type')
            ->pluck('cart_count', 'event_type');

        $funnel = [];
        $previousCount = null;

        foreach (self::FUNNEL_STAGES as $stage) {
            $count = (int) ($counts->get($stage) ?? 0);

            $funnel[] = [
                'stage' => $stage,
                'cart_count' => $count,
                // % of carts that reached this stage relative to the
                // PREVIOUS stage — null for the first stage, since
                // there's nothing before it to compare against.
                'drop_off_from_previous' => $previousCount === null
                    ? null
                    : ($previousCount > 0 ? round((1 - ($count / $previousCount)) * 100, 1) : null),
                // % relative to the very first stage (item_added) —
                // useful for an overall "of everyone who added an item,
                // X% completed checkout" style stat.
                'percent_of_start' => isset($funnel[0])
                    ? ($funnel[0]['cart_count'] > 0 ? round(($count / $funnel[0]['cart_count']) * 100, 1) : 0)
                    : 100,
            ];

            $previousCount = $count;
        }

        return response()->json(['success' => true, 'funnel' => $funnel]);
    }

    /**
     * How many distinct sessions have pinged (pageview OR heartbeat)
     * within the last 5 minutes — this is your "X live now" number.
     * Also returns what pages they're currently on, grouped.
     */
    public function liveNow(Request $request)
    {
        $shopId = session('shop_id');
        $window = now()->subMinutes(5);

        $liveCount = ActiveVisitor::where('shop_id', $shopId)
            ->where('last_seen_at', '>=', $window)
            ->count();

        $currentPages = ActiveVisitor::where('shop_id', $shopId)
            ->where('last_seen_at', '>=', $window)
            ->select('current_path')
            ->selectRaw('COUNT(*) as visitor_count')
            ->groupBy('current_path')
            ->orderByDesc('visitor_count')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'live_count' => $liveCount,
            'current_pages' => $currentPages,
        ]);
    }

    /**
     * Day-by-day pageview + unique visitor (distinct session_id) trend,
     * same zero-filled-days pattern as salesTrend().
     */
    public function pageviewTrend(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as pageviews, COUNT(DISTINCT session_id) as unique_visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $series = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $dateKey = $cursor->toDateString();
            $row = $rows->get($dateKey);

            $series[] = [
                'date' => $dateKey,
                'pageviews' => $row ? (int) $row->pageviews : 0,
                'unique_visitors' => $row ? (int) $row->unique_visitors : 0,
            ];

            $cursor->addDay();
        }

        return response()->json(['success' => true, 'series' => $series]);
    }

    /**
     * Most-viewed pages/paths within the period.
     */
    public function topPages(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        $limit = min((int) $request->input('limit', 15), 50);

        $pages = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->select('path')
            ->selectRaw('COUNT(*) as views, COUNT(DISTINCT session_id) as unique_visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'pages' => $pages]);
    }

    /**
     * Breakdown by device_type or browser, for a donut chart. Same
     * generic-column pattern as breakdown() above, just against
     * page_views instead of orders.
     *
     * Usage: /analytics/traffic-breakdown?by=device_type
     *        /analytics/traffic-breakdown?by=browser
     */
    public function trafficBreakdown(Request $request)
    {
        $allowedColumns = ['device_type', 'browser'];
        $by = $request->input('by');

        if (! in_array($by, $allowedColumns, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid breakdown column'], 422);
        }

        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE({$by}, 'Unknown') as label")
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_visitors')
            ->groupBy('label')
            ->orderByDesc('views')
            ->get();

        return response()->json(['success' => true, 'by' => $by, 'breakdown' => $rows]);
    }

    /**
     * Paginated order list for a table view — filterable by status,
     * searchable by order number, respects the same date range.
     */
    public function orders(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $query = Order::where('shop_id', $shopId)
            ->whereBetween('placed_at', [$from, $to])
            ->with(['customer:customer_id,fname,lname,email']); // adjust relation name to match your Order model

        if ($status = $request->input('order_status')) {
            $query->where('order_status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        $orders = $query->orderByDesc('placed_at')
            ->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
            'total' => $orders->total(),
            'page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }
}
