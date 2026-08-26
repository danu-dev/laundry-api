<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class ChangeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', [
                Order::STATUS_NEW,
                Order::STATUS_WASHING,
                Order::STATUS_IRONING,
                Order::STATUS_READY,
                Order::STATUS_COMPLETED,
            ])],
        ];
    }
}
