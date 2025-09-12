<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string', // Can be email or phone
            'password' => 'required|string',
        ]);

        // Determine if login is email or phone
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        // Find user by email or phone
        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account is inactive. Please contact administrator.'],
            ]);
        }

        // Update last login
        $user->updateLastLogin();

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'unique_id' => $user->unique_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'can_change_password' => $user->canChangeOwnPassword(),
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (revoke all tokens)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'unique_id' => $user->unique_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleNames()->first(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'parent' => $user->parent ? [
                    'id' => $user->parent->id,
                    'unique_id' => $user->parent->unique_id,
                    'name' => $user->parent->name,
                    'role' => $user->parent->getRoleNames()->first(),
                ] : null,
                'present_address' => $user->presentAddress?->load(['division', 'district', 'upazilla']),
                'permanent_address' => $user->permanentAddress?->load(['division', 'district', 'upazilla']),
                'bkash_merchant_number' => $user->bkash_merchant_number,
                'nagad_merchant_number' => $user->nagad_merchant_number,
                'can_change_password' => $user->canChangeOwnPassword(),
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'hierarchy_level' => $user->getHierarchyLevel(),
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Change password (only if allowed)
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->canChangeOwnPassword()) {
            return response()->json([
                'message' => 'You do not have permission to change your password. Contact your administrator.'
            ], 403);
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}
