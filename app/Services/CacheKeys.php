<?php

namespace App\Services;

class CacheKeys
{
    public static function product($shopId, $slug): string
    {
        return "shop:$shopId:product:$slug";
    }

    public static function productSections(int $shopId, string $slug): string
    {
        return "shop:$shopId:product-sections:$slug";
    }

    public static function productFilters(int $shopId): string
    {
        return "shop:$shopId:product_filters";
    }

    public static function brands($shopId): string
    {
        return "shop:$shopId:brands";
    }

    public static function brand($shopId, $slug): string
    {
        return "shop:$shopId:brand:$slug";
    }

    public static function brandProducts($shopId, $slug): string
    {
        return "shop:$shopId:brand-products:$slug";
    }

    public static function brandSections($shopId, $slug): string
    {
        return "shop:$shopId:brand-sections:$slug";
    }

    public static function brandPage($shopId, $slug): string
    {
        return "shop:$shopId:brand-page:$slug";
    }

    public static function mainMenu($shopId): string
    {
        return "shop:$shopId:main_menu";
    }

    public static function cats(int $shopId): string
    {
        return "shop:$shopId:cats";
    }

    public static function cat(int $shopId, string $slug): string
    {
        return "shop:$shopId:cat:$slug";
    }

    public static function catSections(int $shopId, string $slug): string {
        return "shop:$shopId:cat_sections:$slug";
    }

    public static function catSectionsData(int $shopId, string $slug): string {
        return "shop:$shopId:cat_sections_data:$slug";
    }

    public static function blogs(int $shopId): string
    {
        return "shop:$shopId:blogs";
    }

    public static function blog(int $shopId, string $slug): string
    {
        return "shop:$shopId:blog:$slug";
    }

    public static function cartPage(int $shopId): string
    {
        return "shop:$shopId:cart_page";
    }

    public static function htmlSitemap(int $shopId): string
    {
        return "shop:$shopId:html_sitemap";
    }
}
