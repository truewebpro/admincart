<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\ShopCacheService;

class BrandObserver
{
    /**
     * Handle the Brand "saved" event.
     */
    public function saved(Brand $brand): void
    {
        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
    }

}
