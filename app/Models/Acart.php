<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acart extends Model
{
    use HasFactory;
    protected $table = 'acarts';
    protected $primaryKey = 'acart_id';
    protected $fillable = [
        'shop_id',
        'cart_token',
        'customer_id',
        'address_id',
        'order_id',
        'is_active',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'currency',
        'cart_status',
        'cart_version',
        'items_count',
        'subtotal',
        'discount_amount',
        'coupon_discount',
        'rule_discount',
        'tax_amount',
        'cart_total',
        'last_activity_at',
        'checkout_id',
        'payment_method',
        'payment_fee',
        'shipping_method',
        'shipping_cost',
        'shipping_protection_enabled',
        'shipping_protection_fee',
        'notes',
        'expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'shipping_protection_enabled' => 'boolean',
        'shipping_protection_fee' => 'float',
        'payment_fee' => 'float',
    ];

    public function items(){
        return $this->hasMany(AcartItem::class, 'acart_id', 'acart_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public function aevents(): HasMany
    {
        return $this->hasMany(AcartEvent::class, 'acart_id', 'acart_id')
            ->orderBy('created_at', 'DESC')
            ->orderBy('acart_event_id', 'DESC');;
    }

    public function applied_coupons(): HasMany
    {
        return $this->hasMany(AcartCoupon::class, 'acart_id', 'acart_id');
    }
}
