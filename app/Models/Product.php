<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'product_id';
    protected $fillable = [
        'title',
        'handle',
        'publish_status',
        'body_html',
        'featured_image',
        'product_status',
        'product_type_id',
        'brand_id',
        'tags',
        'thirdparty_id',
        'shop_id',
        'meta_title',
        'meta_desc',
        'unit_name',
        'unit_pack_qty'
    ];
    protected $casts = [
        'tags' => 'array',
    ];

    public function variants():HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'product_id')
            ->join('stocks', 'stocks.variant_id', '=', 'variants.variant_id')
            ->select('variants.*','stocks.quantity as stock')->orderBy('variants.option_values', 'ASC');
    }

    public function brand():BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id')
            ->select(['brand_id', 'brand_name','brand_slug','brand_image']);
    }

    public function ptype()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id', 'product_type_id')
            ->select(['product_type_id', 'product_type_name']);
    }

    public function astock()
    {
        return $this->hasMany(Stock::class, 'product_id', 'product_id');
    }

    public function cats()
    {
        return $this->belongsToMany(Cat::class, 'catpros', 'cat_id', 'product_id')
            ->withPivot( 'shop_id')
            ->withTimestamps();
    }

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable', 'sectionable_type', 'sectionable_id','product_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function psections()
    {
        return $this->hasMany(Section::class,'sectionable_id','product_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->orderBy('sort_order', 'ASC');
    }

    public function highs()
    {
        return $this->hasMany(Highlight::class,'product_id','product_id')
            ->join('features','features.feature_id','=','highlights.feature_id')
            ->select('highlights.*','features.ftitle','features.fimage');
    }

    public function reviews()
    {
        return $this->hasMany(Proreview::class, 'product_id', 'product_id')
            ->join('reviewers', 'reviewers.id', '=', 'proreviews.reviewer_id')
            ->select('proreviews.*','reviewers.first_name','reviewers.last_name')
            ->orderBy('proreviews.created_at','DESC');
    }

    public function specifics()
    {
        return $this->hasMany(Specific::class, 'product_id', 'product_id');
    }

    public function tiers():HasMany
    {
        return $this->hasMany(ProductPriceTier::class , 'product_id', 'product_id')
            ->orderBy('min_qty', 'ASC');
    }

    public function faqs():HasMany
    {
        return $this->hasMany(ProductFaq::class, 'product_id', 'product_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function pvariants():HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'product_id')
            ->join('stocks', 'stocks.variant_id', '=', 'variants.variant_id')
            ->select('variants.*','stocks.quantity as astock')->orderBy('variants.option_values', 'ASC');
    }

    public function defaultVariant(): HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'product_id')
            ->orderByDesc('isdefault');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id', 'product_type_id');
    }

    public function isActive(): bool
    {
        return $this->product_status === 'Active';
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

}
