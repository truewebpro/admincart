<?php

namespace App\Observers;

use App\Models\CustomerShop;
use App\Services\MailtrapService;
use Illuminate\Support\Facades\Log;

class CustomerShopObserver
{
    public function created(CustomerShop $customerShop): void
    {
        $customerShop->load([
            'customer',
            'shop',
        ]);

        app(MailtrapService::class)->syncCustomer($customerShop);
    }
}
