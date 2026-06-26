<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcartItem extends Model
{
    use HasFactory;
    protected $table = 'acart_items';
    protected $primaryKey = 'acart_item_id';
    protected $fillable = [
        'acart_id',
        'shop_id',
        'product_id',
        'variant_id',
        'title',
        'options_json',
        'price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'options_json' => 'array',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round((float) $value, 2),
        );
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function variant(){
        return $this->belongsTo(Variant::class, 'variant_id', 'variant_id');
    }
}
