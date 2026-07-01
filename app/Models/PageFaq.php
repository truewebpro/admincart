<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageFaq extends Model
{
    use HasFactory;
    protected $fillable = [
        'page_id',
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

    public function page():BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id', 'page_id');
    }
}
