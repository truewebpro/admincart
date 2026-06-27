<?php

namespace App\Observers;

use App\Models\Business;
use App\Models\BusinessShop;
use App\Services\ShopCacheService;

class BusinessObserver
{
    /**
     * Handle the Business "created" event.
     */
    public function created(Business $business): void
    {
        $this->updated($business);
    }

    /**
     * Handle the Business "updated" event.
     */
    public function updated(Business $business): void
    {
        BusinessShop::where('business_id', $business->business_id)
            ->pluck('shop_id')
            ->each(function ($shopId) {
                ShopCacheService::forgetFooter($shopId);
            });
    }

    /**
     * Handle the Business "deleted" event.
     */
    public function deleted(Business $business): void
    {
        $this->updated($business);
    }

}
