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

        // Get collectors by role (salesmen and sub-dealers can collect)
        $salesmen = User::role('salesman')->get();
        $subDealers = User::role('sub_dealer')->get();
        $collectors = $salesmen->merge($subDealers);

        if ($collectors->isEmpty()) {
            $collectors = User::take(5)->get(); // Fallback
        }

        $paymentMethods = ['cash', 'bank_transfer', 'mobile_banking', 'card', 'cheque'];

        // Define payment behavior patterns for more realistic data
        $paymentPatterns = [
            'excellent' => ['weight' => 20, 'paid_months' => [90, 100], 'on_time' => 95],  // 20% excellent payers
            'good' => ['weight' => 40, 'paid_months' => [70, 90], 'on_time' => 80],        // 40% good payers
            'average' => ['weight' => 25, 'paid_months' => [50, 70], 'on_time' => 60],     // 25% average payers
            'poor' => ['weight' => 10, 'paid_months' => [30, 50], 'on_time' => 40],        // 10% poor payers
            'defaulted' => ['weight' => 5, 'paid_months' => [0, 30], 'on_time' => 20],     // 5% defaulted
        ];

        $this->command->info('Creating installments with realistic payment patterns...');
        $progressBar = $this->command->getOutput()->createProgressBar($customers->count());

        foreach ($customers as $customer) {
            // Check if installments already exist
            if ($customer->installments()->count() > 0) {
                $progressBar->advance();

                continue;
            }

            $emiAmount = $customer->emi_per_month;
            $totalMonths = $customer->emi_duration_months;
            $startDate = Carbon::parse($customer->created_at);

            // Assign payment pattern based on weights
            $pattern = $this->selectPaymentPattern($paymentPatterns);
            $paidMonthsPercent = rand($pattern['paid_months'][0], $pattern['paid_months'][1]);
            $monthsToPay = (int) ceil(($paidMonthsPercent / 100) * $totalMonths);

            // Get a collector for this customer (same collector for consistency)
            $collector = $collectors->random();

            // Create installments
            for ($i = 1; $i <= $totalMonths; $i++) {
                $dueDate = $startDate->copy()->addMonths($i);

                // Base installment data
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

                // Only process past or current month installments
                if ($dueDate->isFuture()) {
                    Installment::create($installmentData);

                    continue;
                }

                // Determine if this installment should be paid based on pattern
                if ($i <= $monthsToPay) {
                    // Determine if paid on time based on pattern
                    $isOnTime = rand(1, 100) <= $pattern['on_time'];

                    // 90% full payment, 10% partial for paid installments
                    $isFullPayment = rand(1, 100) <= 90;

                    if ($isFullPayment) {
                        // Full payment
                        $paidDate = $isOnTime
                            ? $dueDate->copy()->subDays(rand(0, 3))  // Early or on time
                            : $dueDate->copy()->addDays(rand(1, 10)); // Late payment

                        $installmentData['status'] = 'paid';
                        $installmentData['paid_amount'] = $emiAmount;
                        $installmentData['paid_date'] = $paidDate;
                        $installmentData['collected_by'] = $collector->id;
                        $installmentData['payment_method'] = $this->selectPaymentMethod($paymentMethods);

                        if ($installmentData['payment_method'] !== 'cash') {
                            $installmentData['transaction_reference'] = 'TXN'.strtoupper(uniqid());
                        }

                        $installmentData['notes'] = $isOnTime
                            ? 'Payment received on time'
                            : 'Payment received with '.rand(1, 10).' days delay';
                    } else {
                        // Partial payment
                        $partialPercent = rand(50, 85) / 100;
                        $partialAmount = round($emiAmount * $partialPercent, 2);
                        $paidDate = $dueDate->copy()->addDays(rand(1, 15));

                        $installmentData['status'] = 'partial';
                        $installmentData['paid_amount'] = $partialAmount;
                        $installmentData['paid_date'] = $paidDate;
                        $installmentData['collected_by'] = $collector->id;
                        $installmentData['payment_method'] = $this->selectPaymentMethod($paymentMethods);

                        if ($installmentData['payment_method'] !== 'cash') {
                            $installmentData['transaction_reference'] = 'TXN'.strtoupper(uniqid());
                        }

                        $remaining = $emiAmount - $partialAmount;
                        $installmentData['notes'] = 'Partial payment received. Remaining: BDT '.number_format($remaining, 2);
                    }
                } else {
                    // Unpaid installment
                    $installmentData['status'] = 'overdue';
                    $daysOverdue = now()->diffInDays($dueDate);
                    $installmentData['notes'] = 'Payment overdue by '.$daysOverdue.' days';
                }

                Installment::create($installmentData);
            }

            // Update customer status based on payment progress
            $paidCount = $customer->installments()->where('status', 'paid')->count();
            $totalCount = $customer->installments()->count();
            $overdueCount = $customer->installments()->where('status', 'overdue')->count();

            // Update customer status based on payment history
            // Valid customer statuses: active, completed, defaulted, cancelled
            if ($paidCount === $totalCount) {
                $customer->update(['status' => 'completed']);
            } elseif ($overdueCount > 3 || ($overdueCount > 0 && $paidCount === 0)) {
                // Customers with 3+ overdue or no payments at all are defaulted
                $customer->update(['status' => 'defaulted']);
            } else {
                // Customers with some overdue but making payments remain active
                $customer->update(['status' => 'active']);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine(2);
        $this->command->info('Installments seeded successfully!');

        // Display comprehensive summary
        $this->displayDetailedSummary();
    }

    /**
     * Select payment pattern based on weights
     */
    private function selectPaymentPattern(array $patterns): array
    {
        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($patterns as $pattern) {
            $cumulative += $pattern['weight'];
            if ($random <= $cumulative) {
                return $pattern;
            }
        }

        return $patterns['average']; // Fallback
    }

    /**
     * Select payment method with realistic distribution
     */
    private function selectPaymentMethod(array $methods): string
    {
        $weights = [
            'cash' => 40,            // 40% cash
            'mobile_banking' => 35,  // 35% mobile banking (bKash, Nagad)
            'bank_transfer' => 15,   // 15% bank transfer
            'card' => 8,             // 8% card
            'cheque' => 2,           // 2% cheque
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $method => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $method;
            }
        }

        return 'cash'; // Fallback
    }

    /**
     * Display detailed summary with statistics
     */
    private function displayDetailedSummary(): void
    {
        $totalInstallments = Installment::count();
        $paidInstallments = Installment::where('status', 'paid')->count();
        $partialInstallments = Installment::where('status', 'partial')->count();
        $overdueInstallments = Installment::where('status', 'overdue')->count();
        $pendingInstallments = Installment::where('status', 'pending')->count();

        // Financial summary
        $totalAmount = Installment::sum('amount');
        $paidAmount = Installment::where('status', 'paid')->sum('paid_amount')
            + Installment::where('status', 'partial')->sum('paid_amount');
        $dueAmount = $totalAmount - $paidAmount;
        $collectionRate = $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0;

        // Customer status summary (Valid statuses: active, completed, defaulted, cancelled)
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $completedCustomers = Customer::where('status', 'completed')->count();
        $defaultedCustomers = Customer::where('status', 'defaulted')->count();
        $cancelledCustomers = Customer::where('status', 'cancelled')->count();

        // Payment method breakdown
        $paymentMethodStats = Installment::whereNotNull('payment_method')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(paid_amount) as total')
            ->groupBy('payment_method')
            ->get();

        $this->command->info("\n".str_repeat('=', 70));
        $this->command->info('📊 INSTALLMENT SEEDING SUMMARY');
        $this->command->info(str_repeat('=', 70));

        $this->command->table(
            ['Metric', 'Value', 'Percentage'],
            [
                ['Total Installments', number_format($totalInstallments), '100%'],
                ['✅ Paid', number_format($paidInstallments), round(($paidInstallments / $totalInstallments) * 100, 2).'%'],
                ['⚠️  Partial', number_format($partialInstallments), round(($partialInstallments / $totalInstallments) * 100, 2).'%'],
                ['🔴 Overdue', number_format($overdueInstallments), round(($overdueInstallments / $totalInstallments) * 100, 2).'%'],
                ['⏳ Pending', number_format($pendingInstallments), round(($pendingInstallments / $totalInstallments) * 100, 2).'%'],
            ]
        );

        $this->command->info("\n💰 FINANCIAL SUMMARY:");
        $this->command->table(
            ['Metric', 'Amount (BDT)'],
            [
                ['Total Amount Due', number_format($totalAmount, 2)],
                ['Amount Collected', number_format($paidAmount, 2)],
                ['Amount Remaining', number_format($dueAmount, 2)],
                ['Collection Rate', round($collectionRate, 2).'%'],
            ]
        );

        $this->command->info("\n👥 CUSTOMER STATUS:");
        $this->command->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Customers', $totalCustomers, '100%'],
                ['✅ Active', $activeCustomers, round(($activeCustomers / $totalCustomers) * 100, 2).'%'],
                ['🎉 Completed', $completedCustomers, round(($completedCustomers / $totalCustomers) * 100, 2).'%'],
                ['🔴 Defaulted', $defaultedCustomers, round(($defaultedCustomers / $totalCustomers) * 100, 2).'%'],
                ['❌ Cancelled', $cancelledCustomers, round(($cancelledCustomers / $totalCustomers) * 100, 2).'%'],
            ]
        );

        if ($paymentMethodStats->isNotEmpty()) {
            $this->command->info("\n💳 PAYMENT METHOD BREAKDOWN:");
            $paymentMethodTable = [];
            foreach ($paymentMethodStats as $stat) {
                $paymentMethodTable[] = [
                    ucfirst(str_replace('_', ' ', $stat->payment_method)),
                    number_format($stat->count),
                    'BDT '.number_format($stat->total, 2),
                ];
            }
            $this->command->table(['Payment Method', 'Transactions', 'Total Amount'], $paymentMethodTable);
        }

        $this->command->info("\n".str_repeat('=', 70));
        $this->command->info('✅ Realistic installment data generated successfully!');
        $this->command->info('📈 Reports will show meaningful payment patterns and trends.');
        $this->command->info(str_repeat('=', 70)."\n");
    }
}
