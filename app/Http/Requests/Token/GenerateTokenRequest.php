<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $userRole = $user ? $user->getRoleNames()->first() : null;

        return $user && $userRole === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
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
            'quantity.required' => 'The number of tokens to generate is required.',
            'quantity.integer' => 'The quantity must be a valid number.',
            'quantity.min' => 'You must generate at least 1 token.',
            'quantity.max' => 'You cannot generate more than 1000 tokens at once.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => 'number of tokens',
        ];
    }
}
