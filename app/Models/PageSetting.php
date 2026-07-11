<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSetting extends Model
{
    use HasFactory;
    protected $table = 'page_settings';

    protected $fillable = [
        'shop_id',
        'page_type',
        'slug',
        'title',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public $hidden = ['created_at','updated_at'];
}
