<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;
    protected $primaryKey = 'setting_id';
    protected $fillable = [
        'shop_id',
        'min_checkout_price',
        'vat_included',
        'hide_price',
        'shipping_protection_enabled',
        'shipping_protection_fee',
        'free_delivery_amount',
    ];

    protected $casts = [
        'vat_included' => 'boolean',
        'hide_price' => 'boolean',
        'shipping_protection_enabled' => 'boolean',
        'free_delivery_amount' => 'float',
    ];

    protected function minCheckoutPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    protected function shippingProtectionFee(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    public $hidden = ['created_at', 'updated_at'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }


}
