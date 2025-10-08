<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
{
    /**
     * Transform the resource into an array for listing (minimal data).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nid_no' => $this->nid_no,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'product_type' => $this->product_type,
            'product_model' => $this->product_model,
            'emi_per_month' => number_format($this->emi_per_month, 2, '.', ''),
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
