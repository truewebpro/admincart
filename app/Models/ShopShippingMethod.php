<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopShippingMethod extends Model
{
    use HasFactory;
    protected $primaryKey = 'shop_shipping_method_id';
    protected $fillable = [
        'shipping_method_id',
        'custom_cost',
        'handling_fee',
        'is_enabled',
        'priority',
        'shop_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id', 'shipping_method_id');
    }
}
