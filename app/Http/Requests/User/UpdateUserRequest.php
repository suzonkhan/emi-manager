<?php

namespace App\Http\Requests\User;

use App\Rules\AssignableRole;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,'.$userId,
            'phone' => 'sometimes|string|max:20|unique:users,phone,'.$userId,
            'password' => 'sometimes|string|min:8',
            'role' => [
                'sometimes',
                'string',
                'exists:roles,name',
                new AssignableRole($this->user()),
            ],
            'bkash_merchant_number' => 'sometimes|nullable|string',
            'nagad_merchant_number' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            // Address validation
            'present_address.street_address' => 'sometimes|nullable|string|max:255',
            'present_address.landmark' => 'sometimes|nullable|string|max:255',
            'present_address.postal_code' => 'sometimes|nullable|string|max:10',
            'present_address.division_id' => 'sometimes|nullable|integer|exists:divisions,id',
            'present_address.district_id' => 'sometimes|nullable|integer|exists:districts,id',
            'present_address.upazilla_id' => 'sometimes|nullable|integer|exists:upazillas,id',
            'permanent_address.street_address' => 'sometimes|nullable|string|max:255',
            'permanent_address.landmark' => 'sometimes|nullable|string|max:255',
            'permanent_address.postal_code' => 'sometimes|nullable|string|max:10',
            'permanent_address.division_id' => 'sometimes|nullable|integer|exists:divisions,id',
            'permanent_address.district_id' => 'sometimes|nullable|integer|exists:districts,id',
            'permanent_address.upazilla_id' => 'sometimes|nullable|integer|exists:upazillas,id',
        ];
    }

    public function getUpdateData(): array
    {
        return $this->only([
            'name',
            'email',
            'phone',
            'password',
            'bkash_merchant_number',
            'nagad_merchant_number',
            'is_active'
        ]);
    }

    public function getPresentAddressData(): ?array
    {
        $presentAddress = $this->input('present_address');
        if (!$presentAddress) {
            return null;
        }

        return [
            'street_address' => $presentAddress['street_address'] ?? null,
            'landmark' => $presentAddress['landmark'] ?? null,
            'postal_code' => $presentAddress['postal_code'] ?? null,
            'division_id' => $presentAddress['division_id'] ?? null,
            'district_id' => $presentAddress['district_id'] ?? null,
            'upazilla_id' => $presentAddress['upazilla_id'] ?? null,
        ];
    }

    public function getPermanentAddressData(): ?array
    {
        $permanentAddress = $this->input('permanent_address');
        if (!$permanentAddress) {
            return null;
        }

        return [
            'street_address' => $permanentAddress['street_address'] ?? null,
            'landmark' => $permanentAddress['landmark'] ?? null,
            'postal_code' => $permanentAddress['postal_code'] ?? null,
            'division_id' => $permanentAddress['division_id'] ?? null,
            'district_id' => $permanentAddress['district_id'] ?? null,
            'upazilla_id' => $permanentAddress['upazilla_id'] ?? null,
        ];
    }

    public function getRoleId(): ?int
    {
        $roleName = $this->input('role');
        if (! $roleName) {
            return null;
        }

        $role = Role::where('name', $roleName)->first();

        return $role?->id;
    }
}
