<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'timezone' => $this->timezone,
            'logo_path' => $this->logo_path,
            'opening_hours' => $this->opening_hours,
            'receipt_footer_message' => $this->receipt_footer_message,
        ];
    }
}
