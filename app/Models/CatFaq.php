<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatFaq extends Model
{
    use HasFactory;
    protected $fillable = [
        'cat_id',
        'shop_id',
        'question',
        'answer',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function cat():BelongsTo
    {
        return $this->belongsTo(Cat::class, 'cat_id', 'cat_id');
    }
}
