<?php

namespace App\Services;

use Exception;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseService
{
    private Messaging $messaging;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        // If no path in config, use default storage path
        if (! $credentialsPath) {
            $credentialsPath = storage_path('app/firebase/ime-locker-app-credentials.json');
        }

        // If path is relative (doesn't start with / or C:), resolve it
        // Check if it's not already an absolute path
        if (! str_starts_with($credentialsPath, '/') && ! preg_match('/^[A-Z]:/i', $credentialsPath)) {
            // If path starts with 'storage/', strip it and use storage_path()
            if (str_starts_with($credentialsPath, 'storage/')) {
                $credentialsPath = storage_path(substr($credentialsPath, 8)); // Remove 'storage/' prefix
            } else {
                $credentialsPath = base_path($credentialsPath);
            }
        }

        if (! file_exists($credentialsPath)) {
            throw new Exception('Firebase credentials file not found at: '.$credentialsPath);
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send FCM data message to device
     */
    public function sendDataMessage(string $fcmToken, string $command, array $data = []): array
    {
        try {
            $messageData = array_merge([
                'command' => $command,
            ], $data);

            $message = CloudMessage::new()
                ->toToken($fcmToken)
                ->withData($messageData)
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                    ])
                );

            $result = $this->messaging->send($message);

            return [
                'success' => true,
                'message_id' => $result,
                'response' => $result,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Send command to lock device
     */
    public function lockDevice(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'LOCK_DEVICE', ['state' => 'true']);
    }

    /**
     * Send command to unlock device
     */
    public function unlockDevice(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'UNLOCK_DEVICE', ['state' => 'false']);
    }

    /**
     * Send command to disable camera
     */
    public function disableCamera(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'DISABLE_CAMERA', ['state' => 'true']);
    }

    /**
     * Send command to enable camera
     */
    public function enableCamera(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'ENABLE_CAMERA', ['state' => 'false']);
    }

    /**
     * Send command to disable bluetooth
     */
    public function disableBluetooth(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'DISABLE_BLUETOOTH', ['state' => 'true']);
    }

    /**
     * Send command to enable bluetooth
     */
    public function enableBluetooth(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'ENABLE_BLUETOOTH', ['state' => 'false']);
    }

    /**
     * Send command to hide app
     */
    public function hideApp(string $fcmToken, ?string $packageName = null): array
    {
        $data = ['state' => 'true'];
        if ($packageName) {
            $data['package_name'] = $packageName;
        }

        return $this->sendDataMessage($fcmToken, 'HIDE_APP', $data);
    }

    /**
     * Send command to unhide app
     */
    public function unhideApp(string $fcmToken, ?string $packageName = null): array
    {
        $data = ['state' => 'false'];
        if ($packageName) {
            $data['package_name'] = $packageName;
        }

        return $this->sendDataMessage($fcmToken, 'UNHIDE_APP', $data);
    }

    /**
     * Send command to reset password
     */
    public function resetPassword(string $fcmToken, string $newPassword): array
    {
        return $this->sendDataMessage($fcmToken, 'RESET_PASSWORD', ['password' => $newPassword]);
    }

    /**
     * Send command to remove password
     */
    public function removePassword(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'REMOVE_PASSWORD');
    }

    /**
     * Send command to reboot device
     */
    public function rebootDevice(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'REBOOT_DEVICE');
    }

    /**
     * Send command to remove app
     */
    public function removeApp(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'REMOVE_APP');
    }

    /**
     * Send command to wipe device
     */
    public function wipeDevice(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'WIPE_DEVICE');
    }

    /**
     * Send command to show message
     */
    public function showMessage(string $fcmToken, string $message, string $title = ''): array
    {
        return $this->sendDataMessage($fcmToken, 'SHOW_MESSAGE', [
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Send command to show reminder screen
     */
    public function showReminderScreen(string $fcmToken, string $message): array
    {
        return $this->sendDataMessage($fcmToken, 'REMINDER_SCREEN', ['message' => $message]);
    }

    /**
     * Send command to play reminder audio
     */
    public function playReminderAudio(string $fcmToken, ?string $audioUrl = null): array
    {
        $data = [];
        if ($audioUrl) {
            $data['audio_url'] = $audioUrl;
        }

        return $this->sendDataMessage($fcmToken, 'REMINDER_AUDIO', $data);
    }

    /**
     * Send command to set wallpaper
     */
    public function setWallpaper(string $fcmToken, string $imageUrl): array
    {
        return $this->sendDataMessage($fcmToken, 'SET_WALLPAPER', ['image_url' => $imageUrl]);
    }

    /**
     * Send command to remove wallpaper
     */
    public function removeWallpaper(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'REMOVE_WALLPAPER');
    }

    /**
     * Send command to request location
     */
    public function requestLocation(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'REQUEST_LOCATION');
    }

    /**
     * Send command to enable calls
     */
    public function enableCall(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'ENABLE_CALL', ['state' => 'false']);
    }

    /**
     * Send command to disable calls
     */
    public function disableCall(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'DISABLE_CALL', ['state' => 'true']);
    }

    /**
     * Send command to lock USB
     */
    public function lockUsb(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'LOCK_USB', ['state' => 'true']);
    }

    /**
     * Send command to unlock USB
     */
    public function unlockUsb(string $fcmToken): array
    {
        return $this->sendDataMessage($fcmToken, 'UNLOCK_USB', ['state' => 'false']);
    }

    /**
     * Validate FCM token
     */
    public function validateToken(string $fcmToken): bool
    {
        try {
            $this->messaging->validate($fcmToken);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
