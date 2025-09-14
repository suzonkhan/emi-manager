<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => CustomerResource::collection($this->collection),
            'meta' => [
                'total' => $this->total() ?? $this->collection->count(),
                'count' => $this->collection->count(),
                'per_page' => $this->perPage() ?? null,
                'current_page' => $this->currentPage() ?? null,
                'total_pages' => $this->lastPage() ?? null,
            ],
            'summary' => $this->when($request->has('include_summary'), function () {
                $customers = $this->collection;
                
                return [
                    'total_customers' => $customers->count(),
                    'by_status' => $customers->groupBy('status')->map->count(),
                    'financial_summary' => [
                        'total_loan_amount' => $customers->sum('loan_amount'),
                        'total_pending_amount' => $customers->sum('pending_amount'),
                        'total_paid_amount' => $customers->sum('paid_amount'),
                        'average_emi_amount' => $customers->avg('emi_amount'),
                        'total_product_value' => $customers->sum('product_price'),
                    ],
                    'overdue_customers' => $customers->filter(function ($customer) {
                        return $customer->next_emi_date && 
                               $customer->next_emi_date->isPast() && 
                               $customer->pending_amount > 0;
                    })->count(),
                    'customers_by_tenure' => $customers->groupBy('tenure_months')->map->count(),
                ];
            }),
        ];
    }
}