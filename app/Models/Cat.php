<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cat extends Model
{
    use HasFactory;
    protected $primaryKey = 'cat_id';
    protected $fillable = [
        'cat_name',
        'cat_slug',
        'cat_desc',
        'short_desc',
        'cat_status',
        'cat_image',
        'cat_type',
        'cat_rule',
        'sort_order',
        'shop_id',
        'meta_title',
        'meta_desc',
    ];

    public function rules()
    {
        return $this->hasMany(Rule::class,'cat_id','cat_id');
    }

    public function catpros()
    {
        return $this->hasMany(Catpro::class,'cat_id','cat_id');
    }

    public function catps()
    {
        return $this->belongsToMany(Product::class, 'catpros', 'cat_id', 'product_id')
            ->withTimestamps()
            ->withPivot('position', 'shop_id');
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Catpro::class,
            'cat_id',
            'product_id',
            'cat_id',
            'product_id',
        );

    }

    public function cpros()
    {
        return $this->belongsToMany(Product::class, 'catpros', 'cat_id', 'product_id')
            ->withPivot( 'shop_id')
            ->withTimestamps();
    }

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable', 'sectionable_type', 'sectionable_id','cat_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function csections()
    {
        return $this->hasMany(Section::class,'sectionable_id','cat_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Cat::class)
            ->orderBy('sort_order', 'ASC');
    }

    public function rcats()
    {
        return $this->hasMany(RelatedCat::class,'cat_parent_id','cat_id')
            ->join('cats','cats.cat_id','=','related_cats.cat_child_id')
            ->select('related_cats.*','cats.cat_slug as rslug','cats.cat_name as rcat_name');
    }

    public function faqs():HasMany
    {
        return $this->hasMany(CatFaq::class, 'cat_id', 'cat_id')
            ->orderBy('sort_order', 'ASC');
    }

}
