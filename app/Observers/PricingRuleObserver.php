<?php

namespace App\Observers;

use App\Models\PricingRule;
use App\Services\PromoLabelService;

class PricingRuleObserver
{
    public function __construct(protected PromoLabelService $labels) {}

    public function saved(PricingRule $rule): void
    {
        $this->labels->flush($rule->shop_id);
    }

    public function deleted(PricingRule $rule): void
    {
        $this->labels->flush($rule->shop_id);
    }
}
