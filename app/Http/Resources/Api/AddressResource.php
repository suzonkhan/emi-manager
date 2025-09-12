<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'street_address' => $this->street_address,
            'landmark' => $this->landmark,
            'postal_code' => $this->postal_code,
            'division' => [
                'id' => $this->division?->id,
                'name' => $this->division?->name,
            ],
            'district' => [
                'id' => $this->district?->id,
                'name' => $this->district?->name,
            ],
            'upazilla' => [
                'id' => $this->upazilla?->id,
                'name' => $this->upazilla?->name,
            ],
        ];
    }
}
