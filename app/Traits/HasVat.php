<?php

namespace App\Traits;

trait HasVat
{

    protected function vatSettings()
    {
        return cache()->remember(
            "shop_vat_{$this->shop_id}",
            now()->addHours(12),
            fn() => \App\Models\Setting::select(
                'shop_id',
                'vat_included'
            )->find($this->shop_id)
        );
    }

    public function isVatIncluded(): bool
    {
        return (bool) ($this->vatSettings()->vat_included ?? true);
    }

    public function getVatRate(): float
    {
        return $this->vatSettings()->vat_rate
            ?? config('vat.standard_rate', 20);
    }

    /**
     * Original price stored in database
     */
    public function getPriceAttribute($value): float
    {
        return round((float) $value, 2);
    }

    public function getDisplayPriceAttribute(): float
    {
        return $this->price_incl_vat;
    }

    /**
     * Price excluding VAT
     */
    public function getPriceExclVatAttribute(): float
    {
        $price = $this->price;
        $rate = $this->getVatRate();

        if (!$this->isVatIncluded()) {
            return round($price, 2);
        }

        return round($price / (1 + ($rate / 100)), 2);
    }

    /**
     * Price including VAT
     */
    public function getPriceInclVatAttribute(): float
    {
        $price = $this->price;
        $rate = $this->getVatRate();

        if ($this->isVatIncluded()) {
            return round($price, 2);
        }

        return round($price * (1 + ($rate / 100)), 2);
    }

    /**
     * VAT amount
     */
    public function getVatAmountAttribute(): float
    {
        return round(
            $this->price_incl_vat - $this->price_excl_vat,
            2
        );
    }
}
