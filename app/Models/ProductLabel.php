<?php

namespace App\Models;

use App\Enums\BadgePosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLabel extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id',
        'label',
        'use_label',
        'color',
        'bg_color',
        'style',
        'image',
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
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    /** A label with any rows here is implicitly rule-driven — no
     *  separate type flag, ProductLabelService just checks whether this
     *  relation is non-empty. */
    public function rules(): HasMany
    {
        return $this->hasMany(ProductLabelRule::class, 'product_label_id', 'id');
    }

    /** Direct product assignment — populated manually if this label has
     *  no rules, or automatically by ProductLabelService::syncProduct()
     *  if it does. */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_label_products', 'product_label_id', 'product_id')
            ->withTimestamps();
    }

    public function effectiveLabel(): ?string
    {
        return $this->use_label ? $this->label : null;
    }

    /** Flat array shape the frontend expects — same shape
     *  PromoLabelService produces for coupon/rule badges, so
     *  PromoLabelChips.jsx renders either kind identically, even though
     *  the two systems share no model/table underneath. Returns null
     *  only when there's neither text NOR an image to show — an image
     *  alone is enough to justify rendering (use_label off just means
     *  "don't show the text", not "don't show the badge at all"). */
    public function toLabelArray(): ?array
    {
        $label = $this->effectiveLabel();

        if (!$label && !$this->image) {
            return null;
        }

        return [
            'id' => $this->id,
            'kind' => 'product_label',
            'label' => $label,
            'use_label' => $this->use_label,
            'image' => $this->image,
            'position' => $this->position->value,
            'color' => $this->color,
            'bg_color' => $this->bg_color,
            'style' => $this->style,
        ];
    }


}
