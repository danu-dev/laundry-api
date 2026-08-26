<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'service_id' => ['required', 'exists:services,id'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['exists:extras,id'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['required_with:payment', 'integer', 'min:0'],
            'payment.method' => ['required_with:payment', 'string', 'in:CASH,QRIS,TRANSFER'],
        ];
    }
}
