<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'in:'.implode(',', [
                Payment::METHOD_CASH,
                Payment::METHOD_QRIS,
                Payment::METHOD_TRANSFER,
            ])],
        ];
    }
}
