<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cartpage extends Model
{
    use HasFactory;
    protected $table = 'cartpages';
    protected $primaryKey = "cartpage_id";
    public $hidden = ['created_at','updated_at'];

    protected $fillable = [
        'shop_id',
        'cart_slug',
    ];

    public function sections()
    {
        return $this->morphMany(Section::class, 'sectionable', 'sectionable_type', 'sectionable_id','cartpage_id')
            ->orderBy('sort_order', 'ASC');
    }

    public function csections()
    {
        return $this->hasMany(Section::class,'sectionable_id','cartpage_id')
            ->join('stypes','stypes.stype_id','=','sections.stype_id')
            ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
            ->where('sections.section_status','=','show')
            ->where('sections.sectionable_type',Cartpage::class)
            ->orderBy('sort_order', 'ASC');
    }
}
