<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ShipMethod */
class ShipMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ship_method_id' => $this->ship_method_id,
            'method' => $this->method,
            'price' => (float) $this->price,
            'zone' => $this->zone,
            'courier_id' => $this->courier_id,
            'courier_name' => $this->whenLoaded('courier', fn () => $this->courier?->courier_name),
        ];
    }
}
