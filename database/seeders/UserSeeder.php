<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Upazilla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSuperAdmin();
    }

    private function createSuperAdmin(): void
    {
        // Get first upazilla for address (should exist from BangladeshLocationSeeder)
        $upazilla = Upazilla::first();

        if (! $upazilla) {
            $this->command->error('No upazillas found. Please run BangladeshLocationSeeder first.');

            return;
        }

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
                'password' => Hash::make('SuperAdmin@123'),
                'plain_password' => 'SuperAdmin@123',
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
