<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Featuredbrand extends Model
{
    use HasFactory;
    protected $primaryKey = 'fbrand_id';
    protected $fillable = [
        'brand_id',
        'status',
        'shop_id'
    ];
}
