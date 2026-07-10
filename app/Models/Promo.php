<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    use HasFactory;

    protected $table = 'promos';
    protected $fillable = [
        'shop_id',
        'page_type',
        'heading',
        'subheading',
        'style',
        'bg_color',
        'title_color',
        'subtext_color',
        'status',
        'position',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public $hidden = ['created_at', 'updated_at'];

    public function items():HasMany
    {
        return $this->hasMany(PromoItem::class, 'promo_id', 'id')
            ->orderBy('sort_order');
    }

}
