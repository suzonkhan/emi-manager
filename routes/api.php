<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazilla;
use App\Models\User;
use Illuminate\Http\Request;
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
    });

    // Location routes for addresses
    Route::prefix('locations')->group(function () {
        Route::get('/divisions', function () {
            return response()->json([
                'divisions' => Division::active()->get(),
            ]);
        });

        Route::get('/districts/{division_id}', function ($divisionId) {
            return response()->json([
                'districts' => District::where('division_id', $divisionId)->active()->get(),
            ]);
        });

        Route::get('/upazillas/{district_id}', function ($districtId) {
            return response()->json([
                'upazillas' => Upazilla::where('district_id', $districtId)->active()->get(),
            ]);
        });
    });

    // Dashboard and stats routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', function (Request $request) {
            $user = $request->user();

            $stats = [
                'total_subordinates'  => $user->children()->count(),
                'active_subordinates' => $user->children()->where('is_active', true)->count(),
                'my_role'             => $user->getRoleNames()->first(),
                'hierarchy_level'     => $user->getHierarchyLevel(),
            ];

            if ($user->hasRole('super_admin')) {
                $stats['total_users']        = User::count();
                $stats['active_users']       = User::where('is_active', true)->count();
                $stats['roles_distribution'] = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->groupBy('roles.name')
                    ->selectRaw('roles.name as role, count(*) as count')
                    ->get();
            }

            return response()->json(['stats' => $stats]);
        });
    });
});

// Test API endpoint
Route::get('/test', function () {
    return response()->json([
        'message'         => 'EMI Manager API is working!',
        'laravel_version' => app()->version(),
        'timestamp'       => now(),
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
                'roles'       => \Spatie\Permission\Models\Role::with('permissions')->get(),
                'permissions' => \Spatie\Permission\Models\Permission::all(),
            ]);
        });
    });
}
