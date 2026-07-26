<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;
    protected $primaryKey = 'address_id';
    protected $table = 'customer_addresses';
    protected $fillable = [
        'address_title',
        'fname',
        'lname',
        'address_line1',
        'address_line2',
        'city',
        'postcode',
        'country',
        'phone',
        'is_default',
        'customer_id',
        'thirdparty_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
