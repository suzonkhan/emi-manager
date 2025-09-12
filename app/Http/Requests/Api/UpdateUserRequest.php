<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,'.$userId,
            'phone' => 'sometimes|string|max:20|unique:users,phone,'.$userId,
            'bkash_merchant_number' => 'sometimes|nullable|string',
            'nagad_merchant_number' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function getUpdateData(): array
    {
        return $this->only(['name', 'email', 'phone', 'bkash_merchant_number', 'nagad_merchant_number', 'is_active']);
    }
}
