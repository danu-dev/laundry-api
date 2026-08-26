<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'extras_total' => $this->extras_total,
            'total' => $this->total,
            'payment_status' => $this->payment_status,
            'estimated_completion_at' => $this->estimated_completion_at,
            'ready_at' => $this->ready_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,

            // Relationships if loaded
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => $this->whenLoaded('items'),
            'extras' => $this->whenLoaded('extras'),
            'payments' => $this->whenLoaded('payments'),
            'status_histories' => $this->whenLoaded('statusHistories'),

            // Expose tracking URL if the attribute was injected
            $this->mergeWhen(isset($this->tracking_url), [
                'tracking_url' => $this->tracking_url,
            ]),
        ];
    }
}
