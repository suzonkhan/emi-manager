<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceCommandRequest;
use App\Http\Requests\Api\DeviceRegisterRequest;
use App\Http\Requests\Api\DeviceStatusCheckRequest;
use App\Http\Resources\DeviceCommandLogResource;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\DeviceStatusCheckResource;
use App\Models\Customer;
use App\Services\DeviceCommandService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private DeviceCommandService $deviceCommandService)
    {}

    /**
     * Check device status (for factory reset recovery)
     * Finds device by serial number or IMEI and returns current status
     * Public endpoint - no authentication required
     */
    public function checkStatus(DeviceStatusCheckRequest $request): JsonResponse
    {
        try {
            $serialNumber = $request->input('serial_number');
            $imei1        = $request->input('imei1');

            // Find customer/device by serial number or IMEI
            $customer = Customer::where(function ($query) use ($serialNumber, $imei1) {
                $query->where('serial_number', $serialNumber)
                    ->orWhere('imei_1', $imei1);
            })->first();

            // Device not found in system
            if (!$customer) {
                return response()->json([
                    'success'       => false,
                    'is_registered' => false,
                    'message'       => 'Device not found in system',
                    'data'          => [
                        'serial_number' => $serialNumber,
                        'imei1'         => $imei1,
                    ],
                ], 404);
            }

            // Device found - return full status with resource
            return response()->json(
                new DeviceStatusCheckResource($customer)
            );
        } catch (Exception $e) {
            return $this->error('Failed to check device status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Register device for a customer
     * Finds customer by IMEI1 and updates device info
     */
    public function register(DeviceRegisterRequest $request): JsonResponse
    {
        try {
            $customer = $this->deviceCommandService->registerDevice(
                $request->input('serial_number'),
                $request->input('imei1'),
                $request->input('fcm_token')
            );

            return $this->success([
                'message' => 'Device registered successfully',
                'device'  => new DeviceResource($customer),
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get device information
     */
    public function show(Customer $customer): JsonResponse
    {
        return $this->success([
            'device' => new DeviceResource($customer),
        ]);
    }

    /**
     * Send command to device
     */
    public function sendCommand(DeviceCommandRequest $request, string $command): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($request->input('customer_id'));
            $user     = $request->user();

            $result = match ($command) {
                'lock'              => $this->deviceCommandService->lockDevice($customer, $user),
                'unlock'            => $this->deviceCommandService->unlockDevice($customer, $user),
                'disable-camera'    => $this->deviceCommandService->disableCamera($customer, $user),
                'enable-camera'     => $this->deviceCommandService->enableCamera($customer, $user),
                'disable-bluetooth' => $this->deviceCommandService->disableBluetooth($customer, $user),
                'enable-bluetooth'  => $this->deviceCommandService->enableBluetooth($customer, $user),
                'hide-app'          => $this->deviceCommandService->hideApp($customer, $user, $request->input('package_name')),
                'unhide-app'        => $this->deviceCommandService->unhideApp($customer, $user, $request->input('package_name')),
                'reset-password'    => $this->deviceCommandService->resetPassword($customer, $user, $request->input('password')),
                'remove-password'   => $this->deviceCommandService->removePassword($customer, $user),
                'reboot'            => $this->deviceCommandService->rebootDevice($customer, $user),
                'remove-app'        => $this->deviceCommandService->removeApp($customer, $user),
                'wipe'              => $this->deviceCommandService->wipeDevice($customer, $user),
                'show-message'      => $this->deviceCommandService->showMessage($customer, $user, $request->input('message'), $request->input('title', '')),
                'reminder-screen'   => $this->deviceCommandService->showReminderScreen($customer, $user, $request->input('message')),
                'reminder-audio'    => $this->deviceCommandService->playReminderAudio($customer, $user, $request->input('audio_url')),
                'set-wallpaper'     => $this->deviceCommandService->setWallpaper($customer, $user, $request->input('image_url')),
                'remove-wallpaper'  => $this->deviceCommandService->removeWallpaper($customer, $user),
                'request-location'  => $this->deviceCommandService->requestLocation($customer, $user),
                'enable-call'       => $this->deviceCommandService->enableCall($customer, $user),
                'disable-call'      => $this->deviceCommandService->disableCall($customer, $user),
                'lock-usb'          => $this->deviceCommandService->lockUsb($customer, $user),
                'unlock-usb'        => $this->deviceCommandService->unlockUsb($customer, $user),
                default             => throw new Exception('Invalid command: ' . $command),
            };

            if ($result['success']) {
                return $this->success($result);
            }

            return $this->error($result['message'], $result, 400);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Send message only (no command execution)
     * Just displays a message/notification on the device screen
     */
    public function sendMessage(DeviceCommandRequest $request): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($request->input('customer_id'));
            $user     = $request->user();
            $message  = $request->input('message');
            $title    = $request->input('title', 'Notification');

            $result = $this->deviceCommandService->showMessage($customer, $user, $message, $title);

            if ($result['success']) {
                return $this->success($result);
            }

            return $this->error($result['message'], $result, 400);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Send command with custom message display
     * This executes the command AND shows a message on device screen
     */
    public function sendCommandWithMessage(DeviceCommandRequest $request, string $command): JsonResponse
    {
        try {
            $customer       = Customer::findOrFail($request->input('customer_id'));
            $user           = $request->user();
            $displayMessage = $request->input('display_message', '');
            $displayTitle   = $request->input('display_title', 'Notification');

            // Execute the primary command first
            $result = match ($command) {
                'lock'              => $this->deviceCommandService->lockDevice($customer, $user),
                'unlock'            => $this->deviceCommandService->unlockDevice($customer, $user),
                'disable-camera'    => $this->deviceCommandService->disableCamera($customer, $user),
                'enable-camera'     => $this->deviceCommandService->enableCamera($customer, $user),
                'disable-bluetooth' => $this->deviceCommandService->disableBluetooth($customer, $user),
                'enable-bluetooth'  => $this->deviceCommandService->enableBluetooth($customer, $user),
                'hide-app'          => $this->deviceCommandService->hideApp($customer, $user, $request->input('package_name')),
                'unhide-app'        => $this->deviceCommandService->unhideApp($customer, $user, $request->input('package_name')),
                'reset-password'    => $this->deviceCommandService->resetPassword($customer, $user, $request->input('password')),
                'remove-password'   => $this->deviceCommandService->removePassword($customer, $user),
                'reboot'            => $this->deviceCommandService->rebootDevice($customer, $user),
                'remove-app'        => $this->deviceCommandService->removeApp($customer, $user),
                'wipe'              => $this->deviceCommandService->wipeDevice($customer, $user),
                'show-message'      => $this->deviceCommandService->showMessage($customer, $user, $request->input('message'), $request->input('title', '')),
                'reminder-screen'   => $this->deviceCommandService->showReminderScreen($customer, $user, $request->input('message')),
                'reminder-audio'    => $this->deviceCommandService->playReminderAudio($customer, $user, $request->input('audio_url')),
                'set-wallpaper'     => $this->deviceCommandService->setWallpaper($customer, $user, $request->input('image_url')),
                'remove-wallpaper'  => $this->deviceCommandService->removeWallpaper($customer, $user),
                'request-location'  => $this->deviceCommandService->requestLocation($customer, $user),
                'enable-call'       => $this->deviceCommandService->enableCall($customer, $user),
                'disable-call'      => $this->deviceCommandService->disableCall($customer, $user),
                'lock-usb'          => $this->deviceCommandService->lockUsb($customer, $user),
                'unlock-usb'        => $this->deviceCommandService->unlockUsb($customer, $user),
                default             => throw new Exception('Invalid command: ' . $command),
            };

            // If primary command succeeded and message is provided, send message too
            if ($result['success'] && !empty($displayMessage)) {
                $messageResult = $this->deviceCommandService->showMessage(
                    $customer,
                    $user,
                    $displayMessage,
                    $displayTitle
                );

                // Add message result to response
                $result['message_sent']    = $messageResult['success'];
                $result['display_message'] = $displayMessage;
                $result['display_title']   = $displayTitle;
            }

            if ($result['success']) {
                return $this->success($result);
            }

            return $this->error($result['message'], $result, 400);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get command history for a customer
     */
    public function commandHistory(Customer $customer): JsonResponse
    {
        $logs = $this->deviceCommandService->getCommandHistory($customer);

        return $this->success([
            'commands' => DeviceCommandLogResource::collection($logs),
            'total'    => $logs->count(),
        ]);
    }

    /**
     * Get all available commands
     */
    public function availableCommands(): JsonResponse
    {
        return $this->success([
            'commands' => [
                [
                    'command'         => 'lock',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/lock',
                    'description'     => 'Lock the device',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'unlock',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/unlock',
                    'description'     => 'Unlock the device',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'disable-camera',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/disable-camera',
                    'description'     => 'Disable device camera',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'enable-camera',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/enable-camera',
                    'description'     => 'Enable device camera',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'disable-bluetooth',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/disable-bluetooth',
                    'description'     => 'Disable device bluetooth',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'enable-bluetooth',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/enable-bluetooth',
                    'description'     => 'Enable device bluetooth',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'hide-app',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/hide-app',
                    'description'     => 'Hide an app on the device',
                    'requires_params' => true,
                    'params'          => ['package_name' => 'optional'],
                ],
                [
                    'command'         => 'unhide-app',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/unhide-app',
                    'description'     => 'Unhide an app on the device',
                    'requires_params' => true,
                    'params'          => ['package_name' => 'optional'],
                ],
                [
                    'command'         => 'reset-password',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/reset-password',
                    'description'     => 'Reset device password',
                    'requires_params' => true,
                    'params'          => ['password' => 'required'],
                ],
                [
                    'command'         => 'remove-password',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/remove-password',
                    'description'     => 'Remove device password',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'reboot',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/reboot',
                    'description'     => 'Reboot the device',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'remove-app',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/remove-app',
                    'description'     => 'Remove an app from the device',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'wipe',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/wipe',
                    'description'     => 'Wipe device data (factory reset)',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'show-message',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/show-message',
                    'description'     => 'Show a message on the device',
                    'requires_params' => true,
                    'params'          => ['title' => 'optional', 'message' => 'required'],
                ],
                [
                    'command'         => 'reminder-screen',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/reminder-screen',
                    'description'     => 'Show reminder screen',
                    'requires_params' => true,
                    'params'          => ['message' => 'required'],
                ],
                [
                    'command'         => 'reminder-audio',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/reminder-audio',
                    'description'     => 'Play reminder audio',
                    'requires_params' => true,
                    'params'          => ['audio_url' => 'optional'],
                ],
                [
                    'command'         => 'set-wallpaper',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/set-wallpaper',
                    'description'     => 'Set device wallpaper',
                    'requires_params' => true,
                    'params'          => ['image_url' => 'required'],
                ],
                [
                    'command'         => 'remove-wallpaper',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/remove-wallpaper',
                    'description'     => 'Remove custom wallpaper',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'request-location',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/request-location',
                    'description'     => 'Request device location',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'enable-call',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/enable-call',
                    'description'     => 'Enable phone calls (unlock calls)',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'disable-call',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/disable-call',
                    'description'     => 'Disable phone calls (lock calls)',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'lock-usb',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/lock-usb',
                    'description'     => 'Lock USB port (disable USB)',
                    'requires_params' => false,
                ],
                [
                    'command'         => 'unlock-usb',
                    'method'          => 'POST',
                    'endpoint'        => '/api/devices/command/unlock-usb',
                    'description'     => 'Unlock USB port (enable USB)',
                    'requires_params' => false,
                ],
            ],
        ]);
    }

    /**
     * Update device location (called by device)
     * Public endpoint - device sends location update
     */
    /**
     * Receive command response from device (Public endpoint)
     * Device sends response data after executing a command
     */
    public function commandResponse(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'device_id' => 'required|string',
                'command'   => 'required|string',
                'data'      => 'nullable|array',
            ]);

            // Find customer by device_id
            $deviceId = $validated['device_id'];
            $customer = Customer::where('serial_number', $deviceId)
                ->orWhere('imei_1', $deviceId)
                ->orWhere('imei_2', $deviceId)
                ->first();

            if (!$customer) {
                return $this->error('Device not found', null, 404);
            }

            // Find the most recent command log for this command
            $commandLog = \App\Models\DeviceCommandLog::where('customer_id', $customer->id)
                ->where('command', $validated['command'])
                ->where('status', 'sent')
                ->latest()
                ->first();

            if (!$commandLog) {
                return $this->error('Command log not found', null, 404);
            }

            // Update command log with response data
            $commandLog->update([
                'status'   => 'delivered',
                'metadata' => $validated['data'] ?? [],
            ]);

            return $this->success([
                'message'        => 'Command response received successfully',
                'command_log_id' => $commandLog->id,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }
}
