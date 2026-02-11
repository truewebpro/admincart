<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Page extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'pages';
    protected $primaryKey = 'page_id';
    protected $fillable = [
        'page_title',
        'page_slug',
        'page_description',
        'page_status',
        'shop_id',
        'meta_title',
        'meta_description',
        'og_image',
    ];

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable','sectionable_type','sectionable_id','page_id')
            ->orderBy('sort_order','asc');
    }

    public function psections()
    {
        return $this->hasMany(Section::class,'sectionable_id','page_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Page::class)
            ->orderBy('sort_order', 'ASC');
    }

}
