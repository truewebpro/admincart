<?php

namespace App\Http\Controllers;

use App\Services\PromoLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoLabelController extends Controller
{
    public function __construct(protected PromoLabelService $labels) {}

    /**
     * GET /shop/{shopname}/promo-labels
     *
     * Fetch once per storefront session (frontend should cache this in its
     * own store, e.g. Pinia, keyed by shop — it changes rarely and is
     * already cached server-side, but there's no reason to refetch on
     * every page nav). Response shape:
     * {
     *   entire_order: [{id, kind, promo_type, label, priority}, ...],
     *   by_product: { "123": [...], "456": [...] },
     *   by_cat:     { "9": [...] },
     *   generated_at: "2026-09-04T12:00:00+00:00"
     * }
     */
    public function index(Request $request): JsonResponse
    {
        // ResolveShop middleware sets this via $request->attributes->set('shop', $shop)
        $shop = $request->attributes->get('shop');

        return response()->json($this->labels->getShopLabelMap($shop->shop_id));
    }
}
