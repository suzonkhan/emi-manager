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
        ];
    }

    public function getUpdateData(): array
    {
        return $this->only(['name', 'email', 'phone', 'password', 'bkash_merchant_number', 'nagad_merchant_number', 'is_active']);
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
