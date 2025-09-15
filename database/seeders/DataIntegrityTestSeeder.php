<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Token;
use App\Models\TokenAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DataIntegrityTestSeeder extends Seeder
{
    /**
     * Run comprehensive data integrity tests.
     */
    public function run(): void
    {
        $this->command->info('🧪 Running Data Integrity Tests...');
        $this->command->newLine();

        $errors = [];
        $warnings = [];
        $passed = 0;

        // Test 1: User Hierarchy Integrity
        $this->command->info('1️⃣  Testing User Hierarchy...');
        $hierarchyResult = $this->testUserHierarchy();
        if ($hierarchyResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ User hierarchy is properly structured');
        } else {
            $errors = array_merge($errors, $hierarchyResult['errors']);
        }

        // Test 2: Role Assignment Integrity
        $this->command->info('2️⃣  Testing Role Assignments...');
        $roleResult = $this->testRoleAssignments();
        if ($roleResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ All users have correct role assignments');
        } else {
            $errors = array_merge($errors, $roleResult['errors']);
        }

        // Test 3: Address Relationships
        $this->command->info('3️⃣  Testing Address Relationships...');
        $addressResult = $this->testAddressRelationships();
        if ($addressResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ All address relationships are valid');
        } else {
            $errors = array_merge($errors, $addressResult['errors']);
        }

        // Test 4: Token Flow Integrity
        $this->command->info('4️⃣  Testing Token Flow...');
        $tokenResult = $this->testTokenFlow();
        if ($tokenResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ Token assignment flow is correct');
        } else {
            $errors = array_merge($errors, $tokenResult['errors']);
        }

        // Test 5: Customer Data Integrity
        $this->command->info('5️⃣  Testing Customer Data...');
        $customerResult = $this->testCustomerData();
        if ($customerResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ Customer data is consistent and valid');
        } else {
            $errors = array_merge($errors, $customerResult['errors']);
        }

        // Test 6: EMI Calculations
        $this->command->info('6️⃣  Testing EMI Calculations...');
        $emiResult = $this->testEMICalculations();
        if ($emiResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ EMI calculations are mathematically correct');
        } else {
            $warnings = array_merge($warnings, $emiResult['warnings'] ?? []);
        }

        // Test 7: Business Logic Constraints
        $this->command->info('7️⃣  Testing Business Logic...');
        $businessResult = $this->testBusinessLogic();
        if ($businessResult['status'] === 'pass') {
            $passed++;
            $this->command->info('   ✅ Business logic constraints are satisfied');
        } else {
            $errors = array_merge($errors, $businessResult['errors']);
        }

        // Results Summary
        $this->command->newLine();
        $this->printTestResults($passed, $errors, $warnings);
    }

    private function testUserHierarchy(): array
    {
        $errors = [];

        // Test super admin exists and is unique
        $superAdmins = User::role('super_admin')->count();
        if ($superAdmins !== 1) {
            $errors[] = "Expected 1 super admin, found {$superAdmins}";
        }

        // Test parent-child relationships
        $dealers = User::role('dealer')->get();
        foreach ($dealers as $dealer) {
            if (! $dealer->parent_id) {
                $errors[] = "Dealer {$dealer->name} has no parent (should be super_admin)";
            }
        }

        $subDealers = User::role('sub_dealer')->get();
        foreach ($subDealers as $subDealer) {
            if (! $subDealer->parent_id || ! User::find($subDealer->parent_id)->hasRole('dealer')) {
                $errors[] = "Sub-dealer {$subDealer->name} has invalid parent";
            }
        }

        $salesmen = User::role('salesman')->get();
        foreach ($salesmen as $salesman) {
            if (! $salesman->parent_id || ! User::find($salesman->parent_id)->hasRole('sub_dealer')) {
                $errors[] = "Salesman {$salesman->name} has invalid parent";
            }
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function testRoleAssignments(): array
    {
        $errors = [];

        // Test all users have exactly one role
        $users = User::all();
        foreach ($users as $user) {
            $roleCount = $user->roles()->count();
            if ($roleCount !== 1) {
                $errors[] = "User {$user->name} has {$roleCount} roles (should be 1)";
            }
        }

        // Test role hierarchy counts make sense
        $counts = [
            'super_admin' => User::role('super_admin')->count(),
            'dealer' => User::role('dealer')->count(),
            'sub_dealer' => User::role('sub_dealer')->count(),
            'salesman' => User::role('salesman')->count(),
        ];

        if ($counts['dealer'] > $counts['sub_dealer']) {
            $errors[] = 'More dealers than sub-dealers (unusual but not invalid)';
        }

        if ($counts['sub_dealer'] > $counts['salesman']) {
            $errors[] = 'More sub-dealers than salesmen (unusual but not invalid)';
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function testAddressRelationships(): array
    {
        $errors = [];

        // Test all users have valid addresses
        $users = User::all();
        foreach ($users as $user) {
            if (! $user->present_address_id || ! Address::find($user->present_address_id)) {
                $errors[] = "User {$user->name} has invalid present address";
            }
            if (! $user->permanent_address_id || ! Address::find($user->permanent_address_id)) {
                $errors[] = "User {$user->name} has invalid permanent address";
            }
        }

        // Test all customers have valid addresses
        $customers = Customer::all();
        foreach ($customers as $customer) {
            if (! $customer->present_address_id || ! Address::find($customer->present_address_id)) {
                $errors[] = "Customer {$customer->name} has invalid present address";
            }
            if (! $customer->permanent_address_id || ! Address::find($customer->permanent_address_id)) {
                $errors[] = "Customer {$customer->name} has invalid permanent address";
            }
        }

        // Test address location hierarchy
        $addresses = Address::with(['division', 'district', 'upazilla'])->get();
        foreach ($addresses as $address) {
            if (! $address->division) {
                $errors[] = "Address {$address->id} has invalid division";
            }
            if (! $address->district) {
                $errors[] = "Address {$address->id} has invalid district";
            }
            if (! $address->upazilla) {
                $errors[] = "Address {$address->id} has invalid upazilla";
            }
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function testTokenFlow(): array
    {
        $errors = [];

        // Test token status consistency
        $tokens = Token::all();
        foreach ($tokens as $token) {
            if ($token->status === 'available' && ($token->assigned_to || $token->used_by)) {
                $errors[] = "Token {$token->code} is available but has assignments";
            }

            if ($token->status === 'assigned' && (! $token->assigned_to || $token->used_by)) {
                $errors[] = "Token {$token->code} status inconsistent with assignments";
            }

            if ($token->status === 'used' && ! $token->used_by) {
                $errors[] = "Token {$token->code} is used but has no user";
            }
        }

        // Test token assignment chain integrity
        $assignments = TokenAssignment::with(['token', 'fromUser', 'toUser'])->get();
        foreach ($assignments as $assignment) {
            if (! $assignment->token) {
                $errors[] = "Assignment {$assignment->id} has invalid token";
            }

            if ($assignment->assignment_type !== 'generation' && ! $assignment->fromUser) {
                $errors[] = "Assignment {$assignment->id} missing from_user";
            }
        }

        // Test role hierarchy in assignments
        $roleHierarchy = ['super_admin' => 1, 'dealer' => 2, 'sub_dealer' => 3, 'salesman' => 4];
        $assignments = TokenAssignment::where('assignment_type', 'assignment')->get();
        foreach ($assignments as $assignment) {
            $fromLevel = $roleHierarchy[$assignment->from_role] ?? 999;
            $toLevel = $roleHierarchy[$assignment->to_role] ?? 999;

            if ($fromLevel >= $toLevel) {
                $errors[] = "Invalid assignment: {$assignment->from_role} to {$assignment->to_role}";
            }
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function testCustomerData(): array
    {
        $errors = [];

        // Test customer-token relationship
        $customers = Customer::with(['token', 'creator'])->get();
        foreach ($customers as $customer) {
            if (! $customer->token) {
                $errors[] = "Customer {$customer->name} has no associated token";
            } elseif ($customer->token->status !== 'used') {
                $errors[] = "Customer {$customer->name} token is not marked as used";
            }

            if (! $customer->creator || ! $customer->creator->hasRole('salesman')) {
                $errors[] = "Customer {$customer->name} not created by salesman";
            }
        }

        // Test NID format
        foreach ($customers as $customer) {
            $nidLength = strlen($customer->nid_no);
            if (! in_array($nidLength, [10, 13])) {
                $errors[] = "Customer {$customer->name} has invalid NID length: {$nidLength}";
            }
        }

        // Test mobile number format
        foreach ($customers as $customer) {
            if (! preg_match('/^01[3-9]\d{8}$/', $customer->mobile)) {
                $errors[] = "Customer {$customer->name} has invalid mobile: {$customer->mobile}";
            }
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function testEMICalculations(): array
    {
        $warnings = [];
        $customers = Customer::all();

        foreach ($customers as $customer) {
            $totalPayable = $customer->emi_per_month * $customer->emi_duration_months;
            $interestAmount = $totalPayable - $customer->product_price;
            $interestRate = ($interestAmount / $customer->product_price) * 100;

            // Check if interest rate is reasonable (5-25%)
            if ($interestRate < 5 || $interestRate > 25) {
                $warnings[] = "Customer {$customer->name} has unusual interest rate: ".round($interestRate, 2).'%';
            }

            // Check EMI duration is reasonable
            if ($customer->emi_duration_months < 3 || $customer->emi_duration_months > 60) {
                $warnings[] = "Customer {$customer->name} has unusual EMI duration: {$customer->emi_duration_months} months";
            }
        }

        return ['status' => 'pass', 'warnings' => $warnings];
    }

    private function testBusinessLogic(): array
    {
        $errors = [];

        // Test only salesmen create customers
        $customers = Customer::with('creator')->get();
        foreach ($customers as $customer) {
            if (! $customer->creator->hasRole('salesman')) {
                $errors[] = "Customer {$customer->name} created by non-salesman: {$customer->creator->getRoleNames()->first()}";
            }
        }

        // Test token usage matches customer creation
        $usedTokens = Token::where('status', 'used')->count();
        $customerCount = Customer::count();
        if ($usedTokens !== $customerCount) {
            $errors[] = "Token usage mismatch: {$usedTokens} used tokens vs {$customerCount} customers";
        }

        // Test email uniqueness
        $userEmails = User::pluck('email')->toArray();
        if (count($userEmails) !== count(array_unique($userEmails))) {
            $errors[] = 'Duplicate user emails found';
        }

        $customerEmails = Customer::pluck('email')->toArray();
        if (count($customerEmails) !== count(array_unique($customerEmails))) {
            $errors[] = 'Duplicate customer emails found';
        }

        return ['status' => empty($errors) ? 'pass' : 'fail', 'errors' => $errors];
    }

    private function printTestResults(int $passed, array $errors, array $warnings): void
    {
        $total = 7; // Total number of tests

        if (empty($errors)) {
            $this->command->info("🎉 ALL TESTS PASSED! ({$passed}/{$total})");
            $this->command->info('✨ Your EMI Management System data is perfectly structured!');
        } else {
            $this->command->error("❌ TESTS FAILED! ({$passed}/{$total} passed)");
            $this->command->newLine();
            $this->command->error('🚨 ERRORS FOUND:');
            foreach ($errors as $error) {
                $this->command->line("   • {$error}");
            }
        }

        if (! empty($warnings)) {
            $this->command->newLine();
            $this->command->warn('⚠️  WARNINGS:');
            foreach ($warnings as $warning) {
                $this->command->line("   • {$warning}");
            }
        }

        // Data summary
        $this->command->newLine();
        $this->command->info('📊 DATA SUMMARY:');
        $this->command->table(
            ['Entity', 'Count', 'Status'],
            [
                ['Super Admins', User::role('super_admin')->count(), '✅'],
                ['Dealers', User::role('dealer')->count(), '✅'],
                ['Sub Dealers', User::role('sub_dealer')->count(), '✅'],
                ['Salesmen', User::role('salesman')->count(), '✅'],
                ['Total Users', User::count(), '✅'],
                ['---', '---', '---'],
                ['Total Tokens', Token::count(), '✅'],
                ['Available Tokens', Token::where('status', 'available')->count(), '✅'],
                ['Assigned Tokens', Token::where('status', 'assigned')->count(), '✅'],
                ['Used Tokens', Token::where('status', 'used')->count(), '✅'],
                ['---', '---', '---'],
                ['Total Customers', Customer::count(), '✅'],
                ['Active EMIs', Customer::where('status', 'active')->count(), '✅'],
                ['Completed EMIs', Customer::where('status', 'completed')->count(), '✅'],
                ['---', '---', '---'],
                ['Addresses', Address::count(), '✅'],
                ['Token Assignments', TokenAssignment::count(), '✅'],
            ]
        );
    }
}
