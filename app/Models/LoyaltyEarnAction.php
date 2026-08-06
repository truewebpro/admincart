<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyEarnAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'category', 'platform', 'label', 'description', 'action_url',
        'points', 'verification', 'repeat_scope', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function completions()
    {
        return $this->hasMany(LoyaltyActionCompletion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
