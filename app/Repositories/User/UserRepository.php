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
        if ($currentUser->hasRole('super_admin')) {
            // Super admin sees all users except themselves
            $query = $this->user->where('id', '!=', $currentUser->id)
                ->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla'])
                ->latest();
        } else {
            // Other users see only their hierarchy (all descendants)
            $hierarchyUserIds = $this->getUserHierarchyIds($currentUser);
            // Remove current user from the list
            $hierarchyUserIds = array_diff($hierarchyUserIds, [$currentUser->id]);

            $query = $this->user->whereIn('id', $hierarchyUserIds)
                ->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla'])
                ->latest();
        }

        return $query->paginate($perPage);
    }

    public function searchUsersWithFilters(array $filters, User $currentUser, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->user->with(['roles', 'presentAddress.division', 'presentAddress.district', 'presentAddress.upazilla']);

        // Apply hierarchy filtering first
        if ($currentUser->hasRole('super_admin')) {
            $query->where('id', '!=', $currentUser->id);
        } else {
            // Get all users in hierarchy (all descendants)
            $hierarchyUserIds = $this->getUserHierarchyIds($currentUser);
            // Remove current user from the list
            $hierarchyUserIds = array_diff($hierarchyUserIds, [$currentUser->id]);

            $query->whereIn('id', $hierarchyUserIds);
        }

        // Apply individual filters
        if (! empty($filters['unique_id'])) {
            $query->where('unique_id', 'like', '%'.$filters['unique_id'].'%');
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['email'])) {
            $query->where('email', 'like', '%'.$filters['email'].'%');
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'like', '%'.$filters['phone'].'%');
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']); // Using Spatie's role method
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Apply location filters through present address relationship
        if (! empty($filters['division_id']) || ! empty($filters['district_id']) || ! empty($filters['upazilla_id'])) {
            $query->whereHas('presentAddress', function ($q) use ($filters) {
                if (! empty($filters['division_id'])) {
                    $q->where('division_id', $filters['division_id']);
                }
                if (! empty($filters['district_id'])) {
                    $q->where('district_id', $filters['district_id']);
                }
                if (! empty($filters['upazilla_id'])) {
                    $q->where('upazilla_id', $filters['upazilla_id']);
                }
            });
        }

        return $query->latest()->paginate($perPage);
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

        // Check if target user is in current user's hierarchy
        $hierarchyUserIds = $this->getUserHierarchyIds($currentUser);

        return in_array($targetUser->id, $hierarchyUserIds);
    }

    /**
     * Get all user IDs in the user's hierarchy (including themselves)
     */
    protected function getUserHierarchyIds(User $user): array
    {
        $userIds = [$user->id]; // Include the user themselves

        // Get direct children
        $children = User::where('parent_id', $user->id)->get();

        foreach ($children as $child) {
            // Recursively get all descendants
            $userIds = array_merge($userIds, $this->getUserHierarchyIds($child));
        }

        return array_unique($userIds);
    }
}
