<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\BlogFaq;
use App\Services\ShopCacheService;

class BlogFaqObserver
{
    /**
     * Handle the BlogFaq "created" event.
     */
    public function created(BlogFaq $blogFaq): void
    {
        $this->clearCache($blogFaq);
    }

    /**
     * Handle the BlogFaq "updated" event.
     */
    public function updated(BlogFaq $blogFaq): void
    {
        $this->clearCache($blogFaq);
    }

    /**
     * Handle the BlogFaq "deleted" event.
     */
    public function deleted(BlogFaq $blogFaq): void
    {
        $this->clearCache($blogFaq);
    }

    protected function clearCache(BlogFaq $blogFaq): void
    {
        $blog = Blog::find($blogFaq->blog_id);
        if(!$blog){
            return;
        }
        ShopCacheService::forgetBlog(
            $blog->shop_id,
            $blog->blog_slug
        );
    }
}
