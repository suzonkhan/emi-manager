<?php

namespace App\Repositories\User;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmailOrPhone(string $emailOrPhone): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function updateLastLogin(User $user): bool;

    public function updatePassword(User $user, string $hashedPassword): bool;

    public function loadUserWithProfile(User $user): User;
}
