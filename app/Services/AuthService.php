<?php

namespace App\Services;

use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * Authenticate user and generate token.
     */
    public function login(LoginRequest $request): array
    {
        $user = $this->userRepository->findByEmailOrPhone($request->getLoginValue());

        if (! $user || ! Hash::check($request->getPassword(), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account is inactive. Please contact administrator.'],
            ]);
        }

        // Update last login
        $this->userRepository->updateLastLogin($user);

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Get user profile with relationships.
     */
    public function getUserProfile(User $user): User
    {
        return $this->userRepository->loadUserWithProfile($user);
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, ChangePasswordRequest $request): void
    {
        if (! $user->canChangeOwnPassword()) {
            throw ValidationException::withMessages([
                'permission' => ['You do not have permission to change your password. Contact your administrator.'],
            ]);
        }

        if (! Hash::check($request->getCurrentPassword(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $hashedPassword = Hash::make($request->getNewPassword());
        $this->userRepository->updatePassword($user, $hashedPassword);
    }
}
