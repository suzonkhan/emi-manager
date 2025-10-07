<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InstallmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all customers
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please run CustomerSeeder first.');

            return;
        }

        // Get a salesman user to act as collector (or use first user)
        $collector = User::role('salesman')->first() ?? User::first();

        $paymentMethods = ['cash', 'bank_transfer', 'mobile_banking', 'card', 'cheque'];

        $this->command->info('Creating installments for customers...');

        foreach ($customers as $customer) {
            // Check if installments already exist
            if ($customer->installments()->count() > 0) {
                $this->command->info("Installments already exist for customer: {$customer->name}");

                continue;
            }

            $emiAmount = $customer->emi_per_month;
            $totalMonths = $customer->emi_duration_months;
            $startDate = Carbon::parse($customer->created_at);

            // Create installments
            for ($i = 1; $i <= $totalMonths; $i++) {
                $dueDate = $startDate->copy()->addMonths($i);

                // Simulate different payment scenarios
                $installmentData = [
                    'customer_id' => $customer->id,
                    'installment_number' => $i,
                    'amount' => $emiAmount,
                    'due_date' => $dueDate,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'notes' => null,
                    'collected_by' => null,
                    'payment_method' => null,
                    'transaction_reference' => null,
                    'paid_date' => null,
                ];

                // First 2-3 months: paid
                if ($i <= rand(2, 3) && $dueDate->isPast()) {
                    $paidDate = $dueDate->copy()->subDays(rand(0, 5)); // Paid within due date or few days before
                    $installmentData['status'] = 'paid';
                    $installmentData['paid_amount'] = $emiAmount;
                    $installmentData['paid_date'] = $paidDate;
                    $installmentData['collected_by'] = $collector?->id;
                    $installmentData['payment_method'] = $paymentMethods[array_rand($paymentMethods)];

                    // Add transaction reference for non-cash payments
                    if ($installmentData['payment_method'] !== 'cash') {
                        $installmentData['transaction_reference'] = 'TXN'.strtoupper(uniqid());
                    }

                    $installmentData['notes'] = 'Payment received on time';
                }
                // Next 1-2 months: partial payment (50-80% paid)
                elseif ($i <= rand(3, 5) && $dueDate->isPast()) {
                    $partialPercent = rand(50, 80) / 100;
                    $partialAmount = round($emiAmount * $partialPercent, 2);
                    $paidDate = $dueDate->copy()->addDays(rand(1, 7)); // Paid few days late

                    $installmentData['status'] = 'partial';
                    $installmentData['paid_amount'] = $partialAmount;
                    $installmentData['paid_date'] = $paidDate;
                    $installmentData['collected_by'] = $collector?->id;
                    $installmentData['payment_method'] = $paymentMethods[array_rand($paymentMethods)];

                    if ($installmentData['payment_method'] !== 'cash') {
                        $installmentData['transaction_reference'] = 'TXN'.strtoupper(uniqid());
                    }

                    $installmentData['notes'] = 'Partial payment received';
                }
                // If due date is past and not paid: overdue
                elseif ($dueDate->isPast()) {
                    $installmentData['status'] = 'overdue';
                    $installmentData['notes'] = 'Payment overdue';
                }
                // Future installments: pending
                else {
                    $installmentData['status'] = 'pending';
                }

                Installment::create($installmentData);
            }

            $paidCount = $customer->installments()->where('status', 'paid')->count();
            $totalCount = $customer->installments()->count();

            // Update customer status based on payment progress
            if ($paidCount === $totalCount) {
                $customer->update(['status' => 'completed']);
            } elseif ($customer->installments()->where('status', 'overdue')->count() > 2) {
                $customer->update(['status' => 'defaulted']);
            } else {
                $customer->update(['status' => 'active']);
            }

            $this->command->info("Created {$totalCount} installments for {$customer->name} ({$paidCount} paid)");
        }

        $this->command->info('Installments seeded successfully!');

        // Display summary
        $totalInstallments = Installment::count();
        $paidInstallments = Installment::where('status', 'paid')->count();
        $partialInstallments = Installment::where('status', 'partial')->count();
        $overdueInstallments = Installment::where('status', 'overdue')->count();
        $pendingInstallments = Installment::where('status', 'pending')->count();

        $this->command->info("\n📊 Summary:");
        $this->command->info("Total Installments: {$totalInstallments}");
        $this->command->info("Paid: {$paidInstallments}");
        $this->command->info("Partial: {$partialInstallments}");
        $this->command->info("Overdue: {$overdueInstallments}");
        $this->command->info("Pending: {$pendingInstallments}");
    }
}
