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

    public static function forgetProductFilters(int $shopId): void
    {
        Cache::forget(CacheKeys::productFilters($shopId));
    }

    public static function forgetCat(int $shopId, string $slug): void
    {
        Cache::forget(CacheKeys::cats($shopId));
        Cache::forget(CacheKeys::cat($shopId,$slug));
        Cache::forget(CacheKeys::catSections($shopId,$slug));
        Cache::forget(CacheKeys::catSectionsData($shopId,$slug));
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

    public static function forgetPage(int $shopId, string $slug): void {
        Cache::forget(CacheKeys::page($shopId, $slug));
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

    public static function forgetCartPage(int $shopId): void {
        Cache::forget(CacheKeys::cartPage($shopId));
    }

    public static function forgetHtmlSitemap(int $shopId): void
    {
        Cache::forget(CacheKeys::htmlSitemap($shopId));
    }

    public static function forgetPolicy(int $shopId, string $slug): void
    {
        Cache::forget(CacheKeys::policy($shopId, $slug));
    }

    public static function forgetHeroSections(int $shopId): void {
        Cache::forget(CacheKeys::heroSections($shopId));
    }

    public static function forgetLazySections(int $shopId): void {
        Cache::forget(CacheKeys::lazySections($shopId));
    }

    public static function forgetFooter(int $shopId): void
    {
        Cache::forget(CacheKeys::footer($shopId));
    }

    public static function forgetAnnouncements(int $shopId): void
    {
        Cache::forget(CacheKeys::announcements($shopId));
    }

}
