<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\ReportService;

echo "Testing Report System\n";
echo "====================\n\n";

// Get super admin - try different queries
$user = User::whereHas('roles', function ($query) {
    $query->where('name', 'super_admin');
})->first();

// If not found, try the first user with role
if (! $user) {
    $user = User::first();
}

if (! $user) {
    echo "❌ No users found in database\n";
    exit(1);
}

echo "✅ Testing as: {$user->name} ({$user->role})\n\n";

$service = new ReportService;
$dateRange = [
    'start_date' => '2024-01-01',
    'end_date' => '2025-12-31',
];

// Test 1: Sales Report
echo "1. Testing Sales Report...\n";
try {
    $report = $service->generateSalesReport($dateRange, $user);
    echo '   ✅ Sales Report: '.count($report['data'])." records\n";
    echo '   ✅ Total: BDT '.number_format($report['total'])."\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 2: Installments Report
echo "2. Testing Installments Report...\n";
try {
    $report = $service->generateInstallmentsReport($dateRange, $user);
    echo '   ✅ Installments Report: '.count($report['data'])." records\n";
    echo '   ✅ Total Price: BDT '.number_format($report['total_price'])."\n";
    echo '   ✅ Total Paid: BDT '.number_format($report['total_paid'])."\n";
    echo '   ✅ Total Remaining: BDT '.number_format($report['total_remaining'])."\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 3: Collections Report
echo "3. Testing Collections Report...\n";
try {
    $report = $service->generateCollectionsReport($dateRange, $user);
    echo '   ✅ Collections Report: '.count($report['data'])." records\n";
    echo '   ✅ Total Collected: BDT '.number_format($report['total'])."\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 4: Products Report
echo "4. Testing Products Report...\n";
try {
    $report = $service->generateProductsReport($dateRange, $user);
    echo '   ✅ Products Report: '.count($report['data'])." product types\n";
    echo '   ✅ Total Quantity: '.$report['total_qty']."\n";
    echo '   ✅ Total Price: BDT '.number_format($report['total_price'])."\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 5: Customers Report
echo "5. Testing Customers Report...\n";
try {
    $report = $service->generateCustomersReport($dateRange, $user);
    echo '   ✅ Customers Report: '.count($report['data'])." customers\n";
    echo '   ✅ Total Price: BDT '.number_format($report['total_price'])."\n";
    echo '   ✅ Total Paid: BDT '.number_format($report['total_paid'])."\n";
    echo '   ✅ Total Due: BDT '.number_format($report['total_due'])."\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 6: Dealers Report
echo "6. Testing Dealers Report...\n";
try {
    $report = $service->generateDealersReport($dateRange, $user);
    echo '   ✅ Dealers Report: '.count($report['data'])." dealers\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

// Test 7: Sub-Dealers Report
echo "7. Testing Sub-Dealers Report...\n";
try {
    $report = $service->generateSubDealersReport($dateRange, $user);
    echo '   ✅ Sub-Dealers Report: '.count($report['data'])." sub-dealers\n\n";
} catch (Exception $e) {
    echo '   ❌ Error: '.$e->getMessage()."\n\n";
}

echo "\n====================\n";
echo "All tests completed!\n";
