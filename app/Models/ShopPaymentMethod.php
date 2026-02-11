<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopPaymentMethod extends Model
{
    use HasFactory;
    protected $table = 'shop_payment_methods';
    protected $primaryKey = 'shop_payment_method_id';
    protected $fillable = [
        'payment_name',
        'payment_method',
        'payment_icon',
        'payment_options',
        'payment_status',
        'sort_order',
        'shop_id'
    ];

    protected $casts = [
        'payment_options' => 'array',
    ];
}
