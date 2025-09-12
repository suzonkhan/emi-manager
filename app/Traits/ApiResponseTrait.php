<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     */
    protected function successResponse(string $message = 'Success', mixed $data = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof JsonResource) {
                $response['data'] = $data->resolve();
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message = 'Error', mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return $this->errorResponse(
            'Validation failed',
            $exception->errors(),
            422
        );
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }
}
