<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poptions extends Model
{
    use HasFactory;
    protected $primaryKey = "option_id";
    protected $fillable = [
        'option_name',
        'shop_id',
        ];
}
