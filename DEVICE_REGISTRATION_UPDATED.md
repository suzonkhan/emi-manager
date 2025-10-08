# Device Registration API - Updated Structure

## ✅ Changes Implemented

### Updated Payload Structure

**Previous (Incorrect):**
```json
{
  "nid_no": "123456789",
  "serial_number": "R2Q5X08F00Y",
  "imei1": "356740000000000",
  "fcm_token": "fcm_token_string"
}
```

**Current (Correct):**
```json
{
  "serial_number": "R2Q5X08F00Y",
  "imei1": "356740000000000",
  "fcm_token": "fcm_token_string"
}
```

## Why Remove NID?

The Android device **doesn't know** the customer's NID number at installation time. The device only has access to:
- ✅ Serial Number (Build.SERIAL)
- ✅ IMEI (TelephonyManager)
- ✅ FCM Token (Firebase)

## How It Works Now

### 1. Customer Creation (Admin/Staff in Dashboard)
When creating a customer, the admin enters:
- Customer info (name, NID, mobile, etc.)
- **IMEI1** - Must be entered during customer creation
- Product details

### 2. Device Installation (Automatic)
When the hidden app is installed on the customer's device:
1. App reads device Serial Number
2. App reads device IMEI1
3. App generates FCM token
4. App calls `/api/devices/register` with these 3 values
5. **Server finds customer by IMEI1 match**
6. Server updates customer record with serial_number and fcm_token

### 3. Device Control (Admin/Staff)
After registration, admins can send commands to the device.

## Updated Code

### DeviceRegisterRequest.php
```php
public function rules(): array
{
    return [
        'serial_number' => ['required', 'string', 'max:255'],
        'imei1' => ['required', 'string', 'max:255'],
        'fcm_token' => ['required', 'string'],
    ];
}
```

### DeviceCommandService.php
```php
public function registerDevice(string $serialNumber, string $imei1, string $fcmToken): Customer
{
    // Find customer by IMEI1 (pre-registered during customer creation)
    $customer = Customer::where('imei_1', $imei1)->firstOrFail();

    // Update device information
    $customer->update([
        'serial_number' => $serialNumber,
        'fcm_token' => $fcmToken,
    ]);

    return $customer->fresh();
}
```

### DeviceController.php
```php
public function register(DeviceRegisterRequest $request): JsonResponse
{
    try {
        $customer = $this->deviceCommandService->registerDevice(
            $request->input('serial_number'),
            $request->input('imei1'),
            $request->input('fcm_token')
        );

        return $this->success([
            'message' => 'Device registered successfully',
            'device' => new DeviceResource($customer),
        ]);
    } catch (Exception $e) {
        return $this->error($e->getMessage(), 500);
    }
}
```

## API Endpoint

```
POST /api/devices/register
```

**Public endpoint - No authentication required**

## Request Example

```bash
curl -X POST http://your-domain.com/api/devices/register \
  -H "Content-Type: application/json" \
  -d '{
    "serial_number": "R2Q5X08F00Y",
    "imei1": "356740000000000",
    "fcm_token": "eXXX...token...XXXe"
  }'
```

## Response Examples

### Success Response
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
        "fcm_token": "eXXX...token...XXXe",
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
      "product": {
        "type": "Mobile Phone",
        "model": "Samsung Galaxy A54",
        "price": "25000.00"
      },
      "status": "active",
      "can_receive_commands": true
    }
  }
}
```

### Error Response (IMEI Not Found)
```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Customer].",
  "code": 500
}
```

This means the IMEI1 was not found in the customers table. The customer must be created first with this IMEI.

### Error Response (Validation Failed)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "imei1": ["The IMEI number is required"],
    "fcm_token": ["The FCM token is required"]
  }
}
```

## Android Implementation Example

```kotlin
class DeviceRegistrationService {
    
    suspend fun registerDevice(context: Context) {
        try {
            // Get device information
            val serialNumber = Build.SERIAL
            val imei1 = getIMEI1(context)
            val fcmToken = FirebaseMessaging.getInstance().token.await()
            
            // Prepare payload
            val payload = JSONObject().apply {
                put("serial_number", serialNumber)
                put("imei1", imei1)
                put("fcm_token", fcmToken)
            }
            
            // Send to server
            val response = apiService.registerDevice(payload)
            
            if (response.isSuccessful) {
                Log.d("Registration", "Device registered successfully")
                // Save registration status locally
                saveRegistrationStatus(true)
            } else {
                Log.e("Registration", "Failed: ${response.errorBody()?.string()}")
            }
            
        } catch (e: Exception) {
            Log.e("Registration", "Error: ${e.message}")
        }
    }
    
    @SuppressLint("HardwareIds", "MissingPermission")
    private fun getIMEI1(context: Context): String {
        val telephonyManager = context.getSystemService(Context.TELEPHONY_SERVICE) as TelephonyManager
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            telephonyManager.imei ?: ""
        } else {
            @Suppress("DEPRECATION")
            telephonyManager.deviceId ?: ""
        }
    }
}
```

## Important Notes

1. **IMEI Must Be Pre-Registered**
   - When creating a customer in the admin dashboard, the IMEI1 field MUST be filled
   - The device will use this IMEI to identify itself during registration
   - Without a matching IMEI, registration will fail

2. **One Device Per Customer**
   - Each customer can only register one device
   - The `serial_number` field is unique in the database
   - Attempting to register again will update the existing record

3. **Customer Must Exist First**
   - The customer record must be created in the system BEFORE the device can register
   - The registration process is: Create Customer → Install App → Auto Register → Send Commands

4. **FCM Token Updates**
   - FCM tokens can expire or change
   - The app should re-register whenever it receives a new FCM token
   - This updates the `fcm_token` in the database

## Workflow Diagram

```
┌─────────────────────┐
│  Admin Dashboard    │
│  Create Customer    │
│  + Enter IMEI1      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Device Installation│
│  (Hidden App)       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Auto Registration  │
│  Send: S/N, IMEI,   │
│        FCM Token    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Server Matches     │
│  IMEI1 → Customer   │
│  Updates Record     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Ready for Commands │
│  Lock, Camera, etc. │
└─────────────────────┘
```

## Testing Checklist

- [ ] Customer created with IMEI1 = "356740000000000"
- [ ] Call register API with that IMEI
- [ ] Verify response contains customer info
- [ ] Check `customers` table - `serial_number` and `fcm_token` updated
- [ ] Try registering with non-existent IMEI - should fail
- [ ] Try registering without fcm_token - validation should fail
- [ ] Send lock command - should work after registration

## Files Updated

✅ `app/Http/Requests/Api/DeviceRegisterRequest.php`  
✅ `app/Services/DeviceCommandService.php`  
✅ `app/Http/Controllers/Api/DeviceController.php`  
✅ `DEVICE_CONTROL_IMPLEMENTATION.md`  
✅ `DEVICE_REGISTRATION_PUBLIC_ENDPOINT.md`  

---

**Ready for Android integration!** 🚀
