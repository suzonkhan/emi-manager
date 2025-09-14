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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'phone' => [
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
            'product_name' => [
                'required',
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
                'lt:product_price',
            ],
            'interest_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:50',
            ],
            'tenure_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'token_code' => [
                'required',
                'string',
                'size:12',
                Rule::exists('tokens', 'code')->where(function ($query) {
                    $query->where('status', 'assigned')
                          ->where('assigned_to', $this->user()->id);
                }),
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            // Address fields
            'address_line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'state' => [
                'required',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'required',
                'string',
                'max:20',
            ],
            'country' => [
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
            'product_price.required' => 'Product price is required.',
            'product_price.min' => 'Product price must be at least ₹1,000.',
            'product_price.max' => 'Product price cannot exceed ₹1,00,00,000.',
            'down_payment.required' => 'Down payment is required.',
            'down_payment.lt' => 'Down payment must be less than product price.',
            'interest_rate.required' => 'Interest rate is required.',
            'interest_rate.max' => 'Interest rate cannot exceed 50%.',
            'tenure_months.required' => 'Tenure in months is required.',
            'tenure_months.max' => 'Tenure cannot exceed 120 months.',
            'token_code.required' => 'Token code is required.',
            'token_code.size' => 'Token code must be exactly 12 characters.',
            'token_code.exists' => 'Invalid token code or token is not assigned to you.',
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
            'product_price' => 'product price',
            'down_payment' => 'down payment',
            'interest_rate' => 'interest rate',
            'tenure_months' => 'tenure',
            'token_code' => 'token code',
            'address_line_1' => 'address line 1',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal code',
        ];
    }
}