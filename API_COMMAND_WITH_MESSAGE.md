# Device Command with Message Display API

## 🎯 Purpose

This API allows you to **trigger a device command** (like lock, unlock, disable camera, etc.) **AND automatically show a custom message** on the device screen after the command executes.

Perfect for showing user-friendly notifications like:
- "Device locked due to missed payment"
- "Camera disabled temporarily"
- "Your EMI payment is due tomorrow"

---

## 📡 API Endpoint

```
POST /api/devices/command-with-message/{command}
```

### Authentication
```
Authorization: Bearer {token}
```

---

## 🔧 Request Parameters

### URL Parameter
- `{command}` - The device command to execute (see available commands below)

### Request Body (JSON)

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `customer_id` | integer | ✅ Yes | Customer ID whose device will receive command |
| `display_message` | string | ✅ Yes | Message to show on device screen |
| `display_title` | string | ❌ No | Title for the message (default: "Notification") |
| `password` | string | ⚠️ Conditional | Required for `reset-password` command |
| `package_name` | string | ⚠️ Conditional | Required for app-related commands |
| `image_url` | string | ⚠️ Conditional | Required for `set-wallpaper` command |

---

## 📋 Available Commands

### State-Based Commands (Persist State)

| Command | Description | Extra Params |
|---------|-------------|--------------|
| `lock` | Lock the device | None |
| `unlock` | Unlock the device | None |
| `disable-camera` | Disable device camera | None |
| `enable-camera` | Enable device camera | None |
| `disable-bluetooth` | Disable bluetooth | None |
| `enable-bluetooth` | Enable bluetooth | None |
| `hide-app` | Hide an app | `package_name` (optional) |
| `unhide-app` | Unhide an app | `package_name` (optional) |
| `reset-password` | Set device password | `password` (required) |
| `remove-password` | Remove device password | None |
| `set-wallpaper` | Set custom wallpaper | `image_url` (required) |
| `remove-wallpaper` | Remove custom wallpaper | None |

### Action-Based Commands (One-Time Action)

| Command | Description | Extra Params |
|---------|-------------|--------------|
| `reboot` | Reboot the device | None |
| `remove-app` | Uninstall an app | `package_name` (required) |
| `wipe` | Factory reset device | None |
| `request-location` | Get device location | None |

---

## 📝 Request Examples

### Example 1: Lock Device with Message

```bash
curl -X POST "http://your-api.com/api/devices/command-with-message/lock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "display_message": "Your device has been locked due to missed EMI payment. Please contact us to unlock.",
    "display_title": "Device Locked"
  }'
```

### Example 2: Disable Camera with Message

```bash
curl -X POST "http://your-api.com/api/devices/command-with-message/disable-camera" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "display_message": "Camera has been temporarily disabled for security reasons.",
    "display_title": "Camera Disabled"
  }'
```

### Example 3: Reset Password with Message

```bash
curl -X POST "http://your-api.com/api/devices/command-with-message/reset-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "password": "1234",
    "display_message": "Your device password has been reset to: 1234",
    "display_title": "Password Reset"
  }'
```

### Example 4: Unlock Device with Message

```bash
curl -X POST "http://your-api.com/api/devices/command-with-message/unlock" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "display_message": "Thank you for your payment! Your device has been unlocked.",
    "display_title": "Device Unlocked"
  }'
```

### Example 5: Reboot with Message

```bash
curl -X POST "http://your-api.com/api/devices/command-with-message/reboot" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer_id": 123,
    "display_message": "Your device will reboot now to apply updates.",
    "display_title": "System Reboot"
  }'
```

---

## ✅ Success Response

```json
{
  "success": true,
  "data": {
    "success": true,
    "message": "Device locked successfully",
    "command": "LOCK_DEVICE",
    "customer_id": 123,
    "sent_at": "2025-10-09T14:30:00Z",
    "message_sent": true,
    "display_message": "Your device has been locked due to missed EMI payment.",
    "display_title": "Device Locked"
  }
}
```

### Response Fields Explained

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Overall request success status |
| `data.success` | boolean | Command execution success |
| `data.message` | string | Success message from command |
| `data.command` | string | Command that was executed |
| `data.customer_id` | integer | Customer whose device received command |
| `data.sent_at` | string | Timestamp when command was sent |
| `data.message_sent` | boolean | Whether display message was sent successfully |
| `data.display_message` | string | The message shown on device |
| `data.display_title` | string | The title shown on device |

---

## ❌ Error Responses

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
    "display_message": ["The display message field is required."],
    "password": ["The password field is required when command is reset-password."]
  },
  "status": 422
}
```

---

## 🔄 How It Works (Backend Flow)

```
1. API receives request
   ↓
2. Validates customer_id and command
   ↓
3. Executes primary command (lock, unlock, etc.)
   ↓
4. If command succeeds AND display_message is provided:
   ↓
5. Sends SHOW_MESSAGE command via FCM
   ↓
6. Device receives both commands:
   - First: Execute primary command
   - Second: Show message notification
   ↓
7. Returns combined response
```

---

## 📱 Mobile App Integration

### For Android Developers

Your Android app will receive **2 FCM push notifications**:

#### 1. Primary Command Notification
```json
{
  "command": "LOCK_DEVICE",
  "customer_id": 123,
  "timestamp": "2025-10-09T14:30:00Z"
}
```

#### 2. Message Display Notification
```json
{
  "command": "SHOW_MESSAGE",
  "customer_id": 123,
  "title": "Device Locked",
  "message": "Your device has been locked due to missed EMI payment.",
  "timestamp": "2025-10-09T14:30:01Z"
}
```

### Handling in Android App

```kotlin
// In your FirebaseMessagingService
override fun onMessageReceived(remoteMessage: RemoteMessage) {
    val command = remoteMessage.data["command"]
    
    when (command) {
        "LOCK_DEVICE" -> {
            // Lock the device
            deviceManager.lockDevice()
        }
        "SHOW_MESSAGE" -> {
            // Show notification/dialog
            val title = remoteMessage.data["title"]
            val message = remoteMessage.data["message"]
            showNotification(title, message)
        }
        // ... other commands
    }
}
```

---

## 🆚 Comparison: Regular Command vs Command with Message

### Regular Command Endpoint
```
POST /api/devices/command/lock
{
  "customer_id": 123
}

Result:
✓ Device locked
✗ No message shown to user
```

### Command with Message Endpoint
```
POST /api/devices/command-with-message/lock
{
  "customer_id": 123,
  "display_message": "Device locked due to missed payment",
  "display_title": "Device Locked"
}

Result:
✓ Device locked
✓ Message shown to user
```

---

## 🎨 Use Cases

### 1. Payment Reminder with Lock
```json
{
  "customer_id": 123,
  "display_message": "আপনার EMI পেমেন্ট বকেয়া। দয়া করে অবিলম্বে পরিশোধ করুন। Your device will remain locked until payment.",
  "display_title": "পেমেন্ট বকেয়া / Payment Due"
}
```

### 2. Thank You Message with Unlock
```json
{
  "customer_id": 123,
  "display_message": "ধন্যবাদ! আপনার পেমেন্ট সফল হয়েছে। Thank you! Your payment was successful. Device unlocked.",
  "display_title": "পেমেন্ট সফল / Payment Success"
}
```

### 3. Security Alert with Camera Disable
```json
{
  "customer_id": 123,
  "display_message": "Camera temporarily disabled for security audit. Will be restored within 24 hours.",
  "display_title": "Security Alert"
}
```

### 4. Warning Before Wipe
```json
{
  "customer_id": 123,
  "display_message": "WARNING: All data will be erased. Please backup immediately. Device will wipe in 5 minutes.",
  "display_title": "⚠️ Critical Warning"
}
```

---

## 🧪 Testing with Postman

### Step 1: Setup Environment
```
Variable: api_url
Value: http://localhost:8000/api

Variable: token
Value: YOUR_AUTH_TOKEN
```

### Step 2: Create Request
```
Method: POST
URL: {{api_url}}/devices/command-with-message/lock
Headers:
  Authorization: Bearer {{token}}
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
  "customer_id": 1,
  "display_message": "Test message from API",
  "display_title": "Test Notification"
}
```

### Step 3: Send and Verify
- Check response status: 200 OK
- Verify `message_sent: true`
- Check device for notification

---

## 🔐 Security Considerations

### Rate Limiting
- Max 60 commands per minute per user
- Max 10 wipe commands per hour (security)

### Authorization
- User must have permission to control the device
- Super Admin can control any device
- Dealers/Sub-Dealers/Salesmen can only control their customers

### Message Content
- Max message length: 500 characters
- Max title length: 100 characters
- HTML tags are stripped for security
- Emoji support: ✅ Yes

---

## 📊 Command Statistics

Track command usage:
```
GET /api/devices/{customer_id}/history
```

Returns all commands sent to device with timestamps and results.

---

## 🌐 Supported Languages

Messages support:
- ✅ English
- ✅ বাংলা (Bangla/Bengali)
- ✅ Emoji 😊 🔒 ⚠️ ✅
- ✅ Numbers: ০১২৩৪৫৬৭৮৯

---

## 💡 Pro Tips

### 1. Clear Messages
```
❌ Bad: "Locked"
✅ Good: "Your device has been locked due to missed EMI payment. Please contact 01712-345678 to unlock."
```

### 2. Bilingual Messages
```
✅ "পেমেন্ট বকেয়া / Payment Due - Please pay ৳5,833 by tomorrow"
```

### 3. Action-Oriented
```
✅ "Device locked. Pay now: 01712-345678 or visit our office at Mirpur-10"
```

### 4. Urgency Levels
```
Low: "Reminder: EMI due in 3 days"
Medium: "Warning: EMI overdue by 2 days"
High: "⚠️ URGENT: Device will be locked in 24 hours"
```

---

## 📞 Support

For API issues or questions, contact:
- Email: support@emimanager.com
- Phone: +880 1712-XXXXXX
- Documentation: https://docs.emimanager.com

---

## 🎉 Summary

✅ **One API call** does two things:
1. Executes device command
2. Shows custom message

✅ **Simple integration** - just add 2 extra fields:
- `display_message`
- `display_title` (optional)

✅ **Better user experience** - customers know WHY action happened

✅ **All commands supported** - works with lock, unlock, camera, bluetooth, etc.

🚀 **Start using it today!**
