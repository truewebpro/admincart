<?php

namespace App\Observers;

use App\Models\Cat;
use App\Models\CatFaq;
use App\Services\ShopCacheService;

class CatFaqObserver
{
    /**
     * Handle the CatFaq "created" event.
     */
    public function created(CatFaq $catFaq): void
    {
       $this->clearCache($catFaq);
    }

    /**
     * Handle the CatFaq "updated" event.
     */
    public function updated(CatFaq $catFaq): void
    {
        $this->clearCache($catFaq);
    }

    /**
     * Handle the CatFaq "deleted" event.
     */
    public function deleted(CatFaq $catFaq): void
    {
        $this->clearCache($catFaq);
    }

    protected function clearCache(CatFaq $catFaq): void
    {
        $cat = Cat::find($catFaq->cat_id);
        if(!$cat) {
            return;
        }
        ShopCacheService::forgetCat(
            $cat->shop_id,
            $cat->cat_slug,
        );
    }
}
