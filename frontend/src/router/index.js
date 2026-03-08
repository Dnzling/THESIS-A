"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
Object.defineProperty(exports, "__esModule", { value: true });
var vue_router_1 = require("vue-router");
var HumanResourcesLayout_vue_1 = require("../layouts/HumanResourcesLayout.vue");
var auth_1 = require("../stores/auth");
var inventory_1 = require("./modules/inventory");
// Lazy load components for better performance
var Home = function () { return Promise.resolve().then(function () { return require('../views/marketing/Home.vue'); }); };
var Login = function () { return Promise.resolve().then(function () { return require('../views/auth/Login.vue'); }); };
var Dashboard = function () { return Promise.resolve().then(function () { return require('../views/system/storeAdmin/Dashboard.vue'); }); };
var About = function () { return Promise.resolve().then(function () { return require('../views/marketing/About.vue'); }); };
var Pricing = function () { return Promise.resolve().then(function () { return require('../views/marketing/Pricing.vue'); }); };
var Register = function () { return Promise.resolve().then(function () { return require('../views/auth/Register.vue'); }); };
var Unauthorized = function () { return Promise.resolve().then(function () { return require('../views/Unauthorized.vue'); }); };
var VerifyOtp = function () { return Promise.resolve().then(function () { return require('../views/auth/VerifyOtp.vue'); }); };
var Verification = function () { return Promise.resolve().then(function () { return require('../views/auth/VerifyStore.vue'); }); };
// Import the AdminLayout (your sidebar parent component)
var StoreAdminLayout = function () { return Promise.resolve().then(function () { return require('../layouts/StoreAdminLayout.vue'); }); };
var AdminLayout = function () { return Promise.resolve().then(function () { return require('../layouts/AdminLayout.vue'); }); };
// const ManagerLayout = () => import('../layouts/ManagerLayout.vue')
// const ThreeDManagerLayout = () => import('../layouts/3DManagerLayout.vue')
// const SupplierManagerLayout = () => import('../layouts/SupplierManagerLayout.vue')
// const AccountingLayout = () => import('../layouts/AccountingLayout.vue')
// const HRLayout = () => import('../layouts/HumanResourcesLayout.vue')
// const SalesLayout = () => import('../layouts/SalesLayout.vue')
// const InventoryLayout = () => import('../layouts/InventoryLayout.vue')
// const CustomerLayout = () => import('../layouts/CustomerLayout.vue')
// Admin Routes
var AdminDashboard = function () { return Promise.resolve().then(function () { return require('../views/system/admin/Dashboard.vue'); }); };
var AdminSubscription = function () { return Promise.resolve().then(function () { return require('../views/system/admin/Subscriptions.vue'); }); };
var AdminStoreValidation = function () { return Promise.resolve().then(function () { return require('../views/system/admin/Storevalidation.vue'); }); };
var AdminCustomerValidation = function () { return Promise.resolve().then(function () { return require('../views/system/admin/Customervalidation.vue'); }); };
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
var HrDashboard = function () { return Promise.resolve().then(function () { return require('../views/system/hr/index.vue'); }); };
// Stores
var StoreRegister = function () { return Promise.resolve().then(function () { return require('../views/system/store/Registration.vue'); }); };
// Routes
var routes = __spreadArray(__spreadArray([
    { path: '/', name: 'Home', component: Home, meta: { requiresGuest: true } },
    { path: '/login', name: 'Login', component: Login, meta: { requiresGuest: true } },
    { path: '/register', name: 'Register', component: Register, meta: { requiresGuest: true } },
    { path: '/about', name: 'About', component: About, meta: { requiresGuest: true } },
    { path: '/pricing', name: 'Pricing', component: Pricing, meta: { requiresGuest: true } },
    { path: '/verify-otp', name: 'VerifyOtp', component: VerifyOtp, meta: { requiresGuest: true, title: 'Verify Email' } },
    {
        path: '/system',
        component: StoreAdminLayout,
        meta: { requiresAuth: true, role: ['store_admin', 'owner', 'super_admin'] },
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
            { path: 'roles-permissions', name: 'store.role-permissions', component: function () { return Promise.resolve().then(function () { return require('../views/system/admin/RolePermissions.vue'); }); }, meta: { title: 'Role Permissions', permissions: ['store.role.permission'] } },
            { path: 'store', children: [{ path: 'verification', name: 'StoreVerification', component: Verification, meta: { title: 'Store Verification' } }] }
        ]
    },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, role: ['super_admin'] },
        children: [
            { path: 'dashboard', name: 'AdminDashboard', component: AdminDashboard, meta: { title: 'Dashboard' } },
            { path: 'roles-permissions', name: 'admin.role-permissions', component: function () { return Promise.resolve().then(function () { return require('../views/system/admin/RolePermissions.vue'); }); }, meta: { title: 'Role Permissions' } },
            { path: 'subscription', name: 'AdminSubscription', component: AdminSubscription },
            { path: 'store-validation', name: 'AdminStoreValidation', component: AdminStoreValidation },
            { path: 'customer-validation', name: 'AdminCustomerValidation', component: AdminCustomerValidation },
        ]
    },
    {
        path: '/hr',
        component: HumanResourcesLayout_vue_1.default,
        meta: { requiresAuth: true, preload: true, cache: true, role: ['hr_manager'] },
        children: [
            { path: 'index', name: 'hr.dashboard', component: HrDashboard, meta: { title: 'HR Dashboard' } },
            {
                path: 'employees', children: [
                    { path: '', name: 'hr.employees', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Employees.vue'); }); }, meta: { title: 'Employees' } },
                    { path: 'view/:id?', name: 'hr.employees.view', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/EmployeeView.vue'); }); }, meta: { title: 'View Employee' } },
                    // { path: 'create', name: 'hr.employees.create', component: () => import('../views/system/hr/EmployeeCreate.vue'), meta: { title: 'Create Employee' } },
                    // { path: 'edit/:id', name: 'hr.employees.edit', component: () => import('../views/system/hr/EmployeeEdit.vue'), meta: { title: 'Edit Employee' } },
                ]
            },
            {
                path: 'shifts', children: [
                    { path: '', name: 'hr.shifts', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Shift.vue'); }); }, meta: { title: 'Shift Management' } },
                    { path: 'employees', name: 'hr.shifts.employees', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/EmployeeShifts.vue'); }); }, meta: { title: 'Employee Shifts' } },
                    { path: 'create', name: 'hr.shifts.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/CreateShift.vue'); }); }, meta: { title: 'Create Shift' } },
                    // { path: 'edit/:id', name: 'hr.shifts.edit', component: () => import('../views/system/hr/EditShift.vue'), meta: { title: 'Edit Shift' } },
                ]
            },
            { path: 'attendance', name: 'hr.attendance', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Attendance.vue'); }); }, meta: { title: 'Attendance' } },
            { path: 'departments', name: 'hr.departments', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Department.vue'); }); }, meta: { title: 'Departments' } },
            { path: 'leave-management', name: 'hr.leave', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/LeaveManagement.vue'); }); }, meta: { title: 'Leave Management' } },
            { path: 'leave-balances', name: 'hr.leave.balances', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/LeaveBalances.vue'); }); }, meta: { title: 'Leave Balances' } },
            { path: 'analytics', name: 'hr.analytics', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Analytics.vue'); }); }, meta: { title: 'Analytics' } },
            { path: 'settings', name: 'hr.settings', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/Settings.vue'); }); }, meta: { title: 'Settings' } },
            {
                path: 'payroll', children: [
                    { path: '', name: 'hr.payroll', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollList.vue'); }); }, meta: { title: 'Payroll' } },
                    { path: 'overview', name: 'hr.payroll.overview', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollOverview.vue'); }); }, meta: { title: 'Payroll Overview' } },
                    { path: 'periods', name: 'hr.payroll.periods', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayPeriods.vue'); }); }, meta: { title: 'Pay Periods' } },
                    { path: 'lists', name: 'hr.payroll.list', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollList.vue'); }); }, meta: { title: 'Edit Payroll' } },
                    { path: 'create', name: 'hr.payroll.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollCreate.vue'); }); }, meta: { title: 'Generate Payroll' } },
                    { path: 'view/:id', name: 'hr.payroll.view', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollView.vue'); }); }, meta: { title: 'View Payroll' } },
                    { path: 'edit/:id', name: 'hr.payroll.edit', component: function () { return Promise.resolve().then(function () { return require('../views/system/hr/PayrollEdit.vue'); }); }, meta: { title: 'Edit Payroll' } },
                ]
            },
            { path: ':pathMatch(.*)*', redirect: { name: 'hr.dashboard' } }
        ]
    },
    {
        path: '/merchandising',
        component: function () { return Promise.resolve().then(function () { return require('../layouts/MerchandisingLayout.vue'); }); },
        meta: { requiresAuth: true, roles: ['super_admin', 'store_admin', 'store_manager', 'warehouse_manager', 'inventory_staff', 'sales_staff', 'supplier_coordinator'] },
        children: [
            { path: 'dashboard', name: 'merchandising.dashboard', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/Dashboard.vue'); }); }, meta: { title: 'Product Catalog Dashboard', subtitle: 'Overview of your product catalog and inventory', permissions: ['merchandising.dashboard.view'] } },
            { path: 'products', name: 'merchandising.products', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/products/ProductsList.vue'); }); }, meta: { title: 'All Products', subtitle: 'Manage your furniture product catalog', permissions: ['merchandising.products.view'] } },
            { path: 'products/new', name: 'merchandising.products.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/products/ProductForm.vue'); }); }, meta: { title: 'Add New Product', subtitle: 'Create a new furniture product', permissions: ['merchandising.products.create'] } },
            { path: 'products/:id/edit', name: 'merchandising.products.edit', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/products/ProductForm.vue'); }); }, meta: { title: 'Edit Product', subtitle: 'Update product information', permissions: ['merchandising.products.update'] } },
            { path: 'products/:id', name: 'merchandising.products.view', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/products/ProductView.vue'); }); }, meta: { title: 'Product Details', subtitle: 'View detailed product information and 3D model', permissions: ['merchandising.products.read'] } },
            { path: 'variations', name: 'merchandising.variations', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/variations/VariationsList.vue'); }); }, meta: { title: 'Product Variations', subtitle: 'Manage colors, sizes, and materials', permissions: ['merchandising.variations.view'] } },
            { path: 'variations/new', name: 'merchandising.variations.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/variations/VariationForm.vue'); }); }, meta: { title: 'Add New Variation', subtitle: 'Create a new product variation', permissions: ['merchandising.variations.create'] } },
            { path: 'variations/:id/edit', name: 'merchandising.variations.edit', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/variations/VariationForm.vue'); }); }, meta: { title: 'Edit Variation', subtitle: 'Update variation details', permissions: ['merchandising.variations.update'] } },
            { path: 'assets', name: 'merchandising.assets', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/assets/AssetsList.vue'); }); }, meta: { title: '3D Models & Assets', subtitle: 'Upload and manage 3D models, images, and videos', permissions: ['merchandising.assets.view'] } },
            { path: 'assets/upload', name: 'merchandising.assets.upload', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/assets/AssetUpload.vue'); }); }, meta: { title: 'Upload Asset', subtitle: 'Upload new 3D model or image', permissions: ['merchandising.assets.create'] } },
            { path: '3d-gallery', name: 'merchandising.3d-gallery', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/assets/Gallery3D.vue'); }); }, meta: { title: '3D Models Gallery', subtitle: 'Browse all 3D models' } },
            { path: 'inventory', name: 'merchandising.inventory', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/inventory/InventoryList.vue'); }); }, meta: { title: 'Inventory Status', subtitle: 'Monitor stock levels across all products', permissions: ['merchandising.inventory.view'] } },
            { path: 'categories', name: 'merchandising.categories', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/categories/CategoriesList.vue'); }); }, meta: { title: 'Product Categories', subtitle: 'Organize your furniture catalog', permissions: ['merchandising.categories.view'] } },
            { path: 'categories/new', name: 'merchandising.categories.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/categories/CategoryForm.vue'); }); }, meta: { title: 'Add Category', subtitle: 'Create a new product category', permissions: ['merchandising.categories.create'] } },
            { path: 'categories/:id/edit', name: 'merchandising.categories.edit', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/categories/CategoryForm.vue'); }); }, meta: { title: 'Edit Category', subtitle: 'Update category information', permissions: ['merchandising.categories.update'] } },
            { path: 'attributes', name: 'merchandising.attributes', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/attributes/AttributesList.vue'); }); }, meta: { title: 'Product Attributes', subtitle: 'Define filterable product characteristics', permissions: ['merchandising.attributes.view'] } },
            { path: 'attributes/new', name: 'merchandising.attributes.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/attributes/AttributeForm.vue'); }); }, meta: { title: 'Add Attribute', subtitle: 'Create a new product attribute', permissions: ['merchandising.attributes.create'] } },
            { path: 'tags', name: 'merchandising.tags', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/tags/TagsList.vue'); }); }, meta: { title: 'Tags & Collections', subtitle: 'Manage product tags and collections', permissions: ['merchandising.tags.view'] } },
            { path: 'pricing', name: 'merchandising.pricing', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/pricing/PricingRules.vue'); }); }, meta: { title: 'Pricing Rules', subtitle: 'Set discounts and pricing strategies', permissions: ['merchandising.pricing.view'] } },
            { path: 'pricing/bulk-update', name: 'merchandising.pricing.bulk', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/pricing/BulkPricing.vue'); }); }, meta: { title: 'Bulk Price Update', subtitle: 'Update multiple product prices at once', permissions: ['merchandising.pricing.update'] } },
            { path: 'reports', name: 'merchandising.reports', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/reports/SalesReports.vue'); }); }, meta: { title: 'Sales Reports', subtitle: 'Analyze product performance', permissions: ['merchandising.reports.view'] } },
            { path: 'pricing-history', name: 'merchandising.pricing-history', component: function () { return Promise.resolve().then(function () { return require('../views/system/merchandising/reports/PricingHistory.vue'); }); }, meta: { title: 'Pricing History', subtitle: 'Track price changes over time', permissions: ['merchandising.reports.view'] } },
            { path: '', redirect: { name: 'merchandising.products' } },
        ]
    },
    {
        path: '/procurement',
        component: function () { return Promise.resolve().then(function () { return require('../layouts/ProcurementLayout.vue'); }); },
        meta: { requiresAuth: true, roles: ['super_admin', 'store_admin', 'store_manager', 'warehouse_manager', 'inventory_staff', 'sales_staff', 'supplier_coordinator'] },
        children: [
            { path: 'dashboard', name: 'procurement.dashboard', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Dashboard.vue'); }); }, meta: { title: 'Procurement Dashboard', subtitle: 'Overview of procurement operations' } },
            { path: 'suppliers', name: 'procurement.suppliers', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Suppliers/Index.vue'); }); }, meta: { title: 'Suppliers', subtitle: 'Manage supplier records' } },
            { path: 'suppliers/create', name: 'procurement.suppliers.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Suppliers/Create.vue'); }); }, meta: { title: 'Create Supplier', subtitle: 'Add a supplier profile' } },
            { path: 'suppliers/:id', name: 'procurement.suppliers.detail', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Suppliers/Detail.vue'); }); }, meta: { title: 'Supplier Details', subtitle: 'View supplier information' } },
            { path: 'suppliers/:id/edit', name: 'procurement.suppliers.edit', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Suppliers/Edit.vue'); }); }, meta: { title: 'Edit Supplier', subtitle: 'Update supplier information' } },
            { path: 'purchase-requisitions', name: 'procurement.purchase-requisitions', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseRequisitions/Index.vue'); }); }, meta: { title: 'Purchase Requisitions', subtitle: 'Manage requisitions' } },
            { path: 'purchase-requisitions/create', name: 'procurement.purchase-requisitions.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseRequisitions/Create.vue'); }); }, meta: { title: 'Create Requisition', subtitle: 'Create a purchase requisition' } },
            { path: 'purchase-requisitions/:id', name: 'procurement.purchase-requisitions.detail', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseRequisitions/Detail.vue'); }); }, meta: { title: 'Requisition Details', subtitle: 'Review requisition details' } },
            { path: 'rfqs', name: 'procurement.rfqs', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/RFQs/Index.vue'); }); }, meta: { title: 'RFQs', subtitle: 'Manage requests for quotations' } },
            { path: 'rfqs/create', name: 'procurement.rfqs.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/RFQs/Create.vue'); }); }, meta: { title: 'Create RFQ', subtitle: 'Create a request for quotation' } },
            { path: 'rfqs/:id', name: 'procurement.rfqs.detail', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/RFQs/Detail.vue'); }); }, meta: { title: 'RFQ Details', subtitle: 'Review RFQ details' } },
            { path: 'purchase-orders', name: 'procurement.purchase-orders', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseOrders/Index.vue'); }); }, meta: { title: 'Purchase Orders', subtitle: 'Manage purchase orders' } },
            { path: 'purchase-orders/create', name: 'procurement.purchase-orders.create', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseOrders/Create.vue'); }); }, meta: { title: 'Create Purchase Order', subtitle: 'Create a purchase order' } },
            { path: 'purchase-orders/:id', name: 'procurement.purchase-orders.detail', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/PurchaseOrders/Detail.vue'); }); }, meta: { title: 'Purchase Order Details', subtitle: 'Review purchase order details' } },
            { path: 'goods-receipts', name: 'procurement.goods-receipts', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/GoodsReceipts/Index.vue'); }); }, meta: { title: 'Goods Receipts', subtitle: 'Track incoming goods' } },
            { path: 'payments', name: 'procurement.payments', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Payments/Index.vue'); }); }, meta: { title: 'Supplier Payments', subtitle: 'Manage supplier payments' } },
            { path: 'reports', name: 'procurement.reports', component: function () { return Promise.resolve().then(function () { return require('../views/system/procurement/Reports/Index.vue'); }); }, meta: { title: 'Procurement Reports', subtitle: 'Analyze procurement performance' } },
            { path: '', redirect: { name: 'procurement.dashboard' } },
        ]
    }
], inventory_1.default, true), [
    {
        path: '/unauthorized',
        name: 'Unauthorized',
        component: Unauthorized,
        meta: { requiresAuth: false }
    },
    { path: '/dashboard', redirect: '/system' }
], false);
var router = (0, vue_router_1.createRouter)({
    history: (0, vue_router_1.createWebHistory)(),
    routes: routes,
});
router.beforeEach(function (to, from, next) { return __awaiter(void 0, void 0, void 0, function () {
    var authStore, isAuthenticated, requiredRoles, userRole, hasRole, hasPermission;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                authStore = (0, auth_1.useAuthStore)();
                isAuthenticated = authStore.isAuthenticated;
                console.log('🔍 Router guard:', {
                    to: to.path,
                    from: from.path,
                    isAuthenticated: isAuthenticated,
                    userRole: authStore.userRole,
                    routeRoles: to.meta.role || to.meta.roles,
                    permissionsLoaded: authStore.permissionsLoaded,
                    isLoadingPermissions: authStore.isLoadingPermissions
                });
                // Check if route requires authentication
                if (to.meta.requiresAuth && !isAuthenticated) {
                    console.log('❌ Not authenticated');
                    return [2 /*return*/, next({ name: 'Login', query: { redirect: to.fullPath } })];
                }
                if (!(isAuthenticated && !authStore.permissionsLoaded && !authStore.isLoadingPermissions)) return [3 /*break*/, 2];
                console.log('📥 Router guard loading permissions...');
                return [4 /*yield*/, authStore.loadPermissions()];
            case 1:
                _a.sent();
                return [3 /*break*/, 5];
            case 2:
                if (!authStore.isLoadingPermissions) return [3 /*break*/, 5];
                console.log('⏸️ Router guard waiting for permissions to load...');
                _a.label = 3;
            case 3:
                if (!authStore.isLoadingPermissions) return [3 /*break*/, 5];
                return [4 /*yield*/, new Promise(function (resolve) { return setTimeout(resolve, 50); })];
            case 4:
                _a.sent();
                return [3 /*break*/, 3];
            case 5:
                // ✅ ADD THIS: Check if user has required role
                if (to.meta.role || to.meta.roles) {
                    requiredRoles = to.meta.role || to.meta.roles;
                    userRole = authStore.userRole;
                    hasRole = Array.isArray(requiredRoles)
                        ? requiredRoles.includes(userRole)
                        : requiredRoles === userRole;
                    if (!hasRole) {
                        console.log('❌ Access denied - insufficient role:', {
                            userRole: userRole,
                            requiredRoles: requiredRoles
                        });
                        return [2 /*return*/, next({ name: 'Unauthorized' })];
                    }
                }
                // Check specific permission
                if (to.meta.permission) {
                    hasPermission = authStore.hasPermission(to.meta.permission);
                    if (!hasPermission) {
                        console.log('❌ Access denied:', to.meta.permission);
                        return [2 /*return*/, next({ name: 'Unauthorized' })];
                    }
                }
                // Redirect authenticated users from guest pages
                if (to.meta.requiresGuest && isAuthenticated) {
                    console.log('ℹ️ Redirecting authenticated user');
                    return [2 /*return*/, next(authStore.defaultRoute)];
                }
                console.log('✅ Navigation allowed');
                next();
                return [2 /*return*/];
        }
    });
}); });
exports.default = router;
