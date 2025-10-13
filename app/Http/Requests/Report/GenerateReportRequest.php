<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super_admin', 'dealer', 'sub_dealer', 'salesman']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => [
                'required',
                'string',
                Rule::in([
                    'sales',
                    'installments',
                    'collections',
                    'products',
                    'customers',
                    'dealers',
                    'sub_dealers',
                ]),
            ],
            'start_date' => [
                'required',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'dealer_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'sub_dealer_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'format' => [
                'sometimes',
                'string',
                Rule::in(['pdf', 'json']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'report_type.required' => 'Report type is required.',
            'report_type.in' => 'Invalid report type selected.',
            'start_date.required' => 'Start date is required.',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.required' => 'End date is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'dealer_id.exists' => 'Selected dealer does not exist.',
            'sub_dealer_id.exists' => 'Selected sub-dealer does not exist.',
        ];
    }
}
