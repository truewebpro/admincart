<?php

namespace App\Http\Controllers\Concerns;

use App\Services\LoyaltyService;
use Illuminate\Support\Collection;

/**
 * Drop into your existing product controller(s) to attach loyalty points
 * directly onto variant data already being returned — no separate request,
 * no separate endpoint to keep in sync. Points aren't customer-specific
 * (same rate/override for every guest and logged-in customer at a shop), so
 * there's no reason they can't ride along with product data you're already
 * fetching once.
 *
 * Works with either array variants (['variant_id' => .., 'price' => ..])
 * or Eloquent model instances — same call, no special-casing needed at the
 * call site.
 */
trait EnrichesWithLoyaltyPoints
{
    /**
     * Single variant — for a product-by-slug / product detail response.
     */
    protected function attachLoyaltyPoints(int $shopId, array|object $variant, string $idKey = 'variant_id', string $priceKey = 'price'): array|object
    {
        $points = app(LoyaltyService::class)->pointsForVariant(
            shopId: $shopId,
            variantId: (int) data_get($variant, $idKey),
            price: (float) data_get($variant, $priceKey),
        ) ?? 0;

        return $this->setLoyaltyPoints($variant, $points);
    }

    /**
     * Many variants at once — for a product listing/grid response. One
     * settings lookup and one overrides query for the whole page, same as
     * the bulk points-preview endpoint, just folded into your existing
     * response instead of a second request.
     */
    protected function attachLoyaltyPointsToMany(int $shopId, iterable $variants, string $idKey = 'variant_id', string $priceKey = 'price'): array
    {
        $variants = Collection::make($variants);

        $variantPrices = $variants->mapWithKeys(fn ($v) => [
            data_get($v, $idKey) => ['price' => (float) data_get($v, $priceKey), 'quantity' => 1],
        ])->all();

        $pointsMap = app(LoyaltyService::class)->pointsForVariants($shopId, $variantPrices);

        return $variants
            ->map(fn ($v) => $this->setLoyaltyPoints($v, $pointsMap[data_get($v, $idKey)] ?? 0))
            ->all();
    }

    private function setLoyaltyPoints(array|object $variant, int $points): array|object
    {
        if (is_array($variant)) {
            $variant['loyalty_points'] = $points;
        } else {
            $variant->loyalty_points = $points;
        }

        return $variant;
    }
}
