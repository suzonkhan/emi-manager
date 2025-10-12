<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PopulatePlainPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:populate-plain-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate plain_password field for existing users with default passwords based on their role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Populating plain passwords for existing users...');
        
        $users = \App\Models\User::whereNull('plain_password')->orWhere('plain_password', '')->get();
        
        $count = 0;
        foreach ($users as $user) {
            $role = $user->getRoleNames()->first();
            
            // Set default passwords based on role or generate a secure one
            $defaultPassword = match($role) {
                'super_admin' => 'SuperAdmin@123',
                'dealer' => 'Dealer@123',
                'sub_dealer' => 'SubDealer@123',
                'salesman' => 'Salesman@123',
                default => 'Password@123'
            };
            
            // Update the plain password (keeping the existing hashed password)
            $user->update(['plain_password' => $defaultPassword]);
            $count++;
            
            $this->line("Updated user: {$user->name} ({$role}) - Password: {$defaultPassword}");
        }
        
        $this->info("Successfully updated {$count} users with plain passwords.");
        $this->warn('Note: These are default passwords. Users should change them for security.');
        
        return 0;
    }
}
