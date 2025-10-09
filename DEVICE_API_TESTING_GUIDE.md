# Device API Testing Guide

## Base URL
```
http://localhost:8000/api
```

---

## 1. Get Device Information

### Endpoint
```
GET /api/devices/{customer_id}
```

### Example Request
```bash
curl -X GET "http://localhost:8000/api/devices/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example Response
```json
{
  "success": true,
  "data": {
    "device": {
      "customer_id": 1,
      "customer_name": "রহিম উদ্দিন",
      "nid_no": "1234567890",
      "mobile": "01712345678",
      "device": {
        "serial_number": "SN123456789",
        "imei_1": "123456789012345",
        "imei_2": "543210987654321",
        "fcm_token": "firebase_token_here",
        "registered": true
      },
      "device_status": {
        "is_locked": false,
        "is_camera_disabled": false,
        "is_bluetooth_disabled": false,
        "is_app_hidden": false,
        "has_password": false,
        "custom_wallpaper_url": null,
        "last_command_sent_at": null
      },
      "product": {
        "type": "Mobile Phone",
        "model": "Samsung Galaxy A54",
        "price": "85000.00"
      },
      "status": "active",
      "can_receive_commands": true
    }
  }
}
```

---

## 2. State-Based Commands

### 2.1 Lock Device
```bash
curl -X POST "http://localhost:8000/api/devices/command/lock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Response:**
```json
{
  "success": true,
  "message": "Device locked successfully",
  "command": "LOCK_DEVICE"
}
```

**Database Update:** `is_device_locked = true`

---

### 2.2 Unlock Device
```bash
curl -X POST "http://localhost:8000/api/devices/command/unlock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `is_device_locked = false`

---

### 2.3 Disable Camera
```bash
curl -X POST "http://localhost:8000/api/devices/command/disable-camera" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `is_camera_disabled = true`

---

### 2.4 Enable Camera
```bash
curl -X POST "http://localhost:8000/api/devices/command/enable-camera" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `is_camera_disabled = false`

---

### 2.5 Disable Bluetooth
```bash
curl -X POST "http://localhost:8000/api/devices/command/disable-bluetooth" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `is_bluetooth_disabled = true`

---

### 2.6 Enable Bluetooth
```bash
curl -X POST "http://localhost:8000/api/devices/command/enable-bluetooth" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `is_bluetooth_disabled = false`

---

### 2.7 Hide App
```bash
curl -X POST "http://localhost:8000/api/devices/command/hide-app" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "package_name": "com.facebook.katana"
  }'
```

**Database Update:** `is_app_hidden = true`

---

### 2.8 Unhide App
```bash
curl -X POST "http://localhost:8000/api/devices/command/unhide-app" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "package_name": "com.facebook.katana"
  }'
```

**Database Update:** `is_app_hidden = false`

---

### 2.9 Reset Password
```bash
curl -X POST "http://localhost:8000/api/devices/command/reset-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "password": "1234"
  }'
```

**Database Update:** `has_password = true`

---

### 2.10 Remove Password
```bash
curl -X POST "http://localhost:8000/api/devices/command/remove-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `has_password = false`

---

### 2.11 Set Wallpaper
```bash
curl -X POST "http://localhost:8000/api/devices/command/set-wallpaper" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "image_url": "https://example.com/wallpapers/payment-reminder.jpg"
  }'
```

**Database Update:** `custom_wallpaper_url = "https://example.com/..."`

---

### 2.12 Remove Wallpaper
```bash
curl -X POST "http://localhost:8000/api/devices/command/remove-wallpaper" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** `custom_wallpaper_url = null`

---

## 3. Action-Based Commands (No Database Update)

### 3.1 Show Message
```bash
curl -X POST "http://localhost:8000/api/devices/command/show-message" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "title": "Payment Reminder",
    "message": "Your EMI payment of ৳5,833.33 is due tomorrow!"
  }'
```

**Database Update:** Only `last_command_sent_at`

---

### 3.2 Reminder Screen
```bash
curl -X POST "http://localhost:8000/api/devices/command/reminder-screen" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "message": "Please pay your EMI to avoid device lock"
  }'
```

**Database Update:** Only `last_command_sent_at`

---

### 3.3 Reminder Audio
```bash
curl -X POST "http://localhost:8000/api/devices/command/reminder-audio" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "audio_url": "https://example.com/audio/reminder-bn.mp3"
  }'
```

**Database Update:** Only `last_command_sent_at`

---

### 3.4 Reboot Device
```bash
curl -X POST "http://localhost:8000/api/devices/command/reboot" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** Only `last_command_sent_at`

---

### 3.5 Remove App
```bash
curl -X POST "http://localhost:8000/api/devices/command/remove-app" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 1,
    "package_name": "com.facebook.katana"
  }'
```

**Database Update:** Only `last_command_sent_at`

---

### 3.6 Wipe Device (Factory Reset)
```bash
curl -X POST "http://localhost:8000/api/devices/command/wipe" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** Only `last_command_sent_at`

⚠️ **Warning:** This will erase all data on the device!

---

### 3.7 Request Location
```bash
curl -X POST "http://localhost:8000/api/devices/command/request-location" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"customer_id": 1}'
```

**Database Update:** Only `last_command_sent_at`

---

## 4. Get Command History

### Endpoint
```
GET /api/devices/{customer_id}/command-history
```

### Example Request
```bash
curl -X GET "http://localhost:8000/api/devices/1/command-history" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example Response
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "id": 1,
        "command": "LOCK_DEVICE",
        "parameters": {},
        "sent_by": {
          "id": 1,
          "name": "Super Admin",
          "role": "super_admin"
        },
        "sent_at": "2025-10-08T14:30:00Z",
        "status": "sent"
      },
      {
        "id": 2,
        "command": "SHOW_MESSAGE",
        "parameters": {
          "title": "Payment Reminder",
          "message": "Your EMI is due"
        },
        "sent_by": {
          "id": 1,
          "name": "Super Admin",
          "role": "super_admin"
        },
        "sent_at": "2025-10-08T15:45:00Z",
        "status": "sent"
      }
    ],
    "total": 2
  }
}
```

---

## 5. Get Available Commands

### Endpoint
```
GET /api/devices/commands
```

### Example Request
```bash
curl -X GET "http://localhost:8000/api/devices/commands" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Example Response
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "command": "lock",
        "method": "POST",
        "endpoint": "/api/devices/command/lock",
        "description": "Lock the device",
        "requires_params": false
      },
      {
        "command": "show-message",
        "method": "POST",
        "endpoint": "/api/devices/command/show-message",
        "description": "Show a message on the device",
        "requires_params": true,
        "params": {
          "title": "optional",
          "message": "required"
        }
      }
    ]
  }
}
```

---

## Testing Sequence

### 1. Complete State Test Flow
```bash
# 1. Get initial state
GET /api/devices/1

# 2. Lock device
POST /api/devices/command/lock
# Verify: is_locked = true

# 3. Get updated state
GET /api/devices/1
# Should show: "is_locked": true

# 4. Unlock device
POST /api/devices/command/unlock
# Verify: is_locked = false

# 5. Get state again
GET /api/devices/1
# Should show: "is_locked": false
```

### 2. Multiple States Test
```bash
# Apply multiple states
POST /api/devices/command/lock
POST /api/devices/command/disable-camera
POST /api/devices/command/disable-bluetooth

# Check all states
GET /api/devices/1
# Should show all three states as true
```

### 3. Action Commands Test
```bash
# Send temporary message
POST /api/devices/command/show-message

# Check state (should be unchanged)
GET /api/devices/1
# device_status should be same, only last_command_sent_at updated
```

---

## Error Responses

### Device Not Found
```json
{
  "success": false,
  "message": "Customer not found",
  "errors": null,
  "status": 404
}
```

### Device Not Registered
```json
{
  "success": false,
  "message": "Device not registered or FCM token missing",
  "errors": null,
  "status": 400
}
```

### Invalid Command
```json
{
  "success": false,
  "message": "Invalid command: invalid-cmd",
  "errors": null,
  "status": 500
}
```

### Missing Required Parameters
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "message": ["The message field is required."]
  },
  "status": 422
}
```

---

## Postman Collection

Import this into Postman for easier testing:

**File:** `postman/collections/device-commands.json`

All endpoints are pre-configured with:
- Authorization headers
- Request bodies
- Examples
- Tests

---

## Summary

### State-Based Commands (12):
Update database and persist across reboots

### Action-Based Commands (7):
Execute once, no persistent state

### Device Info API:
Returns current state from database fields

### All commands:
- Logged in `device_command_logs` table
- Update `last_command_sent_at` timestamp
- Require valid FCM token
- Send Firebase push notification
