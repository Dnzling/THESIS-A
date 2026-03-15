# Procurement Stock Request-Based PO Creation - Complete Integration Guide

## 🔄 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                           FRONTEND (Vue 3 + TypeScript)          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │          CreateNew.vue (3-Step Wizard)                    │   │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────────┐      │   │
│  │  │   Step 1   │→ │   Step 2   │→ │    Step 3     │      │   │
│  │  │  Requests  │  │  Supplier  │  │  Confirm &   │      │   │
│  │  │            │  │  Details   │  │  Submit      │      │   │
│  │  └────────────┘  └────────────┘  └────────────────┘      │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                 ↓                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │       procurementService.ts (Service Layer)              │   │
│  │  • getStockOrderRequests()                               │   │
│  │  • getPendingStockOrderRequestsForConversion() [KEY]    │   │
│  │  • getSuppliers()                                        │   │
│  │  • createPurchaseOrder([stock_order_request_ids,...])    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                 ↓                                 │
│        HTTP Request (JSON Payload)                               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                                 ↓
        ┌──────────────────────────────────────────────┐
        │      Backend API (Laravel 11 + MySQL)       │
        └──────────────────────────────────────────────┘
                                 ↓
        ┌──────────────────────────────────────────────┐
        │          Route: /procurement/*               │
        └──────────────────────────────────────────────┘
                                 ↓
┌─────────────────────────────────────────────────────────────────┐
│                     Controller Layer                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  StockOrderRequestController:                                    │
│  • index() - GET /stock-order-requests                          │
│  • show(id) - GET /stock-order-requests/{id}                    │
│  • pendingForConversion() - GET /stock-order-requests/pending   │
│  • summary() - GET /stock-order-requests/summary [KEY]          │
│  • approve(id) - POST /stock-order-requests/{id}/approve        │
│  • reject(id) - POST /stock-order-requests/{id}/reject          │
│                                                                   │
│  PurchaseOrderController:                                        │
│  • store(Request) - POST /purchase-orders                       │
│    ├─ Validates: stock_order_request_ids[]                      │
│    ├─ Fetches: StockOrderRequest models                         │
│    ├─ Extracts: Items, Branch, Product data                    │
│    ├─ Creates: PO with FK to stock_order_request                │
│    └─ Marks: Requests as "converted_to_po"                     │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
                                 ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Database Layer (MySQL)                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Tables:                                                          │
│  • stock_order_requests                                          │
│    ├─ id, uuid (unique identifier)                             │
│    ├─ store_id, branch_inventory_id                            │
│    ├─ requested_quantity, status                               │
│    ├─ created_by, approved_by, approved_at                     │
│    └─ timestamps, soft_deletes                                 │
│                                                                   │
│  • purchase_orders (MODIFIED)                                   │
│    ├─ id, stock_order_request_id (FK) [NEW]                   │
│    ├─ supplier_id, branch_id                                   │
│    ├─ total_amount, status                                     │
│    └─ timestamps                                                │
│                                                                   │
│  • purchase_order_items                                          │
│    ├─ purchase_order_id, product_id                            │
│    ├─ quantity_ordered, unit_cost                              │
│    └─ tax_rate, discount_percent                               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Data Flow Sequence

### 1. Frontend Step 1: Load & Select Stock Requests

```
User navigates to: /procurement/purchase-orders/create
                        ↓
CreateNew.vue mounted()
                        ↓
Call: getPendingStockOrderRequestsForConversion({
  store_id?: number,
  branch_id?: number,
  product_id?: number
})
                        ↓
GET /api/procurement/stock-order-requests/pending/for-conversion?store_id=1&branch_id=2
                        ↓
[Backend] Returns array of stock requests with:
{
  id: 1,
  uuid: "abc123...",
  store_id: 1,
  branch_inventory_id: 5,
  branch: { id: 2, name: "Branch A" },
  product: { 
    id: 10,
    product_name: "Chair",
    last_purchase_price: 1500,
    sku: "CHR-001"
  },
  variation: { 
    id: 20,
    variation_name: "Red/Large"
  },
  requested_quantity: 50,
  status: "approved",
  created_at: "2025-01-15",
  approved_at: "2025-01-16"
}
                        ↓
Display in DataTable with multi-select
                        ↓
User selects multiple requests: [1, 3, 7]
                        ↓
Next Step button ENABLED ✓
```

### 2. Frontend Step 2: Select Supplier

```
User clicks "Next: Choose Supplier"
                        ↓
Display Step 2: Supplier Selection
                        ↓
Extract PO items from selected requests:
[
  { product_id: 10, quantity: 50, branch_name: "Branch A" },
  { product_id: 15, quantity: 25, branch_name: "Branch A" },
  { product_id: 20, quantity: 100, branch_name: "Branch B" }
]
                        ↓
Show items summary card
                        ↓
Call: getSuppliers({ per_page: 100 })
GET /api/procurement/suppliers
                        ↓
Display supplier dropdown (auto-load suppliers)
                        ↓
User selects supplier: supplier_id = 5
                        ↓
Call onSupplierChange():
{
  id: 5,
  supplier_name: "Acme Furniture",
  contact_person: "John Doe",
  email: "john@acme.com",
  phone: "555-1234",
  average_delivery_days: 7,
  payment_terms: "net_30"
}
                        ↓
Auto-populate supplier details card ✓
                        ↓
Next Step button ENABLED ✓
```

### 3. Frontend Step 3: Enter Terms & Submit

```
User clicks "Next: Enter Terms"
                        ↓
Display Step 3: Items Summary + Terms Entry
                        ↓
Show read-only table of items:
Product          | Qty | Unit Cost | Line Total
─────────────────────────────────────────────
Chair (Red/L)    | 50  | 1,500     | 75,000
Desk (White)     | 25  | 2,500     | 62,500
Cabinet (Black)  | 100 | 800       | 80,000
─────────────────────────────────────────────
Subtotal:                              217,500
                        ↓
User enters:
- Payment Terms: "net_30"
- Shipping Cost: 500
- Discount Amount: 0
                        ↓
Calculate & Display:
- Subtotal: ₱217,500
- Shipping: +₱500
- Discount: -₱0
- TOTAL: ₱218,000
                        ↓
User clicks "Create Purchase Order"
                        ↓
Validation:
✓ stock_order_request_ids: [1, 3, 7]
✓ supplier_id: 5
✓ payment_terms: "net_30"
✓ shipping_cost: 500
✓ discount_amount: 0
✓ notes: "..."
                        ↓
Payload created:
{
  "stock_order_request_ids": [1, 3, 7],
  "supplier_id": 5,
  "payment_terms": "net_30",
  "shipping_cost": 500,
  "discount_amount": 0,
  "notes": "Rush delivery appreciated"
}
                        ↓
Call: createPurchaseOrder(payload)
POST /api/procurement/purchase-orders
[JSON Payload shown above]
```

### 4. Backend Processing

```
POST /api/procurement/purchase-orders
[Payload received: stock_order_request_ids, supplier_id, ...]
                        ↓
PurchaseOrderController::store()
                        ↓
Validation:
✓ stock_order_request_ids is array
✓ All IDs exist in database
✓ All have status = "approved"
✓ All belong to same store
✓ supplier_id exists
                        ↓
Begin Database Transaction:
                        ↓
Fetch StockOrderRequest models:
SELECT * FROM stock_order_requests WHERE id IN (1,3,7)
                        ↓
Extract data for PO:
{
  store_id: (from first request),
  branch_id: (from first request),
  supplier_id: 5,
  payment_terms: "net_30",
  items: [
    { product_id: 10, quantity: 50, unit_cost: 1500 },
    { product_id: 15, quantity: 25, unit_cost: 2500 },
    { product_id: 20, quantity: 100, unit_cost: 800 }
  ],
  total_amount: 218000
}
                        ↓
Create PurchaseOrder:
INSERT INTO purchase_orders (
  stock_order_request_id, ← FK TO FIRST REQUEST (OR MULTIPLE?)
  supplier_id,
  branch_id,
  payment_terms,
  total_amount,
  status
) VALUES (...)
                        ↓
Create PurchaseOrderItems:
INSERT INTO purchase_order_items (
  purchase_order_id,
  product_id,
  quantity_ordered,
  unit_cost
)
VALUES
  (po_id, 10, 50, 1500),
  (po_id, 15, 25, 2500),
  (po_id, 20, 100, 800)
                        ↓
Update StockOrderRequests:
UPDATE stock_order_requests 
SET status = "converted_to_po" 
WHERE id IN (1,3,7)
                        ↓
Commit Transaction ✓
                        ↓
Return Response:
{
  id: 123,
  po_number: "PO-2025-001",
  stock_order_request_id: 1,  ← Links back to first request
  supplier_id: 5,
  status: "pending_approval",
  total_amount: 218000,
  items: [...]
}
```

### 5. Frontend - Post-Submit

```
Backend returns: PO created successfully
                        ↓
Toast: "Purchase Order created from stock requests"
                        ↓
Redirect to: /procurement/purchase-orders
                        ↓
Index.vue loads all POs:
GET /api/procurement/purchase-orders
                        ↓
Display table with new PO:
PO No.    | Supplier         | Source          | Amount    | Status
──────────────────────────────────────────────────────────────────
PO-2025-001 | Acme Furniture | [Stock Request] | 218,000   | Pending
          
          ↓ (blue badge shows source)
```

---

## 🔗 Integration Points Reference

### Frontend Service Methods → Backend Routes

| Service Method | HTTP Method | Backend Route | Controller Method |
|---|---|---|---|
| `getStockOrderRequests(params)` | GET | `/stock-order-requests` | `index` |
| `getStockOrderRequest(id)` | GET | `/stock-order-requests/{id}` | `show` |
| `createStockOrderRequest(data)` | POST | `/stock-order-requests` | `store` |
| `bulkCreateStockOrderRequestsFromLowStock(params)` | POST | `/stock-order-requests/bulk/create-from-low-stock` | `createFromLowStock` |
| `getPendingStockOrderRequestsForConversion(params)` | GET | `/stock-order-requests/pending/for-conversion` | `pendingForConversion` |
| `getStockOrderRequestSummary(params)` | GET | `/stock-order-requests/summary` | `summary` |
| `approveStockOrderRequest(id)` | POST | `/stock-order-requests/{id}/approve` | `approve` |
| `rejectStockOrderRequest(id, reason)` | POST | `/stock-order-requests/{id}/reject` | `reject` |
| `createPurchaseOrder(payload)` | POST | `/purchase-orders` | `store` (REFACTORED) |

---

## ⚙️ Critical Configuration

### Route Order (IMPORTANT - FIXED)

Backend routes must be in this order to avoid wildcard catching:

```php
Route::prefix('stock-order-requests')->group(function () {
    Route::get('/', [StockOrderRequestController::class, 'index']);
    Route::post('/', [StockOrderRequestController::class, 'store']);
    // ✓ Named routes BEFORE wildcard
    Route::post('/bulk/create-from-low-stock', [...]);
    Route::get('/pending/for-conversion', [...]);  ← MUST BE BEFORE /{id}
    Route::get('/summary', [...]);
    // Wildcard routes LAST
    Route::get('/{id}', [...]);
    Route::post('/{id}/approve', [...]);
    Route::post('/{id}/reject', [...]);
});
```

**Status:** ✅ FIXED in backend/routes/procurement_routes.php

---

## 🧪 Request/Response Examples

### Request 1: Get Pending Stock Order Requests

```http
GET /api/procurement/stock-order-requests/pending/for-conversion?store_id=1&branch_id=2

HTTP/1.1 200 OK
Content-Type: application/json

{
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "store_id": 1,
      "store": { "id": 1, "store_name": "Main Store" },
      "branch_inventory_id": 5,
      "branch_inventory": {...},
      "branch": { "id": 2, "name": "Branch A" },
      "product_id": 10,
      "product": {
        "id": 10,
        "product_name": "Office Chair",
        "sku": "CHR-001",
        "last_purchase_price": 1500
      },
      "variation_id": 20,
      "variation": { "id": 20, "variation_name": "Red/Large" },
      "requested_quantity": 50,
      "status": "approved",
      "created_by": 1,
      "approved_by": 3,
      "approved_at": "2025-01-16T10:30:00,
      "created_at": "2025-01-15T09:00:00",
      "updated_at": "2025-01-16T10:30:00"
    },
    {...},  // More requests
  ],
  "total": 3,
  "per_page": 100
}
```

### Request 2: Create Purchase Order from Stock Requests

```http
POST /api/procurement/purchase-orders
Content-Type: application/json
Authorization: Bearer {token}

{
  "stock_order_request_ids": [1, 3, 7],
  "supplier_id": 5,
  "payment_terms": "net_30",
  "shipping_cost": 500,
  "discount_amount": 0,
  "notes": "Rush delivery if possible"
}

HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "id": 123,
    "po_number": "PO-2025-001",
    "store_id": 1,
    "branch_id": 2,
    "supplier_id": 5,
    "stock_order_request_id": 1,
    "payment_terms": "net_30",
    "order_date": "2025-01-17",
    "expected_delivery_date": "2025-01-24",
    "shipping_cost": 500,
    "discount_amount": 0,
    "subtotal": 217500,
    "tax_total": 0,
    "total_amount": 218000,
    "status": "pending_approval",
    "notes": "Rush delivery if possible",
    "created_at": "2025-01-17T11:00:00",
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "quantity_ordered": 50,
        "unit_cost": 1500,
        "line_total": 75000
      },
      {...}  // More items
    ]
  },
  "message": "Purchase Order created from stock requests"
}
```

---

## 🔐 Validation Rules

### Frontend Validation (Client-side)

1. **Step 1:**
   - ✓ At least 1 stock request must be selected
   - ✓ Can apply filters without breaking selection

2. **Step 2:**
   - ✓ Supplier must be selected
   - ✓ Must be able to proceed if supplier selected

3. **Step 3:**
   - ✓ Payment terms must be selected (non-null)
   - ✓ Shipping cost ≥ 0
   - ✓ Discount amount ≥ 0
   - ✓ Create button disabled until all validations pass

### Backend Validation (Server-side)

1. **Stock Order Requests:**
   - ✓ All IDs must exist in database
   - ✓ All must have status = "approved"
   - ✓ All must belong to same store
   - ✓ Cannot have duplicate IDs

2. **Supplier:**
   - ✓ Must exist in database
   - ✓ Must not be inactive/deleted

3. **Data Consistency:**
   - ✓ Branch determined from first request
   - ✓ Items extracted from all requests
   - ✓ Totals calculated before creation
   - ✓ All created in single transaction (atomic)

---

## 📋 Checklist for Production

### Backend (Already Complete)
- ✅ StockOrderRequest model with relationships
- ✅ StockOrderRequestController with all 7 endpoints
- ✅ PurchaseOrderController.store() refactored
- ✅ Migration executed (table created, FK added)
- ✅ Routes configured (with corrected order)
- ✅ Transaction safety implemented
- ✅ All validations in place

### Frontend (Pending Testing)
- ⏳ CreateNew.vue compiles without errors
- ⏳ All service methods working correctly
- ⏳ Step 1: Stock requests load and display
- ⏳ Step 2: Supplier selection works
- ⏳ Step 3: Totals calculate correctly
- ⏳ Submit: Sends correct payload
- ⏳ Post-submit: Redirects correctly
- ⏳ Index.vue: Source badge displays correctly
- ⏳ Error handling: Messages display clearly
- ⏳ Edge cases: Handled gracefully

---

## 🎯 Success Criteria

**System is production-ready when:**
1. Frontend compiles without errors ✅/⏳
2. All 3 wizard steps functional (Create > Test all 3 steps) ⏳
3. Stock requests load from approved status ⏳
4. PO creation sends correct payload ⏳
5. PO appears in list with "Stock Request" badge ⏳
6. No console errors or warnings ⏳
7. Manual testing: Happy path works end-to-end ⏳
8. Manual testing: All validation scenarios work ⏳

---

**Last Updated:** After Frontend Implementation Phase  
**Status:** 🔄 Frontend implementation complete, ready for testing  
**Next Phase:** Testing & Bug Fixes
