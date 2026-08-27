<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sorder extends Model
{
    protected $fillable = [
        'shop_id',
        'thirdparty_id',
        'is_pinned',
        'order_number',
        'email',
        'financial_status',
        'fulfillment_status',
        'subtotal_price',
        'total_discounts',
        'total_tax',
        'total_shipping',
        'total_price',
        'currency',
        'customer_thirdparty_id',
        'customer_name',
        'line_items',
        'shipping_address',
        'shopify_created_at',
    ];

    protected $casts = [
        'is_pinned'          => 'boolean',
        'line_items'         => 'array',
        'shipping_address'   => 'array',
        'shopify_created_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}
