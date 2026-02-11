<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;
    protected $table = 'blogs';
    protected $primaryKey = 'blog_id';
    protected $fillable = [
        'blog_title',
        'blog_slug',
        'blog_description',
        'blog_excerpt',
        'blog_image',
        'btags',
        'blog_status',
        'meta_title',
        'meta_desc',
        'user_id',
        'shop_id',
    ];

    protected $casts = [
        'btags' => 'array',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable','sectionable_type','sectionable_id','blog_id')
            ->orderBy('sort_order','asc');
    }

    public function bsections()
    {
        return $this->hasMany(Section::class,'sectionable_id','blog_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Blog::class)
            ->orderBy('sort_order', 'ASC');
    }

}
