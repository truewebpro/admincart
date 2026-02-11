<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reviewer extends Model
{
    use HasFactory;
    protected $table = 'reviewers';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
    ];
    public $hidden = ['created_at','updated_at'];
}
