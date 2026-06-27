<?php

namespace App\Observers;

use App\Models\BusinessShop;
use App\Services\ShopCacheService;

class BusinessShopObserver
{
    /**
     * Handle the BusinessShop "created" event.
     */
    public function created(BusinessShop $businessShop): void
    {
        ShopCacheService::forgetFooter(
            $businessShop->shop_id
        );
    }

    /**
     * Handle the BusinessShop "updated" event.
     */
    public function updated(BusinessShop $businessShop): void
    {
        ShopCacheService::forgetFooter(
            $businessShop->shop_id
        );
    }

    /**
     * Handle the BusinessShop "deleted" event.
     */
    public function deleted(BusinessShop $businessShop): void
    {
        ShopCacheService::forgetFooter(
            $businessShop->shop_id
        );
    }

}
