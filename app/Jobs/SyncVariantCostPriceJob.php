<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobRun;
use App\Models\ShopifyShop;
use App\Models\Variant;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncVariantCostPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksJobRun;

    public int $tries = 1;
    public int $timeout = 280;

    protected const JOB_TYPE = 'variant_cost_sync';

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

            $variants = Variant::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->select('variant_id', 'thirdparty_id', 'shop_id', 'sku');

            $updated = 0;
            $noCostData = 0;
            $checked = 0;

            $variants->chunk(100, function ($chunk) use ($service, &$updated, &$noCostData, &$checked) {
                $checked += $chunk->count();
                $shopifyIds = $chunk->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();
                $costData = $service->getVariantsCostPrice($shopifyIds);

                foreach ($chunk as $variant) {
                    $cost = $costData[(int) $variant->thirdparty_id] ?? null;

                    if ($cost === null) {
                        $noCostData++;
                        continue;
                    }

                    $variant->update(['costprice' => $cost]);
                    $updated++;
                }
            });

            // If EVERY variant came back with no cost data, that's a
            // strong signal the store's "View product costs"
            // permission isn't granted to this app — surfaced directly
            // in the dashboard-visible result now, not buried in logs.
            $likelyPermissionIssue = $updated === 0 && $noCostData > 0;

            $this->completeRun($run, [
                'updated'   => $updated,
                'checked'   => $checked,
                'no_cost_data' => $noCostData,
                'likely_permission_issue' => $likelyPermissionIssue,
            ]);
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }
}
