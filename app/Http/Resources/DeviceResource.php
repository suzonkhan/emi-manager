<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->id,
            'customer_name' => $this->name,
            'nid_no' => $this->nid_no,
            'mobile' => $this->mobile,
            'device' => [
                'serial_number' => $this->serial_number,
                'imei_1' => $this->imei_1,
                'imei_2' => $this->imei_2,
                'fcm_token' => $this->fcm_token,
                'registered' => $this->hasDevice(),
            ],
            'device_status' => [
                'is_locked' => $this->is_device_locked,
                'is_camera_disabled' => $this->is_camera_disabled,
                'is_bluetooth_disabled' => $this->is_bluetooth_disabled,
                'is_app_hidden' => $this->is_app_hidden,
                'has_password' => $this->has_password,
                'custom_wallpaper_url' => $this->custom_wallpaper_url,
                'last_command_sent_at' => $this->last_command_sent_at?->toIso8601String(),
            ],
            'product' => [
                'type' => $this->product_type,
                'model' => $this->product_model,
                'price' => $this->product_price,
            ],
            'status' => $this->status,
            'can_receive_commands' => $this->canReceiveCommands(),
        ];
    }
}
