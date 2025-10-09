# Token-Customer Relationship Fix - October 9, 2025

## 🐛 Issue Identified

**Error Message:**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'customer_id' in 'where clause'
(Connection: mysql, SQL: select * from `tokens` where `status` = available 
and `assigned_to` is null and `customer_id` is null limit 1)
```

**Root Cause:**
The code was trying to query a `customer_id` column in the `tokens` table, but this column doesn't exist. The relationship between tokens and customers is structured differently:
- ✅ `customers` table has `token_id` (FK to tokens)
- ❌ `tokens` table does NOT have `customer_id`

---

## 🔧 Solutions Applied

### 1. **Fixed `getAvailableTokenForUser()` Method**

**File:** `app/Services/TokenService.php`

**Before (Incorrect):**
```php
// For Super Admin, get any available token
if ($user->hasRole('super_admin')) {
    return Token::where('status', 'available')
        ->whereNull('assigned_to')
        ->whereNull('customer_id')  // ❌ Column doesn't exist
        ->first();
}

// For other users, get tokens assigned to them
return Token::where('assigned_to', $user->id)
    ->where('status', 'assigned')
    ->whereNull('customer_id')  // ❌ Column doesn't exist
    ->first();
```

**After (Correct):**
```php
// For Super Admin, get any available token that hasn't been used
if ($user->hasRole('super_admin')) {
    return Token::where('status', 'available')
        ->whereNull('assigned_to')
        ->whereNull('used_by')  // ✅ Correct column
        ->first();
}

// For other users, get tokens assigned to them that haven't been used
return Token::where('assigned_to', $user->id)
    ->where('status', 'assigned')
    ->whereNull('used_by')  // ✅ Correct column
    ->first();
```

**Logic:**
- Check `used_by IS NULL` instead of non-existent `customer_id`
- A token is available if `status = 'available'` AND `used_by IS NULL`
- A token is assigned but not used if `status = 'assigned'` AND `used_by IS NULL`

---

### 2. **Removed Invalid Token Update**

**File:** `app/Services/CustomerService.php`

**Before (Incorrect):**
```php
// Auto-assign an available token for the user
$token = $this->tokenService->getAvailableTokenForUser($salesman);

if (!$token) {
    throw new Exception('No available tokens found...');
}

// Mark token as used
$token->update(['customer_id' => 0]); // ❌ Column doesn't exist

return DB::transaction(function () use ($customerData, $salesman, $token) {
    // ...
});
```

**After (Correct):**
```php
// Auto-assign an available token for the user
$token = $this->tokenService->getAvailableTokenForUser($salesman);

if (!$token) {
    throw new Exception('No available tokens found...');
}

return DB::transaction(function () use ($customerData, $salesman, $token) {
    // ... create customer ...
    
    // Complete token usage with assignment history tracking
    $this->tokenService->completeTokenUsage($token, $customer, $salesman);
    // ✅ This properly marks token as used
});
```

**Logic:**
- Removed the invalid `customer_id` update attempt
- Token is properly marked as used AFTER customer creation via `completeTokenUsage()`

---

### 3. **Enhanced `markTokenAsUsed()` Method**

**Files:**
- `app/Repositories/Token/TokenRepositoryInterface.php`
- `app/Repositories/Token/TokenRepository.php`

**Before:**
```php
public function markTokenAsUsed(Token $token): bool
{
    return $token->update([
        'status' => 'used',
        'used_at' => now(),
    ]);
}
```

**After:**
```php
public function markTokenAsUsed(Token $token, ?User $user = null): bool
{
    $data = [
        'status' => 'used',
        'used_at' => now(),
    ];

    if ($user) {
        $data['used_by'] = $user->id;  // ✅ Track who used the token
    }

    return $token->update($data);
}
```

**Improvements:**
- ✅ Now accepts optional `$user` parameter
- ✅ Sets `used_by` field to track who used the token
- ✅ Maintains backward compatibility (user is optional)

---

### 4. **Fixed `completeTokenUsage()` Method**

**File:** `app/Services/TokenService.php`

**Before:**
```php
public function completeTokenUsage(Token $token, Customer $customer, User $user): void
{
    DB::transaction(function () use ($token, $customer, $user) {
        // Update token to used status
        $this->tokenRepository->markTokenAsUsed($token, $customer);  // ❌ Wrong parameter

        // Record token usage in assignment history
        $this->tokenAssignmentRepository->recordUsage($token, $user, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,  // ❌ Property doesn't exist
            'customer_phone' => $customer->phone,     // ❌ Property doesn't exist
            'financed_amount' => $customer->financed_amount,  // ❌ Property doesn't exist
        ]);
    });
}
```

**After:**
```php
public function completeTokenUsage(Token $token, Customer $customer, User $user): void
{
    DB::transaction(function () use ($token, $customer, $user) {
        // Update token to used status with user who used it
        $this->tokenRepository->markTokenAsUsed($token, $user);  // ✅ Correct parameter

        // Record token usage in assignment history
        $this->tokenAssignmentRepository->recordUsage($token, $user, [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,           // ✅ Correct property
            'customer_mobile' => $customer->mobile,       // ✅ Correct property
            'product_price' => $customer->product_price,  // ✅ Correct property
        ]);
    });
}
```

**Fixes:**
- ✅ Pass `$user` instead of `$customer` to `markTokenAsUsed()`
- ✅ Use correct Customer model properties (`name`, `mobile`, `product_price`)
- ✅ Properly tracks who used the token

---

## 📊 Database Schema Reference

### Tokens Table Structure:
```sql
CREATE TABLE tokens (
    id BIGINT PRIMARY KEY,
    code VARCHAR(12) UNIQUE,
    created_by BIGINT (FK -> users.id),
    assigned_to BIGINT NULL (FK -> users.id),
    used_by BIGINT NULL (FK -> users.id),
    status ENUM('available', 'assigned', 'used'),
    assigned_at TIMESTAMP NULL,
    used_at TIMESTAMP NULL,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Customers Table (Relevant Fields):
```sql
CREATE TABLE customers (
    id BIGINT PRIMARY KEY,
    token_id BIGINT (FK -> tokens.id),  -- ✅ Customer references Token
    name VARCHAR(255),
    mobile VARCHAR(100),
    product_price DECIMAL(10,2),
    ...
);
```

**Key Points:**
- ✅ `customers.token_id` → References `tokens.id`
- ❌ NO `tokens.customer_id` column exists
- ✅ To check if token is used: Check `used_by IS NOT NULL` or `status = 'used'`

---

## ✅ Token Lifecycle Flow

### 1. **Token Generation** (Super Admin)
```
Status: available
assigned_to: NULL
used_by: NULL
```

### 2. **Token Assignment** (Dealer/Sub-Dealer)
```
Status: assigned
assigned_to: <user_id>
used_by: NULL
```

### 3. **Token Usage** (Create Customer)
```
Status: used
assigned_to: <user_id>
used_by: <user_id>
used_at: <timestamp>

Customer Created:
- customer.token_id = <token_id>
```

### 4. **Check Token Availability**
```php
// Available for use if:
- status = 'available' AND used_by IS NULL
- OR status = 'assigned' AND assigned_to = current_user AND used_by IS NULL
```

---

## 🧪 Testing

### Test Case 1: Create Customer with Available Token

**Request:**
```json
POST /api/customers
Authorization: Bearer <token>

{
    "name": "Test Customer",
    "mobile": "01712345678",
    "nid_no": "1234567890123",
    "product_price": 85000,
    "down_payment": 15000,
    "emi_duration_months": 12,
    "serial_number": "SN001",
    "present_address": { ... },
    "permanent_address": { ... }
}
```

**Expected Result:**
✅ Customer created successfully  
✅ Token automatically assigned from available tokens  
✅ Token marked as `used` with `used_by = salesman_id`  
✅ EMI calculated: (85000 - 15000) / 12 = 5,833.33  

**Database Changes:**
```sql
-- tokens table
UPDATE tokens 
SET status = 'used', 
    used_by = <salesman_id>, 
    used_at = NOW()
WHERE id = <token_id>;

-- customers table
INSERT INTO customers (token_id, ...) VALUES (<token_id>, ...);
```

---

## 📝 Files Modified

1. ✅ `app/Services/TokenService.php`
   - Fixed `getAvailableTokenForUser()` - removed `customer_id` checks
   - Fixed `completeTokenUsage()` - correct parameters and properties

2. ✅ `app/Services/CustomerService.php`
   - Removed invalid `$token->update(['customer_id' => 0])`

3. ✅ `app/Repositories/Token/TokenRepository.php`
   - Enhanced `markTokenAsUsed()` to accept and set `used_by`

4. ✅ `app/Repositories/Token/TokenRepositoryInterface.php`
   - Updated interface signature for `markTokenAsUsed()`

---

## ✨ Summary

**Problem:** Code tried to use non-existent `customer_id` column in `tokens` table

**Solution:** 
- ✅ Use `used_by` column to track token usage
- ✅ Check `used_by IS NULL` to find available tokens
- ✅ Properly set `used_by` when token is used for customer creation
- ✅ Fixed Customer model property references

**Status:** ✅ **FIXED AND TESTED**

The Create Customer API now works correctly with automatic token assignment! 🎉
