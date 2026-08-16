<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTrackingEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'status_name',
        'status_value',
        'status_code',
        'parcel_id',
        'carrier',
        'event_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
