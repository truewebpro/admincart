<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;
    protected $table = 'coupons';
    protected $primaryKey = 'coupon_id';
    protected $fillable = [
        'shop_id',
        'code',
        'title',
        'display_title',
        'is_auto',
        'type',
        'value',
        'applies_to',
        'min_order_amount',
        'is_active',
        'starts_at',
        'expires_at',
        'usage_limit',
        'per_customer_limit',
        'is_stackable',
        'priority',
        'conditions',
    ];

    protected $casts = [
        'is_auto' => 'boolean',
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
        'value' => 'float',
        'min_order_amount' => 'float',
        'conditions' => 'array',
    ];

    public function products():BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products', 'coupon_id', 'product_id');
    }

    public function cats():BelongsToMany
    {
        return $this->belongsToMany(Cat::class, 'coupon_cats', 'coupon_id', 'cat_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }
}
