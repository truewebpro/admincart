<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;
    protected $table = 'features';
    protected $primaryKey = 'feature_id';
    protected $fillable = [
        'ftitle',
        'fimage',
        'shop_id',
    ];

    public $hidden = [
        'created_at','updated_at'
    ];
}
