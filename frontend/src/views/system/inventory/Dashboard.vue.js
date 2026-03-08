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
var _a, _b, _c, _d, _e, _f;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var inventory_service_1 = require("../../../services/inventory.service");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(true);
// Dashboard data structure matching your API
var dashboardData = (0, vue_1.ref)({
    inventory: {
        total_items: 0,
        in_stock: 0,
        low_stock: 0,
        out_of_stock: 0,
        total_quantity: 0,
        total_available: 0,
        total_reserved: 0,
        total_damaged: 0,
        total_value: 0
    },
    alerts: {
        total: 0,
        active: 0,
        acknowledged: 0,
        resolved: 0,
        low_stock: 0,
        out_of_stock: 0,
        overstock: 0
    },
    adjustments: {
        total: 0,
        pending_approvals: 0,
        approved: 0,
        applied: 0
    },
    transfers: {
        total: 0,
        pending: 0,
        in_transit: 0,
        completed: 0,
        total_goods_value: 0,
        total_transfer_cost: 0
    },
    recent_transactions: [],
    top_moving_products: [],
    transaction_trends: [],
    value_by_category: [],
    period: {
        start_date: '',
        end_date: '',
        range: ''
    }
});
// ==================== LOAD DASHBOARD DATA ====================
var loadDashboard = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getDashboardStats()];
            case 2:
                response = _c.sent();
                if (response.success && response.data) {
                    // Map the API response to our reactive data
                    dashboardData.value = __assign(__assign({}, dashboardData.value), response.data);
                    console.log('Dashboard loaded:', dashboardData.value);
                }
                else {
                    throw new Error('Invalid response format');
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Failed to load inventory dashboard', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load dashboard data',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// ==================== HELPER FUNCTIONS ====================
var formatTransactionType = function (type) {
    var types = {
        'purchase': 'Purchase',
        'sale': 'Sale',
        'adjustment': 'Adjustment',
        'transfer': 'Transfer',
        'return': 'Return',
        'damage': 'Damage',
        'receipt': 'Receipt'
    };
    return types[type] || type;
};
var getTransactionSeverity = function (type) {
    var severities = {
        'purchase': 'success',
        'sale': 'info',
        'adjustment': 'warning',
        'transfer': 'info',
        'return': 'danger',
        'damage': 'danger',
        'receipt': 'success'
    };
    return severities[type] || 'info';
};
var getQuantityClass = function (quantity) {
    if (quantity > 0)
        return 'text-green-600 font-medium';
    if (quantity < 0)
        return 'text-red-600 font-medium';
    return 'text-gray-600';
};
var formatDate = function (dateString) {
    if (!dateString)
        return 'N/A';
    var date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
// ==================== LIFECYCLE ====================
(0, vue_1.onMounted)(function () {
    loadDashboard();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    for (var _i = 0, _g = __VLS_vFor((4)); _i < _g.length; _i++) {
        var i = _g[_i][0];
        var __VLS_0 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })));
        var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    Skeleton;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ height: "300px" }, { class: "rounded-lg" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ height: "300px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    var __VLS_15 = void 0;
    var __VLS_16 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'inventory.items' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_17 = __VLS_13.slots.default;
    {
        var __VLS_18 = __VLS_13.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.dashboardData.inventory.total_items);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-emerald-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-emerald-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-3xl text-emerald-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-emerald-600']} */ ;
        // @ts-ignore
        [dashboardData,];
    }
    // @ts-ignore
    [];
    var __VLS_13;
    var __VLS_14;
    var __VLS_19 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_20), false));
    var __VLS_24 = void 0;
    var __VLS_25 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'inventory.alerts' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_26 = __VLS_22.slots.default;
    {
        var __VLS_27 = __VLS_22.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.dashboardData.inventory.low_stock);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-red-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.dashboardData.inventory.out_of_stock);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-3xl text-red-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        // @ts-ignore
        [dashboardData, dashboardData,];
    }
    // @ts-ignore
    [];
    var __VLS_22;
    var __VLS_23;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'inventory.adjustments' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_35 = __VLS_31.slots.default;
    {
        var __VLS_36 = __VLS_31.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.dashboardData.adjustments.pending_approvals);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-amber-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-amber-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sync text-3xl text-amber-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-sync']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-amber-600']} */ ;
        // @ts-ignore
        [dashboardData,];
    }
    // @ts-ignore
    [];
    var __VLS_31;
    var __VLS_32;
    var __VLS_37 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_38), false));
    var __VLS_42 = void 0;
    var __VLS_43 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'inventory.transfers' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_44 = __VLS_40.slots.default;
    {
        var __VLS_45 = __VLS_40.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.dashboardData.transfers.pending);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-arrow-right-arrow-left text-3xl text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-arrow-right-arrow-left']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        // @ts-ignore
        [dashboardData,];
    }
    // @ts-ignore
    [];
    var __VLS_40;
    var __VLS_41;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    var __VLS_46 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ class: "lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ class: "lg:col-span-3 hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_51 = __VLS_49.slots.default;
    {
        var __VLS_52 = __VLS_49.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        var __VLS_53 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })));
        var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_54), false));
        var __VLS_58 = void 0;
        var __VLS_59 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'inventory.transactions' });
                    // @ts-ignore
                    [router,];
                } });
        var __VLS_56;
        var __VLS_57;
        // @ts-ignore
        [];
    }
    {
        var __VLS_60 = __VLS_49.slots.content;
        var __VLS_61 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61(__assign(__assign({ value: (__VLS_ctx.dashboardData.recent_transactions) }, { class: "p-datatable-sm" }), { responsiveLayout: "scroll", loading: (__VLS_ctx.loading), sortMode: "multiple", removableSort: true, rowHover: true, stripedRows: true, size: "small" })));
        var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.dashboardData.recent_transactions) }, { class: "p-datatable-sm" }), { responsiveLayout: "scroll", loading: (__VLS_ctx.loading), sortMode: "multiple", removableSort: true, rowHover: true, stripedRows: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_62), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_66 = __VLS_64.slots.default;
        var __VLS_67 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67({
            field: "transaction_number",
            header: "Reference",
            sortable: true,
            removableSort: true,
        }));
        var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([{
                field: "transaction_number",
                header: "Reference",
                sortable: true,
                removableSort: true,
            }], __VLS_functionalComponentArgsRest(__VLS_68), false));
        var __VLS_72 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72({
            field: "transaction_type",
            header: "Type",
            sortable: true,
            removableSort: true,
        }));
        var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([{
                field: "transaction_type",
                header: "Type",
                sortable: true,
                removableSort: true,
            }], __VLS_functionalComponentArgsRest(__VLS_73), false));
        var __VLS_77 = __VLS_75.slots.default;
        {
            var __VLS_78 = __VLS_75.slots.body;
            var data = __VLS_vSlot(__VLS_78)[0].data;
            var __VLS_79 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79({
                severity: (__VLS_ctx.getTransactionSeverity(data.transaction_type)),
                value: (__VLS_ctx.formatTransactionType(data.transaction_type)),
            }));
            var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([{
                    severity: (__VLS_ctx.getTransactionSeverity(data.transaction_type)),
                    value: (__VLS_ctx.formatTransactionType(data.transaction_type)),
                }], __VLS_functionalComponentArgsRest(__VLS_80), false));
            // @ts-ignore
            [loading, dashboardData, getTransactionSeverity, formatTransactionType,];
        }
        // @ts-ignore
        [];
        var __VLS_75;
        var __VLS_84 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84({
            field: "branch.name",
            header: "Branch",
            sortable: true,
            removableSort: true,
        }));
        var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([{
                field: "branch.name",
                header: "Branch",
                sortable: true,
                removableSort: true,
            }], __VLS_functionalComponentArgsRest(__VLS_85), false));
        var __VLS_89 = __VLS_87.slots.default;
        {
            var __VLS_90 = __VLS_87.slots.body;
            var data = __VLS_vSlot(__VLS_90)[0].data;
            (((_a = data.branch) === null || _a === void 0 ? void 0 : _a.name) || 'N/A');
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_87;
        var __VLS_91 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91({
            field: "product.product_name",
            header: "Product",
            sortable: true,
        }));
        var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([{
                field: "product.product_name",
                header: "Product",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_92), false));
        var __VLS_96 = __VLS_94.slots.default;
        {
            var __VLS_97 = __VLS_94.slots.body;
            var data = __VLS_vSlot(__VLS_97)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (((_b = data.product) === null || _b === void 0 ? void 0 : _b.product_name) || 'N/A');
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (((_c = data.product) === null || _c === void 0 ? void 0 : _c.sku) || '');
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_94;
        var __VLS_98 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({
            field: "quantity_change",
            header: "Quantity",
            sortable: true,
            removableSort: true,
        }));
        var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{
                field: "quantity_change",
                header: "Quantity",
                sortable: true,
                removableSort: true,
            }], __VLS_functionalComponentArgsRest(__VLS_99), false));
        var __VLS_103 = __VLS_101.slots.default;
        {
            var __VLS_104 = __VLS_101.slots.body;
            var data = __VLS_vSlot(__VLS_104)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: (__VLS_ctx.getQuantityClass(data.quantity_change)) }));
            (data.quantity_change > 0 ? '+' : '');
            (data.quantity_change);
            // @ts-ignore
            [getQuantityClass,];
        }
        // @ts-ignore
        [];
        var __VLS_101;
        var __VLS_105 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105({
            field: "transaction_date",
            header: "Date",
            sortable: true,
            removableSort: true,
        }));
        var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([{
                field: "transaction_date",
                header: "Date",
                sortable: true,
                removableSort: true,
            }], __VLS_functionalComponentArgsRest(__VLS_106), false));
        var __VLS_110 = __VLS_108.slots.default;
        {
            var __VLS_111 = __VLS_108.slots.body;
            var data = __VLS_vSlot(__VLS_111)[0].data;
            (__VLS_ctx.formatDate(data.transaction_date || data.created_at));
            // @ts-ignore
            [formatDate,];
        }
        // @ts-ignore
        [];
        var __VLS_108;
        {
            var __VLS_112 = __VLS_64.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-300 mb-3" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_64;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_49;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 text-right" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
    (((_d = __VLS_ctx.dashboardData.period) === null || _d === void 0 ? void 0 : _d.range) || 'current');
    (__VLS_ctx.formatDate((_e = __VLS_ctx.dashboardData.period) === null || _e === void 0 ? void 0 : _e.start_date));
    (__VLS_ctx.formatDate((_f = __VLS_ctx.dashboardData.period) === null || _f === void 0 ? void 0 : _f.end_date));
}
// @ts-ignore
[dashboardData, dashboardData, dashboardData, formatDate, formatDate,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
