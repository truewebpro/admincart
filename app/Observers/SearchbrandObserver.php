<?php

namespace App\Observers;

use App\Models\Searchbrand;
use App\Services\ShopCacheService;

class SearchbrandObserver
{
    /**
     * Handle the Searchbrand "created" event.
     */
    public function created(Searchbrand $searchbrand): void
    {
        ShopCacheService::forgetSearchBrands(
            $searchbrand->shop_id
        );
    }

    /**
     * Handle the Searchbrand "updated" event.
     */
    public function updated(Searchbrand $searchbrand): void
    {
        ShopCacheService::forgetSearchBrands(
            $searchbrand->shop_id
        );
    }

    /**
     * Handle the Searchbrand "deleted" event.
     */
    public function deleted(Searchbrand $searchbrand): void
    {
        ShopCacheService::forgetSearchBrands(
            $searchbrand->shop_id
        );
    }
}
