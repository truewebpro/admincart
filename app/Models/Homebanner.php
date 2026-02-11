<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homebanner extends Model
{
    use HasFactory;
    protected $primaryKey = 'banner_id';
    protected $fillable = [
        'heading',
        'subheading',
        'button_text',
        'link',
        'status',
        'image_url',
        'shop_id'
    ];
}
