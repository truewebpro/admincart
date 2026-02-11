<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponProduct extends Model
{
    use HasFactory;
    protected $table = 'coupon_products';
    protected $primaryKey = 'coupon_product_id';
    protected $fillable = [
        'coupon_id',
        'product_id',
    ];
}
