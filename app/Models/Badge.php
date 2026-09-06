<?php

namespace App\Models;

use App\Enums\BadgePosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasFactory;
    protected $table = 'badges';
    protected $fillable = [
        'shop_id',
        'label',
        'use_label',
        'color',
        'bg_color',
        'style',
        'position',
        'is_active',
    ];

    protected $casts = [
        'use_label' => 'boolean',
        'is_active' => 'boolean',
        'position' => BadgePosition::class,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id','shop_id');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'badge_coupons', 'badge_id', 'coupon_id')
            ->withTimestamps();
    }

    public function pricingRules(): BelongsToMany
    {
        return $this->belongsToMany(PricingRule::class, 'badge_rules', 'badge_id', 'pricing_rule_id')
            ->withTimestamps();
    }

    /** The text to actually display: custom label if enabled, else null
     *  (caller falls back to the coupon's/rule's own ->label). */
    public function effectiveLabel(): ?string
    {
        return $this->use_label ? $this->label : null;
    }


}
