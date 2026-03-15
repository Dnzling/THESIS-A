# Procurement Refactor Plan - Stock Order Requests

## Current Issue
- PurchaseOrder can be created from scratch
- No connection between Branch Inventory (low stock) → Procurement → Supplier
- `procurement_inventories` table is standalone and doesn't bridge branches to suppliers

## Correct Architecture

```
┌─────────────────────────────────────────────────────────────┐
│           BRANCH INVENTORY (Low Stock Detection)            │
│  quantity_on_hand < reorder_point                           │
└────────────────────┬────────────────────────────────────────┘
                     │ AUTO-triggers or Manual Request
                     ↓
┌─────────────────────────────────────────────────────────────┐
│      STOCK ORDER REQUEST (Procurement stage 1)              │
│  - branch_inventory_id (ties to specific item & location)   │
│  - requested_quantity (how much to order)                   │
│  - status: pending → approved → converted_to_po → cancelled │
│  - supplier_ids (which suppliers can supply this)           │
│  - approved_date & approved_by                              │
└────────────────────┬────────────────────────────────────────┘
                     │ Reviewer selects supplier
                     ↓
┌─────────────────────────────────────────────────────────────┐
│      PURCHASE ORDER (Procurement stage 2 - from Branch)     │
│  - stock_order_request_id (the source request)              │
│  - supplier_id (chosen during review)                       │
│  - items inherited from stock request                       │
│  - links: stock_request → branch_inventory → supplier       │
└─────────────────────────────────────────────────────────────┘
```

## Action Items

### 1. ✅ Drop procurement_inventories table
- No longer needed - it's redundant
- Use branch_inventory + stock_order_requests instead

### 2. ✅ Create stock_order_requests table
```sql
- id
- store_id
- branch_inventory_id (FK to branch_inventory)
- requested_quantity
- notes
- status (pending, approved, converted_to_po, rejected, cancelled)
- created_by
- approved_by
- approved_date
- conversion_date
- created_at / updated_at / deleted_at (soft delete)
```

### 3. ✅ Update purchase_orders table
```sql
ADD COLUMN: stock_order_request_id (FK to stock_order_requests)
CONSTRAINT: NOT NULL for new POs (legacy POs can be NULL)
```

### 4. ✅ Update PurchaseOrder model
- Add: `belongsTo(StockOrderRequest)`
- Update: items relationship to fetch from stock request
- Add: method to create from stock order requests

### 5. ✅ Update PurchaseOrderController
- Change: POST /create endpoint to return pending stock_order_requests
- Change: POST /store to convert stock requests to PO (bulk or individual)
- Add: method to list pending requests

### 6. ✅ Update Frontend
- Change: PO creation flow to select stock requests
- Auto-populate: items from request
- Auto-select: suppliers (if product has supplier)
- Display: branch info, product, quantity needed, suppliers available

## Database Migration Strategy
1. Create new migration: `create_stock_order_requests_table.php`
2. Add column migration: `add_stock_order_request_to_purchase_orders.php`
3. Migrate existing POs (if any) → create stock_order_requests retroactively
