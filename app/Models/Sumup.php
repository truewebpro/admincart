<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sumup extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id',
        'client_id',
        'client_secret',
        'merchant_email',
        'mode',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
