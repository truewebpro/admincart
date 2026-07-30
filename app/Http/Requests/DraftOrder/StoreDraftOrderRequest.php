<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreDraftOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No permission system — authorization is "logged in via admin
        // session" (enforced by route middleware) plus shop isolation
        // (enforced explicitly in the controller, not here).
        return true;
    }

    public function rules(): array
    {
        return [
            // customer_id intentionally NOT validated against a shop-scoped
            // existence check here — DraftOrderController is responsible
            // for confirming the customer actually belongs to the current
            // shop (via Customer::scopeForShop()) before it ever reaches
            // the service layer.
            'customer_id' => ['nullable', 'integer'],
            'internal_note' => ['nullable', 'string'],
            'customer_note' => ['nullable', 'string'],
        ];
    }
}
