<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $table = 'sections';
    protected $primaryKey = 'section_id';
    protected $fillable = [
        'stype_id',
        'sectionable_id',
        'sectionable_type',
        'section_json',
        'sort_order',
        'section_status',
    ];

    protected $casts = [
        'section_json' => 'array',
    ];

    protected $hidden = [
      'created_at',
      'updated_at'
    ];

    public function stype()
    {
        return $this->belongsTo(Stype::class, 'stype_id', 'stype_id');
    }

    public function sectionable()
    {
        return $this->morphTo();
    }

//    public function scopeSorted($query)
//    {
//        return $query->orderBy('sort_order','asc');
//    }

//    protected static function boot()
//    {
//        parent::boot();
//        static::creating(function ($section) {
//            if($section->stype_id && empty($section->section_json)){
//                $stype = Stype::find($section->stype_id);
//                if($stype && isset($stype->stype_json)){
//                    $section->section_json = array_merge(
//                        (array) json_decode($stype->stype_json, true),
//                        (array) $section->section_json,
//                    );
//                }
//            }
//        });
//    }

}
