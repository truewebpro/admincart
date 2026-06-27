<?php

namespace App\Observers;

use App\Models\Policy;
use App\Services\ShopCacheService;

class PolicyObserver
{
    /**
     * Handle the Policy "created" event.
     */
    public function created(Policy $policy): void
    {
        ShopCacheService::forgetPolicy(
            $policy->shop_id,
            $policy->policy_slug
        );
    }

    /**
     * Handle the Policy "updated" event.
     */
    public function updated(Policy $policy): void
    {
        ShopCacheService::forgetPolicy(
            $policy->shop_id,
            $policy->policy_slug
        );
        if ($policy->wasChanged('policy_slug')) {
            ShopCacheService::forgetPolicy(
                $policy->shop_id,
                $policy->getOriginal('policy_slug')
            );
        }
    }

    /**
     * Handle the Policy "deleted" event.
     */
    public function deleted(Policy $policy): void
    {
        ShopCacheService::forgetPolicy(
            $policy->shop_id,
            $policy->policy_slug
        );
    }

    /**
     * Handle the Policy "restored" event.
     */
    public function restored(Policy $policy): void
    {
        //
    }

    /**
     * Handle the Policy "force deleted" event.
     */
    public function forceDeleted(Policy $policy): void
    {
        //
    }
}
