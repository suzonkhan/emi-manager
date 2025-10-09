# Device Command Flow Diagrams

## 1. Complete System Architecture

```
┌─────────────────┐
│   Admin Panel   │
│   (Frontend)    │
└────────┬────────┘
         │ HTTP Request
         ▼
┌─────────────────────────────────────────────────────────┐
│              Laravel Backend API                         │
│                                                          │
│  ┌─────────────────┐      ┌──────────────────┐         │
│  │ DeviceController│─────▶│DeviceCommandSvc  │         │
│  └─────────────────┘      └─────────┬────────┘         │
│                                      │                   │
│                           ┌──────────▼─────────┐        │
│                           │  Firebase Service  │        │
│                           │  (FCM Push)        │        │
│                           └──────────┬─────────┘        │
│                                      │                   │
│                           ┌──────────▼─────────┐        │
│                           │  Database          │        │
│                           │  (State Updates)   │        │
│                           └────────────────────┘        │
└────────────────────────────────┬────────────────────────┘
                                 │ FCM Push Notification
                                 ▼
                    ┌────────────────────────┐
                    │   Android Device       │
                    │   (Customer Phone)     │
                    │                        │
                    │  - Receives command    │
                    │  - Executes action     │
                    │  - Syncs state on boot │
                    └────────────────────────┘
```

---

## 2. State-Based Command Flow (Example: Lock Device)

```
┌─────────┐                                    ┌──────────┐
│  Admin  │                                    │  Device  │
└────┬────┘                                    └────┬─────┘
     │                                              │
     │ POST /api/devices/command/lock              │
     │ Body: {"customer_id": 123}                  │
     ├─────────────────────────────────────────────┤
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 1. DeviceController validates   │       │
     │   │ 2. Calls DeviceCommandService   │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 3. Sends FCM Push Notification  │       │
     │   │    Command: LOCK_DEVICE          │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     ├──────────────────────────────────────────▶  │ FCM Push
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 4. Updates Database:            │       │
     │   │    is_device_locked = true      │       │
     │   │    last_command_sent_at = now() │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 5. Logs to device_command_logs  │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │ ◀─── Response: {"success": true}             │
     │                                              │
     │                                              ├─────────────────┐
     │                                              │ 6. Device locks │
     │                                              │    screen       │
     │                                              ◀─────────────────┘
```

---

## 3. Action-Based Command Flow (Example: Show Message)

```
┌─────────┐                                    ┌──────────┐
│  Admin  │                                    │  Device  │
└────┬────┘                                    └────┬─────┘
     │                                              │
     │ POST /api/devices/command/show-message      │
     │ Body: {                                     │
     │   "customer_id": 123,                       │
     │   "title": "Payment Reminder",              │
     │   "message": "Your EMI is due tomorrow"     │
     │ }                                            │
     ├─────────────────────────────────────────────┤
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 1. DeviceController validates   │       │
     │   │ 2. Calls DeviceCommandService   │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 3. Sends FCM Push Notification  │       │
     │   │    Command: SHOW_MESSAGE         │       │
     │   │    Data: {title, message}        │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     ├──────────────────────────────────────────▶  │ FCM Push
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 4. Updates last_command_sent_at │       │
     │   │    (NO state change)            │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │   ┌─────────────────────────────────┐       │
     │   │ 5. Logs to device_command_logs  │       │
     │   └─────────────────────────────────┘       │
     │                                              │
     │ ◀─── Response: {"success": true}             │
     │                                              │
     │                                              ├──────────────────┐
     │                                              │ 6. Shows message │
     │                                              │    notification  │
     │                                              ◀──────────────────┘
     │                                              │
     │                                              ├──────────────────┐
     │                                              │ 7. User dismisses│
     │                                              │    (gone forever)│
     │                                              ◀──────────────────┘
```

**Key Difference:** No database state update! Message is temporary.

---

## 4. Device Boot/Restart Flow

```
┌─────────────────┐
│  Device Boots   │
│  (App Starts)   │
└────────┬────────┘
         │
         │ 1. GET /api/devices/{customer}
         ├─────────────────────────────────────┐
         │                                      │
         │                    ┌─────────────────▼──────────┐
         │                    │ DeviceController::show()   │
         │                    │ Returns DeviceResource     │
         │                    └─────────────────┬──────────┘
         │                                      │
         │ ◀──────────────────────────────────  │
         │                                      │
         │ Response:                            │
         │ {                                    │
         │   "device_status": {                 │
         │     "is_locked": true,               │
         │     "is_camera_disabled": false,     │
         │     "is_bluetooth_disabled": true,   │
         │     "has_password": true,            │
         │     "custom_wallpaper_url": "url"    │
         │   }                                  │
         │ }                                    │
         │                                      │
         ▼                                      │
┌──────────────────────────────┐               │
│ Device Applies All States:  │               │
│                              │               │
│ ✓ Lock screen               │               │
│ ✓ Keep camera enabled       │               │
│ ✓ Disable bluetooth         │               │
│ ✓ Require password          │               │
│ ✓ Set wallpaper from URL    │               │
└──────────────────────────────┘               │
```

**Critical:** Device reads state from database on every boot!

---

## 5. Complete Command Matrix

```
┌──────────────────────────┬───────────────────┬──────────────────┐
│ Command                  │ Database Field    │ Type             │
├──────────────────────────┼───────────────────┼──────────────────┤
│ LOCK_DEVICE              │ is_device_locked  │ STATE-BASED ✓    │
│ UNLOCK_DEVICE            │ is_device_locked  │ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ DISABLE_CAMERA           │ is_camera_disabled│ STATE-BASED ✓    │
│ ENABLE_CAMERA            │ is_camera_disabled│ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ DISABLE_BLUETOOTH        │ is_bluetooth_disa.│ STATE-BASED ✓    │
│ ENABLE_BLUETOOTH         │ is_bluetooth_disa.│ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ HIDE_APP                 │ is_app_hidden     │ STATE-BASED ✓    │
│ UNHIDE_APP               │ is_app_hidden     │ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ RESET_PASSWORD           │ has_password      │ STATE-BASED ✓    │
│ REMOVE_PASSWORD          │ has_password      │ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ SET_WALLPAPER            │ custom_wallpaper_.│ STATE-BASED ✓    │
│ REMOVE_WALLPAPER         │ custom_wallpaper_.│ STATE-BASED ✓    │
├──────────────────────────┼───────────────────┼──────────────────┤
│ REBOOT_DEVICE            │ (none)            │ ACTION-BASED ✗   │
│ SHOW_MESSAGE             │ (none)            │ ACTION-BASED ✗   │
│ REMINDER_SCREEN          │ (none)            │ ACTION-BASED ✗   │
│ REMINDER_AUDIO           │ (none)            │ ACTION-BASED ✗   │
│ REQUEST_LOCATION         │ (none)            │ ACTION-BASED ✗   │
│ REMOVE_APP               │ (none)            │ ACTION-BASED ✗   │
│ WIPE_DEVICE              │ (none)            │ ACTION-BASED ✗   │
└──────────────────────────┴───────────────────┴──────────────────┘
```

---

## 6. Database Schema Visualization

```
┌─────────────────────────────────────────────────────────────┐
│                    customers table                          │
├─────────────────────────────────────────────────────────────┤
│ id                    │ PRIMARY KEY                         │
│ name                  │ VARCHAR                             │
│ serial_number         │ VARCHAR UNIQUE                      │
│ fcm_token             │ TEXT (Firebase token)               │
├─────────────────────────────────────────────────────────────┤
│        DEVICE STATE FIELDS (Persistent)                     │
├─────────────────────────────────────────────────────────────┤
│ is_device_locked      │ BOOLEAN DEFAULT false  ◀── STATE    │
│ is_camera_disabled    │ BOOLEAN DEFAULT false  ◀── STATE    │
│ is_bluetooth_disabled │ BOOLEAN DEFAULT false  ◀── STATE    │
│ is_app_hidden         │ BOOLEAN DEFAULT false  ◀── STATE    │
│ has_password          │ BOOLEAN DEFAULT false  ◀── STATE    │
│ custom_wallpaper_url  │ VARCHAR NULLABLE       ◀── STATE    │
│ last_command_sent_at  │ TIMESTAMP NULLABLE     ◀── TRACKING │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. DeviceResource Transformation

```
┌──────────────────────┐        ┌─────────────────────────┐
│  Customer Model      │        │  API Response           │
│  (Database Record)   │  ────▶ │  (JSON)                 │
└──────────────────────┘        └─────────────────────────┘
                                
Customer {                      {
  id: 123                         "customer_id": 123,
  name: "John"                    "customer_name": "John",
  serial_number: "SN123"          "device": {
  fcm_token: "xyz"                  "serial_number": "SN123",
  is_device_locked: true            "fcm_token": "xyz"
  is_camera_disabled: false       },
  has_password: true              "device_status": {
  custom_wallpaper_url: "url"       "is_locked": true,
}                                   "is_camera_disabled": false,
                                    "has_password": true,
                                    "custom_wallpaper_url": "url"
                                  }
                                }
```

---

## 8. Real-World Scenario Timeline

```
Time    │ Event                              │ Database State
────────┼────────────────────────────────────┼──────────────────────
09:00   │ Customer receives device           │ All false
09:15   │ Admin locks device                 │ is_locked = true
09:16   │ Device receives FCM, locks         │ (no change)
10:30   │ Customer reboots device            │ (no change)
10:31   │ Device boots, calls API            │ (no change)
10:31   │ Device applies locked state        │ (no change)
11:00   │ Admin shows "Payment Due" message  │ last_command_sent_at updated
11:00   │ Customer sees message, dismisses   │ (no change)
14:00   │ Admin unlocks device               │ is_locked = false
14:01   │ Device receives FCM, unlocks       │ (no change)
15:00   │ Customer reboots again             │ (no change)
15:01   │ Device boots, stays unlocked ✓     │ (reads is_locked = false)
```

**Key Insight:** State-based commands persist across reboots, action-based don't!

---

## Summary

### State-Based Commands:
- ✓ Update database fields
- ✓ Survive device reboots
- ✓ Synced on app start

### Action-Based Commands:
- ✓ Execute immediately
- ✗ No database update
- ✗ Not persisted

### Device Info API:
- Returns current state from database
- Device applies state on boot
- Real-time updates via FCM
