<?php

namespace App\Listeners;

use App\Events\DeviceCommandResponseReceived;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDeviceCommandResponseNotification
{
    /**
     * Handle the event.
     */
    public function handle(DeviceCommandResponseReceived $event): void
    {
        try {
            $commandLog = $event->commandLog;
            
            // Get Firebase Database URL from config
            $databaseUrl = config('firebase.database_url');
            
            if (!$databaseUrl) {
                Log::warning('Firebase Database URL not configured, skipping real-time notification');
                return;
            }

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

            // Send to Firebase Realtime Database via HTTP
            // Path: /device_command_responses/{user_id}/{command_log_id}
            $path = "device_command_responses/{$commandLog->sent_by}/{$commandLog->id}.json";
            $url = rtrim($databaseUrl, '/') . '/' . $path;

            $response = Http::timeout(5)->put($url, $notificationData);

            if ($response->successful()) {
                Log::info('Device command response notification sent', [
                    'command_log_id' => $commandLog->id,
                    'user_id'        => $commandLog->sent_by,
                ]);
            } else {
                Log::error('Failed to send device command response notification', [
                    'status'         => $response->status(),
                    'response'       => $response->body(),
                    'command_log_id' => $commandLog->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send device command response notification', [
                'error'          => $e->getMessage(),
                'command_log_id' => $event->commandLog->id,
            ]);
        }
    }
}

