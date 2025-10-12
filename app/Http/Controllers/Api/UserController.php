<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserListResource;
use App\Services\RoleHierarchyService;
use App\Services\UserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private UserService $userService,
        private RoleHierarchyService $roleHierarchyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);

            // Build filters array
            $filters = [
                'unique_id' => $request->input('unique_id'),
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'role' => $request->input('role'),
                'division_id' => $request->input('division_id'),
                'district_id' => $request->input('district_id'),
                'upazilla_id' => $request->input('upazilla_id'),
                'status' => $request->input('status'),
            ];

            // Remove null values
            $filters = array_filter($filters, fn ($value) => $value !== null);

            $users = $this->userService->searchUsers($filters, $request->user(), $perPage);

            return $this->success([
                'users' => UserListResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
                'filters_applied' => $filters,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request, $request->user());

            return $this->success([
                'user' => new UserDetailResource($user),
                'message' => 'User created successfully',
            ], 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create user: '.$e->getMessage(), null, 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserDetails($id, $request->user());

        if (! $user) {
            return $this->forbidden('You do not have permission to view this user');
        }

        return $this->success([
            'user' => new UserDetailResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($request, $id, $request->user());

            if (! $user) {
                return $this->forbidden('You do not have permission to update this user');
            }

            return $this->success([
                'user' => new UserDetailResource($user),
                'message' => 'User updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to update user: '.$e->getMessage(), null, 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return $this->forbidden('Only super admin can reset passwords');
        }

        $success = $this->userService->resetPassword($request, $id, $request->user());

        if (! $success) {
            return $this->forbidden('You do not have permission to reset this user\'s password');
        }

        return $this->success(['message' => 'Password reset successfully']);
    }

    public function getAvailableRoles(Request $request): JsonResponse
    {
        $availableRoles = $this->roleHierarchyService->getAssignableRoles($request->user());

        return $this->success(['available_roles' => $availableRoles]);
    }

    public function getPassword(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            return $this->forbidden('Only super admin can view user passwords');
        }

        $user = $this->userService->getUserDetails($id, $request->user());

        if (! $user) {
            return $this->forbidden('You do not have permission to view this user');
        }

        $plainPassword = $user->getPlainPasswordForViewer($request->user());

        if (! $plainPassword) {
            return $this->error('Password not available for this user', null, 404);
        }

        return $this->success([
            'password' => $plainPassword,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'message' => 'Password retrieved successfully',
        ]);
    }
}
