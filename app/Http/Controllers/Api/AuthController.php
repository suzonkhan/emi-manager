<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserProfileResource;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $authData = $this->authService->login($request);

            return $this->successResponse(
                'Login successful',
                [
                    'user' => new UserResource($authData['user']),
                    'token' => $authData['token'],
                    'token_type' => $authData['token_type'],
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }
    }

    /**
     * Logout user (revoke all tokens)
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse('Logged out successfully');
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->authService->getUserProfile($request->user());

        return $this->successResponse(
            'Profile retrieved successfully',
            ['user' => new UserProfileResource($user)]
        );
    }

    /**
     * Change password (only if allowed)
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword($request->user(), $request);

            return $this->successResponse('Password changed successfully');
        } catch (ValidationException $e) {
            if (isset($e->errors()['permission'])) {
                return $this->forbiddenResponse($e->errors()['permission'][0]);
            }

            return $this->validationErrorResponse($e);
        }
    }
}
