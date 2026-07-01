<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\PageFaq;
use App\Services\ShopCacheService;

class PageFaqObserver
{
    /**
     * Handle the PageFaq "created" event.
     */
    public function created(PageFaq $pageFaq): void
    {
        $this->clearCache($pageFaq);
    }

    /**
     * Handle the PageFaq "updated" event.
     */
    public function updated(PageFaq $pageFaq): void
    {
        $this->clearCache($pageFaq);
    }

    /**
     * Handle the PageFaq "deleted" event.
     */
    public function deleted(PageFaq $pageFaq): void
    {
        $this->clearCache($pageFaq);
    }
    protected function clearCache(PageFaq $pageFaq): void
    {
        $page = Page::find($pageFaq->page_id);

        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->page_slug
        );
    }
}
