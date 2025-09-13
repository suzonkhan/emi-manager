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

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function getAllUsers(User $currentUser, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getUsersByHierarchy($currentUser, $perPage);
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
            // Create addresses first
            $presentAddress = Address::create($request->getPresentAddressData());
            $permanentAddress = Address::create($request->getPermanentAddressData());

            // Get user data and add address IDs
            $userData = $request->getCreateData();
            $userData['password'] = Hash::make($userData['password']);
            $userData['present_address_id'] = $presentAddress->id;
            $userData['permanent_address_id'] = $permanentAddress->id;

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
            $updateData = $request->getUpdateData();

            if (isset($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
                $updateData['can_change_password'] = true;
            }

            $this->userRepository->updateUser($user, $updateData);

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

        $hashedPassword = Hash::make($request->getPassword());
        $canChangePassword = $request->getCanChangePassword();

        return $this->userRepository->resetUserPassword($user, $hashedPassword, $canChangePassword);
    }

    public function canUserAccess(User $currentUser, User $targetUser): bool
    {
        return $this->userRepository->canUserAccessUser($currentUser, $targetUser);
    }
}
