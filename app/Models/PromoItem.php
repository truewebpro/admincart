<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoItem extends Model
{
    use HasFactory;
    protected $table = 'promo_items';
    protected $fillable = [
        'promo_id',
        'media_type',
        'media_value',
        'title',
        'subtext',
        'sort_order',
    ];

    public $hidden = ['created_at', 'updated_at'];

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'promo_id', 'id');
    }
}
