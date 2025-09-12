<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string', // Can be email or phone
            'password' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email or phone number is required.',
            'password.required' => 'Password is required.',
        ];
    }

    /**
     * Determine if login is email or phone.
     */
    public function getLoginField(): string
    {
        return filter_var($this->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    }

    /**
     * Get the login value.
     */
    public function getLoginValue(): string
    {
        return $this->input('login');
    }

    /**
     * Get the password value.
     */
    public function getPassword(): string
    {
        return $this->input('password');
    }
}
