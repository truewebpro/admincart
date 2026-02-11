<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'user_id',
        'shop_id',
        'product_id',
    ];
}
