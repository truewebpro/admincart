<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponCat extends Model
{
    use HasFactory;
    protected $table = 'coupon_cats';
    protected $primaryKey = 'coupon_cat_id';
    protected $fillable = [
        'coupon_id',
        'cat_id',
    ];
}
