<?php

namespace App\Observers;

use App\Models\Blog;
use App\Services\ShopCacheService;

class BlogObserver
{
    /**
     * Handle the Blog "created" event.
     */
    public function created(Blog $blog): void
    {
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
    }

    /**
     * Handle the Blog "updated" event.
     */
    public function updated(Blog $blog): void
    {
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
        if ($blog->wasChanged('blog_slug')) {
            ShopCacheService::forgetBlog(
                $blog->shop_id,
                $blog->getOriginal('blog_slug')
            );
        }
    }

    /**
     * Handle the Blog "deleted" event.
     */
    public function deleted(Blog $blog): void
    {
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
    }

    /**
     * Handle the Blog "restored" event.
     */
    public function restored(Blog $blog): void
    {
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
    }

    /**
     * Handle the Blog "force deleted" event.
     */
    public function forceDeleted(Blog $blog): void
    {
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
    }
}
