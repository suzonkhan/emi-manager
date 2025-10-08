<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['super_admin', 'dealer', 'sub_dealer', 'salesman']);
    }

    public function rules(): array
    {
        return [
            'nid_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('customers'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'mobile' => [
                'required',
                'string',
                'regex:/^[0-9+\-\s()]{10,15}$/',
                Rule::unique('customers'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers'),
            ],
            'product_type' => [
                'required',
                'string',
                'max:255',
            ],
            'product_model' => [
                'nullable',
                'string',
                'max:255',
            ],
            'product_price' => [
                'required',
                'numeric',
                'min:1000',
                'max:10000000',
            ],
            'down_payment' => [
                'required',
                'numeric',
                'min:0',
                'max:10000000',
            ],
            'emi_duration_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'imei_1' => [
                'nullable',
                'string',
                'max:255',
            ],
            'imei_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers'),
            ],
            'documents' => [
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
                'required',
                'array',
            ],
            'present_address.street_address' => [
                'required',
                'string',
                'max:255',
            ],
            'present_address.landmark' => [
                'nullable',
                'string',
                'max:255',
            ],
            'present_address.postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],
            'present_address.division_id' => [
                'required',
                'integer',
                'exists:divisions,id',
            ],
            'present_address.district_id' => [
                'required',
                'integer',
                'exists:districts,id',
            ],
            'present_address.upazilla_id' => [
                'required',
                'integer',
                'exists:upazillas,id',
            ],
            // Permanent Address fields
            'permanent_address' => [
                'required',
                'array',
            ],
            'permanent_address.street_address' => [
                'required',
                'string',
                'max:255',
            ],
            'permanent_address.landmark' => [
                'nullable',
                'string',
                'max:255',
            ],
            'permanent_address.postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],
            'permanent_address.division_id' => [
                'required',
                'integer',
                'exists:divisions,id',
            ],
            'permanent_address.district_id' => [
                'required',
                'integer',
                'exists:districts,id',
            ],
            'permanent_address.upazilla_id' => [
                'required',
                'integer',
                'exists:upazillas,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nid_no.required' => 'NID number is required.',
            'nid_no.unique' => 'This NID number is already registered.',
            'name.required' => 'Customer name is required.',
            'name.min' => 'Customer name must be at least 2 characters.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Please enter a valid mobile number.',
            'mobile.unique' => 'This mobile number is already registered.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'product_type.required' => 'Product type is required.',
            'product_price.required' => 'Product price is required.',
            'product_price.min' => 'Product price must be at least ৳1,000.',
            'product_price.max' => 'Product price cannot exceed ৳1,00,00,000.',
            'emi_per_month.required' => 'EMI per month is required.',
            'emi_duration_months.required' => 'EMI duration in months is required.',
            'emi_duration_months.max' => 'EMI duration cannot exceed 120 months.',
            'down_payment.required' => 'Down payment is required.',
            'down_payment.min' => 'Down payment must be at least ৳0.',
            'down_payment.max' => 'Down payment cannot exceed ৳1,00,00,000.',
            'serial_number.required' => 'Serial number is required.',
            'serial_number.unique' => 'This serial number is already registered.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
            'documents.*.max' => 'Document size cannot exceed 5MB.',
            'present_address.street_address.required' => 'Present address street is required.',
            'present_address.division_id.required' => 'Present address division is required.',
            'present_address.district_id.required' => 'Present address district is required.',
            'present_address.upazilla_id.required' => 'Present address upazilla is required.',
            'permanent_address.street_address.required' => 'Permanent address street is required.',
            'permanent_address.division_id.required' => 'Permanent address division is required.',
            'permanent_address.district_id.required' => 'Permanent address district is required.',
            'permanent_address.upazilla_id.required' => 'Permanent address upazilla is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nid_no' => 'NID number',
            'mobile' => 'mobile number',
            'product_type' => 'product type',
            'product_model' => 'product model',
            'product_price' => 'product price',
            'down_payment' => 'down payment',
            'emi_per_month' => 'EMI per month',
            'emi_duration_months' => 'EMI duration',
            'serial_number' => 'serial number',
        ];
    }
}
