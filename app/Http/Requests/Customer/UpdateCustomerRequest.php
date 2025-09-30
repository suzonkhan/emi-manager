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
        $customerId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'mobile' => [
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
            'product_type' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'product_model' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'product_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:1000',
            ],
            'emi_per_month' => [
                'sometimes',
                'required',
                'numeric',
                'min:100',
            ],
            'emi_duration_months' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'imei_1' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'imei_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'completed', 'defaulted', 'cancelled']),
            ],
            'documents' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            // Present Address fields
            'present_address' => [
                'sometimes',
                'array',
            ],
            'present_address.street_address' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'present_address.landmark' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'present_address.postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],
            'present_address.division_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:divisions,id',
            ],
            'present_address.district_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:districts,id',
            ],
            'present_address.upazilla_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:upazillas,id',
            ],
            // Permanent Address fields
            'permanent_address' => [
                'sometimes',
                'array',
            ],
            'permanent_address.street_address' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'permanent_address.landmark' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'permanent_address.postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],
            'permanent_address.division_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:divisions,id',
            ],
            'permanent_address.district_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:districts,id',
            ],
            'permanent_address.upazilla_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:upazillas,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.min' => 'Customer name must be at least 2 characters.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Please enter a valid mobile number.',
            'mobile.unique' => 'This mobile number is already registered.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'product_type.required' => 'Product type is required.',
            'status.in' => 'Status must be one of: active, completed, defaulted, cancelled.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
            'documents.*.max' => 'Document size cannot exceed 5MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'mobile' => 'mobile number',
            'product_type' => 'product type',
            'product_model' => 'product model',
            'emi_per_month' => 'EMI per month',
            'emi_duration_months' => 'EMI duration',
        ];
    }
}