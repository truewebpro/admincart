<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acart extends Model
{
    use HasFactory;
    protected $table = 'acarts';
    protected $primaryKey = 'acart_id';
    protected $fillable = [
        'shop_id',
        'cart_token',
        'customer_id',
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
        'shipping_amount',
        'tax_amount',
        'cart_total',
        'last_activity_at',
        'checkout_id',
        'payment_method',
        'shipping_method',
        'shipping_cost',
        'expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
