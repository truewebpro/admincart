<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;
    protected $primaryKey = 'brand_id';
    protected $fillable = [
        'brand_name',
        'brand_slug',
        'brand_image',
        'brand_desc',
        'brand_status',
        'shop_id',
        'meta_title',
        'meta_desc',
    ];

    public function product(){
        return $this->hasMany(Product::class, 'brand_id', 'brand_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id', 'brand_id');
    }

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable', 'sectionable_type', 'sectionable_id','brand_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function bsections()
    {
        return $this->hasMany(Section::class,'sectionable_id','brand_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Brand::class)
            ->orderBy('sort_order', 'ASC');
    }

    public function faqs():HasMany
    {
        return $this->hasMany(BrandFaq::class, 'brand_id', 'brand_id')
            ->orderBy('sort_order', 'ASC');
    }

}
