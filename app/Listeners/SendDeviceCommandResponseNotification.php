<?php

namespace App\Listeners;

use App\Events\DeviceCommandResponseReceived;
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Facades\Log;

class SendDeviceCommandResponseNotification
{
    protected Database $database;

    /**
     * Create the event listener.
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

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

            // Send to Firebase Realtime Database
            // Path: /device_command_responses/{user_id}/{command_log_id}
            $this->database
                ->getReference('device_command_responses/' . $commandLog->sent_by . '/' . $commandLog->id)
                ->set($notificationData);

            Log::info('Device command response notification sent', [
                'command_log_id' => $commandLog->id,
                'user_id'        => $commandLog->sent_by,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send device command response notification', [
                'error'          => $e->getMessage(),
                'command_log_id' => $event->commandLog->id,
            ]);
        }
    }
}

