<?php

namespace App\Http\Resources\Token;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'used_at' => $this->used_at?->format('Y-m-d H:i:s'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'role' => $this->creator->role,
                ];
            }),
            'assigned_to_user' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                    'role' => $this->assignedTo->role,
                ] : null;
            }),
            'used_by_user' => $this->whenLoaded('usedBy', function () {
                return $this->usedBy ? [
                    'id' => $this->usedBy->id,
                    'name' => $this->usedBy->name,
                    'email' => $this->usedBy->email,
                    'role' => $this->usedBy->role,
                ] : null;
            }),
            'assignment_chain' => $this->when($this->relationLoaded('creator') && $this->relationLoaded('assignedTo'), function () {
                $chain = [];
                
                if ($this->creator) {
                    $chain[] = [
                        'step' => 'created',
                        'user' => [
                            'id' => $this->creator->id,
                            'name' => $this->creator->name,
                            'role' => $this->creator->role,
                        ],
                        'timestamp' => $this->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
                
                if ($this->assignedTo && $this->assigned_at) {
                    $chain[] = [
                        'step' => 'assigned',
                        'user' => [
                            'id' => $this->assignedTo->id,
                            'name' => $this->assignedTo->name,
                            'role' => $this->assignedTo->role,
                        ],
                        'timestamp' => $this->assigned_at?->format('Y-m-d H:i:s'),
                    ];
                }
                
                if ($this->usedBy && $this->used_at) {
                    $chain[] = [
                        'step' => 'used',
                        'user' => [
                            'id' => $this->usedBy->id,
                            'name' => $this->usedBy->name,
                            'role' => $this->usedBy->role,
                        ],
                        'timestamp' => $this->used_at?->format('Y-m-d H:i:s'),
                    ];
                }
                
                return $chain;
            }),
        ];
    }
}