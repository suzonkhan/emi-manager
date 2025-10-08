# Device Registration API - Public Endpoint Update

## Changes Made

### Route Update
The device registration endpoint has been moved **outside** the `auth:sanctum` middleware to make it publicly accessible.

**Before:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('devices')->group(function () {
        Route::post('/register', [DeviceController::class, 'register']);
        // ... other routes
    });
});
```

**After:**
```php
// Public device registration (for automatic app installation)
Route::post('/devices/register', [DeviceController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('devices')->group(function () {
        // Only protected device routes here
    });
});
```

## Why This Change?

When your hidden Android app is installed on a customer's device, it needs to register itself with the server immediately. At this point:

1. ❌ No user is logged in
2. ❌ No authentication token exists
3. ❌ The app cannot pass `Authorization: Bearer token` header

Therefore, the registration endpoint **must be public** to allow automatic registration during app installation.

## Current API Endpoint Structure

### Public Endpoints (No Auth Required)
- `POST /api/devices/register` - Device registration

### Protected Endpoints (Auth Required)
- `GET /api/devices/commands` - List available commands
- `GET /api/devices/{customer}` - Get device info
- `GET /api/devices/{customer}/history` - Get command history  
- `POST /api/devices/command/{command}` - Send device commands

## Security Measures

Even though the registration endpoint is public, it's still secure because:

1. **IMEI Validation**: The `imei1` must exist in the `customers` table
   - Only customers with pre-registered IMEI can register devices
   - The IMEI1 must be entered during customer creation
   - Random devices cannot register

2. **One-Time Registration**: Each customer can only have one device registered
   - The `serial_number` field is unique

3. **Data Validation**: All inputs are validated via `DeviceRegisterRequest`

## Recommended Additional Security

To further secure the public endpoint, consider adding an API key:

### 1. Add Environment Variable
```env
# .env
DEVICE_REGISTRATION_API_KEY=your-super-secret-key-12345
```

### 2. Add to Config
```php
// config/app.php
'device_api_key' => env('DEVICE_REGISTRATION_API_KEY'),
```

### 3. Update Validation
```php
// app/Http/Requests/Api/DeviceRegisterRequest.php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'api_key' => ['required', 'string', Rule::in([config('app.device_api_key')])],
        'serial_number' => ['required', 'string', 'max:255'],
        'imei1' => ['required', 'string', 'max:255'],
        'fcm_token' => ['required', 'string'],
    ];
}

public function messages(): array
{
    return [
        'api_key.required' => 'API key is required',
        'api_key.in' => 'Invalid API key',
        // ... other messages
    ];
}
```

### 4. Android App Integration
```kotlin
// In your Android app
val apiKey = "your-super-secret-key-12345" // Store securely, maybe obfuscated

val payload = JSONObject().apply {
    put("api_key", apiKey)
    put("serial_number", Build.SERIAL)
    put("imei1", getIMEI())
    put("fcm_token", FirebaseMessaging.getInstance().token.await())
}

// Send to server
registerDevice(payload)
```

## Testing the Public Endpoint

### Without Authentication
```bash
curl -X POST http://your-domain.com/api/devices/register \
  -H "Content-Type: application/json" \
  -d '{
    "serial_number": "R2Q5X08F00Y",
    "imei1": "356740000000000",
    "fcm_token": "fcm_token_string_here"
  }'
```

### Expected Response (Success)
```json
{
  "success": true,
  "data": {
    "message": "Device registered successfully",
    "device": {
      "customer_id": 1,
      "customer_name": "John Doe",
      "nid_no": "123456789",
      "device": {
        "serial_number": "R2Q5X08F00Y",
        "imei_1": "356740000000000",
        "fcm_token": "fcm_token_string_here",
        "registered": true
      },
      "can_receive_commands": true
    }
  }
}
```

### Expected Response (IMEI Not Found)
```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Customer].",
  "code": 500
}
```

## Rate Limiting Recommendation

Add rate limiting to prevent abuse:

```php
// In routes/api.php
Route::post('/devices/register', [DeviceController::class, 'register'])
    ->middleware('throttle:5,1'); // 5 requests per minute
```

Or create a custom rate limiter:

```php
// In app/Providers/AppServiceProvider.php or RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('device-registration', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->input('imei1') ?: $request->ip())
        ->response(function () {
            return response()->json([
                'success' => false,
                'message' => 'Too many registration attempts. Please try again later.'
            ], 429);
        });
});

// Then in routes/api.php
Route::post('/devices/register', [DeviceController::class, 'register'])
    ->middleware('throttle:device-registration');
```

## Summary

✅ Device registration endpoint is now public  
✅ Automatic registration works during app installation  
✅ Security maintained through NID validation  
✅ All device control commands remain protected  
✅ Recommended additional security measures documented  

The system is ready for Android app integration! 🚀
