<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerShop extends Model
{
    use HasFactory;
    protected $primaryKey = 'cshop_id';
    protected $table = 'customer_shops';
    protected $fillable = [
        'ctags',
        'customer_id',
        'shop_id',
        'status',
        'registered_at',
    ];

    protected $casts = [
        'ctags' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if(empty($model->ctags)) {
                $model->ctags = ['b2c'];
            }
        });
    }
}
