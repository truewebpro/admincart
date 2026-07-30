<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'cshop_id',
        'business_name',
        'vat_number',
        'customer_group',
        'credit_limit',
        'outstanding_balance',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'outstanding_balance' => 'float',
    ];

    public function customerShop(): BelongsTo
    {
        return $this->belongsTo(CustomerShop::class, 'cshop_id', 'cshop_id');
    }

    /**
     * Not stored directly — always derived from credit_limit minus the
     * cached outstanding_balance, so the two can never drift out of sync
     * with each other (only outstanding_balance needs to be kept fresh
     * by the payment/order services).
     */
    public function getAvailableCreditAttribute(): float
    {
        return (float) bcsub((string) $this->credit_limit, (string) $this->outstanding_balance, 2);
    }


    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }


}
