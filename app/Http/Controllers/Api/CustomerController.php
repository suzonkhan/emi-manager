<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CustomerService $customerService) {}

    /**
     * Get customers list
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $customers = $this->customerService->getCustomersByUser($request->user(), $perPage);

            return $this->success([
                'customers' => CustomerResource::collection($customers->items()),
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
     * Create new customer
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->createCustomer(
                $request->validated(),
                $request->user()
            );

            return $this->success([
                'customer' => new CustomerResource($customer),
                'message' => 'Customer created successfully',
            ], 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Get customer details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerDetails($id, $request->user());

            if (! $customer) {
                return $this->error('Customer not found or access denied', null, 404);
            }

            return $this->success([
                'customer' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update customer
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
                'customer' => new CustomerResource($customer),
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
     * Search customers
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

            return $this->success(CustomerResource::collection($customers));
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
     * Get customers with overdue EMIs
     */
    public function overdue(Request $request): JsonResponse
    {
        try {
            $overdueCustomers = $this->customerService->getOverdueCustomers($request->user());

            return $this->success([
                'customers' => CustomerResource::collection($overdueCustomers),
                'count' => $overdueCustomers->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get customers with EMIs due soon
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
                'customers' => CustomerResource::collection($dueSoonCustomers),
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
