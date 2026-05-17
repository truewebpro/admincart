<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasFactory;
    protected $primaryKey = 'product_type_id';
    protected $fillable = [
        'product_type_name',
        'product_type_status',
        'shop_id',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type_id', 'product_type_id');
    }
}
