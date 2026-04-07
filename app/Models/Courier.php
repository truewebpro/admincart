<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;
    protected $primaryKey = 'courier_id';
    protected $fillable = [
        'courier_name',
        'tracking_url',
        'courier_logo',
        'courier_status',
    ];

    public function shippingMethods()
    {
        return $this->hasMany(ShippingMethod::class, 'courier_id', 'courier_id');
    }

    public $hidden = ['created_at', 'updated_at'];
}
