# Customer API Improvements - October 9, 2025

## Overview
Enhanced the customer creation API with automatic token assignment, down payment support, and improved device registration.

---

## ✅ Changes Implemented

### 1. **Database Schema Updates**

#### Migration: `2025_10_08_192315_add_down_payment_to_customers_table`
- ✅ Added `down_payment` column (decimal 10,2, default 0)
- ✅ Positioned after `product_price` column

#### Customer Model Updates
- ✅ Added `down_payment` to `$fillable` array
- ✅ Added `down_payment` to casts (decimal:2)
- ✅ Verified `serial_number` already exists in fillable

---

### 2. **Automatic Token Assignment**

#### Previous Behavior:
```json
{
  "token_code": "KVZHKVEQ3KBD",  // ❌ Required input
  "name": "Customer Name",
  ...
}
```

#### New Behavior:
```json
{
  "name": "Customer Name",  // ✅ No token_code needed
  ...
}
```

#### Implementation Details:

**TokenService** (`app/Services/TokenService.php`):
- ✅ Added `getAvailableTokenForUser()` method
- ✅ Super Admin: Gets any available unassigned token
- ✅ Other Roles: Gets tokens assigned to them
- ✅ Returns `null` if no tokens available (throws exception in service)

**CustomerService** (`app/Services/CustomerService.php`):
- ✅ Removed `token_code` parameter requirement
- ✅ Automatically fetches available token for the user
- ✅ Shows helpful error: "No available tokens found. Please request tokens from your administrator."
- ✅ Auto-calculates EMI: `(product_price - down_payment) / emi_duration_months`

---

### 3. **Down Payment & EMI Calculation**

#### Request Body Changes:
```json
{
  "product_price": 85000,
  "down_payment": 15000,        // ✅ NEW: Required field
  "emi_duration_months": 12,
  // emi_per_month is AUTO-CALCULATED (no input needed)
}
```

#### Calculation Logic:
```php
$remainingAmount = $product_price - $down_payment;
$emiPerMonth = round($remainingAmount / $emi_duration_months, 2);

// Example: (85000 - 15000) / 12 = 5,833.33 per month
```

#### Validation Rules:
```php
'down_payment' => [
    'required',
    'numeric',
    'min:0',
    'max:10000000',
],
// emi_per_month validation REMOVED (auto-calculated)
```

---

### 4. **Serial Number Support**

#### Request Body:
```json
{
  "serial_number": "SN123456789",  // ✅ Required, unique
  "imei_1": "123456789012345",     // ✅ Optional
  "imei_2": "543210987654321"      // ✅ Optional
}
```

#### Validation Rules:
```php
'serial_number' => [
    'required',
    'string',
    'max:255',
    Rule::unique('customers'),
],
```

---

### 5. **Device Registration Improvements**

#### Previous Behavior:
```php
// Found customer by IMEI1 only
$customer = Customer::where('imei_1', $imei1)->firstOrFail();

// Updated both serial_number and fcm_token
$customer->update([
    'serial_number' => $serialNumber,
    'fcm_token' => $fcmToken,
]);
```

#### New Behavior:
```php
// Find by serial_number OR imei_1 (more flexible)
$customer = Customer::where('serial_number', $serialNumber)
    ->orWhere('imei_1', $imei1)
    ->firstOrFail();

// Update ONLY fcm_token (serial_number already set during creation)
$customer->update([
    'fcm_token' => $fcmToken,
]);
```

**Benefits:**
- ✅ More flexible device matching
- ✅ Serial number set during customer creation (not device registration)
- ✅ Device registration only updates FCM token

---

### 6. **Postman Collection Updates**

#### File: `48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`

**Create Customer Request Body:**
```json
{
    "name": "রহিম উদ্দিন",
    "email": "rahim@example.com",
    "mobile": "01712345678",
    "nid_no": "1234567890123",
    "present_address": {
        "street_address": "১২৩ মুক্তিযোদ্ধা সরণি",
        "landmark": "ঢাকা মেডিকেল কলেজের কাছে",
        "postal_code": "1000",
        "division_id": 1,
        "district_id": 1,
        "upazilla_id": 1
    },
    "permanent_address": {
        "street_address": "৪৫৬ পুরাতন ঢাকা",
        "landmark": "লালবাগ কেল্লার পাশে",
        "postal_code": "1211",
        "division_id": 1,
        "district_id": 1,
        "upazilla_id": 2
    },
    "product_type": "mobile",
    "product_model": "Samsung Galaxy S23",
    "product_price": 85000,
    "down_payment": 15000,
    "emi_duration_months": 12,
    "imei_1": "123456789012345",
    "imei_2": "543210987654321",
    "serial_number": "SN123456789"
}
```

**Removed Fields:**
- ❌ `token_code` (auto-assigned)
- ❌ `emi_per_month` (auto-calculated)
- ❌ `father_name`, `mother_name`, `date_of_birth`, `occupation`, `monthly_income` (not in current schema)
- ❌ `loan_amount`, `interest_rate`, `guarantor_*` fields (not in current schema)

**Added Fields:**
- ✅ `down_payment` (required)
- ✅ `serial_number` (required, unique)

**Updated Description:**
> "Create a new customer with EMI details. Token is automatically assigned from available tokens. EMI per month is automatically calculated based on (product_price - down_payment) / emi_duration_months."

---

## 📋 Validation Rules Summary

### CreateCustomerRequest

| Field | Rules | Notes |
|-------|-------|-------|
| `product_price` | required, numeric, min:1000, max:10000000 | In BDT |
| `down_payment` | required, numeric, min:0, max:10000000 | **NEW** |
| `emi_duration_months` | required, integer, min:1, max:120 | 1-10 years |
| `serial_number` | required, string, max:255, unique | **NEW** |
| `imei_1` | nullable, string, max:255 | Optional |
| `imei_2` | nullable, string, max:255 | Optional |
| `token_code` | ~~required~~ | **REMOVED** |
| `emi_per_month` | ~~required~~ | **REMOVED** (auto-calculated) |

---

## 🔄 API Flow Changes

### Before:
1. Frontend provides `token_code`
2. Backend validates token
3. Frontend calculates and provides `emi_per_month`
4. Customer created
5. Device registration updates both `serial_number` and `fcm_token`

### After:
1. ~~Frontend provides `token_code`~~ ✅ Auto-assigned
2. Backend finds available token automatically
3. ~~Frontend calculates and provides `emi_per_month`~~ ✅ Auto-calculated
4. Backend calculates: `(product_price - down_payment) / emi_duration_months`
5. Customer created with `serial_number` included
6. Device registration updates only `fcm_token` (serial_number already set)

---

## 🚀 Benefits

1. **Simplified Frontend**
   - No need to input token code
   - No need to calculate EMI manually
   - Less fields to manage

2. **Automatic Token Management**
   - System assigns next available token
   - No manual token selection
   - Clear error when no tokens available

3. **Down Payment Support**
   - Proper financial calculation
   - Remaining amount = Product Price - Down Payment
   - EMI calculated on remaining amount only

4. **Better Device Management**
   - Serial number captured during customer creation
   - Device registration simplified (FCM token only)
   - More flexible device matching (serial OR IMEI)

5. **Data Integrity**
   - Auto-calculated EMI ensures accuracy
   - Unique serial number validation
   - Proper token assignment tracking

---

## 🧪 Testing Recommendations

### Test Case 1: Customer Creation with Available Token
```bash
POST /api/customers
{
  "name": "Test Customer",
  "mobile": "01712345678",
  "nid_no": "1234567890123",
  "product_price": 100000,
  "down_payment": 20000,
  "emi_duration_months": 10,
  "serial_number": "SN001",
  ...
}

Expected:
✅ Token auto-assigned
✅ EMI per month = (100000 - 20000) / 10 = 8000
✅ Customer created successfully
```

### Test Case 2: Customer Creation with No Available Tokens
```bash
POST /api/customers
(Same request as above)

Expected:
❌ 400 Bad Request
{
  "success": false,
  "message": "No available tokens found. Please request tokens from your administrator."
}
```

### Test Case 3: Device Registration by Serial Number
```bash
POST /api/device/register
{
  "serial_number": "SN001",
  "imei1": "123456789012345",
  "fcm_token": "firebase-token-here"
}

Expected:
✅ Customer found by serial_number
✅ Only fcm_token updated
✅ Device registered successfully
```

### Test Case 4: Device Registration by IMEI (Fallback)
```bash
POST /api/device/register
{
  "serial_number": "WRONG_SN",
  "imei1": "123456789012345",
  "fcm_token": "firebase-token-here"
}

Expected:
✅ Customer found by imei_1 (fallback)
✅ Only fcm_token updated
✅ Device registered successfully
```

---

## 📝 Error Messages

### Validation Errors:
- `down_payment.required` → "Down payment is required."
- `down_payment.min` → "Down payment must be at least ৳0."
- `down_payment.max` → "Down payment cannot exceed ৳1,00,00,000."
- `serial_number.required` → "Serial number is required."
- `serial_number.unique` → "This serial number is already registered."

### Business Logic Errors:
- No available tokens → "No available tokens found. Please request tokens from your administrator."
- Invalid role → "User role cannot create customers"
- Device not found → "No query results for model [Customer]"

---

## 🔧 Files Modified

1. ✅ `database/migrations/2025_10_08_192315_add_down_payment_to_customers_table.php` (NEW)
2. ✅ `app/Models/Customer.php`
3. ✅ `app/Http/Requests/Customer/CreateCustomerRequest.php`
4. ✅ `app/Services/CustomerService.php`
5. ✅ `app/Services/TokenService.php`
6. ✅ `app/Services/DeviceCommandService.php`
7. ✅ `postman/collections/48373923-360dc581-66c5-4f28-b174-f14d95dcaa9b.json`

---

## ✨ Summary

All requested changes have been successfully implemented:

✅ **Token Code**: Automatically assigned from available tokens (no frontend input)  
✅ **Down Payment**: Added to database, validation, and calculation logic  
✅ **EMI Calculation**: Automatic: `(product_price - down_payment) / emi_duration_months`  
✅ **Serial Number**: Added to customer creation (required, unique)  
✅ **Device Registration**: Uses serial_number OR imei_1, updates only fcm_token  
✅ **Postman Collection**: Updated request body with new fields  
✅ **Code Quality**: Laravel Pint formatting applied  

**Ready for testing!** 🎉
