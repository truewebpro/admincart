<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VivaPayment extends Model
{
    use HasFactory;
    protected $table = 'viva_payments';
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'order_code',
        'transaction_id',
        'status_id',
        'amount',
        'fullname',
        'email',
        'shop_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
