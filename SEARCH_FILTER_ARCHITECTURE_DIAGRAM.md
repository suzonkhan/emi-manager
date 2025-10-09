# Search & Filter System Architecture

## System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                           FRONTEND APPLICATION                       │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  User Filter Form                                           │   │
│  │  • Name input field                                         │   │
│  │  • Role dropdown (salesman, dealer, sub_dealer)            │   │
│  │  • Status toggle (active/inactive)                          │   │
│  │  • Division/District/Upazilla selects                      │   │
│  │  • Email/Phone search fields                                │   │
│  │  [Apply Filters Button]                                     │   │
│  └────────────────────────────────────────────────────────────┘   │
│                               │                                      │
│                               │ Build Query Params                   │
│                               ▼                                      │
│  GET /api/users?role=salesman&status=active&division_id=1          │
└──────────────────────────────┬───────────────────────────────────────┘
                               │
                               │ HTTP Request with Bearer Token
                               │
┌──────────────────────────────▼───────────────────────────────────────┐
│                    LARAVEL BACKEND APPLICATION                       │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  ROUTE LAYER (routes/api.php)                              │   │
│  │  Route::get('/users', [UserController::class, 'index'])    │   │
│  │  • Authentication middleware (sanctum)                      │   │
│  │  • Rate limiting                                            │   │
│  └──────────────────────────┬─────────────────────────────────┘   │
│                             │                                       │
│                             ▼                                       │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  CONTROLLER LAYER (UserController.php)                     │   │
│  │  public function index(Request $request)                   │   │
│  │  {                                                          │   │
│  │    // 1. Extract query parameters                          │   │
│  │    $filters = [                                             │   │
│  │      'name' => $request->input('name'),                    │   │
│  │      'role' => $request->input('role'),                    │   │
│  │      'status' => $request->input('status'),                │   │
│  │      'division_id' => $request->input('division_id'),      │   │
│  │      // ... more filters                                   │   │
│  │    ];                                                       │   │
│  │                                                             │   │
│  │    // 2. Remove null values                                │   │
│  │    $filters = array_filter($filters, fn($v) => $v !== null);│   │
│  │                                                             │   │
│  │    // 3. Call service layer                                │   │
│  │    $users = $this->userService->searchUsers(               │   │
│  │      $filters,                                              │   │
│  │      $request->user(),                                      │   │
│  │      $perPage                                               │   │
│  │    );                                                       │   │
│  │                                                             │   │
│  │    // 4. Return JSON response                              │   │
│  │    return $this->success([...]);                           │   │
│  │  }                                                          │   │
│  └──────────────────────────┬─────────────────────────────────┘   │
│                             │                                       │
│                             ▼                                       │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  SERVICE LAYER (UserService.php)                           │   │
│  │  public function searchUsers(                              │   │
│  │    array $filters,                                          │   │
│  │    User $currentUser,                                       │   │
│  │    int $perPage = 15                                        │   │
│  │  ): LengthAwarePaginator                                   │   │
│  │  {                                                          │   │
│  │    // Business logic layer                                 │   │
│  │    // Delegates to repository for data access              │   │
│  │    return $this->userRepository->searchUsersWithFilters(   │   │
│  │      $filters,                                              │   │
│  │      $currentUser,                                          │   │
│  │      $perPage                                               │   │
│  │    );                                                       │   │
│  │  }                                                          │   │
│  └──────────────────────────┬─────────────────────────────────┘   │
│                             │                                       │
│                             ▼                                       │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  REPOSITORY LAYER (UserRepository.php)                     │   │
│  │  public function searchUsersWithFilters(...)               │   │
│  │  {                                                          │   │
│  │    $query = User::query()->with([...]);                    │   │
│  │                                                             │   │
│  │    // 1. Apply hierarchy filtering                         │   │
│  │    if ($currentUser->hasRole('super_admin')) {             │   │
│  │      $query->where('id', '!=', $currentUser->id);          │   │
│  │    } else {                                                 │   │
│  │      $query->where('parent_id', $currentUser->id);         │   │
│  │    }                                                        │   │
│  │                                                             │   │
│  │    // 2. Apply text filters (partial match)                │   │
│  │    if (!empty($filters['name'])) {                         │   │
│  │      $query->where('name', 'like', '%'.$filters['name'].'%');│   │
│  │    }                                                        │   │
│  │                                                             │   │
│  │    // 3. Apply exact filters                               │   │
│  │    if (!empty($filters['role'])) {                         │   │
│  │      $query->role($filters['role']);                       │   │
│  │    }                                                        │   │
│  │                                                             │   │
│  │    // 4. Apply location filters (join)                     │   │
│  │    if (!empty($filters['division_id'])) {                  │   │
│  │      $query->whereHas('presentAddress', fn($q) =>          │   │
│  │        $q->where('division_id', $filters['division_id'])   │   │
│  │      );                                                     │   │
│  │    }                                                        │   │
│  │                                                             │   │
│  │    // 5. Return paginated results                          │   │
│  │    return $query->latest()->paginate($perPage);            │   │
│  │  }                                                          │   │
│  └──────────────────────────┬─────────────────────────────────┘   │
│                             │                                       │
│                             ▼                                       │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  DATABASE LAYER (MySQL)                                    │   │
│  │                                                             │   │
│  │  SELECT users.*                                             │   │
│  │  FROM users                                                 │   │
│  │  LEFT JOIN addresses ON users.present_address_id = addresses.id│
│  │  WHERE users.parent_id = ?                                 │   │
│  │    AND users.name LIKE '%John%'                            │   │
│  │    AND users.is_active = 1                                 │   │
│  │    AND addresses.division_id = 1                           │   │
│  │  ORDER BY users.created_at DESC                            │   │
│  │  LIMIT 15 OFFSET 0                                         │   │
│  │                                                             │   │
│  │  Uses indexes: idx_users_parent_id, idx_users_name,       │   │
│  │                idx_addresses_division_id                   │   │
│  └──────────────────────────┬─────────────────────────────────┘   │
│                             │                                       │
│                             │ Returns Result Set                    │
│                             │                                       │
└──────────────────────────────┴───────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         JSON RESPONSE                                │
│  {                                                                   │
│    "success": true,                                                  │
│    "data": {                                                         │
│      "users": [                                                      │
│        {                                                             │
│          "id": 8,                                                    │
│          "name": "John Salesman",                                   │
│          "role": "salesman",                                         │
│          "is_active": true,                                          │
│          "present_address": {                                        │
│            "division": "Dhaka",                                      │
│            "district": "Dhaka"                                       │
│          }                                                           │
│        }                                                             │
│      ],                                                              │
│      "pagination": {                                                 │
│        "current_page": 1,                                            │
│        "total": 73,                                                  │
│        "per_page": 15                                                │
│      },                                                              │
│      "filters_applied": {                                            │
│        "name": "John",                                               │
│        "status": "active",                                           │
│        "division_id": "1"                                            │
│      }                                                               │
│    }                                                                 │
│  }                                                                   │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Data Flow Comparison

### User Search Flow
```
Frontend Form → Controller → Service → Repository → Database
     ↓              ↓           ↓          ↓           ↓
Query Params → Extract    → Delegate → Build Query → Execute
               Validate              Apply Filters   Use Indexes
               Sanitize              Hierarchy       Return Data
                                     Rules
```

### Customer Search Flow
```
Frontend Form → Controller → Service → Repository → Database
     ↓              ↓           ↓          ↓           ↓
Query Params → Extract    → Delegate → Build Query → Execute
               Validate              Apply Filters   Use Indexes
               Sanitize              Hierarchy       Return Data
                                     Rules
                                     Dealer Rules
```

---

## Filter Processing Pipeline

```
┌─────────────────────────────────────────────────────────────────────┐
│  1. RECEIVE FILTERS                                                 │
│  Raw query parameters from frontend                                 │
│  ?name=John&role=salesman&status=active&division_id=1             │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  2. EXTRACT & STRUCTURE                                             │
│  $filters = [                                                       │
│    'name' => 'John',                                                │
│    'role' => 'salesman',                                            │
│    'status' => 'active',                                            │
│    'division_id' => 1                                               │
│  ]                                                                  │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  3. SANITIZE (Remove Nulls)                                         │
│  $filters = array_filter($filters, fn($v) => $v !== null)          │
│  Empty/null values removed automatically                            │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  4. APPLY HIERARCHY RULES                                           │
│  Super Admin: No restrictions                                       │
│  Dealer: parent_id = current_user.id                                │
│  Sub-Dealer: parent_id = current_user.id                            │
│  Salesman: No users (can't manage anyone)                           │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  5. APPLY TEXT FILTERS (Partial Match)                              │
│  WHERE name LIKE '%John%'                                           │
│  WHERE email LIKE '%john@example.com%'                              │
│  WHERE phone LIKE '%01712%'                                         │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  6. APPLY EXACT FILTERS                                             │
│  WHERE role = 'salesman' (using Spatie role() method)               │
│  WHERE is_active = 1 (when status = 'active')                       │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  7. APPLY LOCATION FILTERS (Join)                                   │
│  LEFT JOIN addresses ON users.present_address_id = addresses.id     │
│  WHERE addresses.division_id = 1                                    │
│  WHERE addresses.district_id = 5                                    │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  8. ORDER & PAGINATE                                                │
│  ORDER BY created_at DESC                                           │
│  LIMIT 15 OFFSET 0                                                  │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  9. EAGER LOAD RELATIONSHIPS                                        │
│  ->with(['roles', 'presentAddress.division', ...])                  │
│  Prevents N+1 query problems                                        │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  10. RETURN RESULTS                                                 │
│  LengthAwarePaginator with:                                         │
│  • Filtered users/customers                                         │
│  • Pagination metadata                                              │
│  • Applied filters info                                             │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Hierarchy Filtering Logic

```
┌───────────────────────────────────────────────────────────────┐
│  Current User Role Check                                      │
└───────────────────┬───────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
┌──────────────┐        ┌──────────────┐
│ Super Admin? │        │ Other Roles? │
└──────┬───────┘        └──────┬───────┘
       │                       │
       ▼                       ▼
┌──────────────────┐    ┌──────────────────────┐
│ Show All Users   │    │ Show Only Direct     │
│ (except self)    │    │ Subordinates         │
│                  │    │ WHERE parent_id =    │
│ WHERE id !=      │    │   current_user.id    │
│   current_user.id│    │                      │
└──────────────────┘    └──────────────────────┘
```

### Example Hierarchy Tree

```
Super Admin (ID: 1)
  ├─ Dealer A (ID: 2) [parent_id = 1]
  │   ├─ Sub-Dealer A1 (ID: 5) [parent_id = 2]
  │   │   └─ Salesman A1a (ID: 8) [parent_id = 5]
  │   └─ Sub-Dealer A2 (ID: 6) [parent_id = 2]
  └─ Dealer B (ID: 3) [parent_id = 1]
      └─ Sub-Dealer B1 (ID: 7) [parent_id = 3]
          └─ Salesman B1a (ID: 9) [parent_id = 7]
```

**What Each User Can See:**

| User | Can See Users |
|------|---------------|
| Super Admin (1) | All users: 2, 3, 5, 6, 7, 8, 9 |
| Dealer A (2) | Sub-dealers & salesmen: 5, 6, 8 |
| Dealer B (3) | Sub-dealers & salesmen: 7, 9 |
| Sub-Dealer A1 (5) | Salesmen: 8 |
| Sub-Dealer B1 (7) | Salesmen: 9 |
| Salesman (8 or 9) | No users (empty result) |

---

## Filter Match Type Decision Tree

```
┌─────────────────────────────────────┐
│  Filter Type?                       │
└────────────┬────────────────────────┘
             │
    ┌────────┴────────┐
    │                 │
    ▼                 ▼
┌─────────┐      ┌──────────┐
│  Text   │      │  ID/Enum │
│ Filter? │      │  Filter? │
└────┬────┘      └────┬─────┘
     │                │
     ▼                ▼
┌─────────────────┐  ┌─────────────────┐
│ Partial Match   │  │ Exact Match     │
│ LIKE '%term%'   │  │ WHERE col = val │
│                 │  │                 │
│ Examples:       │  │ Examples:       │
│ • name          │  │ • role          │
│ • email         │  │ • status        │
│ • phone         │  │ • division_id   │
│ • nid_no        │  │ • district_id   │
│ • product_type  │  │ • created_by    │
└─────────────────┘  └─────────────────┘
```

---

## Performance Optimization Flow

```
┌──────────────────────────────────────────────────────────────┐
│  Query Optimization Strategy                                 │
└──────────────────────────┬───────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              │                         │
              ▼                         ▼
┌──────────────────────┐    ┌──────────────────────┐
│  Use Database        │    │  Use Eager Loading   │
│  Indexes             │    │  to Avoid N+1        │
│                      │    │                      │
│ • unique_id         │    │ ->with([             │
│ • email             │    │   'roles',           │
│ • phone             │    │   'presentAddress'   │
│ • nid_no            │    │ ])                   │
│ • mobile            │    │                      │
│ • parent_id         │    │                      │
│ • created_by        │    │                      │
│ • dealer_id         │    │                      │
└──────────────────────┘    └──────────────────────┘
```

### Query Execution Plan

```
1. Use index on parent_id for hierarchy filter (fastest)
2. Use index on name/email/phone for text filters
3. Join addresses table for location filters
4. Apply remaining filters
5. Order by created_at DESC
6. Apply pagination LIMIT/OFFSET
7. Eager load relationships in one query
```

---

## Error Handling Flow

```
┌──────────────────────────────────────────────────────────────┐
│  Request Received                                            │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
              ┌────────────────┐
              │ Authentication │
              │ Valid?         │
              └────┬───────┬───┘
                   │       │
              Yes  │       │ No
                   │       │
                   ▼       ▼
          ┌──────────┐  ┌──────────────┐
          │ Process  │  │ Return 401   │
          │ Request  │  │ Unauthorized │
          └────┬─────┘  └──────────────┘
               │
               ▼
      ┌────────────────┐
      │ Try Execute    │
      │ Search         │
      └────┬───────┬───┘
           │       │
      OK   │       │ Error
           │       │
           ▼       ▼
  ┌──────────┐  ┌──────────────┐
  │ Return   │  │ Catch Error  │
  │ 200 OK   │  │ Return 500   │
  └──────────┘  └──────────────┘
```

---

## Complete Implementation Stack

```
┌─────────────────────────────────────────────────────────────────┐
│  LAYER 8: Frontend UI (React/Vue/Angular)                       │
│  • Filter form components                                       │
│  • Data table with filters                                      │
│  • Pagination controls                                          │
└─────────────────────────┬───────────────────────────────────────┘
                          │ AJAX/Fetch
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 7: API Routes (routes/api.php)                           │
│  Route::get('/users', [UserController::class, 'index'])         │
│  Route::get('/customers', [CustomerController::class, 'index']) │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 6: Controller (UserController, CustomerController)       │
│  • Extract query params                                         │
│  • Build filters array                                          │
│  • Call service layer                                           │
│  • Return JSON response                                         │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 5: Service (UserService, CustomerService)                │
│  • Business logic                                               │
│  • Validation                                                   │
│  • Delegate to repository                                       │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 4: Repository (UserRepository, CustomerRepository)       │
│  • Build Eloquent queries                                       │
│  • Apply filters                                                │
│  • Hierarchy filtering                                          │
│  • Return paginated results                                     │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 3: Eloquent ORM                                          │
│  • Query builder                                                │
│  • Relationships                                                │
│  • Eager loading                                                │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 2: Database Connection (MySQL PDO)                       │
│  • Execute SQL queries                                          │
│  • Use prepared statements                                      │
│  • Connection pooling                                           │
└─────────────────────────┬───────────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────────┐
│  LAYER 1: MySQL Database                                        │
│  • Tables: users, customers, addresses                          │
│  • Indexes for optimization                                     │
│  • Foreign keys for integrity                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Summary

This architecture diagram shows:
- Complete request/response flow
- Data processing pipeline
- Hierarchy filtering logic
- Filter type decision tree
- Performance optimization strategy
- Error handling flow
- Complete implementation stack

All layers work together to provide:
✅ Fast, secure, hierarchy-aware filtering
✅ Flexible search with multiple criteria
✅ Optimized database queries
✅ Clean separation of concerns
✅ Maintainable, testable code
