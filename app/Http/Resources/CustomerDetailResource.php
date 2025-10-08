<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array for details (complete data).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nid_no' => $this->nid_no,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,

            // Product Information
            'product_type' => $this->product_type,
            'product_model' => $this->product_model,
            'product_price' => number_format($this->product_price, 2, '.', ''),
            'imei_1' => $this->imei_1,
            'imei_2' => $this->imei_2,

            // EMI Information
            'emi_per_month' => number_format($this->emi_per_month, 2, '.', ''),
            'emi_duration_months' => $this->emi_duration_months,
            'total_payable' => number_format($this->getTotalPayableAmount(), 2, '.', ''),
            'service_charge_amount' => number_format($this->getServiceChargeAmount(), 2, '.', ''),

            // Addresses
            'present_address' => $this->whenLoaded('presentAddress', function () {
                return $this->presentAddress ? new AddressResource($this->presentAddress) : null;
            }),
            'permanent_address' => $this->whenLoaded('permanentAddress', function () {
                return $this->permanentAddress ? new AddressResource($this->permanentAddress) : null;
            }),

            // Token Information
            'token' => $this->whenLoaded('token', function () {
                return $this->token ? [
                    'id' => $this->token->id,
                    'code' => $this->token->code,
                    'status' => $this->token->status,
                ] : null;
            }),

            // Creator Information
            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                    'phone' => $this->creator->phone,
                    'role' => $this->creator->getRoleNames()->first(),
                ] : null;
            }),

            // Documents
            'documents' => $this->documents ?? [],

            // Status
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Financial Summary
            'financial_summary' => [
                'product_price' => number_format($this->product_price, 2, '.', ''),
                'emi_per_month' => number_format($this->emi_per_month, 2, '.', ''),
                'emi_duration_months' => $this->emi_duration_months,
                'total_payable' => number_format($this->getTotalPayableAmount(), 2, '.', ''),
                'service_charge_amount' => number_format($this->getServiceChargeAmount(), 2, '.', ''),
                'service_charge_percentage' => $this->product_price > 0
                    ? number_format(($this->getServiceChargeAmount() / $this->product_price) * 100, 2, '.', '')
                    : '0.00',
            ],

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'defaulted' => 'Defaulted',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
