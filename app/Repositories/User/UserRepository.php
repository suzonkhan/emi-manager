<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private User $user) {}

    public function findByEmailOrPhone(string $emailOrPhone): ?User
    {
        $field = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return $this->user->where($field, $emailOrPhone)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->user->where('phone', $phone)->first();
    }

    public function updateLastLogin(User $user): bool
    {
        $user->updateLastLogin();

        return true;
    }

    public function updatePassword(User $user, string $hashedPassword): bool
    {
        return $user->update(['password' => $hashedPassword]);
    }

    public function loadUserWithProfile(User $user): User
    {
        return $user->load([
            'parent',
            'presentAddress.division',
            'presentAddress.district',
            'presentAddress.upazilla',
            'permanentAddress.division',
            'permanentAddress.district',
            'permanentAddress.upazilla',
        ]);
    }

    public function getUsersByHierarchy(User $currentUser, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->user->where('parent_id', $currentUser->id)
            ->where('id', '!=', $currentUser->id) // Exclude current user
            ->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla']);

        if ($currentUser->hasRole('super_admin')) {
            $query = $this->user->where('id', '!=', $currentUser->id) // Exclude current user for super admin too
                ->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla']);
        }

        return $query->paginate($perPage);
    }

    public function findUserWithDetails(int $id): ?User
    {
        return $this->user->with([
            'roles',
            'presentAddress.division',
            'presentAddress.district',
            'presentAddress.upazilla',
            'permanentAddress.division',
            'permanentAddress.district',
            'permanentAddress.upazilla',
            'parent',
            'children',
        ])->find($id);
    }

    public function createUser(array $userData, int $parentId): User
    {
        $userData['parent_id'] = $parentId;
        $userData['can_change_password'] = false;
        $userData['is_active'] = true;

        return $this->user->create($userData);
    }

    public function updateUser(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function resetUserPassword(User $user, string $hashedPassword, bool $canChangePassword): bool
    {
        return $user->update([
            'password' => $hashedPassword,
            'can_change_password' => $canChangePassword,
        ]);
    }

    public function canUserAccessUser(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->hasRole('super_admin')) {
            return true;
        }

        return $targetUser->parent_id === $currentUser->id || $targetUser->id === $currentUser->id;
    }
}
