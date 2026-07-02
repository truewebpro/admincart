<?php

namespace App\Observers;

use App\Models\Homepage;
use App\Models\HomepageFaq;
use App\Services\ShopCacheService;

class HomepageFaqObserver
{
    /**
     * Handle the HomepageFaq "created" event.
     */
    public function created(HomepageFaq $homepageFaq): void
    {
        $this->clearCache($homepageFaq);
    }

    /**
     * Handle the HomepageFaq "updated" event.
     */
    public function updated(HomepageFaq $homepageFaq): void
    {
        $this->clearCache($homepageFaq);
    }

    /**
     * Handle the HomepageFaq "deleted" event.
     */
    public function deleted(HomepageFaq $homepageFaq): void
    {
        $this->clearCache($homepageFaq);
    }

    protected function clearCache(HomepageFaq $homepageFaq): void
    {
        $homepage = Homepage::find($homepageFaq->homepage_id);

        if(!$homepage) {
            return;
        }
        ShopCacheService::forgetHeroSections(
            $homepage->shop_id
        );
    }
}
