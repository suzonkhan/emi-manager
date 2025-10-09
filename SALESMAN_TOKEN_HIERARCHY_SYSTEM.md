# Salesman Token Hierarchy System

## Overview
Salesmen **DO NOT receive token assignments**. They automatically use tokens from their parent hierarchy (dealer or sub-dealer). This eliminates unnecessary token management overhead and simplifies the workflow.

## The Problem We Solved

### ❌ Old System (Complex & Redundant)
```
Super Admin generates tokens
  ↓
Assigns to Dealer
  ↓
Dealer assigns to Sub-Dealer
  ↓
Sub-Dealer assigns to Salesman  ← Unnecessary extra step!
  ↓
Salesman uses token
```

### ✅ New System (Simplified & Efficient)
```
Super Admin generates tokens
  ↓
Assigns to Dealer
  ↓
Dealer assigns to Sub-Dealer
  ↓
Salesman automatically uses parent's tokens  ← Direct access!
```

## How It Works

### Token Access Rules

#### 1. **Super Admin**
- Can generate tokens
- Can use any available token
- Assigns tokens to dealers

#### 2. **Dealer**
- Receives token assignments from Super Admin
- Can use their assigned tokens
- Can assign tokens to Sub-Dealers

#### 3. **Sub-Dealer**
- Receives token assignments from Dealer
- Can use their assigned tokens
- ~~Cannot assign tokens to Salesmen~~ (removed!)

#### 4. **Salesman** ⭐ NEW BEHAVIOR
- **Does NOT receive token assignments**
- **Automatically uses tokens from parent hierarchy**
- When creating a customer, the system:
  1. Checks if salesman has a parent
  2. Looks for available tokens in parent's pool
  3. Uses the parent's token automatically
  4. Records the usage under the salesman's name

## Code Implementation

### TokenService.php - Smart Token Lookup

```php
/**
 * Get an available token for the user (auto-assign)
 * Salesmen automatically use tokens from their parent (dealer or sub-dealer)
 */
public function getAvailableTokenForUser(User $user): ?Token
{
    // For Salesman: Use tokens from parent hierarchy
    if ($user->hasRole('salesman')) {
        return $this->getTokenFromParentHierarchy($user);
    }

    // For Dealer/Sub-Dealer: Use their assigned tokens
    return Token::where('assigned_to', $user->id)
        ->where('status', 'assigned')
        ->whereNull('used_by')
        ->first();
}

/**
 * Recursively find available tokens from parent hierarchy
 */
private function getTokenFromParentHierarchy(User $user): ?Token
{
    if (!$user->parent_id) {
        return null;
    }

    $parent = User::find($user->parent_id);
    
    // Try to get token from this parent
    $token = Token::where('assigned_to', $parent->id)
        ->where('status', 'assigned')
        ->whereNull('used_by')
        ->first();

    if ($token) {
        return $token;
    }

    // If parent is also sub-dealer/salesman, check their parent
    if (in_array($parent->role, ['salesman', 'sub_dealer'])) {
        return $this->getTokenFromParentHierarchy($parent);
    }

    return null;
}
```

### Validation - Prevent Token Assignment to Salesmen

```php
/**
 * Assign token from current user to target user
 */
public function assignToken(User $fromUser, User $toUser, string $tokenCode): Token
{
    // Salesmen cannot receive token assignments
    if ($toUser->hasRole('salesman')) {
        throw new Exception('Salesmen cannot receive token assignments. They automatically use tokens from their parent (dealer or sub-dealer).');
    }
    
    // ... rest of assignment logic
}
```

## Real-World Examples

### Example 1: Basic Hierarchy

```
Dealer A (has 100 tokens)
  └─ Sub-Dealer A1 (assigned 50 tokens)
      └─ Salesman A1-1 (NO tokens assigned)
```

**When Salesman A1-1 creates a customer:**
1. System checks: "Does Salesman A1-1 have assigned tokens?" → No
2. System looks up: "Who is Salesman A1-1's parent?" → Sub-Dealer A1
3. System checks: "Does Sub-Dealer A1 have available tokens?" → Yes (50 tokens)
4. System uses one of Sub-Dealer A1's tokens
5. Token is marked as used by Salesman A1-1
6. Customer is created successfully

### Example 2: Multi-Level Hierarchy

```
Dealer B (has 200 tokens)
  └─ Sub-Dealer B1 (assigned 80 tokens)
      └─ Salesman B1-1 (uses parent's tokens)
      └─ Salesman B1-2 (uses parent's tokens)
  └─ Sub-Dealer B2 (assigned 120 tokens)
      └─ Salesman B2-1 (uses parent's tokens)
```

**Token Pool Sharing:**
- Salesman B1-1 and B1-2 both use Sub-Dealer B1's 80 tokens
- Salesman B2-1 uses Sub-Dealer B2's 120 tokens
- Each sub-dealer's token pool is shared among their salesmen

### Example 3: Direct Dealer → Salesman

```
Dealer C (has 150 tokens)
  └─ Salesman C1 (parent_id = Dealer C's ID)
```

**When Salesman C1 creates a customer:**
1. System looks for parent → Dealer C
2. Uses one of Dealer C's 150 tokens directly
3. No sub-dealer in between, but works the same way

## API Behavior

### ❌ Attempting to Assign Tokens to Salesman (Blocked)

**Request:**
```http
POST /api/tokens/assign
Authorization: Bearer {sub_dealer_token}
Content-Type: application/json

{
  "assignee_id": 25,  // Salesman ID
  "token_code": "ABC123DEF456"
}
```

**Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Salesmen cannot receive token assignments. They automatically use tokens from their parent (dealer or sub-dealer).",
  "data": null
}
```

### ✅ Salesman Creates Customer (Automatic Token Usage)

**Request:**
```http
POST /api/customers
Authorization: Bearer {salesman_token}
Content-Type: application/json

{
  "nid_no": "1234567890",
  "name": "John Doe",
  "mobile": "01712345678",
  // ... other customer data
}
```

**What Happens Behind the Scenes:**
1. `CustomerService::createCustomer()` is called
2. `TokenService::getAvailableTokenForUser($salesman)` is called
3. System finds salesman's parent (sub-dealer or dealer)
4. Uses one of parent's available tokens
5. Customer is created with that token
6. Token is marked as used by the salesman

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 123,
      "name": "John Doe",
      "token": {
        "code": "XYZ789ABC123",
        "assigned_to": {
          "id": 20,
          "name": "Sub-Dealer A1",
          "role": "sub_dealer"
        },
        "used_by": {
          "id": 25,
          "name": "Salesman A1-1",
          "role": "salesman"
        }
      }
    }
  }
}
```

### ✅ Get Assignable Users (Salesmen Excluded)

**Request:**
```http
GET /api/tokens/assignable-users
Authorization: Bearer {dealer_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 15,
        "name": "Sub-Dealer A1",
        "email": "subdealer.a1@emimanager.com",
        "role": "sub_dealer"
      },
      {
        "id": 16,
        "name": "Sub-Dealer A2",
        "email": "subdealer.a2@emimanager.com",
        "role": "sub_dealer"
      }
      // Note: Salesmen are NOT included in this list
    ]
  }
}
```

## Benefits

### 1. **Simplified Workflow** ⚡
- **Before:** 4 steps (Super Admin → Dealer → Sub-Dealer → Salesman)
- **After:** 3 steps (Super Admin → Dealer → Sub-Dealer), salesmen use automatically

### 2. **Reduced Management Overhead** 📉
- No need to track individual salesman token assignments
- No need to redistribute tokens among salesmen
- Parent manages token pool for all their salesmen

### 3. **Better Resource Utilization** 🎯
- Tokens are pooled at sub-dealer/dealer level
- More efficient distribution
- Less waste from unused individual allocations

### 4. **Clearer Responsibility** 👥
- Sub-dealers manage token inventory for their team
- Salesmen focus on sales, not token management
- Clear accountability hierarchy

### 5. **Automatic Scaling** 📈
- Add new salesmen without redistributing tokens
- Remove salesmen without token cleanup
- Dynamic team size handling

## Database Changes

### Token Assignment Flow

**Old System:**
```sql
-- Token assigned to salesman
UPDATE tokens 
SET assigned_to = 25,  -- Salesman ID
    status = 'assigned'
WHERE code = 'ABC123';

-- Record assignment
INSERT INTO token_assignments (token_id, to_user_id, to_role, action)
VALUES (1, 25, 'salesman', 'assigned');
```

**New System:**
```sql
-- Token remains assigned to sub-dealer
UPDATE tokens 
SET assigned_to = 20,  -- Sub-Dealer ID (parent)
    status = 'assigned'
WHERE code = 'ABC123';

-- When salesman uses it:
UPDATE tokens
SET used_by = 25,  -- Salesman ID
    status = 'used'
WHERE code = 'ABC123';

-- Record usage with parent context
INSERT INTO token_assignments (token_id, from_user_id, action, metadata)
VALUES (1, 25, 'used', '{"parent_id": 20, "parent_role": "sub_dealer"}');
```

## Seeder Updates

### TokenManagementSeeder.php

**Old Flow:**
```php
// 1. Assign to dealers
$dealerTokens = $this->assignTokensToDealers(...);

// 2. Assign to sub-dealers
$subDealerTokens = $this->assignTokensToSubDealers(...);

// 3. Assign to salesmen ❌ REMOVED
$salesmenTokens = $this->assignTokensToSalesmen(...);

// 4. Mark some as used
$this->markSomeTokensAsUsed($salesmenTokens);
```

**New Flow:**
```php
// 1. Assign to dealers
$dealerTokens = $this->assignTokensToDealers(...);

// 2. Assign to sub-dealers
$subDealerTokens = $this->assignTokensToSubDealers(...);

// 3. Salesmen use parent's tokens automatically ✅
$this->command->info('Salesmen will use tokens from their parent hierarchy');

// 4. Mark some as used (by salesmen, from parent pool)
$this->markSomeTokensAsUsedByHierarchy($subDealerTokens, $salesmen);
```

## Testing

### Test 1: Salesman Creates Customer

```php
it('allows salesman to create customer using parent tokens', function () {
    $dealer = User::factory()->create()->assignRole('dealer');
    $subDealer = User::factory()->create(['parent_id' => $dealer->id])->assignRole('sub_dealer');
    $salesman = User::factory()->create(['parent_id' => $subDealer->id])->assignRole('salesman');
    
    // Assign tokens to sub-dealer (NOT salesman)
    $token = Token::factory()->create([
        'assigned_to' => $subDealer->id,
        'status' => 'assigned',
    ]);
    
    // Salesman creates customer
    $response = $this->actingAs($salesman)->postJson('/api/customers', [
        // customer data...
    ]);
    
    $response->assertCreated();
    
    // Verify token was used by salesman
    expect($token->fresh()->used_by)->toBe($salesman->id);
    expect($token->fresh()->status)->toBe('used');
});
```

### Test 2: Cannot Assign Tokens to Salesman

```php
it('prevents assigning tokens to salesmen', function () {
    $subDealer = User::factory()->create()->assignRole('sub_dealer');
    $salesman = User::factory()->create(['parent_id' => $subDealer->id])->assignRole('salesman');
    
    $token = Token::factory()->create([
        'assigned_to' => $subDealer->id,
        'status' => 'assigned',
    ]);
    
    $response = $this->actingAs($subDealer)->postJson('/api/tokens/assign', [
        'assignee_id' => $salesman->id,
        'token_code' => $token->code,
    ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Salesmen cannot receive token assignments. They automatically use tokens from their parent (dealer or sub-dealer).'
        ]);
});
```

### Test 3: Recursive Hierarchy Lookup

```php
it('finds tokens from grandparent if parent has none', function () {
    $dealer = User::factory()->create()->assignRole('dealer');
    $subDealer = User::factory()->create(['parent_id' => $dealer->id])->assignRole('sub_dealer');
    $salesman = User::factory()->create(['parent_id' => $subDealer->id])->assignRole('salesman');
    
    // Assign tokens to dealer (not sub-dealer)
    $token = Token::factory()->create([
        'assigned_to' => $dealer->id,
        'status' => 'assigned',
    ]);
    
    // Salesman creates customer
    $response = $this->actingAs($salesman)->postJson('/api/customers', [
        // customer data...
    ]);
    
    $response->assertCreated();
    
    // Verify token was found from grandparent (dealer)
    expect($token->fresh()->used_by)->toBe($salesman->id);
});
```

## Migration Guide

### For Existing Systems

If you already have tokens assigned to salesmen, run this migration:

```php
// Remove token assignments from salesmen, return to parent
Token::whereIn('assigned_to', User::role('salesman')->pluck('id'))
    ->where('status', 'assigned')
    ->chunk(100, function ($tokens) {
        foreach ($tokens as $token) {
            $salesman = User::find($token->assigned_to);
            
            if ($salesman && $salesman->parent_id) {
                // Return token to parent
                $token->update([
                    'assigned_to' => $salesman->parent_id,
                ]);
                
                Log::info("Token {$token->code} returned from salesman {$salesman->name} to parent");
            }
        }
    });
```

## Summary

| Aspect | Old System | New System |
|--------|-----------|-----------|
| Token Assignment | Super Admin → Dealer → Sub-Dealer → **Salesman** | Super Admin → Dealer → Sub-Dealer |
| Salesman Access | Receives direct assignments | Uses parent's tokens automatically |
| Management Overhead | High (4 levels) | Low (3 levels) |
| Token Pool | Individual per salesman | Shared at parent level |
| Flexibility | Limited by individual allocations | Dynamic shared pool |
| API Endpoints | Can assign to salesmen | Blocks salesman assignments |
| Seeding | 4-step assignment chain | 3-step with automatic usage |

## Related Files

- `app/Services/TokenService.php` - Core token logic with hierarchy lookup
- `app/Http/Controllers/Api/TokenController.php` - API endpoints with validation
- `database/seeders/TokenManagementSeeder.php` - Updated seeding logic
- `app/Services/CustomerService.php` - Uses automatic token lookup

---

**Last Updated:** October 9, 2025  
**Version:** 2.0 (Salesman Hierarchy System)
