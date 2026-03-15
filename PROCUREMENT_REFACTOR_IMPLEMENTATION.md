# Procurement Refactor - Implementation Status ✅

## Summary
You were correct! The procurement flow has been refactored to NOT allow creating POs from scratch. Instead, **POs must now be created FROM stock order requests**, which are triggered when branch inventory reaches low stock levels.

## What Was Done

### ✅ 1. Database Changes (Migration Created & Executed)
**New Table: `stock_order_requests`**
```sql
- id (primary key)
- uuid (unique identifier)
- store_id (which store)
- branch_inventory_id (FK - the item that's low on stock)
- requested_quantity (how much to order)
- notes (audit trail)
- status: pending → approved → converted_to_po → rejected/cancelled
- created_by (user)
- approved_by (user)
- approved_date
- conversion_date
- timestamps & soft_delete
```

**Updated Table: `purchase_orders`**
- ✅ Added column: `stock_order_request_id` (FK to stock_order_requests)
- ✅ This links PO back to the original branch inventory request

### ✅ 2. Backend Models Created

**StockOrderRequest.php**
- Location: `app/Models/Procurement/StockOrder/StockOrderRequest.php`
- Relationships:
  - `belongsTo(Store)`
  - `belongsTo(BranchInventory)` - the item that needs restocking
  - `belongsTo(Employee)` via created_by & approved_by
  - `hasMany(PurchaseOrder)` - all POs created from this request
- Methods:
  - `approve()` - approve the stock request
  - `reject()` - reject it
  - `markConverted()` - mark as converted to PO
  - `getAvailableSuppliers()` - get suppliers that can supply this product
- Scopes:
  - `pending()`, `approved()`, `converted()`
  - `byBranch()`, `byProduct()`

**PurchaseOrder.php (Updated)**
- ✅ Added import: `use App\Models\Procurement\StockOrder\StockOrderRequest`
- ✅ Added relationship: `stockOrderRequest(): BelongsTo`

### ✅ 3. Backend Controller Created

**StockOrderRequestController.php**
- Location: `app/Http/Controllers/Api/Procurement/StockOrder/StockOrderRequestController.php`
- Methods:
  - `index()` - list all stock order requests (with filters)
  - `show()` - view single request with available suppliers
  - `store()` - create new stock order request from branch inventory
  - `createFromLowStock()` - AUTO-create from branch items below reorder point
  - `approve()` - approve a pending request
  - `reject()` - reject a pending request
  - `pendingForConversion()` - get approved requests ready for PO
  - `summary()` - stats dashboard

### ✅ 4. PurchaseOrderController Updated

**NEW store() Method Logic:**
```
Input: {
  stock_order_request_ids: [1, 2, 3],  // IDs of approved stock requests
  supplier_id: 5,                       // supplier to order from
  payment_terms: "net_30",
  shipping_cost: 500,
  discount_amount: 100,
  notes: "...",
  terms_conditions: "..."
}

Process:
1. Fetch all stock order requests
2. Verify they're all APPROVED status
3. Get branch from first request (all should be same branch)
4. Extract items from stock requests (product, quantity)
5. Calculate totals from items
6. Create ONE PO with all items
7. Link PO to first stock request
8. Mark all stock requests as "converted_to_po"
9. Create PO items for each stock request
```

**OLD store() Method:**
- ❌ REMOVED - no longer allows creating PO from scratch
- ❌ Previously: Could manually enter branch, supplier, items
- ✅ NOW: Must use stock order requests

### ✅ 5. Routes Added

New endpoints in `routes/procurement_routes.php`:

```php
// Stock Order Requests (NEW)
Route::prefix('stock-order-requests')->group(function () {
    GET  /                          // List all requests
    GET  /{id}                      // View single request
    POST /                          // Create new request
    POST /bulk/create-from-low-stock// Auto-create from low stock
    GET  /pending/for-conversion    // Get requests ready for PO
    GET  /summary                   // Stats dashboard
    POST /{id}/approve              // Approve request
    POST /{id}/reject               // Reject request
});

// Purchase Orders (UPDATED)
POST /purchase-orders              // NOW: requires stock_order_request_ids
                                   // NOT: freeform branch/supplier/items
```

## New Workflow

```
┌─────────────────────────────────────────────┐
│  BRANCH INVENTORY                          │
│  quantity_on_hand < reorder_point          │
└────────────┬────────────────────────────────┘
             │
             ↓ (Manual or Auto-trigger)
┌─────────────────────────────────────────────┐
│  STOCK ORDER REQUEST                        │
│  ✓ branch_inventory_id (LINKS to item)     │
│  ✓ requested_quantity                       │
│  ✓ status: pending                          │
│  ✓ created_by: user_id                      │
└────────────┬────────────────────────────────┘
             │
             ↓ (Procurement reviews)
┌─────────────────────────────────────────────┐
│  APPROVAL STEP                              │
│  POST /{id}/approve                         │
│  status: pending → approved                 │
│  approved_by: manager_id                    │
└────────────┬────────────────────────────────┘
             │
             ↓ (Convert to PO)
┌─────────────────────────────────────────────┐
│  CREATE PURCHASE ORDER                      │
│  POST /purchase-orders                      │
│  {                                          │
│    stock_order_request_ids: [1,2,3],       │
│    supplier_id: 5,                          │
│    payment_terms: "net_30"                  │
│  }                                          │
│  Result:                                    │
│  - PO created with items from requests      │
│  - Linked to stock requests                 │
│  - Stock requests marked "converted_to_po"  │
└────────────┬────────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────────┐
│  SEND TO SUPPLIER                           │
│  PO sent to supplier_id                     │
│  Stays linked to: branch → product → qty    │
└─────────────────────────────────────────────┘
```

## API Usage Examples

### 1. Auto-Create Stock Requests from Low Stock
```bash
POST /api/procurement/stock-order-requests/bulk/create-from-low-stock
{
  "branch_id": 2,
  "reorder_automatically": true
}
```
Response: Creates requests for all branch items below reorder_point

### 2. View Pending Requests Ready for Approval
```bash
GET /api/procurement/stock-order-requests?status=pending
```

### 3. Approve a Stock Request
```bash
POST /api/procurement/stock-order-requests/5/approve
```

### 4. Get Approved Requests Ready for PO Conversion
```bash
GET /api/procurement/stock-order-requests/pending/for-conversion
```
Response: Only approved requests without POs yet

### 5. Create PO from Stock Requests
```bash
POST /api/procurement/purchase-orders
{
  "stock_order_request_ids": [1, 2, 3],
  "supplier_id": 5,
  "payment_terms": "net_30",
  "shipping_cost": 500,
  "discount_amount": 0,
  "notes": "Order from branch 2"
}
```

## Files Created/Modified

### Created (2 files)
- ✅ `database/migrations/2026_03_11_000008_create_stock_order_requests_table.php`
- ✅ `app/Models/Procurement/StockOrder/StockOrderRequest.php`
- ✅ `app/Http/Controllers/Api/Procurement/StockOrder/StockOrderRequestController.php`

### Modified (2 files)
- ✅ `backend/routes/procurement_routes.php` - Added stock order request routes
- ✅ `app/Models/Procurement/PurchaseOrder/PurchaseOrder.php` - Added relationship to StockOrderRequest
- ✅ `app/Http/Controllers/Api/Procurement/PurchaseOrder/PurchaseOrderController.php` - Refactored store() method

### Migration Status
- ✅ `2026_03_11_000008_create_stock_order_requests_table` - EXECUTED
- ✅ New table created and column added to purchase_orders

## ✅ Backend Complete

All backend refactoring is complete and tested. Database is ready.

## 🚀 Frontend TODO (Next Steps)

The frontend needs to be updated to:

1. **Remove old PO creation form**
   - Remove free-form branch/supplier/items input
   - Remove product list picker

2. **Create new "Create PO from Stock Requests" flow**
   - Step 1: Display pending stock order requests
   - Step 2: User selects which requests to convert to PO
   - Step 3: Fetch available suppliers for items
   - Step 4: User selects supplier
   - Step 5: User confirms payment terms, shipping, discounts
   - Step 6: Submit to create PO

3. **Add Stock Request Management UI**
   - List stock order requests
   - View request details (branch, product, quantity needed)
   - Approve/Reject button
   - Mark as converted

4. **Update PO List View**
   - Add column showing linked stock request
   - Show branch origin
   - Show how many stock requests were converted in this PO

## Files to Update on Frontend

1. `frontend/src/services/procurement.service.ts`
   - Add methods for stock request endpoints

2. `frontend/src/views/system/procurement/PurchaseOrders/Create.vue`  
   - Completely redesign: show stock requests instead of form
   - Add step-by-step wizard

3. `frontend/src/views/system/procurement/PurchaseOrders/List.vue`
   - Update columns to show stock request info

4. `frontend/src/components/` (new components needed)
   - StockOrderRequestList.vue
   - StockOrderRequestDetail.vue
   - StockRequestSelector.vue (choose which to convert)

## Key Benefits

✅ **Branch Inventory → Procurement Link** - Clear traceability from who needs stock to who orders  
✅ **No Accidental POs** - Can't create PO without verified low-stock reason  
✅ **Automated Requests** - Can auto-trigger from reorder points  
✅ **Supplier Flexibility** - Can switch suppliers per request  
✅ **Better Auditing** - Know why each PO was created  

## Migration Safety

- All existing POs remain unchanged (stock_order_request_id is nullable for legacy)
- New POs MUST come from stock requests
- No data loss - just cleaner workflow

---

**Status:** ✅ Backend 100% Complete | 🚀 Frontend TODO
