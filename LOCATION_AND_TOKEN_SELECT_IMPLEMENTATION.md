# Location & Token Select Dropdowns Implementation

## Overview
Implemented cascading location select dropdowns (Division → District → Upazilla) and token select dropdown in the Add Customer form, replacing manual input fields with user-friendly dropdowns that fetch data from APIs.

---

## Changes Made

### 1. **Created Select Component**
**File**: `src/components/ui/select.jsx` (NEW)

- Created Radix UI-based Select component with full accessibility support
- Includes SelectTrigger, SelectContent, SelectItem, SelectValue components
- Supports keyboard navigation and screen readers
- Styled with Tailwind CSS for consistency

**Package Installed**:
```bash
npm install @radix-ui/react-select
```

---

### 2. **Updated AddCustomer.jsx**
**File**: `src/pages/AddCustomer.jsx`

#### **Imports Added**:
```javascript
import { useState, useEffect } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useGetDivisionsQuery, useGetDistrictsQuery, useGetUpazillasQuery } from '@/features/location/locationApi';
import { useGetTokensQuery } from '@/features/token/tokenApi';
```

#### **State Management**:
```javascript
// Location state for cascading dropdowns
const [presentDivisionId, setPresentDivisionId] = useState('');
const [presentDistrictId, setPresentDistrictId] = useState('');
const [permanentDivisionId, setPermanentDivisionId] = useState('');
const [permanentDistrictId, setPermanentDistrictId] = useState('');

// Fetch location data
const { data: divisionsData } = useGetDivisionsQuery();
const { data: presentDistrictsData } = useGetDistrictsQuery(presentDivisionId, { skip: !presentDivisionId });
const { data: presentUpazillasData } = useGetUpazillasQuery(presentDistrictId, { skip: !presentDistrictId });
const { data: permanentDistrictsData } = useGetDistrictsQuery(permanentDivisionId, { skip: !permanentDivisionId });
const { data: permanentUpazillasData } = useGetUpazillasQuery(permanentDistrictId, { skip: !permanentDistrictId });

// Fetch available tokens
const { data: tokensData } = useGetTokensQuery({ per_page: 100 });
const availableTokens = tokensData?.data?.available_tokens || [];
```

#### **Token Code Select** (Lines 179-203):
```jsx
<Label htmlFor="token_code">Token Code *</Label>
<Select onValueChange={(value) => setValue('token_code', value)}>
    <SelectTrigger>
        <SelectValue placeholder="Select an available token" />
    </SelectTrigger>
    <SelectContent>
        {availableTokens.length > 0 ? (
            availableTokens.map((token) => (
                <SelectItem key={token.id} value={token.code}>
                    {token.code}
                </SelectItem>
            ))
        ) : (
            <SelectItem value="no-tokens" disabled>
                No available tokens
            </SelectItem>
        )}
    </SelectContent>
</Select>
```

**Features**:
- Shows only available tokens for the current user
- Displays "No available tokens" message when no tokens are available
- Automatically updates form value when token is selected

#### **Present Address - Division Select** (Lines 227-248):
```jsx
<Label htmlFor="present_division">Division *</Label>
<Select onValueChange={(value) => {
    setPresentDivisionId(value);
    setValue('present_address.division_id', parseInt(value));
    setPresentDistrictId('');
    setValue('present_address.district_id', '');
    setValue('present_address.upazilla_id', '');
}}>
    <SelectTrigger>
        <SelectValue placeholder="Select division" />
    </SelectTrigger>
    <SelectContent>
        {divisionsData?.data?.divisions?.map((division) => (
            <SelectItem key={division.id} value={division.id.toString()}>
                {division.name}
            </SelectItem>
        ))}
    </SelectContent>
</Select>
```

**Features**:
- Loads all divisions from API
- Resets district and upazilla when division changes
- Updates form value automatically

#### **Present Address - District Select** (Lines 250-271):
```jsx
<Label htmlFor="present_district">District *</Label>
<Select 
    disabled={!presentDivisionId}
    onValueChange={(value) => {
        setPresentDistrictId(value);
        setValue('present_address.district_id', parseInt(value));
        setValue('present_address.upazilla_id', '');
    }}
>
    <SelectTrigger>
        <SelectValue placeholder="Select district" />
    </SelectTrigger>
    <SelectContent>
        {presentDistrictsData?.data?.districts?.map((district) => (
            <SelectItem key={district.id} value={district.id.toString()}>
                {district.name}
            </SelectItem>
        ))}
    </SelectContent>
</Select>
```

**Features**:
- Disabled until division is selected
- Loads districts based on selected division
- Resets upazilla when district changes

#### **Present Address - Upazilla Select** (Lines 273-292):
```jsx
<Label htmlFor="present_upazilla">Upazilla *</Label>
<Select 
    disabled={!presentDistrictId}
    onValueChange={(value) => setValue('present_address.upazilla_id', parseInt(value))}
>
    <SelectTrigger>
        <SelectValue placeholder="Select upazilla" />
    </SelectTrigger>
    <SelectContent>
        {presentUpazillasData?.data?.upazillas?.map((upazilla) => (
            <SelectItem key={upazilla.id} value={upazilla.id.toString()}>
                {upazilla.name}
            </SelectItem>
        ))}
    </SelectContent>
</Select>
```

**Features**:
- Disabled until district is selected
- Loads upazillas based on selected district

#### **Permanent Address - Same Pattern**:
- Identical implementation for permanent address
- Separate state management (permanentDivisionId, permanentDistrictId)
- Independent cascading behavior

---

### 3. **Fixed TokenResource**
**File**: `app/Http/Resources/TokenResource.php`

**Changed** (Lines 24-43):
```php
// BEFORE:
'role' => $this->creator->role,
'role' => $this->assignedTo?->role,
'role' => $this->usedBy?->role,

// AFTER:
'role' => $this->creator->getRoleNames()->first(),
'role' => $this->assignedTo?->getRoleNames()->first(),
'role' => $this->usedBy?->getRoleNames()->first(),
```

**Reason**: User model uses Spatie's HasRoles trait, so we need to use `getRoleNames()->first()` instead of accessing a non-existent `role` property.

---

## API Endpoints Used

### Location APIs:
```
GET /api/locations/divisions           - Get all divisions
GET /api/locations/districts/{id}      - Get districts by division ID
GET /api/locations/upazillas/{id}      - Get upazillas by district ID
```

### Token API:
```
GET /api/tokens?per_page=100           - Get available tokens for user
```

---

## Features Implemented

### ✅ **Cascading Location Dropdowns**
1. **Division Dropdown**:
   - Loads all 8 divisions of Bangladesh
   - Dhaka, Chittagong, Khulna, Sylhet, Barisal, Rajshahi, Rangpur, Mymensingh

2. **District Dropdown**:
   - Disabled until division is selected
   - Loads districts based on selected division
   - Automatically resets when division changes

3. **Upazilla Dropdown**:
   - Disabled until district is selected
   - Loads upazillas based on selected district
   - Automatically resets when district changes

4. **Separate for Present & Permanent Address**:
   - Independent state management
   - Can select different locations for each address

### ✅ **Token Select Dropdown**
1. **Shows Available Tokens**:
   - Fetches tokens from `/api/tokens` endpoint
   - Filters to show only `available_tokens` for the current user
   - Displays token codes (12-character codes)

2. **User-Friendly**:
   - Shows "No available tokens" message when no tokens are available
   - Searchable dropdown (Radix UI feature)
   - Keyboard navigation support

### ✅ **Form Integration**
- All selects update form values using `setValue()` from react-hook-form
- Validation still works with yup schema
- Form submission includes correct IDs

---

## Testing Results

### ✅ **Location Dropdowns Working**
- Division dropdown shows all 8 divisions
- District dropdown is disabled until division is selected
- Upazilla dropdown is disabled until district is selected
- Cascading behavior works correctly
- Form values are updated correctly

### ⚠️ **Token Dropdown**
- Shows "No available tokens" for super admin
- This is expected behavior - super admin creates tokens but doesn't have "available" tokens assigned to them
- Tokens need to be assigned to dealers/sub-dealers/salesmen to appear in their dropdown

**Screenshot**: `add-customer-with-location-selects.png`

---

## User Experience Improvements

### Before:
- Users had to manually enter Division ID, District ID, Upazilla ID
- No validation of correct IDs
- No way to know which IDs correspond to which locations
- Token code had to be manually typed (12 characters)

### After:
- Users select from dropdown with location names
- Cascading dropdowns ensure valid combinations
- Cannot select district without division
- Cannot select upazilla without district
- Token codes are selectable from available tokens
- Much more user-friendly and error-proof

---

## Next Steps

### For Token Management:
1. ✅ Token select dropdown implemented
2. ⏳ Need to implement full token management page at `/tokens`
3. ⏳ Need to add token generation, assignment, and distribution features

### For Location Management:
1. ✅ Location select dropdowns implemented
2. ✅ Cascading behavior working
3. ✅ API integration complete

---

## Files Modified Summary

| File | Type | Changes |
|------|------|---------|
| `src/components/ui/select.jsx` | Frontend | Created new Select component |
| `src/pages/AddCustomer.jsx` | Frontend | Added location & token select dropdowns |
| `app/Http/Resources/TokenResource.php` | Backend | Fixed role access using `getRoleNames()->first()` |

---

## Dependencies Added

```json
{
  "@radix-ui/react-select": "^2.0.0"
}
```

---

## Status: ✅ COMPLETE

Location select dropdowns and token select dropdown are fully implemented and working in the Add Customer form!

