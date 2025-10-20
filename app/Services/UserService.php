<?php

namespace App\Services;

use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Address;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleHierarchyService $roleHierarchyService
    ) {}

    public function getAllUsers(User $currentUser, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getUsersByHierarchy($currentUser, $perPage);
    }

    public function searchUsers(array $filters, User $currentUser, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->searchUsersWithFilters($filters, $currentUser, $perPage);
    }

    public function getUsersByRole(string $role, User $currentUser): \Illuminate\Database\Eloquent\Collection
    {
        // Validate that the current user can access users with this role
        if (! $this->roleHierarchyService->canAssignRole($currentUser, $role)) {
            return collect(); // Return empty collection if not authorized
        }

        $query = User::role($role)->select(['id', 'name', 'email', 'phone']);

        // Filter based on current user's role and hierarchy
        $currentUserRole = $currentUser->getRoleNames()->first();

        if ($currentUserRole === 'dealer' && $role === 'sub_dealer') {
            // Dealer should see only their own created sub dealers
            $query->where('parent_id', $currentUser->id);
        } elseif ($currentUserRole === 'sub_dealer' && $role === 'salesman') {
            // Sub Dealer should see only their own created salesmen
            $query->where('parent_id', $currentUser->id);
        }
        // Super Admin can see all users of the requested role (no additional filtering)

        return $query->get();
    }

    public function getUserDetails(int $id, User $currentUser): ?User
    {
        $user = $this->userRepository->findUserWithDetails($id);

        if (! $user || ! $this->userRepository->canUserAccessUser($currentUser, $user)) {
            return null;
        }

        return $user;
    }

    public function createUser(CreateUserRequest $request, User $currentUser): User
    {
        DB::beginTransaction();

        try {
            // Additional role hierarchy validation
            $requestedRole = $request->input('role');
            if ($requestedRole && ! $this->roleHierarchyService->canAssignRole($currentUser, $requestedRole)) {
                throw ValidationException::withMessages([
                    'role' => ['You do not have permission to assign this role.'],
                ]);
            }

            // Create addresses first
            $presentAddress = Address::create($request->getPresentAddressData());
            $permanentAddress = Address::create($request->getPermanentAddressData());

            // Get user data and add address IDs
            $userData = $request->getCreateData();
            $plainPassword = $userData['password'];
            $userData['password'] = Hash::make($userData['password']);
            $userData['plain_password'] = $plainPassword; // Store plain password for admin viewing
            $userData['present_address_id'] = $presentAddress->id;
            $userData['permanent_address_id'] = $permanentAddress->id;

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $fileName = 'user_'.time().'_'.uniqid().'.'.$photo->getClientOriginalExtension();
                $path = $photo->storeAs('photos/users', $fileName, 'public');
                $userData['photo'] = $path;
            }

            $user = $this->userRepository->createUser($userData, $currentUser->id);

            if ($roleId = $request->getRoleId()) {
                $user->assignRole($roleId);
            }

            DB::commit();

            return $this->userRepository->findUserWithDetails($user->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateUser(UpdateUserRequest $request, int $id, User $currentUser): ?User
    {
        $user = $this->userRepository->findUserWithDetails($id);

        if (! $user || ! $this->userRepository->canUserAccessUser($currentUser, $user)) {
            return null;
        }

        DB::beginTransaction();

        try {
            // Additional role hierarchy validation
            $requestedRole = $request->input('role');
            if ($requestedRole && ! $this->roleHierarchyService->canAssignRole($currentUser, $requestedRole)) {
                throw ValidationException::withMessages([
                    'role' => ['You do not have permission to assign this role.'],
                ]);
            }

            $updateData = $request->getUpdateData();

            if (isset($updateData['password'])) {
                $plainPassword = $updateData['password'];
                $updateData['password'] = Hash::make($updateData['password']);
                $updateData['plain_password'] = $plainPassword; // Store plain password for admin viewing
                $updateData['can_change_password'] = true;
            }

            $this->userRepository->updateUser($user, $updateData);

            // Update addresses if provided
            if ($presentAddressData = $request->getPresentAddressData()) {
                if ($user->present_address_id && $user->presentAddress) {
                    $user->presentAddress->update($presentAddressData);
                } else {
                    $presentAddress = Address::create($presentAddressData);
                    $user->update(['present_address_id' => $presentAddress->id]);
                }
            }

            if ($permanentAddressData = $request->getPermanentAddressData()) {
                if ($user->permanent_address_id && $user->permanentAddress) {
                    $user->permanentAddress->update($permanentAddressData);
                } else {
                    $permanentAddress = Address::create($permanentAddressData);
                    $user->update(['permanent_address_id' => $permanentAddress->id]);
                }
            }

            if ($roleId = $request->getRoleId()) {
                $user->syncRoles([$roleId]);
            }

            DB::commit();

            return $this->userRepository->findUserWithDetails($user->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resetPassword(ResetPasswordRequest $request, int $id, User $currentUser): bool
    {
        $user = $this->userRepository->findUserWithDetails($id);

        if (! $user || ! $this->userRepository->canUserAccessUser($currentUser, $user)) {
            return false;
        }

        $plainPassword = $request->getPassword();
        $hashedPassword = Hash::make($plainPassword);
        $canChangePassword = $request->getCanChangePassword();

        // Update both hashed and plain password
        $user->update([
            'password' => $hashedPassword,
            'plain_password' => $plainPassword,
            'can_change_password' => $canChangePassword,
        ]);

        return true;
    }

    public function canUserAccess(User $currentUser, User $targetUser): bool
    {
        return $this->userRepository->canUserAccessUser($currentUser, $targetUser);
    }
}
