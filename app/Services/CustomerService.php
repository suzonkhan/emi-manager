<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Customer;
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
     * Create customer with token (salesman function)
     */
    public function createCustomer(array $customerData, User $salesman): Customer
    {
        if (! in_array($salesman->role, ['super_admin', 'dealer', 'sub_dealer', 'salesman'])) {
            throw new Exception('User role cannot create customers');
        }

        // Validate token
        $token = $this->tokenService->useTokenForCustomer($salesman, $customerData['token_code']);

        return DB::transaction(function () use ($customerData, $salesman, $token) {
            // Create address
            $address = Address::create([
                'address_line_1' => $customerData['address_line_1'],
                'address_line_2' => $customerData['address_line_2'] ?? null,
                'city' => $customerData['city'],
                'state' => $customerData['state'],
                'postal_code' => $customerData['postal_code'],
                'country' => $customerData['country'],
            ]);

            // Calculate loan amount and EMI
            $loanAmount = $customerData['product_price'] - $customerData['down_payment'];
            $emiAmount = $this->calculateEMI(
                $loanAmount,
                $customerData['interest_rate'],
                $customerData['tenure_months']
            );

            // Handle document upload
            $documentPath = null;
            if (isset($customerData['document'])) {
                $documentPath = $customerData['document']->store('customer-documents', 'public');
            }

            // Create customer
            $customer = $this->customerRepository->create([
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? null,
                'product_name' => $customerData['product_name'],
                'product_price' => $customerData['product_price'],
                'down_payment' => $customerData['down_payment'],
                'loan_amount' => $loanAmount,
                'interest_rate' => $customerData['interest_rate'],
                'tenure_months' => $customerData['tenure_months'],
                'emi_amount' => $emiAmount,
                'pending_amount' => $loanAmount,
                'paid_amount' => 0,
                'next_emi_date' => now()->addMonth(),
                'status' => 'active',
                'address_id' => $address->id,
                'token_id' => $token->id,
                'created_by' => $salesman->id,
                'document_path' => $documentPath,
            ]);

            // Mark token as used
            $this->tokenService->markTokenAsUsed($token);

            return $customer->load(['address', 'token', 'createdBy']);
        });
    }

    /**
     * Calculate EMI based on loan amount, interest rate, and tenure
     */
    private function calculateEMI(float $loanAmount, float $interestRate, int $tenureMonths): float
    {
        $monthlyRate = ($interestRate / 100) / 12;
        
        if ($monthlyRate == 0) {
            return $loanAmount / $tenureMonths;
        }

        $emi = $loanAmount * $monthlyRate * pow(1 + $monthlyRate, $tenureMonths) / 
               (pow(1 + $monthlyRate, $tenureMonths) - 1);

        return round($emi, 2);
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

        // Handle document upload if provided
        if (isset($updateData['document'])) {
            // Delete old document if exists
            if ($customer->document_path) {
                Storage::disk('public')->delete($customer->document_path);
            }
            
            $updateData['document_path'] = $updateData['document']->store('customer-documents', 'public');
            unset($updateData['document']);
        }

        // Update address if provided
        if (isset($updateData['address_line_1'])) {
            $addressData = [
                'address_line_1' => $updateData['address_line_1'],
                'address_line_2' => $updateData['address_line_2'] ?? null,
                'city' => $updateData['city'],
                'state' => $updateData['state'],
                'postal_code' => $updateData['postal_code'],
                'country' => $updateData['country'],
            ];

            $customer->address->update($addressData);

            // Remove address data from customer update
            unset($updateData['address_line_1'], $updateData['address_line_2'], 
                  $updateData['city'], $updateData['state'], 
                  $updateData['postal_code'], $updateData['country']);
        }

        $this->customerRepository->updateCustomer($customer, $updateData);

        return $customer->fresh(['address', 'token', 'createdBy']);
    }

    /**
     * Search customers
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

        // Delete associated document if exists
        if ($customer->document_path) {
            Storage::disk('public')->delete($customer->document_path);
        }

        return $this->customerRepository->deleteCustomer($customer);
    }
}
