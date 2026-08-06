<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

/**
 * Public — deliberately NOT behind auth:customer or resolve.customer-shop.
 * "Earn 19 points" on a product card has to work for guests too; it's part of
 * what convinces someone to create an account in the first place. Only needs
 * $request->shop_id (from resolve.shop), no customer identity at all.
 *
 * GET  /shop/{shopname}/loyalty/points-preview/{variantId}
 * POST /shop/{shopname}/loyalty/points-preview
 */
class LoyaltyPointsPreviewController extends Controller
{
    public function __construct(protected LoyaltyService $loyaltyService)
    {
    }

    /**
     * GET /shop/{shopname}/loyalty/points-preview/{variantId}
     * Single variant — for a product detail page.
     * Returns { points: 19 } or { points: 0 } if the program's inactive /
     * this variant is excluded via an override set to 0.
     *
     * Takes a raw id and looks the Variant up manually rather than relying on
     * implicit route-model-binding (Variant $variant) — binding wasn't
     * resolving in testing (got a raw string instead of a model instance),
     * so this sidesteps whatever's intercepting it rather than debugging that
     * separately. firstOrFail() gives the same 404-on-missing behavior either way.
     *
     * $shopname is unused in the body (shop comes from $request->shop_id via
     * resolve.shop instead) but must stay in the signature — this route sits
     * under the parent shop/{shopname} prefix, and confirmed via testing that
     * Laravel needs every route parameter present as a method parameter here
     * to resolve correctly, or the route 404s even though it's registered.
     */
    public function show(Request $request, $shopname, string $variantId)
    {
        $variant = Variant::where('shop_id', (int) $request->shop_id)
            ->where('variant_id', $variantId)
            ->firstOrFail();

        $points = $this->loyaltyService->pointsForVariant(
            shopId: (int) $request->shop_id,
            variantId: $variant->variant_id,
            price: (float) $variant->price,
        );

        return response()->json(['points' => $points ?? 0]);
    }

    /**
     * POST /shop/{shopname}/loyalty/points-preview
     * Body: { variant_ids: [1, 2, 3] }
     * Bulk — for a product listing/grid page, one request instead of one per card.
     * Looks up each variant's own price directly rather than trusting a
     * client-supplied price (avoids a customer spoofing a lower price to
     * inflate the points shown — the number displayed should always match
     * what the backend actually has on file).
     * Returns { "1": 19, "2": 0, "3": 25 } keyed by variant_id.
     *
     * $shopname required in the signature for the same reason as show() above —
     * see that method's docblock. Easy to miss here specifically because this
     * method doesn't call firstOrFail(), so a broken $shopname resolution
     * wouldn't 404 — it'd just silently return an empty/zeroed points map,
     * which is worse (fails quietly instead of erroring loudly).
     */
    public function bulk(Request $request, $shopname)
    {
        $request->validate([
            'variant_ids' => 'required|array|max:100',
            'variant_ids.*' => 'integer',
        ]);

        $shopId = (int) $request->shop_id;

        $variants = Variant::where('shop_id', $shopId)
            ->whereIn('variant_id', $request->variant_ids)
            ->get(['variant_id', 'price']);

        $variantPrices = $variants->mapWithKeys(fn ($v) => [
            $v->variant_id => ['price' => (float) $v->price, 'quantity' => 1],
        ])->all();

        return response()->json(
            $this->loyaltyService->pointsForVariants($shopId, $variantPrices)
        );
    }
}
