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
     * Shopify-style "Sessions over time" — hourly distinct-session counts
     * for today vs. yesterday, aligned by hour so they overlay on one chart.
     */
    public function sessionsOverTime(Request $request)
    {
        $shopId = session('shop_id');
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $todayHourly = $this->hourlySessionCounts($shopId, $today);
        $yesterdayHourly = $this->hourlySessionCounts($shopId, $yesterday);

        return response()->json([
            'success' => true,
            'today' => $todayHourly,
            'yesterday' => $yesterdayHourly,
            'today_label' => $today->format('j M Y'),
            'yesterday_label' => $yesterday->format('j M Y'),
        ]);
    }

    protected function hourlySessionCounts(int $shopId, Carbon $dayStart): array
    {
        $dayEnd = $dayStart->copy()->endOfDay();

        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->selectRaw('HOUR(created_at) as hour, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('hour')
            ->pluck('sessions', 'hour');

        // Zero-fill all 24 hours so the chart has a consistent x-axis
        // even for hours with no traffic yet (e.g. "today" before now).
        $hourly = [];
        for ($h = 0; $h < 24; $h++) {
            $hourly[] = (int) ($rows->get($h) ?? 0);
        }

        return $hourly;
    }

    /**
     * The pill row: Sessions, Total Sales, Orders, Conversion Rate — each
     * with % change vs. yesterday, matching the reference UI exactly.
     */
    public function dailyStats(Request $request)
    {
        $shopId = session('shop_id');
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $yesterdayEnd = $yesterday->copy()->endOfDay();

        $todayStats = $this->dailyStatsFor($shopId, $today, now());
        $yesterdayStats = $this->dailyStatsFor($shopId, $yesterday, $yesterdayEnd);

        return response()->json([
            'success' => true,
            'sessions' => [
                'value' => $todayStats['sessions'],
                'change' => $this->percentChange($yesterdayStats['sessions'], $todayStats['sessions']),
            ],
            'total_sales' => [
                'value' => $todayStats['revenue'],
                'change' => $this->percentChange($yesterdayStats['revenue'], $todayStats['revenue']),
            ],
            'orders' => [
                'value' => $todayStats['order_count'],
                'change' => $this->percentChange($yesterdayStats['order_count'], $todayStats['order_count']),
            ],
            'conversion_rate' => [
                'value' => $todayStats['conversion_rate'],
                'change' => $this->percentChange($yesterdayStats['conversion_rate'], $todayStats['conversion_rate']),
            ],
        ]);
    }

    protected function dailyStatsFor(int $shopId, Carbon $from, Carbon $to): array
    {
        $sessions = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('session_id')
            ->count('session_id');

        $orderRow = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as revenue')
            ->first();

        $orderCount = (int) $orderRow->order_count;
        $revenue = round((float) $orderRow->revenue, 2);

        return [
            'sessions' => $sessions,
            'order_count' => $orderCount,
            'revenue' => $revenue,
            'conversion_rate' => $sessions > 0 ? round(($orderCount / $sessions) * 100, 2) : 0,
        ];
    }

    /**
     * Live visitor positions for the 3D globe — one marker per active
     * session, with lat/long for placement. Only returns sessions that
     * successfully resolved a location (GeoIP miss = excluded, not
     * plotted at 0,0 which would falsely show a cluster at the equator).
     */
    public function liveLocations(Request $request)
    {
        $shopId = session('shop_id');
        $window = now()->subMinutes(5);

        $visitors = ActiveVisitor::where('shop_id', $shopId)
            ->where('last_seen_at', '>=', $window)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('session_id', 'city', 'country', 'latitude', 'longitude', 'current_path', 'last_seen_at')
            ->get();

        return response()->json(['success' => true, 'visitors' => $visitors]);
    }

    /**
     * "Sessions by location" — matches the reference UI's grouped
     * country/region/city breakdown with counts, for the selected range.
     */
    public function sessionsByLocation(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        $limit = min((int) $request->input('limit', 10), 50);

        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('country')
            ->selectRaw("
                COALESCE(country, 'Unknown') as country,
                COALESCE(region, 'None') as region,
                COALESCE(city, 'None') as city
            ")
            ->selectRaw('COUNT(DISTINCT session_id) as sessions')
            ->groupBy('country', 'region', 'city')
            ->orderByDesc('sessions')
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'locations' => $rows]);
    }

    /**
     * Customer behavior funnel: Active carts / Checking out / Purchased
     * — a live snapshot (not date-ranged, matches the "right now" feel
     * of the reference UI), built from acarts + acart_events.
     */
    public function customerBehavior(Request $request)
    {
        $shopId = session('shop_id');

        // Active carts: has items, not yet converted to an order, seen
        // recently (last 30 min — otherwise very old inactive carts
        // would inflate this indefinitely).
        $activeCarts = Acart::where('shop_id', $shopId)
            ->where('items_count', '>', 0)
            ->whereNull('order_id')
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->count();

        // Checking out: carts that fired checkout_started recently but
        // haven't converted yet.
        $checkingOutCartIds = AcartEvent::where('shop_id', $shopId)
            ->where('event_type', 'checkout_started')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->pluck('acart_id')
            ->unique();

        $checkingOut = Acart::whereIn('acart_id', $checkingOutCartIds)
            ->whereNull('order_id')
            ->count();

        // Purchased: orders created in the same recent window.
        $purchased = AcartEvent::where('shop_id', $shopId)
            ->where('event_type', 'order_created')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();

        return response()->json([
            'success' => true,
            'active_carts' => $activeCarts,
            'checking_out' => $checkingOut,
            'purchased' => $purchased,
        ]);
    }

    /**
     * The 4 top stat cards: Gross sales, Returning customer rate,
     * Orders fulfilled, Orders — each with % change vs. the immediately
     * preceding period of equal length, plus a daily sparkline series.
     */
    public function salesStatCards(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $current = $this->salesCardStats($shopId, $from, $to);
        $previous = $this->salesCardStats($shopId, $prevFrom, $prevTo);

        return response()->json([
            'success' => true,
            'gross_sales' => [
                'value' => $current['gross_sales'],
                'change' => $this->percentChange($previous['gross_sales'], $current['gross_sales']),
                'sparkline' => $this->dailySeries($shopId, $from, $to, 'gross_sales'),
            ],
            'returning_customer_rate' => [
                'value' => $current['returning_customer_rate'],
                'change' => $this->percentChange($previous['returning_customer_rate'], $current['returning_customer_rate']),
                'sparkline' => $this->dailySeries($shopId, $from, $to, 'returning_customer_rate'),
            ],
            'orders_fulfilled' => [
                'value' => $current['orders_fulfilled'],
                'change' => $this->percentChange($previous['orders_fulfilled'], $current['orders_fulfilled']),
                'sparkline' => $this->dailySeries($shopId, $from, $to, 'orders_fulfilled'),
            ],
            'orders' => [
                'value' => $current['orders'],
                'change' => $this->percentChange($previous['orders'], $current['orders']),
                'sparkline' => $this->dailySeries($shopId, $from, $to, 'orders'),
            ],
        ]);
    }

    protected function previousPeriod(Carbon $from, Carbon $to): array
    {
        $lengthSeconds = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($lengthSeconds);

        return [$prevFrom, $prevTo];
    }

    protected function salesCardStats(int $shopId, Carbon $from, Carbon $to): array
    {
        $orderRow = Order::where('shop_id', $shopId)
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as orders,
                SUM(CASE WHEN fulfillment_status = "fulfilled" THEN 1 ELSE 0 END) as orders_fulfilled,
                COALESCE(SUM(CASE WHEN payment_status = "paid" AND order_status NOT IN ("cancelled","refunded") THEN subtotal ELSE 0 END), 0) as gross_sales
            ')
            ->first();

        $paidCustomerIds = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->pluck('customer_id')
            ->unique();

        $returningCount = 0;
        if ($paidCustomerIds->isNotEmpty()) {
            $returningCount = Order::whereIn('customer_id', $paidCustomerIds)
                ->where('payment_status', 'paid')
                ->whereNotIn('order_status', ['cancelled', 'refunded'])
                ->where('placed_at', '<', $from)
                ->distinct('customer_id')
                ->count('customer_id');
        }

        return [
            'gross_sales' => round((float) $orderRow->gross_sales, 2),
            'orders' => (int) $orderRow->orders,
            'orders_fulfilled' => (int) $orderRow->orders_fulfilled,
            'returning_customer_rate' => $paidCustomerIds->count() > 0
                ? round(($returningCount / $paidCustomerIds->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Daily values for a single metric, used to feed each stat card's
     * small sparkline. Deliberately simple/un-cached — sparklines only
     * need rough shape, not perfect precision.
     */
    protected function dailySeries(int $shopId, Carbon $from, Carbon $to, string $metric): array
    {
        $rows = Order::where('shop_id', $shopId)
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('
                DATE(placed_at) as date,
                COUNT(*) as orders,
                SUM(CASE WHEN fulfillment_status = "fulfilled" THEN 1 ELSE 0 END) as orders_fulfilled,
                COALESCE(SUM(CASE WHEN payment_status = "paid" AND order_status NOT IN ("cancelled","refunded") THEN subtotal ELSE 0 END), 0) as gross_sales
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $series = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $row = $rows->get($cursor->toDateString());
            // returning_customer_rate isn't meaningfully computable per
            // single day at sparkline resolution — approximate with 0
            // for now rather than an expensive per-day subquery.
            $series[] = $metric === 'returning_customer_rate' ? 0 : (float) ($row->{$metric} ?? 0);
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * "Total sales over time" — current period vs. the immediately
     * preceding period of equal length, day by day.
     */
    public function totalSalesOverTime(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        return response()->json([
            'success' => true,
            'current' => $this->dailyTotalSalesSeries($shopId, $from, $to),
            'previous' => $this->dailyTotalSalesSeries($shopId, $prevFrom, $prevTo),
            'current_label' => $from->format('j M') . ' - ' . $to->format('j M Y'),
            'previous_label' => $prevFrom->format('j M') . ' - ' . $prevTo->format('j M Y'),
            'current_total' => round(
                Order::where('shop_id', $shopId)->where('payment_status', 'paid')
                    ->whereNotIn('order_status', ['cancelled', 'refunded'])
                    ->whereBetween('placed_at', [$from, $to])->sum('order_total'), 2
            ),
        ]);
    }

    protected function dailyTotalSalesSeries(int $shopId, Carbon $from, Carbon $to): array
    {
        $rows = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('DATE(placed_at) as date, COALESCE(SUM(order_total), 0) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $series = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $series[] = round((float) ($rows->get($cursor->toDateString()) ?? 0), 2);
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * "Total sales breakdown" line items.
     *
     * IMPORTANT — two rows are approximated/unavailable given the current
     * schema:
     *   - "Sales reversals": there's no refund-amount column, only
     *     order_status. This uses the full order_total of orders marked
     *     'refunded' as a stand-in — accurate only if refunds are always
     *     full-order, not partial.
     *   - "Return fees": no column tracks this at all. Hardcoded to 0.
     *     Add a column if you want this to be real.
     */
    public function salesBreakdown(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $current = $this->breakdownFor($shopId, $from, $to);
        $previous = $this->breakdownFor($shopId, $prevFrom, $prevTo);

        $rows = [];
        foreach ($current as $key => $value) {
            $rows[$key] = [
                'value' => $value,
                'change' => $this->percentChange($previous[$key], $value),
            ];
        }

        return response()->json(['success' => true, 'breakdown' => $rows]);
    }

    protected function breakdownFor(int $shopId, Carbon $from, Carbon $to): array
    {
        $paid = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to]);

        $grossSales = (clone $paid)->sum('subtotal'); // ASSUMPTION: subtotal = pre-discount product total — confirm this matches your actual field semantics
        $discounts = (clone $paid)->selectRaw('COALESCE(SUM(discount_amount + coupon_discount), 0) as d')->value('d');
        $shipping = (clone $paid)->sum('shipping_cost');
        $taxes = (clone $paid)->sum('tax_amount');

        $reversals = Order::where('shop_id', $shopId)
            ->where('order_status', 'refunded')
            ->whereBetween('placed_at', [$from, $to])
            ->sum('order_total');

        $netSales = $grossSales - $discounts - $reversals;
        $totalSales = $netSales + $shipping + $taxes;

        return [
            'gross_sales' => round($grossSales, 2),
            'discounts' => round(-$discounts, 2), // negative, matches reference display
            'sales_reversals' => round(-$reversals, 2),
            'net_sales' => round($netSales, 2),
            'shipping_charges' => round($shipping, 2),
            'return_fees' => 0.0, // not tracked in current schema
            'taxes' => round($taxes, 2),
            'total_sales' => round($totalSales, 2),
        ];
    }

    /**
     * "Total sales by sales channel" — currently every order is
     * effectively "Online Store" since there's no channel/source column
     * on orders. Returns a single-channel breakdown; extend this if you
     * add POS/marketplace channels later.
     */
    public function salesByChannel(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $total = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->sum('order_total');

        return response()->json([
            'success' => true,
            'channels' => [
                ['name' => 'Online Store', 'value' => round((float) $total, 2), 'change' => null],
            ],
        ]);
    }

    /**
     * "Average order value over time" — same current-vs-previous-period
     * pattern as totalSalesOverTime(), but dividing by order count per day.
     */
    public function aovOverTime(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        return response()->json([
            'success' => true,
            'current' => $this->dailyAovSeries($shopId, $from, $to),
            'previous' => $this->dailyAovSeries($shopId, $prevFrom, $prevTo),
        ]);
    }

    protected function dailyAovSeries(int $shopId, Carbon $from, Carbon $to): array
    {
        $rows = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as cnt, COALESCE(SUM(order_total), 0) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $series = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $row = $rows->get($cursor->toDateString());
            $series[] = ($row && $row->cnt > 0) ? round($row->total / $row->cnt, 2) : 0;
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * "Total sales by product" — current vs. previous period revenue per
     * product, matching the reference's dual-bar comparison per row.
     */
    public function salesByProduct(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);
        $limit = min((int) $request->input('limit', 10), 50);

        $current = $this->productRevenueFor($shopId, $from, $to);
        $previous = $this->productRevenueFor($shopId, $prevFrom, $prevTo)->keyBy('product_id');

        $rows = $current->map(function ($row) use ($previous) {
            $prevRevenue = (float) ($previous->get($row->product_id)->total_revenue ?? 0);

            return [
                'product_id' => $row->product_id,
                'title' => $row->title,
                'current_revenue' => round((float) $row->total_revenue, 2),
                'previous_revenue' => round($prevRevenue, 2),
                'change' => $this->percentChange($prevRevenue, (float) $row->total_revenue),
            ];
        })->sortByDesc('current_revenue')->take($limit)->values();

        return response()->json(['success' => true, 'products' => $rows]);
    }

    protected function productRevenueFor(int $shopId, Carbon $from, Carbon $to)
    {
        return OrderItem::query()
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.order_status', ['cancelled', 'refunded'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->groupBy('order_items.product_id', 'order_items.title')
            ->selectRaw('order_items.product_id, order_items.title, SUM(order_items.total) as total_revenue')
            ->get();
    }

    /**
     * "Sessions over time" — current vs. previous period, same pattern
     * as totalSalesOverTime() but counting distinct page_views sessions.
     */
    public function sessionsOverTimeRange(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        return response()->json([
            'success' => true,
            'current' => $this->dailySessionsSeries($shopId, $from, $to),
            'previous' => $this->dailySessionsSeries($shopId, $prevFrom, $prevTo),
            'current_total' => PageView::where('shop_id', $shopId)
                ->whereBetween('created_at', [$from, $to])
                ->distinct('session_id')->count('session_id'),
        ]);
    }

    protected function dailySessionsSeries(int $shopId, Carbon $from, Carbon $to): array
    {
        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('date')
            ->pluck('sessions', 'date');

        $series = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $series[] = (int) ($rows->get($cursor->toDateString()) ?? 0);
            $cursor->addDay();
        }
        return $series;
    }

    /**
     * "Conversion rate over time" — orders / sessions per day, current
     * vs. previous period.
     */
    public function conversionRateOverTime(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        return response()->json([
            'success' => true,
            'current' => $this->dailyConversionSeries($shopId, $from, $to),
            'previous' => $this->dailyConversionSeries($shopId, $prevFrom, $prevTo),
        ]);
    }

    protected function dailyConversionSeries(int $shopId, Carbon $from, Carbon $to): array
    {
        $sessions = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('date')->pluck('sessions', 'date');

        $orders = Order::where('shop_id', $shopId)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded'])
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as orders')
            ->groupBy('date')->pluck('orders', 'date');

        $series = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $s = (int) ($sessions->get($key) ?? 0);
            $o = (int) ($orders->get($key) ?? 0);
            $series[] = $s > 0 ? round(($o / $s) * 100, 2) : 0;
            $cursor->addDay();
        }
        return $series;
    }

    /**
     * "Conversion rate breakdown" funnel — Sessions / Added to cart /
     * Reached checkout / Completed.
     *
     * CAVEAT: page_views sessions and acart_events are NOT currently
     * linked by a shared identifier (page_views.session_id vs.
     * acarts.acart_id are separate systems). "Added to cart" and
     * "Reached checkout" below count DISTINCT CARTS with that event,
     * not distinct SESSIONS — a reasonable proxy, but not the same unit
     * as "Sessions", so the percentages are an approximation, not a
     * true session-level funnel. To make this exact, add a session_id
     * column to acarts (populated from the same tracking cookie used
     * for page_views) so cart activity can be joined back to sessions.
     */
    public function conversionBreakdown(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $current = $this->funnelStagesFor($shopId, $from, $to);
        $previous = $this->funnelStagesFor($shopId, $prevFrom, $prevTo);

        $stages = [];
        foreach ($current as $key => $value) {
            $stages[$key] = [
                'value' => $value,
                'percent_of_sessions' => $current['sessions'] > 0 ? round(($value / $current['sessions']) * 100, 2) : 0,
                'change' => $this->percentChange($previous[$key], $value),
            ];
        }

        return response()->json(['success' => true, 'stages' => $stages]);
    }

    protected function funnelStagesFor(int $shopId, Carbon $from, Carbon $to): array
    {
        $sessions = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('session_id')->count('session_id');

        $addedToCart = AcartEvent::where('shop_id', $shopId)
            ->where('event_type', 'item_added')
            ->whereBetween('created_at', [$from, $to])
            ->distinct('acart_id')->count('acart_id');

        $reachedCheckout = AcartEvent::where('shop_id', $shopId)
            ->where('event_type', 'checkout_started')
            ->whereBetween('created_at', [$from, $to])
            ->distinct('acart_id')->count('acart_id');

        $completed = AcartEvent::where('shop_id', $shopId)
            ->where('event_type', 'order_created')
            ->whereBetween('created_at', [$from, $to])
            ->distinct('acart_id')->count('acart_id');

        return [
            'sessions' => $sessions,
            'added_to_cart' => $addedToCart,
            'reached_checkout' => $reachedCheckout,
            'completed' => $completed,
        ];
    }

    /**
     * "Sessions by location" with current vs. previous period bars,
     * matching this reference's dual-bar-per-row comparison style
     * (different from the single-period version used on the Live View page).
     */
    public function sessionsByLocationComparison(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);
        $limit = min((int) $request->input('limit', 10), 50);

        $current = $this->locationSessionsFor($shopId, $from, $to);
        $previous = $this->locationSessionsFor($shopId, $prevFrom, $prevTo)->keyBy('location_key');

        $rows = $current->map(function ($row) use ($previous) {
            $prevSessions = (int) ($previous->get($row->location_key)->sessions ?? 0);
            return [
                'country' => $row->country,
                'region' => $row->region,
                'city' => $row->city,
                'sessions' => (int) $row->sessions,
                'previous_sessions' => $prevSessions,
                'change' => $this->percentChange($prevSessions, (int) $row->sessions),
            ];
        })->sortByDesc('sessions')->take($limit)->values();

        return response()->json(['success' => true, 'locations' => $rows]);
    }

    protected function locationSessionsFor(int $shopId, Carbon $from, Carbon $to)
    {
        return PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('country')
            ->selectRaw("
                CONCAT(COALESCE(country,'Unknown'), '|', COALESCE(region,'None'), '|', COALESCE(city,'None')) as location_key,
                COALESCE(country, 'Unknown') as country,
                COALESCE(region, 'None') as region,
                COALESCE(city, 'None') as city,
                COUNT(DISTINCT session_id) as sessions
            ")
            ->groupBy('location_key', 'country', 'region', 'city')
            ->get();
    }

    /**
     * "Total sales by social referrer" — groups revenue by referrer
     * domain, filtered to common social platforms. This is a best-effort
     * classification from the raw referrer URL stored on page_views;
     * there's no dedicated "traffic source" tracking beyond that, so
     * anything not matching a known social domain is excluded rather
     * than lumped into "Other" (matches the reference's honest empty
     * state when there's genuinely no matching traffic).
     */
    public function salesBySocialReferrer(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);

        $socialDomains = [
            'facebook.com' => 'Facebook',
            'instagram.com' => 'Instagram',
            'twitter.com' => 'Twitter / X',
            'x.com' => 'Twitter / X',
            'tiktok.com' => 'TikTok',
            'pinterest.com' => 'Pinterest',
            'linkedin.com' => 'LinkedIn',
            'youtube.com' => 'YouTube',
        ];

        // Sessions whose first-seen referrer matched a known social domain.
        $socialSessions = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('referrer')
            ->get(['session_id', 'referrer', 'customer_id'])
            ->filter(function ($pv) use ($socialDomains) {
                foreach ($socialDomains as $domain => $label) {
                    if (str_contains($pv->referrer, $domain)) return true;
                }
                return false;
            });

        if ($socialSessions->isEmpty()) {
            return response()->json(['success' => true, 'referrers' => []]);
        }

        // Best-effort: attribute revenue via customer_id match on orders
        // in the same window — approximate, since session != customer
        // for guest checkouts without a linked customer_id.
        $customerIds = $socialSessions->pluck('customer_id')->filter()->unique();

        $revenue = Order::where('shop_id', $shopId)
            ->whereIn('customer_id', $customerIds)
            ->where('payment_status', 'paid')
            ->whereBetween('placed_at', [$from, $to])
            ->sum('order_total');

        return response()->json([
            'success' => true,
            'referrers' => $customerIds->isEmpty() ? [] : [
                ['name' => 'Social (combined)', 'value' => round((float) $revenue, 2)],
            ],
        ]);
    }

    /**
     * Classifies a raw referrer URL into (medium, source), matching the
     * reference UI's "Search · google · <city>" / "Direct · None · <city>"
     * grouping style.
     */
    protected function classifyReferrer(?string $referrer): array
    {
        if (empty($referrer)) {
            return ['Direct', 'None'];
        }

        $host = parse_url($referrer, PHP_URL_HOST) ?? '';

        $searchEngines = [
            'google.' => 'google', 'bing.com' => 'bing', 'yahoo.' => 'yahoo',
            'duckduckgo.com' => 'duckduckgo', 'baidu.com' => 'baidu',
        ];
        foreach ($searchEngines as $domain => $name) {
            if (str_contains($host, $domain)) return ['Search', $name];
        }

        $social = [
            'facebook.com' => 'facebook', 'instagram.com' => 'instagram',
            'twitter.com' => 'twitter', 'x.com' => 'twitter', 'tiktok.com' => 'tiktok',
            'pinterest.com' => 'pinterest', 'linkedin.com' => 'linkedin', 'youtube.com' => 'youtube',
        ];
        foreach ($social as $domain => $name) {
            if (str_contains($host, $domain)) return ['Social', $name];
        }

        return ['Referral', $host ?: 'Unknown'];
    }

    /**
     * "Sessions by referrer" — medium · source · city grouping, current
     * vs. previous period, same dual-bar comparison style as the
     * location breakdown.
     */
    public function sessionsByReferrer(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);
        $limit = min((int) $request->input('limit', 10), 50);

        $current = $this->referrerSessionsFor($shopId, $from, $to);
        $previousLookup = $this->referrerSessionsFor($shopId, $prevFrom, $prevTo)
            ->mapWithKeys(fn ($r) => ["{$r['medium']}|{$r['source']}|{$r['city']}" => $r['sessions']]);

        $rows = $current->map(function ($row) use ($previousLookup) {
            $key = "{$row['medium']}|{$row['source']}|{$row['city']}";
            $prevSessions = $previousLookup->get($key, 0);

            return [
                'medium' => $row['medium'],
                'source' => $row['source'],
                'city' => $row['city'],
                'sessions' => $row['sessions'],
                'previous_sessions' => $prevSessions,
                'change' => $this->percentChange($prevSessions, $row['sessions']),
            ];
        })->sortByDesc('sessions')->take($limit)->values();

        return response()->json(['success' => true, 'referrers' => $rows]);
    }

    /**
     * Groups page_views into medium/source/city buckets in PHP rather
     * than SQL, since referrer classification (parsing hostnames into
     * "Search"/"Social"/"Direct"/"Referral") isn't expressible as a
     * simple SQL GROUP BY — needs the classifyReferrer() logic per row.
     * Fine at current traffic volume; revisit if page_views grows large
     * enough that pulling raw rows into PHP becomes slow.
     *
     * @return \Illuminate\Support\Collection<array{medium: string, source: string, city: string, sessions: int}>
     */
    protected function referrerSessionsFor(int $shopId, Carbon $from, Carbon $to)
    {
        $rows = PageView::where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->get(['session_id', 'referrer', 'city']);

        $grouped = [];
        foreach ($rows as $row) {
            [$medium, $source] = $this->classifyReferrer($row->referrer);
            $city = $row->city ?? 'None';
            $key = "{$medium}|{$source}|{$city}";

            $grouped[$key]['medium'] = $medium;
            $grouped[$key]['source'] = $source;
            $grouped[$key]['city'] = $city;
            $grouped[$key]['sessions'][$row->session_id] = true; // dedupe by session
        }

        return collect($grouped)->map(fn ($g) => [
            'medium' => $g['medium'],
            'source' => $g['source'],
            'city' => $g['city'],
            'sessions' => count($g['sessions']),
        ])->values();
    }

    /**
     * "Total sales by POS location" — there is no POS system in this
     * stack (online-only DTC storefront), so this always returns empty,
     * matching the reference's own honest "No data for this date range"
     * state. Not a bug — there's genuinely nothing to show.
     */
    public function salesByPosLocation(Request $request)
    {
        return response()->json(['success' => true, 'locations' => []]);
    }

    /**
     * "Products by sell-through rate" — units sold in period ÷ (units
     * sold + current remaining stock) × 100. Uses CURRENT stock
     * (a snapshot, not historical), since the Stock table only tracks
     * present-day quantity — meaning this is "sell-through against
     * today's inventory," not a true point-in-time historical rate.
     */
    public function productsBySellThroughRate(Request $request)
    {
        $shopId = session('shop_id');
        [$from, $to] = $this->resolveDateRange($request);
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);
        $limit = min((int) $request->input('limit', 10), 50);

        $currentSold = $this->soldQuantityByProduct($shopId, $from, $to);
        $previousSold = $this->soldQuantityByProduct($shopId, $prevFrom, $prevTo);

        $currentStock = \App\Models\Stock::whereHas('product', fn ($q) => $q->where('shop_id', $shopId))
            ->selectRaw('product_id, SUM(quantity) as stock')
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $rows = $currentSold->map(function ($row) use ($currentStock, $previousSold) {
            $sold = (int) $row->sold;
            $stock = (int) ($currentStock->get($row->product_id) ?? 0);
            $denominator = $sold + $stock;

            $prevSold = (int) ($previousSold->firstWhere('product_id', $row->product_id)->sold ?? 0);
            $prevDenominator = $prevSold + $stock; // approximation: uses same current stock, since historical stock isn't tracked
            $prevRate = $prevDenominator > 0 ? round(($prevSold / $prevDenominator) * 100, 2) : 0;

            $rate = $denominator > 0 ? round(($sold / $denominator) * 100, 2) : 0;

            return [
                'product_id' => $row->product_id,
                'title' => $row->title,
                'sold' => $sold,
                'current_stock' => $stock,
                'sell_through_rate' => $rate,
                'change' => $prevRate > 0 ? $this->percentChange($prevRate, $rate) : null,
            ];
        })->sortByDesc('sell_through_rate')->take($limit)->values();

        return response()->json(['success' => true, 'products' => $rows]);
    }

    protected function soldQuantityByProduct(int $shopId, Carbon $from, Carbon $to)
    {
        return OrderItem::query()
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.order_status', ['cancelled', 'refunded'])
            ->whereBetween('orders.placed_at', [$from, $to])
            ->groupBy('order_items.product_id', 'order_items.title')
            ->selectRaw('order_items.product_id, order_items.title, SUM(order_items.quantity) as sold')
            ->get();
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
