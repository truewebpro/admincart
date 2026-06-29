<?php

namespace App\Observers;

use App\Models\Proreview;
use App\Services\ShopCacheService;

class ProreviewObserver
{
    /**
     * Handle the Proreview "created" event.
     */
    public function created(Proreview $proreview): void
    {
        ShopCacheService::forgetProreviews(
            $proreview->shop_id
        );
    }

    /**
     * Handle the Proreview "updated" event.
     */
    public function updated(Proreview $proreview): void
    {
        ShopCacheService::forgetProreviews(
            $proreview->shop_id
        );
    }

    /**
     * Handle the Proreview "deleted" event.
     */
    public function deleted(Proreview $proreview): void
    {
        ShopCacheService::forgetProreviews(
            $proreview->shop_id
        );
    }
}
