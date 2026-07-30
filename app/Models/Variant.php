<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variant extends Model
{
    use HasFactory;
    protected $primaryKey = 'variant_id';
    protected $fillable = [
        'sku',
        'price',
        'compareprice',
        'costprice',
        'barcode',
        'variant_image',
        'istax',
        'isdefault',
        'weight',
        'options',
        'option_values',
        'product_id',
        'shop_id',
    ];

    protected $casts = [
        'options' => 'array',
        'option_values' => 'array',
        'istax' => 'boolean',
        'isdefault' => 'boolean',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    protected function compareprice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    public function stock()
    {
        return $this->hasMany(Stock::class, 'variant_id', 'variant_id')
            ->join('locations', 'locations.location_id', '=', 'stocks.location_id')
            ->select('stocks.*', 'locations.location_name');
    }

    public function astock()
    {
        return $this->hasOne(Stock::class, 'variant_id', 'variant_id');
    }

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'variant_id', 'variant_id');
    }

    public function currentStock(?int $locationId = null): int
    {
        $query = $this->stocks();

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return (int) $query->sum('quantity');
    }

    public function getDisplayTitleAttribute(): ?string
    {
        if (empty($this->option_values) || ! is_array($this->option_values)) {
            return null;
        }

        return implode(' / ', array_values($this->option_values));
    }

    public function vatRate(): float
    {
        return $this->istax ? (float) config('vat.standard_rate') : 0.00;
    }

    public function isBelowCost(float $sellingPrice): bool
    {
        return ! is_null($this->costprice) && $sellingPrice < (float) $this->costprice;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}
