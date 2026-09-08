<?php

namespace App\Models;

use App\Enums\LabelRuleColumn;
use App\Enums\LabelRuleRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLabelRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_label_id',
        'shop_id',
        'column',
        'relation',
        'condition',
        'join_type',
    ];

    protected $casts = [
        'column' => LabelRuleColumn::class,
        'relation' => LabelRuleRelation::class,
    ];


    public function productLabel(): BelongsTo
    {
        return $this->belongsTo(ProductLabel::class, 'product_label_id', 'id');
    }

}
