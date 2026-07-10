<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Specific;
use App\Services\ShopCacheService;

class SpecificObserver
{
    /**
     * Handle the Specific "created" event.
     */
    public function created(Specific $specific): void
    {
        $this->clearCache($specific);
    }

    /**
     * Handle the Specific "updated" event.
     */
    public function updated(Specific $specific): void
    {
        $this->clearCache($specific);
    }

    /**
     * Handle the Specific "deleted" event.
     */
    public function deleted(Specific $specific): void
    {
        $this->clearCache($specific);
    }

    protected function clearCache(Specific $specific): void
    {
        $product = Product::find($specific->product_id);

        if (!$product) {
            return;
        }

        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->handle
        );
    }
}
