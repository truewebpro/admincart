<?php

namespace App\Observers;

use App\Models\Brand;
use App\Models\BrandFaq;
use App\Services\ShopCacheService;

class BrandFaqObserver
{
    /**
     * Handle the BrandFaq "created" event.
     */
    public function created(BrandFaq $brandFaq): void
    {
        $this->clearCache($brandFaq);
    }

    /**
     * Handle the BrandFaq "updated" event.
     */
    public function updated(BrandFaq $brandFaq): void
    {
        $this->clearCache($brandFaq);
    }

    /**
     * Handle the BrandFaq "deleted" event.
     */
    public function deleted(BrandFaq $brandFaq): void
    {
        $this->clearCache($brandFaq);
    }

    protected function clearCache(BrandFaq $brandFaq): void
    {
        $brand = Brand::find($brandFaq->brand_id);
        if(!$brand) return;
        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
    }
}
