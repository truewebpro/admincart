<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Variant;
use App\Services\ShopCacheService;

class VariantObserver
{
    /**
     * Handle the Variant "created" event.
     */
    public function created(Variant $variant): void
    {
        $this->clearCache($variant);
    }

    /**
     * Handle the Variant "updated" event.
     */
    public function updated(Variant $variant): void
    {
        $this->clearCache($variant);
    }

    /**
     * Handle the Variant "deleted" event.
     */
    public function deleted(Variant $variant): void
    {
        $this->clearCache($variant);
    }

    /**
     * Handle the Variant "restored" event.
     */
    public function restored(Variant $variant): void
    {
        $this->clearCache($variant);
    }

    /**
     * Handle the Variant "force deleted" event.
     */
    public function forceDeleted(Variant $variant): void
    {
        $this->clearCache($variant);
    }

    protected function clearCache(Variant $variant): void
    {
        $product = Product::find($variant->product_id);

        if (!$product) {
            return;
        }

        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->handle
        );
    }
}
