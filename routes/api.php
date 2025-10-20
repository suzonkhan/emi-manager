<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PresetMessageController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

// Location routes for addresses (public access for now)
Route::prefix('locations')->group(function () {
    Route::get('/divisions', [LocationController::class, 'getDivisions']);
    Route::get('/districts/{division_id}', [LocationController::class, 'getDistricts']);
    Route::get('/upazillas/{district_id}', [LocationController::class, 'getUpazillas']);
});

// Public device routes (no authentication required)
Route::prefix('devices')->group(function () {
    // Device registration (for automatic app installation)
    Route::post('/register', [DeviceController::class, 'register']);
    
    // Device status check (for factory reset recovery)
    Route::post('/status/check', [DeviceController::class, 'checkStatus']);
    
    // Device command response (device sends any command response)
    Route::post('/command-response', [DeviceController::class, 'commandResponse']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/available-roles', [UserController::class, 'getAvailableRoles']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::get('/{id}/password', [UserController::class, 'getPassword']);
    });

    // Token management routes
    Route::prefix('tokens')->group(function () {
        Route::get('/', [TokenController::class, 'index']); // Get available tokens for user
        Route::get('/history', [TokenController::class, 'history']); // Get token history (all tokens related to user)
        Route::post('/generate', [TokenController::class, 'generate']); // Super Admin only
        Route::post('/assign', [TokenController::class, 'assign']); // Assign single token
        Route::post('/assign-bulk', [TokenController::class, 'assignBulk']); // Assign multiple tokens by quantity
        Route::post('/distribute', [TokenController::class, 'distribute']); // Super Admin bulk distribution
        Route::get('/statistics', [TokenController::class, 'statistics']);
        Route::get('/assignable-users', [TokenController::class, 'assignableUsers']);
        Route::get('/available-for-customer', [TokenController::class, 'availableForCustomer']); // Get available tokens for customer creation
    });

    // Customer management routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/search', [CustomerController::class, 'search']);
        Route::get('/statistics', [CustomerController::class, 'statistics']);
        Route::get('/overdue', [CustomerController::class, 'overdue']);
        Route::get('/due-soon', [CustomerController::class, 'dueSoon']);
        Route::get('/pending-amount', [CustomerController::class, 'pendingAmount']);
        Route::get('/{id}', [CustomerController::class, 'show']);
        Route::put('/{id}', [CustomerController::class, 'update']);
        Route::delete('/{id}', [CustomerController::class, 'destroy']);
    });

    // Installment management routes
    Route::prefix('installments')->group(function () {
        Route::get('/customers', [InstallmentController::class, 'getAllCustomersWithInstallments']); // Get all customers with installment summary
        Route::get('/customer/{customer}', [InstallmentController::class, 'getCustomerInstallments']); // Get installment history for a customer
        Route::post('/generate/{customer}', [InstallmentController::class, 'generateInstallments']); // Generate installments for a customer
        Route::post('/payment/{installment}', [InstallmentController::class, 'recordPayment']); // Record payment for an installment
        Route::post('/update-overdue', [InstallmentController::class, 'updateOverdueInstallments']); // Update overdue status
    });

    // Location routes for addresses
    Route::prefix('locations')->group(function () {
        Route::get('/divisions', [LocationController::class, 'getDivisions']);
        Route::get('/districts/{division_id}', [LocationController::class, 'getDistricts']);
        Route::get('/upazillas/{district_id}', [LocationController::class, 'getUpazillas']);
    });

    // Dashboard and stats routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
    });

    // Device management routes
    Route::prefix('devices')->group(function () {
        Route::get('/commands', [DeviceController::class, 'availableCommands']); // List all available commands
        Route::get('/{customer}', [DeviceController::class, 'show']); // Get device info
        Route::get('/{customer}/history', [DeviceController::class, 'commandHistory']); // Get command history
        Route::post('/send-message', [DeviceController::class, 'sendMessage']); // Send message only (no command)
        Route::post('/command/{command}', [DeviceController::class, 'sendCommand']); // Send device command
        Route::post('/command-with-message/{command}', [DeviceController::class, 'sendCommandWithMessage']); // Send command + display message
    });

    // Preset message management routes
    Route::prefix('preset-messages')->group(function () {
        Route::get('/', [PresetMessageController::class, 'index']); // Get all preset messages for authenticated user
        Route::get('/available-commands', [PresetMessageController::class, 'availableCommands']); // Get list of commands that can have presets
        Route::post('/', [PresetMessageController::class, 'store']); // Create or update preset message
        Route::get('/{presetMessage}', [PresetMessageController::class, 'show']); // Get specific preset message
        Route::put('/{presetMessage}', [PresetMessageController::class, 'update']); // Update preset message
        Route::delete('/{presetMessage}', [PresetMessageController::class, 'destroy']); // Delete preset message
        Route::post('/{presetMessage}/toggle', [PresetMessageController::class, 'toggle']); // Toggle active status
    });

    // Report management routes
    Route::prefix('reports')->group(function () {
        Route::post('/generate', [ReportController::class, 'generate']); // Generate report (JSON or PDF)
        Route::get('/dealers', [ReportController::class, 'getDealers']); // Get dealers list for super admin
        Route::get('/sub-dealers', [ReportController::class, 'getSubDealers']); // Get sub-dealers based on dealer selection
    });
});

// Test API endpoint
Route::get('/test', function () {
    return response()->json([
        'message' => 'EMI Manager API is working!',
        'laravel_version' => app()->version(),
        'timestamp' => now(),
    ]);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'service' => 'emi-manager']);
});

// Debug test endpoints (only in development)
if (app()->environment(['local', 'development'])) {
    Route::prefix('debug')->group(function () {
        Route::get('/users', function () {
            ray('Fetching users for debugging');

            $users = User::with('roles')->get();

            return response()->json([
                'users' => $users,
                'count' => $users->count(),
                'debug' => 'Check Telescope and Debugbar for details',
            ]);
        });

        Route::get('/roles', function () {
            return response()->json([
                'roles' => \Spatie\Permission\Models\Role::with('permissions')->get(),
                'permissions' => \Spatie\Permission\Models\Permission::all(),
            ]);
        });
    });
}
