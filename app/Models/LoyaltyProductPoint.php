<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyProductPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'variant_id', 'points_per_unit', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
