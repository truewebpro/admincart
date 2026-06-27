<?php

namespace App\Observers;

use App\Models\Footer;
use App\Services\ShopCacheService;

class FooterObserver
{
    /**
     * Handle the Footer "created" event.
     */
    public function created(Footer $footer): void
    {
        ShopCacheService::forgetFooter(
            $footer->shop_id
        );
    }

    /**
     * Handle the Footer "updated" event.
     */
    public function updated(Footer $footer): void
    {
        ShopCacheService::forgetFooter(
            $footer->shop_id
        );
    }

    /**
     * Handle the Footer "deleted" event.
     */
    public function deleted(Footer $footer): void
    {
        ShopCacheService::forgetFooter(
            $footer->shop_id
        );
    }

}
