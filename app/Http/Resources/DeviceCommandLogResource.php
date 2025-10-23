<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceCommandLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'command' => $this->command,
            'command_data' => $this->command_data,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'error_message' => $this->error_message,
            'notes' => $this->error_message, // Include notes field for frontend
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sent_by_user' => $this->whenLoaded('sentBy', function () {
                return [
                    'id' => $this->sentBy->id,
                    'name' => $this->sentBy->name,
                    'email' => $this->sentBy->email,
                ];
            }),
            'has_location_response' => $this->command === 'REQUEST_LOCATION' ? $this->hasLocationResponse() : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
