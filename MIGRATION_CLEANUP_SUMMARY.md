# Migration Cleanup Summary

## What Was Done

### Problem
In development phase, we created **2 separate migrations** for adding fields to the `customers` table:
1. `2025_10_08_192315_add_down_payment_to_customers_table.php`
2. `2025_10_08_213732_add_wallpaper_fields_to_customers_table.php`

This is not ideal during development because:
- Creates unnecessary migration history
- Makes it harder to track changes
- Not clean for version control
- Can cause issues when sharing with team

---

## Solution Applied

### 1. Modified Main Migration File
**File:** `database/migrations/2025_09_14_224204_create_customers_table.php`

**Added Fields:**
```php
// Product Information section
$table->decimal('down_payment', 10, 2)->default(0);  // Added after product_price

// Device Control States section
$table->string('custom_wallpaper_url')->nullable();   // Added after has_password
```

### 2. Removed Extra Migration Files
**Deleted:**
- ❌ `2025_10_08_192315_add_down_payment_to_customers_table.php`
- ❌ `2025_10_08_213732_add_wallpaper_fields_to_customers_table.php`

### 3. Fresh Database Migration
**Command:** `php artisan migrate:fresh --seed`

**Result:** 
- ✅ Database rebuilt from scratch
- ✅ All seeders ran successfully
- ✅ 238 customers created with installments
- ✅ 1000 tokens distributed across hierarchy
- ✅ 117 users in complete organizational structure

---

## Final Database Schema

### customers Table (30 columns)

```sql
CREATE TABLE customers (
    -- Identity
    id BIGINT UNSIGNED PRIMARY KEY,
    nid_no VARCHAR(100) UNIQUE,
    name VARCHAR(255),
    email VARCHAR(255) NULLABLE,
    mobile VARCHAR(100),
    
    -- Address References
    present_address_id BIGINT UNSIGNED FK → addresses.id,
    permanent_address_id BIGINT UNSIGNED FK → addresses.id,
    
    -- Product & EMI Information
    token_id BIGINT UNSIGNED FK → tokens.id,
    emi_duration_months INT,
    product_type VARCHAR(255),
    product_model VARCHAR(255) NULLABLE,
    product_price DECIMAL(10,2),
    down_payment DECIMAL(10,2) DEFAULT 0,        -- ✨ ADDED
    emi_per_month DECIMAL(8,2),
    
    -- Device Information
    imei_1 VARCHAR(255) NULLABLE,
    imei_2 VARCHAR(255) NULLABLE,
    serial_number VARCHAR(255) UNIQUE NULLABLE,
    fcm_token TEXT NULLABLE,
    
    -- Device Control States
    is_device_locked BOOLEAN DEFAULT false,
    is_camera_disabled BOOLEAN DEFAULT false,
    is_bluetooth_disabled BOOLEAN DEFAULT false,
    is_app_hidden BOOLEAN DEFAULT false,
    has_password BOOLEAN DEFAULT false,
    custom_wallpaper_url VARCHAR(255) NULLABLE,  -- ✨ ADDED
    last_command_sent_at TIMESTAMP NULLABLE,
    
    -- Metadata
    created_by BIGINT UNSIGNED FK → users.id,
    documents JSON NULLABLE,
    status ENUM('active', 'completed', 'defaulted', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    INDEX idx_nid_mobile (nid_no, mobile),
    INDEX idx_created_status (created_by, status),
    INDEX idx_token (token_id)
);
```

---

## Verification

### Database Column List
```php
php artisan tinker --execute="print_r(Schema::getColumnListing('customers'));"
```

**Result (30 columns):**
```
[0] => id
[1] => nid_no
[2] => name
[3] => email
[4] => mobile
[5] => present_address_id
[6] => permanent_address_id
[7] => token_id
[8] => emi_duration_months
[9] => product_type
[10] => product_model
[11] => product_price
[12] => down_payment              ✅ Present
[13] => emi_per_month
[14] => imei_1
[15] => imei_2
[16] => serial_number
[17] => fcm_token
[18] => is_device_locked
[19] => is_camera_disabled
[20] => is_bluetooth_disabled
[21] => is_app_hidden
[22] => has_password
[23] => custom_wallpaper_url     ✅ Present
[24] => last_command_sent_at
[25] => created_by
[26] => documents
[27] => status
[28] => created_at
[29] => updated_at
```

### Migration Files Check
```bash
ls database/migrations/*_add_*_to_customers_table.php
# Result: No files found ✅
```

---

## Customer Model Updated

**File:** `app/Models/Customer.php`

### Fillable Array
```php
protected $fillable = [
    // ... other fields
    'down_payment',           // ✅ Added
    'custom_wallpaper_url',   // ✅ Added
    // ... other fields
];
```

### Casts
```php
protected function casts(): array
{
    return [
        'down_payment' => 'decimal:2',  // Already present
        // custom_wallpaper_url is string, no cast needed
    ];
}
```

---

## API Response Updated

**File:** `app/Http/Resources/DeviceResource.php`

```php
'device_status' => [
    'is_locked' => $this->is_device_locked,
    'is_camera_disabled' => $this->is_camera_disabled,
    'is_bluetooth_disabled' => $this->is_bluetooth_disabled,
    'is_app_hidden' => $this->is_app_hidden,
    'has_password' => $this->has_password,
    'custom_wallpaper_url' => $this->custom_wallpaper_url,  // ✅ Added
    'last_command_sent_at' => $this->last_command_sent_at?->toIso8601String(),
],
```

---

## Service Layer Updated

**File:** `app/Services/DeviceCommandService.php`

### Set Wallpaper
```php
public function setWallpaper(Customer $customer, User $user, string $imageUrl): array
{
    $result = $this->sendCommand(/* ... */);
    
    if ($result['success']) {
        $customer->update(['custom_wallpaper_url' => $imageUrl]);  // ✅ Saves to DB
    }
    
    return $result;
}
```

### Remove Wallpaper
```php
public function removeWallpaper(Customer $customer, User $user): array
{
    $result = $this->sendCommand(/* ... */);
    
    if ($result['success']) {
        $customer->update(['custom_wallpaper_url' => null]);  // ✅ Clears from DB
    }
    
    return $result;
}
```

---

## Best Practices Applied

### ✅ Development Phase
- **Single migration file** for table creation
- **All fields** defined in one place
- **Easy to track** changes in version control
- **Clean history** for team collaboration

### ✅ Production Phase
When you deploy to production later:
- Create **new migration files** for schema changes
- Never modify existing migrations that ran in production
- Use `php artisan make:migration add_field_name_to_table`

### ✅ Migration Strategy

**Development (Current):**
```bash
# Modify existing migration
# Run: php artisan migrate:fresh --seed
```

**Production (Future):**
```bash
# Create new migration
php artisan make:migration add_new_field_to_customers_table

# Run: php artisan migrate (NOT fresh!)
```

---

## Testing

### 1. Get Device Info
```bash
GET /api/devices/{customer_id}
```

**Response includes:**
```json
{
  "device_status": {
    "custom_wallpaper_url": null
  }
}
```

### 2. Set Wallpaper
```bash
POST /api/devices/command/set-wallpaper
{
  "customer_id": 1,
  "image_url": "https://example.com/wallpaper.jpg"
}
```

**Database Update:**
```sql
UPDATE customers 
SET custom_wallpaper_url = 'https://example.com/wallpaper.jpg'
WHERE id = 1;
```

### 3. Remove Wallpaper
```bash
POST /api/devices/command/remove-wallpaper
{
  "customer_id": 1
}
```

**Database Update:**
```sql
UPDATE customers 
SET custom_wallpaper_url = NULL
WHERE id = 1;
```

---

## Summary

### Changes Made
1. ✅ Modified main `create_customers_table` migration
2. ✅ Added `down_payment` field (DECIMAL 10,2)
3. ✅ Added `custom_wallpaper_url` field (VARCHAR nullable)
4. ✅ Deleted 2 extra migration files
5. ✅ Rolled back and re-migrated database fresh
6. ✅ Updated Customer model fillable array
7. ✅ Updated DeviceResource to include wallpaper URL
8. ✅ Updated DeviceCommandService to persist wallpaper state
9. ✅ Verified all 30 columns present in database
10. ✅ Code formatted with Laravel Pint

### Database State
- **Total Tables:** 15 (includes migrations, cache, jobs, etc.)
- **Customers Table:** 30 columns (verified)
- **Total Customers:** 238 (with realistic data)
- **Total Installments:** 5,643 (payment schedules)
- **Total Tokens:** 1,000 (distributed across hierarchy)
- **Total Users:** 117 (complete organizational structure)

### Migration Files
- **Before:** 17 migrations (2 extra for customers table)
- **After:** 15 migrations (clean, consolidated)
- **Status:** ✅ All migrations up-to-date

---

## Conclusion

✅ **Clean migration structure achieved!**  
✅ **All fields in single migration file**  
✅ **Database fully seeded and operational**  
✅ **Device command system complete with wallpaper state tracking**  
✅ **Ready for development and testing**

This is the **correct approach for development phase**. When you move to production, remember to create new migrations for any schema changes instead of modifying existing ones! 🎉
