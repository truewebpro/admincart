<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'discount_type' => ['sometimes', 'nullable', 'in:fixed,percentage'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
