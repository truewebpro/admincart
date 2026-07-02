<?php

namespace App\Observers;

use App\Models\Cat;
use App\Models\RelatedCat;
use App\Services\ShopCacheService;

class RelatedCatObserver
{
    /**
     * Handle the RelatedCat "created" event.
     */
    public function created(RelatedCat $relatedCat): void
    {
        $this->clearCache($relatedCat);
    }

    /**
     * Handle the RelatedCat "updated" event.
     */
    public function updated(RelatedCat $relatedCat): void
    {
        $this->clearCache($relatedCat);
    }

    /**
     * Handle the RelatedCat "deleted" event.
     */
    public function deleted(RelatedCat $relatedCat): void
    {
        $this->clearCache($relatedCat);
    }

    /**
     * Handle the RelatedCat "restored" event.
     */
    public function restored(RelatedCat $relatedCat): void
    {
        $this->clearCache($relatedCat);
    }

    /**
     * Handle the RelatedCat "force deleted" event.
     */
    public function forceDeleted(RelatedCat $relatedCat): void
    {
        $this->clearCache($relatedCat);
    }

    protected function clearCache(RelatedCat $relatedCat): void
    {
        $cat = Cat::find($relatedCat->cat_id);
        if(!$cat) {
            return;
        }

        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug,
        );
    }
}
