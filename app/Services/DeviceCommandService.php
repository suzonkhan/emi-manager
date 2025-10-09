<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DeviceCommandLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class DeviceCommandService
{
    public function __construct(private FirebaseService $firebaseService) {}

    /**
     * Register device for customer
     * Find customer by serial_number OR IMEI1 (which should be pre-registered during customer creation)
     */
    public function registerDevice(string $serialNumber, string $imei1, string $fcmToken): Customer
    {
        // Find customer by serial_number OR IMEI1
        $customer = Customer::where('serial_number', $serialNumber)
            ->orWhere('imei_1', $imei1)
            ->firstOrFail();

        // Update only FCM token
        $customer->update([
            'fcm_token' => $fcmToken,
        ]);

        return $customer->fresh();
    }

    /**
     * Send command to device and log the action
     */
    private function sendCommand(Customer $customer, string $command, array $commandData, User $user, callable $firebaseMethod): array
    {
        if (! $customer->canReceiveCommands()) {
            throw new Exception('Customer device is not registered or customer is not active');
        }

        DB::beginTransaction();

        try {
            // Create command log
            $log = DeviceCommandLog::create([
                'customer_id' => $customer->id,
                'command' => $command,
                'command_data' => $commandData,
                'status' => 'pending',
                'sent_by' => $user->id,
            ]);

            // Send FCM message
            $result = $firebaseMethod($customer->fcm_token);

            // Update log with result
            if ($result['success']) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'fcm_response' => json_encode($result),
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'fcm_response' => json_encode($result),
                ]);
            }

            // Update customer's last command time
            $customer->update(['last_command_sent_at' => now()]);

            DB::commit();

            return [
                'success' => $result['success'],
                'command' => $command,
                'log_id' => $log->id,
                'message' => $result['success'] ? 'Command sent successfully' : 'Command failed to send',
                'details' => $result,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lock device
     */
    public function lockDevice(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'LOCK_DEVICE',
            ['state' => 'true'],
            $user,
            fn ($token) => $this->firebaseService->lockDevice($token)
        );

        if ($result['success']) {
            $customer->update(['is_device_locked' => true]);
        }

        return $result;
    }

    /**
     * Unlock device
     */
    public function unlockDevice(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'UNLOCK_DEVICE',
            ['state' => 'false'],
            $user,
            fn ($token) => $this->firebaseService->unlockDevice($token)
        );

        if ($result['success']) {
            $customer->update(['is_device_locked' => false]);
        }

        return $result;
    }

    /**
     * Disable camera
     */
    public function disableCamera(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'DISABLE_CAMERA',
            ['state' => 'true'],
            $user,
            fn ($token) => $this->firebaseService->disableCamera($token)
        );

        if ($result['success']) {
            $customer->update(['is_camera_disabled' => true]);
        }

        return $result;
    }

    /**
     * Enable camera
     */
    public function enableCamera(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'ENABLE_CAMERA',
            ['state' => 'false'],
            $user,
            fn ($token) => $this->firebaseService->enableCamera($token)
        );

        if ($result['success']) {
            $customer->update(['is_camera_disabled' => false]);
        }

        return $result;
    }

    /**
     * Disable bluetooth
     */
    public function disableBluetooth(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'DISABLE_BLUETOOTH',
            ['state' => 'true'],
            $user,
            fn ($token) => $this->firebaseService->disableBluetooth($token)
        );

        if ($result['success']) {
            $customer->update(['is_bluetooth_disabled' => true]);
        }

        return $result;
    }

    /**
     * Enable bluetooth
     */
    public function enableBluetooth(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'ENABLE_BLUETOOTH',
            ['state' => 'false'],
            $user,
            fn ($token) => $this->firebaseService->enableBluetooth($token)
        );

        if ($result['success']) {
            $customer->update(['is_bluetooth_disabled' => false]);
        }

        return $result;
    }

    /**
     * Hide app
     */
    public function hideApp(Customer $customer, User $user, ?string $packageName = null): array
    {
        $result = $this->sendCommand(
            $customer,
            'HIDE_APP',
            ['state' => 'true', 'package_name' => $packageName],
            $user,
            fn ($token) => $this->firebaseService->hideApp($token, $packageName)
        );

        if ($result['success']) {
            $customer->update(['is_app_hidden' => true]);
        }

        return $result;
    }

    /**
     * Unhide app
     */
    public function unhideApp(Customer $customer, User $user, ?string $packageName = null): array
    {
        $result = $this->sendCommand(
            $customer,
            'UNHIDE_APP',
            ['state' => 'false', 'package_name' => $packageName],
            $user,
            fn ($token) => $this->firebaseService->unhideApp($token, $packageName)
        );

        if ($result['success']) {
            $customer->update(['is_app_hidden' => false]);
        }

        return $result;
    }

    /**
     * Reset password
     */
    public function resetPassword(Customer $customer, User $user, string $newPassword): array
    {
        $result = $this->sendCommand(
            $customer,
            'RESET_PASSWORD',
            ['password' => $newPassword],
            $user,
            fn ($token) => $this->firebaseService->resetPassword($token, $newPassword)
        );

        if ($result['success']) {
            $customer->update(['has_password' => true]);
        }

        return $result;
    }

    /**
     * Remove password
     */
    public function removePassword(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'REMOVE_PASSWORD',
            [],
            $user,
            fn ($token) => $this->firebaseService->removePassword($token)
        );

        if ($result['success']) {
            $customer->update(['has_password' => false]);
        }

        return $result;
    }

    /**
     * Reboot device
     */
    public function rebootDevice(Customer $customer, User $user): array
    {
        return $this->sendCommand(
            $customer,
            'REBOOT_DEVICE',
            [],
            $user,
            fn ($token) => $this->firebaseService->rebootDevice($token)
        );
    }

    /**
     * Remove app
     */
    public function removeApp(Customer $customer, User $user, string $packageName): array
    {
        return $this->sendCommand(
            $customer,
            'REMOVE_APP',
            ['package_name' => $packageName],
            $user,
            fn ($token) => $this->firebaseService->removeApp($token, $packageName)
        );
    }

    /**
     * Wipe device
     */
    public function wipeDevice(Customer $customer, User $user): array
    {
        return $this->sendCommand(
            $customer,
            'WIPE_DEVICE',
            [],
            $user,
            fn ($token) => $this->firebaseService->wipeDevice($token)
        );
    }

    /**
     * Show message
     */
    public function showMessage(Customer $customer, User $user, string $message, string $title = ''): array
    {
        return $this->sendCommand(
            $customer,
            'SHOW_MESSAGE',
            ['title' => $title, 'message' => $message],
            $user,
            fn ($token) => $this->firebaseService->showMessage($token, $message, $title)
        );
    }

    /**
     * Show reminder screen
     */
    public function showReminderScreen(Customer $customer, User $user, string $message): array
    {
        return $this->sendCommand(
            $customer,
            'REMINDER_SCREEN',
            ['message' => $message],
            $user,
            fn ($token) => $this->firebaseService->showReminderScreen($token, $message)
        );
    }

    /**
     * Play reminder audio
     */
    public function playReminderAudio(Customer $customer, User $user, ?string $audioUrl = null): array
    {
        return $this->sendCommand(
            $customer,
            'REMINDER_AUDIO',
            ['audio_url' => $audioUrl],
            $user,
            fn ($token) => $this->firebaseService->playReminderAudio($token, $audioUrl)
        );
    }

    /**
     * Set wallpaper
     */
    public function setWallpaper(Customer $customer, User $user, string $imageUrl): array
    {
        $result = $this->sendCommand(
            $customer,
            'SET_WALLPAPER',
            ['image_url' => $imageUrl],
            $user,
            fn ($token) => $this->firebaseService->setWallpaper($token, $imageUrl)
        );

        if ($result['success']) {
            $customer->update(['custom_wallpaper_url' => $imageUrl]);
        }

        return $result;
    }

    /**
     * Remove wallpaper
     */
    public function removeWallpaper(Customer $customer, User $user): array
    {
        $result = $this->sendCommand(
            $customer,
            'REMOVE_WALLPAPER',
            [],
            $user,
            fn ($token) => $this->firebaseService->removeWallpaper($token)
        );

        if ($result['success']) {
            $customer->update(['custom_wallpaper_url' => null]);
        }

        return $result;
    }

    /**
     * Request location
     */
    public function requestLocation(Customer $customer, User $user): array
    {
        return $this->sendCommand(
            $customer,
            'REQUEST_LOCATION',
            [],
            $user,
            fn ($token) => $this->firebaseService->requestLocation($token)
        );
    }

    /**
     * Get command history for customer
     */
    public function getCommandHistory(Customer $customer, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $customer->deviceCommandLogs()
            ->with('sentBy')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
