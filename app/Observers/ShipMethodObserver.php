<?php

namespace App\Observers;

use App\Models\ShipMethod;
use App\Services\ShopCacheService;

class ShipMethodObserver
{
    /**
     * Handle the ShipMethod "created" event.
     */
    public function created(ShipMethod $method): void
    {
        ShopCacheService::forgetShippingMethods(
            $method->shop_id
        );
    }

    /**
     * Handle the ShipMethod "updated" event.
     */
    public function updated(ShipMethod $method): void
    {
        ShopCacheService::forgetShippingMethods(
            $method->shop_id
        );
    }

    /**
     * Handle the ShipMethod "deleted" event.
     */
    public function deleted(ShipMethod $method): void
    {
        ShopCacheService::forgetShippingMethods(
            $method->shop_id
        );
    }

}
