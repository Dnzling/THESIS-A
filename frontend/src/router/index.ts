import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useJobPortalAuthStore } from '../stores/jobPortalAuth'
import inventoryRoutes from './modules/inventory.router'
import hrPortalRoutes from './modules/hr.router'
import { supplierRoutes } from './modules/supplier.router'

// Lazy load components for better performance
const Home = () => import('../views/marketing/Home.vue')
const Login = () => import('../views/auth/Login.vue')
const CustomerLogin = () => import('../views/auth/CustomerLogin.vue')
const CustomerRegister = () => import('../views/auth/CustomerRegister.vue')
const Dashboard = () => import('../views/system/storeAdmin/Dashboard.vue')
const About = () => import('../views/marketing/About.vue')
const Pricing = () => import('../views/marketing/Pricing.vue')
const Register = () => import('../views/auth/Register.vue')
const Unauthorized = () => import('../views/Unauthorized.vue')
const VerifyOtp = () => import('../views/auth/VerifyOtp.vue')
const Verification = () => import('../views/auth/VerifyStore.vue')

// Import the unified SystemLayout for all authenticated modules
const SystemLayout = () => import('../layouts/SystemLayout.vue')
const StoreAdminLayout = () => import('../layouts/StoreAdminLayout.vue')
const AdminLayout = () => import('../layouts/AdminLayout.vue')
// const ManagerLayout = () => import('../layouts/ManagerLayout.vue')
// const ThreeDManagerLayout = () => import('../layouts/3DManagerLayout.vue')
// const SupplierManagerLayout = () => import('../layouts/SupplierManagerLayout.vue')
// const AccountingLayout = () => import('../layouts/AccountingLayout.vue')
// const HRLayout = () => import('../layouts/HumanResourcesLayout.vue')
// const SalesLayout = () => import('../layouts/SalesLayout.vue')
// const InventoryLayout = () => import('../layouts/InventoryLayout.vue')
// const CustomerLayout = () => import('../layouts/CustomerLayout.vue')

// Admin Routes
const AdminDashboard = () => import('../views/system/admin/Dashboard.vue')
const AdminSubscription = () => import('../views/system/admin/Subscriptions.vue')
const AdminStoreValidation = () => import('../views/system/admin/Storevalidation.vue')
const AdminCustomerValidation = () => import('../views/system/admin/Customervalidation.vue')
const AdminCustomerManagement = () => import('../views/system/admin/CustomerManagement.vue')
const AdminStoresIndex = () => import('../views/system/admin/StoresIndex.vue')
const AdminStoreDetail = () => import('../views/system/admin/StoreDetail.vue')
const AdminUsersIndex = () => import('../views/system/admin/UsersIndex.vue')
const SupplierList = () => import('../views/system/supplier/SupplierList.vue')
const SupplierDetail = () => import('../views/system/supplier/SupplierDetail.vue')
const SupplierDashboard = () => import('../views/system/supplier/SupplierDashboard.vue')
const SupplierPortalDashboard = () => import('../views/system/supplier/SupplierPortalDashboard.vue')
const SupplierPortalRegistration = () => import('../views/system/supplier/SupplierPortalRegistration.vue')
const SupplierRFQIndex = () => import('../views/system/supplier/SupplierRFQIndex.vue')
const SupplierRFQDetail = () => import('../views/system/supplier/SupplierRFQDetail.vue')
const SupplierPOIndex = () => import('../views/system/supplier/SupplierPOIndex.vue')
const SupplierPOApprove = () => import('../views/system/supplier/SupplierPOApprove.vue')
const SupplierPODetail = () => import('../views/system/supplier/SupplierPODetail.vue')
const SupplierPODeliveryTemplate = () => import('../views/system/supplier/SupplierPODeliveryTemplate.vue')
const SupplierPOInvoiceConfirm = () => import('../views/system/supplier/SupplierPOShipmentConfirm.vue')
const SupplierTransactions = () => import('../views/system/supplier/SupplierTransactions.vue')
const SupplierDriverShipmentsIndex = () => import('../views/system/supplier/SupplierDriverShipmentsIndex.vue')
const SupplierDriverShipmentDetail = () => import('../views/system/supplier/SupplierDriverShipmentDetail.vue')
const ProfileIndex = () => import('../views/system/profile/ProfileIndex.vue')

// Store Admin Views
// const Sales = () => import('../views/system/inventory/Sales.vue')
// const Transactions = () => import('../views/system/sales/Transactions.vue')
// const Purchases = () => import('../views/system/inventory/Purchases.vue')
// const Users = () => import('../views/system/storeAdmin/Users.vue')
// const Products = () => import('../views/system/inventory/Products.vue')
// const Orders = () => import('../views/system/inventory/Orders.vue')
// const Inventory = () => import('../views/system/inventory/Inventory.vue')
// const Suppliers = () => import('../views/system/inventory/Suppliers.vue')
// const ProductRegistration = () => import('../views/system/inventory/ProductRegistration.vue')

// HR Views
const HrDashboard = () => import('../views/system/hr/index.vue')

// Stores
const StoreRegister = () => import('../views/system/store/Registration.vue')


// Routes
const routes: RouteRecordRaw[] = [
  { path: '/', name: 'Home', component: Home, meta: { requiresGuest: true } },
  { path: '/login', name: 'Login', component: Login, meta: { requiresGuest: true } },
  { path: '/customer/login', name: 'customer.login', component: CustomerLogin, meta: { requiresGuest: true } },
  { path: '/customer/register', name: 'customer.register', component: CustomerRegister, meta: { requiresGuest: true } },
  { path: '/register', name: 'Register', component: Register, meta: { requiresGuest: true } },
  { path: '/about', name: 'About', component: About, meta: { requiresGuest: true } },
  { path: '/pricing', name: 'Pricing', component: Pricing, meta: { requiresGuest: true } },
  { path: '/verify-otp', name: 'VerifyOtp', component: VerifyOtp, meta: { requiresGuest: true, title: 'Verify Email' } },

  {
    path: '/shop',
    component: () => import('../layouts/EcommerceLayout.vue'),
    meta: { requiresAuth: false },
    children: [
      { path: '', name: 'ecommerce.products', component: () => import('../views/system/ecommerce/EcommerceProducts.vue'), meta: { title: 'Shop Products' } },
      { path: 'stores', name: 'ecommerce.stores', component: () => import('../views/system/ecommerce/EcommerceStoreDirectory.vue'), meta: { title: 'Stores' } },
      { path: 'stores/:storeId', name: 'ecommerce.store-profile', component: () => import('../views/system/ecommerce/EcommerceStoreProfile.vue'), meta: { title: 'Store Profile' } },
      { path: 'stores/:storeId/products', name: 'ecommerce.store-products', component: () => import('../views/system/ecommerce/EcommerceStoreProducts.vue'), meta: { title: 'Store Products' } },
      { path: 'products/:id', name: 'ecommerce.product', component: () => import('../views/system/ecommerce/EcommerceProductOverview.vue'), meta: { title: 'Product Overview' } },
      { path: 'cart', name: 'ecommerce.cart', component: () => import('../views/system/ecommerce/EcommerceCart.vue'), meta: { title: 'My Cart', requiresAuth: true } },
      { path: 'checkout', name: 'ecommerce.checkout', component: () => import('../views/system/ecommerce/EcommerceCheckout.vue'), meta: { title: 'Checkout', requiresAuth: true } },
      { path: 'orders', name: 'ecommerce.orders', component: () => import('../views/system/ecommerce/EcommerceOrders.vue'), meta: { title: 'My Orders', requiresAuth: true } },
      { path: 'orders/:id', name: 'ecommerce.order-detail', component: () => import('../views/system/ecommerce/EcommerceOrderDetail.vue'), meta: { title: 'Order Details', requiresAuth: true } },
      { path: 'orders/:id/cancel', name: 'ecommerce.order-cancel', component: () => import('../views/system/ecommerce/EcommerceOrderCancel.vue'), meta: { title: 'Cancel Order', requiresAuth: true } },
      { path: 'orders/:id/items/:itemId/return', name: 'ecommerce.order-return', component: () => import('../views/system/ecommerce/EcommerceOrderReturn.vue'), meta: { title: 'Return Item', requiresAuth: true } },
      { path: 'orders/:id/items/:itemId/review', name: 'ecommerce.order-review', component: () => import('../views/system/ecommerce/EcommerceOrderReview.vue'), meta: { title: 'Review Item', requiresAuth: true } },
      { path: 'profile', name: 'ecommerce.profile', component: () => import('../views/system/ecommerce/EcommerceProfile.vue'), meta: { title: 'My Profile', requiresAuth: true } },
    ]
  },
 

  {
    path: '/system',
    component: StoreAdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'index', name: 'store.dashboard', component: Dashboard, meta: { title: 'Dashboard' } },
      // { path: 'sales', name: 'Sales', component: Sales, meta: { title: 'Sales & Reports' } },
      // { path: 'transactions', name: 'Transactions', component: Transactions, meta: { title: 'Transactions', subtitle: 'Customer Transactions History' } },
      // { path: 'purchases', name: 'Purchases', component: Purchases, meta: { title: 'Purchases', subtitle: 'Inventory Purchases & Supplier Orders' } },
      // { path: 'products', name: 'Products', component: Products, meta: { title: 'Products', subtitle: 'Furniture Products Catalog' } },
      // { path: 'orders', name: 'Orders', component: Orders, meta: { title: 'Orders', subtitle: 'Customer Orders Management' } },
      // { path: 'inventory', name: 'Inventory', component: Inventory, meta: { title: 'Inventory', subtitle: 'Stock Levels & Warehouse Management' } },
      // { path: 'suppliers', name: 'Suppliers', component: Suppliers, meta: { title: 'Suppliers', subtitle: 'Supplier Information & Contracts' } },
      // { path: 'users', name: 'Users', component: Users, meta: { title: 'Users', subtitle: 'User Management & Permissions' } },
      // { path: 'productRegistration', name: 'ProductRegistration', component: ProductRegistration },
      // { path: 'profile', name: 'ProductRegistration', component: ProductRegistration },
      { path: 'roles-permissions', name: 'store.role-permissions', component: () => import('../views/system/storeAdmin/RolePermissions.vue'), meta: { title: 'Role Permissions', permissions: ['store.role.permission'] } },
      { path: 'store', children: [{ path: 'verification', name: 'StoreVerification', component: Verification, meta: { title: 'Store Verification' } }] }
    ]
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: ['super_admin'] },
    children: [
      { path: 'dashboard', name: 'AdminDashboard', component: AdminDashboard, meta: { title: 'Dashboard' } },
      { path: 'roles-permissions', name: 'admin.role-permissions', component: () => import('../views/system/admin/RolePermissions.vue'), meta: { title: 'Role Permissions' } },
      { path: 'subscription', name: 'AdminSubscription', component: AdminSubscription },
      { path: 'store-validation', name: 'AdminStoreValidation', component: AdminStoreValidation },
      { path: 'customer-validation', name: 'AdminCustomerValidation', component: AdminCustomerValidation },
      { path: 'customer-management', name: 'admin.customer-management', component: AdminCustomerManagement },
      { path: 'stores', name: 'admin.stores', component: AdminStoresIndex, meta: { title: 'Stores' } },
      { path: 'stores/:id', name: 'admin.stores.detail', component: AdminStoreDetail, meta: { title: 'Store Detail' } },
      { path: 'users', name: 'admin.users', component: AdminUsersIndex, meta: { title: 'Users' } },
      {
        path: 'suppliers',
        redirect: '/admin/suppliers/list',
        meta: { title: 'Supplier Management', breadcrumb: 'Suppliers' },
      },
      {
        path: 'suppliers/list',
        component: SupplierList,
        meta: { title: 'Supplier List', breadcrumb: 'Suppliers', requiresAuth: true },
      },
      {
        path: 'suppliers/:id',
        component: SupplierDetail,
        meta: { title: 'Supplier Details', breadcrumb: 'Supplier Details', requiresAuth: true },
      },
      {
        path: 'suppliers/dashboard',
        component: SupplierDashboard,
        meta: { title: 'Supplier Dashboard', breadcrumb: 'Dashboard', requiresAuth: true },
      },
    ]
  },
  {
    path: '/supplier-portal',
    component: SystemLayout,
    meta: { requiresAuth: true, roles: ['supplier', 'supplier_portal'] },
    children: [
      { path: 'dashboard', name: 'supplier.dashboard', component: SupplierPortalDashboard, meta: { title: 'Supplier Dashboard' } },
      { path: 'registration', name: 'supplier.registration', component: SupplierPortalRegistration, meta: { title: 'Supplier Registration' } },
      { path: 'rfqs', name: 'supplier.rfqs', component: SupplierRFQIndex, meta: { title: 'RFQs' } },
      { path: 'rfqs/:id', name: 'supplier.rfqs.detail', component: SupplierRFQDetail, meta: { title: 'RFQ Details' } },
      { path: 'pos', name: 'supplier.pos', component: SupplierPOIndex, meta: { title: 'Purchase Orders' } },
      { path: 'pos/:id', name: 'supplier.pos.approve', component: SupplierPOApprove, meta: { title: 'Review Purchase Order' } },
      { path: 'pos/:id/view', name: 'supplier.pos.view', component: SupplierPODetail, meta: { title: 'Purchase Order Details' } },
      { path: 'pos/:id/delivery-template', name: 'supplier.pos.delivery-template', component: SupplierPODeliveryTemplate, meta: { title: 'Delivery Form' } },
      { path: 'pos/:id/invoice', name: 'supplier.pos.invoice', component: SupplierPOInvoiceConfirm, meta: { title: 'Invoice Confirmation' } },
      { path: 'pos/:id/invoice-view', name: 'supplier.pos.invoice-view', component: () => import('../views/system/supplier/SupplierInvoiceDetail.vue'), meta: { title: 'Invoice Details' } },
      { path: 'deliveries', name: 'supplier.deliveries', component: SupplierDriverShipmentsIndex, meta: { title: 'Delivery Logs' } },
      { path: 'deliveries/:id', name: 'supplier.deliveries.detail', component: SupplierDriverShipmentDetail, meta: { title: 'Delivery Log Detail' } },
      { path: 'transactions', name: 'supplier.transactions', component: SupplierTransactions, meta: { title: 'Transactions' } },
      { path: '', redirect: { name: 'supplier.dashboard' } },
    ]
  },
  {
    path: '/profile',
    component: SystemLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'profile.index', component: ProfileIndex, meta: { title: 'My Profile' } }
    ]
  },
  {
    path: '/sales',
    component: SystemLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'sales.dashboard', component: () => import('../views/system/sales/SalesDashboard.vue'), meta: { title: 'Sales Dashboard' } },
      { path: 'crm', name: 'sales.crm', component: () => import('../views/system/sales/SalesCRM.vue'), meta: { title: 'CRM Leads' } },
      { path: 'pos', name: 'sales.pos', component: () => import('../views/system/sales/SalesPOS.vue'), meta: { title: 'In-Store POS' } },
      { path: '', redirect: { name: 'sales.dashboard' } },
    ]
  },
  {
    path: '/hr',
    component: SystemLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'index', name: 'hr.dashboard', component: HrDashboard, meta: { title: 'HR Dashboard' } },
      {
        path: 'employees', children: [
          { path: '', name: 'hr.employees', component: () => import('../views/system/hr/Employees.vue'), meta: { title: 'Employees' } },
          { path: 'view/:id?', name: 'hr.employees.view', component: () => import('../views/system/hr/EmployeeView.vue'), meta: { title: 'View Employee' } },
          // { path: 'create', name: 'hr.employees.create', component: () => import('../views/system/hr/EmployeeCreate.vue'), meta: { title: 'Create Employee' } },
          // { path: 'edit/:id', name: 'hr.employees.edit', component: () => import('../views/system/hr/EmployeeEdit.vue'), meta: { title: 'Edit Employee' } },
        ]
      },
      {
        path: 'shifts', children: [
          { path: '', name: 'hr.shifts', component: () => import('../views/system/hr/Shift.vue'), meta: { title: 'Shift Management' } },
          { path: 'employees', name: 'hr.shifts.employees', component: () => import('../views/system/hr/EmployeeShifts.vue'), meta: { title: 'Employee Shifts' } },
          { path: 'create', name: 'hr.shifts.create', component: () => import('../views/system/hr/CreateShift.vue'), meta: { title: 'Create Shift' } },
          // { path: 'edit/:id', name: 'hr.shifts.edit', component: () => import('../views/system/hr/EditShift.vue'), meta: { title: 'Edit Shift' } },
        ]
      },
      { path: 'attendance', name: 'hr.attendance', component: () => import('../views/system/hr/Attendance.vue'), meta: { title: 'Attendance' } },
      { path: 'departments', name: 'hr.departments', component: () => import('../views/system/hr/Department.vue'), meta: { title: 'Departments' } },
      { path: 'leave-management', name: 'hr.leave', component: () => import('../views/system/hr/LeaveManagement.vue'), meta: { title: 'Leave Management' } },
      { path: 'leave-balances', name: 'hr.leave.balances', component: () => import('../views/system/hr/LeaveBalances.vue'), meta: { title: 'Leave Balances' } },
      { path: 'analytics', name: 'hr.analytics', component: () => import('../views/system/hr/Analytics.vue'), meta: { title: 'Analytics' } },
      { path: 'settings', name: 'hr.settings', component: () => import('../views/system/hr/Settings.vue'), meta: { title: 'Settings' } },
      {
        path: 'payroll', children: [
          { path: '', name: 'hr.payroll', component: () => import('../views/system/hr/PayrollList.vue'), meta: { title: 'Payroll' } },
          { path: 'overview', name: 'hr.payroll.overview', component: () => import('../views/system/hr/PayrollOverview.vue'), meta: { title: 'Payroll Overview' } },
          { path: 'periods', name: 'hr.payroll.periods', component: () => import('../views/system/hr/PayPeriods.vue'), meta: { title: 'Pay Periods' } },
          { path: 'lists', name: 'hr.payroll.list', component: () => import('../views/system/hr/PayrollList.vue'), meta: { title: 'Edit Payroll' } },
          { path: 'create', name: 'hr.payroll.create', component: () => import('../views/system/hr/PayrollCreate.vue'), meta: { title: 'Generate Payroll' } },
          { path: 'view/:id', name: 'hr.payroll.view', component: () => import('../views/system/hr/PayrollView.vue'), meta: { title: 'View Payroll' } },
          { path: 'edit/:id', name: 'hr.payroll.edit', component: () => import('../views/system/hr/PayrollEdit.vue'), meta: { title: 'Edit Payroll' } },
        ]
      },
      {
        path: 'job-hiring', children: [
          { path: '', name: 'hr.job-postings', component: () => import('../views/system/hr/JobPostings/JobPostingsIndex.vue'), meta: { title: 'Job Postings', permissions: ['view-job-postings'] } },
          { path: 'postings/:postingId', name: 'hr.job-postings.detail', component: () => import('../views/system/hr/JobPostings/JobPostingDetailView.vue'), meta: { title: 'Job Posting Overview', permissions: ['view-job-postings'] } },
          { path: 'postings/:postingId/applicants', name: 'hr.job-postings.applicants', component: () => import('../views/system/hr/JobPostings/JobPostingApplicantsList.vue'), meta: { title: 'Applicants', permissions: ['view-job-applications'] } },
          { path: 'postings/:postingId/screening', name: 'hr.screening-pipeline', component: () => import('../views/system/hr/JobPostings/JobPostingsScreening.vue'), meta: { title: 'Screening Pipeline', permissions: ['view-job-applications'] } },
          { path: 'postings/:postingId/apply', name: 'hr.apply-job', component: () => import('../views/system/hr/JobPostings/JobPostingsApply.vue'), meta: { title: 'Apply for Job', public: true } },
          { path: 'applications/:applicationId/review', name: 'hr.job-applications.review', component: () => import('../views/system/hr/JobPostings/ApplicantReviewDetail.vue'), meta: { title: 'Applicant Review', permissions: ['view-job-applications'] } },
          { path: 'applications/:applicationId/decision', name: 'hr.job-applications.decision', component: () => import('../views/system/hr/JobPostings/ApplicantDecision.vue'), meta: { title: 'Applicant Decision', permissions: ['view-job-applications'] } },
          { path: 'applications/:applicationId/onboarding', name: 'hr.job-applications.onboarding', component: () => import('../views/system/hr/JobPostings/EmployeeOnboardingCreate.vue'), meta: { title: 'Employee Onboarding', permissions: ['update-application-status'] } },
        ]
      },
      { path: ':pathMatch(.*)*', redirect: { name: 'hr.dashboard' } }
    ]
  },
  {
    path: '/merchandising',
    component: SystemLayout,
    meta: { requiresAuth: true, roles: ['super_admin', 'store_admin', 'store_manager', 'warehouse_manager', 'inventory_staff', 'sales_staff', 'supplier_coordinator'] },
    children: [
      { path: 'dashboard', name: 'merchandising.dashboard', component: () => import('../views/system/merchandising/Dashboard.vue'), meta: { title: 'Product Catalog Dashboard', subtitle: 'Overview of your product catalog and inventory', permissions: ['merchandising.dashboard.view'] } },
      { path: 'products', name: 'merchandising.products', component: () => import('../views/system/merchandising/products/ProductsList.vue'), meta: { title: 'All Products', subtitle: 'Manage your furniture product catalog', permissions: ['merchandising.products.view'] } },
      { path: 'products/logs', name: 'merchandising.products.logs', component: () => import('../views/system/merchandising/products/ProductLogs.vue'), meta: { title: 'Product Logs', subtitle: 'View product module activity logs', permissions: ['merchandising.products.view'] } },
      { path: 'products/new', name: 'merchandising.products.create', component: () => import('../views/system/merchandising/products/ProductForm.vue'), meta: { title: 'Add New Product', subtitle: 'Create a new furniture product', permissions: ['merchandising.products.create'] } },
      { path: 'products/raw/new', name: 'merchandising.products.raw.create', component: () => import('../views/system/merchandising/products/ProductForm.vue'), meta: { title: 'Add Raw Material', subtitle: 'Create a new raw material', permissions: ['merchandising.products.create'] } },
      { path: 'products/:id/edit', name: 'merchandising.products.edit', component: () => import('../views/system/merchandising/products/ProductForm.vue'), meta: { title: 'Edit Product', subtitle: 'Update product information', permissions: ['merchandising.products.update'] } },
      { path: 'products/:id', name: 'merchandising.products.view', component: () => import('../views/system/merchandising/products/ProductView.vue'), meta: { title: 'Product Details', subtitle: 'View detailed product information and 3D model', permissions: ['merchandising.products.read'] } },
      { path: 'variations', name: 'merchandising.variations', component: () => import('../views/system/merchandising/variations/VariationsList.vue'), meta: { title: 'Product Variations', subtitle: 'Manage colors, sizes, and materials', permissions: ['merchandising.variations.view'] } },
      { path: 'variations/new', name: 'merchandising.variations.create', component: () => import('../views/system/merchandising/variations/VariationForm.vue'), meta: { title: 'Add New Variation', subtitle: 'Create a new product variation', permissions: ['merchandising.variations.create'] } },
      { path: 'variations/:id/edit', name: 'merchandising.variations.edit', component: () => import('../views/system/merchandising/variations/VariationForm.vue'), meta: { title: 'Edit Variation', subtitle: 'Update variation details', permissions: ['merchandising.variations.update'] } },
      { path: 'assets', name: 'merchandising.assets', component: () => import('../views/system/merchandising/assets/AssetsList.vue'), meta: { title: '3D Models & Assets', subtitle: 'Upload and manage 3D models, images, and videos', permissions: ['merchandising.assets.view'] } },
      { path: 'assets/upload', name: 'merchandising.assets.upload', component: () => import('../views/system/merchandising/assets/AssetUpload.vue'), meta: { title: 'Upload Asset', subtitle: 'Upload new 3D model or image', permissions: ['merchandising.assets.create'] } },
      { path: '3d-gallery', name: 'merchandising.3d-gallery', component: () => import('../views/system/merchandising/assets/Gallery3D.vue'), meta: { title: '3D Models Gallery', subtitle: 'Browse all 3D models' } },
      { path: 'inventory', name: 'merchandising.inventory', component: () => import('../views/system/merchandising/inventory/InventoryList.vue'), meta: { title: 'Inventory Status', subtitle: 'Monitor stock levels across all products', permissions: ['merchandising.inventory.view'] } },
      { path: 'categories', name: 'merchandising.categories', component: () => import('../views/system/merchandising/categories/CategoriesList.vue'), meta: { title: 'Product Categories', subtitle: 'Organize your furniture catalog', permissions: ['merchandising.categories.view'] } },
      { path: 'categories/new', name: 'merchandising.categories.create', component: () => import('../views/system/merchandising/categories/CategoryForm.vue'), meta: { title: 'Add Category', subtitle: 'Create a new product category', permissions: ['merchandising.categories.create'] } },
      { path: 'categories/:id/edit', name: 'merchandising.categories.edit', component: () => import('../views/system/merchandising/categories/CategoryForm.vue'), meta: { title: 'Edit Category', subtitle: 'Update category information', permissions: ['merchandising.categories.update'] } },
      { path: 'attributes', name: 'merchandising.attributes', component: () => import('../views/system/merchandising/attributes/AttributesList.vue'), meta: { title: 'Product Attributes', subtitle: 'Define filterable product characteristics', permissions: ['merchandising.attributes.view'] } },
      { path: 'attributes/new', name: 'merchandising.attributes.create', component: () => import('../views/system/merchandising/attributes/AttributeForm.vue'), meta: { title: 'Add Attribute', subtitle: 'Create a new product attribute', permissions: ['merchandising.attributes.create'] } },
      { path: 'tags', name: 'merchandising.tags', component: () => import('../views/system/merchandising/tags/TagsList.vue'), meta: { title: 'Tags & Collections', subtitle: 'Manage product tags and collections', permissions: ['merchandising.tags.view'] } },
      { path: 'pricing', name: 'merchandising.pricing', component: () => import('../views/system/merchandising/pricing/PricingRules.vue'), meta: { title: 'Pricing Rules', subtitle: 'Set discounts and pricing strategies', permissions: ['merchandising.pricing.view'] } },
      { path: 'delivery-fees', name: 'merchandising.delivery-fees', component: () => import('../views/system/merchandising/DeliveryFeeSettings.vue'), meta: { title: 'Delivery Fee Settings', subtitle: 'Configure fixed, distance, or hybrid delivery charges', permissions: ['merchandising.pricing.edit'] } },
      { path: 'pricing/bulk-update', name: 'merchandising.pricing.bulk', component: () => import('../views/system/merchandising/pricing/BulkPricing.vue'), meta: { title: 'Bulk Price Update', subtitle: 'Update multiple product prices at once', permissions: ['merchandising.pricing.update'] } },
      { path: 'reports', name: 'merchandising.reports', component: () => import('../views/system/merchandising/reports/SalesReports.vue'), meta: { title: 'Sales Reports', subtitle: 'Analyze product performance', permissions: ['merchandising.reports.view'] } },
      { path: 'pricing-history', name: 'merchandising.pricing-history', component: () => import('../views/system/merchandising/reports/PricingHistory.vue'), meta: { title: 'Pricing History', subtitle: 'Track price changes over time', permissions: ['merchandising.reports.view'] } },
      { path: '', redirect: { name: 'merchandising.products' } },
    ]
  },
  {
    path: '/procurement',
    component: SystemLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'procurement.dashboard', component: () => import('../views/system/procurement/Dashboard.vue'), meta: { title: 'Procurement Dashboard', subtitle: 'Overview of procurement operations' } },
      { path: 'suppliers', name: 'procurement.suppliers', component: () => import('../views/system/procurement/Suppliers/SupplierIndex.vue'), meta: { title: 'Suppliers', subtitle: 'Manage supplier records' } },
      { path: 'suppliers/create', name: 'procurement.suppliers.create', component: () => import('../views/system/procurement/Suppliers/SupplierCreate.vue'), meta: { title: 'Create Supplier', subtitle: 'Add a supplier profile' } },
      { path: 'suppliers/:id', name: 'procurement.suppliers.detail', component: () => import('../views/system/procurement/Suppliers/SupplierDetail.vue'), meta: { title: 'Supplier Details', subtitle: 'View supplier information' } },
      { path: 'suppliers/:id/edit', name: 'procurement.suppliers.edit', component: () => import('../views/system/procurement/Suppliers/SupplierEdit.vue'), meta: { title: 'Edit Supplier', subtitle: 'Update supplier information' } },
      { path: 'supplier-contracts', name: 'procurement.supplier-contracts.index', component: () => import('../views/system/procurement/SupplierContracts/SupplierContractIndex.vue'), meta: { title: 'Supplier Contracts', subtitle: 'Manage supplier contracts with terms and documents' } },
      { path: 'supplier-contracts/create', name: 'procurement.supplier-contracts.create', component: () => import('../views/system/procurement/SupplierContracts/SupplierContractCreate.vue'), meta: { title: 'Create Contract', subtitle: 'Create a new supplier contract' } },
      { path: 'supplier-contracts/:id', name: 'procurement.supplier-contracts.detail', component: () => import('../views/system/procurement/SupplierContracts/SupplierContractDetail.vue'), meta: { title: 'Contract Details', subtitle: 'View contract information' } },
      { path: 'supplier-contracts/:id/edit', name: 'procurement.supplier-contracts.edit', component: () => import('../views/system/procurement/SupplierContracts/SupplierContractEdit.vue'), meta: { title: 'Edit Contract', subtitle: 'Update contract details' } },
      { path: 'purchase-requisitions', name: 'procurement.purchase-requisitions', component: () => import('../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionIndex.vue'), meta: { title: 'Purchase Requisitions', subtitle: 'Manage requisitions' } },
      { path: 'purchase-requisitions/create', name: 'procurement.purchase-requisitions.create', component: () => import('../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionCreate.vue'), meta: { title: 'Create Requisition', subtitle: 'Create a purchase requisition' } },
      { path: 'purchase-requisitions/:id', name: 'procurement.purchase-requisitions.detail', component: () => import('../views/system/procurement/PurchaseRequisitions/PurchaseRequisitionDetail.vue'), meta: { title: 'Requisition Details', subtitle: 'Review requisition details' } },
      { path: 'rfqs', name: 'procurement.rfqs', component: () => import('../views/system/procurement/RFQs/RFQIndex.vue'), meta: { title: 'RFQs', subtitle: 'Manage requests for quotations' } },
      { path: 'rfqs/create', name: 'procurement.rfqs.create', component: () => import('../views/system/procurement/RFQs/RFQCreate.vue'), meta: { title: 'Create RFQ', subtitle: 'Create a request for quotation' } },
      { path: 'rfqs/:id', name: 'procurement.rfqs.detail', component: () => import('../views/system/procurement/RFQs/RFQDetail.vue'), meta: { title: 'RFQ Details', subtitle: 'Review RFQ details' } },
      { path: 'purchase-orders', name: 'procurement.purchase-orders', component: () => import('../views/system/procurement/PurchaseOrders/PurchaseOrderIndex.vue'), meta: { title: 'Purchase Orders', subtitle: 'Manage purchase orders' } },
      { path: 'purchase-orders/create', name: 'procurement.purchase-orders.create', component: () => import('../views/system/procurement/PurchaseOrders/PurchaseOrderCreate.vue'), meta: { title: 'Create Purchase Order', subtitle: 'Create a purchase order from stock requests' } },
      { path: 'purchase-orders/:id', name: 'procurement.purchase-orders.detail', component: () => import('../views/system/procurement/PurchaseOrders/PurchaseOrderDetail.vue'), meta: { title: 'Purchase Order Details', subtitle: 'Review purchase order details' } },
      { path: 'invoices', name: 'procurement.invoices', component: () => import('../views/system/procurement/Invoices/InvoiceIndex.vue'), meta: { title: 'Invoices', subtitle: 'Manage supplier invoices and 3-way matching' } },
      { path: 'invoices/create', name: 'procurement.invoices.create', component: () => import('../views/system/procurement/Invoices/InvoiceCreate.vue'), meta: { title: 'Create Invoice', subtitle: 'Create a new invoice' } },
      { path: 'invoices/:id', name: 'procurement.invoices.detail', component: () => import('../views/system/procurement/Invoices/InvoiceDetail.vue'), meta: { title: 'Invoice Details', subtitle: 'View invoice details and 3-way match status' } },
      { path: 'invoices/:id/edit', name: 'procurement.invoices.edit', component: () => import('../views/system/procurement/Invoices/InvoiceEdit.vue'), meta: { title: 'Edit Invoice', subtitle: 'Edit invoice information' } },
      { path: 'goods-receipts', name: 'procurement.goods-receipts', component: () => import('../views/system/procurement/GoodsReceipts/GoodsReceiptIndex.vue'), meta: { title: 'Goods Receipts', subtitle: 'Track incoming goods' } },
      { path: 'goods-receipts/create', name: 'procurement.goods-receipts.create', component: () => import('../views/system/procurement/GoodsReceipts/GoodsReceiptCreate.vue'), meta: { title: 'Create Goods Receipt', subtitle: 'Record incoming goods' } },
      { path: 'goods-receipts/:id', name: 'procurement.goods-receipts.detail', component: () => import('../views/system/procurement/GoodsReceipts/GoodsReceiptDetail.vue'), meta: { title: 'Goods Receipt Details', subtitle: 'View receipt details' } },
      { path: 'products', name: 'procurement.products', component: () => import('../views/system/procurement/ProductsIndex.vue'), meta: { title: 'Products', subtitle: 'Procurement product catalog with supplier pricing' } },
      // Analytics Routes
      { path: 'analytics/reorder-suggestions', name: 'procurement.analytics.reorder-suggestions', component: () => import('../views/system/procurement/Analytics/ReorderSuggestions.vue'), meta: { title: 'Reorder Suggestions', subtitle: 'Products below reorder point' } },
      { path: 'analytics/spend', name: 'procurement.analytics.spend', component: () => import('../views/system/procurement/Analytics/SpendAnalytics.vue'), meta: { title: 'Spend Analytics', subtitle: 'Analyze spending trends and patterns' } },
      { path: 'analytics/budget', name: 'procurement.analytics.budget', component: () => import('../views/system/procurement/Analytics/BudgetTracking.vue'), meta: { title: 'Budget Tracking', subtitle: 'Monitor spending against budgets' } },
      { path: 'analytics/suppliers', name: 'procurement.analytics.suppliers', component: () => import('../views/system/procurement/Analytics/SupplierPerformance.vue'), meta: { title: 'Supplier Performance', subtitle: 'Track supplier metrics and ratings' } },
      { path: 'analytics/lead-time', name: 'procurement.analytics.lead-time', component: () => import('../views/system/procurement/Analytics/LeadTimeMonitoring.vue'), meta: { title: 'Lead Time Monitoring', subtitle: 'Track delivery performance and trends' } },
      { path: 'payments', name: 'procurement.payments', component: () => import('../views/system/procurement/Payments/PaymentIndex.vue'), meta: { title: 'Supplier Payments', subtitle: 'Manage supplier payments' } },
      { path: 'reports', name: 'procurement.reports', component: () => import('../views/system/procurement/Reports/ReportIndex.vue'), meta: { title: 'Procurement Reports', subtitle: 'Analyze procurement performance' } },
      { path: 'settings/workflow', name: 'procurement.settings.workflow', component: () => import('../views/system/procurement/Settings/WorkflowSettings.vue'), meta: { title: 'Workflow Settings', subtitle: 'Business-size based approval policy' } },
      { path: '', redirect: { name: 'procurement.dashboard' } },
    ]
  },
  {
    path: '/finance',
    component: SystemLayout,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'finance.dashboard', component: () => import('../views/system/finance/FinanceDashboard.vue'), meta: { title: 'Finance Dashboard' } },
      { path: 'payables', name: 'finance.payables', component: () => import('../views/system/finance/FinancePayablesIndex.vue'), meta: { title: 'Accounts Payable' } },
      { path: 'invoices/:id', name: 'finance.invoices.detail', component: () => import('../views/system/procurement/Invoices/InvoiceDetail.vue'), meta: { title: 'Invoice Review' } },
      { path: 'purchase-orders', name: 'finance.purchase-orders', component: () => import('../views/system/finance/FinancePurchaseOrderIndex.vue'), meta: { title: 'PO Finance Approvals' } },
      { path: 'purchase-orders/:id', name: 'finance.purchase-orders.detail', component: () => import('../views/system/finance/FinancePurchaseOrderDetail.vue'), meta: { title: 'PO Finance Review' } },
      { path: 'price-approvals', name: 'finance.price-approvals', component: () => import('../views/system/finance/FinancePriceApprovalIndex.vue'), meta: { title: 'Price Approvals' } },
      { path: 'price-approvals/:id', name: 'finance.price-approvals.detail', component: () => import('../views/system/finance/FinancePriceApprovalDetail.vue'), meta: { title: 'Price Approval Review' } },
      { path: 'receivables', name: 'finance.receivables', component: () => import('../views/system/finance/FinanceReceivablesIndex.vue'), meta: { title: 'Accounts Receivable' } },
      { path: 'expenses', name: 'finance.expenses', component: () => import('../views/system/finance/FinanceExpensesIndex.vue'), meta: { title: 'Expenses' } },
      { path: 'payroll', name: 'finance.payroll', component: () => import('../views/system/finance/FinancePayrollIndex.vue'), meta: { title: 'Payroll' } },
      { path: 'budgets', name: 'finance.budgets', component: () => import('../views/system/finance/FinanceBudgetsIndex.vue'), meta: { title: 'Budgets' } },
      { path: 'reports', name: 'finance.reports', component: () => import('../views/system/finance/FinanceReportsIndex.vue'), meta: { title: 'Reports' } },
      { path: '', redirect: { name: 'finance.dashboard' } },
    ],
  },
  ...hrPortalRoutes,
  ...inventoryRoutes,
  ...supplierRoutes,
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: Unauthorized,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    redirect: () => {
      const authStore = useAuthStore()
      if (authStore.userRole === 'super_admin') {
        return '/admin/dashboard'
      }
      return '/system'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const portalAuthStore = useJobPortalAuthStore()
  const isAuthenticated = authStore.isAuthenticated
  const isPortalAuthenticated = portalAuthStore.isAuthenticated

  console.log('Router guard:', {
    to: to.path,
    from: from.path,
    isAuthenticated,
    userRole: authStore.userRole,
    routeRoles: to.meta.role || to.meta.roles,
    permissionsLoaded: authStore.permissionsLoaded,
    isLoadingPermissions: authStore.isLoadingPermissions
  })

  // Check if route requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    console.log('Not authenticated')
    const loginRoute = to.path.startsWith('/shop') ? 'CustomerLogin' : 'Login'
    return next({ name: loginRoute, query: { redirect: to.fullPath } })
  }

  if (to.meta.portalAuth && !isPortalAuthenticated) {
    portalAuthStore.setPendingRedirect(to.fullPath)
    return next({ name: 'job-portal.login' })
  }

  // Load permissions if authenticated
  if (isAuthenticated && !authStore.permissionsLoaded && !authStore.isLoadingPermissions) {
    console.log('Router guard loading permissions...')
    await authStore.loadPermissions()
  } else if (authStore.isLoadingPermissions) {
    console.log('Router guard waiting for permissions to load...')
    while (authStore.isLoadingPermissions) {
      await new Promise(resolve => setTimeout(resolve, 50))
    }
  }

  // ADD THIS: Check if user has required role
  if (to.meta.role || to.meta.roles) {
    const requiredRoles = to.meta.role || to.meta.roles
    const userRole = authStore.userRole

    const hasRole = Array.isArray(requiredRoles)
      ? requiredRoles.includes(userRole)
      : requiredRoles === userRole

    if (!hasRole) {
      console.log('Access denied - insufficient role:', {
        userRole,
        requiredRoles
      })
      return next({ name: 'Unauthorized' })
    }
  }

  // Check specific permission
  if (to.meta.permission) {
    const hasPermission = authStore.hasPermission(to.meta.permission as string)

    if (!hasPermission) {
      console.log('Access denied:', to.meta.permission)
      return next({ name: 'Unauthorized' })
    }
  }

  // Redirect authenticated users from guest pages
  if (to.meta.requiresGuest && isAuthenticated) {
    console.log('Redirecting authenticated user')
    return next(authStore.defaultRoute)
  }

  if (to.meta.portalGuestOnly && isPortalAuthenticated) {
    return next(portalAuthStore.pendingRedirect || '/job-portal/applications')
  }

  console.log('Navigation allowed')
  next()
})

export default router

