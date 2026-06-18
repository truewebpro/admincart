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
        //
    }

    /**
     * Handle the Cat "updated" event.
     */
    public function updated(Cat $cat): void
    {
        ShopCacheService::forgetCategory(
            $cat->shop_id,
            $cat->slug
        );
    }

    /**
     * Handle the Cat "deleted" event.
     */
    public function deleted(Cat $cat): void
    {
        ShopCacheService::forgetCategory(
            $cat->shop_id,
            $cat->slug
        );
    }

    /**
     * Handle the Cat "restored" event.
     */
    public function restored(Cat $cat): void
    {
        ShopCacheService::forgetCategory(
            $cat->shop_id,
            $cat->slug
        );
    }

    /**
     * Handle the Cat "force deleted" event.
     */
    public function forceDeleted(Cat $cat): void
    {
        ShopCacheService::forgetCategory(
            $cat->shop_id,
            $cat->slug
        );
    }
}
