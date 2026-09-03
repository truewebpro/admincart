<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;
    protected $primaryKey = 'review_id';
    protected $fillable = [
        'review_title',
        'review_text',
        'review_status',
        'review_images',
        'rating',
        'customer_id',
        'shop_id',
        'product_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
