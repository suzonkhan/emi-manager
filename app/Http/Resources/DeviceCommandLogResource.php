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
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sent_by' => $this->whenLoaded('sentBy', function () {
                return [
                    'id' => $this->sentBy->id,
                    'name' => $this->sentBy->name,
                    'email' => $this->sentBy->email,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
