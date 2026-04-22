<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRuleCat extends Model
{
    use HasFactory;
    protected $table = 'pricing_rule_cats';
    protected $primaryKey = 'id';
    protected $fillable = ['pricing_rule_id', 'cat_id'];
}
