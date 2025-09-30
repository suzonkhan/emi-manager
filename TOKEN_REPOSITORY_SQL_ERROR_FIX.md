# Token Repository SQL Error Fix

## Problem
The token API was throwing SQL errors when trying to fetch tokens:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'role' in 'where clause'
SQL: select count(*) as aggregate from `tokens` where (`created_by` = 2 or `assigned_to` = 2 
or exists (select * from `users` where `tokens`.`created_by` = `users`.`id` and `role` in (sub_dealer, salesman)))
```

## Root Cause
The `TokenRepository` was trying to access a non-existent `role` column in the `users` table. The application uses Spatie's Laravel Permission package, which stores roles in a separate `model_has_roles` table, not as a column in the `users` table.

**Problematic Code Locations:**
1. Line 200: `whereIn('role', $assignableRoles)` in `getTokensQueryForUser()`
2. Line 148: `$user->role` and `$token->creator->role` in `canUserAccessToken()`
3. Line 158: Selecting `role` column in `getTokensWithAssignmentChain()`
4. Lines 183-188: Checking `$user->role` in `getTokensQueryForUser()`

---

## Solution

### 1. Fixed `getTokensQueryForUser()` Method (Lines 181-209)

**Before:**
```php
protected function getTokensQueryForUser(User $user): Builder
{
    $query = Token::query();

    // If user has no role, return empty query
    if (!$user->role) {
        return $query->whereRaw('1 = 0');
    }

    // Super admin can see all tokens
    if ($user->role === 'super_admin') {
        return $query;
    }

    // Get assignable roles for the user
    $assignableRoles = $this->roleHierarchyService->getAssignableRolesByRole($user->role);

    // User can see tokens created by users in their hierarchy or assigned to them
    return $query->where(function (Builder $q) use ($user, $assignableRoles) {
        $q->where('created_by', $user->id)
          ->orWhere('assigned_to', $user->id)
          ->orWhereHas('creator', function (Builder $creatorQuery) use ($assignableRoles) {
              $creatorQuery->whereIn('role', $assignableRoles);  // ❌ WRONG
          });
    });
}
```

**After:**
```php
protected function getTokensQueryForUser(User $user): Builder
{
    $query = Token::query();

    // Get user's role
    $userRole = $user->getRoleNames()->first();  // ✅ Use Spatie method

    // If user has no role, return empty query
    if (!$userRole) {
        return $query->whereRaw('1 = 0');
    }

    // Super admin can see all tokens
    if ($userRole === 'super_admin') {
        return $query;
    }

    // Get assignable roles for the user
    $assignableRoles = $this->roleHierarchyService->getAssignableRolesByRole($userRole);

    // User can see tokens created by users in their hierarchy or assigned to them
    return $query->where(function (Builder $q) use ($user, $assignableRoles) {
        $q->where('created_by', $user->id)
          ->orWhere('assigned_to', $user->id)
          ->orWhereHas('creator', function (Builder $creatorQuery) use ($assignableRoles) {
              $creatorQuery->role($assignableRoles);  // ✅ Use Spatie scope
          });
    });
}
```

**Changes:**
- Changed `$user->role` to `$user->getRoleNames()->first()`
- Changed `whereIn('role', $assignableRoles)` to `role($assignableRoles)` (Spatie scope)

---

### 2. Fixed `canUserAccessToken()` Method (Lines 134-155)

**Before:**
```php
public function canUserAccessToken(User $user, Token $token): bool
{
    // User can access tokens they created
    if ($token->created_by === $user->id) {
        return true;
    }

    // User can access tokens assigned to them
    if ($token->assigned_to === $user->id) {
        return true;
    }

    // User can access tokens if they are in the hierarchy below the creator
    if ($token->creator) {
        return $this->roleHierarchyService->canAssignRole($user->role, $token->creator->role);  // ❌ WRONG
    }

    return false;
}
```

**After:**
```php
public function canUserAccessToken(User $user, Token $token): bool
{
    // User can access tokens they created
    if ($token->created_by === $user->id) {
        return true;
    }

    // User can access tokens assigned to them
    if ($token->assigned_to === $user->id) {
        return true;
    }

    // User can access tokens if they are in the hierarchy below the creator
    if ($token->creator) {
        $creatorRole = $token->creator->getRoleNames()->first();  // ✅ Use Spatie method
        if ($creatorRole) {
            return $this->roleHierarchyService->canAssignRole($user, $creatorRole);  // ✅ Pass User object
        }
    }

    return false;
}
```

**Changes:**
- Changed `$user->role` to pass the entire `$user` object
- Changed `$token->creator->role` to `$token->creator->getRoleNames()->first()`

---

### 3. Fixed `getTokensWithAssignmentChain()` Method (Lines 157-166)

**Before:**
```php
public function getTokensWithAssignmentChain(User $user): Collection
{
    return $this->getTokensQueryForUser($user)
        ->with([
            'creator:id,name,email,role',      // ❌ WRONG - role column doesn't exist
            'assignedTo:id,name,email,role',   // ❌ WRONG
            'usedBy:id,name,email,role'        // ❌ WRONG
        ])
        ->get();
}
```

**After:**
```php
public function getTokensWithAssignmentChain(User $user): Collection
{
    return $this->getTokensQueryForUser($user)
        ->with([
            'creator:id,name,email',      // ✅ Removed role column
            'assignedTo:id,name,email',   // ✅ Removed role column
            'usedBy:id,name,email'        // ✅ Removed role column
        ])
        ->get();
}
```

**Changes:**
- Removed `,role` from all eager loading selects
- Roles will be fetched via `getRoleNames()` when needed in the resource

---

## Files Modified

| File | Lines Changed | Description |
|------|---------------|-------------|
| `app/Repositories/Token/TokenRepository.php` | 134-155 | Fixed `canUserAccessToken()` to use `getRoleNames()` |
| `app/Repositories/Token/TokenRepository.php` | 157-166 | Removed `role` from eager loading in `getTokensWithAssignmentChain()` |
| `app/Repositories/Token/TokenRepository.php` | 181-209 | Fixed `getTokensQueryForUser()` to use Spatie methods |

---

## Testing Results

### ✅ **Token API Working**
- `GET /api/tokens?per_page=100` - Returns 200 OK
- No SQL errors in console
- Available tokens are fetched correctly
- Token dropdown in Add Customer page works

### ✅ **Location API Working**
- `GET /api/locations/divisions` - Returns 200 OK
- Division dropdown shows all 8 divisions
- No errors in console

---

## Related Fixes

This is the same type of error that was previously fixed in:
1. **CustomerRepository** - Fixed `whereIn('role', ...)` to use `role()` scope
2. **TokenResource** - Fixed `$this->creator->role` to use `getRoleNames()->first()`
3. **CustomerDetailResource** - Fixed creator role access

**Pattern to Remember:**
- ❌ Never use `$user->role` directly
- ✅ Always use `$user->getRoleNames()->first()`
- ❌ Never use `whereIn('role', ...)` on users table
- ✅ Always use `role($roles)` scope (Spatie method)
- ❌ Never select `role` column in eager loading
- ✅ Fetch roles separately using `getRoleNames()` when needed

---

## Status: ✅ FIXED

All SQL errors related to the non-existent `role` column in the `tokens` API have been resolved!

The token select dropdown in the Add Customer page is now working correctly without any SQL errors.

