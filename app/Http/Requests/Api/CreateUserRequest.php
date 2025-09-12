<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'present_address.street_address' => 'required|string',
            'present_address.landmark' => 'nullable|string',
            'present_address.postal_code' => 'nullable|string|max:10',
            'present_address.division_id' => 'required|exists:divisions,id',
            'present_address.district_id' => 'required|exists:districts,id',
            'present_address.upazilla_id' => 'required|exists:upazillas,id',
            'permanent_address.street_address' => 'required|string',
            'permanent_address.landmark' => 'nullable|string',
            'permanent_address.postal_code' => 'nullable|string|max:10',
            'permanent_address.division_id' => 'required|exists:divisions,id',
            'permanent_address.district_id' => 'required|exists:districts,id',
            'permanent_address.upazilla_id' => 'required|exists:upazillas,id',
            'bkash_merchant_number' => 'nullable|string',
            'nagad_merchant_number' => 'nullable|string',
        ];
    }

    public function getUserData(): array
    {
        return $this->only(['name', 'email', 'phone', 'password', 'role', 'bkash_merchant_number', 'nagad_merchant_number']);
    }

    public function getPresentAddressData(): array
    {
        return $this->input('present_address');
    }

    public function getPermanentAddressData(): array
    {
        return $this->input('permanent_address');
    }
}
