<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\ShopCacheService;

class PageObserver
{
    /**
     * Handle the Page "created" event.
     */
    public function created(Page $page): void
    {
        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->slug
        );
    }

    /**
     * Handle the Page "updated" event.
     */
    public function updated(Page $page): void
    {
        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->slug
        );
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->slug
        );
    }

    /**
     * Handle the Page "restored" event.
     */
    public function restored(Page $page): void
    {
        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->slug
        );
    }

    /**
     * Handle the Page "force deleted" event.
     */
    public function forceDeleted(Page $page): void
    {
        ShopCacheService::forgetPage(
            $page->shop_id,
            $page->slug
        );
    }
}
