<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcements';
    protected $primaryKey = 'announcement_id';
    protected $fillable = [
        'title',
        'setting',
        'status',
        'shop_id',
    ];

    public $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'setting' => 'array',
    ];
}
