<?php

namespace App\Http\Requests\User;

use App\Rules\AssignableRole;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

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
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'password' => 'required|string|min:8',
            'role' => [
                'required',
                'string',
                'exists:roles,name',
                new AssignableRole($this->user()),
            ],
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

    public function getCreateData(): array
    {
        $data = $this->only(['name', 'email', 'phone', 'password', 'bkash_merchant_number', 'nagad_merchant_number']);

        // Add address IDs
        $data['present_address_id'] = null; // Will be set after address creation
        $data['permanent_address_id'] = null; // Will be set after address creation

        return $data;
    }

    public function getPresentAddressData(): array
    {
        return $this->input('present_address');
    }

    public function getPermanentAddressData(): array
    {
        return $this->input('permanent_address');
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
