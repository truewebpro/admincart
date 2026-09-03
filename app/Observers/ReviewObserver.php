<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Review;
use App\Services\ShopCacheService;

class ReviewObserver
{
    public function created(Review $review): void
    {
        ShopCacheService::forgetReviews(
            $review->shop_id
        );
        $product = Product::where('product_id',$review->product_id)->first();
        if ($product) {
            ShopCacheService::forgetProduct(
                $review->shop_id,
                $product->handle
            );
        }

    }

    public function updated(Review $review): void
    {
        ShopCacheService::forgetReviews(
            $review->shop_id
        );
        $product = Product::where('product_id',$review->product_id)->first();
        if ($product) {
            ShopCacheService::forgetProduct(
                $review->shop_id,
                $product->handle
            );
        }
    }

    public function deleted(Review $review): void
    {
        ShopCacheService::forgetReviews(
            $review->shop_id
        );
        $product = Product::where('product_id',$review->product_id)->first();
        if ($product) {
            ShopCacheService::forgetProduct(
                $review->shop_id,
                $product->handle
            );
        }
    }

}
