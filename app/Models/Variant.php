<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variant extends Model
{
    use HasFactory;
    protected $primaryKey = 'variant_id';
    protected $fillable = [
        'sku',
        'price',
        'compareprice',
        'costprice',
        'barcode',
        'variant_image',
        'istax',
        'isdefault',
        'weight',
        'options',
        'option_values',
        'product_id',
        'shop_id',
    ];

    protected $casts = [
        'options' => 'array',
        'option_values' => 'array',
        'istax' => 'boolean',
        'isdefault' => 'boolean',
    ];
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'variant_id', 'variant_id');
    }

    public function astock()
    {
        return $this->hasOne(Stock::class, 'variant_id', 'variant_id');
    }

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
