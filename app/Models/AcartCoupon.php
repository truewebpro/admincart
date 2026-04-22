<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcartCoupon extends Model
{
    use HasFactory;
    protected $table = 'acart_coupons';
    protected $primaryKey = 'id';
    protected $fillable = [
        'acart_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'type',
        'value',
        'priority',
        'shop_id'
    ];
}
