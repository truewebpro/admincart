<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscribeSection extends Model
{
    use HasFactory;
    protected $table = 'subscribe_sections';
    protected $fillable = [
        'style',
        'headline',
        'subheadline',
        'promo_text',
        'privacy_text',
        'settings',
        'extras',
        'shop_id',
    ];

    protected $casts = [
        'settings' => 'array',
        'extras' => 'array',
    ];

    public $hidden = ['created_at', 'updated_at'];
}
