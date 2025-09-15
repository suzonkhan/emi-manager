<?php

namespace App\Services;

use App\Models\User;

class RoleHierarchyService
{
    /**
     * Define the role hierarchy where higher roles can create/manage lower roles
     */
    private array $hierarchy = [
        'super_admin' => ['dealer', 'sub_dealer', 'salesman'],
        'dealer' => ['sub_dealer', 'salesman'],
        'sub_dealer' => ['salesman'],
        'salesman' => [],
    ];

    /**
     * Get roles that a user can assign/create
     */
    public function getAssignableRoles(User $user): array
    {
        $userRole = $user->getRoleNames()->first();

        return $this->hierarchy[$userRole] ?? [];
    }

    /**
     * Check if a user can assign a specific role
     */
    public function canAssignRole(User $user, string $targetRole): bool
    {
        $assignableRoles = $this->getAssignableRoles($user);

        return in_array($targetRole, $assignableRoles);
    }

    /**
     * Check if a user can manage (create/update) another user with a specific role
     */
    public function canManageUserWithRole(User $currentUser, string $targetUserRole): bool
    {
        return $this->canAssignRole($currentUser, $targetUserRole);
    }

    /**
     * Get all roles in the hierarchy
     */
    public function getAllRoles(): array
    {
        return array_keys($this->hierarchy);
    }

    /**
     * Get role hierarchy structure
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }

    /**
     * Check if a role exists in the hierarchy
     */
    public function isValidRole(string $role): bool
    {
        return array_key_exists($role, $this->hierarchy);
    }

    /**
     * Get the role level (lower number = higher authority)
     */
    public function getRoleLevel(string $role): int
    {
        $levels = [
            'super_admin' => 1,
            'dealer' => 2,
            'sub_dealer' => 3,
            'salesman' => 4,
        ];

        return $levels[$role] ?? 999;
    }

    /**
     * Check if role A is higher than role B
     */
    public function isHigherRole(string $roleA, string $roleB): bool
    {
        return $this->getRoleLevel($roleA) < $this->getRoleLevel($roleB);
    }

    /**
     * Check if role A is same level as role B
     */
    public function isSameRole(string $roleA, string $roleB): bool
    {
        return $this->getRoleLevel($roleA) === $this->getRoleLevel($roleB);
    }
}
