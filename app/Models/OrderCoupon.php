<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{
    use HasFactory;
    protected $table = 'order_coupons';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'type',
        'value',
        'priority',
        'shop_id',
    ];

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'coupon_id', 'coupon_id')
            ->select(['coupon_id', 'code', 'display_title', 'type']);
    }
}
