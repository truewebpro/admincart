<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;
    protected $table = "policies";
    protected $primaryKey = 'policy_id';
    protected $fillable = [
        'policy_name',
        'policy_slug',
        'policy_description',
        'policy_status',
        'shop_id'
    ];
}
