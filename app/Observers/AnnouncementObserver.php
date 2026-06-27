<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Services\ShopCacheService;

class AnnouncementObserver
{
    /**
     * Handle the Announcement "created" event.
     */
    public function created(Announcement $announcement): void
    {
        ShopCacheService::forgetAnnouncements(
            $announcement->shop_id
        );
    }

    /**
     * Handle the Announcement "updated" event.
     */
    public function updated(Announcement $announcement): void
    {
        ShopCacheService::forgetAnnouncements(
            $announcement->shop_id
        );
    }

    /**
     * Handle the Announcement "deleted" event.
     */
    public function deleted(Announcement $announcement): void
    {
        ShopCacheService::forgetAnnouncements(
            $announcement->shop_id
        );
    }
}
