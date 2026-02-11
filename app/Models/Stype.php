<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stype extends Model
{
    use HasFactory;
    protected $table = 'stypes';
    protected $primaryKey = 'stype_id';
    protected $fillable = [
        'stype_name',
        'stype_slug',
        'stype_json',
    ];

    protected $casts = [
        'stype_json' => 'array',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

//    public function sections()
//    {
//        return $this->hasMany(Section::class, 'stype_id', 'stype_id');
//    }

}
