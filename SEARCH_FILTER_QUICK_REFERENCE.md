# Quick Reference: Search & Filter API

## User Search API

### Endpoint
```
GET /api/users
```

### Quick Filters
```bash
# By name
?name=John

# By role
?role=salesman
?role=dealer
?role=sub_dealer

# By status
?status=active
?status=inactive

# By location
?division_id=1
?district_id=5
?upazilla_id=10

# By contact
?email=john@example.com
?phone=01712345678

# Combine multiple
?role=salesman&status=active&division_id=1&per_page=25
```

---

## Customer Search API

### Endpoint
```
GET /api/customers
```

### Quick Filters
```bash
# By NID
?nid_no=1234567890

# By name
?name=Ahmed

# By phone/mobile
?phone=01812345678
?mobile=01912345678

# By status
?status=active
?status=completed
?status=defaulted
?status=cancelled

# By product
?product_type=Refrigerator

# By location
?division_id=1
?district_id=5
?upazilla_id=10

# By dealer/creator
?dealer_id=3
?created_by=5

# Combine multiple
?status=active&product_type=Refrigerator&division_id=1&per_page=25
```

---

## Response Format

```json
{
  "success": true,
  "data": {
    "users": [...],        // or "customers": [...]
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 73
    },
    "filters_applied": {
      "role": "salesman",
      "status": "active"
    }
  }
}
```

---

## Common Scenarios

### Find active salesmen in Dhaka
```
GET /api/users?role=salesman&status=active&division_id=1
```

### Find customer by NID
```
GET /api/customers?nid_no=1234567890
```

### View dealer's active customers
```
GET /api/customers?dealer_id=3&status=active
```

### Search by partial phone
```
GET /api/customers?phone=01812
```

### Find refrigerator customers
```
GET /api/customers?product_type=Refrigerator&status=active
```

---

## Filter Types

| Filter Type | Match | Example |
|------------|-------|---------|
| Text (name, email, phone) | Partial (LIKE %term%) | `?name=John` finds "John Doe" |
| ID (role, status, division) | Exact (=) | `?role=salesman` only salesmen |
| Null/Empty | Ignored | `?name=` has no effect |

---

## Hierarchy Rules

| Role | Can See |
|------|---------|
| Super Admin | All users/customers |
| Dealer | Sub-dealers, salesmen, their customers |
| Sub-Dealer | Salesmen, their customers |
| Salesman | Only their own customers |

---

## Pagination

```
?per_page=25     # Items per page (default: 15)
?page=2          # Page number (Laravel auto-handles)
```

---

## Frontend Example (JavaScript)

```javascript
// Search users
const response = await fetch('/api/users?role=salesman&status=active', {
  headers: { 'Authorization': 'Bearer ' + token }
});
const data = await response.json();
console.log(data.data.users);

// Search customers
const response = await fetch('/api/customers?status=active&division_id=1', {
  headers: { 'Authorization': 'Bearer ' + token }
});
const data = await response.json();
console.log(data.data.customers);
```

---

## Testing Commands

```bash
# Test user search
curl -X GET "http://localhost:8000/api/users?name=John" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test customer search
curl -X GET "http://localhost:8000/api/customers?status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Full Documentation

For complete details, see:
- `SEARCH_FILTER_API_DOCUMENTATION.md` - Comprehensive API docs
- `SEARCH_FILTER_IMPLEMENTATION_SUMMARY.md` - Implementation details
