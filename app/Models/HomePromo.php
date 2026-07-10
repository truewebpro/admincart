<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomePromo extends Model
{
    use HasFactory;
    protected $table = 'home_promos';
    protected $fillable = [
        'homepage_id',
        'shop_id',
        'position',
        'heading',
        'subheading',
        'style',
        'bg_color',
        'title_color',
        'subtext_color',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public $hidden = ['created_at','updated_at'];

    public function items(): HasMany
    {
        return $this->hasMany(HomePromoItem::class)
            ->orderBy('sort_order');
    }
}
