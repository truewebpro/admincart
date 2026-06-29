<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'homepage_id',
        'shop_id',
        'question',
        'answer',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function homepage():BelongsTo
    {
        return $this->belongsTo(Homepage::class, 'homepage_id','homepage_id');
    }
}
