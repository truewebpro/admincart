<?php

namespace App\Observers;

use App\Models\Cat;
use App\Services\ShopCacheService;

class CatObserver
{
    /**
     * Handle the Cat "created" event.
     */
    public function created(Cat $cat): void
    {
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug
        );
        ShopCacheService::forgetHtmlSitemap(
            $cat->shop_id
        );
    }

    /**
     * Handle the Cat "updated" event.
     */
    public function updated(Cat $cat): void
    {
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug
        );
        ShopCacheService::forgetHtmlSitemap(
            $cat->shop_id
        );
    }

    /**
     * Handle the Cat "deleted" event.
     */
    public function deleted(Cat $cat): void
    {
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug
        );
        ShopCacheService::forgetHtmlSitemap(
            $cat->shop_id
        );
    }

    /**
     * Handle the Cat "restored" event.
     */
    public function restored(Cat $cat): void
    {
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug
        );
    }

    /**
     * Handle the Cat "force deleted" event.
     */
    public function forceDeleted(Cat $cat): void
    {
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug
        );
    }
}
