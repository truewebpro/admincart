<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ShopifyShop;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncProductSeoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // batchFetchNodes() already retries internally for throttling

    public int $timeout = 280; // matches the scheduled queue:work --timeout=280 (see Kernel.php)

    public function __construct(protected int $shopId)
    {
    }

    public function uniqueId(): string
    {
        return "product-seo-sync-{$this->shopId}";
    }

    public int $uniqueFor = 600;

    public function handle(): void
    {
        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyService($shopifyShop);

            // Same select() bug fix as before — include every column
            // ProductObserver touches (shop_id, handle), not just the
            // two we actually read here. A narrower select() previously
            // caused the observer to receive null for these on ->update().
            $products = Product::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('product_id', 'thirdparty_id', 'shop_id', 'handle')
                ->get();

            if ($products->isEmpty()) {
                return;
            }

            // Chunked locally too (100 at a time), same reasoning as the
            // original: avoid holding a huge catalog's worth of product
            // rows in memory at once, on top of the GraphQL side already
            // chunking at 100 per API call inside getProductsSeo().
            $products->chunk(100)->each(function ($chunk) use ($service) {
                $shopifyIds = $chunk->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();
                $seoData = $service->getProductsSeo($shopifyIds);

                foreach ($chunk as $product) {
                    $seo = $seoData[(int) $product->thirdparty_id] ?? null;

                    if (! $seo) {
                        continue;
                    }

                    $updates = [];

                    if (! empty($seo['title'])) {
                        $updates['meta_title'] = mb_substr($seo['title'], 0, 255);
                    }

                    if (! empty($seo['description'])) {
                        // This exact truncation was the missing piece —
                        // SproController's old synchronous sync wrote
                        // the raw, unbounded description straight to a
                        // VARCHAR(255) column, causing the "Data too
                        // long" SQL exception (uncaught -> 500 Server Error).
                        $updates['meta_desc'] = mb_substr($seo['description'], 0, 255);
                    }

                    if ($updates) {
                        $product->update($updates);
                    }
                }
            });
        } finally {
            Cache::forget("product_seo_sync_running_{$this->shopId}");
        }
    }
}
