# Search & Filter API Documentation

## Overview

This document describes the comprehensive search and filter functionality for Users and Customers in the EMI Manager system. These APIs allow frontend applications to filter and search data based on multiple criteria, matching the UI requirements shown in the application screenshots.

---

## User Search & Filter API

### Endpoint
```
GET /api/users
```

### Description
Retrieve a paginated list of users with optional filtering by multiple criteria. Results are automatically filtered based on the authenticated user's role and hierarchy.

### Authentication
- **Required**: Yes (Bearer Token)
- **Roles**: All authenticated users

### Query Parameters

| Parameter      | Type    | Required | Description                                    | Example                |
|---------------|---------|----------|------------------------------------------------|------------------------|
| `unique_id`   | string  | No       | Filter by unique ID (partial match)            | `?unique_id=DLR001`    |
| `name`        | string  | No       | Filter by name (partial match)                 | `?name=John`           |
| `email`       | string  | No       | Filter by email (partial match)                | `?email=john@mail.com` |
| `phone`       | string  | No       | Filter by phone number (partial match)         | `?phone=01712345678`   |
| `role`        | string  | No       | Filter by role (exact match)                   | `?role=salesman`       |
| `division_id` | integer | No       | Filter by division ID                          | `?division_id=1`       |
| `district_id` | integer | No       | Filter by district ID                          | `?district_id=5`       |
| `upazilla_id` | integer | No       | Filter by upazilla ID                          | `?upazilla_id=10`      |
| `status`      | string  | No       | Filter by status: `active` or `inactive`       | `?status=active`       |
| `per_page`    | integer | No       | Number of results per page (default: 15)       | `?per_page=25`         |

### Available Roles
- `super_admin` - System administrator
- `dealer` - Dealer level user
- `sub_dealer` - Sub-dealer level user
- `salesman` - Salesman level user

### Hierarchy Rules

**Super Admin:**
- Can see all users in the system (except themselves)
- Can filter by any role

**Dealer:**
- Can only see their own created sub-dealers and salesmen
- Cannot see other dealers or super admin

**Sub-Dealer:**
- Can only see their own created salesmen
- Cannot see dealers, other sub-dealers, or super admin

**Salesman:**
- Cannot see any users (no subordinates)

### Request Examples

#### Example 1: Basic User List
```bash
GET /api/users
Authorization: Bearer {token}
```

#### Example 2: Filter by Name
```bash
GET /api/users?name=John
Authorization: Bearer {token}
```

#### Example 3: Filter by Role and Location
```bash
GET /api/users?role=salesman&division_id=1&district_id=5
Authorization: Bearer {token}
```

#### Example 4: Multiple Filters with Pagination
```bash
GET /api/users?role=dealer&status=active&per_page=25
Authorization: Bearer {token}
```

#### Example 5: Search by Phone and Email
```bash
GET /api/users?phone=01712&email=john
Authorization: Bearer {token}
```

### Response Structure

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    "users": [
      {
        "id": 5,
        "unique_id": "DLR001",
        "name": "John Dealer",
        "email": "john@example.com",
        "phone": "01712345678",
        "role": "dealer",
        "is_active": true,
        "present_address": {
          "division": "Dhaka",
          "district": "Dhaka",
          "upazilla": "Dhanmondi"
        },
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
      // ... more users
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 73
    },
    "filters_applied": {
      "role": "dealer",
      "status": "active"
    }
  }
}
```

#### Error Response (401 Unauthorized)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

#### Error Response (500 Internal Server Error)
```json
{
  "success": false,
  "message": "Error message details",
  "data": null
}
```

### Implementation Details

**Service Layer:**
```php
// app/Services/UserService.php
public function searchUsers(array $filters, User $currentUser, int $perPage = 15): LengthAwarePaginator
```

**Repository Layer:**
```php
// app/Repositories/User/UserRepository.php
public function searchUsersWithFilters(array $filters, User $currentUser, int $perPage = 15): LengthAwarePaginator
```

**Features:**
- Automatic hierarchy filtering based on authenticated user
- Partial matching for text fields (unique_id, name, email, phone)
- Exact matching for role and status
- Location filtering through address relationships
- Null filter values are automatically ignored
- Results ordered by latest first

---

## Customer Search & Filter API

### Endpoint
```
GET /api/customers
```

### Description
Retrieve a paginated list of customers with optional filtering by multiple criteria. Results are automatically filtered based on the authenticated user's role and hierarchy - users can only see customers created by themselves or their subordinates.

### Authentication
- **Required**: Yes (Bearer Token)
- **Roles**: All authenticated users

### Query Parameters

| Parameter      | Type    | Required | Description                                    | Example                      |
|---------------|---------|----------|------------------------------------------------|------------------------------|
| `nid_no`      | string  | No       | Filter by NID number (partial match)           | `?nid_no=1234567890`         |
| `name`        | string  | No       | Filter by customer name (partial match)        | `?name=Ahmed`                |
| `email`       | string  | No       | Filter by email (partial match)                | `?email=ahmed@mail.com`      |
| `phone`       | string  | No       | Filter by phone number (partial match)         | `?phone=01812345678`         |
| `mobile`      | string  | No       | Filter by mobile number (partial match)        | `?mobile=01912345678`        |
| `division_id` | integer | No       | Filter by division ID                          | `?division_id=1`             |
| `district_id` | integer | No       | Filter by district ID                          | `?district_id=5`             |
| `upazilla_id` | integer | No       | Filter by upazilla ID                          | `?upazilla_id=10`            |
| `status`      | string  | No       | Filter by status                               | `?status=active`             |
| `product_type`| string  | No       | Filter by product type (partial match)         | `?product_type=Refrigerator` |
| `created_by`  | integer | No       | Filter by creator user ID                      | `?created_by=5`              |
| `dealer_id`   | integer | No       | Filter by dealer ID                            | `?dealer_id=3`               |
| `per_page`    | integer | No       | Number of results per page (default: 15)       | `?per_page=25`               |

### Available Statuses
- `active` - Customer account is active with pending EMIs
- `completed` - All EMIs paid, account completed
- `defaulted` - Customer defaulted on payments
- `cancelled` - Customer account cancelled

### Hierarchy Rules

**Super Admin:**
- Can see all customers in the system
- Can filter by any parameter

**Dealer:**
- Can see customers created by themselves and their subordinates (sub-dealers, salesmen)
- Can filter by any parameter within their hierarchy

**Sub-Dealer:**
- Can see customers created by themselves and their subordinates (salesmen)
- Can filter by any parameter within their hierarchy

**Salesman:**
- Can only see customers they created themselves
- Can filter by any parameter for their customers

### Request Examples

#### Example 1: Basic Customer List
```bash
GET /api/customers
Authorization: Bearer {token}
```

#### Example 2: Filter by NID
```bash
GET /api/customers?nid_no=1234567890
Authorization: Bearer {token}
```

#### Example 3: Filter by Location and Status
```bash
GET /api/customers?division_id=1&district_id=5&status=active
Authorization: Bearer {token}
```

#### Example 4: Filter by Product Type and Dealer
```bash
GET /api/customers?product_type=Refrigerator&dealer_id=3
Authorization: Bearer {token}
```

#### Example 5: Multiple Filters with Pagination
```bash
GET /api/customers?name=Ahmed&status=active&division_id=1&per_page=25
Authorization: Bearer {token}
```

#### Example 6: Search by Phone/Mobile
```bash
GET /api/customers?phone=01812&mobile=01912
Authorization: Bearer {token}
```

### Response Structure

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    "customers": [
      {
        "id": 42,
        "nid_no": "1234567890",
        "name": "Ahmed Hassan",
        "email": "ahmed@example.com",
        "mobile": "01812345678",
        "dealer_customer_id": 15,
        "product_type": "Refrigerator",
        "product_model": "Samsung RT38K50",
        "product_price": 45000,
        "emi_per_month": 3750,
        "emi_duration_months": 12,
        "status": "active",
        "present_address": {
          "division": "Dhaka",
          "district": "Dhaka",
          "upazilla": "Dhanmondi"
        },
        "creator": {
          "id": 8,
          "name": "Salesman John",
          "role": "salesman"
        },
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
      // ... more customers
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 8,
      "per_page": 15,
      "total": 112
    },
    "filters_applied": {
      "status": "active",
      "division_id": "1"
    }
  }
}
```

#### Error Response (401 Unauthorized)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "data": null
}
```

#### Error Response (500 Internal Server Error)
```json
{
  "success": false,
  "message": "Error message details",
  "data": null
}
```

### Implementation Details

**Service Layer:**
```php
// app/Services/CustomerService.php
public function searchCustomersWithFilters(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
```

**Repository Layer:**
```php
// app/Repositories/Customer/CustomerRepository.php
public function searchCustomersWithFilters(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
```

**Features:**
- Automatic hierarchy filtering based on authenticated user
- Partial matching for text fields (nid_no, name, email, phone, mobile, product_type)
- Exact matching for status, created_by, dealer_id
- Location filtering through address relationships
- Null filter values are automatically ignored
- Results ordered by latest first
- Includes related data: addresses, token, creator

---

## Frontend Integration Examples

### JavaScript/Fetch API

#### User Search Example
```javascript
async function searchUsers(filters) {
  const queryParams = new URLSearchParams(filters).toString();
  
  const response = await fetch(`/api/users?${queryParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data;
}

// Usage
const filters = {
  name: 'John',
  role: 'salesman',
  status: 'active',
  per_page: 20
};

const result = await searchUsers(filters);
console.log('Users:', result.users);
console.log('Total:', result.pagination.total);
```

#### Customer Search Example
```javascript
async function searchCustomers(filters) {
  const queryParams = new URLSearchParams(filters).toString();
  
  const response = await fetch(`/api/customers?${queryParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data;
}

// Usage
const filters = {
  nid_no: '1234567890',
  status: 'active',
  division_id: 1,
  per_page: 25
};

const result = await searchCustomers(filters);
console.log('Customers:', result.customers);
console.log('Filters applied:', result.filters_applied);
```

### Axios Example

```javascript
import axios from 'axios';

// User search
const searchUsers = async (filters) => {
  try {
    const response = await axios.get('/api/users', {
      params: filters,
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    });
    return response.data.data;
  } catch (error) {
    console.error('Search error:', error.response.data.message);
    throw error;
  }
};

// Customer search
const searchCustomers = async (filters) => {
  try {
    const response = await axios.get('/api/customers', {
      params: filters,
      headers: {
        'Authorization': `Bearer ${authToken}`
      }
    });
    return response.data.data;
  } catch (error) {
    console.error('Search error:', error.response.data.message);
    throw error;
  }
};
```

### Vue.js Component Example

```vue
<template>
  <div>
    <input v-model="filters.name" placeholder="Search by name..." />
    <select v-model="filters.status">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <button @click="search">Search</button>
    
    <div v-for="user in users" :key="user.id">
      {{ user.name }} - {{ user.role }}
    </div>
    
    <pagination 
      :current="pagination.current_page"
      :total="pagination.total"
      @page-change="loadPage"
    />
  </div>
</template>

<script>
export default {
  data() {
    return {
      filters: {
        name: '',
        status: '',
        role: '',
        per_page: 15
      },
      users: [],
      pagination: {}
    };
  },
  methods: {
    async search() {
      const response = await this.$axios.get('/api/users', {
        params: this.filters
      });
      this.users = response.data.data.users;
      this.pagination = response.data.data.pagination;
    },
    async loadPage(page) {
      this.filters.page = page;
      await this.search();
    }
  }
};
</script>
```

---

## Performance Considerations

### Database Indexes

Ensure the following indexes exist for optimal performance:

**Users Table:**
```sql
-- Already indexed
INDEX idx_users_unique_id (unique_id)
INDEX idx_users_email (email)
INDEX idx_users_phone (phone)
INDEX idx_users_parent_id (parent_id)

-- Consider adding
INDEX idx_users_is_active (is_active)
INDEX idx_users_name (name)
```

**Customers Table:**
```sql
-- Already indexed
INDEX idx_customers_nid_no (nid_no)
INDEX idx_customers_mobile (mobile)
INDEX idx_customers_email (email)
INDEX idx_customers_created_by (created_by)
INDEX idx_customers_dealer_id (dealer_id)

-- Consider adding
INDEX idx_customers_status (status)
INDEX idx_customers_name (name)
INDEX idx_customers_product_type (product_type)
```

**Addresses Table:**
```sql
-- Consider adding for location filtering
INDEX idx_addresses_division_id (division_id)
INDEX idx_addresses_district_id (district_id)
INDEX idx_addresses_upazilla_id (upazilla_id)
```

### Query Optimization Tips

1. **Use Specific Filters**: More filters = faster queries (smaller result set)
2. **Pagination**: Always use pagination, avoid loading all records
3. **Location Filters**: Location filters (division/district/upazilla) require joins, so use them only when necessary
4. **Partial Matching**: LIKE queries with leading wildcards (`%term`) are slower than trailing (`term%`)
5. **Caching**: Consider caching frequently used filter combinations (e.g., "active users", "active customers")

---

## Testing

### Manual Testing with cURL

#### Test User Search
```bash
# Basic list
curl -X GET "http://localhost:8000/api/users" \
  -H "Authorization: Bearer YOUR_TOKEN"

# With filters
curl -X GET "http://localhost:8000/api/users?role=salesman&status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Multiple filters
curl -X GET "http://localhost:8000/api/users?name=John&division_id=1&per_page=25" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Test Customer Search
```bash
# Basic list
curl -X GET "http://localhost:8000/api/customers" \
  -H "Authorization: Bearer YOUR_TOKEN"

# With filters
curl -X GET "http://localhost:8000/api/customers?status=active&division_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# NID search
curl -X GET "http://localhost:8000/api/customers?nid_no=1234567890" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Automated Testing with Pest

Create feature tests to verify search functionality:

```php
// tests/Feature/UserSearchTest.php
it('can search users by name', function () {
    $dealer = User::factory()->create(['role' => 'dealer']);
    $salesman = User::factory()->create([
        'name' => 'John Salesman',
        'parent_id' => $dealer->id,
        'role' => 'salesman'
    ]);
    
    $response = $this->actingAs($dealer)
        ->getJson('/api/users?name=John');
    
    $response->assertOk()
        ->assertJsonPath('data.users.0.name', 'John Salesman')
        ->assertJsonPath('data.filters_applied.name', 'John');
});

it('filters users by role', function () {
    $dealer = User::factory()->create(['role' => 'dealer']);
    
    User::factory()->count(5)->create([
        'parent_id' => $dealer->id,
        'role' => 'salesman'
    ]);
    
    User::factory()->count(3)->create([
        'parent_id' => $dealer->id,
        'role' => 'sub_dealer'
    ]);
    
    $response = $this->actingAs($dealer)
        ->getJson('/api/users?role=salesman');
    
    $response->assertOk()
        ->assertJsonCount(5, 'data.users');
});

// tests/Feature/CustomerSearchTest.php
it('can search customers by NID', function () {
    $salesman = User::factory()->create(['role' => 'salesman']);
    $customer = Customer::factory()->create([
        'nid_no' => '1234567890',
        'created_by' => $salesman->id
    ]);
    
    $response = $this->actingAs($salesman)
        ->getJson('/api/customers?nid_no=1234567890');
    
    $response->assertOk()
        ->assertJsonPath('data.customers.0.nid_no', '1234567890');
});

it('filters customers by status', function () {
    $salesman = User::factory()->create(['role' => 'salesman']);
    
    Customer::factory()->count(5)->create([
        'created_by' => $salesman->id,
        'status' => 'active'
    ]);
    
    Customer::factory()->count(2)->create([
        'created_by' => $salesman->id,
        'status' => 'completed'
    ]);
    
    $response = $this->actingAs($salesman)
        ->getJson('/api/customers?status=active');
    
    $response->assertOk()
        ->assertJsonCount(5, 'data.customers');
});
```

---

## Common Use Cases

### Use Case 1: Find All Active Salesmen in Dhaka
```bash
GET /api/users?role=salesman&status=active&division_id=1
```

### Use Case 2: Find Customers with Overdue Payments
```bash
GET /api/customers?status=active
# Then filter by overdue logic on frontend or use dedicated endpoint
```

### Use Case 3: Find All Refrigerator Customers for a Dealer
```bash
GET /api/customers?product_type=Refrigerator&dealer_id=3
```

### Use Case 4: Search Customer by Partial Phone Number
```bash
GET /api/customers?phone=01812
```

### Use Case 5: Get All Sub-Dealers in Chittagong District
```bash
GET /api/users?role=sub_dealer&district_id=5
```

---

## Migration from Old System

If upgrading from a simpler search system:

**Old Approach:**
```php
// Simple text search only
GET /api/users/search?q=john
```

**New Approach:**
```php
// Comprehensive filtering
GET /api/users?name=john&role=salesman&status=active
```

**Benefits of New System:**
- More precise results
- Multiple filter combinations
- Better performance (indexed columns)
- Consistent API structure
- Filter transparency (filters_applied in response)
- Automatic hierarchy enforcement

---

## Troubleshooting

### No Results Returned
1. Check if filters are too restrictive
2. Verify user has access to the requested data (hierarchy rules)
3. Check if filter values match database values (e.g., exact role names)

### Slow Performance
1. Ensure database indexes exist
2. Reduce number of filters (especially location filters)
3. Use appropriate pagination (don't request too many per page)
4. Consider caching common filter combinations

### Unexpected Results
1. Remember partial matching on text fields uses LIKE with wildcards
2. Check `filters_applied` in response to see what was actually filtered
3. Verify hierarchy rules are being applied correctly
4. Check if you're mixing `phone` and `mobile` filters

---

## Future Enhancements

Potential improvements for future versions:

1. **Date Range Filters**: Filter by creation date range
2. **Sorting Options**: Custom sorting by different fields
3. **Full-Text Search**: Implement full-text search for better text matching
4. **Export Functionality**: Export filtered results to CSV/Excel
5. **Saved Filters**: Allow users to save frequently used filter combinations
6. **Advanced Filters**: Compound filters with AND/OR logic
7. **Filter Suggestions**: Auto-suggest filter values based on existing data

---

## Summary

The Search & Filter API provides comprehensive filtering capabilities for both Users and Customers, with:

✅ **9 filter parameters** for Users (unique_id, name, email, phone, role, division, district, upazilla, status)
✅ **12 filter parameters** for Customers (nid_no, name, email, phone, mobile, division, district, upazilla, status, product_type, created_by, dealer_id)
✅ **Automatic hierarchy enforcement** - users only see authorized data
✅ **Partial matching** on text fields for flexible searching
✅ **Location filtering** through address relationships
✅ **Pagination support** for large datasets
✅ **Filter transparency** - response includes applied filters
✅ **Performance optimized** - uses indexed columns
✅ **Consistent API structure** across both endpoints

This implementation matches the UI requirements shown in the application screenshots and provides a robust, scalable filtering system for the EMI Manager application.
