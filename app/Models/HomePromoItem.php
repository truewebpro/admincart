<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomePromoItem extends Model
{
    use HasFactory;
    protected $table = 'home_promo_items';
    protected $fillable = [
        'home_promo_id',
        'media_type',
        'media_value',
        'title',
        'subtext',
        'sort_order',
    ];

    public $hidden = ['created_at','updated_at'];

    public function promo():BelongsTo
    {
        return $this->belongsTo(HomePromo::class,'home_promo_id','id');
    }
}
