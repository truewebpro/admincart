<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipMethod extends Model
{
    use HasFactory;
    protected $table = 'ship_methods';
    protected $primaryKey = 'ship_method_id';
    protected $fillable = [
        'method',
        'price',
        'zone',
        'courier_id',
        'shop_id',
    ];

    public function courier()
    {
       return $this->belongsTo(Courier::class, 'courier_id', 'courier_id');
    }

    public $hidden = ['created_at', 'updated_at'];
}
