<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class SetDraftOrderShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:customer_addresses,address_id'],
        ];
    }
}
