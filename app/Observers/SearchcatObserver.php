<?php

namespace App\Observers;

use App\Models\Searchcat;
use App\Services\ShopCacheService;

class SearchcatObserver
{
    /**
     * Handle the Searchcat "created" event.
     */
    public function created(Searchcat $searchcat): void
    {
        ShopCacheService::forgetSearchCats(
            $searchcat->shop_id
        );
    }

    /**
     * Handle the Searchcat "updated" event.
     */
    public function updated(Searchcat $searchcat): void
    {
        ShopCacheService::forgetSearchCats(
            $searchcat->shop_id
        );
    }

    /**
     * Handle the Searchcat "deleted" event.
     */
    public function deleted(Searchcat $searchcat): void
    {
        ShopCacheService::forgetSearchCats(
            $searchcat->shop_id
        );
    }
}
