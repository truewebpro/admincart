<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    use HasFactory;
    protected $table = 'order_logs';
    protected $fillable = [
        'order_id',
        'cart_id',
        'event',
        'description',
        'meta',
        'source',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
