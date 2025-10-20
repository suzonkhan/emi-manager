<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'role' => $this->getRoleNames()->first(),
            'plain_password' => $this->when($request->user() && $this->canViewPassword($request->user()), $this->plain_password),
            'is_active' => $this->is_active,
            'present_address' => new AddressResource($this->whenLoaded('presentAddress')),
            'total_tokens' => $this->total_tokens ?? 0,
            'total_available_tokens' => $this->total_available_tokens ?? 0,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
        ];
    }
}
