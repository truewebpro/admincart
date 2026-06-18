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
}
