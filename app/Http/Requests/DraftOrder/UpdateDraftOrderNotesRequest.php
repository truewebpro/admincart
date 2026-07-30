<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftOrderNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'internal_note' => ['nullable', 'string'],
            'customer_note' => ['nullable', 'string'],
        ];
    }
}
