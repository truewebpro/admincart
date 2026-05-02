<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shop;

class ShopUser extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop_id',
        'user_id',
        'role',
        'shop_user_status',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

}
