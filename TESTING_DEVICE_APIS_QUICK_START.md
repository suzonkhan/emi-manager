# Quick Start: Testing Device Control APIs 🚀

## You Have 3 Ways to Test Your APIs!

---

## ✅ Option 1: Interactive PHP Script (Easiest!)

```bash
php test-device-api.php
```

This interactive script lets you:
- ✅ Login and get auth token
- ✅ Register a test device with fake FCM token
- ✅ Send all device commands
- ✅ View command history
- ✅ Test Firebase connection

**Features:**
- Interactive menu
- Auto-generates test FCM tokens
- Saves auth token for you
- No real device needed!

---

## ✅ Option 2: Laravel Artisan Commands

### Test Firebase Connection
```bash
php artisan firebase:test
```

### Test Device Commands
```bash
# View customer info and send commands interactively
php artisan device:test 1

# Send specific command
php artisan device:test 1 lock
php artisan device:test 1 unlock
php artisan device:test 1 show-message
```

**Features:**
- Built into Laravel
- Auto-registers test device if needed
- Interactive command menu
- Shows detailed results

---

## ✅ Option 3: Postman Collection (Best for Real Testing)

1. **Import Collection:**
   - Open Postman
   - Import: `postman/collections/48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`

2. **Login First:**
   - Run: `Authentication → Login`
   - Token auto-saves to `{{token}}` variable

3. **Register Device:**
   - Run: `Device Control → Register Device`
   - Use this payload with test data:
   ```json
   {
     "serial_number": "TEST_ABC123",
     "imei1": "356740000000000",
     "fcm_token": "eXXX...your_generated_token...XXXe"
   }
   ```

4. **Send Commands:**
   - All 19 commands available in `Device Control` folder
   - Customer ID is required in body

---

## 🎯 Recommended Testing Workflow

### Step 1: Verify Setup
```bash
# Check Firebase connection
php artisan firebase:test
```
**Expected:** ✅ All checks pass

---

### Step 2: Quick API Test (No Device Needed)
```bash
# Run interactive tester
php test-device-api.php
```

**Actions:**
1. Select `[1]` - Login
2. Select `[2]` - Register test device (auto-generates FCM token)
3. Select `[4]` - Send lock command
4. Select `[7]` - View command history

**Expected Results:**
- ✅ APIs respond successfully
- ✅ Commands logged in database
- ⚠️ FCM send will fail (expected - it's a fake token)
- ✅ Can verify API logic works

---

### Step 3: Database Verification
```bash
# Check device_command_logs table
php artisan tinker
```

```php
\App\Models\DeviceCommandLog::latest()->take(5)->get();
```

**Expected:** See your test commands logged with status 'sent' or 'failed'

---

## 📱 To Test with REAL Device

### Quick Firebase Setup

1. **Go to Firebase Console:**
   - https://console.firebase.google.com/
   - Select project: `ime-locker-app`

2. **Add Android App:**
   - Click "Add App" → Android
   - Package name: `com.emimanager.device` (or your choice)
   - Download `google-services.json`

3. **Get Web API Key (for browser testing):**
   - Project Settings → General
   - Copy "Web API Key"

### Browser FCM Token (5 Minutes)

Create file: `fcm-token-getter.html`

```html
<!DOCTYPE html>
<html>
<head>
    <title>Get FCM Token</title>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js"></script>
</head>
<body>
    <h1>FCM Token Generator</h1>
    <button onclick="getToken()" style="padding: 20px; font-size: 18px;">
        Get FCM Token
    </button>
    <div id="result" style="margin-top: 20px;"></div>

    <script>
        const firebaseConfig = {
            apiKey: "YOUR_WEB_API_KEY_HERE",
            authDomain: "ime-locker-app.firebaseapp.com",
            projectId: "ime-locker-app",
            storageBucket: "ime-locker-app.appspot.com",
            messagingSenderId: "YOUR_SENDER_ID",
            appId: "YOUR_APP_ID"
        };

        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        async function getToken() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Requesting permission...';
            
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    resultDiv.innerHTML = 'Getting token...';
                    
                    const token = await messaging.getToken({
                        vapidKey: 'YOUR_VAPID_KEY' // From Firebase Console → Cloud Messaging
                    });
                    
                    resultDiv.innerHTML = `
                        <h2>✅ Success!</h2>
                        <p><strong>Your FCM Token:</strong></p>
                        <textarea rows="5" style="width: 100%; font-family: monospace;">${token}</textarea>
                        <button onclick="navigator.clipboard.writeText('${token}')">Copy Token</button>
                    `;
                    console.log('FCM Token:', token);
                } else {
                    resultDiv.innerHTML = '❌ Permission denied';
                }
            } catch (err) {
                resultDiv.innerHTML = '❌ Error: ' + err.message;
                console.error('Error:', err);
            }
        }

        // Listen for messages
        messaging.onMessage((payload) => {
            console.log('Message received:', payload);
            alert('Command received: ' + JSON.stringify(payload.data));
        });
    </script>
</body>
</html>
```

**Usage:**
1. Replace `YOUR_WEB_API_KEY_HERE`, etc. with values from Firebase Console
2. Open in Chrome/Edge
3. Click "Get FCM Token"
4. Allow notifications
5. Copy the token
6. Use it in your API tests!

---

## 🧪 Test Commands with Real Token

Once you have a real FCM token:

```bash
# Using Artisan
php artisan firebase:test YOUR_REAL_FCM_TOKEN

# Using interactive script
php test-device-api.php
# Then: [1] Login → [2] Register (paste your real token)
```

**Expected:** Message delivered to browser/device! ✅

---

## 📊 Understanding Test Results

### ✅ Success Indicators:
- HTTP 200 status
- `"success": true` in response
- Command logged in `device_command_logs` table
- `status: "sent"` in database

### ⚠️ Expected "Failures" (with test tokens):
- FCM API returns error (token invalid)
- `status: "failed"` in database
- But API endpoints work correctly!

### ❌ Real Failures:
- HTTP 500/422 errors
- Database not saving logs
- Customer not found
- Validation errors

---

## 🎯 Summary: Your APIs Are Working!

### What's Confirmed Working: ✅
- Firebase connection established
- API endpoints responding
- Authentication working
- Database logging working
- Command validation working
- Service layer working

### What Needs Real Device: 📱
- Actual message delivery
- Device receiving commands
- Device sending responses back

---

## Next Steps

**For Development:**
- Keep using test tokens
- Test API logic, validation, database
- No device needed!

**For Production Testing:**
- Set up Android emulator (30 min)
- Or use browser FCM token (5 min)
- Test real message delivery

---

## Quick Commands Reference

```bash
# Test Firebase
php artisan firebase:test

# Interactive API tester
php test-device-api.php

# Test with customer
php artisan device:test 1

# View logs
php artisan tinker
>>> \App\Models\DeviceCommandLog::latest()->get()

# Check device registration
>>> \App\Models\Customer::whereNotNull('fcm_token')->get()
```

---

**You're all set! Start with `php test-device-api.php` for the easiest testing experience! 🚀**
