<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceRegisterRequest extends FormRequest
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
        return [
            'serial_number' => ['required', 'string', 'max:255'],
            'imei1' => ['required', 'string', 'max:255'],
            'fcm_token' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'serial_number.required' => 'The device serial number is required',
            'imei1.required' => 'The IMEI number is required',
            'fcm_token.required' => 'The FCM token is required',
        ];
    }
}
