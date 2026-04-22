<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRuleProduct extends Model
{
    use HasFactory;
    protected $table = 'pricing_rule_products';
    protected $primaryKey = 'id';
    protected $fillable = ['pricing_rule_id', 'product_id'];
}
