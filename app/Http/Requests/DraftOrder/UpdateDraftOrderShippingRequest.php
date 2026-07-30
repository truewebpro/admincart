<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftOrderShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_method' => ['sometimes', 'nullable', 'string', 'max:100'],
            'courier' => ['sometimes', 'nullable', 'string', 'max:100'],
            'shipping_total' => ['sometimes', 'numeric', 'min:0'],
            'requested_delivery_date' => ['sometimes', 'nullable', 'date'],
            'collection_only' => ['sometimes', 'boolean'],
            'billing_same_as_shipping' => ['sometimes', 'boolean'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'shipping_address' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
