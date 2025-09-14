<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'product_name' => $this->product_name,
            'product_price' => $this->product_price,
            'down_payment' => $this->down_payment,
            'loan_amount' => $this->loan_amount,
            'interest_rate' => $this->interest_rate,
            'tenure_months' => $this->tenure_months,
            'emi_amount' => $this->emi_amount,
            'total_payable' => $this->getTotalPayableAmount(),
            'interest_amount' => $this->getInterestAmount(),
            'pending_amount' => $this->pending_amount,
            'paid_amount' => $this->paid_amount,
            'status' => $this->status,
            'next_emi_date' => $this->next_emi_date?->format('Y-m-d'),
            'document_path' => $this->document_path,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'address' => $this->whenLoaded('address', function () {
                return $this->address ? [
                    'id' => $this->address->id,
                    'address_line_1' => $this->address->address_line_1,
                    'address_line_2' => $this->address->address_line_2,
                    'city' => $this->address->city,
                    'state' => $this->address->state,
                    'postal_code' => $this->address->postal_code,
                    'country' => $this->address->country,
                    'full_address' => $this->address->getFullAddressAttribute(),
                ] : null;
            }),
            'token' => $this->whenLoaded('token', function () {
                return $this->token ? [
                    'id' => $this->token->id,
                    'code' => $this->token->code,
                    'status' => $this->token->status,
                ] : null;
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                    'role' => $this->createdBy->role,
                ] : null;
            }),
            'financial_summary' => [
                'loan_to_value_ratio' => $this->product_price > 0 ? round(($this->loan_amount / $this->product_price) * 100, 2) : 0,
                'payment_progress' => $this->loan_amount > 0 ? round(($this->paid_amount / $this->loan_amount) * 100, 2) : 0,
                'remaining_emis' => $this->emi_amount > 0 ? ceil($this->pending_amount / $this->emi_amount) : 0,
                'is_overdue' => $this->next_emi_date && $this->next_emi_date->isPast() && $this->pending_amount > 0,
                'days_overdue' => $this->next_emi_date && $this->next_emi_date->isPast() ? $this->next_emi_date->diffInDays(now()) : 0,
            ],
            'status_labels' => [
                'active' => 'Active',
                'inactive' => 'Inactive', 
                'completed' => 'Completed',
                'defaulted' => 'Defaulted',
            ][$this->status] ?? $this->status,
        ];
    }
}