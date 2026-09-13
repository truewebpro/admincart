<?php

namespace App\Jobs;

use App\Models\ShopifyShop;
use App\Models\Variant;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncVariantCostPriceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 280;

    public function __construct(protected int $shopId)
    {
    }

    public function uniqueId(): string
    {
        return "variant-cost-sync-{$this->shopId}";
    }

    public int $uniqueFor = 600;

    public function handle(): void
    {
        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyService($shopifyShop);

            $variants = Variant::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('variant_id', 'thirdparty_id', 'shop_id', 'sku'); // include every column any VariantObserver touches, same reasoning as every prior select() fix

            $updated = 0;
            $noPermissionOrEmpty = 0;

            $variants->chunk(100, function ($chunk) use ($service, &$updated, &$noPermissionOrEmpty) {
                $shopifyIds = $chunk->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();
                $costData = $service->getVariantsCostPrice($shopifyIds);

                foreach ($chunk as $variant) {
                    $cost = $costData[(int) $variant->thirdparty_id] ?? null;

                    if ($cost === null) {
                        $noPermissionOrEmpty++;
                        continue; // either genuinely no cost set on Shopify, or the "View product costs" permission isn't granted — see method docblock
                    }

                    $variant->update(['costprice' => $cost]);
                    $updated++;
                }
            });

            Log::info("Variant cost-price sync for shop {$this->shopId}: {$updated} updated, {$noPermissionOrEmpty} returned no cost data.");

            // If EVERY variant came back with no cost data, that's a
            // strong signal the "View product costs" permission isn't
            // granted, rather than every product genuinely having no
            // cost set — worth a distinct log line to make that
            // diagnosis obvious later without re-deriving it.
            if ($updated === 0 && $noPermissionOrEmpty > 0) {
                Log::warning("Variant cost-price sync for shop {$this->shopId}: ZERO variants got cost data. Check the store's \"View product costs\" permission for this app — this is likely a permissions issue, not a data issue.");
            }
        } finally {
            Cache::forget("variant_cost_sync_running_{$this->shopId}");
        }
    }
}
