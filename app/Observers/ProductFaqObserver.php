<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductFaq;
use App\Services\ShopCacheService;

class ProductFaqObserver
{
    public function created(ProductFaq $faq): void
    {
        $this->clearCache($faq);
    }

    public function updated(ProductFaq $faq): void
    {
        $this->clearCache($faq);
    }

    public function deleted(ProductFaq $faq): void
    {
        $this->clearCache($faq);
    }

    protected function clearCache(ProductFaq $faq): void
    {
        $product = Product::find($faq->product_id);

        if (!$product) {
            return;
        }

        ShopCacheService::forgetProduct(
            $product->shop_id,
            $product->handle
        );
    }
}
