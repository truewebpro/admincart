<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sendcloud extends Model
{
    use HasFactory;
    protected $table = 'sendclouds';
    protected $primaryKey = 'id';
    protected $fillable = [
        'shop_id',
        'public_key',
        'secret_key',
        'default_sender_address_id',
        'api_version',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function shippingOptions():HasMany
    {
        return $this->hasMany(SendcloudShippingOption::class);
    }
}
