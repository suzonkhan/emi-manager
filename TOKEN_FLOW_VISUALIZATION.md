# Token Flow Visualization

## Token Assignment Flow (New System)

```
┌─────────────────┐
│  Super Admin    │
│  (Generates)    │
└────────┬────────┘
         │ generates 1000 tokens
         │
         ▼
┌─────────────────────────┐
│  Available Token Pool   │
│  Status: "available"    │
│  assigned_to: null      │
└────────┬────────────────┘
         │ assigns to dealers
         │
         ▼
┌─────────────────┐
│   Dealer A      │  ◄─── Receives 200 tokens
│   (Manages)     │       assigned_to: dealer_id
└────────┬────────┘       Status: "assigned"
         │
         │ assigns to sub-dealers
         │
         ├──────────────────┬──────────────────┐
         ▼                  ▼                  ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│Sub-Dealer A1 │   │Sub-Dealer A2 │   │Sub-Dealer A3 │
│  80 tokens   │   │  60 tokens   │   │  60 tokens   │
└──────┬───────┘   └──────┬───────┘   └──────┬───────┘
       │                  │                  │
       │ ⚡ Automatic     │ ⚡ Automatic     │ ⚡ Automatic
       │   Access         │   Access         │   Access
       │                  │                  │
       ▼                  ▼                  ▼
┌────────────┐     ┌────────────┐     ┌────────────┐
│Salesman A1-│     │Salesman A2-│     │Salesman A3-│
│     1      │     │     1      │     │     1      │
└────────────┘     └────────────┘     └────────────┘
       │                  │                  │
       │                  │                  │
       │ uses parent's    │                  │
       │ token to create  │                  │
       │ customer         │                  │
       ▼                  ▼                  ▼
┌────────────┐     ┌────────────┐     ┌────────────┐
│ Customer 1 │     │ Customer 2 │     │ Customer 3 │
│Token used  │     │Token used  │     │Token used  │
│by:Salesman │     │by:Salesman │     │by:Salesman │
└────────────┘     └────────────┘     └────────────┘
```

## Token Status Lifecycle

```
┌──────────────┐
│  GENERATED   │  Super Admin creates token
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  AVAILABLE   │  Status: "available"
│              │  assigned_to: null
└──────┬───────┘  created_by: super_admin_id
       │
       │ Super Admin assigns to Dealer
       ▼
┌──────────────┐
│  ASSIGNED    │  Status: "assigned"
│  (Dealer)    │  assigned_to: dealer_id
└──────┬───────┘
       │
       │ Dealer assigns to Sub-Dealer
       ▼
┌──────────────┐
│  ASSIGNED    │  Status: "assigned"
│(Sub-Dealer)  │  assigned_to: sub_dealer_id
└──────┬───────┘
       │
       │ Salesman creates customer
       │ (uses parent's token automatically)
       ▼
┌──────────────┐
│    USED      │  Status: "used"
│              │  assigned_to: sub_dealer_id
└──────────────┘  used_by: salesman_id ⭐
                  customer_id: 123
```

## Hierarchy Lookup (Salesman Creates Customer)

```
                    ┌─────────────────────────────┐
                    │  Salesman A1-1 clicks       │
                    │  "Create Customer"          │
                    └──────────────┬──────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │ CustomerService calls:       │
                    │ getAvailableTokenForUser()   │
                    └──────────────┬───────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │ Check: Is user a salesman?   │
                    │ ✓ Yes                        │
                    └──────────────┬───────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │ getTokenFromParentHierarchy()│
                    └──────────────┬───────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │ Find parent_id               │
                    │ parent_id = Sub-Dealer A1    │
                    └──────────────┬───────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │ Query tokens:                │
                    │ WHERE assigned_to = parent_id│
                    │   AND status = 'assigned'    │
                    │   AND used_by IS NULL        │
                    └──────────────┬───────────────┘
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
                    ▼                             ▼
        ┌─────────────────┐          ┌──────────────────┐
        │  Token Found?   │          │  No Token?       │
        │  ✓ Yes          │          │  ✗ Check parent  │
        └────────┬────────┘          │    of parent     │
                 │                   └──────────────────┘
                 ▼
        ┌─────────────────┐
        │  Use Token      │
        │  Mark as used   │
        │  used_by =      │
        │  salesman_id    │
        └────────┬────────┘
                 │
                 ▼
        ┌─────────────────┐
        │ Customer Created│
        └─────────────────┘
```

## Token Pool Sharing

```
Sub-Dealer A1 has 50 tokens
       │
       ├─────────────────┬─────────────────┬─────────────────┐
       ▼                 ▼                 ▼                 ▼
  Salesman 1        Salesman 2        Salesman 3        Salesman 4
  (uses pool)       (uses pool)       (uses pool)       (uses pool)
       │                 │                 │                 │
       ▼                 ▼                 ▼                 ▼
  Customer 1        Customer 2        Customer 3        Customer 4
  Token #1          Token #2          Token #3          Token #4

       ▼                 ▼                 ▼                 ▼
  ├─────────────────────────────────────────────────────────┤
                    46 tokens remaining
            (shared among all 4 salesmen)
```

## Comparison: Old vs New

### Old System (Complex)
```
Token ABC123:
  created_by: super_admin
  assigned_to: dealer  ──┐
                         │
Token ABC123:            │ reassign
  created_by: super_admin│
  assigned_to: sub_dealer◄┘
                         │
Token ABC123:            │ reassign
  created_by: super_admin│
  assigned_to: salesman  ◄┘ ❌ Extra step!
                         │
Token ABC123:            │ use
  used_by: salesman ─────┘
```

### New System (Simplified)
```
Token ABC123:
  created_by: super_admin
  assigned_to: dealer  ──┐
                         │
Token ABC123:            │ reassign
  created_by: super_admin│
  assigned_to: sub_dealer◄┘
                         │
                         │ salesman uses automatically ⚡
                         │ (no reassignment needed)
                         │
Token ABC123:            │ use
  assigned_to: sub_dealer│ ← stays with parent!
  used_by: salesman ─────┘
```

## API Response Example

### When Salesman Creates Customer

```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 245,
      "name": "John Doe",
      "dealer_id": 10,
      "dealer_customer_id": 5,
      "token": {
        "code": "ABC123DEF456",
        "status": "used",
        "assigned_to": {
          "id": 15,
          "name": "Sub-Dealer A1",
          "role": "sub_dealer"
        },
        "used_by": {
          "id": 20,
          "name": "Salesman A1-1",
          "role": "salesman"
        },
        "assignment_chain": [
          {
            "action": "generated",
            "by": "Super Admin",
            "timestamp": "2025-10-01T10:00:00Z"
          },
          {
            "action": "assigned",
            "from": "Super Admin",
            "to": "Dealer A",
            "timestamp": "2025-10-01T10:05:00Z"
          },
          {
            "action": "assigned",
            "from": "Dealer A",
            "to": "Sub-Dealer A1",
            "timestamp": "2025-10-02T09:00:00Z"
          },
          {
            "action": "used",
            "by": "Salesman A1-1",
            "for": "Customer Registration",
            "note": "Used parent's (sub-dealer) token",
            "timestamp": "2025-10-05T14:30:00Z"
          }
        ]
      }
    }
  }
}
```

## Token Statistics View

```
┌──────────────────────────────────────────────────────┐
│              Token Distribution                      │
├──────────────────────────────────────────────────────┤
│  Role            │ Assigned │ Available │ Used       │
├──────────────────┼──────────┼───────────┼────────────┤
│  Super Admin     │    0     │   800     │    200     │
│  Dealers         │   500    │   300     │    200     │
│  Sub-Dealers     │   450    │   250     │    200     │
│  Salesmen        │    0  ⭐ │    0  ⭐  │    200  ⭐ │
└──────────────────┴──────────┴───────────┴────────────┘

⭐ Salesmen have:
   - 0 assigned (they don't receive assignments)
   - 0 available (they use parent's pool)
   - 200 used (they've used parent's tokens to create customers)
```

## Error Flow: Attempting to Assign to Salesman

```
User tries to assign token to salesman
              │
              ▼
┌──────────────────────────────┐
│ POST /api/tokens/assign      │
│ {                            │
│   "assignee_id": 25,         │  ← Salesman ID
│   "token_code": "ABC123"     │
│ }                            │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│ TokenController::assign()    │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│ Check: Is assignee salesman? │
│ ✓ Yes                        │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────────────────────┐
│ ❌ ERROR 400                                 │
│                                              │
│ "Salesmen cannot receive token assignments. │
│  They automatically use tokens from their   │
│  parent (dealer or sub-dealer)."            │
└──────────────────────────────────────────────┘
```

---

## Legend

- `┌─┐` Box = Process/Entity
- `│` Vertical line = Flow direction
- `▼` Arrow down = Next step
- `◄` Arrow left = Assignment/Transfer
- `⚡` Lightning = Automatic process
- `✓` Check = Yes/Success
- `✗` X = No/Failure
- `❌` Red X = Error/Blocked
- `⭐` Star = Important/New behavior
