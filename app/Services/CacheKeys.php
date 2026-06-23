<?php

namespace App\Services;

class CacheKeys
{

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
}
