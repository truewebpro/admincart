<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $primaryKey = 'stock_id';
    protected $fillable = [
        'quantity',
        'location_id',
        'variant_id',
        'product_id',
        'shop_id',
    ];

    public function astock()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
