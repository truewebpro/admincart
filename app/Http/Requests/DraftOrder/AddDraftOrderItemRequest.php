<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class AddDraftOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'exists:variants,variant_id'],
            'quantity' => ['required', 'integer', 'min:1'],

            // Price override — controller decides whether the current user
            // is allowed to actually use this (no permission system means
            // "allowed" for now; flagged here so it's easy to gate later
            // without touching validation).
            'unit_price' => ['nullable', 'numeric', 'min:0'],

            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'required_with:discount_type'],
        ];
    }
}
