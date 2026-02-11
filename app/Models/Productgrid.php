<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productgrid extends Model
{
    use HasFactory;
    protected $primaryKey = 'pgrid_id';
    protected $fillable = [
        'pgrid_name',
        'heading',
        'subheading',
        'limit',
        'status',
        'cat_id',
        'shop_id',
    ];
}
