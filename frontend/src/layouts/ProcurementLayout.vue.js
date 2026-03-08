"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
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
var _a;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var axios_1 = require("axios");
var auth_1 = require("../stores/auth");
var usePermissions_vue_1 = require("../composables/usePermissions.vue");
var lodash_1 = require("lodash");
var route = (0, vue_router_1.useRoute)();
var authStore = (0, auth_1.useAuthStore)();
var router = (0, vue_router_1.useRouter)();
var permissions = (0, usePermissions_vue_1.usePermissions)();
var userData = localStorage.getItem('user');
var user = userData ? JSON.parse(userData) : null;
var firstName = (0, lodash_1.startCase)((0, lodash_1.toLower)(user === null || user === void 0 ? void 0 : user.first_name));
var lastName = (0, lodash_1.startCase)((0, lodash_1.toLower)(user === null || user === void 0 ? void 0 : user.last_name));
var roleLabel = (0, lodash_1.startCase)((_a = user === null || user === void 0 ? void 0 : user.role) === null || _a === void 0 ? void 0 : _a.replace(/_/g, ' '));
// Role badge for display
var roleBadge = (0, vue_1.computed)(function () {
    switch (permissions.userRole.value) {
        case usePermissions_vue_1.ROLES.SUPER_ADMIN:
            return 'Super Admin';
        case usePermissions_vue_1.ROLES.STORE_ADMIN:
            return 'Store Admin';
        case usePermissions_vue_1.ROLES.STORE_MANAGER:
            return 'Manager';
        case usePermissions_vue_1.ROLES.WAREHOUSE_MANAGER:
            return 'Warehouse';
        case usePermissions_vue_1.ROLES.INVENTORY_STAFF:
            return 'Inventory';
        case usePermissions_vue_1.ROLES.SALES_STAFF:
            return 'Sales';
        default:
            return 'Staff';
    }
});
var productMenu = [
    {
        to: "/procurement/dashboard",
        label: "Dashboard",
        icon: "pi pi-home text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/suppliers",
        label: "Suppliers",
        icon: "pi pi-users text-gray-500 w-5"
        // No requiredRoles = everyone can see
    },
    {
        to: "/procurement/purchase-requisitions",
        label: "Purchase Requisitions",
        icon: "pi pi-file-edit text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/rfqs",
        label: "RFQs",
        icon: "pi pi-send text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER, usePermissions_vue_1.ROLES.WAREHOUSE_MANAGER, usePermissions_vue_1.ROLES.SALES_STAFF]
    },
    {
        to: "/procurement/purchase-orders",
        label: "Purchase Orders",
        icon: "pi pi-shopping-cart text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER, usePermissions_vue_1.ROLES.SALES_STAFF]
    },
    {
        to: "/procurement/goods-receipts",
        label: "Goods Receipts",
        icon: "pi pi-inbox text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER, usePermissions_vue_1.ROLES.WAREHOUSE_MANAGER, usePermissions_vue_1.ROLES.INVENTORY_STAFF, usePermissions_vue_1.ROLES.SUPPLIER_COORDINATOR]
    }
];
var catalogMenu = [
    {
        to: "/procurement/payments",
        label: "Supplier Payments",
        icon: "pi pi-wallet text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/suppliers/create",
        label: "Add Supplier",
        icon: "pi pi-user-plus text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/purchase-requisitions/create",
        label: "Create Requisition",
        icon: "pi pi-plus-circle text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/rfqs/create",
        label: "Create RFQ",
        icon: "pi pi-plus text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    }
];
var analyticsMenu = [
    {
        to: "/procurement/reports",
        label: "Procurement Reports",
        icon: "pi pi-chart-line text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    },
    {
        to: "/procurement/dashboard",
        label: "Pending Approvals",
        icon: "pi pi-history text-gray-500 w-5",
        requiredRoles: [usePermissions_vue_1.ROLES.SUPER_ADMIN, usePermissions_vue_1.ROLES.STORE_ADMIN, usePermissions_vue_1.ROLES.STORE_MANAGER]
    }
];
// ==========================================
// FILTER MENUS BASED ON USER ROLE
// ==========================================
var filteredProductMenu = (0, vue_1.computed)(function () {
    return productMenu.filter(function (item) {
        if (!item.requiredRoles)
            return true; // No restrictions
        return permissions.hasAnyRole(item.requiredRoles);
    });
});
var filteredCatalogMenu = (0, vue_1.computed)(function () {
    return catalogMenu.filter(function (item) {
        if (!item.requiredRoles)
            return true;
        return permissions.hasAnyRole(item.requiredRoles);
    });
});
var filteredAnalyticsMenu = (0, vue_1.computed)(function () {
    return analyticsMenu.filter(function (item) {
        if (!item.requiredRoles)
            return true;
        return permissions.hasAnyRole(item.requiredRoles);
    });
});
// ==========================================
// LOGOUT HANDLER
// ==========================================
var isLoggingOut = (0, vue_1.ref)(false);
var handleLogout = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        isLoggingOut.value = true;
        try {
            authStore.logout();
            delete axios_1.default.defaults.headers.common['Authorization'];
            router.replace('/login');
        }
        catch (error) {
            console.error('Logout error:', error);
            localStorage.clear();
            sessionStorage.clear();
            delete axios_1.default.defaults.headers.common['Authorization'];
        }
        finally {
            isLoggingOut.value = false;
        }
        return [2 /*return*/];
    });
}); };
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex h-screen w-full max-w-[100vw] overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-[100vw]']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.aside, __VLS_intrinsics.aside)(__assign({ class: "sidebar bg-white w-64 shadow-lg flex flex-col z-30 overflow-y-auto" }));
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['z-30']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-5 py-4 border-b border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['px-5']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center w-10 h-10 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: "../../public/F.svg", alt: "Furnisync" }, { class: "w-20 h-20" }));
/** @type {__VLS_StyleScopedClasses['w-20']} */ ;
/** @type {__VLS_StyleScopedClasses['h-20']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "leading-tight" }, { style: {} }));
/** @type {__VLS_StyleScopedClasses['leading-tight']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-lg font-semibold text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "flex-1 overflow-y-auto py-4" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
if (__VLS_ctx.filteredProductMenu.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-1 pb-4" }));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
    /** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
    /** @type {__VLS_StyleScopedClasses['pl-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    for (var _i = 0, _b = __VLS_vFor((__VLS_ctx.filteredProductMenu)); _i < _b.length; _i++) {
        var item = _b[_i][0];
        var __VLS_0 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
        routerLink;
        // @ts-ignore
        var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })));
        var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
        var __VLS_5 = __VLS_3.slots.default;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (item.icon) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (item.label);
        // @ts-ignore
        [filteredProductMenu, filteredProductMenu,];
        var __VLS_3;
        // @ts-ignore
        [];
    }
}
if (__VLS_ctx.filteredCatalogMenu.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-1 pb-4 border-t border-gray-200 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
    /** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
    /** @type {__VLS_StyleScopedClasses['pl-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.filteredCatalogMenu)); _c < _d.length; _c++) {
        var item = _d[_c][0];
        var __VLS_6 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
        routerLink;
        // @ts-ignore
        var __VLS_7 = __VLS_asFunctionalComponent1(__VLS_6, new __VLS_6(__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })));
        var __VLS_8 = __VLS_7.apply(void 0, __spreadArray([__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })], __VLS_functionalComponentArgsRest(__VLS_7), false));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
        var __VLS_11 = __VLS_9.slots.default;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (item.icon) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (item.label);
        // @ts-ignore
        [filteredCatalogMenu, filteredCatalogMenu,];
        var __VLS_9;
        // @ts-ignore
        [];
    }
}
if (__VLS_ctx.filteredAnalyticsMenu.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-1 pb-4 border-t border-gray-200 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
    /** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
    /** @type {__VLS_StyleScopedClasses['pl-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    for (var _e = 0, _f = __VLS_vFor((__VLS_ctx.filteredAnalyticsMenu)); _e < _f.length; _e++) {
        var item = _f[_e][0];
        var __VLS_12 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
        routerLink;
        // @ts-ignore
        var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })));
        var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ key: (item.to), to: (item.to) }, { class: "text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
        var __VLS_17 = __VLS_15.slots.default;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (item.icon) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (item.label);
        // @ts-ignore
        [filteredAnalyticsMenu, filteredAnalyticsMenu,];
        var __VLS_15;
        // @ts-ignore
        [];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)(__assign({ onClick: (__VLS_ctx.handleLogout) }, { class: "flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-700 hover:bg-blue-50 transition-colors w-full text-left" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
/** @type {__VLS_StyleScopedClasses['py-3']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-sign-out-alt text-blue-600 w-5" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-sign-out-alt']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['w-5']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 flex flex-col h-screen" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)(__assign({ class: "bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['sticky']} */ ;
/** @type {__VLS_StyleScopedClasses['top-0']} */ ;
/** @type {__VLS_StyleScopedClasses['z-20']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.route.meta.title);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-400" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
(__VLS_ctx.route.meta.subtitle);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold" }));
/** @type {__VLS_StyleScopedClasses['px-3']} */ ;
/** @type {__VLS_StyleScopedClasses['py-1']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
(__VLS_ctx.roleBadge);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-l border-gray-200 pl-4" }));
/** @type {__VLS_StyleScopedClasses['border-l']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['pl-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-user text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-user']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.firstName);
(__VLS_ctx.lastName);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.roleLabel);
__VLS_asFunctionalElement1(__VLS_intrinsics.main, __VLS_intrinsics.main)(__assign({ class: "flex-1 overflow-y-auto p-5 bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['p-5']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.routerView | typeof __VLS_components.RouterView} */
routerView;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18({}));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_19), false));
// @ts-ignore
[handleLogout, route, route, roleBadge, firstName, lastName, roleLabel,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
