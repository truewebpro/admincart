<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Models\ShopifyShop;
use App\Services\ShopifyBlogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncBlogSeoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 280;

    public function __construct(protected int $shopId)
    {
    }

    public function uniqueId(): string
    {
        return "blog-seo-sync-{$this->shopId}";
    }

    public int $uniqueFor = 600;

    public function handle(): void
    {
        try {
            $shopifyShop = ShopifyShop::where('shop_id', $this->shopId)->firstOrFail();
            $service = new ShopifyBlogService($shopifyShop);

            // Include every column BlogObserver touches, same reasoning
            // as the products/collections select() fix.
            $blogs = Blog::where('shop_id', $this->shopId)
                ->whereNotNull('thirdparty_id')
                ->get(['blog_id', 'thirdparty_id', 'shop_id', 'blog_slug']);

            if ($blogs->isEmpty()) {
                return;
            }

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
                    // Same fix as products — this is exactly what was
                    // missing before, causing the "Data too long for
                    // column meta_desc" crash reported for blogs.
                    $updates['meta_desc'] = mb_substr($seo['description'], 0, 255);
                }

                if ($updates) {
                    $blog->update($updates);
                }
            }
        } finally {
            Cache::forget("blog_seo_sync_running_{$this->shopId}");
        }
    }
}
