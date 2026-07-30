<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DraftOrder */
class DraftOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'draft_number' => $this->draft_number,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'customer_id' => $this->customer->customer_id,
                'name' => $this->customer->full_name,
            ] : null),
            'item_count' => $this->whenCounted('items'),
            'total' => $this->total,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
