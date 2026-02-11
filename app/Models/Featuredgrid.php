<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Featuredgrid extends Model
{
    use HasFactory;
    protected $primaryKey = 'fgrid_id';
    protected $fillable = [
        'heading',
        'subheading',
        'promo_text',
        'label_text',
        'type',
        'status',
        'image_url',
        'link',
        'shop_id',
    ];
}
