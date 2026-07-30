<?php

namespace App\Http\Requests\DraftOrder;

use Illuminate\Foundation\Http\FormRequest;

class SetDraftOrderCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Existence checked against customers.customer_id (non-standard
            // PK) — the shop-membership check (via customer_shops) happens
            // in the controller, not here, since that requires knowing the
            // resolved shop, which a request rule can't cleanly express.
            'customer_id' => ['required', 'integer', 'exists:customers,customer_id'],
        ];
    }
}
