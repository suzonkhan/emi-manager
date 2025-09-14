<?php

namespace App\Http\Requests\Token;

use App\Rules\AssignableRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['super_admin', 'dealer', 'sub_dealer']);
    }

    public function rules(): array
    {
        return [
            'token_code' => [
                'required',
                'string',
                'size:12',
                Rule::exists('tokens', 'code')->where(function ($query) {
                    $query->where('status', 'available');
                }),
            ],
            'assignee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                new AssignableRole(),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token_code.required' => 'Token code is required.',
            'token_code.size' => 'Token code must be exactly 12 characters.',
            'token_code.exists' => 'Invalid token code or token is not available.',
            'assignee_id.required' => 'Please select a user to assign the token to.',
            'assignee_id.exists' => 'Selected user does not exist.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token_code' => 'token code',
            'assignee_id' => 'assignee',
        ];
    }
}