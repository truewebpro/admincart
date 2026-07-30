<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ASSUMPTION: when 'shops' is eager-loaded, callers must constrain it to
 * the current shop only (e.g. ->with(['shops' => fn($q) => $q->where(
 * 'shop_id', $shopId)])) — this resource takes shops->first() and trusts
 * it's already scoped correctly, since a customer can belong to multiple
 * shops and grabbing an unscoped "first" would leak another shop's
 * business_name.
 *
 * @mixin \App\Models\Customer
 */
class CustomerSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultAddress = $this->addresses?->firstWhere('is_default', true)
            ?? $this->addresses?->first();

        return [
            'customer_id' => $this->customer_id,
            'name' => trim($this->fname . ' ' . $this->lname),
            'fname' => trim($this->fname),
            'lname' => trim($this->lname),
            'email' => $this->email,
            'phone' => $this->phone,
            'postcode' => $defaultAddress?->postcode,
            'business_name' => $this->whenLoaded(
                'shops',
                fn () => $this->shops->first()?->businessProfile?->business_name
            ),
        ];
    }
}
