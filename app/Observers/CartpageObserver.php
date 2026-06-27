<?php

namespace App\Observers;

use App\Models\Cartpage;
use App\Services\ShopCacheService;

class CartpageObserver
{
    /**
     * Handle the Cartpage "created" event.
     */
    public function created(Cartpage $cartpage): void
    {
        ShopCacheService::forgetCartPage(
            $cartpage->shop_id
        );
    }

    /**
     * Handle the Cartpage "updated" event.
     */
    public function updated(Cartpage $cartpage): void
    {
        ShopCacheService::forgetCartPage(
            $cartpage->shop_id
        );
    }

    /**
     * Handle the Cartpage "deleted" event.
     */
    public function deleted(Cartpage $cartpage): void
    {
        ShopCacheService::forgetCartPage(
            $cartpage->shop_id
        );
    }

    /**
     * Handle the Cartpage "restored" event.
     */
    public function restored(Cartpage $cartpage): void
    {
        //
    }

    /**
     * Handle the Cartpage "force deleted" event.
     */
    public function forceDeleted(Cartpage $cartpage): void
    {
        //
    }
}
