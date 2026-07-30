<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a Variant (with product eager-loaded and stock summed via
 * withSum('stocks as stock', 'quantity')) into what the product search
 * dialog actually needs — not a raw model dump.
 *
 * @mixin \App\Models\Variant
 */
class VariantSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'variant_id' => $this->variant_id,
            'product_id' => $this->product_id,
            'title' => $this->product?->title,
            'variant_title' => $this->display_title,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'image' => $this->variant_image ?? $this->product?->featured_image,
            // ->stock comes from withSum('stocks as stock', 'quantity') on
            // the query — null if no stock rows exist at all, so cast to
            // int defensively (0, not "unknown") since this is a
            // list-for-selection context, not the same "unknown vs zero"
            // distinction Variant::currentStock() is careful about.
            'stock' => (int) ($this->stock ?? 0),
            'price' => (float) $this->price,
            'cost_price' => $this->costprice !== null ? (float) $this->costprice : null,
            'vat_rate' => $this->vatRate(),
        ];
    }
}
