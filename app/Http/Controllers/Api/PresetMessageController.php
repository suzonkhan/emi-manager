<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommandPresetMessage;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresetMessageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all preset messages for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $presetMessages = CommandPresetMessage::where('user_id', $request->user()->id)
            ->orderBy('command')
            ->get();

        return $this->success([
            'preset_messages' => $presetMessages,
            'total' => $presetMessages->count(),
        ]);
    }

    /**
     * Get available commands for preset messages
     */
    public function availableCommands(): JsonResponse
    {
        return $this->success([
            'commands' => [
                ['command' => 'LOCK_DEVICE', 'label' => 'Lock Device', 'description' => 'Message shown when device is locked'],
                ['command' => 'UNLOCK_DEVICE', 'label' => 'Unlock Device', 'description' => 'Message shown when device is unlocked'],
                ['command' => 'DISABLE_CAMERA', 'label' => 'Disable Camera', 'description' => 'Message shown when camera is disabled'],
                ['command' => 'ENABLE_CAMERA', 'label' => 'Enable Camera', 'description' => 'Message shown when camera is enabled'],
                ['command' => 'DISABLE_BLUETOOTH', 'label' => 'Disable Bluetooth', 'description' => 'Message shown when bluetooth is disabled'],
                ['command' => 'ENABLE_BLUETOOTH', 'label' => 'Enable Bluetooth', 'description' => 'Message shown when bluetooth is enabled'],
                ['command' => 'HIDE_APP', 'label' => 'Hide App', 'description' => 'Message shown when app is hidden'],
                ['command' => 'UNHIDE_APP', 'label' => 'Unhide App', 'description' => 'Message shown when app is unhidden'],
                ['command' => 'RESET_PASSWORD', 'label' => 'Reset Password', 'description' => 'Message shown when password is reset'],
                ['command' => 'REMOVE_PASSWORD', 'label' => 'Remove Password', 'description' => 'Message shown when password is removed'],
                ['command' => 'REBOOT_DEVICE', 'label' => 'Reboot Device', 'description' => 'Message shown before device reboots'],
                ['command' => 'REMOVE_APP', 'label' => 'Remove App', 'description' => 'Message shown when app is removed'],
                ['command' => 'WIPE_DEVICE', 'label' => 'Wipe Device', 'description' => 'Message shown before device wipe'],
                ['command' => 'SET_WALLPAPER', 'label' => 'Set Wallpaper', 'description' => 'Message shown when wallpaper is set'],
                ['command' => 'REMOVE_WALLPAPER', 'label' => 'Remove Wallpaper', 'description' => 'Message shown when wallpaper is removed'],
                ['command' => 'REQUEST_LOCATION', 'label' => 'Request Location', 'description' => 'Message shown when location is requested'],
                ['command' => 'ENABLE_CALL', 'label' => 'Enable Call', 'description' => 'Message shown when calls are enabled'],
                ['command' => 'DISABLE_CALL', 'label' => 'Disable Call', 'description' => 'Message shown when calls are disabled'],
            ],
        ]);
    }

    /**
     * Create or update preset message for a command
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'command' => 'required|string|max:255',
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'is_active' => 'boolean',
            ]);

            $presetMessage = CommandPresetMessage::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'command' => $validated['command'],
                ],
                [
                    'title' => $validated['title'] ?? null,
                    'message' => $validated['message'],
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );

            return $this->success([
                'message' => 'Preset message saved successfully',
                'preset_message' => $presetMessage,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get specific preset message
     */
    public function show(CommandPresetMessage $presetMessage): JsonResponse
    {
        // Ensure user can only view their own preset messages
        if ($presetMessage->user_id !== request()->user()->id) {
            return $this->error('Unauthorized', null, 403);
        }

        return $this->success([
            'preset_message' => $presetMessage,
        ]);
    }

    /**
     * Update preset message
     */
    public function update(Request $request, CommandPresetMessage $presetMessage): JsonResponse
    {
        try {
            // Ensure user can only update their own preset messages
            if ($presetMessage->user_id !== $request->user()->id) {
                return $this->error('Unauthorized', null, 403);
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'is_active' => 'boolean',
            ]);

            $presetMessage->update($validated);

            return $this->success([
                'message' => 'Preset message updated successfully',
                'preset_message' => $presetMessage->fresh(),
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete preset message
     */
    public function destroy(CommandPresetMessage $presetMessage): JsonResponse
    {
        try {
            // Ensure user can only delete their own preset messages
            if ($presetMessage->user_id !== request()->user()->id) {
                return $this->error('Unauthorized', null, 403);
            }

            $presetMessage->delete();

            return $this->success([
                'message' => 'Preset message deleted successfully',
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Toggle preset message active status
     */
    public function toggle(CommandPresetMessage $presetMessage): JsonResponse
    {
        try {
            // Ensure user can only toggle their own preset messages
            if ($presetMessage->user_id !== request()->user()->id) {
                return $this->error('Unauthorized', null, 403);
            }

            $presetMessage->update([
                'is_active' => ! $presetMessage->is_active,
            ]);

            return $this->success([
                'message' => 'Preset message status toggled successfully',
                'preset_message' => $presetMessage->fresh(),
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 500);
        }
    }
}
