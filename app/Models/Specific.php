<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specific extends Model
{
    use HasFactory;
    protected $table = 'specifics';
    protected $primaryKey = 'specific_id';
    protected $fillable = [
        'stitle',
        'svalue',
        'sicon',
        'shop_id',
        'product_id',
    ];
    public $hidden = ['created_at','updated_at'];
}
