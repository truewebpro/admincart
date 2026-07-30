<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCtag extends Model
{
    protected $table = 'customer_ctag';

    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'ctag_id',
    ];

}
