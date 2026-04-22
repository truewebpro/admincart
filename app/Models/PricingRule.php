<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PricingRule extends Model
{
    use HasFactory;
    protected $table = 'pricing_rules';
    protected $primaryKey = 'id';
    protected $fillable = [
        'shop_id',
        'name',
        'type',
        'min_qty',
        'price',
        'discount_percent',
        'scope',
        'is_active',
        'priority',
        'starts_at',
        'expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'=> 'float',
        'discount_percent'=> 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'pricing_rule_products', 'pricing_rule_id', 'product_id');
    }

    public function cats(): BelongsToMany
    {
        return $this->belongsToMany(Cat::class, 'pricing_rule_cats', 'pricing_rule_id', 'cat_id');
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

    public function isCurrentlyActive()
    {
        $now = now();
        return $this->is_active &&
            (!$this->starts_at || $this->starts_at <= $now) &&
            (!$this->expires_at || $this->expires_at >= $now);
    }
}
