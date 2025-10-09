<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findByEmailOrPhone(string $emailOrPhone): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function updateLastLogin(User $user): bool;

    public function updatePassword(User $user, string $hashedPassword): bool;

    public function loadUserWithProfile(User $user): User;

    public function getUsersByHierarchy(User $currentUser, int $perPage = 15): LengthAwarePaginator;

    public function searchUsersWithFilters(array $filters, User $currentUser, int $perPage = 15): LengthAwarePaginator;

    public function findUserWithDetails(int $id): ?User;

    public function createUser(array $userData, int $parentId): User;

    public function updateUser(User $user, array $data): bool;

    public function resetUserPassword(User $user, string $hashedPassword, bool $canChangePassword): bool;

    public function canUserAccessUser(User $currentUser, User $targetUser): bool;
}
