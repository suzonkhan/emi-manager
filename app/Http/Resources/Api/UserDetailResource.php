<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->getRoleNames()->first(),
            'parent' => $this->when($this->parent, function () {
                return [
                    'id' => $this->parent->id,
                    'unique_id' => $this->parent->unique_id,
                    'name' => $this->parent->name,
                    'role' => $this->parent->getRoleNames()->first(),
                ];
            }),
            'children_count' => $this->whenLoaded('children', fn () => $this->children->count()),
            'present_address' => new AddressResource($this->whenLoaded('presentAddress')),
            'permanent_address' => new AddressResource($this->whenLoaded('permanentAddress')),
            'bkash_merchant_number' => $this->bkash_merchant_number,
            'nagad_merchant_number' => $this->nagad_merchant_number,
            'can_change_password' => $this->canChangeOwnPassword(),
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'hierarchy_level' => $this->getHierarchyLevel(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
