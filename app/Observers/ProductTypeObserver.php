<?php

namespace App\Observers;

use App\Models\ProductType;
use App\Services\ShopCacheService;

class ProductTypeObserver
{
    public function created(ProductType $ptype): void
    {
        ShopCacheService::forgetProductFilters(
            $ptype->shop_id
        );
    }

    public function updated(ProductType $ptype): void
    {
        ShopCacheService::forgetProductFilters(
            $ptype->shop_id
        );
    }

    public function deleted(ProductType $ptype): void
    {
        ShopCacheService::forgetProductFilters(
            $ptype->shop_id
        );
    }
}
