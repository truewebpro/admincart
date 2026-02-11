<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Searchtag extends Model
{
    use HasFactory;
    protected $table = 'searchtags';
    protected $primaryKey = 'stag_id';
    protected $fillable = [
        'title',
        'link',
        'status',
        'shop_id',
    ];
    public $hidden = ['created_at','updated_at'];
}
