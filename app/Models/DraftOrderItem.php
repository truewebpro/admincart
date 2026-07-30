<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\DraftOrderItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'draft_order_id',
        'product_id',
        'variant_id',
        'item_type',
        'title',
        'variant_title',
        'sku',
        'barcode',
        'product_image',
        'quantity',
        'unit_cost',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_total',
        'vat_rate',
        'tax_total',
        'line_subtotal',
        'line_total',
        'stock_snapshot',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'item_type' => DraftOrderItemType::class,
        'discount_type' => DiscountType::class,
        'quantity' => 'integer',
        'unit_cost' => 'float',
        'unit_price' => 'float',
        'discount_value' => 'float',
        'discount_total' => 'float',
        'vat_rate' => 'float',
        'tax_total' => 'float',
        'line_subtotal' => 'float',
        'line_total' => 'float',
        'metadata' => 'array',
    ];

    public function draftOrder(): BelongsTo
    {
        return $this->belongsTo(DraftOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'variant_id');
    }

    /**
     * True if unit_price is below unit_cost — used to surface the
     * "price below cost" warning in the UI and to gate on the
     * draft_orders.override_price permission server-side.
     */
    public function isPriceBelowCost(): bool
    {
        return ! is_null($this->unit_cost) && $this->unit_price < $this->unit_cost;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}
