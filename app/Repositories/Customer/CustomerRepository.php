<?php

namespace App\Repositories\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        protected RoleHierarchyService $roleHierarchyService
    ) {}

    public function create(array $customerData): Customer
    {
        return Customer::create($customerData);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::with([
            'presentAddress.division',
            'presentAddress.district',
            'presentAddress.upazilla',
            'permanentAddress.division',
            'permanentAddress.district',
            'permanentAddress.upazilla',
            'token',
            'creator',
        ])->find($id);
    }

    public function findByMobile(string $mobile): ?Customer
    {
        return Customer::where('mobile', $mobile)->first();
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function getCustomersForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getCustomersQueryForUser($user)
            ->with(['presentAddress', 'permanentAddress', 'token', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function getCustomersByStatus(string $status, ?User $user = null): Collection
    {
        $query = Customer::where('status', $status);

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['presentAddress', 'permanentAddress', 'token', 'creator'])->get();
    }

    public function updateCustomer(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }

    public function deleteCustomer(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function getCustomerStatistics(User $user): array
    {
        $query = $this->getCustomersQueryForUser($user);

        return [
            'total_customers' => (clone $query)->count(),
            'active_customers' => (clone $query)->where('status', 'active')->count(),
            'completed_customers' => (clone $query)->where('status', 'completed')->count(),
            'defaulted_customers' => (clone $query)->where('status', 'defaulted')->count(),
            'cancelled_customers' => (clone $query)->where('status', 'cancelled')->count(),
            'total_product_value' => (clone $query)->sum('product_price'),
            'customers_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'average_emi_amount' => (clone $query)->avg('emi_per_month'),
        ];
    }

    public function canUserAccessCustomer(User $user, Customer $customer): bool
    {
        // User can access customers they created
        if ($customer->created_by === $user->id) {
            return true;
        }

        // Check if user can access based on hierarchy
        if ($customer->creator) {
            $creatorRole = $customer->creator->getRoleNames()->first();
            if ($creatorRole) {
                return $this->roleHierarchyService->canAssignRole($user, $creatorRole);
            }
        }

        return false;
    }

    public function searchCustomers(string $searchTerm, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getCustomersQueryForUser($user)
            ->where(function (Builder $query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('mobile', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('nid_no', 'like', "%{$searchTerm}%")
                    ->orWhere('product_type', 'like', "%{$searchTerm}%")
                    ->orWhere('product_model', 'like', "%{$searchTerm}%");
            })
            ->with(['presentAddress', 'permanentAddress', 'token', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function searchCustomersWithFilters(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->getCustomersQueryForUser($user);

        // Apply individual filters
        if (! empty($filters['nid_no'])) {
            $query->where('nid_no', 'like', '%'.$filters['nid_no'].'%');
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email', 'like', '%'.$filters['email'].'%');
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.$filters['phone'].'%');
        }

        if (! empty($filters['mobile'])) {
            $query->where('mobile', 'like', '%'.$filters['mobile'].'%');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['product_type'])) {
            $query->where('product_type', 'like', '%'.$filters['product_type'].'%');
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['dealer_id'])) {
            $query->where('dealer_id', $filters['dealer_id']);
        }

        // Apply location filters through present address relationship
        if (! empty($filters['division_id']) || ! empty($filters['district_id']) || ! empty($filters['upazilla_id'])) {
            $query->whereHas('presentAddress', function ($q) use ($filters) {
                if (! empty($filters['division_id'])) {
                    $q->where('division_id', $filters['division_id']);
                }
                if (! empty($filters['district_id'])) {
                    $q->where('district_id', $filters['district_id']);
                }
                if (! empty($filters['upazilla_id'])) {
                    $q->where('upazilla_id', $filters['upazilla_id']);
                }
            });
        }

        return $query->with(['presentAddress', 'permanentAddress', 'token', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function getCustomersWithOverdueEMIs(?User $user = null): Collection
    {
        // Since we don't have next_emi_date in the new schema, return empty collection for now
        // This would need EMI payment tracking implementation
        $query = Customer::where('status', 'active');

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['presentAddress', 'permanentAddress', 'token', 'creator'])->get();
    }

    public function getCustomersCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?User $user = null): Collection
    {
        $query = Customer::whereBetween('created_at', [$startDate, $endDate]);

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['presentAddress', 'permanentAddress', 'token', 'creator'])->get();
    }

    public function getTotalPendingAmountForUser(User $user): float
    {
        // Calculate total pending based on EMI duration and price
        // This would need EMI payment tracking implementation
        return $this->getCustomersQueryForUser($user)
            ->where('status', 'active')
            ->get()
            ->sum(function ($customer) {
                return $customer->getTotalPayableAmount();
            });
    }

    public function getCustomersNearEMIDue(int $days = 7, ?User $user = null): Collection
    {
        // Since we don't have next_emi_date in the new schema, return empty collection for now
        // This would need EMI payment tracking implementation
        $query = Customer::where('status', 'active');

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['presentAddress', 'permanentAddress', 'token', 'creator'])->get();
    }

    public function findByPhone(string $phone): ?Customer
    {
        // Alias for findByMobile for backward compatibility
        return $this->findByMobile($phone);
    }

    protected function getCustomersQueryForUser(User $user): Builder
    {
        $query = Customer::query();

        return $this->applyUserAccessControl($query, $user);
    }

    protected function applyUserAccessControl(Builder $query, User $user): Builder
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
