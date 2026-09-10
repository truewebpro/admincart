<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tenancy itself is checked in the controller against session('shop_id');
        // this is the operator-permission check only.
        return $this->user()?->can('refund', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount'          => ['nullable', 'numeric', 'min:0.01'], // omit for a full refund
            'action'          => ['nullable', 'in:auto,refund,cancel'],
            'reason'          => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function amountMinor(): ?int
    {
        return $this->filled('amount')
            ? (int) round(((float) $this->input('amount')) * 100)
            : null;
    }
}
