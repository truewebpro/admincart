<?php

namespace App\Observers;

use App\Models\Preference;
use App\Services\ShopCacheService;

class PreferenceObserver
{
    /**
     * Handle the Preference "created" event.
     */
    public function created(Preference $preference): void
    {
        ShopCacheService::forgetShop(
            $preference->shop_id
        );
    }

    /**
     * Handle the Preference "updated" event.
     */
    public function updated(Preference $preference): void
    {
        ShopCacheService::forgetShop(
            $preference->shop_id
        );
    }

    /**
     * Handle the Preference "deleted" event.
     */
    public function deleted(Preference $preference): void
    {
        ShopCacheService::forgetShop(
            $preference->shop_id
        );
    }
}
