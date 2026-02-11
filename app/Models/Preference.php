<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;
    protected $primaryKey = 'preference_id';
    protected $fillable = [
        'home_title',
        'home_description',
        'home_image',
        'shop_logo',
        'social_links',
        'shop_id',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];
}
