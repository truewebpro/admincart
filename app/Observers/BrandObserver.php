<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\ShopCacheService;

class BrandObserver
{
    public function created(Brand $brand): void
    {
        ShopCacheService::forgetProductFilters(
            $brand->shop_id
        );

        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
    }

    public function updated(Brand $brand): void
    {
        ShopCacheService::forgetProductFilters(
            $brand->shop_id
        );

        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
        if ($brand->wasChanged('brand_slug')) {
            ShopCacheService::forgetBrand(
                $brand->shop_id,
                $brand->getOriginal('brand_slug')
            );
        }
    }

    public function deleted(Brand $brand): void
    {
        ShopCacheService::forgetProductFilters(
            $brand->shop_id
        );

        ShopCacheService::forgetBrand(
            $brand->shop_id,
            $brand->brand_slug
        );
    }
}
