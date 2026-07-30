<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class SyncDraftOrderTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tag_names' => ['present', 'array'],
            'tag_names.*' => ['string', 'max:100'],
        ];
    }
}
