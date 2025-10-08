<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\DeviceCommandService;
use Illuminate\Console\Command;

class TestDeviceCommand extends Command
{
    protected $signature = 'device:test 
                            {customer_id : The customer ID to test with}
                            {device_command? : The command to send (optional)}';

    protected $description = 'Test device control APIs with a simulated FCM token';

    public function __construct(private DeviceCommandService $deviceCommandService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $customerId = $this->argument('customer_id');
        $command = $this->argument('device_command');

        $this->info('🧪 Device Control API Test');
        $this->newLine();

        // Find customer
        try {
            $customer = Customer::findOrFail($customerId);
        } catch (\Exception $e) {
            $this->error("Customer not found with ID: {$customerId}");

            return self::FAILURE;
        }

        // Check if device is registered
        if (! $customer->hasDevice()) {
            $this->warn('⚠️  Customer does not have a device registered.');
            $this->newLine();

            if ($this->confirm('Would you like to register a test device?', true)) {
                $this->registerTestDevice($customer);
            } else {
                return self::FAILURE;
            }
        }

        // Display customer info
        $this->displayCustomerInfo($customer);

        // If no command specified, show menu
        if (! $command) {
            return $this->showCommandMenu($customer);
        }

        // Execute the specified command
        return $this->executeCommand($customer, $command);
    }

    private function registerTestDevice(Customer $customer): void
    {
        $this->info('Registering test device...');

        $testToken = $this->generateTestFCMToken();
        $serialNumber = 'TEST_'.strtoupper(substr(md5(time()), 0, 10));

        $customer->update([
            'serial_number' => $serialNumber,
            'fcm_token' => $testToken,
        ]);

        $this->line('✅ Test device registered');
        $this->line("   Serial: {$serialNumber}");
        $this->line("   FCM Token: {$testToken}");
        $this->newLine();
    }

    private function displayCustomerInfo(Customer $customer): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Customer ID', $customer->id],
                ['Name', $customer->name],
                ['Mobile', $customer->mobile],
                ['IMEI 1', $customer->imei_1 ?? 'Not set'],
                ['Serial Number', $customer->serial_number ?? 'Not set'],
                ['FCM Token', $customer->fcm_token ? substr($customer->fcm_token, 0, 30).'...' : 'Not set'],
                ['Device Locked', $customer->is_device_locked ? 'Yes' : 'No'],
                ['Can Receive Commands', $customer->canReceiveCommands() ? 'Yes' : 'No'],
            ]
        );
        $this->newLine();
    }

    private function showCommandMenu(Customer $customer): int
    {
        $commands = [
            '1' => 'lock - Lock Device',
            '2' => 'unlock - Unlock Device',
            '3' => 'disable-camera - Disable Camera',
            '4' => 'enable-camera - Enable Camera',
            '5' => 'show-message - Show Message',
            '6' => 'reminder-screen - Show Reminder',
            '7' => 'reboot - Reboot Device',
            '8' => 'request-location - Request Location',
            '0' => 'Exit',
        ];

        $this->info('Available Commands:');
        foreach ($commands as $key => $desc) {
            $this->line("  [{$key}] {$desc}");
        }
        $this->newLine();

        $choice = $this->ask('Select a command (0-8)');

        $commandMap = [
            '1' => 'lock',
            '2' => 'unlock',
            '3' => 'disable-camera',
            '4' => 'enable-camera',
            '5' => 'show-message',
            '6' => 'reminder-screen',
            '7' => 'reboot',
            '8' => 'request-location',
        ];

        if ($choice === '0') {
            return self::SUCCESS;
        }

        if (! isset($commandMap[$choice])) {
            $this->error('Invalid choice');

            return self::FAILURE;
        }

        return $this->executeCommand($customer, $commandMap[$choice]);
    }

    private function executeCommand(Customer $customer, string $command): int
    {
        $this->info("Executing command: {$command}");
        $this->newLine();

        $user = \App\Models\User::first();

        if (! $user) {
            $this->error('No user found in database. Please run seeders first.');

            return self::FAILURE;
        }

        try {
            $result = match ($command) {
                'lock' => $this->deviceCommandService->lockDevice($customer, $user),
                'unlock' => $this->deviceCommandService->unlockDevice($customer, $user),
                'disable-camera' => $this->deviceCommandService->disableCamera($customer, $user),
                'enable-camera' => $this->deviceCommandService->enableCamera($customer, $user),
                'show-message' => $this->sendMessage($customer, $user),
                'reminder-screen' => $this->sendReminder($customer, $user),
                'reboot' => $this->deviceCommandService->rebootDevice($customer, $user),
                'request-location' => $this->deviceCommandService->requestLocation($customer, $user),
                default => throw new \Exception("Unknown command: {$command}"),
            };

            if ($result['success']) {
                $this->line('<fg=green>✅ Command sent successfully</>');
                $this->newLine();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Command', $result['command']],
                        ['Status', $result['status']],
                        ['Message', $result['message']],
                        ['Sent At', $result['sent_at'] ?? 'Just now'],
                    ]
                );

                $this->newLine();
                $this->warn('⚠️  Note: This is a test FCM token, so the message won\'t be delivered to a real device.');
                $this->info('💡 To test with a real device, use the Android app to get a valid FCM token.');
            } else {
                $this->error('❌ Command failed');
                $this->error($result['message'] ?? 'Unknown error');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function sendMessage(Customer $customer, $user): array
    {
        $title = $this->ask('Message title', 'Payment Reminder');
        $message = $this->ask('Message text', 'Please pay your EMI installment.');

        return $this->deviceCommandService->showMessage($customer, $user, $message, $title);
    }

    private function sendReminder(Customer $customer, $user): array
    {
        $message = $this->ask('Reminder message', 'Your EMI payment is due!');

        return $this->deviceCommandService->showReminderScreen($customer, $user, $message);
    }

    private function generateTestFCMToken(): string
    {
        // Generate a realistic-looking test FCM token
        // Real FCM tokens are ~152+ characters
        $prefix = 'eXXX';
        $suffix = 'XXXe';
        $middle = bin2hex(random_bytes(64)); // 128 characters

        return $prefix.$middle.$suffix;
    }
}
