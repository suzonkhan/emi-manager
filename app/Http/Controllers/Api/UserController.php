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
        $users = $this->userService->getAllUsers($request->user(), 15);

        return $this->success([
            'users' => UserListResource::collection($users->items()),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
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
}
