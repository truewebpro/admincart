<?php

namespace App\Observers;

use App\Models\Highlight;
use App\Models\Product;
use App\Services\ShopCacheService;

class HighlightObserver
{
    /**
     * Handle the Highlight "created" event.
     */
    public function created(Highlight $highlight): void
    {
        $this->clearCache($highlight);
    }

    /**
     * Handle the Highlight "updated" event.
     */
    public function updated(Highlight $highlight): void
    {
        $this->clearCache($highlight);
    }

    /**
     * Handle the Highlight "deleted" event.
     */
    public function deleted(Highlight $highlight): void
    {
        $this->clearCache($highlight);
    }

    protected function clearCache(Highlight $highlight): void
    {
        $product = Product::find($highlight->product_id);

        if (!$product) {
            return;
        }

        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->handle
        );
    }
}
