<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $authData = $this->authService->login($request);

            return $this->success(
                [
                    'user' => new UserResource($authData['user']),
                    'token' => $authData['token'],
                    'token_type' => $authData['token_type'],
                    'message' => 'Login successful',
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationError($e);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->authService->getUserProfile($request->user());

        return $this->success([
            'user' => new UserProfileResource($user),
            'message' => 'Profile retrieved successfully',
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword($request->user(), $request);

            return $this->success(['message' => 'Password changed successfully']);
        } catch (ValidationException $e) {
            if (isset($e->errors()['permission'])) {
                return $this->forbidden($e->errors()['permission'][0]);
            }

            return $this->validationError($e);
        }
    }

    /**
     * Register or update FCM token for push notifications
     */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $validated['fcm_token'];
        $user->save();

        return $this->success([
            'message' => 'FCM token registered successfully',
        ]);
    }
}
