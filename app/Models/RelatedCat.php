<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedCat extends Model
{
    use HasFactory;
    protected $table = 'related_cats';
    protected $primaryKey = 'related_cat_id';
    protected $fillable = [
        'cat_parent_id',
        'related_cat_title',
        'related_image',
        'cat_child_id',
        'shop_id',
    ];

    public $hidden = ['created_at', 'updated_at'];
}
