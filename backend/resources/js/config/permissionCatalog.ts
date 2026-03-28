export type PermissionFeature = {
  key: string
  label: string
  permissions: string[]
}

export type PermissionModule = {
  key: string
  label: string
  features: PermissionFeature[]
}

export const PERMISSION_CATALOG: PermissionModule[] = [
  {
    key: 'inventory',
    label: 'Inventory',
    features: [
      {
        key: 'products_catalog',
        label: 'Products / Catalog',
        permissions: [
          'inventory.products.view',
          'inventory.products.create',
          'inventory.products.update',
        ],
      },
      {
        key: 'categories_units',
        label: 'Categories & Units',
        permissions: [
          'inventory.categories.view',
          'inventory.categories.create',
          'inventory.categories.update',
          'inventory.units.view',
          'inventory.units.create',
          'inventory.units.update',
        ],
      },
      {
        key: 'stock_in_out',
        label: 'Stock In / Stock Out',
        permissions: [
          'inventory.adjustments.view',
          'inventory.adjustments.manage',
        ],
      },
      {
        key: 'stock_counts',
        label: 'Stock Counts',
        permissions: [
          'inventory.stock_counts.view',
          'inventory.stock_counts.manage',
        ],
      },
      {
        key: 'low_stock_alerts',
        label: 'Low-stock Alerts',
        permissions: [
          'inventory.alerts.view',
        ],
      },
      {
        key: 'branch_inventory',
        label: 'Branch Inventory',
        permissions: [
          'inventory.branch_inventory.view',
          'inventory.branch_inventory.manage',
        ],
      },
      {
        key: 'transfers',
        label: 'Stock Transfers',
        permissions: [
          'inventory.transfers.view',
          'inventory.transfers.manage',
        ],
      },
      {
        key: 'requisites',
        label: 'Purchase Requisitions',
        permissions: [
          'inventory.requisites.manage',
          'inventory.requisites.approve',
        ],
      },
    ],
  },
  {
    key: 'sales',
    label: 'Sales / POS',
    features: [
      {
        key: 'basic_pos',
        label: 'Basic POS',
        permissions: [
          'sales.pos.manage',
        ],
      },
      {
        key: 'sales_orders',
        label: 'Orders (POS + Online)',
        permissions: [
          'sales.pos.view',
          'sales.ecommerce-orders.view',
        ],
      },
      {
        key: 'customer_list_basic',
        label: 'Customer List (basic)',
        permissions: [
          'sales.crm.manage',
        ],
      },
      {
        key: 'sales_summary',
        label: 'Sales Summary',
        permissions: [
          'sales.dashboard.view',
          'sales.analytics.view',
        ],
      },
      {
        key: 'sales_chats',
        label: 'Sales Chats',
        permissions: [
          'sales.chats.manage',
        ],
      },
    ],
  },
  {
    key: 'procurement',
    label: 'Procurement',
    features: [
      {
        key: 'suppliers',
        label: 'Suppliers',
        permissions: [
          'procurement.suppliers.manage',
        ],
      },
      {
        key: 'purchase_orders',
        label: 'Purchase Orders',
        permissions: [
          'procurement.purchase_orders.manage',
        ],
      },
      {
        key: 'goods_receipts',
        label: 'Goods Receipt',
        permissions: [
          'procurement.receiving.manage',
        ],
      },
      {
        key: 'requisitions',
        label: 'Purchase Requisitions',
        permissions: [
          'procurement.requisitions.manage',
        ],
      },
      {
        key: 'rfq',
        label: 'RFQ Management',
        permissions: [
          'procurement.rfq.manage',
        ],
      },
      {
        key: 'invoices',
        label: 'Invoices',
        permissions: [
          'procurement.invoices.manage',
          'procurement.invoices.approve',
        ],
      },
      {
        key: 'supplier_contracts',
        label: 'Supplier Contracts',
        permissions: [
          'procurement.supplier_contracts.manage',
          'procurement.supplier_contracts.approve',
        ],
      },
    ],
  },
  {
    key: 'hr',
    label: 'Employees / HR',
    features: [
      {
        key: 'employees',
        label: 'Employee List & Manual Add',
        permissions: [
          'hr.employees.view',
          'hr.employees.create',
        ],
      },
      {
        key: 'simple_payroll',
        label: 'Simple Payroll Batch',
        permissions: [
          'hr.payroll.view',
          'hr.payroll.create',
        ],
      },
    ],
  },
  {
    key: 'finance',
    label: 'Finance',
    features: [
      {
        key: 'expenses_simple',
        label: 'Expenses (simple)',
        permissions: [
          'finance.expenses.view',
          'finance.expenses.manage',
        ],
      },
      {
        key: 'cashflow_summary',
        label: 'Cashflow Summary (lite)',
        permissions: [
          'finance.cashflow.view',
        ],
      },
      {
        key: 'invoice_log_basic',
        label: 'Basic Invoice Log',
        permissions: [
          'finance.invoices.view',
        ],
      },
      {
        key: 'purchase_orders_approval',
        label: 'Purchase Order Approval',
        permissions: [
          'finance.purchase-orders.view',
          'finance.purchase-orders.approve',
        ],
      },
    ],
  },
]

const SMALL_TIER_FEATURES: Record<string, string[]> = {
  inventory: [
    'products_catalog',
    'categories_units',
    'stock_in_out',
    'stock_counts',
    'low_stock_alerts',
  ],
  sales: [
    'basic_pos',
    'sales_orders',
    'customer_list_basic',
    'sales_summary',
  ],
  procurement: [
    'suppliers',
    'purchase_orders',
    'goods_receipts',
  ],
  hr: [
    'employees',
    'simple_payroll',
  ],
  finance: [
    'expenses_simple',
    'cashflow_summary',
    'invoice_log_basic',
  ],
}

export const getTierPermissionFeatures = (tier: 'small' | 'mid' | 'enterprise') => {
  if (tier === 'small') {
    return SMALL_TIER_FEATURES
  }
  return PERMISSION_CATALOG.reduce<Record<string, string[]>>((acc, mod) => {
    acc[mod.key] = mod.features.map(feature => feature.key)
    return acc
  }, {})
}

export const getTierPermissionList = (tier: 'small' | 'mid' | 'enterprise') => {
  const featureMap = getTierPermissionFeatures(tier)
  const permissions = new Set<string>()

  PERMISSION_CATALOG.forEach(module => {
    const allowedFeatures = featureMap[module.key] || []
    module.features.forEach(feature => {
      if (allowedFeatures.includes(feature.key)) {
        feature.permissions.forEach(perm => permissions.add(perm))
      }
    })
  })

  return Array.from(permissions).sort()
}
