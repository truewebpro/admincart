<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobRun;
use App\Models\Cat;
use App\Models\ShopifyShop;
use App\Services\ShopifyCollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCollectionSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksJobRun;

    public int $tries = 1;
    public int $timeout = 280;

    protected const JOB_TYPE = 'collection_seo_sync';

    public function __construct(protected int $shopId)
    {
    }

    public function handle(): void
    {
        // Replaces the old ShouldBeUnique + Cache-lock pattern entirely
        // — one check, backed by the job_runs table, which also gives
        // us history instead of just a transient yes/no flag.
        if ($this->isAlreadyRunning($this->shopId, self::JOB_TYPE)) {
            return;
        }

        $run = $this->startRun($this->shopId, self::JOB_TYPE);

        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyCollectionService($shopifyShop);

            $cats = Cat::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->get(['cat_id', 'thirdparty_id', 'shop_id', 'cat_slug']); // include every column any Cat observer touches

            $updated = 0;

            if ($cats->isNotEmpty()) {
                $shopifyIds = $cats->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();
                $seoData = $service->getCollectionsSeo($shopifyIds);

                foreach ($cats as $cat) {
                    $seo = $seoData[(int) $cat->thirdparty_id] ?? null;

                    if (! $seo) {
                        continue;
                    }

                    $updates = [];

                    if (! empty($seo['title'])) {
                        $updates['meta_title'] = $seo['title'];
                    }

                    if (! empty($seo['description'])) {
                        // Truncated defensively in case meta_desc is
                        // still VARCHAR(255) — mb_substr, not substr,
                        // so a multi-byte UTF-8 character never gets
                        // cut in half.
                        $updates['meta_desc'] = mb_substr($seo['description'], 0, 255);
                    }

                    if ($updates) {
                        $cat->update($updates);
                        $updated++;
                    }
                }
            }

            // This now ALWAYS runs when the work completes without
            // throwing — whether there were 0 cats, or 50 — so the
            // run never gets stuck at "processing" in the dashboard.
            $this->completeRun($run, [
                'updated' => $updated,
                'checked' => $cats->count(),
            ]);
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
            throw $e; // still let the queue's own failed_jobs mechanism see this too
        }
    }
}
