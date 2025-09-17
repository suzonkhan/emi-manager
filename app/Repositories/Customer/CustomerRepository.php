<?php

namespace App\Repositories\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
        return Customer::with(['address', 'token', 'createdBy'])->find($id);
    }

    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function getCustomersForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getCustomersQueryForUser($user)
            ->with(['address', 'token', 'createdBy'])
            ->latest()
            ->paginate($perPage);
    }

    public function getCustomersByStatus(string $status, ?User $user = null): Collection
    {
        $query = Customer::where('status', $status);

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['address', 'token', 'createdBy'])->get();
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
            'inactive_customers' => (clone $query)->where('status', 'inactive')->count(),
            'total_pending_amount' => (clone $query)->sum('pending_amount'),
            'total_financed_amount' => (clone $query)->sum('product_price'),
            'customers_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'average_emi_amount' => (clone $query)->avg('emi_amount'),
        ];
    }

    public function canUserAccessCustomer(User $user, Customer $customer): bool
    {
        // User can access customers they created
        if ($customer->created_by === $user->id) {
            return true;
        }

        // Check if user can access based on hierarchy
        if ($customer->createdBy) {
            return $this->roleHierarchyService->canAssignRole($user->role, $customer->createdBy->role);
        }

        return false;
    }

    public function searchCustomers(string $searchTerm, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getCustomersQueryForUser($user)
            ->where(function (Builder $query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('product_name', 'like', "%{$searchTerm}%");
            })
            ->with(['address', 'token', 'createdBy'])
            ->latest()
            ->paginate($perPage);
    }

    public function getCustomersWithOverdueEMIs(?User $user = null): Collection
    {
        $query = Customer::where('status', 'active')
            ->where('pending_amount', '>', 0)
            ->where('next_emi_date', '<', now());

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['address', 'token', 'createdBy'])->get();
    }

    public function getCustomersCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?User $user = null): Collection
    {
        $query = Customer::whereBetween('created_at', [$startDate, $endDate]);

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['address', 'token', 'createdBy'])->get();
    }

    public function getTotalPendingAmountForUser(User $user): float
    {
        return $this->getCustomersQueryForUser($user)
            ->where('status', 'active')
            ->sum('pending_amount');
    }

    public function getCustomersNearEMIDue(int $days = 7, ?User $user = null): Collection
    {
        $dueDate = Carbon::now()->addDays($days);

        $query = Customer::where('status', 'active')
            ->where('pending_amount', '>', 0)
            ->where('next_emi_date', '<=', $dueDate)
            ->where('next_emi_date', '>=', now());

        if ($user) {
            $query = $this->applyUserAccessControl($query, $user);
        }

        return $query->with(['address', 'token', 'createdBy'])->get();
    }

    protected function getCustomersQueryForUser(User $user): Builder
    {
        $query = Customer::query();

        return $this->applyUserAccessControl($query, $user);
    }

    protected function applyUserAccessControl(Builder $query, User $user): Builder
    {
        // If user has no role, return empty query
        if (!$user->role) {
            return $query->whereRaw('1 = 0'); // Returns no results
        }

        // Super admin can see all customers
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Get assignable roles for the user
        $assignableRoles = $this->roleHierarchyService->getAssignableRolesByRole($user->role);

        // User can see customers created by users in their hierarchy
        return $query->where(function (Builder $q) use ($user, $assignableRoles) {
            $q->where('created_by', $user->id)
                ->orWhereHas('createdBy', function (Builder $creatorQuery) use ($assignableRoles) {
                    $creatorQuery->whereIn('role', $assignableRoles);
                });
        });
    }
}
