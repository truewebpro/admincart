<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcartDiscount extends Model
{
    use HasFactory;
    protected $table = "acart_discounts";
    protected $primaryKey = "acart_discount_id";
    protected $fillable = [
        'acart_id',
        'shop_id',
        'discount_code',
        'discount_source',
        'discount_type',
        'discount_value',
        'applied_amount',
        'is_stackable',
        'meta',
        'discount_status',
        'expires_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_stackable' => 'boolean',
    ];
}
