<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;

class TestFirebaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'firebase:test {fcm_token?}';

    /**
     * The console command description.
     */
    protected $description = 'Test Firebase Cloud Messaging connection by sending a test message';

    public function __construct(private FirebaseService $firebaseService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Firebase Connection Test');
        $this->newLine();

        $fcmToken = $this->argument('fcm_token');

        if (! $fcmToken) {
            $this->warn('No FCM token provided. Testing service initialization only.');
            $this->newLine();

            try {
                $this->info('✓ FirebaseService initialized successfully');
                $this->info('✓ Firebase credentials loaded');
                $this->info('✓ Messaging instance created');
                $this->newLine();
                $this->line('<fg=green>✅ Firebase is properly configured!</>');
                $this->newLine();
                $this->info('To test sending a message, provide an FCM token:');
                $this->line('  <fg=yellow>php artisan firebase:test YOUR_FCM_TOKEN</>');

                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error('✗ Firebase initialization failed');
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('Testing message send to token:');
        $this->line('  '.$fcmToken);
        $this->newLine();

        try {
            $result = $this->firebaseService->sendDataMessage(
                $fcmToken,
                'TEST_MESSAGE',
                [
                    'title' => 'Test Message',
                    'body' => 'This is a test message from EMI Manager',
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            if ($result['success']) {
                $this->line('<fg=green>✅ Message sent successfully!</>');
                $this->newLine();
                $this->info('Message ID: '.$result['message_id']);
                $this->info('Response: '.json_encode($result['response'], JSON_PRETTY_PRINT));
                $this->newLine();
                $this->line('<fg=cyan>Check the device to see if it received the message.</>');

                return self::SUCCESS;
            }

            $this->error('✗ Failed to send message');
            $this->error($result['error'] ?? 'Unknown error');

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('✗ Error sending message');
            $this->error($e->getMessage());
            $this->newLine();

            if (str_contains($e->getMessage(), 'Invalid registration token')) {
                $this->warn('The FCM token appears to be invalid or expired.');
                $this->info('Make sure you are using a valid FCM token from a registered device.');
            }

            return self::FAILURE;
        }
    }
}
