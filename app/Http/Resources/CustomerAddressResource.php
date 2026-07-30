<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomerAddress */
class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'address_id' => $this->address_id,
            'address_title' => $this->address_title,
            'name' => trim($this->fname . ' ' . $this->lname),
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
        ];
    }
}
