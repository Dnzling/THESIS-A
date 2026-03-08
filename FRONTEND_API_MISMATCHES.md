# Frontend API Mismatches & UI Issues

## Summary of Issues Found

The UI components don't match the backend API endpoints and response structures defined in `inventory.service.ts`. Below is a detailed breakdown of all the issues.

---

## 1. **Stock Adjustments** 

### File: `AdjustmentIndex.vue` & `AdjustmentCreate.vue`

#### Issues:
- ✗ Wrong API endpoint: Currently calling `/api/inventory/adjustments` 
- ✗ Wrong data structure: Service expects `StockAdjustment` with nested `items` array
- ✗ Wrong field names: 
  - UI uses: `reference_no`, `adjustment_date`, `reason`
  - Should match service interface

#### Current Issues in Code:
```typescript
// WRONG - Service expects different structure
const response = await axios.post(`/api/inventory/adjustments`, data)

// AdjustmentCreate.vue uses wrong field names:
form.type               // WRONG - should be in items
form.reference_document // WRONG - not in service
form.actual_quantity    // WRONG - should be quantity in items
```

#### Fixes Needed:
```typescript
// CORRECT structure from service:
interface StockAdjustment {
  branch_id: number
  adjustment_date: string
  reason: string
  remarks?: string
  items: Array<{
    inventory_item_id: number
    adjustment_type: 'add' | 'deduct'  // NOT "actual_quantity"
    quantity: number
    remarks?: string
  }>
}

// Form should build this structure
const form = {
  branch_id: 123,
  adjustment_date: '2024-03-08',
  reason: 'inventory_count',  // or 'damage', 'theft', etc
  remarks: '',
  items: [
    {
      inventory_item_id: 1,
      adjustment_type: 'add',
      quantity: 5
    }
  ]
}
```

---

## 2. **Stock Transfers**

### File: `TransferIndex.vue`, `TransferCreate.vue`, `TransferDetail.vue`

#### Issues:
- ✗ Wrong status values:
  - UI uses: `pending`, `in_transit`, `rejected`
  - Service expects: `draft`, `submitted`, `approved`, `shipped`, `received`, `cancelled`
  
- ✗ Wrong field names:
  - UI uses: `reference_no` (missing in create)
  - Should use: `transfer_no` or `transfer_no`
  
- ✗ Wrong data structure for items

#### Current Issues in Code:
```typescript
// WRONG statuses - TransferIndex.vue
const statusOptions = [
  { label: 'Pending', value: 'pending' },      // ✗ should be 'draft' or 'submitted'
  { label: 'Approved', value: 'approved' },    // ✓ correct
  { label: 'In Transit', value: 'in_transit' },// ✗ should be 'shipped'
  { label: 'Received', value: 'received' },    // ✓ correct
  { label: 'Rejected', value: 'rejected' }     // ✗ should be 'cancelled'
]

// WRONG - approveTransfer on "pending" status
if (data.status === 'pending')  // Should check for 'draft' or 'submitted'
```

#### Fixes Needed:
```typescript
// CORRECT statuses
const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Submitted', value: 'submitted' },
  { label: 'Approved', value: 'approved' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Received', value: 'received' },
  { label: 'Cancelled', value: 'cancelled' }
]

// CORRECT structure
interface StockTransfer {
  from_branch_id: number
  to_branch_id: number
  transfer_date: string
  expected_receive_date?: string
  remarks?: string
  items: Array<{
    inventory_item_id: number
    quantity: number
    remarks?: string
  }>
}
```

---

## 3. **Inventory Items (Branch Selection)**

### File: `ItemsIndex.vue`

#### Issues:
- ✗ Missing branch filter: Service has `getBranchInventory(branchId)` but UI doesn't use it
- ✗ Wrong API endpoint: Using `/api/inventory/items` but should allow filtering by branch
- ✗ Missing branch selector UI

#### Current Issues:
```typescript
// WRONG - No branch filtering
const response = await axios.get('/api/inventory/items', { params })

// Should support:
// GET /api/inventory/branch/{branchId}
// GET /api/inventory/branch/{branchId}/summary
// GET /api/inventory/branch/{branchId}/low-stock
```

#### Fixes Needed:
```typescript
// Add branch selector to filters
const filters = reactive({
  branch_id: null,  // ADD THIS
  search: '',
  stock_status: null,
  page: 1,
  per_page: 15
})

// Use branch-specific endpoint when branch is selected
const loadItems = async () => {
  if (filters.branch_id) {
    // Use branch-specific endpoint
    const response = await inventoryService.getBranchInventory(filters.branch_id)
  } else {
    // Use general endpoint
    const response = await inventoryService.getInventoryItem()
  }
}
```

---

## 4. **Stock Alerts**

### File: `AlertsIndex.vue`

#### Issues:
- ✗ Wrong status values:
  - UI uses: `active`, `acknowledged`, `resolved`
  - Service expects: Check backend for proper statuses
  
- ✗ Field names don't match:
  - UI uses: `inventory_item.product.sku`
  - Need to verify exact structure
  
- ✗ Missing acknowledged_at/resolved_at timestamp tracking

#### Current Issues:
```typescript
// WRONG - Stats counting (need to verify):
stats.active      // What does this mean?
stats.critical    // Might need severity_level
stats.acknowledged  // Field should be checked_at?
stats.resolved    // Should be resolved_at?

// WRONG - API endpoint for stats
// Service has: getAlertSummary() but UI might not be using it correctly
```

#### Fixes Needed:
```typescript
// Use service methods correctly
const loadAlerts = async () => {
  // First get stats
  const statsResponse = await inventoryService.getAlertSummary()
  
  // Then get alerts list
  const alertsResponse = await inventoryService.getAlerts(params)
}

// Proper bulk acknowledge
const bulkAcknowledge = async () => {
  const ids = alerts.value
    .filter(a => a.status === 'active')
    .map(a => a.id)
  
  await inventoryService.bulkAcknowledgeAlerts(ids)
}
```

---

## 5. **Transactions/History**

### File: `TransactionIndex.vue`

#### Issues:
- ✗ Missing chart data loading: Service has `getTransactionChartData()` but UI might not use it
- ✗ Missing export functionality: Service has `exportTransactions()` but UI might not implement it
- ✗ Wrong transaction summary grouping

#### Fixes Needed:
```typescript
// Use all service methods
const loadTransactions = async () => {
  const [transactions, summary, chartData] = await Promise.all([
    inventoryService.getTransactions(params),
    inventoryService.getTransactionSummary(params),
    inventoryService.getTransactionChartData(params)
  ])
}

// Export functionality
const exportAll = async () => {
  const blob = await inventoryService.exportTransactions(filters)
  // Download blob as CSV
}
```

---

## 6. **Notifications**

### File: `NotificationIndex.vue` (if exists)

#### Issues:
- ✗ Service expects different notification structure
- ✗ Missing notification type filtering

---

## 7. **Configuration** 

### File: `ConfigIndex.vue`

#### Issues:
- ✗ Service expects different configuration structure
- ✗ Missing hierarchical rule levels (Product → Category → Store)

---

## API Endpoint Reference (From Service File)

```typescript
// Branch Inventory
GET    /api/inventory/branches
GET    /api/inventory/branch/{branchId}
GET    /api/inventory/branch/{branchId}/summary
GET    /api/inventory/branch/{branchId}/low-stock

// Inventory Items
GET    /api/inventory/items              // List all
GET    /api/inventory/items/{id}         // Get one
POST   /api/inventory/items              // Create
PUT    /api/inventory/items/{id}         // Update
DELETE /api/inventory/items/{id}         // Delete
POST   /api/inventory/items/{id}/update-status

// Stock Adjustments
GET    /api/inventory/adjustments
GET    /api/inventory/adjustments/{id}
POST   /api/inventory/adjustments
POST   /api/inventory/adjustments/{id}/submit
POST   /api/inventory/adjustments/{id}/approve
POST   /api/inventory/adjustments/{id}/reject

// Stock Transfers
GET    /api/inventory/transfers
GET    /api/inventory/transfers/{id}
POST   /api/inventory/transfers
POST   /api/inventory/transfers/{id}/approve
POST   /api/inventory/transfers/{id}/ship
POST   /api/inventory/transfers/{id}/receive
POST   /api/inventory/transfers/{id}/cancel

// Stock Alerts
GET    /api/inventory/alerts
GET    /api/inventory/alerts/summary
POST   /api/inventory/alerts/{id}/acknowledge
POST   /api/inventory/alerts/{id}/resolve
POST   /api/inventory/alerts/bulk-acknowledge
POST   /api/inventory/alerts/generate

// Inventory Transactions
GET    /api/inventory/transactions
GET    /api/inventory/transactions/summary
GET    /api/inventory/transactions/product/{productId}
GET    /api/inventory/transactions/chart
GET    /api/inventory/transactions/recent
GET    /api/inventory/transactions/export
```

---

## Action Items

### Priority 1 - CRITICAL (Breaks Functionality)
- [ ] Fix StockAdjustment form to send correct structure
- [ ] Fix StockTransfer status values (pending → draft/submitted, in_transit → shipped)
- [ ] Fix Inventory Items branch filtering
- [ ] Fix Stock Alerts status values

### Priority 2 - HIGH (Wrong Field Names)
- [ ] Update all `reference_no` to match backend field names
- [ ] Fix nested item structures in forms
- [ ] Update timestamp field names (acknowledged_at, resolved_at)

### Priority 3 - MEDIUM (Missing Features)
- [ ] Add branch selector to ItemsIndex
- [ ] Add export functionality to TransactionIndex
- [ ] Add chart loading to dashboard
- [ ] Add bulk operations where applicable

### Priority 4 - LOW (Polish)
- [ ] Update status badges to match new values
- [ ] Update filter options to match actual statuses
- [ ] Update help text and tooltips
- [ ] Improve error messages

---

## Files to Update

```
frontend/src/views/system/inventory/
├── Adjustments/
│   ├── AdjustmentIndex.vue        ← FIX: Structure, endpoints
│   ├── AdjustmentCreate.vue       ← FIX: Form structure, validation
│   └── AdjustmentDetail.vue       ← FIX: Display fields
├── Transfers/
│   ├── TransferIndex.vue          ← FIX: Status values, endpoints
│   ├── TransferCreate.vue         ← FIX: Form structure
│   └── TransferDetail.vue         ← FIX: Display fields
├── Items/
│   └── ItemsIndex.vue             ← FIX: Add branch filter
├── Alerts/
│   └── AlertsIndex.vue            ← FIX: Status values, stats
├── Transactions/
│   └── TransactionIndex.vue       ← FIX: Add export, charts
└── Configuration/
    └── ConfigIndex.vue            ← FIX: Structure
```

---

## Testing Checklist After Fixes

- [ ] Create adjustment → Submit → Approve workflow works
- [ ] Create transfer → Approve → Ship → Receive workflow works
- [ ] Stock alerts trigger and can be acknowledged/resolved
- [ ] Branch inventory filtering works correctly
- [ ] Export functionality generates valid CSV
- [ ] Charts load and display correctly
- [ ] All filter combinations work without errors
- [ ] Pagination works on all list views
- [ ] Real-time updates show correctly
- [ ] Error messages are helpful and clear
