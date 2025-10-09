<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\CustomerListResource;
use App\Services\CustomerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CustomerService $customerService) {}

    /**
     * Get customers list (minimal data for listing)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);

            // Build filters array
            $filters = [
                'nid_no' => $request->input('nid_no'),
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'mobile' => $request->input('mobile'),
                'division_id' => $request->input('division_id'),
                'district_id' => $request->input('district_id'),
                'upazilla_id' => $request->input('upazilla_id'),
                'status' => $request->input('status'),
                'product_type' => $request->input('product_type'),
                'created_by' => $request->input('created_by'),
                'dealer_id' => $request->input('dealer_id'),
            ];

            // Remove null values
            $filters = array_filter($filters, fn ($value) => $value !== null);

            $customers = $this->customerService->searchCustomersWithFilters($filters, $request->user(), $perPage);

            return $this->success([
                'customers' => CustomerListResource::collection($customers->items()),
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ],
                'filters_applied' => $filters,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Create new customer (returns detailed data)
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->createCustomer(
                $request->validated(),
                $request->user()
            );

            return $this->success([
                'customer' => new CustomerDetailResource($customer),
                'message' => 'Customer created successfully',
            ], 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Get customer details (complete data)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerDetails($id, $request->user());

            if (! $customer) {
                return $this->error('Customer not found or access denied', null, 404);
            }

            return $this->success([
                'customer' => new CustomerDetailResource($customer),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update customer (returns detailed data)
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->updateCustomer(
                $id,
                $request->validated(),
                $request->user()
            );

            if (! $customer) {
                return $this->error('Customer not found or access denied', null, 404);
            }

            return $this->success([
                'customer' => new CustomerDetailResource($customer),
                'message' => 'Customer updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Delete customer
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $success = $this->customerService->deleteCustomer($id, $request->user());

            if (! $success) {
                return $this->error('Customer not found or access denied', null, 404);
            }

            return $this->success([
                'message' => 'Customer deleted successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Search customers (returns list data)
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $perPage = $request->integer('per_page', 15);
            $customers = $this->customerService->searchCustomers(
                $request->string('query'),
                $request->user(),
                $perPage
            );

            return $this->success([
                'customers' => CustomerListResource::collection($customers->items()),
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get customer statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->customerService->getCustomerStatistics($request->user());

            return $this->success([
                'statistics' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get customers with overdue EMIs (returns list data)
     */
    public function overdue(Request $request): JsonResponse
    {
        try {
            $overdueCustomers = $this->customerService->getOverdueCustomers($request->user());

            return $this->success([
                'customers' => CustomerListResource::collection($overdueCustomers),
                'count' => $overdueCustomers->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get customers with EMIs due soon (returns list data)
     */
    public function dueSoon(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:30',
        ]);

        try {
            $days = $request->integer('days', 7);
            $dueSoonCustomers = $this->customerService->getCustomersNearEMIDue($request->user(), $days);

            return $this->success([
                'customers' => CustomerListResource::collection($dueSoonCustomers),
                'count' => $dueSoonCustomers->count(),
                'days_ahead' => $days,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get total pending amount
     */
    public function pendingAmount(Request $request): JsonResponse
    {
        try {
            $totalPending = $this->customerService->getTotalPendingAmount($request->user());

            return $this->success([
                'total_pending_amount' => $totalPending,
                'formatted_amount' => '₹'.number_format($totalPending, 2),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
