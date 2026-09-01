<?php

namespace App\Jobs;

use App\Models\Cat;
use App\Models\ShopifyShop;
use App\Services\ShopifyCollectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCollectionSeoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // batchFetchNodes() already retries internally for throttling — don't also re-run the whole job on failure

    // If your managed queue is on Laravel Cloud's standard compute
    // class (fixed runtime limit), keep this comfortably under that
    // limit. If it's on the Pro class, jobs get up to an hour grace
    // period regardless of this value — but setting an explicit
    // timeout is still good practice so a genuinely stuck job doesn't
    // run forever.
    public int $timeout = 300; // 5 minutes

    public function __construct(protected int $shopId)
    {
    }

    /**
     * ShouldBeUnique: prevents a second sync for the SAME shop from
     * being dispatched while one is already running/queued — e.g. if
     * the "Sync Seo" button gets double-clicked, or a slow network
     * causes a retry. Uniqueness is scoped per-shop, so shop A syncing
     * doesn't block shop B from syncing at the same time.
     */
    public function uniqueId(): string
    {
        return "collection-seo-sync-{$this->shopId}";
    }

    public int $uniqueFor = 600; // release the uniqueness lock after 10 minutes, in case a job dies without cleanly finishing

    public function handle(): void
    {
        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyCollectionService($shopifyShop);

            $cats = Cat::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->get(['cat_id', 'thirdparty_id', 'shop_id', 'cat_slug']); // include every column any Cat observer touches — see the earlier select() bug

            if ($cats->isEmpty()) {
                return;
            }

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
                    // Truncated defensively in case meta_desc is still
                    // VARCHAR(255) rather than TEXT — mb_substr, not
                    // substr, so a multi-byte UTF-8 character never gets
                    // cut in half.
                    $updates['meta_desc'] = mb_substr($seo['description'], 0, 255);
                }

                if ($updates) {
                    $cat->update($updates);
                }
            }
        } finally {
            // Clear our explicit "sync in progress" flag regardless of
            // outcome, so a failed/crashed job doesn't leave the UI
            // permanently showing "already running" for up to the full
            // 10-minute cache TTL.
            \Illuminate\Support\Facades\Cache::forget("collection_seo_sync_running_{$this->shopId}");
        }
    }
}
