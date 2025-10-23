# Real-time Device Command Notifications Setup

This guide explains how the real-time notification system works when device commands are executed.

## Architecture Overview

```
User sends command → Backend API → FCM to Device
                                      ↓
                                Device executes
                                      ↓
                       Device sends response back
                                      ↓
                          commandResponse endpoint
                                      ↓
                        Firebase Realtime Database
                                      ↓
                          Frontend listens (real-time)
                                      ↓
                          Toast notification appears!
```

## How It Works

### 1. **Backend (Laravel)**

When a device responds to a command via the `commandResponse` endpoint:

1. Command log is updated with status `delivered`
2. Event `DeviceCommandResponseReceived` is fired
3. Listener `SendDeviceCommandResponseNotification` catches the event
4. Data is pushed to Firebase Realtime Database at path:
   ```
   /device_command_responses/{user_id}/{command_log_id}
   ```

### 2. **Frontend (React)**

The `useDeviceCommandNotifications` hook:

1. Listens to Firebase Realtime Database for new entries
2. Automatically shows toast notifications
3. Marks notifications as read and removes them from Firebase
4. Works globally across the entire app (activated in `app-shell.jsx`)

## Setup Instructions

### Backend Setup

1. **Ensure Firebase Admin SDK is configured** (already done in your project)

2. **Run the following command to register event listener:**
   ```bash
   php artisan event:list
   ```
   You should see `DeviceCommandResponseReceived` listed.

### Frontend Setup

1. **Add Firebase configuration to `.env` file:**
   ```bash
   # Firebase Configuration
   VITE_FIREBASE_API_KEY=your_firebase_api_key_here
   VITE_FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
   VITE_FIREBASE_DATABASE_URL=https://your_project.firebaseio.com
   VITE_FIREBASE_PROJECT_ID=your_project_id
   VITE_FIREBASE_STORAGE_BUCKET=your_project.appspot.com
   VITE_FIREBASE_MESSAGING_SENDER_ID=123456789
   VITE_FIREBASE_APP_ID=1:123456789:web:abcdef123456
   ```

2. **Get Firebase config from Firebase Console:**
   - Go to: https://console.firebase.google.com
   - Select your project (ime-locker-app)
   - Go to Project Settings → General
   - Scroll to "Your apps" section
   - Click "Web app" config icon
   - Copy the config values

3. **Restart Vite dev server:**
   ```bash
   npm run dev
   ```

## Firebase Realtime Database Rules

Add these rules in Firebase Console → Realtime Database → Rules:

```json
{
  "rules": {
    "device_command_responses": {
      "$userId": {
        ".read": "auth != null && auth.uid == $userId",
        ".write": "true",
        "$commandLogId": {
          ".validate": "newData.hasChildren(['command_log_id', 'customer_id', 'command', 'status', 'timestamp'])"
        }
      }
    }
  }
}
```

## Testing

### 1. Send a Command
- Go to Device Management page
- Click any command button (e.g., "Lock Device")
- Command is sent via FCM

### 2. Device Responds
When the device executes the command and sends a response to:
```
POST /api/devices/command-response
{
  "device_id": "serial_or_imei",
  "command": "lock",
  "data": { ... }
}
```

### 3. Real-time Notification
- ✅ Toast notification appears instantly
- ✅ Shows success/failure status
- ✅ Includes customer name and command
- ✅ Works even if you're on a different page

## Features

### ✅ Global Notifications
- Notifications work across the entire app
- User receives alerts even when not on Device Management page

### ✅ Toast Notifications
- Success: Green toast with checkmark
- Failure: Red toast with error
- Auto-dismiss after 5 seconds

### ✅ Custom Callbacks
You can add custom logic when notifications are received:

```javascript
useDeviceCommandNotifications(
    user?.id,
    (notification) => {
        console.log('Received:', notification);
        // Refetch data, update UI, etc.
    },
    true // Show toast
);
```

### ✅ Automatic Cleanup
- Notifications are automatically removed after being read
- No database bloat
- Firebase listeners are properly cleaned up on unmount

## Troubleshooting

### Notifications not appearing?

1. **Check Firebase config in `.env`**
   ```bash
   echo $VITE_FIREBASE_DATABASE_URL
   ```

2. **Check browser console for errors**
   - Open DevTools → Console
   - Look for Firebase errors

3. **Verify Firebase Realtime Database is enabled**
   - Go to Firebase Console
   - Ensure Realtime Database is created and active

4. **Check Firebase rules**
   - Make sure write permissions are set to `true` for testing

### Testing without actual device?

You can manually trigger a notification by calling the endpoint:

```bash
curl -X POST http://127.0.0.1:8000/api/devices/command-response \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "your_imei_or_serial",
    "command": "lock",
    "data": {"status": "success"}
  }'
```

## Advantages of This Approach

✅ **Real-time updates** - No polling needed
✅ **Firebase integration** - Uses existing Firebase setup
✅ **Scalable** - Firebase handles millions of concurrent connections
✅ **Reliable** - Firebase guarantees message delivery
✅ **Efficient** - Only sends data when commands are executed
✅ **Global** - Notifications work across entire app
✅ **Clean** - Automatic cleanup of old notifications

## Alternative Approaches (Not Implemented)

### 1. Polling (Simple but inefficient)
- Frontend checks for updates every few seconds
- ❌ High server load
- ❌ Delayed notifications
- ❌ Waste of bandwidth

### 2. WebSockets (Complex setup)
- Requires WebSocket server
- ❌ More complex to maintain
- ❌ Requires additional infrastructure

### 3. Pusher/Laravel Echo (Paid service)
- Laravel Broadcasting with Pusher
- ❌ Requires monthly subscription
- ❌ Additional service dependency

## Files Modified

### Backend
- ✅ `app/Events/DeviceCommandResponseReceived.php`
- ✅ `app/Listeners/SendDeviceCommandResponseNotification.php`
- ✅ `app/Providers/EventServiceProvider.php`
- ✅ `app/Http/Controllers/Api/DeviceController.php`
- ✅ `bootstrap/providers.php`

### Frontend
- ✅ `src/hooks/useDeviceCommandNotifications.js`
- ✅ `src/components/app-shell.jsx`
- ✅ `package.json` (added firebase dependency)

## Support

For issues or questions, check:
- Firebase Console: https://console.firebase.google.com
- Laravel Events: https://laravel.com/docs/events
- Firebase JS SDK: https://firebase.google.com/docs/web/setup

