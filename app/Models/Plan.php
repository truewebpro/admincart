<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $table = 'plans';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'stripe_product_id',
        'price',
        'currency',
        'interval',
        'trial_days',
        'features',
        'is_active',
        'sort_order',
        'is_popular',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'features' => 'array',
        'is_popular' => 'boolean',
    ];
}
