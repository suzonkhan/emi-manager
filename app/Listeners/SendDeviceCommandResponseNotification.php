<?php

namespace App\Listeners;

use App\Events\DeviceCommandResponseReceived;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDeviceCommandResponseNotification
{
    public function __construct(private FirebaseService $firebaseService) {}

    /**
     * Handle the event.
     */
    public function handle(DeviceCommandResponseReceived $event): void
    {
        try {
            $commandLog = $event->commandLog;
            
            // Create notification data
            $notificationData = [
                'command_log_id' => $commandLog->id,
                'customer_id'    => $commandLog->customer_id,
                'command'        => $commandLog->command,
                'status'         => $commandLog->status,
                'response_data'  => $event->responseData,
                'timestamp'      => now()->toIso8601String(),
                'customer_name'  => $commandLog->customer->name ?? 'Unknown',
                'read'           => false,
            ];

            // 1. Send to Firebase Realtime Database for web app
            $this->sendToRealtimeDatabase($commandLog, $notificationData);

            // 2. Send FCM push notification to admin Android app
            $this->sendFcmToAdmin($commandLog, $notificationData);

        } catch (\Exception $e) {
            Log::error('Failed to send device command response notification', [
                'error'          => $e->getMessage(),
                'command_log_id' => $event->commandLog->id,
            ]);
        }
    }

    /**
     * Send to Firebase Realtime Database (for web app)
     */
    private function sendToRealtimeDatabase($commandLog, array $data): void
    {
        try {
            $databaseUrl = config('firebase.database_url');
            
            if (!$databaseUrl) {
                Log::warning('Firebase Database URL not configured, skipping RTDB notification');
                return;
            }

            $path = "device_command_responses/{$commandLog->sent_by}/{$commandLog->id}.json";
            $url = rtrim($databaseUrl, '/') . '/' . $path;

            $response = Http::timeout(5)->put($url, $data);

            if ($response->successful()) {
                Log::info('RTDB notification sent', [
                    'command_log_id' => $commandLog->id,
                    'user_id'        => $commandLog->sent_by,
                ]);
            } else {
                Log::error('Failed to send RTDB notification', [
                    'status'         => $response->status(),
                    'command_log_id' => $commandLog->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RTDB notification error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send FCM push notification to admin Android app
     */
    private function sendFcmToAdmin($commandLog, array $data): void
    {
        try {
            // Get admin user who sent the command
            $admin = $commandLog->sentBy;
            
            if (!$admin || !$admin->fcm_token) {
                Log::info('Admin has no FCM token, skipping push notification', [
                    'user_id' => $commandLog->sent_by,
                ]);
                return;
            }

            // Prepare FCM notification
            $title = '✅ Command Executed';
            $body = "{$data['customer_name']}'s device: {$data['command']} - {$data['status']}";

            $fcmData = [
                'command_log_id' => (string) $data['command_log_id'],
                'customer_id'    => (string) $data['customer_id'],
                'customer_name'  => $data['customer_name'],
                'command'        => $data['command'],
                'status'         => $data['status'],
                'timestamp'      => $data['timestamp'],
            ];

            // Send FCM with notification (shows in notification tray)
            $result = $this->firebaseService->sendDataMessage(
                $admin->fcm_token,
                'COMMAND_RESPONSE',
                array_merge($fcmData, [
                    'title' => $title,
                    'body'  => $body,
                ])
            );

            if ($result['success']) {
                Log::info('FCM notification sent to admin', [
                    'user_id'        => $admin->id,
                    'command_log_id' => $commandLog->id,
                ]);
            } else {
                Log::error('Failed to send FCM to admin', [
                    'user_id' => $admin->id,
                    'error'   => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM notification error', ['error' => $e->getMessage()]);
        }
    }
}

