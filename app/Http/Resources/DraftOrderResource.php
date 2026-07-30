<?php

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DraftOrder */
class DraftOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'draft_number' => $this->draft_number,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'currency' => $this->currency,
            // Lets the frontend label the VAT line correctly — "VAT
            // (included)" vs "VAT" — without needing its own copy of the
            // shop's settings.vat_included flag.
            'vat_included' => Setting::where('shop_id', $this->shop_id)->value('vat_included') ?? true,

            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'customer_id' => $this->customer->customer_id,
                'name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'business_profile' => $this->customer->businessProfileForCurrentShop(),
            ]),

            'items' => DraftOrderItemResource::collection($this->whenLoaded('items')),
            'payments' => DraftOrderPaymentResource::collection($this->whenLoaded('payments')),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
            ])),

            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => $this->discount_value,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'total' => $this->total,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,

            'internal_note' => $this->internal_note,
            'customer_note' => $this->customer_note,

            'shipping_method' => $this->shipping_method,
            'courier' => $this->courier,
            'requested_delivery_date' => $this->requested_delivery_date?->toDateString(),
            'collection_only' => $this->collection_only,
            'billing_same_as_shipping' => $this->billing_same_as_shipping,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            // The real CustomerAddress FK — this is what convert() actually
            // checks before allowing conversion. shipping_address (above)
            // is just a JSON snapshot for display; this id is the source
            // of truth for "has a real address been attached".
            'shipping_address_id' => $this->shipping_address_id,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'converted_order_id' => $this->converted_order_id,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
