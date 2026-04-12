<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$render = function (string $page, array $props = []) {
    return Inertia::render($page, array_filter($props, fn($value) => $value !== null));
};

$inertia = function (string $uri, string $page, ?string $name = null, ?string $title = null, ?string $subtitle = null) use ($render) {
    $route = Route::get($uri, function () use ($render, $page, $title, $subtitle) {
        return $render($page, [
            'title' => $title,
            'subtitle' => $subtitle,
        ]);
    });

    if ($name) {
        $route->name($name);
    }

    return $route;
};


Route::middleware(['auth:sanctum', 'trial.setup'])->group(function () use ($inertia) {


    // Admin (Super Admin only)
    Route::middleware('role:super_admin')->group(function () use ($inertia) {
        $inertia('/admin/dashboard', 'System/Admin/Dashboard', 'AdminDashboard', 'Dashboard');
        $inertia('/admin/roles-permissions', 'System/Admin/RolePermissions', 'admin.role-permissions', 'Role Permissions');
        $inertia('/admin/modules', 'System/Admin/StoreModules', 'admin.modules', 'Store Modules');
        $inertia('/admin/subscription', 'System/Admin/Subscriptions', 'AdminSubscription', 'Subscription');
        $inertia('/admin/subscription-plans/{id}', 'System/Admin/SubscriptionPlanShow', 'admin.subscription-plans.show', 'Subscription Plan');
        $inertia('/admin/store-validation', 'System/Admin/Storevalidation', 'AdminStoreValidation', 'Store Validation');
        $inertia('/admin/customer-validation', 'System/Admin/Customervalidation', 'AdminCustomerValidation', 'Customer Validation');
        $inertia('/admin/verification/suppliers', 'System/Admin/SupplierVerification', 'admin.supplier-verification', 'Supplier Verification');
        $inertia('/admin/support-&-maintenance', 'System/Admin/SupportMaintenance', 'admin.support-maintenance', 'Support & Maintenance', 'Super Admin Management');
        $inertia('/admin/verification/suppliers/{id}', 'System/Admin/SupplierVerificationShow', 'admin.supplier-verification.show', 'Supplier Verification Details');
        $inertia('/admin/customer-management', 'System/Admin/CustomerManagement', 'admin.customer-management', 'Customer Management');
        $inertia('/admin/stores', 'System/Admin/StoresIndex', 'admin.stores', 'Stores');
        $inertia('/admin/stores/{id}', 'System/Admin/StoreDetail', 'admin.stores.detail', 'Store Detail');
        $inertia('/admin/users', 'System/Admin/UsersIndex', 'admin.users', 'Users');
        $inertia('/admin/violation-reports', 'System/Admin/ViolationReportsIndex', 'admin.violation-reports', 'Violation Reports');
        $inertia('/admin/violation-reports/{id}', 'System/Admin/ViolationReportShow', 'admin.violation-reports.show', 'Violation Report');
        Route::redirect('/admin/suppliers', '/admin/suppliers/list')->name('admin.suppliers');
        $inertia('/admin/suppliers/list', 'System/Supplier/SupplierList', 'admin.suppliers.list', 'Supplier List');
        $inertia('/admin/suppliers/{id}', 'System/Supplier/SupplierDetail', 'admin.suppliers.detail', 'Supplier Details');
        $inertia('/admin/suppliers/dashboard', 'System/Supplier/SupplierDashboard', 'admin.suppliers.dashboard', 'Supplier Dashboard');

    });

    // System (Store Admin)u
    Route::middleware('role:store_admin')->group(function () use ($inertia) {
        $inertia('/store/index', 'System/StoreAdmin/Dashboard', 'store.index', 'Dashboard');
        $inertia('/store/settings', 'System/StoreAdmin/Settings', 'store.settings', 'Settings', 'Trial & Configuration');
        $inertia('/store/setup-required', 'System/StoreAdmin/SetupRequired', 'store.setup-required', 'Setup Required');
        $inertia('/store/branches', 'System/StoreAdmin/BranchesIndex', 'store.branches', 'Branches');
        $inertia('/store/branches/{id}', 'System/StoreAdmin/BranchShow', 'store.branches.show', 'Branch Detail');
    });

    // Store role permissions accessible by store admins and HR
    $inertia('/store/role-permissions', 'System/StoreAdmin/RolePermissions', 'store.role-permissions', 'Role Permissions');
    // HR
    $inertia('/hr/index', 'System/HR/index', 'hr.dashboard', 'HR Dashboard');
    $inertia('/hr/employees', 'System/HR/Employees', 'hr.employees', 'Employees');
    $inertia('/hr/employees/view/{id?}', 'System/HR/EmployeeView', 'hr.employees.view', 'View Employee');
    Route::get('/hr/profile', fn() => redirect('/profile'));
    $inertia('/hr/shifts', 'System/HR/ShiftIndex', 'hr.shifts', 'Shift Management');
    $inertia('/hr/shifts/employees', 'System/HR/EmployeeShifts', 'hr.shifts.employees', 'Employee Shifts');
    $inertia('/hr/shifts/create', 'System/HR/ShiftCreate', 'hr.shifts.create', 'Create Shift');
    $inertia('/hr/attendance', 'System/HR/Attendance', 'hr.attendance', 'Attendance');
    $inertia('/hr/departments', 'System/HR/Department', 'hr.departments', 'Departments');
    $inertia('/hr/leave-management', 'System/HR/LeaveManagement', 'hr.leave', 'Leave Management');
    $inertia('/hr/leave-balances', 'System/HR/LeaveBalances', 'hr.leave.balances', 'Leave Balances');
    $inertia('/hr/leaves', 'System/HR/LeaveHistory', 'hr.leaves.history', 'Leave History');
    $inertia('/hr/leaves/{id}', 'System/HR/LeaveDetail', 'hr.leaves.detail', 'Leave Details');
    $inertia('/hr/analytics', 'System/HR/Analytics', 'hr.analytics', 'Analytics');
    $inertia('/hr/settings', 'System/HR/Settings', 'hr.settings', 'Settings');
    $inertia('/hr/payroll', 'System/HR/PayrollList', 'hr.payroll', 'Payroll');
    $inertia('/hr/payroll/overview', 'System/HR/PayrollOverview', 'hr.payroll.overview', 'Payroll Overview');
    $inertia('/hr/payroll/periods', 'System/HR/PayPeriods', 'hr.payroll.periods', 'Pay Periods');
    $inertia('/hr/payroll/lists', 'System/HR/PayrollList', 'hr.payroll.list', 'Edit Payroll');
    $inertia('/hr/payroll/create', 'System/HR/PayrollCreate', 'hr.payroll.create', 'Generate Payroll');
    $inertia('/hr/payroll/view/{id}', 'System/HR/PayrollView', 'hr.payroll.view', 'View Payroll');
    $inertia('/hr/payroll/edit/{id}', 'System/HR/PayrollEdit', 'hr.payroll.edit', 'Edit Payroll');
    $inertia('/hr/recuitment', 'System/HR/JobPostings/JobPostingsIndex', 'hr.recuitment', 'Job Postings');
    $inertia('/hr/recuitment/postings/{postingId}', 'System/HR/JobPostings/JobPostingDetailView', 'hr.recuitment.detail', 'Job Posting Overview');
    $inertia('/hr/recuitment/postings/{postingId}/applicants', 'System/HR/JobPostings/JobPostingApplicantsList', 'hr.recuitment.applicants', 'Applicants');
    $inertia('/hr/recuitment/postings/{postingId}/screening', 'System/HR/JobPostings/JobPostingsScreening', 'hr.screening-pipeline', 'Screening Pipeline');
    $inertia('/hr/recuitment/postings/{postingId}/apply', 'System/HR/JobPostings/JobPostingsApply', 'hr.apply-job', 'Apply for Job');
    $inertia('/hr/recuitment/applications/{applicationId}/review', 'System/HR/JobPostings/ApplicantReviewDetail', 'hr.job-applications.review', 'Applicant Review');
    $inertia('/hr/recuitment/applications/{applicationId}/decision', 'System/HR/JobPostings/ApplicantDecision', 'hr.job-applications.decision', 'Applicant Decision');
    $inertia('/hr/recuitment/applications/{applicationId}/onboarding', 'System/HR/JobPostings/EmployeeOnboardingCreate', 'hr.job-applications.onboarding', 'Employee Onboarding');

    // Inventory
    Route::redirect('/inventory', '/inventory/dashboard')->name('inventory');
    // Redirect legacy/mistyped requisitions paths to the correct 'requisites' routes
    Route::redirect('/inventory/requisitions', '/inventory/requisites')->name('inventory.requisitions');
    Route::redirect('/inventory/requisitions/create', '/inventory/requisites/create')->name('inventory.requisitions.create');
    Route::redirect('/inventory/requisitions/view', '/inventory/requisites');
    Route::redirect('/inventory/requisitions/view/{id}', '/inventory/requisites/{id}');
    Route::get('/inventory/requisitions/{id}', fn($id) => redirect("/inventory/requisites/{$id}"));
    $inertia('/inventory/dashboard', 'System/Inventory/InventoryDashboard', 'inventory.dashboard', 'Inventory Dashboard');
    Route::redirect('/inventory/ecommerce-orders', '/sales/ecommerce-orders')->name('inventory.ecommerce-orders');
    Route::get('/inventory/ecommerce-orders/{id}', fn($id) => redirect("/sales/ecommerce-orders/{$id}"))->name('inventory.ecommerce-orders.detail');
    Route::redirect('/inventory/ecommerce-deliveries', '/logistics/deliveries')->name('inventory.ecommerce-deliveries');
    Route::get('/inventory/ecommerce-deliveries/{id}', fn($id) => redirect("/logistics/deliveries/{$id}"))->name('inventory.ecommerce-deliveries.detail');
    Route::redirect('/inventory/delivery-vehicles', '/logistics/vehicles')->name('inventory.delivery-vehicles');
    $inertia('/inventory/items', 'System/Inventory/Stocks/StocksIndex', 'inventory.items', 'Branch Inventory');
    $inertia('/inventory/items/create', 'System/Inventory/Stocks/ItemsCreate', 'inventory.items.create', 'Add Inventory Item');
    $inertia('/inventory/items/{id}', 'System/Inventory/Stocks/StockShow', 'inventory.items.show', 'Stock Details');
    $inertia('/inventory/items/{id}/edit', 'System/Inventory/Stocks/ItemsEdit', 'inventory.items.edit', 'Edit Inventory Item');
    $inertia('/inventory/products', 'System/Inventory/Products/ProductIndex', 'inventory.products.index', 'Product Catalog');
    $inertia('/inventory/products/create', 'System/Merchandising/products/ProductForm', 'inventory.products.create', 'Add Product', 'Create a new product');
    $inertia('/inventory/products/{id}', 'System/Inventory/Products/ProductDetail', 'inventory.products.detail', 'Product Details');
    $inertia('/inventory/categories', 'System/Inventory/Categories/CategoryIndex', 'inventory.categories', 'Categories');
    $inertia('/inventory/categories/{id}', 'System/Inventory/Categories/CategoryDetail', 'inventory.categories.detail', 'Category Details');
    $inertia('/inventory/units', 'System/Inventory/Units/UnitIndex', 'inventory.units', 'Units');
    $inertia('/inventory/units/create', 'System/Inventory/Units/UnitCreate', 'inventory.units.create', 'Create Unit');
    $inertia('/inventory/units/{id}', 'System/Inventory/Units/UnitDetail', 'inventory.units.detail', 'Unit Details');
    $inertia('/inventory/units/{id}/edit', 'System/Inventory/Units/UnitEdit', 'inventory.units.edit', 'Edit Unit');
    $inertia('/inventory/stock-issues', 'System/Inventory/StockIssues/StockIssueIndex', 'inventory.stock-issues', 'Stock Issues');
    $inertia('/inventory/stock-issues/create', 'System/Inventory/StockIssues/StockIssueCreate', 'inventory.stock-issues.create', 'Create Stock Issue');
    $inertia('/inventory/stock-issues/{id}', 'System/Inventory/StockIssues/StockIssueDetail', 'inventory.stock-issues.detail', 'Stock Issue Detail');
    $inertia('/inventory/stock-issues/{id}/edit', 'System/Inventory/StockIssues/StockIssueEdit', 'inventory.stock-issues.edit', 'Edit Stock Issue');
    $inertia('/inventory/requisites', 'System/Inventory/PurchaseRequisitions/PurchaseRequisitionIndex', 'inventory.requisites.index', 'Purchase Requisitions');
    $inertia('/inventory/requisites/create', 'System/Inventory/PurchaseRequisitions/PurchaseRequisitionCreate', 'inventory.requisites.create', 'Create Purchase Requisition');
    $inertia('/inventory/requisites/{id}', 'System/Inventory/PurchaseRequisitions/PurchaseRequisitionDetail', 'inventory.requisites.detail', 'Purchase Requisition Details');
    $inertia('/inventory/stock-returns', 'System/Inventory/StockReturns/StockReturnIndex', 'inventory.stock-returns', 'Stock Returns');
    $inertia('/inventory/stock-returns/create', 'System/Inventory/StockReturns/StockReturnCreate', 'inventory.stock-returns.create', 'Create Stock Return');
    $inertia('/inventory/stock-returns/{id}', 'System/Inventory/StockReturns/StockReturnDetail', 'inventory.stock-returns.detail', 'Stock Return Detail');
    $inertia('/inventory/stock-counts', 'System/Inventory/StockCounts/StockCountIndex', 'inventory.stock-counts.index', 'Stock Counts');
    $inertia('/inventory/stock-counts/create', 'System/Inventory/StockCounts/StockCountCreate', 'inventory.stock-counts.create', 'Create Stock Count');
    $inertia('/inventory/stock-counts/{id}', 'System/Inventory/StockCounts/StockCountDetail', 'inventory.stock-counts.detail', 'Stock Count Detail');
    $inertia('/inventory/stock-counts/{id}/edit', 'System/Inventory/StockCounts/StockCountEdit', 'inventory.stock-counts.edit', 'Edit Stock Count');
    $inertia('/inventory/warehouses', 'System/Inventory/Warehouses/WarehouseIndex', 'inventory.warehouses.index', 'Warehouses');
    $inertia('/inventory/warehouses/create', 'System/Inventory/Warehouses/WarehouseCreate', 'inventory.warehouses.create', 'Create Warehouse');
    $inertia('/inventory/warehouses/{id}', 'System/Inventory/Warehouses/WarehouseDetail', 'inventory.warehouses.detail', 'Warehouse Detail');
    $inertia('/inventory/warehouses/{id}/edit', 'System/Inventory/Warehouses/WarehouseEdit', 'inventory.warehouses.edit', 'Edit Warehouse');
    $inertia('/inventory/locations', 'System/Inventory/Locations/LocationIndex', 'inventory.locations.index', 'Locations');
    $inertia('/inventory/locations/create', 'System/Inventory/Locations/LocationCreate', 'inventory.locations.create', 'Create Location');
    $inertia('/inventory/locations/{id}', 'System/Inventory/Locations/LocationDetail', 'inventory.locations.detail', 'Location Detail');
    $inertia('/inventory/locations/{id}/edit', 'System/Inventory/Locations/LocationEdit', 'inventory.locations.edit', 'Edit Location');
    $inertia('/inventory/reorder-rules', 'System/Inventory/ReorderRules/ReorderRuleIndex', 'inventory.reorder-rules', 'Reorder Rules');
    $inertia('/inventory/reorder-rules/create', 'System/Inventory/ReorderRules/ReorderRuleCreate', 'inventory.reorder-rules.create', 'Create Reorder Rule');
    $inertia('/inventory/reorder-rules/{id}', 'System/Inventory/ReorderRules/ReorderRuleDetail', 'inventory.reorder-rules.detail', 'Reorder Rule Detail');
    $inertia('/inventory/reorder-rules/{id}/edit', 'System/Inventory/ReorderRules/ReorderRuleEdit', 'inventory.reorder-rules.edit', 'Edit Reorder Rule');
    $inertia('/inventory/reorder-suggestions', 'System/Inventory/ReorderSuggestions/ReorderSuggestionIndex', 'inventory.reorder-suggestions', 'Reorder Suggestions');
    $inertia('/inventory/reorder-suggestions/{id}', 'System/Inventory/ReorderSuggestions/ReorderSuggestionDetail', 'inventory.reorder-suggestions.detail', 'Reorder Suggestion Detail');
    $inertia('/inventory/serial-numbers', 'System/Inventory/SerialNumbers/SerialNumberIndex', 'inventory.serial-numbers.index', 'Serial Numbers');
    $inertia('/inventory/serial-numbers/create', 'System/Inventory/SerialNumbers/SerialNumberCreate', 'inventory.serial-numbers.create', 'Create Serial Number');
    $inertia('/inventory/serial-numbers/{id}', 'System/Inventory/SerialNumbers/SerialNumberDetail', 'inventory.serial-numbers.show', 'Serial Number Detail');
    $inertia('/inventory/serial-numbers/{id}/edit', 'System/Inventory/SerialNumbers/SerialNumberEdit', 'inventory.serial-numbers.edit', 'Edit Serial Number');
    $inertia('/inventory/batches', 'System/Inventory/Batches/BatchIndex', 'inventory.batches', 'Batches');
    $inertia('/inventory/batches/create', 'System/Inventory/Batches/BatchCreate', 'inventory.batches.create', 'Create Batch');
    $inertia('/inventory/batches/{id}', 'System/Inventory/Batches/BatchDetail', 'inventory.batches.detail', 'Batch Detail');
    $inertia('/inventory/batches/{id}/edit', 'System/Inventory/Batches/BatchEdit', 'inventory.batches.edit', 'Edit Batch');
    $inertia('/inventory/adjustments', 'System/Inventory/Adjustments/AdjustmentIndex', 'inventory.adjustments', 'Adjustments');
    $inertia('/inventory/adjustments/create', 'System/Inventory/Adjustments/AdjustmentCreate', 'inventory.adjustments.create', 'Create Adjustment');
    $inertia('/inventory/adjustments/{id}', 'System/Inventory/Adjustments/AdjustmentDetail', 'inventory.adjustments.detail', 'Adjustment Detail');
    $inertia('/inventory/adjustments/{id}/edit', 'System/Inventory/Adjustments/AdjustmentDetail', 'inventory.adjustments.edit', 'Edit Adjustment');
    $inertia('/inventory/transfers', 'System/Inventory/Transfers/TransferIndex', 'inventory.transfers', 'Transfers');
    $inertia('/inventory/transfers/create', 'System/Inventory/Transfers/TransferCreate', 'inventory.transfers.create', 'Create Transfer');
    $inertia('/inventory/transfers/{id}', 'System/Inventory/Transfers/TransferDetail', 'inventory.transfers.detail', 'Transfer Detail');
    $inertia('/inventory/alerts', 'System/Inventory/Alerts/AlertsIndex', 'inventory.alerts', 'Alerts');
    $inertia('/inventory/transactions', 'System/Inventory/Transactions/TransactionIndex', 'inventory.transactions', 'Transactions');
    $inertia('/inventory/transactions/{id}', 'System/Inventory/Transactions/TransactionDetail', 'inventory.transactions.detail', 'Transaction Detail');
    $inertia('/inventory/activity-logs', 'System/Inventory/ActivityLogs/InventoryActivityLogIndex', 'inventory.activity-logs', 'Activity Logs');
    $inertia('/inventory/activity-logs/{id}', 'System/Inventory/ActivityLogs/InventoryActivityLogDetail', 'inventory.activity-logs.detail', 'Activity Log Detail');
    $inertia('/inventory/reports', 'System/Inventory/Reports/ReportsIndex', 'inventory.reports', 'Reports');
    $inertia('/inventory/notifications', 'System/Inventory/Notifications/NotificationIndex', 'inventory.notifications', 'Notifications');
    $inertia('/inventory/configuration', 'System/Inventory/Configuration/ConfigIndex', 'inventory.configuration', 'Configuration');
    $inertia('/inventory/stock-order-requests', 'System/Inventory/PurchaseRequests/PurchaseRequestIndex', 'stock-order-requests.index', 'Stock Order Requests');
    $inertia('/inventory/stock-order-requests/create', 'System/Inventory/PurchaseRequests/PurchaseRequestCreate', 'stock-order-requests.create', 'Create Stock Order Request');
    $inertia('/inventory/stock-order-requests/{id}', 'System/Inventory/PurchaseRequests/PurchaseRequestDetail', 'stock-order-requests.detail', 'Stock Order Request Detail');
    $inertia('/inventory/stock-order-requests/{id}/edit', 'System/Inventory/PurchaseRequests/PurchaseRequestEdit', 'stock-order-requests.edit', 'Edit Stock Order Request');

    // Procurement
    Route::redirect('/procurement', '/procurement/dashboard')->name('procurement');
    $inertia('/procurement/dashboard', 'System/Procurement/Dashboard', 'procurement.dashboard', 'Procurement Dashboard');
    $inertia('/procurement/suppliers', 'System/Procurement/Suppliers/SupplierIndex', 'procurement.suppliers', 'Suppliers');
    $inertia('/procurement/suppliers/create', 'System/Procurement/Suppliers/SupplierCreate', 'procurement.suppliers.create', 'Create Supplier');
    $inertia('/procurement/suppliers/verified/{portalId}', 'System/Procurement/Suppliers/VerifiedSupplierShow', 'procurement.suppliers.verified.show', 'Verified Supplier');
    $inertia('/procurement/suppliers/{id}', 'System/Procurement/Suppliers/SupplierDetail', 'procurement.suppliers.detail', 'Supplier Details');
    $inertia('/procurement/suppliers/{id}/edit', 'System/Procurement/Suppliers/SupplierEdit', 'procurement.suppliers.edit', 'Edit Supplier');
    $inertia('/procurement/supplier-contracts', 'System/Procurement/SupplierContracts/SupplierContractIndex', 'procurement.supplier-contracts.index', 'Supplier Contracts');
    $inertia('/procurement/supplier-contracts/create', 'System/Procurement/SupplierContracts/SupplierContractCreate', 'procurement.supplier-contracts.create', 'Create Contract');
    $inertia('/procurement/supplier-contracts/{id}', 'System/Procurement/SupplierContracts/SupplierContractDetail', 'procurement.supplier-contracts.detail', 'Contract Details');
    $inertia('/procurement/supplier-contracts/{id}/edit', 'System/Procurement/SupplierContracts/SupplierContractEdit', 'procurement.supplier-contracts.edit', 'Edit Contract');
    $inertia('/procurement/purchase-requisitions', 'System/Procurement/PurchaseRequisitions/PurchaseRequisitionIndex', 'procurement.purchase-requisitions', 'Purchase Requisitions');
    $inertia('/procurement/purchase-requisitions/create', 'System/Procurement/PurchaseRequisitions/PurchaseRequisitionCreate', 'procurement.purchase-requisitions.create', 'Create Requisition');
    $inertia('/procurement/purchase-requisitions/{id}', 'System/Procurement/PurchaseRequisitions/PurchaseRequisitionDetail', 'procurement.purchase-requisitions.detail', 'Requisition Details');
    $inertia('/procurement/rfqs', 'System/Procurement/RFQs/RFQIndex', 'procurement.rfqs', 'RFQs');
    $inertia('/procurement/rfqs/create', 'System/Procurement/RFQs/RFQCreate', 'procurement.rfqs.create', 'Create RFQ');
    $inertia('/procurement/rfqs/{id}', 'System/Procurement/RFQs/RFQDetail', 'procurement.rfqs.detail', 'RFQ Details');
    $inertia('/procurement/purchase-orders', 'System/Procurement/PurchaseOrders/PurchaseOrderIndex', 'procurement.purchase-orders', 'Purchase Orders');
    $inertia('/procurement/purchase-orders/create', 'System/Procurement/PurchaseOrders/PurchaseOrderCreate', 'procurement.purchase-orders.create', 'Create Purchase Order');
    $inertia('/procurement/purchase-orders/{id}', 'System/Procurement/PurchaseOrders/PurchaseOrderDetail', 'procurement.purchase-orders.detail', 'Purchase Order Details');
    $inertia('/procurement/purchase-orders/{id}/edit', 'System/Procurement/PurchaseOrders/PurchaseOrderCreate', 'procurement.purchase-orders.edit', 'Edit Purchase Order');
    $inertia('/procurement/invoices', 'System/Procurement/Invoices/InvoiceIndex', 'procurement.invoices', 'Invoices');
    $inertia('/procurement/invoices/create', 'System/Procurement/Invoices/InvoiceCreate', 'procurement.invoices.create', 'Create Invoice');
    $inertia('/procurement/invoices/{id}', 'System/Procurement/Invoices/InvoiceDetail', 'procurement.invoices.detail', 'Invoice Details');
    $inertia('/procurement/invoices/{id}/edit', 'System/Procurement/Invoices/InvoiceEdit', 'procurement.invoices.edit', 'Edit Invoice');
    $inertia('/inventory/goods-receipts', 'System/Inventory/GoodsReceipts/GoodsReceiptIndex', 'inventory.goods-receipts', 'Goods Receipts');
    $inertia('/inventory/goods-receipts/create', 'System/Inventory/GoodsReceipts/GoodsReceiptCreate', 'inventory.goods-receipts.create', 'Create Goods Receipt');
    $inertia('/inventory/goods-receipts/{id}', 'System/Inventory/GoodsReceipts/GoodsReceiptDetail', 'inventory.goods-receipts.detail', 'Goods Receipt Detail');

    // Procurement aliases (same UI as Inventory Goods Receipts)
    $inertia('/procurement/goods-receipts', 'System/Inventory/GoodsReceipts/GoodsReceiptIndex', 'procurement.goods-receipts', 'Goods Receipts');
    $inertia('/procurement/goods-receipts/create', 'System/Inventory/GoodsReceipts/GoodsReceiptCreate', 'procurement.goods-receipts.create', 'Create Goods Receipt');
    $inertia('/procurement/goods-receipts/{id}', 'System/Inventory/GoodsReceipts/GoodsReceiptDetail', 'procurement.goods-receipts.detail', 'Goods Receipt Detail');
    $inertia('/procurement/products', 'System/Procurement/ProductsIndex', 'procurement.products', 'Products');
    $inertia('/procurement/analytics/reorder-suggestions', 'System/Procurement/Analytics/ReorderSuggestions', 'procurement.analytics.reorder-suggestions', 'Reorder Suggestions');
    $inertia('/procurement/analytics/spend', 'System/Procurement/Analytics/SpendAnalytics', 'procurement.analytics.spend', 'Spend Analytics');
    $inertia('/procurement/analytics/budget', 'System/Procurement/Analytics/BudgetTracking', 'procurement.analytics.budget', 'Budget Tracking');
    $inertia('/procurement/analytics/suppliers', 'System/Procurement/Analytics/SupplierPerformance', 'procurement.analytics.suppliers', 'Supplier Performance');
    $inertia('/procurement/analytics/lead-time', 'System/Procurement/Analytics/LeadTimeMonitoring', 'procurement.analytics.lead-time', 'Lead Time Monitoring');
    $inertia('/procurement/payments', 'System/Procurement/Payments/PaymentIndex', 'procurement.payments', 'Payments');
    $inertia('/procurement/reports', 'System/Procurement/Reports/ReportIndex', 'procurement.reports', 'Reports');
    $inertia('/procurement/settings/workflow', 'System/Procurement/Settings/WorkflowSettings', 'procurement.settings.workflow', 'Workflow Settings');

    // Finance
    Route::redirect('/finance', '/finance/dashboard')->name('finance');
    $inertia('/finance/dashboard', 'System/Finance/FinanceDashboard', 'finance.dashboard', 'Finance Dashboard');
    $inertia('/finance/payables', 'System/Finance/FinancePayablesIndex', 'finance.payables', 'Payables');
    $inertia('/finance/invoices/{id}', 'System/Procurement/Invoices/InvoiceDetail', 'finance.invoices.detail', 'Invoice Detail');
    $inertia('/finance/purchase-orders', 'System/Finance/FinancePurchaseOrderIndex', 'finance.purchase-orders', 'Purchase Orders');
    $inertia('/finance/purchase-orders/{id}', 'System/Finance/FinancePurchaseOrderDetail', 'finance.purchase-orders.detail', 'Purchase Order Detail');
    $inertia('/finance/price-approvals', 'System/Finance/FinancePriceApprovalIndex', 'finance.price-approvals', 'Price Approvals');
    $inertia('/finance/price-approvals/{id}', 'System/Finance/FinancePriceApprovalDetail', 'finance.price-approvals.detail', 'Price Approval Detail');
    $inertia('/finance/receivables', 'System/Finance/FinanceReceivablesIndex', 'finance.receivables', 'Receivables');
    $inertia('/finance/receivables/{source}/{id}', 'System/Finance/FinanceReceivablesDetail', 'finance.receivables.detail', 'Receivable Detail');
    $inertia('/finance/expenses', 'System/Finance/FinanceExpensesIndex', 'finance.expenses', 'Expenses');
    $inertia('/finance/expenses/{id}', 'System/Finance/FinanceExpenseDetail', 'finance.expenses.detail', 'Expense Detail');
    $inertia('/finance/payroll', 'System/Finance/FinancePayrollIndex', 'finance.payroll', 'Payroll');
    $inertia('/finance/payroll/basic', 'System/Finance/FinancePayrollBasicIndex', 'finance.payroll.basic', 'Basic Payroll');
    $inertia('/finance/payroll/{payPeriodId}', 'System/Finance/FinancePayrollDetail', 'finance.payroll.detail', 'Payroll Period Detail');
    $inertia('/finance/cashflow', 'System/Finance/FinanceCashflowIndex', 'finance.cashflow', 'Cashflow');
    $inertia('/finance/budgets', 'System/Finance/FinanceBudgetsIndex', 'finance.budgets', 'Budgets');
    $inertia('/finance/reports', 'System/Finance/FinanceReportsIndex', 'finance.reports', 'Reports');

    // Logistics
    Route::redirect('/logistics', '/logistics/deliveries')->name('logistics');
    $inertia('/logistics/deliveries', 'System/Logistics/Deliveries/DeliveryIndex', 'logistics.deliveries', 'Deliveries');
    $inertia('/logistics/deliveries/create', 'System/Logistics/Deliveries/DeliveryCreate', 'logistics.deliveries.create', 'Create Delivery');
    $inertia('/logistics/deliveries/{source}/{orderId}', 'System/Logistics/Deliveries/DeliveryDetail', 'logistics.deliveries.detail', 'Delivery Detail');
    $inertia('/logistics/return-pickups', 'System/Logistics/ReturnPickups/ReturnPickupIndex', 'logistics.return-pickups', 'Return Pickups');
    $inertia('/logistics/return-pickups/{id}', 'System/Logistics/ReturnPickups/ReturnPickupDetail', 'logistics.return-pickups.detail', 'Return Pickup Detail');
    $inertia('/logistics/stock-transfers', 'System/Logistics/StockTransfers/StockTransferIndex', 'logistics.stock-transfers', 'Stock Transfers');
    $inertia('/logistics/stock-transfers/{id}', 'System/Logistics/StockTransfers/StockTransferDetail', 'logistics.stock-transfers.detail', 'Stock Transfer Detail');
    $inertia('/logistics/trips', 'System/Logistics/Trips/TripIndex', 'logistics.trips', 'Trips');
    $inertia('/logistics/trips/{id}', 'System/Logistics/Trips/TripDetail', 'logistics.trips.detail', 'Trip Detail');
    $inertia('/logistics/vehicles', 'System/Inventory/Deliveries/DeliveryVehicles', 'logistics.vehicles', 'Fleet');
    $inertia('/logistics/delivery-fees', 'System/Merchandising/DeliveryFeeSettings', 'logistics.delivery-fees', 'Delivery Fee Settings', 'Configure store delivery fees');
    Route::get('/logistics/zones', fn() => redirect('/logistics/delivery-fees'));

    // Sales
    Route::redirect('/sales', '/sales/dashboard')->name('sales');
    $inertia('/sales/dashboard', 'System/Sales/SalesDashboard', 'sales.dashboard', 'Sales Dashboard');
    $inertia('/sales/crm', 'System/Sales/SalesCRM', 'sales.crm', 'CRM Leads');
    $inertia('/sales/chats', 'System/Sales/SalesChats', 'sales.chats', 'Customer Chats');
    $inertia('/sales/orders', 'System/Sales/SalesOrdersUnified', 'sales.orders', 'Orders');
    $inertia('/sales/orders-unified', 'System/Sales/SalesOrdersUnified', 'sales.orders-unified', 'Unified Orders');
    $inertia('/sales/pos/overview', 'System/Sales/SalesPOSOverview', 'sales.pos.overview', 'POS Overview');
    $inertia('/sales/pos', 'System/Sales/SalesPOS', 'sales.pos', 'POS');
    $inertia('/sales/pos/orders/{id}', 'System/Sales/SalesPOSOrderDetail', 'sales.pos.order-detail', 'POS Order Detail');
    $inertia('/sales/deliveries', 'System/Sales/SalesOrderDeliveriesIndex', 'sales.deliveries', 'Sales Deliveries');
    $inertia('/sales/deliveries/{id}', 'System/Sales/SalesOrderDeliveryDetail', 'sales.deliveries.detail', 'Delivery Detail');
    $inertia('/sales/ecommerce-orders', 'System/Inventory/EcommerceOrders/EcommerceOrderIndex', 'sales.ecommerce-orders', 'Ecommerce Orders');
    $inertia('/sales/ecommerce-orders/{id}', 'System/Inventory/EcommerceOrders/EcommerceOrderDetail', 'sales.ecommerce-orders.detail', 'Order Detail');
    $inertia('/sales/reviews', 'System/Sales/SalesReviews', 'sales.reviews', 'Reviews');
    $inertia('/sales/reviews/{id}', 'System/Sales/SalesReviewDetail', 'sales.reviews.detail', 'Review Detail');
    $inertia('/sales/vouchers', 'System/Sales/SalesVouchers', 'sales.vouchers', 'Vouchers');
    $inertia('/sales/vouchers/create', 'System/Sales/SalesVoucherCreate', 'sales.vouchers.create', 'Create Voucher');
    $inertia('/sales/vouchers/{id}', 'System/Sales/SalesVoucherShow', 'sales.vouchers.detail', 'Voucher Details');
    $inertia('/sales/reports', 'System/Sales/SalesReports', 'sales.reports', 'Reports');
    $inertia('/sales/refunds', 'System/Sales/SalesRefunds', 'sales.refunds', 'Refunds');
    $inertia('/sales/refunds/{id}', 'System/Sales/SalesRefundDetail', 'sales.refunds.detail', 'Refund Detail');
    $inertia('/sales/returns', 'System/Sales/SalesReturnsIndex', 'sales.returns', 'Returns');
    $inertia('/sales/returns/{id}', 'System/Sales/SalesReturnDetail', 'sales.returns.detail', 'Return Detail');

    // Merchandising
    Route::redirect('/merchandising', '/merchandising/products')->name('merchandising');
    Route::get('/merchandising/delivery-fees', fn() => redirect('/logistics/delivery-fees'));
    $inertia('/merchandising/dashboard', 'System/Merchandising/Dashboard', 'merchandising.dashboard', 'Product Catalog Dashboard', 'Overview of your product catalog and inventory');
    $inertia('/merchandising/products', 'System/Merchandising/products/ProductsList', 'merchandising.products', 'All Products', 'Manage your furniture product catalog');
    $inertia('/merchandising/products/logs', 'System/Merchandising/products/ProductLogs', 'merchandising.products.logs', 'Product Logs', 'View product module activity logs');
    $inertia('/merchandising/products/new', 'System/Merchandising/products/ProductForm', 'merchandising.products.create', 'Add New Product', 'Create a new furniture product');
    Route::redirect('/merchandising/products/raw/new', '/merchandising/products/new')->name('merchandising.products.raw.create');
    $inertia('/merchandising/products/{id}/edit', 'System/Merchandising/products/ProductForm', 'merchandising.products.edit', 'Edit Product', 'Update product information');
    $inertia('/merchandising/products/{id}', 'System/Merchandising/products/ProductView', 'merchandising.products.view', 'Product Details', 'View detailed product information and 3D model');
    $inertia('/merchandising/variations', 'System/Merchandising/variations/VariationsList', 'merchandising.variations', 'Product Variations', 'Manage colors, sizes, and materials');
    $inertia('/merchandising/variations/new', 'System/Merchandising/variations/VariationForm', 'merchandising.variations.create', 'Add New Variation', 'Create a new product variation');
    $inertia('/merchandising/variations/{id}/edit', 'System/Merchandising/variations/VariationForm', 'merchandising.variations.edit', 'Edit Variation', 'Update variation details');
    $inertia('/merchandising/assets', 'System/Merchandising/assets/AssetsList', 'merchandising.assets', '3D Models & Assets', 'Upload and manage 3D models, images, and videos');
    $inertia('/merchandising/assets/upload', 'System/Merchandising/assets/AssetUpload', 'merchandising.assets.upload', 'Upload Asset', 'Upload new 3D model or image');
    $inertia('/merchandising/3d-gallery', 'System/Merchandising/assets/Gallery3D', 'merchandising.3d-gallery', '3D Models Gallery', 'Browse all 3D models');
    $inertia('/merchandising/3d-reconstruction', 'System/Merchandising/assets/Reconstructions', 'merchandising.3d-reconstruction', '3D Reconstruction', 'Generate 3D models from photos');
    $inertia('/merchandising/inventory', 'System/Merchandising/inventory/InventoryList', 'merchandising.inventory', 'Inventory Status', 'Monitor stock levels across all products');
    $inertia('/merchandising/categories', 'System/Merchandising/categories/CategoriesList', 'merchandising.categories', 'Product Categories', 'Organize your furniture catalog');
    $inertia('/merchandising/categories/new', 'System/Merchandising/categories/CategoryForm', 'merchandising.categories.create', 'Add Category', 'Create a new product category');
    $inertia('/merchandising/categories/{id}/edit', 'System/Merchandising/categories/CategoryForm', 'merchandising.categories.edit', 'Edit Category', 'Update category information');
    $inertia('/merchandising/attributes', 'System/Merchandising/attributes/AttributesList', 'merchandising.attributes', 'Product Attributes', 'Define filterable product characteristics');
    $inertia('/merchandising/attributes/new', 'System/Merchandising/attributes/AttributeForm', 'merchandising.attributes.create', 'Add Attribute', 'Create a new product attribute');
    $inertia('/merchandising/tags', 'System/Merchandising/tags/TagsList', 'merchandising.tags', 'Tags & Collections', 'Manage product tags and collections');
    $inertia('/merchandising/pricing', 'System/Merchandising/pricing/PricingRules', 'merchandising.pricing', 'Pricing Rules', 'Set discounts and pricing strategies');
    $inertia('/merchandising/pricing/bulk-update', 'System/Merchandising/pricing/BulkPricing', 'merchandising.pricing.bulk', 'Bulk Price Update', 'Update multiple product prices at once');
    $inertia('/merchandising/reports', 'System/Merchandising/reports/SalesReports', 'merchandising.reports', 'Sales Reports', 'Analyze product performance');
    $inertia('/merchandising/pricing-history', 'System/Merchandising/reports/PricingHistory', 'merchandising.pricing-history', 'Pricing History', 'Track price changes over time');

    // Supplier Portal
    Route::redirect('/supplier-portal', '/supplier-portal/dashboard')->name('supplier-portal');
    $inertia('/supplier-portal/dashboard', 'System/Supplier/SupplierPortalDashboard', 'supplier.dashboard', 'Supplier Dashboard');
    $inertia('/supplier-portal/registration', 'System/Supplier/SupplierPortalRegistration', 'supplier.registration', 'Supplier Registration');
    $inertia('/supplier-portal/rfqs', 'System/Supplier/SupplierRFQIndex', 'supplier.rfqs', 'RFQs');
    $inertia('/supplier-portal/rfqs/{id}', 'System/Supplier/SupplierRFQDetail', 'supplier.rfqs.detail', 'RFQ Details');
    Route::get('/supplier-portal/rfqs/{id}/view', fn($id) => redirect("/supplier-portal/rfqs/{$id}"));
    $inertia('/supplier-portal/pos', 'System/Supplier/SupplierPOIndex', 'supplier.pos', 'Purchase Orders');
    $inertia('/supplier-portal/pos/{id}', 'System/Supplier/SupplierPOApprove', 'supplier.pos.approve', 'Review Purchase Order');
    $inertia('/supplier-portal/pos/{id}/view', 'System/Supplier/SupplierPODetail', 'supplier.pos.view', 'Purchase Order Details');
    $inertia('/supplier-portal/pos/{id}/delivery-template', 'System/Supplier/SupplierPODeliveryTemplate', 'supplier.pos.delivery-template', 'Delivery Form');
    $inertia('/supplier-portal/pos/{id}/invoice', 'System/Supplier/SupplierPOShipmentConfirm', 'supplier.pos.invoice', 'Invoice Confirmation');
    $inertia('/supplier-portal/pos/{id}/invoice-view', 'System/Supplier/SupplierInvoiceDetail', 'supplier.pos.invoice-view', 'Invoice Details');
    $inertia('/supplier-portal/deliveries', 'System/Supplier/SupplierDriverShipmentsIndex', 'supplier.deliveries', 'Delivery Logs');
    $inertia('/supplier-portal/deliveries/{id}', 'System/Supplier/SupplierDriverShipmentDetail', 'supplier.deliveries.detail', 'Delivery Log Detail');
    $inertia('/supplier-portal/transactions', 'System/Supplier/SupplierTransactions', 'supplier.transactions', 'Transactions');
    $inertia('/supplier-portal/stores', 'System/Supplier/SupplierStores', 'supplier.stores', 'Linked Stores');
    $inertia('/supplier-portal/stores/{storeId}', 'System/Supplier/SupplierStoreShow', 'supplier.stores.show', 'Store Details');
    $inertia('/supplier-portal/contracts/create', 'System/Supplier/SupplierContractCreate', 'supplier.contracts.create', 'Create Contract');
    $inertia('/supplier-portal/contracts/{id}', 'System/Supplier/SupplierContractShow', 'supplier.contracts.show', 'Contract Details');
    $inertia('/supplier-portal/profile', 'Profile/SupplierProfile', 'supplier.profile', 'Supplier Profile');
});

// Job Portal (public portal)
$inertia('/job-portal', 'System/HR/JobPortal/JobPortalIndex', 'job-portal.index', 'Job Portal');
$inertia('/job-portal/login', 'System/HR/JobPortal/JobPortalLogin', 'job-portal.login', 'Applicant Login');
$inertia('/job-portal/register', 'System/HR/JobPortal/JobPortalRegister', 'job-portal.register', 'Applicant Register');
$inertia('/job-portal/verify-otp', 'System/HR/JobPortal/JobPortalVerifyOtp', 'job-portal.verify-otp', 'Verify Applicant Email');
$inertia('/job-portal/postings/{id}', 'System/HR/JobPortal/JobPortalDetail', 'job-portal.detail', 'Job Details');
$inertia('/job-portal/postings/{id}/apply', 'System/HR/Applicant/ApplicantApplicationCreate', 'job-portal.apply', 'Apply for Job');
$inertia('/job-portal/applications', 'System/HR/Applicant/ApplicantDashboard', 'job-portal.dashboard', 'My Applications');
$inertia('/job-portal/applications/{id}', 'System/HR/Applicant/ApplicantApplicationDetail', 'job-portal.applications.detail', 'Application Details');
$inertia('/job-portal/profile', 'System/HR/Applicant/ApplicantProfile', 'job-portal.profile', 'Applicant Profile');


// Ecommerce storefront
$inertia('/shop', 'System/Ecommerce/EcommerceProducts', 'ecommerce.products', 'Shop Products');
$inertia('/shop/trending', 'System/Ecommerce/EcommerceTrending', 'ecommerce.trending.view', 'Trending Products');
$inertia('/shop/stores', 'System/Ecommerce/EcommerceStoreDirectory', 'ecommerce.stores', 'Stores');
$inertia('/shop/stores/{storeId}', 'System/Ecommerce/EcommerceStoreProfile', 'ecommerce.store-profile', 'Store Profile');
$inertia('/shop/stores/{storeId}/products', 'System/Ecommerce/EcommerceStoreProducts', 'ecommerce.store-products', 'Store Products');
$inertia('/shop/stores/{storeId}/vouchers', 'System/Ecommerce/EcommerceStoreVouchers', 'ecommerce.store-vouchers', 'Store Vouchers');
$inertia('/shop/products/{id}', 'System/Ecommerce/EcommerceProductOverview', 'ecommerce.product', 'Product Overview');

Route::middleware('auth:sanctum')->group(function () use ($inertia) {
    $inertia('/shop/cart', 'System/Ecommerce/EcommerceCart', 'ecommerce.cart', 'My Cart');
    $inertia('/shop/checkout', 'System/Ecommerce/EcommerceCheckout', 'ecommerce.checkout', 'Checkout');
    $inertia('/shop/orders', 'System/Ecommerce/EcommerceOrders', 'ecommerce.orders', 'My Orders');
    $inertia('/shop/orders/{id}', 'System/Ecommerce/EcommerceOrderDetail', 'ecommerce.order-detail', 'Order Details');
    $inertia('/shop/orders/{id}/cancel', 'System/Ecommerce/EcommerceOrderCancel', 'ecommerce.order-cancel', 'Cancel Order');
    $inertia('/shop/orders/{id}/items/{itemId}/return', 'System/Ecommerce/EcommerceOrderReturn', 'ecommerce.order-return', 'Return Item');
    $inertia('/shop/orders/{id}/items/{itemId}/review', 'System/Ecommerce/EcommerceOrderReview', 'ecommerce.order-review', 'Review Item');
    $inertia('/shop/chats', 'System/Ecommerce/EcommerceChats', 'ecommerce.chats', 'Chats');
    $inertia('/shop/profile', 'System/Ecommerce/EcommerceProfile', 'ecommerce.profile', 'My Profile');
});
