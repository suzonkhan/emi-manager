<?php

namespace App\Http\Resources\Token;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TokenCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => TokenResource::collection($this->collection),
            'meta' => [
                'total' => $this->total() ?? $this->collection->count(),
                'count' => $this->collection->count(),
                'per_page' => $this->perPage() ?? null,
                'current_page' => $this->currentPage() ?? null,
                'total_pages' => $this->lastPage() ?? null,
            ],
            'statistics' => $this->when($request->has('include_statistics'), function () {
                return [
                    'by_status' => $this->collection->groupBy('status')->map->count(),
                    'by_creator_role' => $this->collection->filter(function ($token) {
                        return $token->creator;
                    })->groupBy('creator.role')->map->count(),
                ];
            }),
        ];
    }
}