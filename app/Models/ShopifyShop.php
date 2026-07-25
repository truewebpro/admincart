<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_domain',
        'client_id',
        'client_secret',
        'access_token',
        'token_expires_at',
        'scope',
        'shop_id',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function isTokenExpired(): bool
    {
        return ! $this->token_expires_at
            || $this->token_expires_at->isPast();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class,'shop_id','shop_id');
    }

    public function hasScope(string $scope): bool
    {
        if (! $this->scope) {
            return false;
        }

        return in_array($scope, array_map('trim', explode(',', $this->scope)), true);
    }

    public function scopes(): array
    {
        return $this->scope
            ? array_map('trim', explode(',', $this->scope))
            : [];
    }



}
