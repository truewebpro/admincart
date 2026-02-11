<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catpro extends Model
{
    use HasFactory;
    protected $table = 'catpros';
    protected $fillable = [
        'cat_id',
        'product_id',
        'position',
        'shop_id',
    ];

    public function category()
    {
        return $this->belongsTo(Cat::class,'cat_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id','product_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class,'shop_id');
    }

    public function cpro()
    {
        return $this->hasOne(Product::class,'product_id','product_id');
    }
}
