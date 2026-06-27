<?php

namespace App\Observers;

use App\Models\ShopPaymentMethod;
use App\Services\ShopCacheService;

class ShopPaymentMethodObserver
{
    /**
     * Handle the ShopPaymentMethod "created" event.
     */
    public function created(ShopPaymentMethod $method): void
    {
        ShopCacheService::forgetPaymentMethods(
            $method->shop_id
        );
    }

    /**
     * Handle the ShopPaymentMethod "updated" event.
     */
    public function updated(ShopPaymentMethod $method): void
    {
        ShopCacheService::forgetPaymentMethods(
            $method->shop_id
        );
    }

    /**
     * Handle the ShopPaymentMethod "deleted" event.
     */
    public function deleted(ShopPaymentMethod $method): void
    {
        ShopCacheService::forgetPaymentMethods(
            $method->shop_id
        );
    }
}
