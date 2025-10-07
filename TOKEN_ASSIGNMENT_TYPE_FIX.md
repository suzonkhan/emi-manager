# Token Assignment Return Type Fix

## Issue

**Error:**
```
App\Services\TokenService::assignTokens(): Return value must be of type 
Illuminate\Database\Eloquent\Collection, Illuminate\Support\Collection returned
```

## Root Cause

The `assignTokens()` method was using `collect()` which creates an `Illuminate\Support\Collection`, but the method signature declared it would return an `Illuminate\Database\Eloquent\Collection`.

## Solution

Changed from using `collect()` to instantiating a proper Eloquent Collection:

### Before (Wrong)
```php
$assignedTokens = collect(); // Creates Support\Collection

foreach ($availableTokens as $token) {
    // ... assignment logic
    $assignedTokens->push($token->fresh());
}

return $assignedTokens; // Wrong type!
```

### After (Fixed)
```php
use Illuminate\Database\Eloquent\Collection;

$assignedTokens = new Collection(); // Creates Eloquent\Collection

foreach ($availableTokens as $token) {
    // ... assignment logic
    $assignedTokens->push($token->fresh());
}

return $assignedTokens; // Correct type!
```

## Key Differences

### Support Collection
- `Illuminate\Support\Collection`
- General-purpose collection class
- Used for arrays and general data
- Created with `collect()` helper

### Eloquent Collection
- `Illuminate\Database\Eloquent\Collection`
- Specifically for Eloquent models
- Extends Support Collection with model-specific methods
- Created with `new Collection()` or returned from Eloquent queries

## Why This Matters

1. **Type Safety**: PHP strict types ensure method contracts are honored
2. **IDE Support**: Better autocomplete and type hints for model-specific methods
3. **Consistency**: Other methods in TokenService return Eloquent Collections
4. **Model Methods**: Eloquent Collections have model-specific methods like `load()`, `loadMissing()`, etc.

## Testing

After fix, the bulk assignment works correctly:

**Request:**
```bash
POST /api/tokens/assign-bulk
{
  "assignee_id": 5,
  "quantity": 10,
  "notes": "Test assignment"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "tokens": [...],
    "message": "10 tokens assigned to John Doe successfully",
    "assigned_count": 10,
    "token_codes": ["J2EFXLGBMP8K", "V8SZTJN6Z1UZ", ...]
  }
}
```

## Files Modified

- `app/Services/TokenService.php` - Fixed return type in `assignTokens()` method

## Status

✅ **FIXED** - Bulk token assignment now works correctly with proper type safety!
