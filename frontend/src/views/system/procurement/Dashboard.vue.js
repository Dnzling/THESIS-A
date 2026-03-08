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
var procurement_service_1 = require("../../../services/procurement.service");
var router = (0, vue_router_1.useRouter)();
var loading = (0, vue_1.ref)(true);
var stats = (0, vue_1.ref)({});
var pendingRows = (0, vue_1.computed)(function () {
    var _a, _b;
    return [
        { type: 'Purchase Requisitions', count: ((_a = stats.value.pending_approvals) === null || _a === void 0 ? void 0 : _a.pr_count) || 0 },
        { type: 'Purchase Orders', count: ((_b = stats.value.pending_approvals) === null || _b === void 0 ? void 0 : _b.po_count) || 0 }
    ];
});
var loadDashboard = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getDashboardStats()];
            case 2:
                response = _a.sent();
                stats.value = response.data || {};
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load procurement dashboard', error_1);
                stats.value = {};
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var formatNumber = function (value) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
};
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
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ height: "280px" }, { class: "rounded-lg" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ height: "280px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
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
                __VLS_ctx.router.push({ name: 'procurement.suppliers' });
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
        (((_a = __VLS_ctx.stats.active_suppliers) === null || _a === void 0 ? void 0 : _a.count) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-3xl text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        // @ts-ignore
        [stats,];
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
                __VLS_ctx.router.push({ name: 'procurement.purchase-requisitions' });
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
        (((_b = __VLS_ctx.stats.pending_approvals) === null || _b === void 0 ? void 0 : _b.pr_count) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-amber-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-amber-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-edit text-3xl text-amber-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-file-edit']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-amber-600']} */ ;
        // @ts-ignore
        [stats,];
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
                __VLS_ctx.router.push({ name: 'procurement.purchase-orders' });
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
        (((_c = __VLS_ctx.stats.active_pos) === null || _c === void 0 ? void 0 : _c.count) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.formatNumber(((_d = __VLS_ctx.stats.active_pos) === null || _d === void 0 ? void 0 : _d.total_value) || 0));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-indigo-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-indigo-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-shopping-cart text-3xl text-indigo-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-shopping-cart']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
        // @ts-ignore
        [stats, stats, formatNumber,];
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
                __VLS_ctx.router.push({ name: 'procurement.payments' });
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
        (((_e = __VLS_ctx.stats.pending_payments) === null || _e === void 0 ? void 0 : _e.count) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.formatNumber(((_f = __VLS_ctx.stats.pending_payments) === null || _f === void 0 ? void 0 : _f.total_amount) || 0));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-wallet text-3xl text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-wallet']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        // @ts-ignore
        [stats, stats, formatNumber,];
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
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ class: "lg:col-span-2" })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ class: "lg:col-span-2" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    var __VLS_51 = __VLS_49.slots.default;
    {
        var __VLS_52 = __VLS_49.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-history text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-history']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_53 = __VLS_49.slots.content;
        var __VLS_54 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54(__assign(__assign({ value: (__VLS_ctx.pendingRows) }, { class: "p-datatable-sm" }), { stripedRows: true })));
        var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.pendingRows) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_55), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_59 = __VLS_57.slots.default;
        var __VLS_60 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60({
            field: "type",
            header: "Type",
        }));
        var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([{
                field: "type",
                header: "Type",
            }], __VLS_functionalComponentArgsRest(__VLS_61), false));
        var __VLS_65 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({
            field: "count",
            header: "Count",
        }));
        var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{
                field: "count",
                header: "Count",
            }], __VLS_functionalComponentArgsRest(__VLS_66), false));
        // @ts-ignore
        [pendingRows,];
        var __VLS_57;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_49;
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70({}));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_71), false));
    var __VLS_75 = __VLS_73.slots.default;
    {
        var __VLS_76 = __VLS_73.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-bolt text-yellow-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-bolt']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_77 = __VLS_73.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        var __VLS_78 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78(__assign(__assign({ 'onClick': {} }, { label: "Add Supplier", icon: "pi pi-plus" }), { class: "w-full" })));
        var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Add Supplier", icon: "pi pi-plus" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_79), false));
        var __VLS_83 = void 0;
        var __VLS_84 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'procurement.suppliers.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_81;
        var __VLS_82;
        var __VLS_85 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign(__assign({ 'onClick': {} }, { label: "Create PR", icon: "pi pi-file", severity: "secondary" }), { class: "w-full" })));
        var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create PR", icon: "pi pi-file", severity: "secondary" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_86), false));
        var __VLS_90 = void 0;
        var __VLS_91 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'procurement.purchase-requisitions.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_88;
        var __VLS_89;
        var __VLS_92 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92(__assign(__assign({ 'onClick': {} }, { label: "Create RFQ", icon: "pi pi-send", severity: "info" }), { class: "w-full" })));
        var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create RFQ", icon: "pi pi-send", severity: "info" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_93), false));
        var __VLS_97 = void 0;
        var __VLS_98 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'procurement.rfqs.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_95;
        var __VLS_96;
        var __VLS_99 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign(__assign({ 'onClick': {} }, { label: "Create PO", icon: "pi pi-shopping-cart", severity: "success" }), { class: "w-full" })));
        var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create PO", icon: "pi pi-shopping-cart", severity: "success" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_100), false));
        var __VLS_104 = void 0;
        var __VLS_105 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'procurement.purchase-orders.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_102;
        var __VLS_103;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_73;
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
