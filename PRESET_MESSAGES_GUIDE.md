# 📨 Preset Messages Feature Guide

## Overview
The Preset Messages feature allows users to configure automatic messages that are sent to devices when specific commands are executed. This eliminates the need to manually specify messages each time a command is sent, ensuring consistent communication with customers.

---

## ✨ Key Features

- ✅ **Automatic Message Delivery**: Messages automatically send when commands execute
- ✅ **User-Specific Presets**: Each user can configure their own preset messages
- ✅ **18 Supported Commands**: Cover all major device control actions
- ✅ **Active/Inactive Toggle**: Enable or disable presets without deletion
- ✅ **Title & Message**: Customize both title and body content
- ✅ **One Preset Per Command**: Unique constraint ensures clarity

---

## 🎯 How It Works

### Without Preset Messages (Manual):
```http
POST /api/devices/command/lock
{
  "customer_id": 123
}
```
Device gets locked, but **no message is shown** to the customer.

### With Preset Messages (Automatic):
1. User creates preset message for `LOCK_DEVICE` command
2. User executes lock command:
```http
POST /api/devices/command/lock
{
  "customer_id": 123
}
```
3. **Device automatically receives BOTH**:
   - Lock command execution
   - Preset message display

**Result**: Device is locked AND customer sees your preset message like "Your device has been locked due to missed payment. Please contact us."

---

## 📊 Database Schema

### Table: `command_preset_messages`

| Column     | Type         | Description                          |
|------------|--------------|--------------------------------------|
| id         | bigint       | Primary key                          |
| user_id    | bigint       | Foreign key to users                 |
| command    | varchar(255) | Command name (e.g., LOCK_DEVICE)     |
| title      | varchar(255) | Message title (nullable)             |
| message    | text         | Message content                      |
| is_active  | boolean      | Active status (default: true)        |
| created_at | timestamp    | Created timestamp                    |
| updated_at | timestamp    | Updated timestamp                    |

**Unique Constraint**: `user_id` + `command` (one preset per user per command)

### Migration File
- **`2025_10_17_024204_create_command_preset_messages_table.php`**

---

## 🔌 API Endpoints

### 1. Get All Preset Messages
```http
GET /api/preset-messages
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "preset_messages": [
      {
        "id": 1,
        "user_id": 5,
        "command": "LOCK_DEVICE",
        "title": "Payment Reminder",
        "message": "Your device has been locked due to missed payment. Please contact us to resolve.",
        "is_active": true,
        "created_at": "2025-10-17T02:45:00.000000Z",
        "updated_at": "2025-10-17T02:45:00.000000Z"
      }
    ],
    "total": 1
  }
}
```

### 2. Get Available Commands
```http
GET /api/preset-messages/available-commands
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "commands": [
      {
        "command": "LOCK_DEVICE",
        "label": "Lock Device",
        "description": "Message shown when device is locked"
      },
      {
        "command": "UNLOCK_DEVICE",
        "label": "Unlock Device",
        "description": "Message shown when device is unlocked"
      }
      // ... 16 more commands
    ]
  }
}
```

### 3. Create or Update Preset Message
```http
POST /api/preset-messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "command": "LOCK_DEVICE",
  "title": "Payment Reminder",
  "message": "Your device has been locked due to missed payment. Please contact us at 01700000000.",
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Preset message saved successfully",
    "preset_message": {
      "id": 1,
      "user_id": 5,
      "command": "LOCK_DEVICE",
      "title": "Payment Reminder",
      "message": "Your device has been locked due to missed payment. Please contact us at 01700000000.",
      "is_active": true
    }
  }
}
```

### 4. Get Specific Preset Message
```http
GET /api/preset-messages/{id}
Authorization: Bearer {token}
```

### 5. Update Preset Message
```http
PUT /api/preset-messages/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "message": "Updated message content",
  "is_active": true
}
```

### 6. Delete Preset Message
```http
DELETE /api/preset-messages/{id}
Authorization: Bearer {token}
```

### 7. Toggle Active Status
```http
POST /api/preset-messages/{id}/toggle
Authorization: Bearer {token}
```

---

## 🎨 Use Cases & Examples

### Use Case 1: Payment Reminder on Lock
```json
{
  "command": "LOCK_DEVICE",
  "title": "⚠️ Payment Required",
  "message": "Your device has been locked due to missed payment. Please pay your installment to unlock. Contact: 01700000000"
}
```

### Use Case 2: Thank You on Unlock
```json
{
  "command": "UNLOCK_DEVICE",
  "title": "✅ Thank You!",
  "message": "Thank you for your payment! Your device has been unlocked. Next payment due: [Date]"
}
```

### Use Case 3: Camera Restriction Notice
```json
{
  "command": "DISABLE_CAMERA",
  "title": "📷 Camera Restricted",
  "message": "Camera access has been temporarily restricted due to policy violation. Please contact support."
}
```

### Use Case 4: Location Request Notification
```json
{
  "command": "REQUEST_LOCATION",
  "title": "📍 Location Request",
  "message": "We are requesting your device location for verification purposes. This helps us serve you better."
}
```

### Use Case 5: Call Restriction Notice
```json
{
  "command": "DISABLE_CALL",
  "title": "📵 Calls Restricted",
  "message": "Phone calls have been temporarily restricted. Please clear your overdue payments to restore service."
}
```

---

## 🔧 Integration Flow

### DeviceCommandService.php
The `sendCommand()` method automatically:
1. Executes the device command via Firebase
2. Checks if user has active preset message for this command
3. If preset exists, sends it automatically to device
4. Returns response including preset message status

```php
// Automatic preset message check (inside sendCommand method)
if ($result['success']) {
    $presetMessage = CommandPresetMessage::where('user_id', $user->id)
        ->where('command', $command)
        ->where('is_active', true)
        ->first();

    if ($presetMessage) {
        $messageResult = $this->firebaseService->showMessage(
            $customer->fcm_token,
            $presetMessage->message,
            $presetMessage->title ?? 'Notification'
        );

        $result['preset_message_sent'] = $messageResult['success'];
        $result['preset_message'] = [
            'title' => $presetMessage->title,
            'message' => $presetMessage->message,
        ];
    }
}
```

### Command Response Format
When command executes with preset message:
```json
{
  "success": true,
  "command": "LOCK_DEVICE",
  "log_id": 456,
  "message": "Command sent successfully",
  "details": {
    "success": true,
    "preset_message_sent": true,
    "preset_message": {
      "title": "Payment Reminder",
      "message": "Your device has been locked..."
    }
  }
}
```

---

## 🎯 Supported Commands (18 Total)

| Command             | Label              | Use Case                                |
|---------------------|--------------------|-----------------------------------------|
| LOCK_DEVICE         | Lock Device        | Payment reminder, security enforcement  |
| UNLOCK_DEVICE       | Unlock Device      | Thank you message, payment confirmation |
| DISABLE_CAMERA      | Disable Camera     | Policy violation notice                 |
| ENABLE_CAMERA       | Enable Camera      | Restriction lifted notification         |
| DISABLE_BLUETOOTH   | Disable Bluetooth  | Security restriction notice             |
| ENABLE_BLUETOOTH    | Enable Bluetooth   | Restriction lifted notification         |
| HIDE_APP            | Hide App           | App management notification             |
| UNHIDE_APP          | Unhide App         | App restored notification               |
| RESET_PASSWORD      | Reset Password     | New password information                |
| REMOVE_PASSWORD     | Remove Password    | Password removed notification           |
| REBOOT_DEVICE       | Reboot Device      | Reboot reason explanation               |
| REMOVE_APP          | Remove App         | App removal notice                      |
| WIPE_DEVICE         | Wipe Device        | Factory reset warning                   |
| SET_WALLPAPER       | Set Wallpaper      | Wallpaper change notice                 |
| REMOVE_WALLPAPER    | Remove Wallpaper   | Wallpaper reset notice                  |
| REQUEST_LOCATION    | Request Location   | Location verification notice            |
| ENABLE_CALL         | Enable Call        | Call service restored                   |
| DISABLE_CALL        | Disable Call       | Call restriction notice                 |

---

## 💡 Best Practices

### 1. **Clear & Concise Messages**
- Keep messages under 200 characters
- Include contact information
- Specify next action required

### 2. **Professional Tone**
- Use polite language
- Avoid threatening tone
- Be helpful and supportive

### 3. **Actionable Information**
- Tell customer what to do next
- Provide contact details
- Include payment methods/deadlines

### 4. **Consistent Branding**
- Use company name
- Include logo/emoji for recognition
- Maintain consistent messaging style

### 5. **Active Management**
- Review and update messages regularly
- Disable outdated presets
- Test messages before deployment

---

## 🔒 Security & Authorization

### User Isolation
- Each user can only view/edit their own preset messages
- Automatic `user_id` assignment on creation
- Authorization checks on all update/delete operations

### Controller Middleware
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('preset-messages')->group(function () {
        // All endpoints require authentication
    });
});
```

### Authorization Logic
```php
if ($presetMessage->user_id !== request()->user()->id) {
    return $this->error('Unauthorized', null, 403);
}
```

---

## 🧪 Testing

### Factory Example
```php
// Create preset message for testing
CommandPresetMessage::factory()->create([
    'user_id' => $user->id,
    'command' => 'LOCK_DEVICE',
    'title' => 'Test Title',
    'message' => 'Test message content',
    'is_active' => true,
]);

// Create inactive preset
CommandPresetMessage::factory()->inactive()->create();

// Create for specific command
CommandPresetMessage::factory()
    ->forCommand('UNLOCK_DEVICE')
    ->create();
```

### Test Scenario
```php
it('automatically sends preset message when command executes', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    
    // Create preset message
    CommandPresetMessage::factory()->create([
        'user_id' => $user->id,
        'command' => 'LOCK_DEVICE',
        'message' => 'Device locked due to payment',
    ]);
    
    // Execute command
    $service = new DeviceCommandService(new FirebaseService());
    $result = $service->lockDevice($customer, $user);
    
    // Verify preset message was sent
    expect($result['details']['preset_message_sent'])->toBeTrue();
    expect($result['details']['preset_message']['message'])
        ->toBe('Device locked due to payment');
});
```

---

## 📝 Frontend Integration Example

### React/Vue Component
```javascript
// Fetch preset messages
const fetchPresetMessages = async () => {
  const response = await axios.get('/api/preset-messages', {
    headers: { Authorization: `Bearer ${token}` }
  });
  return response.data.data.preset_messages;
};

// Create/Update preset
const savePresetMessage = async (data) => {
  const response = await axios.post('/api/preset-messages', {
    command: data.command,
    title: data.title,
    message: data.message,
    is_active: true
  }, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return response.data;
};

// Toggle active status
const togglePreset = async (id) => {
  const response = await axios.post(`/api/preset-messages/${id}/toggle`, {}, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return response.data;
};
```

---

## 🚀 Quick Start

1. **Run Migration** (when database is available):
```bash
php artisan migrate
```

2. **Create Your First Preset**:
```bash
POST /api/preset-messages
{
  "command": "LOCK_DEVICE",
  "title": "Payment Required",
  "message": "Please pay your installment to unlock device.",
  "is_active": true
}
```

3. **Execute Command**:
```bash
POST /api/devices/command/lock
{
  "customer_id": 123
}
```

4. **Verify**: Check response for `preset_message_sent: true`

---

## 📞 Support

For questions or issues with preset messages:
- Check API response for error details
- Verify user has active preset for the command
- Ensure Firebase connection is working
- Review device command logs for delivery status

---

**Last Updated**: October 17, 2025  
**Version**: 1.0.0  
**Laravel Version**: 12
