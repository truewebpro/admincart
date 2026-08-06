<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveVisitor extends Model
{
    protected $fillable = [
        'shop_id',
        'session_id',
        'customer_id',
        'current_path',
        'last_seen_at',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function isLive(): bool
    {
        return $this->last_seen_at?->gte(now()->subMinutes(5)) ?? false;
    }

}
