<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function stock()
    {
        return $this->hasMany(Stock::class, 'variant_id', 'variant_id');
    }

    public function astock()
    {
        return $this->hasOne(Stock::class, 'variant_id', 'variant_id');
    }
}
