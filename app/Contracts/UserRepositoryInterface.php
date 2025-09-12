<?php

namespace App\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Find user by email or phone.
     */
    public function findByEmailOrPhone(string $emailOrPhone): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by phone.
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Update user's last login timestamp.
     */
    public function updateLastLogin(User $user): bool;

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $hashedPassword): bool;

    /**
     * Load user with relationships for profile.
     */
    public function loadUserWithProfile(User $user): User;
}
