<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InstallmentController extends Controller
{
    /**
     * Get installment history for a customer
     */
    public function getCustomerInstallments(Customer $customer): JsonResponse
    {
        try {
            $installments = $customer->installments()
                ->with('collectedBy:id,name')
                ->orderBy('installment_number')
                ->get();

            $summary = [
                'total_installments' => $customer->emi_duration_months,
                'total_amount' => $customer->getTotalPayableAmount(),
                'total_paid' => $installments->where('status', 'paid')->sum('paid_amount'),
                'total_pending' => $installments->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'paid_count' => $installments->where('status', 'paid')->count(),
                'pending_count' => $installments->whereIn('status', ['pending', 'overdue'])->count(),
                'overdue_count' => $installments->where('status', 'overdue')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'nid_no' => $customer->nid_no,
                        'mobile' => $customer->mobile,
                        'product_type' => $customer->product_type,
                        'product_model' => $customer->product_model,
                        'product_price' => $customer->product_price,
                        'emi_per_month' => $customer->emi_per_month,
                        'emi_duration_months' => $customer->emi_duration_months,
                        'token_code' => $customer->token->code ?? null,
                        'imei_1' => $customer->imei_1,
                        'imei_2' => $customer->imei_2,
                    ],
                    'installments' => $installments,
                    'summary' => $summary,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch installment history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record an installment payment
     */
    public function recordPayment(Request $request, Installment $installment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_banking,card,cheque',
            'transaction_reference' => 'nullable|string|max:255',
            'paid_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $paidAmount = $request->paid_amount;
            $totalPaid = $installment->paid_amount + $paidAmount;

            // Determine status
            $status = 'pending';
            if ($totalPaid >= $installment->amount) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            }

            $installment->update([
                'paid_amount' => $totalPaid,
                'paid_date' => $request->paid_date,
                'status' => $status,
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference,
                'notes' => $request->notes,
                'collected_by' => auth()->id(),
            ]);

            // Check if all installments are paid
            $customer = $installment->customer;
            $allPaid = $customer->installments()
                ->where('status', '!=', 'paid')
                ->count() === 0;

            if ($allPaid) {
                $customer->update(['status' => 'completed']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $installment->fresh(['collectedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate installments for a customer (called when customer is created)
     */
    public function generateInstallments(Customer $customer): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete existing installments if any
            $customer->installments()->delete();

            $installments = [];
            $dueDate = now()->addMonth(); // First installment due next month

            for ($i = 1; $i <= $customer->emi_duration_months; $i++) {
                $installments[] = [
                    'customer_id' => $customer->id,
                    'installment_number' => $i,
                    'amount' => $customer->emi_per_month,
                    'due_date' => $dueDate->copy()->addMonths($i - 1),
                    'status' => 'pending',
                    'paid_amount' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Installment::insert($installments);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Installments generated successfully',
                'data' => [
                    'total_installments' => count($installments),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate installments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all customers with installment summary
     */
    public function getAllCustomersWithInstallments(Request $request): JsonResponse
    {
        try {
            $query = Customer::with(['token:id,code', 'installments'])
                ->withCount([
                    'installments as total_installments',
                    'installments as paid_installments' => function ($query) {
                        $query->where('status', 'paid');
                    },
                    'installments as pending_installments' => function ($query) {
                        $query->whereIn('status', ['pending', 'overdue']);
                    },
                ]);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nid_no', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $customers = $query->latest()
                ->paginate($request->per_page ?? 10);

            // Add calculated fields
            $customers->getCollection()->transform(function ($customer) {
                $customer->total_paid = $customer->installments->where('status', 'paid')->sum('paid_amount');
                $customer->total_payable = $customer->getTotalPayableAmount();
                $customer->remaining_amount = $customer->total_payable - $customer->total_paid;

                return $customer;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers->items(),
                    'pagination' => [
                        'current_page' => $customers->currentPage(),
                        'last_page' => $customers->lastPage(),
                        'per_page' => $customers->perPage(),
                        'total' => $customers->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark overdue installments
     */
    public function updateOverdueInstallments(): JsonResponse
    {
        try {
            $updated = Installment::where('status', 'pending')
                ->where('due_date', '<', now())
                ->update(['status' => 'overdue']);

            return response()->json([
                'success' => true,
                'message' => 'Overdue installments updated',
                'data' => [
                    'updated_count' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update overdue installments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
