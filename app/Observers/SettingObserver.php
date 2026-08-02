<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\ShopCacheService;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        ShopCacheService::forgetShop(
            $setting->shop_id
        );
        Cache::forget("shop_vat_{$setting->shop_id}");

    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        ShopCacheService::forgetShop(
            $setting->shop_id
        );
        Cache::forget("shop_vat_{$setting->shop_id}");
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        ShopCacheService::forgetShop(
            $setting->shop_id
        );
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        ShopCacheService::forgetShop(
            $setting->shop_id
        );
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        ShopCacheService::forgetShop(
            $setting->shop_id
        );
    }
}
