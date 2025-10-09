# Dealer Customer ID System

## 🎯 Purpose

Each **dealer's customers** now have **separate sequential IDs** starting from 1. This means:

- **Dealer A's customers**: 1, 2, 3, 4, 5...
- **Dealer B's customers**: 1, 2, 3, 4, 5...
- **Dealer C's customers**: 1, 2, 3, 4, 5...

Each dealer tracks their own customers independently!

---

## 📊 Database Schema

### New Columns Added to `customers` Table

| Column | Type | Description |
|--------|------|-------------|
| `dealer_id` | foreignId | References the dealer (user with role='dealer') |
| `dealer_customer_id` | unsignedInteger | Sequential customer number per dealer (1, 2, 3...) |

### Unique Constraint

```sql
UNIQUE KEY `unique_dealer_customer` (`dealer_id`, `dealer_customer_id`)
```

This ensures:
- ✅ Dealer A can have customer #1
- ✅ Dealer B can also have customer #1
- ❌ Dealer A CANNOT have two customers with #1

---

## 🔄 How It Works

### Customer Creation Flow

```
1. User (Dealer/Sub-Dealer/Salesman) creates a customer
   ↓
2. System identifies the dealer:
   - If creator is Dealer → dealer_id = creator.id
   - If creator is Sub-Dealer → finds parent Dealer
   - If creator is Salesman → finds parent Dealer
   ↓
3. System gets next sequential number for that dealer
   - Query: SELECT MAX(dealer_customer_id) WHERE dealer_id = X
   - Next ID = Max + 1 (or 1 if no customers exist)
   ↓
4. Customer created with:
   - dealer_id = X
   - dealer_customer_id = next sequential number
```

### Example Scenario

**Dealer Hierarchy:**
```
Super Admin
  ├─ Dealer A
  │   ├─ Sub-Dealer A1
  │   │   └─ Salesman A1-S1
  │   └─ Sub-Dealer A2
  │       └─ Salesman A2-S1
  └─ Dealer B
      ├─ Sub-Dealer B1
      │   └─ Salesman B1-S1
      └─ Salesman B-S1
```

**Customer Creation:**

| Created By | Dealer ID | Customer ID | Formatted ID |
|------------|-----------|-------------|--------------|
| Dealer A | Dealer A | 1 | D-001 |
| Sub-Dealer A1 | Dealer A | 2 | D-002 |
| Salesman A1-S1 | Dealer A | 3 | D-003 |
| Sub-Dealer A2 | Dealer A | 4 | D-004 |
| Salesman A2-S1 | Dealer A | 5 | D-005 |
| **Dealer B** | **Dealer B** | **1** | **D-001** |
| Sub-Dealer B1 | Dealer B | 2 | D-002 |
| Salesman B1-S1 | Dealer B | 3 | D-003 |

Notice:
- ✅ Dealer A has customers 1-5
- ✅ Dealer B ALSO has customers 1-3 (separate sequence!)

---

## 💻 Code Implementation

### 1. Customer Model (`app/Models/Customer.php`)

```php
// New relationship
public function dealer(): BelongsTo
{
    return $this->belongsTo(User::class, 'dealer_id');
}

// Get formatted dealer customer ID
public function getFormattedDealerCustomerId(): ?string
{
    if (!$this->dealer_customer_id) {
        return null;
    }
    
    return 'D-' . str_pad($this->dealer_customer_id, 3, '0', STR_PAD_LEFT);
}

// Get next ID for a dealer
public static function getNextDealerCustomerId(int $dealerId): int
{
    $lastCustomer = self::where('dealer_id', $dealerId)
        ->orderBy('dealer_customer_id', 'desc')
        ->first();

    return $lastCustomer ? $lastCustomer->dealer_customer_id + 1 : 1;
}
```

### 2. CustomerService (`app/Services/CustomerService.php`)

```php
public function createCustomer(array $customerData, User $salesman): Customer
{
    // Determine the dealer for this customer
    $dealerId = $this->getDealerIdForSalesman($salesman);
    
    // Get next sequential customer ID for this dealer
    $dealerCustomerId = Customer::getNextDealerCustomerId($dealerId);
    
    // Create customer with dealer info
    $customer = $this->customerRepository->create([
        // ... other fields
        'dealer_id' => $dealerId,
        'dealer_customer_id' => $dealerCustomerId,
        // ... other fields
    ]);
}

// Find the dealer for any user
private function getDealerIdForSalesman(User $salesman): ?int
{
    if ($salesman->role === 'dealer') {
        return $salesman->id;
    }

    if (in_array($salesman->role, ['sub_dealer', 'salesman'])) {
        return $this->findDealerParent($salesman);
    }

    return null;
}

// Recursively find dealer parent
private function findDealerParent(User $user): ?int
{
    if (!$user->parent_id) {
        return null;
    }

    $parent = User::find($user->parent_id);
    
    if ($parent->role === 'dealer') {
        return $parent->id;
    }

    return $this->findDealerParent($parent);
}
```

---

## 📡 API Response

### Customer List Response

```json
{
  "success": true,
  "data": {
    "customers": [
      {
        "id": 245,
        "dealer_customer_id": 5,
        "formatted_dealer_customer_id": "D-005",
        "nid_no": "1234567890",
        "name": "John Doe",
        "mobile": "01712345678",
        "product_type": "Mobile",
        "emi_per_month": "5833.00",
        "status": "active"
      }
    ]
  }
}
```

### Customer Detail Response

```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 245,
      "dealer_customer_id": 5,
      "formatted_dealer_customer_id": "D-005",
      "name": "John Doe",
      // ... other fields
      "dealer": {
        "id": 10,
        "name": "Dealer A",
        "phone": "01712000000"
      }
    }
  }
}
```

---

## 🎨 Display Examples

### Mobile App UI

```
┌─────────────────────────────┐
│ Customer #D-005             │
│ John Doe                    │
│ 01712-345678                │
│                             │
│ Dealer: Dealer A            │
│ EMI: ৳5,833/month          │
└─────────────────────────────┘
```

### Dashboard/Admin Panel

```
Customer ID: D-005
Database ID: 245
Dealer: Dealer A (ID: 10)
```

---

## 🔍 Querying Customers

### Get All Customers for a Dealer

```php
$dealerCustomers = Customer::where('dealer_id', $dealerId)
    ->orderBy('dealer_customer_id', 'asc')
    ->get();
```

### Get Specific Customer by Dealer ID

```php
$customer = Customer::where('dealer_id', $dealerId)
    ->where('dealer_customer_id', 5)
    ->first();
```

### Search Within Dealer's Customers

```php
$customers = Customer::where('dealer_id', $dealerId)
    ->where('name', 'LIKE', "%{$searchTerm}%")
    ->orderBy('dealer_customer_id', 'asc')
    ->get();
```

---

## ⚠️ Important Notes

### 1. Existing Customers

Existing customers in the database will have:
- `dealer_id` = `NULL`
- `dealer_customer_id` = `NULL`

**Solution Options:**

**Option A: Leave as-is** (customers created before this feature)
```sql
-- They'll show without dealer customer IDs
SELECT * FROM customers WHERE dealer_customer_id IS NULL;
```

**Option B: Migrate existing customers**
```php
// Run this ONCE to assign dealer IDs to existing customers
DB::transaction(function () {
    $customers = Customer::whereNull('dealer_customer_id')->get();
    
    foreach ($customers as $customer) {
        $creator = $customer->creator;
        
        // Find dealer for this customer
        $dealerId = $this->getDealerIdForUser($creator);
        
        if ($dealerId) {
            // Get next ID for this dealer
            $nextId = Customer::getNextDealerCustomerId($dealerId);
            
            // Update customer
            $customer->update([
                'dealer_id' => $dealerId,
                'dealer_customer_id' => $nextId,
            ]);
        }
    }
});
```

### 2. Super Admin Customers

If **Super Admin** creates a customer directly:
- `dealer_id` = `NULL`
- `dealer_customer_id` = `NULL`

This is by design - super admin customers don't belong to any dealer.

### 3. Concurrent Customer Creation

The system uses database transactions to prevent race conditions:

```php
DB::transaction(function () {
    $dealerCustomerId = Customer::getNextDealerCustomerId($dealerId);
    // Creates customer with this ID
});
```

If two salesmen create customers simultaneously:
- ✅ Transaction ensures sequential IDs
- ✅ No duplicate dealer_customer_id
- ✅ Unique constraint prevents conflicts

---

## 🧪 Testing

### Test Case 1: Dealer Creates Customer

```php
// Arrange
$dealer = User::factory()->create(['role' => 'dealer']);

// Act
$customer1 = Customer::create([/* ... */, 'created_by' => $dealer->id]);
$customer2 = Customer::create([/* ... */, 'created_by' => $dealer->id]);

// Assert
$this->assertEquals(1, $customer1->dealer_customer_id);
$this->assertEquals(2, $customer2->dealer_customer_id);
$this->assertEquals($dealer->id, $customer1->dealer_id);
```

### Test Case 2: Sub-Dealer Creates Customer

```php
// Arrange
$dealer = User::factory()->create(['role' => 'dealer']);
$subDealer = User::factory()->create([
    'role' => 'sub_dealer',
    'parent_id' => $dealer->id
]);

// Act
$customer = Customer::create([/* ... */, 'created_by' => $subDealer->id]);

// Assert
$this->assertEquals($dealer->id, $customer->dealer_id);
$this->assertEquals(1, $customer->dealer_customer_id);
```

### Test Case 3: Multiple Dealers

```php
// Arrange
$dealerA = User::factory()->create(['role' => 'dealer']);
$dealerB = User::factory()->create(['role' => 'dealer']);

// Act
$customerA1 = Customer::create([/* ... */, 'created_by' => $dealerA->id]);
$customerB1 = Customer::create([/* ... */, 'created_by' => $dealerB->id]);
$customerA2 = Customer::create([/* ... */, 'created_by' => $dealerA->id]);

// Assert
$this->assertEquals(1, $customerA1->dealer_customer_id);
$this->assertEquals(1, $customerB1->dealer_customer_id); // Also starts at 1!
$this->assertEquals(2, $customerA2->dealer_customer_id);
```

---

## 📋 Migration Details

### Migration File

```bash
2025_10_09_010737_add_dealer_customer_id_to_customers_table.php
```

### Applied Changes

```sql
ALTER TABLE `customers` 
ADD COLUMN `dealer_id` BIGINT UNSIGNED NULL AFTER `created_by`,
ADD COLUMN `dealer_customer_id` INT UNSIGNED NULL AFTER `dealer_id`,
ADD UNIQUE KEY `unique_dealer_customer` (`dealer_id`, `dealer_customer_id`),
ADD INDEX `customers_dealer_customer_id_index` (`dealer_customer_id`),
ADD CONSTRAINT `customers_dealer_id_foreign` 
    FOREIGN KEY (`dealer_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE;
```

---

## 🎉 Benefits

### For Dealers
✅ Easy customer tracking per dealer  
✅ Simple customer numbering (1, 2, 3...)  
✅ Independent from global IDs  

### For Sub-Dealers
✅ All customers belong to parent dealer  
✅ Consistent numbering system  

### For Salesmen
✅ Customers automatically assigned to dealer  
✅ No manual dealer selection needed  

### For Users
✅ Clear "Customer #5" instead of "Customer #12834"  
✅ Formatted display: "D-001", "D-002"  
✅ Easy to reference in conversations  

---

## 🔄 Future Enhancements

### Custom Prefixes per Dealer

```php
// Instead of "D-001", dealers could have custom prefixes:
Dealer A: "A-001", "A-002", "A-003"
Dealer B: "B-001", "B-002", "B-003"
```

### Multiple Numbering Schemes

```php
// Different schemes for different product types:
Mobile customers: "M-001", "M-002"
Laptop customers: "L-001", "L-002"
```

### Year-based Reset

```php
// Reset counter every year:
2025: "D-001", "D-002"
2026: "D-001", "D-002" (restarts)
```

---

## 📞 Support

For questions or issues:
- **Email**: support@emimanager.com
- **Phone**: +880 1712-XXXXXX
- **Documentation**: https://docs.emimanager.com

---

## ✅ Summary

🎯 **What**: Each dealer has separate customer numbering starting from 1  
🔧 **How**: New `dealer_id` and `dealer_customer_id` columns  
📱 **Display**: Shows as "D-001", "D-002", etc. in API responses  
🚀 **Auto**: Automatically assigned when creating customers  
✨ **Result**: Cleaner, simpler customer tracking per dealer  

**Example:**
- Dealer A: Customers 1, 2, 3, 4, 5
- Dealer B: Customers 1, 2, 3 (independent!)
- Dealer C: Customers 1, 2, 3, 4, 5, 6, 7, 8

Each dealer's world is separate! 🎉
