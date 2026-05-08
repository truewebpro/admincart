<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Homepage extends Model
{
    use HasFactory;
    protected $table = 'homepages';
    protected $primaryKey = 'homepage_id';
    public $timestamps = false;
    protected $fillable = [
        'shop_id',
        'title',
        'slug',
        'layout_json',
        'status',
    ];

    public $casts = [
        'layout_json' => 'array',
    ];

    public function sections():MorphMany
    {
        return $this->morphMany(Section::class, 'sectionable', 'sectionable_type', 'sectionable_id','homepage_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function hsections():HasMany
    {
        return $this->hasMany(Section::class,'sectionable_id','homepage_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Homepage::class)
            ->orderBy('sort_order', 'ASC');
    }

    public function herosections():HasMany
    {
        return $this->hasMany(Section::class,'sectionable_id','homepage_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Homepage::class)
            ->orderBy('sort_order', 'ASC')->limit(2);
    }

    public function lsections():HasMany
    {
        return $this->hasMany(Section::class,'sectionable_id','homepage_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Homepage::class)
            ->orderBy('sort_order', 'ASC')->skip(2)->take(20);
    }

}
