<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    use HasFactory;
    protected $table = "carts";
    protected $primaryKey = "cart_id";
    protected $fillable = [
        'shop_id',
        'order_id',
        'is_active',
        'customer_id',
        'address_id',
        'order_status',
        'payment_method',
        'payment_status',
        'fulfillment_status',
        'shipping_method',
        'shipping_cost',
        'coupon_id',
        'coupon_code',
        'discount_amount',
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
        'placed_at',
    ];

    protected $casts = [
        'is_guest_order' => 'boolean',
        'placed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'cart_id', 'cart_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'customer_id');
    }

    public function orderAddress()
    {
        return $this->hasOne(CustomerAddress::class,'address_id','address_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }
}
