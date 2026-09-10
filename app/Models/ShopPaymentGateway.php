<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopPaymentGateway extends Model
{
    protected $fillable = [
        'shop_id', 'provider', 'label', 'credentials',
        'is_active', 'is_default', 'environment',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_active'   => 'boolean',
        'is_default'  => 'boolean',
    ];

    // Credentials must never leak through an accidental toArray()/JSON response.
    protected $hidden = ['credentials'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function scopeForShop(Builder $q, int $shopId): Builder
    {
        return $q->where('shop_id', $shopId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }

    public function isSandbox(): bool
    {
        return $this->environment !== 'production';
    }
}
