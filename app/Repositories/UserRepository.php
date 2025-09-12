<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private User $user) {}

    /**
     * Find user by email or phone.
     */
    public function findByEmailOrPhone(string $emailOrPhone): ?User
    {
        $field = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return $this->user->where($field, $emailOrPhone)->first();
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    /**
     * Find user by phone.
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->user->where('phone', $phone)->first();
    }

    /**
     * Update user's last login timestamp.
     */
    public function updateLastLogin(User $user): bool
    {
        $user->updateLastLogin();

        return true;
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $hashedPassword): bool
    {
        return $user->update(['password' => $hashedPassword]);
    }

    /**
     * Load user with relationships for profile.
     */
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
}
