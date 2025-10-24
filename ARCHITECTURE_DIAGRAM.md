# Complete System Architecture - Real-time Notifications

## 🏗️ Full System Overview

```
┌────────────────────────────────────────────────────────────────────┐
│                   Firebase Project: ime-locker-app                 │
│                      (Single Firebase Project)                     │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │              Firebase Cloud Messaging (FCM)              │    │
│  │         (For sending commands to customer devices)       │    │
│  └──────────────────────────────────────────────────────────┘    │
│                              ▲                                     │
│                              │ Sends commands via FCM              │
│                              │                                     │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │           Firebase Realtime Database (RTDB)              │    │
│  │      (For real-time notifications to admin apps)         │    │
│  │                                                           │    │
│  │  device_command_responses/                                │    │
│  │  ├── {admin_user_1}/                                      │    │
│  │  │   ├── {command_log_123}/                              │    │
│  │  │   │   ├── command: "LOCK_DEVICE"                      │    │
│  │  │   │   ├── status: "delivered"                         │    │
│  │  │   │   ├── customer_name: "John Doe"                   │    │
│  │  │   │   └── timestamp: "2025-10-24..."                  │    │
│  │  │   └── {command_log_124}/ ...                          │    │
│  │  └── {admin_user_2}/ ...                                 │    │
│  └──────────────────────────────────────────────────────────┘    │
│                              ▲                                     │
│                              │ Listens for updates                 │
└──────────────────────────────┼─────────────────────────────────────┘
                               │
                               │
        ┌──────────────────────┴────────────────────────┐
        │                                                │
        │                                                │
┌───────▼────────┐                              ┌───────▼────────┐
│  Laravel       │                              │  Admin Apps    │
│  Backend       │                              │                │
│                │                              │  1. Web App    │
│  - FCM Send    │                              │     (React)    │
│  - Receive     │                              │                │
│    Responses   │                              │  2. Android    │
│  - Push to     │                              │     App        │
│    Firebase    │                              │                │
│    RTDB        │                              │  Both listen   │
│                │                              │  to same       │
│  Uses:         │                              │  Firebase path │
│  - Service     │                              │                │
│    Account     │                              │  Show real-    │
│    JSON        │                              │  time toast/   │
│  - Kreait      │                              │  notification  │
│    v7.13 FCM   │                              │                │
└───────▲────────┘                              └────────────────┘
        │
        │ 3. Device sends response
        │    POST /api/devices/command-response
        │
┌───────┴────────┐
│   Customer     │
│   Android      │
│   Device       │
│                │
│   1. Receives  │ ◄─── FCM from Laravel
│      command   │
│   2. Executes  │
│   3. Responds  │ ───► Laravel API
│                │
│   Uses:        │
│   - google-    │
│     services   │
│     .json      │
└────────────────┘
```

---

## 🔄 Command Flow (Step by Step)

### **Scenario: Admin locks customer's device**

```
STEP 1: Admin Initiates Command
┌──────────────┐
│ Admin App    │
│ (Web/Mobile) │
└──────┬───────┘
       │ POST /api/devices/command/lock
       │ { customer_id: 45 }
       ▼
┌──────────────┐
│   Laravel    │
│   Backend    │
└──────┬───────┘
       │ 1. Create command log (status: "sent")
       │ 2. Get customer's FCM token
       │ 3. Send FCM message
       ▼
┌──────────────┐
│   Firebase   │
│     FCM      │
└──────┬───────┘
       │ Push notification with data
       ▼
┌──────────────┐
│   Customer   │
│   Device     │
└──────┬───────┘
       │ Device receives FCM
       │ Executes: Lock screen
       │
       
STEP 2: Device Responds
┌──────────────┐
│   Customer   │
│   Device     │
└──────┬───────┘
       │ POST /api/devices/command-response
       │ {
       │   device_id: "IMEI1234...",
       │   command: "LOCK_DEVICE",
       │   data: { status: "success" }
       │ }
       ▼
┌──────────────┐
│   Laravel    │
│   Backend    │
└──────┬───────┘
       │ 1. Find command log by device_id + command
       │ 2. Update status: "sent" → "delivered"
       │ 3. Fire DeviceCommandResponseReceived event
       │ 4. Listener pushes to Firebase RTDB
       │    (HTTP PUT to /device_command_responses/{user_id}/{cmd_id}.json)
       ▼
┌──────────────┐
│   Firebase   │
│     RTDB     │
└──────┬───────┘
       │ Data written to:
       │ device_command_responses/123/456
       │
       
STEP 3: Real-time Notification to Admin
┌──────────────┐
│   Firebase   │
│     RTDB     │
└──────┬───────┘
       │ Real-time update pushed to listeners
       ├────────────────┬────────────────┐
       ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  Admin Web  │  │Admin Android│  │Admin Android│
│     App     │  │   App #1    │  │   App #2    │
└─────────────┘  └─────────────┘  └─────────────┘
  Toast: "✅      Notification:      Notification:
  John's device   "Device locked"    "Device locked"
  locked!"        + Sound + Badge    + Sound + Badge
```

---

## 📱 App Configurations

### **Customer Android App**
```
google-services.json
├── project_id: "ime-locker-app"
├── client:
│   └── package_name: "com.yourcompany.emilocker"
└── Purpose: Receive FCM commands from Laravel
```

### **Admin Android App**
```
google-services.json (SAME project_id!)
├── project_id: "ime-locker-app"  ← Same!
├── client:
│   └── package_name: "com.yourcompany.emimanager.admin"  ← Different!
└── Purpose: Listen to Firebase RTDB for real-time updates
```

### **Admin Web App**
```
.env
├── VITE_FIREBASE_API_KEY=...
├── VITE_FIREBASE_DATABASE_URL=https://ime-locker-app-default-rtdb.firebaseio.com
├── VITE_FIREBASE_PROJECT_ID=ime-locker-app  ← Same!
└── Purpose: Listen to Firebase RTDB via JavaScript SDK
```

### **Laravel Backend**
```
.env
├── FIREBASE_CREDENTIALS=storage/app/firebase/ime-locker-app-credentials.json
├── FIREBASE_PROJECT_ID=ime-locker-app  ← Same!
├── FIREBASE_DATABASE_URL=https://ime-locker-app-default-rtdb.firebaseio.com
└── Purpose:
    1. Send FCM to customer devices (Kreait v7.13)
    2. Push notifications to RTDB (HTTP REST API)
```

---

## 🔐 Security & Authentication

### **For Customer Devices**
```
┌────────────────┐
│ Customer App   │
│ Registration   │
└────────┬───────┘
         │ POST /api/devices/register
         │ { serial_number, imei1, fcm_token }
         ▼
┌────────────────┐
│   Laravel      │
│   Saves:       │
│   - FCM token  │
│   - Device ID  │
└────────────────┘
         
Authentication: ❌ None needed (public endpoint)
Security: ✅ Device identified by IMEI/Serial
```

### **For Admin Apps**

#### **Option A: Custom Token (Recommended)**
```
┌────────────────┐
│   Admin App    │
│   Login        │
└────────┬───────┘
         │ POST /api/auth/login
         │ { email, password }
         ▼
┌────────────────┐
│   Laravel      │
│   Returns:     │
│   - user_id    │
│   - JWT token  │
│   - firebase_  │
│     custom_    │
│     token      │
└────────┬───────┘
         │
         ▼
┌────────────────┐
│   Admin App    │
│   Signs into   │
│   Firebase     │
│   with custom  │
│   token        │
└────────┬───────┘
         │ Firebase UID = Laravel user_id
         ▼
┌────────────────┐
│  Firebase Auth │
│  Authenticated │
└────────┬───────┘
         │ Can now read:
         │ device_command_responses/{user_id}
         ▼
┌────────────────┐
│  Firebase RTDB │
│  Security      │
│  Rules Allow   │
└────────────────┘
```

#### **Option B: Test Mode (Development Only)**
```
{
  "rules": {
    "device_command_responses": {
      "$userId": {
        ".read": true,   ← Anyone can read
        ".write": true   ← Anyone can write
      }
    }
  }
}

⚠️ WARNING: Only use during development!
```

---

## 📊 Data Ownership & Isolation

```
Firebase RTDB Structure:

device_command_responses/
├── 1/    ← Admin User ID: 1 (Super Admin)
│   ├── 101/  ← Command Log ID
│   ├── 102/
│   └── 103/
│
├── 5/    ← Admin User ID: 5 (Dealer)
│   ├── 201/
│   └── 202/
│
└── 12/   ← Admin User ID: 12 (Salesman)
    └── 301/

✅ Each admin only sees their own commands
✅ Data isolation by user ID
✅ Security rules enforce this
```

---

## 🌐 Network Flow

```
Internet
    │
    ├─────────────────────────────────────┐
    │                                     │
    │                                     │
┌───▼──────────────┐          ┌──────────▼─────┐
│  Firebase Cloud  │          │   Your Laravel │
│  (Google)        │          │   Server       │
│                  │          │                │
│  - FCM Service   │          │  API Endpoints │
│  - RTDB Service  │          │  - /devices/*  │
│  - Auth Service  │          │  - /auth/*     │
└───┬──────────────┘          └──────────┬─────┘
    │                                     │
    │ FCM Push                           │ HTTPS POST
    │                                     │ (Command Response)
    │                                     │
┌───▼─────────────┐                     ┌▼───────────┐
│  Customer       │                     │  Customer  │
│  Android Device │                     │  Android   │
│                 │                     │  Device    │
│  - Receives FCM │                     │            │
│  - Executes cmd │─────────────────────┤            │
│  - Sends response to Laravel API      │            │
└─────────────────┘                     └────────────┘
    
    
┌────────────────────────────────────────────────────┐
│             Real-time Listeners                    │
├────────────────────────────────────────────────────┤
│                                                    │
│  ┌──────────────┐    ┌──────────────┐            │
│  │  Admin Web   │    │ Admin Android│            │
│  │  App (React) │    │    App       │            │
│  │              │    │              │            │
│  │  Listens to: │    │  Listens to: │            │
│  │  Firebase    │    │  Firebase    │            │
│  │  RTDB via    │    │  RTDB via    │            │
│  │  JavaScript  │    │  Android SDK │            │
│  │  SDK         │    │              │            │
│  └──────▲───────┘    └──────▲───────┘            │
│         │                   │                     │
│         │                   │                     │
│         └───────────┬───────┘                     │
│                     │                             │
│                ┌────▼─────┐                       │
│                │ Firebase │                       │
│                │   RTDB   │                       │
│                │          │                       │
│                │ Pushes   │                       │
│                │ updates  │                       │
│                │ in real- │                       │
│                │ time     │                       │
│                └──────────┘                       │
└────────────────────────────────────────────────────┘
```

---

## 💡 Key Takeaways

### ✅ Single Firebase Project
- One `project_id`: `ime-locker-app`
- Multiple apps (customer, admin web, admin Android)
- Each app has its own `google-services.json` or config
- All share same Realtime Database

### ✅ Separate Concerns
- **FCM**: For pushing commands to customer devices
- **RTDB**: For real-time notifications to admin apps
- Both use same Firebase project, different services

### ✅ No Conflicts
- Laravel uses Kreait v7.13 for FCM ✅
- Laravel uses HTTP REST API for RTDB ✅
- Admin apps use native SDKs for RTDB ✅
- Customer app uses FCM via native SDK ✅

### ✅ Data Flow
1. Admin → Laravel API → FCM → Customer Device
2. Customer Device → Laravel API → RTDB → Admin Apps
3. Loop complete with real-time feedback!

---

## 🚀 Implementation Order

### Phase 1: Backend (Already Done! ✅)
- [x] Laravel listener uses HTTP for RTDB
- [x] FIREBASE_DATABASE_URL configured
- [x] Event fires on command response

### Phase 2: Admin Web App (Already Done! ✅)
- [x] Firebase JavaScript SDK configured
- [x] useDeviceCommandNotifications hook
- [x] Toast notifications

### Phase 3: Admin Android App (Your Turn! 🎯)
1. [ ] Add Firebase Android SDK
2. [ ] Download google-services.json
3. [ ] Implement real-time listener
4. [ ] Show Android notifications
5. [ ] Test with real devices

---

## 📚 Documentation Files

1. **REALTIME_NOTIFICATIONS_SETUP.md** - Backend setup (done)
2. **ADMIN_ANDROID_APP_REALTIME_SETUP.md** - Full Android guide (detailed)
3. **ADMIN_APP_QUICK_START.md** - Quick 5-minute setup (quick reference)
4. **ARCHITECTURE_DIAGRAM.md** - This file (overview)

---

**Ready to implement? Start with `ADMIN_APP_QUICK_START.md` for a quick setup, or dive into `ADMIN_ANDROID_APP_REALTIME_SETUP.md` for the complete guide!**

