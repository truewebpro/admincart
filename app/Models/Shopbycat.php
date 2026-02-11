<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shopbycat extends Model
{
    use HasFactory;
    protected $primaryKey = 'sbcat_id';
    protected $fillable = [
        'heading',
        'status',
        'image_url',
        'cat_id',
        'shop_id',
    ];
}
