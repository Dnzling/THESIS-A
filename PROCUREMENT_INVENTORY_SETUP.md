# Procurement Inventory System Implementation

## Overview
You now have a **separate Procurement Inventory** system that tracks stock independently from Branch Inventory. This allows you to know what's available to order before requesting from suppliers.

## What Was Created

### 1. **Database Layer**
- **Migration**: `2026_03_11_000007_create_procurement_inventories_table.php`
- **Table**: `procurement_inventories`

#### Table Structure:
```
id                      - Primary key
store_id                - Which store
product_id              - Which product
variation_id            - Product variation (optional)
available_qty           - Available to order
on_order_qty            - Quantity on active POs (pending)
received_qty            - Total items received
pending_receive_qty     - Ordered but not yet received
status                  - active/inactive/discontinued
last_order_date         - Timestamp of last order
last_receive_date       - Timestamp of last receipt
notes                   - Audit trail
created_by              - User who created
updated_by              - User who modified
timestamps & soft_delete
```

### 2. **Backend - Model**
- **File**: `app/Models/Procurement/Inventory/ProcurementInventory.php`
- **Features**:
  - Relationships: Store, Product
  - Methods to manage inventory (add, receive, cancel orders)
  - Scopes for filtering
  - Automatic audit trail logging

#### Key Methods:
```php
increaseAvailable(qty, reason)    // Add available stock
decreaseAvailable(qty, reason)    // Remove available stock
addToOrder(qty, poNumber)          // When PO created
addToReceived(qty, grNumber)       // When goods receipt created
cancelOrder(qty, poNumber)         // When PO cancelled
```

### 3. **Backend - Controller**
- **File**: `app/Http/Controllers/Api/Procurement/Inventory/ProcurementInventoryController.php`
- **Endpoints**:
  - `GET /api/procurement/inventory` - List inventory
  - `GET /api/procurement/inventory/{id}` - Get item details
  - `GET /api/procurement/inventory/summary` - Stats/summary
  - `GET /api/procurement/inventory/low-stock` - Low stock alerts
  - `POST /api/procurement/inventory/init` - Initialize inventory
  - `PUT /api/procurement/inventory/{id}` - Update inventory

### 4. **Backend - Routes**
- **File**: `routes/procurement_routes.php`
- Added new `inventory` prefix routes for procurement inventory management

### 5. **Backend - Product Controller Update**
- **File**: `app/Http/Controllers/Api/Procurement/ProductController.php`
- Now enriches products with Procurement Inventory data:
  - `available_qty` - Stock available to order
  - `on_order_qty` - Currently ordered
  - `received_qty` - Already received
  - `pending_receive_qty` - Waiting to arrive

### 6. **Frontend - Service**
- **File**: `frontend/src/services/procurement.service.ts`
- New methods added:
  - `getProcurementInventory()` - Fetch inventory list
  - `getProcurementInventoryItem(id)` - Get single item
  - `getProcurementInventorySummary()` - Get statistics
  - `getProcurementLowStockItems()` - Get low stock items
  - `initializeProcurementInventory()` - Initialize items
  - `updateProcurementInventory()` - Update inventory

### 7. **Frontend - RFQ Create Form**
- **File**: `frontend/src/views/system/procurement/RFQs/Create.vue`
- **Changes**:
  - Now fetches products from Procurement Inventory (not Branch Inventory)
  - Products include inventory tracking fields
  - Updated Product interface with inventory fields

## How It Works

### Workflow:

```
Product Catalog
       ↓
Procurement Inventory Created
  ├─ available_qty: 100 (can order from suppliers)
  ├─ on_order_qty: 0 (not ordered yet)
  ├─ received_qty: 0 (nothing received)
  └─ pending_receive_qty: 0
       ↓
User Creates RFQ → Selects Products
       ↓
RFQ Products Show Available Qty
       ↓ (Next steps - to be implemented)
Create PO from RFQ
  ├─ Call: addToOrder(qty, poNumber)
  ├─ available_qty: 70 (decreased)
  ├─ on_order_qty: 30 (increased)
  └─ pending_receive_qty: 30
       ↓
Receive Goods Receipt (GR)
  ├─ Call: addToReceived(qty, grNumber)
  ├─ on_order_qty: 0 (decreased)
  ├─ received_qty: 30 (increased)
  ├─ pending_receive_qty: 0 (decreased)
  └─ Items then go to Branch Inventory
```

## API Examples

### Get Procurement Inventory
```bash
GET /api/procurement/inventory?per_page=15&search=chair
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product": {
        "id": 5,
        "product_name": "Executive Chair",
        "sku": "CHAIR-001"
      },
      "available_qty": 100,
      "on_order_qty": 50,
      "received_qty": 200,
      "pending_receive_qty": 50,
      "last_order_date": "2026-03-10T10:30:00"
    }
  ]
}
```

### Get Summary
```bash
GET /api/procurement/inventory/summary
```

### Low Stock Alert
```bash
GET /api/procurement/inventory/low-stock?threshold=20
```

### Initialize Inventory
```bash
POST /api/procurement/inventory/init
Body: {
  "product_ids": [1, 2, 3, 4, 5],
  "initial_qty": 100
}
```

### Update Inventory
```bash
PUT /api/procurement/inventory/1
Body: {
  "available_qty": 150,
  "status": "active",
  "notes": "Stock added from manual count"
}
```

## Next Steps to Integrate

1. **Update Purchase Order Controller** 
   - When PO created: Call `procInventory.addToOrder()`
   - When PO cancelled: Call `procInventory.cancelOrder()`

2. **Update Goods Receipt Controller**
   - When GR created: Call `procInventory.addToReceived()`
   - This moves items to Branch Inventory

3. **Update Frontend RFQ Display**
   - Show procurement inventory levels when selecting products
   - Optional: Show low stock warnings

4. **Seed Initial Data**
   - Initialize procurement inventory for all products in the system

## Database Migration
Already executed:
```
✓ 2026_03_11_000007_create_procurement_inventories_table
```

## Files Modified/Created

### Created:
- `backend/database/migrations/2026_03_11_000007_create_procurement_inventories_table.php`
- `backend/app/Models/Procurement/Inventory/ProcurementInventory.php`
- `backend/app/Http/Controllers/Api/Procurement/Inventory/ProcurementInventoryController.php`

### Modified:
- `backend/routes/procurement_routes.php` - Added inventory routes
- `backend/app/Http/Controllers/Api/Procurement/ProductController.php` - Updated to use procurement inventory
- `frontend/src/services/procurement.service.ts` - Added inventory methods
- `frontend/src/views/system/procurement/RFQs/Create.vue` - Updated to fetch from procurement inventory

## Status
✅ Complete - Setup, migration run, routes added, frontend updated
⏳ Pending - PO/GR integration to call inventory methods
