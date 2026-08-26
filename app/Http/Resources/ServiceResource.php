<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pricing_type' => $this->pricing_type,
            'price' => $this->price,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
