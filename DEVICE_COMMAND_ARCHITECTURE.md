# Device Command Architecture Explanation

## Overview
The device control system has **14 commands** but only **6 database fields**. This is by design.

---

## How It Works

### 1. Device Registration Flow
```
Customer Created → Device Registers → FCM Token Saved → Ready to Receive Commands
```

When you call `GET /api/devices/{customer}`, the `DeviceController::show()` method:
1. Takes the Customer model (route model binding)
2. Passes it to `DeviceResource`
3. Returns current device state from database

```php
public function show(Customer $customer): JsonResponse
{
    return $this->success([
        'device' => new DeviceResource($customer),
    ]);
}
```

### 2. DeviceResource Transforms Customer Data

```php
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->id,
            'device' => [
                'serial_number' => $this->serial_number,
                'imei_1' => $this->imei_1,
                'fcm_token' => $this->fcm_token,
            ],
            'device_status' => [
                'is_locked' => $this->is_device_locked,
                'is_camera_disabled' => $this->is_camera_disabled,
                'has_password' => $this->has_password,
                'custom_wallpaper_url' => $this->custom_wallpaper_url,
            ],
        ];
    }
}
```

---

## Command Types

### A. STATE-BASED Commands (Persistent - Need Database Fields)
These states **must survive** app restarts:

| Command | Database Field | Why? |
|---------|---------------|------|
| `LOCK_DEVICE` / `UNLOCK_DEVICE` | `is_device_locked` | Device must know if it's locked on restart |
| `DISABLE_CAMERA` / `ENABLE_CAMERA` | `is_camera_disabled` | Camera state persists across reboots |
| `DISABLE_BLUETOOTH` / `ENABLE_BLUETOOTH` | `is_bluetooth_disabled` | Bluetooth state persists |
| `HIDE_APP` / `UNHIDE_APP` | `is_app_hidden` | App visibility persists |
| `RESET_PASSWORD` / `REMOVE_PASSWORD` | `has_password` | Track if password is set |
| `SET_WALLPAPER` / `REMOVE_WALLPAPER` | `custom_wallpaper_url` | Store wallpaper URL |

**Database Schema:**
```php
$table->boolean('is_device_locked')->default(false);
$table->boolean('is_camera_disabled')->default(false);
$table->boolean('is_bluetooth_disabled')->default(false);
$table->boolean('is_app_hidden')->default(false);
$table->boolean('has_password')->default(false);
$table->string('custom_wallpaper_url')->nullable();
$table->timestamp('last_command_sent_at')->nullable();
```

---

### B. ACTION-BASED Commands (One-Time - No Database Needed)
These execute once and don't need state tracking:

| Command | Why No Database? |
|---------|-----------------|
| `REBOOT_DEVICE` | One-time action, device reboots immediately |
| `SHOW_MESSAGE` | Temporary notification, no persistence needed |
| `REMINDER_SCREEN` | Temporary display, dismissed by user |
| `REMINDER_AUDIO` | Plays once, no state to track |
| `REQUEST_LOCATION` | One-time request, location sent back immediately |
| `REMOVE_APP` | Uninstalls app, action completed, no state |
| `WIPE_DEVICE` | Factory reset, all data erased anyway |

**Example Flow:**
```
Admin clicks "Show Message" 
→ API sends FCM push 
→ Device displays message 
→ Done (no database update needed)
```

---

## Command Execution Flow

### Example: Lock Device

```
1. POST /api/devices/command/lock
   Body: { "customer_id": 123 }

2. DeviceController::sendCommand()
   ↓
3. DeviceCommandService::lockDevice()
   ↓
4. Sends FCM push notification to device
   ↓
5. Updates database: is_device_locked = true
   ↓
6. Logs command in device_command_logs table
   ↓
7. Returns success response
```

### Code Example:
```php
public function lockDevice(Customer $customer, User $user): array
{
    return $this->sendCommand(
        $customer,
        'LOCK_DEVICE',
        [],
        $user,
        fn($token) => $this->firebaseService->lockDevice($token),
        fn($customer) => $customer->update(['is_device_locked' => true])
    );
}
```

---

## Device State Synchronization

### When Device App Starts:
1. Device calls `GET /api/devices/{customer}`
2. Receives current state:
```json
{
  "device_status": {
    "is_locked": true,
    "is_camera_disabled": false,
    "is_bluetooth_disabled": false,
    "is_app_hidden": false,
    "has_password": true,
    "custom_wallpaper_url": "https://example.com/wallpaper.jpg"
  }
}
```
3. Device applies all states from database
4. Locks itself if `is_locked: true`
5. Disables camera if `is_camera_disabled: true`
6. Sets wallpaper if URL exists

---

## Command History Tracking

All commands (both state-based and action-based) are logged:

```php
// device_command_logs table
{
  "customer_id": 123,
  "command": "LOCK_DEVICE",
  "sent_by": 1,
  "sent_at": "2025-10-08 14:30:00",
  "status": "sent"
}
```

This allows:
- Audit trail of all commands
- Command history per device
- Troubleshooting failed commands

---

## Why This Design?

### ✅ Benefits:
1. **Minimal Database Overhead**: Only 6 fields instead of 14
2. **Clear Separation**: State vs Actions
3. **Scalability**: Action-based commands don't bloat database
4. **Real-time**: State changes via FCM push notifications
5. **Persistence**: Critical states survive app/device restarts

### Example Scenario:
```
Device A: Lock command sent at 2:00 PM
Device reboots at 2:05 PM
Device app starts → Checks database → Sees is_locked=true → Locks itself
```

Without database state tracking, device would unlock after reboot! ❌

---

## API Response Examples

### Get Device Info
**Request:** `GET /api/devices/123`

**Response:**
```json
{
  "success": true,
  "data": {
    "device": {
      "customer_id": 123,
      "customer_name": "John Doe",
      "device": {
        "serial_number": "SN123456",
        "imei_1": "123456789012345",
        "fcm_token": "firebase_token_here",
        "registered": true
      },
      "device_status": {
        "is_locked": true,
        "is_camera_disabled": false,
        "is_bluetooth_disabled": false,
        "is_app_hidden": false,
        "has_password": true,
        "custom_wallpaper_url": "https://example.com/wall.jpg",
        "last_command_sent_at": "2025-10-08T14:30:00Z"
      },
      "can_receive_commands": true
    }
  }
}
```

---

## Complete Command Reference

### State-Based (Updates Database)
```php
POST /api/devices/command/lock              → is_device_locked = true
POST /api/devices/command/unlock            → is_device_locked = false
POST /api/devices/command/disable-camera    → is_camera_disabled = true
POST /api/devices/command/enable-camera     → is_camera_disabled = false
POST /api/devices/command/disable-bluetooth → is_bluetooth_disabled = true
POST /api/devices/command/enable-bluetooth  → is_bluetooth_disabled = false
POST /api/devices/command/hide-app          → is_app_hidden = true
POST /api/devices/command/unhide-app        → is_app_hidden = false
POST /api/devices/command/reset-password    → has_password = true
POST /api/devices/command/remove-password   → has_password = false
POST /api/devices/command/set-wallpaper     → custom_wallpaper_url = {url}
POST /api/devices/command/remove-wallpaper  → custom_wallpaper_url = null
```

### Action-Based (No Database Update)
```php
POST /api/devices/command/reboot           → Just sends FCM
POST /api/devices/command/show-message     → Just sends FCM
POST /api/devices/command/reminder-screen  → Just sends FCM
POST /api/devices/command/reminder-audio   → Just sends FCM
POST /api/devices/command/request-location → Just sends FCM
POST /api/devices/command/remove-app       → Just sends FCM
POST /api/devices/command/wipe             → Just sends FCM
```

---

## Summary

### Database Fields (6):
1. `is_device_locked` - Lock state
2. `is_camera_disabled` - Camera state
3. `is_bluetooth_disabled` - Bluetooth state
4. `is_app_hidden` - App visibility
5. `has_password` - Password protection
6. `custom_wallpaper_url` - Wallpaper URL

### Total Commands (14):
- **12 state-based** (6 pairs of enable/disable)
- **7 action-based** (one-time actions)

### How `show()` Works:
1. Route model binding loads Customer
2. DeviceResource transforms Customer to JSON
3. Returns all device states from database fields
4. Device app receives and applies states

This architecture is **efficient, scalable, and maintains critical state persistence**! 🎯
