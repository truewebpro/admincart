<?php

namespace App\Observers;

use App\Models\Coupon;
use App\Services\PromoLabelService;

/**
 * Fires on ANY Coupon save/delete — regardless of which code path triggers
 * it (admin controller, an import job, artisan tinker, future features).
 * Keeps cache invalidation from depending on every caller remembering to
 * flush manually.
 */
class CouponObserver
{
    public function __construct(protected PromoLabelService $labels) {}

    public function saved(Coupon $coupon): void
    {
        $this->labels->flush($coupon->shop_id);
    }

    public function deleted(Coupon $coupon): void
    {
        $this->labels->flush($coupon->shop_id);
    }
}
