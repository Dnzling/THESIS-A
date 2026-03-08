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
var lodash_1 = require("lodash");
var button_1 = require("primevue/button");
var badge_1 = require("primevue/badge");
var skeleton_1 = require("primevue/skeleton");
var menu_1 = require("primevue/menu");
var auth_1 = require("../stores/auth");
var inventory_service_1 = require("../services/inventory.service");
var UserDialog_vue_1 = require("../components/dialogs/UserDialog.vue");
var NotificationBell_vue_1 = require("../components/Inventory/NotificationBell.vue");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var authStore = (0, auth_1.useAuthStore)();
var quickActionsMenu = (0, vue_1.ref)(null);
var userDialogRef = (0, vue_1.ref)(null);
var loadingNavigation = (0, vue_1.ref)(false);
// Sidebar state with persistence
var sidebarOpen = (0, vue_1.ref)(localStorage.getItem('sidebarOpen') !== 'false'); // Default to open
var toggleSidebar = function () {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('sidebarOpen', sidebarOpen.value.toString());
    // Dispatch event for any components that need to know about sidebar state
    window.dispatchEvent(new CustomEvent('sidebar-toggle', {
        detail: { open: sidebarOpen.value }
    }));
};
// Keyboard shortcut: Ctrl+B to toggle sidebar
var handleKeyboardShortcut = function (event) {
    if (event.ctrlKey && event.key === 'b') {
        event.preventDefault();
        toggleSidebar();
    }
};
(0, vue_1.onMounted)(function () {
    window.addEventListener('keydown', handleKeyboardShortcut);
});
(0, vue_1.onUnmounted)(function () {
    window.removeEventListener('keydown', handleKeyboardShortcut);
});
var canViewAlerts = (0, vue_1.computed)(function () { return authStore.hasPermission('inventory.alerts.view'); });
var canViewNotifications = (0, vue_1.computed)(function () { return authStore.hasPermission('inventory.notifications.view'); });
var canCreateAdjustments = (0, vue_1.computed)(function () { return authStore.hasPermission('inventory.adjustments.create'); });
var canCreateTransfers = (0, vue_1.computed)(function () { return authStore.hasPermission('inventory.transfers.create'); });
var canGenerateAlerts = (0, vue_1.computed)(function () { return authStore.hasPermission('inventory.alerts.generate'); });
var alertSummary = (0, vue_1.ref)({ total_active: 0 });
var openUserDialog = function (event) {
    if (userDialogRef.value) {
        userDialogRef.value.toggle(event);
    }
};
// Alert count
var alertCount = (0, vue_1.computed)(function () { var _a; return ((_a = alertSummary.value) === null || _a === void 0 ? void 0 : _a.total_active) || 0; });
// User data
var fullName = (0, vue_1.computed)(function () {
    var _a, _b;
    var first = (0, lodash_1.startCase)((0, lodash_1.toLower)(((_a = authStore.user) === null || _a === void 0 ? void 0 : _a.first_name) || ''));
    var last = (0, lodash_1.startCase)((0, lodash_1.toLower)(((_b = authStore.user) === null || _b === void 0 ? void 0 : _b.last_name) || ''));
    return "".concat(first, " ").concat(last).trim() || 'User';
});
var userInitials = (0, vue_1.computed)(function () {
    var _a, _b, _c, _d;
    var first = ((_b = (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.first_name) === null || _b === void 0 ? void 0 : _b[0]) || '';
    var last = ((_d = (_c = authStore.user) === null || _c === void 0 ? void 0 : _c.last_name) === null || _d === void 0 ? void 0 : _d[0]) || '';
    return (first + last).toUpperCase() || 'U';
});
var roleDisplay = (0, vue_1.computed)(function () { var _a; return (0, lodash_1.startCase)(((_a = authStore.user) === null || _a === void 0 ? void 0 : _a.role) || 'User'); });
// Breadcrumbs
var breadcrumbs = (0, vue_1.computed)(function () {
    var crumbs = [];
    var title = route.meta.title;
    if (title && title !== 'Inventory Dashboard') {
        crumbs.push({
            name: title,
            path: null
        });
    }
    return crumbs;
});
// Navigation
var allNavigation = (0, vue_1.computed)(function () {
    return authStore.navigation
        .filter(function (item) {
        return item.module === 'inventory' &&
            !item.parent_id &&
            item.is_active;
    })
        .sort(function (a, b) { return a.display_order - b.display_order; });
});
// Quick Actions Menu
var quickActionsItems = (0, vue_1.computed)(function () {
    var items = [];
    if (canCreateAdjustments.value) {
        items.push({
            label: 'Stock Adjustment',
            icon: 'pi pi-sync',
            command: function () { return router.push('/inventory/adjustments/create'); }
        });
    }
    if (canCreateTransfers.value) {
        items.push({
            label: 'Stock Transfer',
            icon: 'pi pi-arrow-right-arrow-left',
            command: function () { return router.push('/inventory/transfers/create'); }
        });
    }
    if (canGenerateAlerts.value) {
        items.push({
            separator: true
        });
        items.push({
            label: 'Generate Alerts',
            icon: 'pi pi-bell',
            command: function () {
                // Implement generate alerts logic
                console.log('Generate alerts clicked');
            }
        });
    }
    return items;
});
var toggleQuickActions = function (event) {
    if (quickActionsMenu.value) {
        quickActionsMenu.value.toggle(event);
    }
};
var navigateToAlerts = function () {
    router.push('/inventory/alerts');
};
// Load navigation and alerts
var loadNavigation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingNavigation.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, authStore.fetchNavigation()];
            case 2:
                _a.sent();
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load navigation:', error_1);
                return [3 /*break*/, 5];
            case 4:
                loadingNavigation.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadAlertSummary = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, total, error_2;
    var _a, _b, _c, _d, _e;
    return __generator(this, function (_f) {
        switch (_f.label) {
            case 0:
                if (!canViewAlerts.value) return [3 /*break*/, 4];
                _f.label = 1;
            case 1:
                _f.trys.push([1, 3, , 4]);
                return [4 /*yield*/, inventory_service_1.default.getAlerts({ per_page: 1, status: 'active' })];
            case 2:
                response = _f.sent();
                total = (_e = (_b = (_a = response === null || response === void 0 ? void 0 : response.data) === null || _a === void 0 ? void 0 : _a.total) !== null && _b !== void 0 ? _b : (_d = (_c = response === null || response === void 0 ? void 0 : response.data) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.length) !== null && _e !== void 0 ? _e : 0;
                alertSummary.value = { total_active: total };
                return [3 /*break*/, 4];
            case 3:
                error_2 = _f.sent();
                console.error('Failed to load alert summary:', error_2);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
(0, vue_1.onMounted)(function () {
    if (authStore.navigation.length === 0) {
        loadNavigation();
    }
    loadAlertSummary();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['closed']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['open']} */ ;
/** @type {__VLS_StyleScopedClasses['menu-button']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex h-screen w-full max-w-[100vw] overflow-hidden bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-[100vw]']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.aside, __VLS_intrinsics.aside)(__assign({ class: "sidebar bg-emerald-50 w-64 flex flex-col z-30 overflow-y-auto" }, { class: ({
        'open': __VLS_ctx.sidebarOpen,
        'closed': !__VLS_ctx.sidebarOpen
    }) }));
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-emerald-50']} */ ;
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['z-30']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['open']} */ ;
/** @type {__VLS_StyleScopedClasses['closed']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: "/F.svg", alt: "Furnisync" }, { class: "w-20 h-20" }));
/** @type {__VLS_StyleScopedClasses['w-20']} */ ;
/** @type {__VLS_StyleScopedClasses['h-20']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "leading-tight" }, { style: {} }));
/** @type {__VLS_StyleScopedClasses['leading-tight']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-lg font-semibold text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "flex-1 overflow-y-auto py-4" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
if (__VLS_ctx.loadingNavigation) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    if (__VLS_ctx.allNavigation.length > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-1 pb-4" }));
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
        for (var _i = 0, _b = __VLS_vFor((__VLS_ctx.allNavigation)); _i < _b.length; _i++) {
            var item = _b[_i][0];
            var __VLS_15 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
            routerLink;
            // @ts-ignore
            var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15(__assign({ key: (item.id), to: (item.route_path) }, { class: "nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" })));
            var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([__assign({ key: (item.id), to: (item.route_path) }, { class: "nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" })], __VLS_functionalComponentArgsRest(__VLS_16), false));
            /** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:bg-emerald-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:text-emerald-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
            var __VLS_20 = __VLS_18.slots.default;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ([item.icon || 'pi pi-circle', 'w-5']) }));
            /** @type {__VLS_StyleScopedClasses['w-5']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "flex-1" }));
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            (item.display_name);
            if (((_a = item.meta) === null || _a === void 0 ? void 0 : _a.badge) === 'count' && __VLS_ctx.alertCount > 0) {
                var __VLS_21 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Badge} */
                badge_1.default;
                // @ts-ignore
                var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({
                    value: (__VLS_ctx.alertCount),
                    severity: "danger",
                    size: "small",
                }));
                var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{
                        value: (__VLS_ctx.alertCount),
                        severity: "danger",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_22), false));
            }
            else if (item.badge_count && item.badge_count > 0) {
                var __VLS_26 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Badge} */
                badge_1.default;
                // @ts-ignore
                var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26({
                    value: (item.badge_count),
                    severity: "danger",
                    size: "small",
                }));
                var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([{
                        value: (item.badge_count),
                        severity: "danger",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_27), false));
            }
            // @ts-ignore
            [sidebarOpen, sidebarOpen, loadingNavigation, allNavigation, allNavigation, alertCount, alertCount,];
            var __VLS_18;
            // @ts-ignore
            [];
        }
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 py-8 text-center" }));
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-300 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-400 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 flex flex-col h-screen overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)(__assign({ class: "bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm" }));
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
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.route.meta.title || 'Inventory Management');
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.route.meta.subtitle || 'Track and manage your stock levels');
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
if (__VLS_ctx.canViewNotifications) {
    var __VLS_31 = NotificationBell_vue_1.default;
    // @ts-ignore
    var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign({ 'onClick': {} }, { badge: (__VLS_ctx.alertCount > 0 ? __VLS_ctx.alertCount.toString() : undefined), badgeSeverity: "danger" })));
    var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { badge: (__VLS_ctx.alertCount > 0 ? __VLS_ctx.alertCount.toString() : undefined), badgeSeverity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_32), false));
    var __VLS_36 = void 0;
    var __VLS_37 = ({ click: {} },
        { onClick: (__VLS_ctx.navigateToAlerts) });
    var __VLS_34;
    var __VLS_35;
}
if (__VLS_ctx.canCreateAdjustments || __VLS_ctx.canCreateTransfers) {
    var __VLS_38 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38(__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "success", text: true, rounded: true })));
    var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "success", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_39), false));
    var __VLS_43 = void 0;
    var __VLS_44 = ({ click: {} },
        { onClick: (__VLS_ctx.toggleQuickActions) });
    var __VLS_41;
    var __VLS_42;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: (__VLS_ctx.openUserDialog) }, { class: "border-l border-gray-200 pl-4 cursor-pointer select-none" }));
/** @type {__VLS_StyleScopedClasses['border-l']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['pl-4']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
/** @type {__VLS_StyleScopedClasses['select-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['px-2']} */ ;
/** @type {__VLS_StyleScopedClasses['py-1']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-emerald-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-semibold text-emerald-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-emerald-600']} */ ;
(__VLS_ctx.userInitials);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-800 text-sm" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
(__VLS_ctx.fullName);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.roleDisplay);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white border-b border-gray-200 px-6 py-2" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "flex items-center space-x-2 text-sm" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45(__assign({ to: "/system/index" }, { class: "text-gray-500 hover:text-emerald-600" })));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([__assign({ to: "/system/index" }, { class: "text-gray-500 hover:text-emerald-600" })], __VLS_functionalComponentArgsRest(__VLS_46), false));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-emerald-600']} */ ;
var __VLS_50 = __VLS_48.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-home" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-home']} */ ;
// @ts-ignore
[alertCount, alertCount, route, route, canViewNotifications, navigateToAlerts, canCreateAdjustments, canCreateTransfers, toggleQuickActions, openUserDialog, userInitials, fullName, roleDisplay,];
var __VLS_48;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-angle-right text-gray-400 text-xs" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-angle-right']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_51;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ to: "/inventory/dashboard" }, { class: "text-gray-500 hover:text-emerald-600" })));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ to: "/inventory/dashboard" }, { class: "text-gray-500 hover:text-emerald-600" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-emerald-600']} */ ;
var __VLS_56 = __VLS_54.slots.default;
// @ts-ignore
[];
var __VLS_54;
if (__VLS_ctx.breadcrumbs.length > 0) {
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.breadcrumbs)); _c < _d.length; _c++) {
        var _e = _d[_c], crumb = _e[0], index = _e[1];
        (index);
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-angle-right text-gray-400 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-angle-right']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        if (crumb.path) {
            var __VLS_57 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
            routerLink;
            // @ts-ignore
            var __VLS_58 = __VLS_asFunctionalComponent1(__VLS_57, new __VLS_57(__assign({ to: (crumb.path) }, { class: "text-gray-500 hover:text-emerald-600" })));
            var __VLS_59 = __VLS_58.apply(void 0, __spreadArray([__assign({ to: (crumb.path) }, { class: "text-gray-500 hover:text-emerald-600" })], __VLS_functionalComponentArgsRest(__VLS_58), false));
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:text-emerald-600']} */ ;
            var __VLS_62 = __VLS_60.slots.default;
            (crumb.name);
            // @ts-ignore
            [breadcrumbs, breadcrumbs,];
            var __VLS_60;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-800 font-medium" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (crumb.name);
        }
        // @ts-ignore
        [];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.main, __VLS_intrinsics.main)(__assign({ class: "flex-1 overflow-y-auto p-6 bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
var __VLS_63;
/** @ts-ignore @type {typeof __VLS_components.routerView | typeof __VLS_components.RouterView} */
routerView;
// @ts-ignore
var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({}));
var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_64), false));
var __VLS_68;
/** @ts-ignore @type {typeof __VLS_components.Menu} */
menu_1.default;
// @ts-ignore
var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
    ref: "quickActionsMenu",
    model: (__VLS_ctx.quickActionsItems),
    popup: (true),
}));
var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
        ref: "quickActionsMenu",
        model: (__VLS_ctx.quickActionsItems),
        popup: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_69), false));
var __VLS_73 = {};
var __VLS_71;
var __VLS_75 = UserDialog_vue_1.default;
// @ts-ignore
var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75({
    ref: "userDialogRef",
}));
var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([{
        ref: "userDialogRef",
    }], __VLS_functionalComponentArgsRest(__VLS_76), false));
var __VLS_80 = {};
var __VLS_78;
// @ts-ignore
var __VLS_74 = __VLS_73, __VLS_81 = __VLS_80;
// @ts-ignore
[quickActionsItems,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
