<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Load environment
$app->loadEnvironmentFrom('.env');

// Test functionality
echo "Testing Password Visibility Functionality:\n\n";

// Test 1: Check if super admin exists
$superAdmin = User::whereHas('roles', function ($query) {
    $query->where('name', 'super_admin');
})->first();

if ($superAdmin) {
    echo "✓ Super Admin found: {$superAdmin->name} ({$superAdmin->email})\n";
    echo "  Plain Password: " . ($superAdmin->plain_password ?? 'NULL') . "\n";
} else {
    echo "✗ No super admin found\n";
}

echo "\n";

// Test 2: Check dealer users
$dealers = User::whereHas('roles', function ($query) {
    $query->where('name', 'dealer');
})->limit(3)->get();

echo "Dealer Users:\n";
foreach ($dealers as $dealer) {
    echo "  - {$dealer->name}: " . ($dealer->plain_password ?? 'NULL') . "\n";
}

echo "\n";

// Test 3: Test canViewPassword method
if ($superAdmin && $dealers->count() > 0) {
    $testDealer = $dealers->first();
    echo "Testing canViewPassword:\n";
    echo "  Super Admin can view dealer password: " . ($testDealer->canViewPassword($superAdmin) ? 'YES' : 'NO') . "\n";
    echo "  Dealer can view another dealer password: " . ($testDealer->canViewPassword($dealers->last()) ? 'YES' : 'NO') . "\n";
}

echo "\nTest completed!\n";