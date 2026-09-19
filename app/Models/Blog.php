<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    use HasFactory, HasMediaFiles;
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
        'thirdparty_id',
        'thirdparty_blog_id',
        'thirdparty_blog_handle',
    ];

    protected $casts = [
        'btags' => 'array',
    ];

    protected $hidden = ['mediaFiles'];

    protected $appends = ['featured_image_alt'];

    public function getFeaturedImageAltAttribute(): ?string
    {
        $featured = $this->mediaFiles->first(fn ($file) => $file->pivot->media_for === 'featured');
        return $featured?->alt_text;
    }


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

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class, 'blog_id', 'blog_id');
    }

    public function faqs():HasMany
    {
        return $this->hasMany(BlogFaq::class, 'blog_id', 'blog_id')
            ->orderBy('sort_order', 'ASC');
    }

}
