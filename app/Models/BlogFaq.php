<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'shop_id',
        'question',
        'answer',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public $hidden = ['created_at', 'updated_at'];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'blog_id','blog_id');
    }
}
