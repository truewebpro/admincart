<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobRun;
use App\Models\Product;
use App\Models\ShopifyShop;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksJobRun;

    public int $tries = 1;
    public int $timeout = 280;

    protected const JOB_TYPE = 'product_seo_sync';

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

            $products = Product::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('product_id', 'thirdparty_id', 'shop_id', 'handle') // every column ProductObserver touches
                ->get();

            $updated = 0;

            $products->chunk(100)->each(function ($chunk) use ($service, &$updated) {
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
                        $updates['meta_desc'] = mb_substr($seo['description'], 0, 255);
                    }

                    if ($updates) {
                        $product->update($updates);
                        $updated++;
                    }
                }
            });

            $this->completeRun($run, [
                'updated' => $updated,
                'checked' => $products->count(),
            ]);
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }
}
