<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazilla;
use App\Models\Address;

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
                'manage_roles', 'change_passwords', 'view_reports', 'manage_system'
            ],
            'dealer' => [
                'create_users', 'view_users', 'edit_users',
                'view_reports'
            ],
            'sub_dealer' => [
                'create_users', 'view_users', 'edit_users',
                'view_reports'
            ],
            'salesman' => [
                'create_users', 'view_users'
            ],
            'customer' => [
                'view_users'
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // Create sample divisions, districts, upazillas
        $this->createLocationData();

        // Create super admin user
        $this->createSuperAdmin();
    }

    private function createLocationData(): void
    {
        // Create Dhaka Division
        $dhakaDivision = Division::firstOrCreate([
            'name' => 'Dhaka',
            'bn_name' => 'ঢাকা',
            'code' => 'DHAKA',
            'is_active' => true,
        ]);

        // Create Dhaka District
        $dhakaDistrict = District::firstOrCreate([
            'name' => 'Dhaka',
            'bn_name' => 'ঢাকা',
            'code' => 'DHAKA',
            'division_id' => $dhakaDivision->id,
            'is_active' => true,
        ]);

        // Create some upazillas
        $upazillas = [
            ['name' => 'Dhanmondi', 'bn_name' => 'ধানমন্ডি', 'code' => 'DHANMONDI'],
            ['name' => 'Gulshan', 'bn_name' => 'গুলশান', 'code' => 'GULSHAN'],
            ['name' => 'Wari', 'bn_name' => 'ওয়ারী', 'code' => 'WARI'],
            ['name' => 'Tejgaon', 'bn_name' => 'তেজগাঁও', 'code' => 'TEJGAON'],
        ];

        foreach ($upazillas as $upazillaData) {
            Upazilla::firstOrCreate([
                'name' => $upazillaData['name'],
                'bn_name' => $upazillaData['bn_name'],
                'code' => $upazillaData['code'],
                'district_id' => $dhakaDistrict->id,
                'is_active' => true,
            ]);
        }
    }

    private function createSuperAdmin(): void
    {
        // Get first upazilla for address
        $upazilla = Upazilla::first();
        
        // Create an address for super admin
        $address = Address::create([
            'street_address' => 'Super Admin Office, Block A',
            'landmark' => 'Near Main Road',
            'postal_code' => '1000',
            'division_id' => $upazilla->district->division_id,
            'district_id' => $upazilla->district_id,
            'upazilla_id' => $upazilla->id,
        ]);

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@emimanager.com'],
            [
                'name' => 'Super Administrator',
                'phone' => '+8801700000000',
                'password' => bcrypt('SuperAdmin@123'),
                'present_address_id' => $address->id,
                'permanent_address_id' => $address->id,
                'can_change_password' => true,
                'is_active' => true,
            ]
        );

        // Assign super admin role
        $superAdmin->assignRole('super_admin');

        $this->command->info('Super Admin created: superadmin@emimanager.com / SuperAdmin@123');
    }
}
