<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;
    protected $primaryKey = 'shipping_method_id';
    protected $fillable = [
        'shipping_method_name',
        'shipping_group',
        'delivery_time',
        'base_cost',
        'is_active',
        'courier_id',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class,'courier_id','courier_id');
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_shipping_methods', 'shipping_method_id', 'shop_id')
            ->withPivot(['custom_cost', 'is_enabled', 'handling_fee','priority']);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
