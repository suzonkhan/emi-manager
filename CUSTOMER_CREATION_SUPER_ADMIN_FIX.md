# Customer Creation - Super Admin Fix

## Issue
When a Super Admin tried to create a customer, the system threw an error:

```
App\Models\Customer::getNextDealerCustomerId(): 
Argument #1 ($dealerId) must be of type int, null given, 
called in C:\laragon\www\emi-manager\app\Services\CustomerService.php on line 46
```

## Root Cause
The `getDealerIdForSalesman()` method returns `null` for super_admin users (as they don't belong to any dealer hierarchy), but the code was unconditionally calling `Customer::getNextDealerCustomerId($dealerId)` which expects a non-null integer.

## Solution
Added a conditional check to handle the super_admin case separately:

### Before
```php
$dealerId = $this->getDealerIdForSalesman($salesman);

// Get next sequential customer ID for this dealer
$dealerCustomerId = Customer::getNextDealerCustomerId($dealerId);
```

### After
```php
$dealerId = $this->getDealerIdForSalesman($salesman);

// Get next sequential customer ID for this dealer (or system-wide for super_admin)
if ($dealerId !== null) {
    $dealerCustomerId = Customer::getNextDealerCustomerId($dealerId);
} else {
    // For super_admin, use system-wide sequential ID
    $dealerCustomerId = Customer::max('dealer_customer_id') + 1;
}
```

## Behavior

### For Super Admin
- `dealer_id`: `null` (not part of any dealer hierarchy)
- `dealer_customer_id`: System-wide sequential ID (highest existing ID + 1)
- Can create customers without hierarchy restrictions

### For Dealers
- `dealer_id`: Their own user ID
- `dealer_customer_id`: Sequential ID within their dealer scope (D001, D002, etc.)

### For Sub-Dealers & Salesmen
- `dealer_id`: Their parent dealer's ID (found recursively)
- `dealer_customer_id`: Sequential ID within their dealer's scope

## Example Customer IDs

### Super Admin Creates Customers
```
Customer 1: dealer_id = null, dealer_customer_id = 1
Customer 2: dealer_id = null, dealer_customer_id = 2
Customer 3: dealer_id = null, dealer_customer_id = 3
```

### Dealer (ID: 5) Creates Customers
```
Customer 1: dealer_id = 5, dealer_customer_id = 1
Customer 2: dealer_id = 5, dealer_customer_id = 2
Customer 3: dealer_id = 5, dealer_customer_id = 3
```

### Another Dealer (ID: 8) Creates Customers
```
Customer 1: dealer_id = 8, dealer_customer_id = 1  // Starts at 1 again (scoped to dealer 8)
Customer 2: dealer_id = 8, dealer_customer_id = 2
Customer 3: dealer_id = 8, dealer_customer_id = 3
```

## Database Schema Support
The `customers` table already supports nullable `dealer_id`:

```php
$table->foreignId('dealer_id')->nullable()->constrained('users');
```

This allows super_admin created customers to have `dealer_id = NULL`.

## Test Case
**Request Body:**
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

**Expected Result:**
- ✅ Customer created successfully
- `dealer_id` = `null` (when created by super_admin)
- `dealer_customer_id` = Next available system-wide ID
- `created_by` = Super admin's user ID
- All addresses created correctly
- Token auto-assigned
- Installments generated

## Related Files Modified
1. `app/Services/CustomerService.php` - Added null check for dealer ID

## Date
October 9, 2025
