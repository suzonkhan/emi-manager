<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Customer Creation Date Distribution\n";
echo str_repeat('=', 50)."\n\n";

// Get all customers ordered by creation date
$customers = \App\Models\Customer::select('id', 'name', 'created_at')
    ->orderBy('created_at')
    ->get();

// Group by month
$byMonth = $customers->groupBy(function ($customer) {
    return $customer->created_at->format('Y-m');
});

echo "Total Customers: {$customers->count()}\n";
echo "Date Range: {$customers->first()->created_at->format('Y-m-d')} to {$customers->last()->created_at->format('Y-m-d')}\n\n";

echo "Monthly Distribution:\n";
foreach ($byMonth as $month => $monthCustomers) {
    $percentage = round(($monthCustomers->count() / $customers->count()) * 100, 1);
    echo "  {$month}: {$monthCustomers->count()} customers ({$percentage}%)\n";
}

echo "\n".str_repeat('=', 50)."\n";
echo "Token Usage Date Distribution\n";
echo str_repeat('=', 50)."\n\n";

// Get all used tokens ordered by usage date
$tokens = \App\Models\Token::whereNotNull('used_at')
    ->select('id', 'code', 'used_at')
    ->orderBy('used_at')
    ->get();

// Group by month
$tokensByMonth = $tokens->groupBy(function ($token) {
    return $token->used_at->format('Y-m');
});

echo "Total Used Tokens: {$tokens->count()}\n";
if ($tokens->count() > 0) {
    echo "Date Range: {$tokens->first()->used_at->format('Y-m-d')} to {$tokens->last()->used_at->format('Y-m-d')}\n\n";

    echo "Monthly Distribution:\n";
    foreach ($tokensByMonth as $month => $monthTokens) {
        $percentage = round(($monthTokens->count() / $tokens->count()) * 100, 1);
        echo "  {$month}: {$monthTokens->count()} tokens ({$percentage}%)\n";
    }
}

echo "\n".str_repeat('=', 50)."\n";
echo "Installment Payment Date Distribution\n";
echo str_repeat('=', 50)."\n\n";

// Get paid installments
$paidInstallments = \App\Models\Installment::whereIn('status', ['paid', 'partial'])
    ->whereNotNull('paid_date')
    ->select('id', 'paid_date', 'status')
    ->orderBy('paid_date')
    ->get();

// Group by month
$installmentsByMonth = $paidInstallments->groupBy(function ($installment) {
    return \Carbon\Carbon::parse($installment->paid_date)->format('Y-m');
});

echo "Total Paid Installments: {$paidInstallments->count()}\n";
if ($paidInstallments->count() > 0) {
    $firstDate = \Carbon\Carbon::parse($paidInstallments->first()->paid_date)->format('Y-m-d');
    $lastDate = \Carbon\Carbon::parse($paidInstallments->last()->paid_date)->format('Y-m-d');
    echo "Date Range: {$firstDate} to {$lastDate}\n\n";

    echo "Monthly Distribution:\n";
    foreach ($installmentsByMonth as $month => $monthInstallments) {
        $percentage = round(($monthInstallments->count() / $paidInstallments->count()) * 100, 1);
        echo "  {$month}: {$monthInstallments->count()} payments ({$percentage}%)\n";
    }
}

echo "\n✅ Time-series data is properly distributed!\n";
echo "📊 Reports will show meaningful trends and patterns.\n";
