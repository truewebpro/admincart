<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobRun;
use App\Models\Product;
use App\Models\ShopifyShop;
use App\Models\Variant;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BackfillVariantThirdpartyIdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksJobRun;

    public int $tries = 1;

    // Generous — one Shopify API call PER already-imported product, a
    // large catalog genuinely needs real time here. Relies on Pro
    // compute's up-to-1-hour grace period.
    public int $timeout = 3300; // 55 minutes

    protected const JOB_TYPE = 'variant_backfill';

    public function __construct(protected int $shopId)
    {
    }

    public function handle(): void
    {
        if ($this->isAlreadyRunning($this->shopId, self::JOB_TYPE)) {
            return;
        }

        $run = $this->startRun($this->shopId, self::JOB_TYPE);

        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyService($shopifyShop);

            $checked = 0;
            $matched = 0;
            $productErrors = 0;

            Product::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('product_id', 'thirdparty_id')
                ->chunk(50, function ($products) use ($service, &$checked, &$matched, &$productErrors) {
                    foreach ($products as $product) {
                        $checked++;

                        try {
                            $shopifyProduct = $service->getProductById($product->thirdparty_id);
                        } catch (\Throwable $e) {
                            // A single product failing (deleted on
                            // Shopify, transient error) shouldn't stop
                            // the whole backfill — this is a DIFFERENT
                            // concern from the outer try/catch, which
                            // handles the job as a whole failing.
                            $productErrors++;
                            continue;
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
                            ->get(['variant_id', 'sku', 'product_id', 'shop_id']);

                        foreach ($localVariants as $variant) {
                            $shopifyVariant = $shopifyVariantsBySku->get($variant->sku);

                            if ($shopifyVariant) {
                                $variant->update(['thirdparty_id' => (string) $shopifyVariant['id']]);
                                $matched++;
                            }
                        }
                    }
                });

            $this->completeRun($run, [
                'checked'         => $checked,
                'matched'         => $matched,
                'product_errors'  => $productErrors,
            ]);
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }
}
