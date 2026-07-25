<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spro extends Model
{
    use HasFactory;

    protected $table = 'spros';

    protected $fillable = [
        'shop_id',
        'shopify_product_id',
        'title',
        'handle',
        'vendor',
        'product_type',
        'status',
        'body_html',
        'tags',
        'variants',
        'options',
        'images',
        'image_one',
        'product_id',
        'imported_at',
    ];

    protected $casts = [
        'variants'    => 'array',
        'options'     => 'array',
        'images'      => 'array',
        'imported_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function isImported(): bool
    {
        return ! is_null($this->product_id);
    }

}
