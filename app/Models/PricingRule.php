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
        'discount_percent'=> 'float',
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

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_rules', 'pricing_rule_id', 'badge_id')
            ->withTimestamps();
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

    /**
     * Prefer the merchant-set name (the same field PricingRuleService
     * already uses for the cart-page rule-discount line) so the
     * product-card/PDP badge and the cart line always show identical
     * text — never two different labels for the same promo. Computed
     * fallback only kicks in for legacy rows with no name set.
     */
    public function getLabelAttribute(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        return $this->type === 'bundle'
            ? sprintf('%d for £%s', $this->min_qty, number_format($this->price, 2))
            : sprintf('%s%% off %d+', rtrim(rtrim(number_format($this->discount_percent, 2), '0'), '.'), $this->min_qty);
    }


}
