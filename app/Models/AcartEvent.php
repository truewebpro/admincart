<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcartEvent extends Model
{
    use HasFactory;
    protected $table = 'acart_events';
    protected $primaryKey = 'acart_event_id';
    protected $fillable = [
        'acart_id',
        'shop_id',
        'event_type',
        'event_data',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

}
