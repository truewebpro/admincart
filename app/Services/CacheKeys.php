<?php

namespace App\Services;

class CacheKeys
{
    public static function product(int $shopId, string $slug): string
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

    public static function page(int $shopId, string $slug): string {
        return "shop:$shopId:page:$slug";
    }

    public static function policy(int $shopId, string $slug): string
    {
        return "shop:$shopId:policy:$slug";
    }

    public static function heroSections(int $shopId): string
    {
        return "shop:$shopId:hero_sections";
    }

    public static function lazySections(int $shopId): string
    {
        return "shop:$shopId:lazy_sections";
    }

    public static function footer(int $shopId): string
    {
        return "shop:$shopId:footer";
    }

    public static function announcements(int $shopId): string
    {
        return "shop:$shopId:announcements";
    }

    public static function searchTags(int $shopId): string
    {
        return "shop:$shopId:search_tags";
    }

    public static function searchBrands(int $shopId): string
    {
        return "shop:$shopId:search_brands";
    }

    public static function searchCats(int $shopId): string
    {
        return "shop:$shopId:search_cats";
    }

    public static function shippingMethods(int $shopId): string
    {
        return "shop:$shopId:shipping_methods";
    }

    public static function paymentMethods(int $shopId): string
    {
        return "shop:$shopId:payment_methods";
    }

    public static function shopSettings(int $shopId): string
    {
        return "shop:$shopId:settings";
    }

    public static function homeMetas(int $shopId): string
    {
        return "shop:$shopId:homemetas";
    }

    public static function shopReviewSummary(int $shopId): string
    {
        return "shop:$shopId:shop_review_summary";
    }

    public static function reviews(int $shopId): string
    {
        return "shop:$shopId:reviews";
    }
}
