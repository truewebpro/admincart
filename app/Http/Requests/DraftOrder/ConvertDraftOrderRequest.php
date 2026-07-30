<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class ConvertDraftOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // No permission system, so this isn't gated by a specific
            // permission — but still an explicit opt-in flag rather than
            // silently overriding stock shortages by default.
            'override_stock' => ['sometimes', 'boolean'],
            'send_invoice' => ['sometimes', 'boolean'],
        ];
    }
}
