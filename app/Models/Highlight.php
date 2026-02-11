<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    use HasFactory;
    protected $table = 'highlights';
    protected $primaryKey = 'highlight_id';
    protected $fillable = [
        'fvalue',
        'position',
        'feature_id',
        'product_id',
    ];
    public $hidden = [
        'created_at','updated_at'
    ];
}
