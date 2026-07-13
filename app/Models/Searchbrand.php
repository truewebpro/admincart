<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Searchbrand extends Model
{
    use HasFactory;
    protected $table = 'searchbrands';
    protected $primaryKey = 'sbrand_id';
    protected $fillable = [
        'title',
        'link',
        'status',
        'shop_id',
    ];
    public $hidden = ['created_at','updated_at'];
}
