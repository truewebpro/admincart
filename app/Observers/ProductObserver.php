<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ShopCacheService;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->slug
        );
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->slug
        );
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->slug
        );
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->slug
        );
    }
}
