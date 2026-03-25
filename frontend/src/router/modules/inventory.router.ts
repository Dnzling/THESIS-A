// frontend/src/router/modules/inventory.ts

import type { RouteRecordRaw } from 'vue-router';

const SystemLayout = () => import('../../layouts/SystemLayout.vue');

const inventoryRoutes: RouteRecordRaw[] = [
  {
    path: '/inventory',
    component: SystemLayout,
    name: 'inventory',
    redirect: '/inventory/dashboard',
    meta: {
      requiresAuth: true,
      module: 'inventory',
      permission: 'inventory.dashboard.view',
    },
    children: [
      // ==================== DASHBOARD ====================
      {
        path: 'dashboard',
        name: 'inventory.dashboard',
        component: () => import('../../views/system/inventory/InventoryDashboard.vue'),
        meta: {
          title: 'Inventory Dashboard',
          permission: 'inventory.dashboard.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Dashboard' },
          ],
        },
      },

      // ==================== ECOMMERCE ORDERS ====================
      {
        path: 'ecommerce-orders',
        name: 'inventory.ecommerce-orders',
        redirect: '/sales/ecommerce-orders',
        meta: {
          title: 'Ecommerce Orders',
          permission: 'sales.ecommerce-orders.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Ecommerce Orders', to: '/sales/ecommerce-orders' },
          ],
        },
      },
      {
        path: 'ecommerce-orders/:id',
        name: 'inventory.ecommerce-orders.detail',
        redirect: to => `/sales/ecommerce-orders/${to.params.id}`,
        meta: {
          title: 'Order Detail',
          permission: 'sales.ecommerce-orders.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Ecommerce Orders', to: '/sales/ecommerce-orders' },
            { label: 'Detail' },
          ],
        },
      },

      // ==================== ECOMMERCE DELIVERIES ====================
      {
        path: 'ecommerce-deliveries',
        name: 'inventory.ecommerce-deliveries',
        redirect: '/logistics/deliveries',
        meta: {
          title: 'Delivery Management',
          permission: 'inventory.ecommerce_deliveries.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Deliveries' },
          ],
        },
      },
      {
        path: 'ecommerce-deliveries/:id',
        name: 'inventory.ecommerce-deliveries.detail',
        redirect: to => `/logistics/deliveries/${to.params.id}`,
        meta: {
          title: 'Delivery Detail',
          permission: 'inventory.ecommerce_deliveries.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Deliveries', to: '/inventory/ecommerce-deliveries' },
            { label: 'Detail' },
          ],
        },
      },

      {
        path: 'delivery-vehicles',
        name: 'inventory.delivery-vehicles',
        redirect: '/logistics/vehicles',
        meta: {
          title: 'Delivery Vehicles',
          permission: 'inventory.ecommerce_deliveries.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Delivery Vehicles' },
          ],
        },
      },

      // ==================== BRANCH INVENTORY ====================
      {
        path: 'items',
        name: 'inventory.items',
        component: () => import('../../views/system/inventory/Stocks/StocksIndex.vue'),
        meta: {
          title: 'Branch Inventory',
          permission: 'inventory.branch_inventory.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Branch Inventory' },
          ],
        },
      },
      {
        path: 'items/create',
        name: 'inventory.items.create',
        component: () => import('../../views/system/inventory/Stocks/ItemsCreate.vue'),
        meta: {
          title: 'Add Inventory Item',
          permission: 'inventory.branch_inventory.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Branch Inventory', to: '/inventory/items' },
            { label: 'Add Item' },
          ],
        },
      },
      {
        path: 'items/:id/edit',
        name: 'inventory.items.edit',
        component: () => import('../../views/system/inventory/Stocks/ItemsEdit.vue'),
        meta: {
          title: 'Edit Inventory Item',
          permission: 'inventory.branch_inventory.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Branch Inventory', to: '/inventory/items' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== PRODUCTS ====================
      {
        path: 'products',
        name: 'inventory.products',
        component: () => import('../../views/system/inventory/Products/ProductIndex.vue'),
        meta: {
          title: 'Product Catalog',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Products' },
          ],
        },
      },
      {
        path: 'products/:id',
        name: 'inventory.products.detail',
        component: () => import('../../views/system/inventory/Products/ProductDetail.vue'),
        meta: {
          title: 'Product Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Products', to: '/inventory/products' },
            { label: 'Details' },
          ],
        },
      },
      // ==================== CATEGORIES ====================
      {
        path: 'categories',
        name: 'inventory.categories',
        component: () => import('../../views/system/inventory/Categories/CategoryIndex.vue'),
        meta: {
          title: 'Categories',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Categories' },
          ],
        },
      },
      {
        path: 'categories/:id',
        name: 'inventory.categories.detail',
        component: () => import('../../views/system/inventory/Categories/CategoryDetail.vue'),
        meta: {
          title: 'Category Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Categories', to: '/inventory/categories' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== UNITS ====================
      {
        path: 'units',
        name: 'inventory.units',
        component: () => import('../../views/system/inventory/Units/UnitIndex.vue'),
        meta: {
          title: 'Units',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Units' },
          ],
        },
      },
      {
        path: 'units/create',
        name: 'inventory.units.create',
        component: () => import('../../views/system/inventory/Units/UnitCreate.vue'),
        meta: {
          title: 'Create Unit',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Units', to: '/inventory/units' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'units/:id',
        name: 'inventory.units.detail',
        component: () => import('../../views/system/inventory/Units/UnitDetail.vue'),
        meta: {
          title: 'Unit Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Units', to: '/inventory/units' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'units/:id/edit',
        name: 'inventory.units.edit',
        component: () => import('../../views/system/inventory/Units/UnitEdit.vue'),
        meta: {
          title: 'Edit Unit',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Units', to: '/inventory/units' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== STOCK ISSUES ====================
      {
        path: 'stock-issues',
        name: 'inventory.stock-issues',
        component: () => import('../../views/system/inventory/StockIssues/StockIssueIndex.vue'),
        meta: {
          title: 'Stock Issues',
          permission: 'inventory.stock_issues.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Issues' },
          ],
        },
      },
      {
        path: 'stock-issues/create',
        name: 'inventory.stock-issues.create',
        component: () => import('../../views/system/inventory/StockIssues/StockIssueCreate.vue'),
        meta: {
          title: 'Create Stock Issue',
          permission: 'inventory.stock_issues.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Issues', to: '/inventory/stock-issues' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'stock-issues/:id',
        name: 'inventory.stock-issues.detail',
        component: () => import('../../views/system/inventory/StockIssues/StockIssueDetail.vue'),
        meta: {
          title: 'Stock Issue Details',
          permission: 'inventory.stock_issues.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Issues', to: '/inventory/stock-issues' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'stock-issues/:id',
        name: 'inventory.stock-issues.edit',
        component: () => import('../../views/system/inventory/StockIssues/StockIssueEdit.vue'),
        meta: {
          title: 'Stock Issue Edit',
          permission: 'inventory.stock_issues.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Issues', to: '/inventory/stock-issues' },
            { label: 'Details' },
          ],
        },
      },

      // =================== Purchase ORders ===========
      { path: 'requisites', name: 'inventory.requisites.index', component: () => import('../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionIndex.vue'), meta: { title: 'Purchase Requisitions', subtitle: 'Manage purchase requisitions for your branch', permission: 'inventory.requisites.view' } },
      { path: 'requisites/create', name: 'inventory.requisites.create', component: () => import('../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionCreate.vue'), meta: { title: 'Create Purchase Requisition', subtitle: 'Request inventory replenishment for your branch', permission: 'inventory.requisites.manage' } },
      { path: 'requisites/:id', name: 'inventory.requisites.detail', component: () => import('../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionDetail.vue'), meta: { title: 'Purchase Requisition Details', subtitle: 'View purchase requisition details', permission: 'inventory.requisites.view' } },
      // { path: 'requisites/:id/edit', name: 'inventory.requisites.edit', component: () => import('../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionEdit.vue'), meta: { title: 'Edit Stock Order Request', subtitle: 'Update stock order request details' } },

      // ==================== STOCK RETURNS ====================
      {
        path: 'stock-returns',
        name: 'inventory.stock-returns',
        component: () => import('../../views/system/inventory/StockReturns/StockReturnIndex.vue'),
        meta: {
          title: 'Stock Returns',
          permission: 'inventory.stock_returns.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Returns' },
          ],
        },
      },
      {
        path: 'stock-returns/create',
        name: 'inventory.stock-returns.create',
        component: () => import('../../views/system/inventory/StockReturns/StockReturnCreate.vue'),
        meta: {
          title: 'Create Stock Return',
          permission: 'inventory.stock_returns.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Returns', to: '/inventory/stock-returns' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'stock-returns/:id',
        name: 'inventory.stock-returns.detail',
        component: () => import('../../views/system/inventory/StockReturns/StockReturnDetail.vue'),
        meta: {
          title: 'Stock Return Details',
          permission: 'inventory.stock_returns.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Returns', to: '/inventory/stock-returns' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== STOCK COUNTS ====================
      {
        path: 'stock-counts',
        name: 'inventory.stock-counts',
        component: () => import('../../views/system/inventory/StockCounts/StockCountIndex.vue'),
        meta: {
          title: 'Stock Counts',
          permission: 'inventory.stock_counts.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Counts' },
          ],
        },
      },
      {
        path: 'stock-counts/create',
        name: 'inventory.stock-counts.create',
        component: () => import('../../views/system/inventory/StockCounts/StockCountCreate.vue'),
        meta: {
          title: 'Create Stock Count',
          permission: 'inventory.stock_counts.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Counts', to: '/inventory/stock-counts' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'stock-counts/:id',
        name: 'inventory.stock-counts.detail',
        component: () => import('../../views/system/inventory/StockCounts/StockCountDetail.vue'),
        meta: {
          title: 'Stock Count Details',
          permission: 'inventory.stock_counts.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Counts', to: '/inventory/stock-counts' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== WAREHOUSES ====================
      {
        path: 'warehouses',
        name: 'inventory.warehouses',
        component: () => import('../../views/system/inventory/Warehouses/WarehouseIndex.vue'),
        meta: {
          title: 'Warehouses',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Warehouses' },
          ],
        },
      },
      {
        path: 'warehouses/create',
        name: 'inventory.warehouses.create',
        component: () => import('../../views/system/inventory/Warehouses/WarehouseCreate.vue'),
        meta: {
          title: 'Create Warehouse',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Warehouses', to: '/inventory/warehouses' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'warehouses/:id',
        name: 'inventory.warehouses.detail',
        component: () => import('../../views/system/inventory/Warehouses/WarehouseDetail.vue'),
        meta: {
          title: 'Warehouse Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Warehouses', to: '/inventory/warehouses' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'warehouses/:id/edit',
        name: 'inventory.warehouses.edit',
        component: () => import('../../views/system/inventory/Warehouses/WarehouseEdit.vue'),
        meta: {
          title: 'Edit Warehouse',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Warehouses', to: '/inventory/warehouses' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== LOCATIONS ====================
      {
        path: 'locations',
        name: 'inventory.locations',
        component: () => import('../../views/system/inventory/Locations/LocationIndex.vue'),
        meta: {
          title: 'Locations',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Locations' },
          ],
        },
      },
      {
        path: 'locations/create',
        name: 'inventory.locations.create',
        component: () => import('../../views/system/inventory/Locations/LocationCreate.vue'),
        meta: {
          title: 'Create Location',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Locations', to: '/inventory/locations' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'locations/:id',
        name: 'inventory.locations.detail',
        component: () => import('../../views/system/inventory/Locations/LocationDetail.vue'),
        meta: {
          title: 'Location Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Locations', to: '/inventory/locations' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'locations/:id/edit',
        name: 'inventory.locations.edit',
        component: () => import('../../views/system/inventory/Locations/LocationEdit.vue'),
        meta: {
          title: 'Edit Location',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Locations', to: '/inventory/locations' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== REORDER RULES ====================
      {
        path: 'reorder-rules',
        name: 'inventory.reorder-rules',
        component: () => import('../../views/system/inventory/ReorderRules/ReorderRuleIndex.vue'),
        meta: {
          title: 'Reorder Rules',
          permission: 'inventory.reorder_rules.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Rules' },
          ],
        },
      },
      {
        path: 'reorder-rules/create',
        name: 'inventory.reorder-rules.create',
        component: () => import('../../views/system/inventory/ReorderRules/ReorderRuleCreate.vue'),
        meta: {
          title: 'Create Reorder Rule',
          permission: 'inventory.reorder_rules.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Rules', to: '/inventory/reorder-rules' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'reorder-rules/:id',
        name: 'inventory.reorder-rules.detail',
        component: () => import('../../views/system/inventory/ReorderRules/ReorderRuleDetail.vue'),
        meta: {
          title: 'Reorder Rule Details',
          permission: 'inventory.reorder_rules.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Rules', to: '/inventory/reorder-rules' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'reorder-rules/:id/edit',
        name: 'inventory.reorder-rules.edit',
        component: () => import('../../views/system/inventory/ReorderRules/ReorderRuleEdit.vue'),
        meta: {
          title: 'Edit Reorder Rule',
          permission: 'inventory.reorder_rules.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Rules', to: '/inventory/reorder-rules' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== REORDER SUGGESTIONS ====================
      {
        path: 'reorder-suggestions',
        name: 'inventory.reorder-suggestions',
        component: () => import('../../views/system/inventory/ReorderSuggestions/ReorderSuggestionIndex.vue'),
        meta: {
          title: 'Reorder Suggestions',
          permission: 'inventory.reorder_suggestions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Suggestions' },
          ],
        },
      },
      {
        path: 'reorder-suggestions/:id',
        name: 'inventory.reorder-suggestions.detail',
        component: () => import('../../views/system/inventory/ReorderSuggestions/ReorderSuggestionDetail.vue'),
        meta: {
          title: 'Reorder Suggestion Details',
          permission: 'inventory.reorder_suggestions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reorder Suggestions', to: '/inventory/reorder-suggestions' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== SERIAL NUMBERS ====================
      {
        path: 'serial-numbers',
        name: 'inventory.serial-numbers',
        component: () => import('../../views/system/inventory/SerialNumbers/SerialNumberIndex.vue'),
        meta: {
          title: 'Serial Numbers',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Serial Numbers' },
          ],
        },
      },
      {
        path: 'serial-numbers/create',
        name: 'inventory.serial-numbers.create',
        component: () => import('../../views/system/inventory/SerialNumbers/SerialNumberCreate.vue'),
        meta: {
          title: 'Create Serial Number',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Serial Numbers', to: '/inventory/serial-numbers' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'serial-numbers/:id',
        name: 'inventory.serial-numbers.detail',
        component: () => import('../../views/system/inventory/SerialNumbers/SerialNumberDetail.vue'),
        meta: {
          title: 'Serial Number Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Serial Numbers', to: '/inventory/serial-numbers' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'serial-numbers/:id/edit',
        name: 'inventory.serial-numbers.edit',
        component: () => import('../../views/system/inventory/SerialNumbers/SerialNumberEdit.vue'),
        meta: {
          title: 'Edit Serial Number',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Serial Numbers', to: '/inventory/serial-numbers' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== BATCHES ====================
      {
        path: 'batches',
        name: 'inventory.batches',
        component: () => import('../../views/system/inventory/Batches/BatchIndex.vue'),
        meta: {
          title: 'Batches',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Batches' },
          ],
        },
      },
      {
        path: 'batches/create',
        name: 'inventory.batches.create',
        component: () => import('../../views/system/inventory/Batches/BatchCreate.vue'),
        meta: {
          title: 'Create Batch',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Batches', to: '/inventory/batches' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'batches/:id',
        name: 'inventory.batches.detail',
        component: () => import('../../views/system/inventory/Batches/BatchDetail.vue'),
        meta: {
          title: 'Batch Details',
          permission: 'inventory.master_data.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Batches', to: '/inventory/batches' },
            { label: 'Details' },
          ],
        },
      },
      {
        path: 'batches/:id/edit',
        name: 'inventory.batches.edit',
        component: () => import('../../views/system/inventory/Batches/BatchEdit.vue'),
        meta: {
          title: 'Edit Batch',
          permission: 'inventory.master_data.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Batches', to: '/inventory/batches' },
            { label: 'Edit' },
          ],
        },
      },

      // ==================== STOCK ADJUSTMENTS ====================
      {
        path: 'adjustments',
        name: 'inventory.adjustments',
        component: () => import('../../views/system/inventory/Adjustments/AdjustmentIndex.vue'),
        meta: {
          title: 'Stock Adjustments',
          permission: 'inventory.adjustments.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Adjustments' },
          ],
        },
      },
      {
        path: 'adjustments/create',
        name: 'inventory.adjustments.create',
        component: () => import('../../views/system/inventory/Adjustments/AdjustmentCreate.vue'),
        meta: {
          title: 'Create Stock Adjustment',
          permission: 'inventory.adjustments.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Adjustments', to: '/inventory/adjustments' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'adjustments/:id',
        name: 'inventory.adjustments.detail',
        component: () => import('../../views/system/inventory/Adjustments/AdjustmentDetail.vue'),
        meta: {
          title: 'Adjustment Details',
          permission: 'inventory.adjustments.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Adjustments', to: '/inventory/adjustments' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== STOCK TRANSFERS ====================
      {
        path: 'transfers',
        name: 'inventory.transfers',
        component: () => import('../../views/system/inventory/Transfers/TransferIndex.vue'),
        meta: {
          title: 'Stock Transfers',
          permission: 'inventory.transfers.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Transfers' },
          ],
        },
      },
      {
        path: 'transfers/create',
        name: 'inventory.transfers.create',
        component: () => import('../../views/system/inventory/Transfers/TransferCreate.vue'),
        meta: {
          title: 'Create Stock Transfer',
          permission: 'inventory.transfers.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Transfers', to: '/inventory/transfers' },
            { label: 'Create' },
          ],
        },
      },
      {
        path: 'transfers/:id',
        name: 'inventory.transfers.detail',
        component: () => import('../../views/system/inventory/Transfers/TransferDetail.vue'),
        meta: {
          title: 'Transfer Details',
          permission: 'inventory.transfers.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Transfers', to: '/inventory/transfers' },
            { label: 'Details' },
          ],
        },
      },

      // ==================== STOCK ALERTS ====================
      {
        path: 'alerts',
        name: 'inventory.alerts',
        component: () => import('../../views/system/inventory/Alerts/AlertsIndex.vue'),
        meta: {
          title: 'Stock Alerts',
          permission: 'inventory.alerts.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Stock Alerts' },
          ],
        },
      },

      // ==================== INVENTORY TRANSACTIONS ====================
      {
        path: 'transactions',
        name: 'inventory.transactions',
        component: () => import('../../views/system/inventory/Transactions/TransactionIndex.vue'),
        meta: {
          title: 'Inventory Transactions',
          permission: 'inventory.transactions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Transactions' },
          ],
        },
      },
      {
        path: 'transactions/:id',
        name: 'inventory.transactions.detail',
        component: () => import('../../views/system/inventory/Transactions/TransactionDetail.vue'),
        meta: {
          title: 'Inventory Transactions',
          permission: 'inventory.transactions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Transactions' },
          ],
        },
      },

      // ==================== INVENTORY ACTIVITY LOGS ====================
      {
        path: 'activity-logs',
        name: 'inventory.activity-logs',
        component: () => import('../../views/system/inventory/ActivityLogs/InventoryActivityLogIndex.vue'),
        meta: {
          title: 'Inventory Activity Logs',
          permission: 'inventory.transactions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Activity Logs' },
          ],
        },
      },
      {
        path: 'activity-logs/:id',
        name: 'inventory.activity-logs.detail',
        component: () => import('../../views/system/inventory/ActivityLogs/InventoryActivityLogDetail.vue'),
        meta: {
          title: 'Inventory Log Detail',
          permission: 'inventory.transactions.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Activity Logs', to: '/inventory/activity-logs' },
            { label: 'Detail' },
          ],
        },
      },

      // ==================== REPORTS ====================
      {
        path: 'reports',
        name: 'inventory.reports',
        component: () => import('../../views/system/inventory/Reports/ReportsIndex.vue'),
        meta: {
          title: 'Inventory Reports',
          permission: 'inventory.reports.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Reports' },
          ],
        },
      },

      // ==================== NOTIFICATIONS ====================
      {
        path: 'notifications',
        name: 'inventory.notifications',
        component: () => import('../../views/system/inventory/Notifications/NotificationIndex.vue'),
        meta: {
          title: 'Notifications',
          permission: 'inventory.notifications.view',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Notifications' },
          ],
        },
      },

      // ==================== CONFIGURATION ====================
      {
        path: 'configuration',
        name: 'inventory.configuration',
        component: () => import('../../views/system/inventory/Configuration/ConfigIndex.vue'),
        meta: {
          title: 'Inventory Configuration',
          permission: 'inventory.configuration.manage',
          breadcrumb: [
            { label: 'Inventory', to: '/inventory' },
            { label: 'Configuration' },
          ],
        },
      },
    ],
  },
];

export default inventoryRoutes;
