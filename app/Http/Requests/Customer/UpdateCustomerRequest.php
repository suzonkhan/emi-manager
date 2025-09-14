<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['super_admin', 'dealer', 'sub_dealer', 'salesman']);
    }

    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[0-9+\-\s()]{10,15}$/',
                Rule::unique('customers')->ignore($customerId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customerId),
            ],
            'product_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive', 'completed', 'defaulted']),
            ],
            'document' => [
                'sometimes',
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            // Address fields
            'address_line_1' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'state' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
            ],
            'country' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.min' => 'Customer name must be at least 2 characters.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.unique' => 'This phone number is already registered.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'product_name.required' => 'Product name is required.',
            'status.in' => 'Status must be one of: active, inactive, completed, defaulted.',
            'document.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
            'document.max' => 'Document size cannot exceed 5MB.',
            'address_line_1.required' => 'Address line 1 is required.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'postal_code.required' => 'Postal code is required.',
            'country.required' => 'Country is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
        ];
    }
}