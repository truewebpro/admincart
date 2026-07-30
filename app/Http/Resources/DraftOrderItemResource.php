<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DraftOrderItem */
class DraftOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'title' => $this->title,
            'variant_title' => $this->variant_title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'product_image' => $this->product_image,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => $this->discount_value,
            'discount_total' => $this->discount_total,
            'vat_rate' => $this->vat_rate,
            'tax_total' => $this->tax_total,
            'line_subtotal' => $this->line_subtotal,
            'line_total' => $this->line_total,
            'stock_snapshot' => $this->stock_snapshot,
            'is_below_cost' => $this->isPriceBelowCost(),
            // Live stock, not the add-time snapshot — for the "insufficient
            // stock" warning to stay accurate if inventory changes after
            // the item was added.
            'current_stock' => $this->whenLoaded('variant', fn () => $this->variant?->currentStock()),
            'sort_order' => $this->sort_order,
        ];
    }
}
