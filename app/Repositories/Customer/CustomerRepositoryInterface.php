<?php

namespace App\Repositories\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    public function create(array $customerData): Customer;

    public function findById(int $id): ?Customer;

    public function findByPhone(string $phone): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function getCustomersForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function getCustomersByStatus(string $status, ?User $user = null): Collection;

    public function updateCustomer(Customer $customer, array $data): bool;

    public function deleteCustomer(Customer $customer): bool;

    public function getCustomerStatistics(User $user): array;

    public function canUserAccessCustomer(User $user, Customer $customer): bool;

    public function searchCustomers(string $searchTerm, User $user, int $perPage = 15): LengthAwarePaginator;

    public function getCustomersWithOverdueEMIs(?User $user = null): Collection;

    public function getCustomersCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?User $user = null): Collection;

    public function getTotalPendingAmountForUser(User $user): float;

    public function getCustomersNearEMIDue(int $days = 7, ?User $user = null): Collection;
}
