<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessShop extends Model
{
    use HasFactory;
    protected $table = 'business_shops';
    protected $primaryKey = 'business_shop_id';
    protected $fillable = [
        'business_id',
        'shop_id',
    ];

    public $hidden = ['created_at', 'updated_at'];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function shops()
    {
        return $this->hasMany(Shop::class, 'shop_id','shop_id');
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'shop_id','shop_id');
    }
}
