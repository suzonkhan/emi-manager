# 📮 Postman Collection Updated - Preset Messages Endpoints

## ✅ Update Complete

The Postman collection has been successfully updated with **10 new preset message endpoints** with complete request bodies and documentation.

---

## 📦 What Was Added

### New Endpoints (10)

| # | Endpoint | Method | Description |
|---|----------|--------|-------------|
| 1 | `/api/preset-messages` | GET | Get all preset messages for authenticated user |
| 2 | `/api/preset-messages/available-commands` | GET | Get list of 18 commands that support presets |
| 3 | `/api/preset-messages` | POST | Create preset for LOCK_DEVICE command |
| 4 | `/api/preset-messages` | POST | Create preset for UNLOCK_DEVICE command |
| 5 | `/api/preset-messages` | POST | Create preset for DISABLE_CALL command |
| 6 | `/api/preset-messages` | POST | Create preset for REQUEST_LOCATION command |
| 7 | `/api/preset-messages/{id}` | GET | Get specific preset message details |
| 8 | `/api/preset-messages/{id}` | PUT | Update existing preset message |
| 9 | `/api/preset-messages/{id}/toggle` | POST | Toggle active/inactive status |
| 10 | `/api/preset-messages/{id}` | DELETE | Delete preset message |

---

## 📝 Sample Request Bodies Included

### 1. Lock Device Preset
```json
{
  "command": "LOCK_DEVICE",
  "title": "⚠️ Payment Required",
  "message": "Your device has been locked due to missed payment. Please pay your installment to unlock. Contact: 01700000000",
  "is_active": true
}
```

### 2. Unlock Device Preset
```json
{
  "command": "UNLOCK_DEVICE",
  "title": "✅ Thank You!",
  "message": "Thank you for your payment! Your device has been unlocked. Next payment due date will be notified.",
  "is_active": true
}
```

### 3. Disable Call Preset
```json
{
  "command": "DISABLE_CALL",
  "title": "📵 Calls Restricted",
  "message": "Phone calls have been temporarily restricted due to overdue payment. Please clear your balance to restore service.",
  "is_active": true
}
```

### 4. Request Location Preset
```json
{
  "command": "REQUEST_LOCATION",
  "title": "📍 Location Request",
  "message": "We are requesting your device location for verification purposes. This helps us serve you better and ensure security.",
  "is_active": true
}
```

### 5. Update Preset
```json
{
  "title": "⚠️ Updated Payment Reminder",
  "message": "Your device has been locked. Please contact us immediately at 01700000000 to resolve payment issues.",
  "is_active": true
}
```

---

## 🎯 How to Use in Postman

### Step 1: Import Updated Collection
If you haven't already imported the collection:
1. Open Postman
2. Click **Import**
3. Select `postman/collections/48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`
4. Collection will appear in your workspace

### Step 2: Authenticate
1. Navigate to **Authentication** → **Login**
2. Use your credentials
3. Token is automatically saved

### Step 3: Test Preset Messages
1. Navigate to **Preset Messages** folder (new!)
2. Start with **Get Available Commands** to see all 18 supported commands
3. Use **Create Preset Message** requests to set up presets
4. Try **Get All Preset Messages** to verify creation
5. Test **Toggle** to enable/disable without deletion

### Step 4: Test Auto-Send Feature
1. Create a preset for `LOCK_DEVICE` command
2. Go to **Device Control** → **Lock Device**
3. Execute the lock command
4. Check response - it should include `preset_message_sent: true`
5. Device receives both: lock command + your preset message!

---

## 📊 Collection Statistics

| Metric | Before | After | Added |
|--------|--------|-------|-------|
| Total Endpoints | 83 | 93 | +10 |
| API Folders | 10 | 11 | +1 |
| Preset Endpoints | 0 | 10 | +10 |
| Sample Request Bodies | N/A | 5 | +5 |

---

## 🎨 Postman Folder Structure

```
EMI Manager API Collection
├── Authentication (4)
├── User Management (7)
├── Locations (3)
├── Dashboard (1)
├── System (2)
├── Device Control (23)
├── Preset Messages (10) ← NEW!
├── Token Management (6)
├── Customer Management (6)
├── Reports (4)
└── Debug (2)
```

---

## 🔍 Key Features

### Automatic Token Management
- ✅ Bearer token automatically used in all preset endpoints
- ✅ No manual header configuration needed

### Complete Request Bodies
- ✅ Ready-to-use JSON samples
- ✅ All required fields included
- ✅ Multiple example scenarios

### Proper Documentation
- ✅ Each endpoint has detailed description
- ✅ Clear explanation of automatic message delivery
- ✅ Use case examples

### Organized Structure
- ✅ Logical grouping in "Preset Messages" folder
- ✅ Sequential IDs for easy reference
- ✅ Consistent naming convention

---

## 💡 Quick Examples

### Example 1: Create Preset for Payment Reminder
```http
POST {{base_url}}/preset-messages
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "command": "LOCK_DEVICE",
  "title": "⚠️ Payment Required",
  "message": "Device locked. Pay now to unlock. Call: 01700000000",
  "is_active": true
}
```

### Example 2: Get All Your Presets
```http
GET {{base_url}}/preset-messages
Authorization: Bearer {{token}}
```

### Example 3: Toggle Preset Status
```http
POST {{base_url}}/preset-messages/1/toggle
Authorization: Bearer {{token}}
```

### Example 4: Update Message Content
```http
PUT {{base_url}}/preset-messages/1
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "title": "New Title",
  "message": "Updated message content",
  "is_active": true
}
```

---

## 🚀 Testing Workflow

### Complete Test Scenario

1. **Setup Phase**
```
1. Login → Get token
2. Get Available Commands → See 18 commands
3. Create preset for LOCK_DEVICE
4. Get All Preset Messages → Verify creation
```

2. **Testing Phase**
```
5. Go to Device Control → Lock Device
6. Execute lock command with customer_id
7. Check response for preset_message_sent: true
8. Verify preset message details in response
```

3. **Management Phase**
```
9. Toggle preset status → Disable temporarily
10. Execute lock again → No preset sent
11. Toggle back to active → Re-enable
12. Update preset message → Change content
```

4. **Cleanup Phase**
```
13. Delete preset → Remove permanently
14. Get All → Verify deletion
```

---

## 📁 Files Updated

### 1. Postman Collection JSON
**File**: `postman/collections/48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`

**Changes**:
- Added 10 new preset message endpoints
- Included full request bodies
- Added proper descriptions
- Maintained consistent structure

### 2. Postman README
**File**: `postman/README.md`

**Changes**:
- Added "Preset Messages" section to overview
- Included 5 sample request bodies
- Added explanation of automatic message delivery
- Updated collection statistics

---

## 🎯 Supported Commands (18)

The preset messages work with these 18 commands:

1. LOCK_DEVICE
2. UNLOCK_DEVICE
3. DISABLE_CAMERA
4. ENABLE_CAMERA
5. DISABLE_BLUETOOTH
6. ENABLE_BLUETOOTH
7. HIDE_APP
8. UNHIDE_APP
9. RESET_PASSWORD
10. REMOVE_PASSWORD
11. REBOOT_DEVICE
12. REMOVE_APP
13. WIPE_DEVICE
14. SET_WALLPAPER
15. REMOVE_WALLPAPER
16. REQUEST_LOCATION
17. ENABLE_CALL
18. DISABLE_CALL

---

## 🔐 Authorization

All preset message endpoints require authentication:
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {{token}}`
- **Scope**: User can only manage their own presets

---

## 📝 Response Examples

### Successful Preset Creation
```json
{
  "success": true,
  "data": {
    "message": "Preset message saved successfully",
    "preset_message": {
      "id": 1,
      "user_id": 5,
      "command": "LOCK_DEVICE",
      "title": "⚠️ Payment Required",
      "message": "Your device has been locked...",
      "is_active": true,
      "created_at": "2025-10-17T02:45:00.000000Z",
      "updated_at": "2025-10-17T02:45:00.000000Z"
    }
  }
}
```

### List All Presets
```json
{
  "success": true,
  "data": {
    "preset_messages": [
      {
        "id": 1,
        "command": "LOCK_DEVICE",
        "title": "⚠️ Payment Required",
        "message": "Device locked due to payment...",
        "is_active": true
      },
      {
        "id": 2,
        "command": "UNLOCK_DEVICE",
        "title": "✅ Thank You!",
        "message": "Thank you for payment...",
        "is_active": true
      }
    ],
    "total": 2
  }
}
```

### Command with Preset Auto-Send
```json
{
  "success": true,
  "command": "LOCK_DEVICE",
  "log_id": 123,
  "message": "Command sent successfully",
  "details": {
    "success": true,
    "preset_message_sent": true,
    "preset_message": {
      "title": "⚠️ Payment Required",
      "message": "Your device has been locked..."
    }
  }
}
```

---

## ✅ Git Commits

### Commit 1: Feature Implementation
```bash
commit ffc912a
feat: Add preset messages feature for automatic command notifications
- 10 files changed, 1,132 insertions(+)
```

### Commit 2: Postman Collection Update
```bash
commit f9b8703
docs: Add preset messages endpoints to Postman collection
- 2 files changed, 399 insertions(+), 1 deletion(-)
```

---

## 🎓 Next Steps

### For Developers
1. Import updated Postman collection
2. Test all 10 new endpoints
3. Verify automatic message delivery
4. Integrate frontend UI for preset management

### For Testers
1. Test CRUD operations on presets
2. Verify authorization (user isolation)
3. Test toggle functionality
4. Validate auto-send with device commands

### For Production
1. Run migration: `php artisan migrate`
2. Clear caches: `php artisan config:clear`
3. Test with real devices
4. Train users on preset message feature

---

## 📞 Support

**Documentation Files**:
- `PRESET_MESSAGES_GUIDE.md` - Complete usage guide
- `PRESET_MESSAGES_COMPLETE.md` - Implementation summary
- `postman/README.md` - Postman collection guide
- `README.md` - Main project documentation

**Postman Collection**:
- Location: `postman/collections/48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`
- Total Endpoints: 93
- Preset Message Endpoints: 10

---

**Updated**: October 17, 2025  
**Version**: 1.1.0  
**Status**: ✅ Complete and Ready for Use
