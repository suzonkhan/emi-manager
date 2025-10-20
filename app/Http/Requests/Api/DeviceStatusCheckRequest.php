<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceStatusCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * This is a public endpoint - no authentication required
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
            'serial_number' => 'required|string|max:255',
            'imei1' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'serial_number.required' => 'Device serial number is required',
            'serial_number.string' => 'Serial number must be a valid string',
            'imei1.required' => 'Device IMEI is required',
            'imei1.string' => 'IMEI must be a valid string',
        ];
    }
}

