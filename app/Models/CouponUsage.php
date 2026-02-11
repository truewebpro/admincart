<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;
    protected $table = 'coupon_usages';
    protected $primaryKey = 'coupon_usage_id';
    protected $fillable = [
        'coupon_id',
        'customer_id',
        'times_used',
    ];
}
