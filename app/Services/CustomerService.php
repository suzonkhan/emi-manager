<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Token;
use App\Models\User;
use App\Repositories\Customer\CustomerRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerService
{
    public function __construct(
        private TokenService $tokenService,
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Create customer with auto-assigned token
     */
    public function createCustomer(array $customerData, User $salesman): Customer
    {
        if (! in_array($salesman->role, ['super_admin', 'dealer', 'sub_dealer', 'salesman'])) {
            throw new Exception('User role cannot create customers');
        }

        // Auto-assign an available token for the user
        $token = $this->tokenService->getAvailableTokenForUser($salesman);

        if (! $token) {
            throw new Exception('No available tokens found. Please request tokens from your administrator.');
        }

        return DB::transaction(function () use ($customerData, $salesman, $token) {
            // Determine the dealer for this customer
            // If salesman is dealer, they are the dealer
            // If salesman is sub_dealer or salesman, find their parent dealer
            $dealerId = $this->getDealerIdForSalesman($salesman);

            // Get next sequential customer ID for this dealer
            $dealerCustomerId = Customer::getNextDealerCustomerId($dealerId);

            // Create present address
            $presentAddress = Address::create([
                'street_address' => $customerData['present_address']['street_address'],
                'landmark' => $customerData['present_address']['landmark'] ?? null,
                'postal_code' => $customerData['present_address']['postal_code'] ?? null,
                'division_id' => $customerData['present_address']['division_id'],
                'district_id' => $customerData['present_address']['district_id'],
                'upazilla_id' => $customerData['present_address']['upazilla_id'],
            ]);

            // Create permanent address
            $permanentAddress = Address::create([
                'street_address' => $customerData['permanent_address']['street_address'],
                'landmark' => $customerData['permanent_address']['landmark'] ?? null,
                'postal_code' => $customerData['permanent_address']['postal_code'] ?? null,
                'division_id' => $customerData['permanent_address']['division_id'],
                'district_id' => $customerData['permanent_address']['district_id'],
                'upazilla_id' => $customerData['permanent_address']['upazilla_id'],
            ]);

            // Handle document uploads
            $documents = [];
            if (isset($customerData['documents']) && is_array($customerData['documents'])) {
                foreach ($customerData['documents'] as $document) {
                    if ($document instanceof \Illuminate\Http\UploadedFile) {
                        $documents[] = $document->store('customer-documents', 'public');
                    }
                }
            }

            // Calculate remaining amount after down payment
            $remainingAmount = $customerData['product_price'] - $customerData['down_payment'];

            // Calculate EMI per month (remaining amount / duration)
            $emiPerMonth = $remainingAmount / $customerData['emi_duration_months'];

            // Create customer
            $customer = $this->customerRepository->create([
                'nid_no' => $customerData['nid_no'],
                'name' => $customerData['name'],
                'email' => $customerData['email'] ?? null,
                'mobile' => $customerData['mobile'],
                'present_address_id' => $presentAddress->id,
                'permanent_address_id' => $permanentAddress->id,
                'token_id' => $token->id,
                'emi_duration_months' => $customerData['emi_duration_months'],
                'product_type' => $customerData['product_type'],
                'product_model' => $customerData['product_model'] ?? null,
                'product_price' => $customerData['product_price'],
                'down_payment' => $customerData['down_payment'],
                'emi_per_month' => round($emiPerMonth, 2),
                'imei_1' => $customerData['imei_1'] ?? null,
                'imei_2' => $customerData['imei_2'] ?? null,
                'serial_number' => $customerData['serial_number'],
                'created_by' => $salesman->id,
                'dealer_id' => $dealerId,
                'dealer_customer_id' => $dealerCustomerId,
                'documents' => $documents,
                'status' => 'active',
            ]);

            // Complete token usage with assignment history tracking
            $this->tokenService->completeTokenUsage($token, $customer, $salesman);

            // Generate installments for the customer
            $this->generateInstallments($customer);

            return $customer->load(['presentAddress', 'permanentAddress', 'token', 'creator']);
        });
    }

    /**
     * Generate installments for a customer
     */
    private function generateInstallments(Customer $customer): void
    {
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
    }

    /**
     * Get customers for user with pagination
     */
    public function getCustomersByUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->getCustomersForUser($user, $perPage);
    }

    /**
     * Get customer details
     */
    public function getCustomerDetails(int $customerId, User $user): ?Customer
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer || ! $this->customerRepository->canUserAccessCustomer($user, $customer)) {
            return null;
        }

        return $customer;
    }

    /**
     * Update customer
     */
    public function updateCustomer(int $customerId, array $updateData, User $user): ?Customer
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer || ! $this->customerRepository->canUserAccessCustomer($user, $customer)) {
            return null;
        }

        return DB::transaction(function () use ($customer, $updateData) {
            // Handle document uploads if provided
            if (isset($updateData['documents']) && is_array($updateData['documents'])) {
                $documents = $customer->documents ?? [];
                foreach ($updateData['documents'] as $document) {
                    if ($document instanceof \Illuminate\Http\UploadedFile) {
                        $documents[] = $document->store('customer-documents', 'public');
                    }
                }
                $updateData['documents'] = $documents;
            }

            // Update present address if provided
            if (isset($updateData['present_address'])) {
                $customer->presentAddress->update([
                    'street_address' => $updateData['present_address']['street_address'] ?? $customer->presentAddress->street_address,
                    'landmark' => $updateData['present_address']['landmark'] ?? $customer->presentAddress->landmark,
                    'postal_code' => $updateData['present_address']['postal_code'] ?? $customer->presentAddress->postal_code,
                    'division_id' => $updateData['present_address']['division_id'] ?? $customer->presentAddress->division_id,
                    'district_id' => $updateData['present_address']['district_id'] ?? $customer->presentAddress->district_id,
                    'upazilla_id' => $updateData['present_address']['upazilla_id'] ?? $customer->presentAddress->upazilla_id,
                ]);
                unset($updateData['present_address']);
            }

            // Update permanent address if provided
            if (isset($updateData['permanent_address'])) {
                $customer->permanentAddress->update([
                    'street_address' => $updateData['permanent_address']['street_address'] ?? $customer->permanentAddress->street_address,
                    'landmark' => $updateData['permanent_address']['landmark'] ?? $customer->permanentAddress->landmark,
                    'postal_code' => $updateData['permanent_address']['postal_code'] ?? $customer->permanentAddress->postal_code,
                    'division_id' => $updateData['permanent_address']['division_id'] ?? $customer->permanentAddress->division_id,
                    'district_id' => $updateData['permanent_address']['district_id'] ?? $customer->permanentAddress->district_id,
                    'upazilla_id' => $updateData['permanent_address']['upazilla_id'] ?? $customer->permanentAddress->upazilla_id,
                ]);
                unset($updateData['permanent_address']);
            }

            $this->customerRepository->updateCustomer($customer, $updateData);

            return $customer->fresh(['presentAddress', 'permanentAddress', 'token', 'creator']);
        });
    }

    /**
     * Search customers with comprehensive filters
     */
    public function searchCustomersWithFilters(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->searchCustomersWithFilters($filters, $user, $perPage);
    }

    /**
     * Search customers (legacy method - text search only)
     */
    public function searchCustomers(string $searchTerm, User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->searchCustomers($searchTerm, $user, $perPage);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(User $user): array
    {
        return $this->customerRepository->getCustomerStatistics($user);
    }

    /**
     * Get customers with overdue EMIs
     */
    public function getOverdueCustomers(User $user)
    {
        return $this->customerRepository->getCustomersWithOverdueEMIs($user);
    }

    /**
     * Get customers with EMIs due soon
     */
    public function getCustomersNearEMIDue(User $user, int $days = 7)
    {
        return $this->customerRepository->getCustomersNearEMIDue($days, $user);
    }

    /**
     * Get total pending amount for user
     */
    public function getTotalPendingAmount(User $user): float
    {
        return $this->customerRepository->getTotalPendingAmountForUser($user);
    }

    /**
     * Delete customer
     */
    public function deleteCustomer(int $customerId, User $user): bool
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer || ! $this->customerRepository->canUserAccessCustomer($user, $customer)) {
            return false;
        }

        return DB::transaction(function () use ($customer) {
            // Delete associated documents if exist
            if ($customer->documents && is_array($customer->documents)) {
                foreach ($customer->documents as $document) {
                    Storage::disk('public')->delete($document);
                }
            }

            return $this->customerRepository->deleteCustomer($customer);
        });
    }

    /**
     * Get dealer ID for the given user
     * - If user is dealer, return their ID
     * - If user is sub_dealer or salesman, find their parent dealer
     * - If super_admin, return null (handled separately)
     */
    private function getDealerIdForSalesman(User $salesman): ?int
    {
        // If salesman is a dealer, they are the dealer
        if ($salesman->role === 'dealer') {
            return $salesman->id;
        }

        // If salesman is sub_dealer or salesman, find their parent dealer
        if (in_array($salesman->role, ['sub_dealer', 'salesman'])) {
            return $this->findDealerParent($salesman);
        }

        // Super admin case - return null or handle specially
        return null;
    }

    /**
     * Recursively find the dealer parent for a user
     */
    private function findDealerParent(User $user): ?int
    {
        if (! $user->parent_id) {
            return null;
        }

        $parent = User::find($user->parent_id);

        if (! $parent) {
            return null;
        }

        // If parent is a dealer, we found it
        if ($parent->role === 'dealer') {
            return $parent->id;
        }

        // Otherwise, keep searching up the tree
        return $this->findDealerParent($parent);
    }
}
