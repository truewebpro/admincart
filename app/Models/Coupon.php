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

    public function isCurrentlyActive(): bool
    {
        $now = now();
        return $this->is_active
            && (!$this->starts_at || $this->starts_at <= $now)
            && (!$this->expires_at || $this->expires_at >= $now);
    }

    public function badges():BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_coupons', 'coupon_id', 'badge_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /**
     * NEW: server-side source of truth for the customer-facing label text.
     * Mirrors SettingsMarketing.vue's getCouponLabel() exactly, so the admin
     * preview and the storefront badge never drift apart. Prefer display_title
     * when the merchant set one (that's literally what the field is for).
     */
    public function getLabelAttribute(): string
    {
        if (!empty($this->display_title)) {
            return $this->display_title;
        }

        $cond = $this->conditions ?? [];

        return match ($this->type) {
            'bogo' => sprintf('Buy %d Get %d Free', $cond['buy_qty'] ?? 0, $cond['get_qty'] ?? 0),
            'bundle' => sprintf('%d for £%s', $cond['bundle_qty'] ?? 0, number_format($cond['bundle_price'] ?? 0, 2)),
            'percentage' => sprintf('%s%% OFF', rtrim(rtrim(number_format($this->value, 2), '0'), '.')),
            default => sprintf('£%s OFF', number_format($this->value, 2)),
        };
    }

}
