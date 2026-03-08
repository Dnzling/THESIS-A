"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.usePermissions = exports.ROLES = void 0;
var vue_1 = require("vue");
var auth_1 = require("../stores/auth");
exports.ROLES = {
    SUPER_ADMIN: 'super_admin',
    STORE_ADMIN: 'store_admin',
    STORE_MANAGER: 'store_manager',
    HR_MANAGER: 'hr_manager',
    ACCOUNTANT: 'accountant',
    CASHIER: 'cashier',
    WAREHOUSE_MANAGER: 'warehouse_manager',
    INVENTORY_STAFF: 'inventory_staff',
    SUPPLIER_COORDINATOR: 'supplier_coordinator',
    SALES_STAFF: 'sales_staff',
    DELIVERY_STAFF: 'delivery_staff'
};
var usePermissions = function () {
    var authStore = (0, auth_1.useAuthStore)();
    var userRole = (0, vue_1.computed)(function () { return authStore.userRole; });
    // Check if user has any of the specified roles
    var hasAnyRole = function (roles) {
        return roles.includes(userRole.value);
    };
    // Check if user has a specific role
    var hasRole = function (role) {
        return userRole.value === role;
    };
    // Check if user can access merchandising features
    var canAccessMerchandising = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SUPER_ADMIN,
            exports.ROLES.STORE_ADMIN,
            exports.ROLES.STORE_MANAGER,
            exports.ROLES.WAREHOUSE_MANAGER,
            exports.ROLES.INVENTORY_STAFF,
            exports.ROLES.SALES_STAFF,
            exports.ROLES.SUPPLIER_COORDINATOR,
            exports.ROLES.CASHIER
        ]);
    });
    // Check if user can edit products
    var canEditProducts = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SUPER_ADMIN,
            exports.ROLES.STORE_ADMIN,
            exports.ROLES.STORE_MANAGER
        ]);
    });
    // Check if user can manage inventory
    var canManageInventory = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SUPER_ADMIN,
            exports.ROLES.STORE_ADMIN,
            exports.ROLES.STORE_MANAGER,
            exports.ROLES.WAREHOUSE_MANAGER,
            exports.ROLES.INVENTORY_STAFF
        ]);
    });
    // Check if user can view only (read-only access)
    var isViewOnly = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SALES_STAFF,
            exports.ROLES.CASHIER,
            exports.ROLES.SUPPLIER_COORDINATOR
        ]);
    });
    // Check if user can manage pricing
    var canManagePricing = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SUPER_ADMIN,
            exports.ROLES.STORE_ADMIN,
            exports.ROLES.STORE_MANAGER
        ]);
    });
    // Check if user can manage categories/attributes
    var canManageCatalogSettings = (0, vue_1.computed)(function () {
        return hasAnyRole([
            exports.ROLES.SUPER_ADMIN,
            exports.ROLES.STORE_ADMIN,
            exports.ROLES.STORE_MANAGER
        ]);
    });
    return {
        userRole: userRole,
        hasRole: hasRole,
        hasAnyRole: hasAnyRole,
        canAccessMerchandising: canAccessMerchandising,
        canEditProducts: canEditProducts,
        canManageInventory: canManageInventory,
        canManagePricing: canManagePricing,
        canManageCatalogSettings: canManageCatalogSettings,
        isViewOnly: isViewOnly
    };
};
exports.usePermissions = usePermissions;
var __VLS_ctx = {};
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
