<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting EMI Manager Database Seeding...');
        $this->command->newLine();

        // 1. Core System Setup (Roles & Permissions)
        $this->command->info('📋 Setting up roles and permissions...');
        $this->call(RolePermissionSeeder::class);
        $this->command->newLine();

        // 2. Location Data (Required for addresses)
        $this->command->info('🗺️  Setting up Bangladesh location data...');
        $this->call(BangladeshLocationSeeder::class);
        $this->command->newLine();

        // 3. User Hierarchy (Super Admin → Dealer → Sub Dealer → Salesman)
        $this->command->info('👥 Creating user hierarchy...');
        $this->call(UserHierarchySeeder::class);
        $this->command->newLine();

        // 4. Token Management System (Generate → Assign → Use)
        $this->command->info('🎫 Setting up token management system...');
        $this->call(TokenManagementSeeder::class);
        $this->command->newLine();

        // 5. Customer Data (EMI customers with products)
        $this->command->info('🏠 Creating customer data...');
        $this->call(CustomerDataSeeder::class);
        $this->command->newLine();

        $this->command->info('✅ EMI Manager Database Seeding Completed Successfully!');
        $this->command->newLine();

        // Print system overview
        $this->printSystemOverview();
    }

    private function printSystemOverview(): void
    {
        $this->command->info('📊 SYSTEM OVERVIEW:');
        $this->command->table(
            ['Component', 'Status', 'Details'],
            [
                ['Roles & Permissions', '✅ Ready', 'super_admin, dealer, sub_dealer, salesman roles configured'],
                ['Location Data', '✅ Ready', 'Bangladesh divisions, districts, upazillas loaded'],
                ['User Hierarchy', '✅ Ready', 'Complete organizational structure created'],
                ['Token System', '✅ Ready', '12-character tokens with full assignment chain'],
                ['Customer Data', '✅ Ready', 'EMI customers with realistic product data'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔐 DEFAULT LOGIN CREDENTIALS:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'admin@emimanager.com', 'Admin@123'],
                ['Dealers', '{name}.{region}@emimanager.com', 'Dealer@123'],
                ['Sub Dealers', '{zone}.{dealer_id}@emimanager.com', 'SubDealer@123'],
                ['Salesmen', '{name}.{subdealer_id}@emimanager.com', 'Salesman@123'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🎯 WORKFLOW READY:');
        $this->command->line('  • Super Admin generates tokens');
        $this->command->line('  • Dealers receive token allocations');
        $this->command->line('  • Sub Dealers distribute to territories');
        $this->command->line('  • Salesmen use tokens for customer onboarding');
        $this->command->line('  • Complete EMI management system operational');

        $this->command->newLine();
        $this->command->info('🚀 Your EMI Management System is ready to use!');
    }
}
