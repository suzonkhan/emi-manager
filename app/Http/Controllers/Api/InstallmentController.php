<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\User;
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
                'collected_by' => $request->user()->id,
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
            $user = $request->user();

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

            // Apply hierarchical access control
            $query = $this->applyUserAccessControl($query, $user);

            // Apply filters
            if ($request->filled('product_type')) {
                $query->where('product_type', $request->product_type);
            }

            if ($request->filled('token')) {
                $query->whereHas('token', function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->token}%");
                });
            }

            if ($request->filled('serial_number')) {
                $query->where('serial_number', 'like', "%{$request->serial_number}%");
            }

            if ($request->filled('name')) {
                $query->where('name', 'like', "%{$request->name}%");
            }

            if ($request->filled('email')) {
                $query->where('email', 'like', "%{$request->email}%");
            }

            if ($request->filled('mobile')) {
                $query->where('mobile', 'like', "%{$request->mobile}%");
            }

            if ($request->filled('division_id')) {
                $query->whereHas('presentAddress', function ($q) use ($request) {
                    $q->where('division_id', $request->division_id);
                });
            }

            if ($request->filled('district_id')) {
                $query->whereHas('presentAddress', function ($q) use ($request) {
                    $q->where('district_id', $request->district_id);
                });
            }

            if ($request->filled('upazilla_id')) {
                $query->whereHas('presentAddress', function ($q) use ($request) {
                    $q->where('upazilla_id', $request->upazilla_id);
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

    /**
     * Apply hierarchical access control to customer query
     */
    protected function applyUserAccessControl($query, User $user)
    {
        // If user has no role, return empty query
        if (! $user->role) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Super admin can see all customers
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Get all users in this user's hierarchy (downline)
        $hierarchyUserIds = $this->getUserHierarchyIds($user);

        // User can see customers created by themselves or their downline users
        return $query->whereIn('created_by', $hierarchyUserIds);
    }

    /**
     * Get all user IDs in the user's hierarchy (including themselves)
     */
    protected function getUserHierarchyIds(User $user): array
    {
        $userIds = [$user->id]; // Include the user themselves

        // Get direct children
        $children = User::where('parent_id', $user->id)->get();

        foreach ($children as $child) {
            // Recursively get all descendants
            $userIds = array_merge($userIds, $this->getUserHierarchyIds($child));
        }

        return array_unique($userIds);
    }
}
