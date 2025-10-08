# Device Control System Implementation

## Overview
A comprehensive device control system has been implemented for the EMI Manager application. This system enables remote management of Android devices using Firebase Cloud Messaging (FCM), allowing you to lock/unlock devices, control camera, bluetooth, show messages, and more.

## What Has Been Implemented

### 1. Database Migrations

#### Updated Customers Table Migration
The `customers` table has been enhanced with the following device-related columns:
- `serial_number` - Unique device serial number
- `fcm_token` - Firebase Cloud Messaging token for push notifications
- `imei_1` - Primary IMEI number
- `imei_2` - Secondary IMEI number (optional)
- `is_device_locked` - Boolean flag for device lock status
- `is_camera_disabled` - Boolean flag for camera status
- `is_bluetooth_disabled` - Boolean flag for bluetooth status
- `is_app_hidden` - Boolean flag for app visibility
- `has_password` - Boolean flag for password status
- `last_command_sent_at` - Timestamp of last command sent

#### Device Command Logs Table
A new `device_command_logs` table tracks all commands sent to devices:
- `customer_id` - Foreign key to customers table
- `command` - Command name (e.g., LOCK_DEVICE, UNLOCK_DEVICE)
- `command_data` - JSON field for additional command parameters
- `status` - Command status (pending, sent, delivered, failed)
- `fcm_response` - FCM API response data
- `error_message` - Error details if command failed
- `sent_at` - Timestamp when command was sent
- `sent_by` - Foreign key to users table (who sent the command)

### 2. Models

#### Customer Model Enhancements
- Added device-related fillable fields
- Added casts for boolean flags and timestamps
- Added `hasDevice()` method - checks if device is registered
- Added `canReceiveCommands()` method - checks if device can receive commands
- Added `deviceCommandLogs()` relationship

#### DeviceCommandLog Model
- New model for tracking device commands
- Relationships to Customer and User (sentBy)
- JSON casting for command_data

### 3. Services

#### FirebaseService (`app/Services/FirebaseService.php`)
Handles all Firebase Cloud Messaging operations:
- `sendDataMessage()` - Generic FCM data message sender
- `lockDevice()` / `unlockDevice()`
- `disableCamera()` / `enableCamera()`
- `disableBluetooth()` / `enableBluetooth()`
- `hideApp()` / `unhideApp()`
- `resetPassword()` / `removePassword()`
- `rebootDevice()`
- `removeApp()`
- `wipeDevice()`
- `showMessage()`
- `showReminderScreen()`
- `playReminderAudio()`
- `setWallpaper()` / `removeWallpaper()`
- `requestLocation()`
- `validateToken()`

#### DeviceCommandService (`app/Services/DeviceCommandService.php`)
Orchestrates device commands with database logging:
- `registerDevice()` - Register device for customer
- All device control methods (lock, unlock, camera, etc.)
- `getCommandHistory()` - Get command logs for a customer
- Automatic command logging with status tracking
- Updates customer device state flags after successful commands

### 4. API Endpoints

#### Device Registration (Public Endpoint - No Auth Required)
```
POST /api/devices/register
```
**Note:** This endpoint is public because it needs to be called automatically when the hidden app is installed on the device, before any user authentication is available.

**How it works:** The device sends its serial number, IMEI1, and FCM token. The system finds the customer by matching the IMEI1 (which was already stored during customer creation).

Payload:
```json
{
  "serial_number": "R2Q5X08F00Y",
  "imei1": "356740000000000",
  "fcm_token": "fcm_token_string_xxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

#### Protected Endpoints (Require Authentication)
All other endpoints are protected with `auth:sanctum` middleware.

#### Get Device Information
```
GET /api/devices/{customer_id}
```

#### Get Command History
```
GET /api/devices/{customer_id}/history
```

#### List Available Commands
```
GET /api/devices/commands
```

#### Send Device Commands
```
POST /api/devices/command/{command}
```

Available commands:
- `lock` - Lock the device
- `unlock` - Unlock the device
- `disable-camera` - Disable device camera
- `enable-camera` - Enable device camera
- `disable-bluetooth` - Disable bluetooth
- `enable-bluetooth` - Enable bluetooth
- `hide-app` - Hide an app (optional: package_name)
- `unhide-app` - Unhide an app (optional: package_name)
- `reset-password` - Reset device password (required: password)
- `remove-password` - Remove device password
- `reboot` - Reboot the device
- `remove-app` - Remove an app (required: package_name)
- `wipe` - Factory reset the device
- `show-message` - Show message on device (required: message, optional: title)
- `reminder-screen` - Show reminder screen (required: message)
- `reminder-audio` - Play reminder audio (optional: audio_url)
- `set-wallpaper` - Set wallpaper (required: image_url)
- `remove-wallpaper` - Remove wallpaper
- `request-location` - Request device location

**Command Payload Structure:**
```json
{
  "customer_id": 1,
  "password": "1234",          // For reset-password
  "package_name": "com.app",   // For hide-app, unhide-app, remove-app
  "message": "Payment due",    // For show-message, reminder-screen
  "title": "Reminder",         // For show-message (optional)
  "audio_url": "https://...",  // For reminder-audio (optional)
  "image_url": "https://..."   // For set-wallpaper
}
```

### 5. Form Requests

#### DeviceRegisterRequest
Validates device registration:
- `nid_no` - Required, must exist in customers table
- `serial_number` - Required string
- `imei1` - Required string
- `fcm_token` - Required string

#### DeviceCommandRequest
Validates device commands with command-specific rules:
- Base validation: `customer_id` required
- Command-specific validation for parameters

### 6. API Resources

#### DeviceResource
Returns comprehensive device information:
- Customer details (id, name, nid_no, mobile)
- Device info (serial_number, imei, fcm_token, registered status)
- Device status flags (locked, camera, bluetooth, app, password)
- Product information
- Can receive commands flag

#### DeviceCommandLogResource
Returns command log information:
- Command details and data
- Status and error messages
- Sent by user information
- Timestamps

### 7. Firebase Configuration

#### Configuration File
Created `config/firebase.php` with settings for:
- Firebase credentials path
- Project ID
- Database URL (optional)
- Storage bucket (optional)

#### Service Account Credentials
Stored at: `storage/app/firebase/ime-locker-app-credentials.json`

**Important:** The credentials are stored in the repository for convenience. In production, you should:
1. Add this path to `.gitignore`
2. Store credentials securely using environment variables or secure vault
3. Update the path in `.env`: `FIREBASE_CREDENTIALS=path/to/credentials.json`

### 8. Packages Installed

- `kreait/firebase-php` v7.22.0 - Firebase Admin SDK for PHP

## Setup Instructions

### 1. Environment Configuration

Add to your `.env` file:
```env
FIREBASE_CREDENTIALS="${PWD}/storage/app/firebase/ime-locker-app-credentials.json"
FIREBASE_PROJECT_ID=ime-locker-app
```

### 2. Run Migrations

Start your database server (MySQL/MariaDB), then run:
```bash
php artisan migrate
```

This will:
- Add device columns to the `customers` table
- Create the `device_command_logs` table

### 3. Test the Implementation

#### 3.1 Register a Device
```bash
curl -X POST http://your-domain.com/api/devices/register \
  -H "Content-Type: application/json" \
  -d '{
    "serial_number": "R2Q5X08F00Y",
    "imei1": "356740000000000",
    "fcm_token": "YOUR_FCM_TOKEN"
  }'
```

#### 3.2 Send a Lock Command
```bash
curl -X POST http://your-domain.com/api/devices/command/lock \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1
  }'
```

#### 3.3 Show a Message on Device
```bash
curl -X POST http://your-domain.com/api/devices/command/show-message \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "title": "Payment Reminder",
    "message": "Your EMI payment of $100 is due tomorrow."
  }'
```

#### 3.4 Get Command History
```bash
curl -X GET http://your-domain.com/api/devices/1/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## FCM Message Format

All commands are sent as FCM data messages with this structure:

```json
{
  "token": "<FCM_DEVICE_TOKEN>",
  "android": { "priority": "high" },
  "data": {
    "command": "LOCK_DEVICE",
    "state": "true"
  }
}
```

## Android App Integration

Your Android app should:

1. **Register FCM token** with the server after installation
2. **Listen for FCM data messages** in a FirebaseMessagingService
3. **Process commands** based on the `command` field
4. **Send acknowledgments** back to server (optional)

### Example Android FCM Handler

```kotlin
class MyFirebaseMessagingService : FirebaseMessagingService() {
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        val command = remoteMessage.data["command"]
        val state = remoteMessage.data["state"]
        
        when (command) {
            "LOCK_DEVICE" -> lockDevice()
            "UNLOCK_DEVICE" -> unlockDevice()
            "DISABLE_CAMERA" -> disableCamera()
            "SHOW_MESSAGE" -> showMessage(
                remoteMessage.data["title"],
                remoteMessage.data["message"]
            )
            // ... handle other commands
        }
    }
    
    override fun onNewToken(token: String) {
        // Send token to your server
        sendTokenToServer(token)
    }
}
```

## Security Considerations

1. **Device Registration Security**: 
   - The registration endpoint is public but validates that the `imei1` exists in the database
   - Only customers with pre-registered IMEI can register devices
   - The IMEI1 must be entered during customer creation in the system
   - Consider adding device signature verification or secret key validation
   - Consider rate limiting to prevent abuse
   
2. **Authentication Required**: All control endpoints require Sanctum authentication
3. **Permission-based Access**: Consider adding permission checks for destructive commands (wipe, remove-app)
4. **Rate Limiting**: Consider adding rate limiting to prevent command spam
5. **Command Validation**: All commands are validated and logged
6. **FCM Token Security**: Store FCM tokens securely, don't expose in public APIs
7. **Firebase Credentials**: Move credentials to secure storage in production

### Recommended: Add API Key Authentication for Device Registration

To better secure the public registration endpoint, consider adding an API key that only the Android app knows:

1. **Add to `.env`:**
```env
DEVICE_REGISTRATION_API_KEY=your-secret-key-here
```

2. **Update DeviceRegisterRequest validation:**
```php
public function rules(): array
{
    return [
        'api_key' => ['required', 'string', Rule::in([config('app.device_api_key')])],
        'serial_number' => ['required', 'string', 'max:255'],
        'imei1' => ['required', 'string', 'max:255'],
        'fcm_token' => ['required', 'string'],
    ];
}
```

3. **Android app sends API key:**
```json
{
  "api_key": "your-secret-key-here",
  "serial_number": "R2Q5X08F00Y",
  "imei1": "356740000000000",
  "fcm_token": "fcm_token_string"
}
```

## Monitoring & Logging

All commands are logged in the `device_command_logs` table with:
- Command name and parameters
- Status (pending, sent, delivered, failed)
- FCM response data
- Error messages
- User who sent the command
- Timestamp

## Troubleshooting

### Common Issues

1. **FCM token invalid**
   - Ensure the Android app is sending a valid FCM token
   - Token may expire - implement token refresh logic

2. **Command not received on device**
   - Check FCM response in command logs
   - Verify device has internet connection
   - Ensure Android app has proper FCM implementation

3. **Firebase connection error**
   - Verify credentials file exists and is valid
   - Check Firebase project ID matches
   - Ensure service account has FCM permissions

4. **Database connection refused**
   - Start your MySQL/MariaDB server
   - Verify database credentials in `.env`
   - Check if migrations have been run

## Next Steps

1. **Run Migrations** - Start database and run `php artisan migrate`
2. **Test Endpoints** - Use Postman or curl to test the API
3. **Integrate Android App** - Implement FCM message handling
4. **Add Permissions** - Implement role-based access control for sensitive commands
5. **Add Tests** - Create feature tests for device commands
6. **Documentation** - Update API documentation with new endpoints

## Files Modified/Created

### Modified Files
- `database/migrations/2025_09_14_224204_create_customers_table.php`
- `app/Models/Customer.php`
- `routes/api.php`

### New Files Created
1. **Migrations**
   - `database/migrations/2025_10_08_164033_create_device_command_logs_table.php`

2. **Models**
   - `app/Models/DeviceCommandLog.php`

3. **Services**
   - `app/Services/FirebaseService.php`
   - `app/Services/DeviceCommandService.php`

4. **Controllers**
   - `app/Http/Controllers/Api/DeviceController.php`

5. **Form Requests**
   - `app/Http/Requests/Api/DeviceRegisterRequest.php`
   - `app/Http/Requests/Api/DeviceCommandRequest.php`

6. **Resources**
   - `app/Http/Resources/DeviceResource.php`
   - `app/Http/Resources/DeviceCommandLogResource.php`

7. **Configuration**
   - `config/firebase.php`
   - `storage/app/firebase/ime-locker-app-credentials.json`

## API Response Examples

### Device Registration Success
```json
{
  "success": true,
  "data": {
    "message": "Device registered successfully",
    "device": {
      "customer_id": 1,
      "customer_name": "John Doe",
      "nid_no": "123456789",
      "mobile": "01712345678",
      "device": {
        "serial_number": "R2Q5X08F00Y",
        "imei_1": "356740000000000",
        "imei_2": null,
        "fcm_token": "fcm_token_string",
        "registered": true
      },
      "device_status": {
        "is_locked": false,
        "is_camera_disabled": false,
        "is_bluetooth_disabled": false,
        "is_app_hidden": false,
        "has_password": false,
        "last_command_sent_at": null
      },
      "can_receive_commands": true
    }
  }
}
```

### Device Registration Error (IMEI Not Found)
```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Customer].",
  "code": 500
}
```

### Command Success Response
```json
{
  "success": true,
  "data": {
    "success": true,
    "command": "LOCK_DEVICE",
    "log_id": 1,
    "message": "Command sent successfully",
    "details": {
      "success": true,
      "message_id": "projects/ime-locker-app/messages/0:1234567890",
      "response": "..."
    }
  }
}
```

### Command History Response
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "id": 1,
        "command": "LOCK_DEVICE",
        "command_data": {"state": "true"},
        "status": "sent",
        "error_message": null,
        "sent_at": "2025-10-08T16:30:00Z",
        "sent_by": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com"
        },
        "created_at": "2025-10-08T16:30:00Z"
      }
    ],
    "total": 1
  }
}
```

---

**Implementation completed successfully!** ✅

All device control functionality has been implemented with proper validation, logging, and error handling. The system is ready for testing once the database is available.
