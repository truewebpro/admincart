<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $primaryKey = 'setting_id';
    protected $fillable = [
        'shop_id',
        'min_checkout_price',
        'vat_included',
        'hide_price',
    ];

    protected $casts = [
        'vat_included' => 'boolean',
        'hide_price' => 'boolean',
    ];

    public $hidden = ['created_at', 'updated_at'];

}
