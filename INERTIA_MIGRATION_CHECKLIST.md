# Inertia Migration Checklist

Use this to move each Vue Router page into Inertia (`backend/resources/js/Pages`).
Each item corresponds to a route in the current SPA router.

**Marketing & Public**
- [ ] `/` ? `../views/marketing/Home.vue`
- [ ] `/about` ? `../views/marketing/About.vue`
- [ ] `/pricing` ? `../views/marketing/Pricing.vue`

**Auth**
- [ ] `/login` ? `../views/auth/Login.vue`
- [ ] `/customer/login` ? `../views/auth/CustomerLogin.vue`
- [ ] `/customer/register` ? `../views/auth/CustomerRegister.vue`
- [ ] `/register` ? `../views/auth/Register.vue`
- [ ] `/verify-otp` ? `../views/auth/VerifyOtp.vue`
- [ ] `/trial-onboarding` ? `../views/auth/TrialOnboarding.vue`
- [ ] `/system/store/verification` ? `../views/auth/VerifyStore.vue`

**Ecommerce Shop**
- [ ] `/shop` ? `../layouts/EcommerceLayout.vue`
- [ ] `/shop` ? `../views/system/ecommerce/EcommerceProducts.vue`
- [ ] `/shop/stores` ? `../views/system/ecommerce/EcommerceStoreDirectory.vue`
- [ ] `/shop/stores/:storeId` ? `../views/system/ecommerce/EcommerceStoreProfile.vue`
- [ ] `/shop/stores/:storeId/products` ? `../views/system/ecommerce/EcommerceStoreProducts.vue`
- [ ] `/shop/products/:id` ? `../views/system/ecommerce/EcommerceProductOverview.vue`
- [ ] `/shop/cart` ? `../views/system/ecommerce/EcommerceCart.vue`
- [ ] `/shop/checkout` ? `../views/system/ecommerce/EcommerceCheckout.vue`
- [ ] `/shop/orders` ? `../views/system/ecommerce/EcommerceOrders.vue`
- [ ] `/shop/orders/:id` ? `../views/system/ecommerce/EcommerceOrderDetail.vue`
- [ ] `/shop/orders/:id/cancel` ? `../views/system/ecommerce/EcommerceOrderCancel.vue`
- [ ] `/shop/orders/:id/items/:itemId/return` ? `../views/system/ecommerce/EcommerceOrderReturn.vue`
- [ ] `/shop/orders/:id/items/:itemId/review` ? `../views/system/ecommerce/EcommerceOrderReview.vue`
- [ ] `/shop/chats` ? `../views/system/ecommerce/EcommerceChats.vue`
- [ ] `/shop/profile` ? `../views/system/ecommerce/EcommerceProfile.vue`

**System (Core)**
- [ ] `/system` ? `../layouts/SystemLayout.vue`
- [ ] `/system/index` ? `../views/system/storeAdmin/Dashboard.vue`
- [ ] `/system/roles-permissions` ? `../views/system/storeAdmin/RolePermissions.vue`
- [ ] `/system/store` ? `(no component)`

**Admin**
- [ ] `/admin` ? `../layouts/AdminLayout.vue`
- [ ] `/admin/dashboard` ? `../views/system/admin/Dashboard.vue`
- [ ] `/admin/roles-permissions` ? `../views/system/admin/RolePermissions.vue`
- [ ] `/admin/subscription` ? `../views/system/admin/Subscriptions.vue`
- [ ] `/admin/store-validation` ? `../views/system/admin/Storevalidation.vue`
- [ ] `/admin/customer-validation` ? `../views/system/admin/Customervalidation.vue`
- [ ] `/admin/customer-management` ? `../views/system/admin/CustomerManagement.vue`
- [ ] `/admin/stores` ? `../views/system/admin/StoresIndex.vue`
- [ ] `/admin/stores/:id` ? `../views/system/admin/StoreDetail.vue`
- [ ] `/admin/users` ? `../views/system/admin/UsersIndex.vue`
- [ ] `/admin/suppliers` ? `(no component)` (redirect: /admin/suppliers/list)
- [ ] `/admin/suppliers/list` ? `../views/system/supplier/SupplierList.vue`
- [ ] `/admin/suppliers/:id` ? `../views/system/supplier/SupplierDetail.vue`
- [ ] `/admin/suppliers/dashboard` ? `../views/system/supplier/SupplierDashboard.vue`

**Supplier Portal**
- [ ] `/supplier-portal` ? `../layouts/SystemLayout.vue`
- [ ] `/supplier-portal/dashboard` ? `../views/system/supplier/SupplierPortalDashboard.vue`
- [ ] `/supplier-portal/registration` ? `../views/system/supplier/SupplierPortalRegistration.vue`
- [ ] `/supplier-portal/rfqs` ? `../views/system/supplier/SupplierRFQIndex.vue`
- [ ] `/supplier-portal/rfqs/:id` ? `../views/system/supplier/SupplierRFQDetail.vue`
- [ ] `/supplier-portal/pos` ? `../views/system/supplier/SupplierPOIndex.vue`
- [ ] `/supplier-portal/pos/:id` ? `../views/system/supplier/SupplierPOApprove.vue`
- [ ] `/supplier-portal/pos/:id/view` ? `../views/system/supplier/SupplierPODetail.vue`
- [ ] `/supplier-portal/pos/:id/delivery-template` ? `../views/system/supplier/SupplierPODeliveryTemplate.vue`
- [ ] `/supplier-portal/pos/:id/invoice` ? `../views/system/supplier/SupplierPOShipmentConfirm.vue`
- [ ] `/supplier-portal/pos/:id/invoice-view` ? `../views/system/supplier/SupplierInvoiceDetail.vue`
- [ ] `/supplier-portal/deliveries` ? `../views/system/supplier/SupplierDriverShipmentsIndex.vue`
- [ ] `/supplier-portal/deliveries/:id` ? `../views/system/supplier/SupplierDriverShipmentDetail.vue`
- [ ] `/supplier-portal/transactions` ? `../views/system/supplier/SupplierTransactions.vue`
- [ ] `/supplier-portal` ? `(no component)` (redirect: expression)

**Profile**
- [ ] `/profile` ? `../layouts/SystemLayout.vue`
- [ ] `/profile` ? `../views/system/profile/ProfileIndex.vue`

**Sales**
- [ ] `/sales` ? `../layouts/SystemLayout.vue`
- [ ] `/sales/dashboard` ? `../views/system/sales/SalesDashboard.vue`
- [ ] `/sales/crm` ? `../views/system/sales/SalesCRM.vue`
- [ ] `/sales/chats` ? `../views/system/sales/SalesChats.vue`
- [ ] `/sales/pos/overview` ? `../views/system/sales/SalesPOSOverview.vue`
- [ ] `/sales/pos` ? `../views/system/sales/SalesPOS.vue`
- [ ] `/sales/pos/orders/:id` ? `../views/system/sales/SalesPOSOrderDetail.vue`
- [ ] `/sales/deliveries` ? `../views/system/sales/SalesOrderDeliveriesIndex.vue`
- [ ] `/sales/deliveries/:id` ? `../views/system/sales/SalesOrderDeliveryDetail.vue`
- [ ] `/sales/ecommerce-orders` ? `../views/system/inventory/EcommerceOrders/EcommerceOrderIndex.vue`
- [ ] `/sales/ecommerce-orders/:id` ? `../views/system/inventory/EcommerceOrders/EcommerceOrderDetail.vue`
- [ ] `/sales` ? `(no component)` (redirect: expression)

**HR (System)**
- [ ] `/hr` ? `../layouts/SystemLayout.vue`
- [ ] `/hr/index` ? `../views/system/hr/index.vue`
- [ ] `/hr/employees` ? `(no component)`
- [ ] `/hr/employees` ? `../views/system/hr/Employees.vue`
- [ ] `/hr/employees/view/:id?` ? `../views/system/hr/EmployeeView.vue`
- [ ] `/hr/shifts` ? `(no component)`
- [ ] `/hr/shifts` ? `../views/system/hr/Shift.vue`
- [ ] `/hr/shifts/employees` ? `../views/system/hr/EmployeeShifts.vue`
- [ ] `/hr/shifts/create` ? `../views/system/hr/CreateShift.vue`
- [ ] `/hr/attendance` ? `../views/system/hr/Attendance.vue`
- [ ] `/hr/departments` ? `../views/system/hr/Department.vue`
- [ ] `/hr/leave-management` ? `../views/system/hr/LeaveManagement.vue`
- [ ] `/hr/leave-balances` ? `../views/system/hr/LeaveBalances.vue`
- [ ] `/hr/analytics` ? `../views/system/hr/Analytics.vue`
- [ ] `/hr/settings` ? `../views/system/hr/Settings.vue`
- [ ] `/hr/payroll` ? `(no component)`
- [ ] `/hr/payroll` ? `../views/system/hr/PayrollList.vue`
- [ ] `/hr/payroll/overview` ? `../views/system/hr/PayrollOverview.vue`
- [ ] `/hr/payroll/periods` ? `../views/system/hr/PayPeriods.vue`
- [ ] `/hr/payroll/lists` ? `../views/system/hr/PayrollList.vue`
- [ ] `/hr/payroll/create` ? `../views/system/hr/PayrollCreate.vue`
- [ ] `/hr/payroll/view/:id` ? `../views/system/hr/PayrollView.vue`
- [ ] `/hr/payroll/edit/:id` ? `../views/system/hr/PayrollEdit.vue`
- [ ] `/hr/job-hiring` ? `(no component)`
- [ ] `/hr/job-hiring` ? `../views/system/hr/JobPostings/JobPostingsIndex.vue`
- [ ] `/hr/job-hiring/postings/:postingId` ? `../views/system/hr/JobPostings/JobPostingDetailView.vue`
- [ ] `/hr/job-hiring/postings/:postingId/applicants` ? `../views/system/hr/JobPostings/JobPostingApplicantsList.vue`
- [ ] `/hr/job-hiring/postings/:postingId/screening` ? `../views/system/hr/JobPostings/JobPostingsScreening.vue`
- [ ] `/hr/job-hiring/postings/:postingId/apply` ? `../views/system/hr/JobPostings/JobPostingsApply.vue`
- [ ] `/hr/job-hiring/applications/:applicationId/review` ? `../views/system/hr/JobPostings/ApplicantReviewDetail.vue`
- [ ] `/hr/job-hiring/applications/:applicationId/decision` ? `../views/system/hr/JobPostings/ApplicantDecision.vue`
- [ ] `/hr/job-hiring/applications/:applicationId/onboarding` ? `../views/system/hr/JobPostings/EmployeeOnboardingCreate.vue`
- [ ] `/hr/:pathMatch(.*)*` ? `(no component)` (redirect: expression)

**Job Portal**
- [ ] `/job-portal` ? `../../views/system/hr/JobPortal/JobPortalLayout.vue`
- [ ] `/job-portal` ? `../../views/system/hr/JobPortal/JobPortalIndex.vue`
- [ ] `/job-portal/login` ? `../../views/system/hr/JobPortal/JobPortalLogin.vue`
- [ ] `/job-portal/register` ? `../../views/system/hr/JobPortal/JobPortalRegister.vue`
- [ ] `/job-portal/verify-otp` ? `../../views/system/hr/JobPortal/JobPortalVerifyOtp.vue`
- [ ] `/job-portal/postings/:id` ? `../../views/system/hr/JobPortal/JobPortalDetail.vue`
- [ ] `/job-portal/postings/:id/apply` ? `../../views/system/hr/Applicant/ApplicantApplicationCreate.vue`
- [ ] `/job-portal/applications` ? `../../views/system/hr/Applicant/ApplicantDashboard.vue`
- [ ] `/job-portal/applications/:id` ? `../../views/system/hr/Applicant/ApplicantApplicationDetail.vue`

**Merchandising**
- [ ] `/merchandising` ? `../layouts/SystemLayout.vue`
- [ ] `/merchandising/dashboard` ? `../views/system/merchandising/Dashboard.vue`
- [ ] `/merchandising/products` ? `../views/system/merchandising/products/ProductsList.vue`
- [ ] `/merchandising/products/logs` ? `../views/system/merchandising/products/ProductLogs.vue`
- [ ] `/merchandising/products/new` ? `../views/system/merchandising/products/ProductForm.vue`
- [ ] `/merchandising/products/raw/new` ? `../views/system/merchandising/products/RawMaterialForm.vue`
- [ ] `/merchandising/products/:id/edit` ? `../views/system/merchandising/products/ProductForm.vue`
- [ ] `/merchandising/products/:id` ? `../views/system/merchandising/products/ProductView.vue`
- [ ] `/merchandising/variations` ? `../views/system/merchandising/variations/VariationsList.vue`
- [ ] `/merchandising/variations/new` ? `../views/system/merchandising/variations/VariationForm.vue`
- [ ] `/merchandising/variations/:id/edit` ? `../views/system/merchandising/variations/VariationForm.vue`
- [ ] `/merchandising/assets` ? `../views/system/merchandising/assets/AssetsList.vue`
- [ ] `/merchandising/assets/upload` ? `../views/system/merchandising/assets/AssetUpload.vue`
- [ ] `/merchandising/3d-gallery` ? `../views/system/merchandising/assets/Gallery3D.vue`
- [ ] `/merchandising/inventory` ? `../views/system/merchandising/inventory/InventoryList.vue`
- [ ] `/merchandising/categories` ? `../views/system/merchandising/categories/CategoriesList.vue`
- [ ] `/merchandising/categories/new` ? `../views/system/merchandising/categories/CategoryForm.vue`
- [ ] `/merchandising/categories/:id/edit` ? `../views/system/merchandising/categories/CategoryForm.vue`
- [ ] `/merchandising/attributes` ? `../views/system/merchandising/attributes/AttributesList.vue`
- [ ] `/merchandising/attributes/new` ? `../views/system/merchandising/attributes/AttributeForm.vue`
- [ ] `/merchandising/tags` ? `../views/system/merchandising/tags/TagsList.vue`
- [ ] `/merchandising/pricing` ? `../views/system/merchandising/pricing/PricingRules.vue`
- [ ] `/merchandising/delivery-fees` ? `../views/system/merchandising/DeliveryFeeSettings.vue`
- [ ] `/merchandising/pricing/bulk-update` ? `../views/system/merchandising/pricing/BulkPricing.vue`
- [ ] `/merchandising/reports` ? `../views/system/merchandising/reports/SalesReports.vue`
- [ ] `/merchandising/pricing-history` ? `../views/system/merchandising/reports/PricingHistory.vue`
- [ ] `/merchandising` ? `(no component)` (redirect: expression)

**Procurement**
- [ ] `/procurement` ? `../layouts/SystemLayout.vue`
- [ ] `/procurement/dashboard` ? `../views/system/procurement/Dashboard.vue`
- [ ] `/procurement/suppliers` ? `../views/system/procurement/Suppliers/SupplierIndex.vue`
- [ ] `/procurement/suppliers/create` ? `../views/system/procurement/Suppliers/SupplierCreate.vue`
- [ ] `/procurement/suppliers/:id` ? `../views/system/procurement/Suppliers/SupplierDetail.vue`
- [ ] `/procurement/suppliers/:id/edit` ? `../views/system/procurement/Suppliers/SupplierEdit.vue`
- [ ] `/procurement/supplier-contracts` ? `../views/system/procurement/SupplierContracts/SupplierContractIndex.vue`
- [ ] `/procurement/supplier-contracts/create` ? `../views/system/procurement/SupplierContracts/SupplierContractCreate.vue`
- [ ] `/procurement/supplier-contracts/:id` ? `../views/system/procurement/SupplierContracts/SupplierContractDetail.vue`
- [ ] `/procurement/supplier-contracts/:id/edit` ? `../views/system/procurement/SupplierContracts/SupplierContractEdit.vue`
- [ ] `/procurement/purchase-requisitions` ? `../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionIndex.vue`
- [ ] `/procurement/purchase-requisitions/create` ? `../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionCreate.vue`
- [ ] `/procurement/purchase-requisitions/:id` ? `../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionDetail.vue`
- [ ] `/procurement/rfqs` ? `../views/system/procurement/RFQs/RFQIndex.vue`
- [ ] `/procurement/rfqs/create` ? `../views/system/procurement/RFQs/RFQCreate.vue`
- [ ] `/procurement/rfqs/:id` ? `../views/system/procurement/RFQs/RFQDetail.vue`
- [ ] `/procurement/purchase-orders` ? `../views/system/procurement/PurchaseOrders/PurchaseOrderIndex.vue`
- [ ] `/procurement/purchase-orders/create` ? `../views/system/procurement/PurchaseOrders/PurchaseOrderCreate.vue`
- [ ] `/procurement/purchase-orders/:id` ? `../views/system/procurement/PurchaseOrders/PurchaseOrderDetail.vue`
- [ ] `/procurement/invoices` ? `../views/system/procurement/Invoices/InvoiceIndex.vue`
- [ ] `/procurement/invoices/create` ? `../views/system/procurement/Invoices/InvoiceCreate.vue`
- [ ] `/procurement/invoices/:id` ? `../views/system/procurement/Invoices/InvoiceDetail.vue`
- [ ] `/procurement/invoices/:id/edit` ? `../views/system/procurement/Invoices/InvoiceEdit.vue`
- [ ] `/procurement/goods-receipts` ? `../views/system/procurement/GoodsReceipts/GoodsReceiptIndex.vue`
- [ ] `/procurement/goods-receipts/create` ? `../views/system/procurement/GoodsReceipts/GoodsReceiptCreate.vue`
- [ ] `/procurement/goods-receipts/:id` ? `../views/system/procurement/GoodsReceipts/GoodsReceiptDetail.vue`
- [ ] `/procurement/products` ? `../views/system/procurement/ProductsIndex.vue`
- [ ] `/procurement/analytics/reorder-suggestions` ? `../views/system/procurement/Analytics/ReorderSuggestions.vue`
- [ ] `/procurement/analytics/spend` ? `../views/system/procurement/Analytics/SpendAnalytics.vue`
- [ ] `/procurement/analytics/budget` ? `../views/system/procurement/Analytics/BudgetTracking.vue`
- [ ] `/procurement/analytics/suppliers` ? `../views/system/procurement/Analytics/SupplierPerformance.vue`
- [ ] `/procurement/analytics/lead-time` ? `../views/system/procurement/Analytics/LeadTimeMonitoring.vue`
- [ ] `/procurement/payments` ? `../views/system/procurement/Payments/PaymentIndex.vue`
- [ ] `/procurement/reports` ? `../views/system/procurement/Reports/ReportIndex.vue`
- [ ] `/procurement/settings/workflow` ? `../views/system/procurement/Settings/WorkflowSettings.vue`
- [ ] `/procurement` ? `(no component)` (redirect: expression)

**Finance**
- [ ] `/finance` ? `../layouts/SystemLayout.vue`
- [ ] `/finance/dashboard` ? `../views/system/finance/FinanceDashboard.vue`
- [ ] `/finance/payables` ? `../views/system/finance/FinancePayablesIndex.vue`
- [ ] `/finance/invoices/:id` ? `../views/system/procurement/Invoices/InvoiceDetail.vue`
- [ ] `/finance/purchase-orders` ? `../views/system/finance/FinancePurchaseOrderIndex.vue`
- [ ] `/finance/purchase-orders/:id` ? `../views/system/finance/FinancePurchaseOrderDetail.vue`
- [ ] `/finance/price-approvals` ? `../views/system/finance/FinancePriceApprovalIndex.vue`
- [ ] `/finance/price-approvals/:id` ? `../views/system/finance/FinancePriceApprovalDetail.vue`
- [ ] `/finance/receivables` ? `../views/system/finance/FinanceReceivablesIndex.vue`
- [ ] `/finance/receivables/:source/:id` ? `../views/system/finance/FinanceReceivablesDetail.vue`
- [ ] `/finance/expenses` ? `../views/system/finance/FinanceExpensesIndex.vue`
- [ ] `/finance/expenses/:id` ? `../views/system/finance/FinanceExpenseDetail.vue`
- [ ] `/finance/payroll` ? `../views/system/finance/FinancePayrollIndex.vue`
- [ ] `/finance/payroll/basic` ? `../views/system/finance/FinancePayrollBasicIndex.vue`
- [ ] `/finance/payroll/:payPeriodId` ? `../views/system/finance/FinancePayrollDetail.vue`
- [ ] `/finance/cashflow` ? `../views/system/finance/FinanceCashflowIndex.vue`
- [ ] `/finance/budgets` ? `../views/system/finance/FinanceBudgetsIndex.vue`
- [ ] `/finance/reports` ? `../views/system/finance/FinanceReportsIndex.vue`
- [ ] `/finance` ? `(no component)` (redirect: expression)

**Inventory**
- [ ] `/inventory` ? `../../layouts/SystemLayout.vue` (redirect: /inventory/dashboard)
- [ ] `/inventory/dashboard` ? `../../views/system/inventory/InventoryDashboard.vue`
- [ ] `/inventory/ecommerce-orders` ? `(no component)` (redirect: /sales/ecommerce-orders)
- [ ] `/inventory/ecommerce-orders/:id` ? `(no component)` (redirect: function)
- [ ] `/inventory/ecommerce-deliveries` ? `(no component)` (redirect: /logistics/deliveries)
- [ ] `/inventory/ecommerce-deliveries/:id` ? `(no component)` (redirect: function)
- [ ] `/inventory/delivery-vehicles` ? `(no component)` (redirect: /logistics/vehicles)
- [ ] `/inventory/items` ? `../../views/system/inventory/Stocks/StocksIndex.vue`
- [ ] `/inventory/items/create` ? `../../views/system/inventory/Stocks/ItemsCreate.vue`
- [ ] `/inventory/items/:id/edit` ? `../../views/system/inventory/Stocks/ItemsEdit.vue`
- [ ] `/inventory/products` ? `../../views/system/inventory/Products/ProductIndex.vue`
- [ ] `/inventory/products/:id` ? `../../views/system/inventory/Products/ProductDetail.vue`
- [ ] `/inventory/categories` ? `../../views/system/inventory/Categories/CategoryIndex.vue`
- [ ] `/inventory/categories/:id` ? `../../views/system/inventory/Categories/CategoryDetail.vue`
- [ ] `/inventory/units` ? `../../views/system/inventory/Units/UnitIndex.vue`
- [ ] `/inventory/units/create` ? `../../views/system/inventory/Units/UnitCreate.vue`
- [ ] `/inventory/units/:id` ? `../../views/system/inventory/Units/UnitDetail.vue`
- [ ] `/inventory/units/:id/edit` ? `../../views/system/inventory/Units/UnitEdit.vue`
- [ ] `/inventory/stock-issues` ? `../../views/system/inventory/StockIssues/StockIssueIndex.vue`
- [ ] `/inventory/stock-issues/create` ? `../../views/system/inventory/StockIssues/StockIssueCreate.vue`
- [ ] `/inventory/stock-issues/:id` ? `../../views/system/inventory/StockIssues/StockIssueDetail.vue`
- [ ] `/inventory/stock-issues/:id` ? `../../views/system/inventory/StockIssues/StockIssueEdit.vue`
- [ ] `/inventory/requisites` ? `../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionIndex.vue`
- [ ] `/inventory/requisites/create` ? `../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionCreate.vue`
- [ ] `/inventory/requisites/:id` ? `../../views/system/inventory/PurchaseRequisitions/PurchaseRequisitionDetail.vue`
- [ ] `/inventory/stock-returns` ? `../../views/system/inventory/StockReturns/StockReturnIndex.vue`
- [ ] `/inventory/stock-returns/create` ? `../../views/system/inventory/StockReturns/StockReturnCreate.vue`
- [ ] `/inventory/stock-returns/:id` ? `../../views/system/inventory/StockReturns/StockReturnDetail.vue`
- [ ] `/inventory/stock-counts` ? `../../views/system/inventory/StockCounts/StockCountIndex.vue`
- [ ] `/inventory/stock-counts/create` ? `../../views/system/inventory/StockCounts/StockCountCreate.vue`
- [ ] `/inventory/stock-counts/:id` ? `../../views/system/inventory/StockCounts/StockCountDetail.vue`
- [ ] `/inventory/warehouses` ? `../../views/system/inventory/Warehouses/WarehouseIndex.vue`
- [ ] `/inventory/warehouses/create` ? `../../views/system/inventory/Warehouses/WarehouseCreate.vue`
- [ ] `/inventory/warehouses/:id` ? `../../views/system/inventory/Warehouses/WarehouseDetail.vue`
- [ ] `/inventory/warehouses/:id/edit` ? `../../views/system/inventory/Warehouses/WarehouseEdit.vue`
- [ ] `/inventory/locations` ? `../../views/system/inventory/Locations/LocationIndex.vue`
- [ ] `/inventory/locations/create` ? `../../views/system/inventory/Locations/LocationCreate.vue`
- [ ] `/inventory/locations/:id` ? `../../views/system/inventory/Locations/LocationDetail.vue`
- [ ] `/inventory/locations/:id/edit` ? `../../views/system/inventory/Locations/LocationEdit.vue`
- [ ] `/inventory/reorder-rules` ? `../../views/system/inventory/ReorderRules/ReorderRuleIndex.vue`
- [ ] `/inventory/reorder-rules/create` ? `../../views/system/inventory/ReorderRules/ReorderRuleCreate.vue`
- [ ] `/inventory/reorder-rules/:id` ? `../../views/system/inventory/ReorderRules/ReorderRuleDetail.vue`
- [ ] `/inventory/reorder-rules/:id/edit` ? `../../views/system/inventory/ReorderRules/ReorderRuleEdit.vue`
- [ ] `/inventory/reorder-suggestions` ? `../../views/system/inventory/ReorderSuggestions/ReorderSuggestionIndex.vue`
- [ ] `/inventory/reorder-suggestions/:id` ? `../../views/system/inventory/ReorderSuggestions/ReorderSuggestionDetail.vue`
- [ ] `/inventory/serial-numbers` ? `../../views/system/inventory/SerialNumbers/SerialNumberIndex.vue`
- [ ] `/inventory/serial-numbers/create` ? `../../views/system/inventory/SerialNumbers/SerialNumberCreate.vue`
- [ ] `/inventory/serial-numbers/:id` ? `../../views/system/inventory/SerialNumbers/SerialNumberDetail.vue`
- [ ] `/inventory/serial-numbers/:id/edit` ? `../../views/system/inventory/SerialNumbers/SerialNumberEdit.vue`
- [ ] `/inventory/batches` ? `../../views/system/inventory/Batches/BatchIndex.vue`
- [ ] `/inventory/batches/create` ? `../../views/system/inventory/Batches/BatchCreate.vue`
- [ ] `/inventory/batches/:id` ? `../../views/system/inventory/Batches/BatchDetail.vue`
- [ ] `/inventory/batches/:id/edit` ? `../../views/system/inventory/Batches/BatchEdit.vue`
- [ ] `/inventory/adjustments` ? `../../views/system/inventory/Adjustments/AdjustmentIndex.vue`
- [ ] `/inventory/adjustments/create` ? `../../views/system/inventory/Adjustments/AdjustmentCreate.vue`
- [ ] `/inventory/adjustments/:id` ? `../../views/system/inventory/Adjustments/AdjustmentDetail.vue`
- [ ] `/inventory/transfers` ? `../../views/system/inventory/Transfers/TransferIndex.vue`
- [ ] `/inventory/transfers/create` ? `../../views/system/inventory/Transfers/TransferCreate.vue`
- [ ] `/inventory/transfers/:id` ? `../../views/system/inventory/Transfers/TransferDetail.vue`
- [ ] `/inventory/alerts` ? `../../views/system/inventory/Alerts/AlertsIndex.vue`
- [ ] `/inventory/transactions` ? `../../views/system/inventory/Transactions/TransactionIndex.vue`
- [ ] `/inventory/transactions/:id` ? `../../views/system/inventory/Transactions/TransactionDetail.vue`
- [ ] `/inventory/activity-logs` ? `../../views/system/inventory/ActivityLogs/InventoryActivityLogIndex.vue`
- [ ] `/inventory/activity-logs/:id` ? `../../views/system/inventory/ActivityLogs/InventoryActivityLogDetail.vue`
- [ ] `/inventory/reports` ? `../../views/system/inventory/Reports/ReportsIndex.vue`
- [ ] `/inventory/notifications` ? `../../views/system/inventory/Notifications/NotificationIndex.vue`
- [ ] `/inventory/configuration` ? `../../views/system/inventory/Configuration/ConfigIndex.vue`

**Logistics**
- [ ] `/logistics` ? `../../layouts/SystemLayout.vue` (redirect: /logistics/deliveries)
- [ ] `/logistics/deliveries` ? `../../views/system/logistics/Deliveries/DeliveryIndex.vue`
- [ ] `/logistics/deliveries/create` ? `../../views/system/logistics/Deliveries/DeliveryCreate.vue`
- [ ] `/logistics/deliveries/:source/:orderId` ? `../../views/system/logistics/Deliveries/DeliveryDetail.vue`
- [ ] `/logistics/vehicles` ? `../../views/system/inventory/Deliveries/DeliveryVehicles.vue`
- [ ] `/logistics/zones` ? `../../views/system/logistics/Zones/DeliveryZonesIndex.vue`

**Misc**
- [ ] `/unauthorized` ? `../views/Unauthorized.vue`
- [ ] `/dashboard` ? `(no component)` (redirect: function)


