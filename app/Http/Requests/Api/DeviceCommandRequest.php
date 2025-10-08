<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceCommandRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $command = $this->route('command');

        $baseRules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ];

        // Add command-specific validation rules
        return match ($command) {
            'reset-password' => array_merge($baseRules, [
                'password' => ['required', 'string', 'min:4', 'max:50'],
            ]),
            'hide-app', 'unhide-app', 'remove-app' => array_merge($baseRules, [
                'package_name' => ['nullable', 'string', 'max:255'],
            ]),
            'show-message' => array_merge($baseRules, [
                'title' => ['nullable', 'string', 'max:255'],
                'message' => ['required', 'string', 'max:1000'],
            ]),
            'reminder-screen' => array_merge($baseRules, [
                'message' => ['required', 'string', 'max:1000'],
            ]),
            'reminder-audio' => array_merge($baseRules, [
                'audio_url' => ['nullable', 'url', 'max:500'],
            ]),
            'set-wallpaper' => array_merge($baseRules, [
                'image_url' => ['required', 'url', 'max:500'],
            ]),
            default => $baseRules,
        };
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer ID is required',
            'customer_id.exists' => 'Customer not found',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 4 characters',
            'message.required' => 'Message is required',
            'image_url.required' => 'Image URL is required',
            'image_url.url' => 'Image URL must be a valid URL',
        ];
    }
}
