<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'title',
        'options',
        'price',
        'quantity',
        'total',
        'allocated_quantity',
        'backorder_quantity',
        'shipped_quantity',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'variant_id');
    }
}
