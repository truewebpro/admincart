<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $table = 'coupons';
    protected $primaryKey = 'coupon_id';
    protected $fillable = [
        'shop_id',
        'code',
        'type',
        'value',
        'applies_to',
        'min_order_amount',
        'is_active',
        'starts_at',
        'expires_at',
        'usage_limit',
        'per_customer_limit',
        'is_stackable',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
        'conditions' => 'array',
    ];
}
