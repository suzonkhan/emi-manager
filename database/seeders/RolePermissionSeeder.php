<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'create_users',
            'view_users',
            'edit_users',
            'delete_users',
            'manage_roles',
            'change_passwords',
            'view_reports',
            'manage_system',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with their hierarchical permissions
        $roles = [
            'super_admin' => [
                'create_users', 'view_users', 'edit_users', 'delete_users',
                'manage_roles', 'change_passwords', 'view_reports', 'manage_system',
            ],
            'dealer' => [
                'create_users', 'view_users', 'edit_users',
                'view_reports',
            ],
            'sub_dealer' => [
                'create_users', 'view_users', 'edit_users',
                'view_reports',
            ],
            'salesman' => [
                'create_users', 'view_users',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions created successfully.');
    }
}
