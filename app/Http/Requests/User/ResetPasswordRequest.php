<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_password' => 'required|string|min:8',
            'can_change_password' => 'sometimes|boolean',
        ];
    }

    public function getPassword(): string
    {
        return $this->input('new_password');
    }

    public function getCanChangePassword(): bool
    {
        return $this->input('can_change_password', false);
    }
}
