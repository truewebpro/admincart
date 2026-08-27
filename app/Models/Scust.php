<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scust extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'thirdparty_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'state',
        'orders_count',
        'total_spent',
        'tags',
        'addresses',
        'shopify_created_at',
        'customer_id',
        'imported_at',
    ];

    protected $casts = [
        'addresses'           => 'array',
        'shopify_created_at'  => 'datetime',
        'imported_at'         => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function isImported(): bool
    {
        return ! is_null($this->customer_id);
    }

}
