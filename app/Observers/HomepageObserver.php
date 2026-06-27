<?php

namespace App\Observers;

use App\Models\Homepage;
use App\Services\ShopCacheService;

class HomepageObserver
{
    /**
     * Handle the Homepage "created" event.
     */
    public function created(Homepage $homepage): void
    {
        ShopCacheService::forgetHeroSections(
            $homepage->shop_id
        );

        ShopCacheService::forgetLazySections(
            $homepage->shop_id
        );
    }

    /**
     * Handle the Homepage "updated" event.
     */
    public function updated(Homepage $homepage): void
    {
        ShopCacheService::forgetHeroSections(
            $homepage->shop_id
        );

        ShopCacheService::forgetLazySections(
            $homepage->shop_id
        );
    }

    /**
     * Handle the Homepage "deleted" event.
     */
    public function deleted(Homepage $homepage): void
    {
        ShopCacheService::forgetHeroSections(
            $homepage->shop_id
        );

        ShopCacheService::forgetLazySections(
            $homepage->shop_id
        );
    }

}
