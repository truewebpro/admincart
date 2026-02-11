<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $primaryKey = 'menu_id';
    protected $fillable = [
        'menu_name',
        'menu_slug',
        'mitems',
        'menu_status',
        'shop_id',
    ];

    protected $casts = [
        'mitems' => 'array',
    ];

    public $hidden = ['created_at','updated_at'];
}
