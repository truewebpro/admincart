<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceTier extends Model
{
    use HasFactory;
    protected $table = 'product_price_tiers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_id',
        'shop_id',
        'min_qty',
        'price',
        'pricing_type',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'shop_id'
    ];

    protected $casts = [
        'price' => 'float',
    ];
}
