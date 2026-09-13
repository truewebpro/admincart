<?php

namespace App\Jobs;

use App\Models\Product;
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

class BackfillVariantThirdpartyIdsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    // Generous, since this does one Shopify API call PER already-
    // imported product — a large catalog genuinely needs real time
    // here, not just retry-safety padding. Relies on Pro compute's
    // up-to-1-hour grace period rather than a short fixed limit.
    public int $timeout = 3300; // 55 minutes

    public function __construct(protected int $shopId)
    {
    }

    public function uniqueId(): string
    {
        return "variant-backfill-{$this->shopId}";
    }

    public int $uniqueFor = 3600;

    public function handle(): void
    {
        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyService($shopifyShop);

            $checked = 0;
            $matched = 0;

            Product::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('product_id', 'thirdparty_id') // include only what this job actually reads — no update() happens on Product here, so no observer-column concern
                ->chunk(50, function ($products) use ($service, &$checked, &$matched) {
                    foreach ($products as $product) {
                        $checked++;

                        try {
                            $shopifyProduct = $service->getProductById($product->thirdparty_id);
                        } catch (\Throwable $e) {
                            continue; // a single product failing (deleted on Shopify, transient error) shouldn't stop the whole backfill
                        }

                        $shopifyVariantsBySku = collect($shopifyProduct['variants'] ?? [])
                            ->filter(fn ($v) => ! empty($v['sku']))
                            ->keyBy('sku');

                        if ($shopifyVariantsBySku->isEmpty()) {
                            continue;
                        }

                        $localVariants = Variant::where('product_id', $product->product_id)
                            ->whereNull('thirdparty_id')
                            ->whereNotNull('sku')
                            ->get(['variant_id', 'sku', 'product_id', 'shop_id']); // include every column VariantObserver touches, if one exists

                        foreach ($localVariants as $variant) {
                            $shopifyVariant = $shopifyVariantsBySku->get($variant->sku);

                            if ($shopifyVariant) {
                                $variant->update(['thirdparty_id' => (string) $shopifyVariant['id']]);
                                $matched++;
                            }
                        }
                    }
                });

            \Illuminate\Support\Facades\Log::info("Variant backfill for shop {$this->shopId}: checked {$checked} products, matched {$matched} variants.");
        } finally {
            Cache::forget("variant_backfill_running_{$this->shopId}");
        }
    }
}
