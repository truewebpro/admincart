<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [
        'order_number',
        'shop_id',
        'customer_id',
        'address_id',
        'order_status',
        'label_status',
        'payment_method',
        'payment_status',
        'fulfillment_status',
        'shipping_method',
        'shipping_cost',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'coupon_discount',
        'subtotal',
        'order_total',
        'tax_amount',
        'currency_code',
        'is_guest_order',
        'shipping_name',
        'shipping_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'notes',
        'checkout_id',
        'parcel_id',
        'tracking_number',
        'shipment_id',
        'shipment_name',
        'placed_at',
        'payment_fee',
        'shipping_protection_fee',
    ];

    protected $casts = [
        'order_number' => 'string',
        'is_guest_order' => 'boolean',
        'placed_at' => 'datetime',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public function orderAddress()
    {
        return $this->hasOne(CustomerAddress::class,'address_id','address_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id', 'address_id');
    }

    public function orderCoupons(): HasMany
    {
        return $this->hasMany(OrderCoupon::class, 'order_id', 'order_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'coupon_id');
    }

    public function draftOrder(): HasOne
    {
        return $this->hasOne(DraftOrder::class, 'converted_order_id', 'order_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function trackingEvents():HasMany
    {
        return $this->hasMany(OrderTrackingEvent::class, 'order_id', 'order_id')
            ->orderBy('event_at','asc');
    }
}
