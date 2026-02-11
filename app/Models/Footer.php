<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Footer extends Model
{
    use HasFactory;
    protected $table = 'footers';
    protected $primaryKey = 'footer_id';
    protected $fillable = [
        'style',
        'fsections',
        'settings',
        'shop_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = [
        'fsections' => 'array',
        'settings' => 'array',
    ];
}
