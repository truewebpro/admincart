<?php

namespace App\Observers;

use App\Models\Searchtag;
use App\Services\ShopCacheService;

class SearchtagObserver
{
    /**
     * Handle the Searchtag "created" event.
     */
    public function created(Searchtag $searchtag): void
    {
        ShopCacheService::forgetSearchTags(
            $searchtag->shop_id
        );
    }

    /**
     * Handle the Searchtag "updated" event.
     */
    public function updated(Searchtag $searchtag): void
    {
        ShopCacheService::forgetSearchTags(
            $searchtag->shop_id
        );
    }

    /**
     * Handle the Searchtag "deleted" event.
     */
    public function deleted(Searchtag $searchtag): void
    {
        ShopCacheService::forgetSearchTags(
            $searchtag->shop_id
        );
    }
}
