<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'minimum_quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
