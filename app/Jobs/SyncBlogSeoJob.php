<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksJobRun;
use App\Models\Blog;
use App\Models\ShopifyShop;
use App\Services\ShopifyBlogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBlogSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksJobRun;

    public int $tries = 1;
    public int $timeout = 280;

    protected const JOB_TYPE = 'blog_seo_sync';

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
            $service = new ShopifyBlogService($shopifyShop);

            $blogs = Blog::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->get(['blog_id', 'thirdparty_id', 'shop_id', 'blog_slug']); // every column BlogObserver touches

            $updated = 0;

            if ($blogs->isNotEmpty()) {
                $shopifyIds = $blogs->pluck('thirdparty_id')->map(fn ($id) => (int) $id)->all();
                $seoData = $service->getArticlesSeo($shopifyIds);

                foreach ($blogs as $blog) {
                    $seo = $seoData[(int) $blog->thirdparty_id] ?? null;

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
                        $blog->update($updates);
                        $updated++;
                    }
                }
            }

            $this->completeRun($run, [
                'updated' => $updated,
                'checked' => $blogs->count(),
            ]);
        } catch (\Throwable $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }
}
