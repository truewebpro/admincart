<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ShopCacheService
{
    public static function forgetProduct(int $shopId, string $slug): void
    {
        Cache::forget("shop:{$shopId}:product:{$slug}");
        Cache::forget("shop:{$shopId}:product-sections:{$slug}");
    }

    public static function forgetCategory(int $shopId, string $slug): void
    {
        Cache::forget("shop:{$shopId}:category:{$slug}");
        Cache::forget("shop:{$shopId}:category-products:{$slug}");
        Cache::forget("shop:{$shopId}:categories");
    }

    public static function forgetBrand(int $shopId, string $slug): void
    {
        Cache::forget(CacheKeys::brands($shopId));

        Cache::forget(CacheKeys::brand($shopId, $slug));

        Cache::forget(CacheKeys::brandProducts($shopId, $slug));

        Cache::forget(CacheKeys::brandSections($shopId, $slug));

        Cache::forget(CacheKeys::brandPage($shopId, $slug));
    }

    public static function forgetBlog(int $shopId, string $slug): void
    {
        Cache::forget("shop:{$shopId}:blog:{$slug}");
        Cache::forget("shop:{$shopId}:blogs");
    }

    public static function forgetPage(int $shopId, string $slug): void
    {
        Cache::forget("shop:{$shopId}:page:{$slug}");
    }

    public static function forgetMenu(int $shopId): void
    {
        Cache::forget(CacheKeys::mainMenu($shopId));
    }

    public static function forgetShop(int $shopId): void
    {
        Cache::forget("shop:{$shopId}:settings");
        Cache::forget("shop:{$shopId}:homepage");
        Cache::forget("shop:{$shopId}:homemetas");
        Cache::forget("shop:{$shopId}:footer");
        Cache::forget("shop:{$shopId}:menu");
    }

}
