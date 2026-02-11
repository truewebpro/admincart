<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proreview extends Model
{
    use HasFactory;
    protected $table = 'proreviews';
    protected $primaryKey = 'proreview_id';
    protected $fillable = [
        'review_title',
        'review_text',
        'review_status',
        'rating',
        'reviewer_id',
        'shop_id',
        'product_id',
        'created_at',
    ];
}
