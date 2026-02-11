<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Featuredcat extends Model
{
    use HasFactory;
    protected $primaryKey = 'fcat_id';
    protected $fillable = [
        'heading',
        'subheading',
        'promo_text',
        'label_text',
        'type',
        'status',
        'image_url',
        'cat_id',
        'shop_id',
    ];
}
