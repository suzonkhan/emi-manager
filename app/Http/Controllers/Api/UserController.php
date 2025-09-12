<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Address;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Get users based on hierarchy
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Users can only see their subordinates and themselves
        $query = User::where('parent_id', $user->id)
            ->orWhere('id', $user->id)
            ->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla']);

        // If super admin, can see all users
        if ($user->hasRole('super_admin')) {
            $query = User::with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla']);
        }

        $users = $query->paginate(15);

        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            
            // Address fields
            'present_address.street_address' => 'required|string',
            'present_address.landmark' => 'nullable|string',
            'present_address.postal_code' => 'nullable|string|max:10',
            'present_address.division_id' => 'required|exists:divisions,id',
            'present_address.district_id' => 'required|exists:districts,id',
            'present_address.upazilla_id' => 'required|exists:upazillas,id',
            
            'permanent_address.street_address' => 'required|string',
            'permanent_address.landmark' => 'nullable|string',
            'permanent_address.postal_code' => 'nullable|string|max:10',
            'permanent_address.division_id' => 'required|exists:divisions,id',
            'permanent_address.district_id' => 'required|exists:districts,id',
            'permanent_address.upazilla_id' => 'required|exists:upazillas,id',
            
            'bkash_merchant_number' => 'nullable|string',
            'nagad_merchant_number' => 'nullable|string',
        ]);

        // Check if user can create this role
        if (!$user->canCreateUser($request->role)) {
            return response()->json([
                'message' => 'You do not have permission to create a user with this role.'
            ], 403);
        }

        // Create addresses
        $presentAddress = Address::create($request->present_address);
        $permanentAddress = Address::create($request->permanent_address);

        // Create user
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'parent_id' => $user->id,
            'present_address_id' => $presentAddress->id,
            'permanent_address_id' => $permanentAddress->id,
            'bkash_merchant_number' => $request->bkash_merchant_number,
            'nagad_merchant_number' => $request->nagad_merchant_number,
            'can_change_password' => false, // Only super admin can change passwords initially
            'is_active' => true,
        ]);

        // Assign role
        $newUser->assignRole($request->role);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $newUser->id,
                'unique_id' => $newUser->unique_id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'phone' => $newUser->phone,
                'role' => $newUser->getRoleNames()->first(),
            ]
        ], 201);
    }

    /**
     * Get specific user details
     */
    public function show(Request $request, $id): JsonResponse
    {
        $currentUser = $request->user();
        
        $user = User::with([
            'roles', 
            'presentAddress.division', 
            'presentAddress.district', 
            'presentAddress.upazilla',
            'permanentAddress.division', 
            'permanentAddress.district', 
            'permanentAddress.upazilla',
            'parent',
            'children'
        ])->findOrFail($id);

        // Check permission - can view self, subordinates, or if super admin
        if (!$currentUser->hasRole('super_admin') && 
            $user->parent_id !== $currentUser->id && 
            $user->id !== $currentUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'unique_id' => $user->unique_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleNames()->first(),
                'parent' => $user->parent ? [
                    'id' => $user->parent->id,
                    'unique_id' => $user->parent->unique_id,
                    'name' => $user->parent->name,
                    'role' => $user->parent->getRoleNames()->first(),
                ] : null,
                'children_count' => $user->children->count(),
                'present_address' => $user->presentAddress,
                'permanent_address' => $user->permanentAddress,
                'bkash_merchant_number' => $user->bkash_merchant_number,
                'nagad_merchant_number' => $user->nagad_merchant_number,
                'can_change_password' => $user->canChangeOwnPassword(),
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'hierarchy_level' => $user->getHierarchyLevel(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update user (limited fields)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $currentUser = $request->user();
        $user = User::findOrFail($id);

        // Check permission
        if (!$currentUser->hasRole('super_admin') && 
            $user->parent_id !== $currentUser->id && 
            $user->id !== $currentUser->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $id,
            'bkash_merchant_number' => 'sometimes|nullable|string',
            'nagad_merchant_number' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = $request->only([
            'name', 'email', 'phone', 'bkash_merchant_number', 
            'nagad_merchant_number'
        ]);

        // Only super admin can change is_active status
        if ($request->has('is_active') && $currentUser->hasRole('super_admin')) {
            $updateData['is_active'] = $request->is_active;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'unique_id' => $user->unique_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * Reset user password (Super admin only)
     */
    public function resetPassword(Request $request, $id): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Only super admin can reset passwords'], 403);
        }

        $request->validate([
            'new_password' => 'required|string|min:8',
            'can_change_password' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($id);
        
        $user->update([
            'password' => Hash::make($request->new_password),
            'can_change_password' => $request->get('can_change_password', false),
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Get available roles for creation
     */
    public function getAvailableRoles(Request $request): JsonResponse
    {
        $user = $request->user();
        $userRole = $user->getRoleNames()->first();
        
        $hierarchy = [
            'super_admin' => ['dealer', 'sub_dealer', 'salesman', 'customer'],
            'dealer' => ['sub_dealer', 'salesman', 'customer'],
            'sub_dealer' => ['salesman', 'customer'],
            'salesman' => ['customer'],
            'customer' => [],
        ];

        $availableRoles = $hierarchy[$userRole] ?? [];
        
        return response()->json([
            'available_roles' => $availableRoles
        ]);
    }
}
