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
var _a, _b, _c, _d;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(false);
var alerts = (0, vue_1.ref)([]);
var stats = (0, vue_1.reactive)({
    active: 0,
    critical: 0,
    acknowledged: 0,
    resolved: 0
});
var filters = (0, vue_1.reactive)({
    status: null,
    severity: null,
    search: ''
});
var statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Acknowledged', value: 'acknowledged' },
    { label: 'Resolved', value: 'resolved' }
];
var severityOptions = [
    { label: 'Warning', value: 'warning' },
    { label: 'Critical', value: 'critical' }
];
var getTypeSeverity = function (type) {
    if (type === 'low_stock')
        return 'warning';
    if (type === 'out_of_stock')
        return 'danger';
    if (type === 'overstock')
        return 'info';
    return 'secondary';
};
var formatDate = function (date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
var loadAlerts = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, statsResponse, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 4, 5, 6]);
                params = {};
                if (filters.status)
                    params.status = filters.status;
                if (filters.severity)
                    params.severity = filters.severity;
                if (filters.search)
                    params.search = filters.search;
                return [4 /*yield*/, axios_1.default.get('/api/inventory/alert-management', { params: params })];
            case 2:
                response = _c.sent();
                alerts.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || [];
                return [4 /*yield*/, axios_1.default.get('/api/inventory/alert-management/statistics')];
            case 3:
                statsResponse = _c.sent();
                Object.assign(stats, ((_b = statsResponse.data) === null || _b === void 0 ? void 0 : _b.data) || {});
                return [3 /*break*/, 6];
            case 4:
                error_1 = _c.sent();
                console.error('Failed to load alerts', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load alerts',
                    life: 3000
                });
                return [3 /*break*/, 6];
            case 5:
                loading.value = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
var acknowledgeAlert = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/inventory/alert-management/".concat(id, "/acknowledge"))];
            case 1:
                _a.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Alert acknowledged',
                    life: 2000
                });
                loadAlerts();
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to acknowledge alert', error_2);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to acknowledge alert',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var resolveAlert = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/inventory/alert-management/".concat(id, "/resolve"))];
            case 1:
                _a.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Alert resolved',
                    life: 2000
                });
                loadAlerts();
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Failed to resolve alert', error_3);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to resolve alert',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var resetFilters = function () {
    filters.status = null;
    filters.severity = null;
    filters.search = '';
    loadAlerts();
};
(0, vue_1.onMounted)(function () {
    loadAlerts();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 bg-gray-50 min-h-screen" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_5 = __VLS_3.slots.default;
{
    var __VLS_6 = __VLS_3.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.stats.active);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-bell text-2xl text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-bell']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.stats.critical);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-2xl text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_19 = __VLS_17.slots.default;
{
    var __VLS_20 = __VLS_17.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.stats.acknowledged);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-yellow-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-yellow-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-2xl text-yellow-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_17;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_26 = __VLS_24.slots.default;
{
    var __VLS_27 = __VLS_24.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.stats.resolved);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-2xl text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_24;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ class: "mb-6" })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_33 = __VLS_31.slots.default;
{
    var __VLS_34 = __VLS_31.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Statuses", showClear: true })));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Statuses", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = void 0;
    var __VLS_41 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAlerts) });
    var __VLS_38;
    var __VLS_39;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.severity), options: (__VLS_ctx.severityOptions), optionLabel: "label", optionValue: "value", placeholder: "All Severities", showClear: true })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.severity), options: (__VLS_ctx.severityOptions), optionLabel: "label", optionValue: "value", placeholder: "All Severities", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    var __VLS_47 = void 0;
    var __VLS_48 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAlerts) });
    var __VLS_45;
    var __VLS_46;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49({}));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_50), false));
    var __VLS_54 = __VLS_52.slots.default;
    var __VLS_55 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign({ class: "pi pi-search" })));
    var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_56), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search item..." })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search item..." })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ keyup: {} },
        { onKeyup: (__VLS_ctx.loadAlerts) });
    var __VLS_63;
    var __VLS_64;
    // @ts-ignore
    [filters, filters, filters, statusOptions, loadAlerts, loadAlerts, loadAlerts, severityOptions,];
    var __VLS_52;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67(__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset" })));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset" })], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = void 0;
    var __VLS_73 = ({ click: {} },
        { onClick: (__VLS_ctx.resetFilters) });
    var __VLS_70;
    var __VLS_71;
    // @ts-ignore
    [resetFilters,];
}
// @ts-ignore
[];
var __VLS_31;
var __VLS_74;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74({}));
var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_75), false));
var __VLS_79 = __VLS_77.slots.default;
{
    var __VLS_80 = __VLS_77.slots.content;
    var __VLS_81 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign(__assign({ value: (__VLS_ctx.alerts), loading: (__VLS_ctx.loading), paginator: true, rows: (10), totalRecords: (__VLS_ctx.alerts.length), dataKey: "id" }, { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.alerts), loading: (__VLS_ctx.loading), paginator: true, rows: (10), totalRecords: (__VLS_ctx.alerts.length), dataKey: "id" }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_82), false));
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_86 = __VLS_84.slots.default;
    {
        var __VLS_87 = __VLS_84.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        // @ts-ignore
        [alerts, alerts, loading,];
    }
    var __VLS_88 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88({
        field: "inventory_item.product.sku",
        header: "SKU",
    }));
    var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([{
            field: "inventory_item.product.sku",
            header: "SKU",
        }], __VLS_functionalComponentArgsRest(__VLS_89), false));
    var __VLS_93 = __VLS_91.slots.default;
    {
        var __VLS_94 = __VLS_91.slots.body;
        var data = __VLS_vSlot(__VLS_94)[0].data;
        (((_b = (_a = data.inventory_item) === null || _a === void 0 ? void 0 : _a.product) === null || _b === void 0 ? void 0 : _b.sku) || 'N/A');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_91;
    var __VLS_95 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95({
        field: "inventory_item.product.product_name",
        header: "Item Name",
    }));
    var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([{
            field: "inventory_item.product.product_name",
            header: "Item Name",
        }], __VLS_functionalComponentArgsRest(__VLS_96), false));
    var __VLS_100 = __VLS_98.slots.default;
    {
        var __VLS_101 = __VLS_98.slots.body;
        var data = __VLS_vSlot(__VLS_101)[0].data;
        (((_d = (_c = data.inventory_item) === null || _c === void 0 ? void 0 : _c.product) === null || _d === void 0 ? void 0 : _d.product_name) || 'N/A');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_98;
    var __VLS_102 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({
        field: "type",
        header: "Alert Type",
    }));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{
            field: "type",
            header: "Alert Type",
        }], __VLS_functionalComponentArgsRest(__VLS_103), false));
    var __VLS_107 = __VLS_105.slots.default;
    {
        var __VLS_108 = __VLS_105.slots.body;
        var data = __VLS_vSlot(__VLS_108)[0].data;
        var __VLS_109 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109({
            value: (data.type),
            severity: (__VLS_ctx.getTypeSeverity(data.type)),
        }));
        var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([{
                value: (data.type),
                severity: (__VLS_ctx.getTypeSeverity(data.type)),
            }], __VLS_functionalComponentArgsRest(__VLS_110), false));
        // @ts-ignore
        [getTypeSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_105;
    var __VLS_114 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114({
        field: "severity",
        header: "Severity",
    }));
    var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([{
            field: "severity",
            header: "Severity",
        }], __VLS_functionalComponentArgsRest(__VLS_115), false));
    var __VLS_119 = __VLS_117.slots.default;
    {
        var __VLS_120 = __VLS_117.slots.body;
        var data = __VLS_vSlot(__VLS_120)[0].data;
        var __VLS_121 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121({
            value: (data.severity),
            severity: (data.severity === 'critical' ? 'danger' : 'warning'),
        }));
        var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([{
                value: (data.severity),
                severity: (data.severity === 'critical' ? 'danger' : 'warning'),
            }], __VLS_functionalComponentArgsRest(__VLS_122), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_117;
    var __VLS_126 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_127 = __VLS_asFunctionalComponent1(__VLS_126, new __VLS_126({
        field: "status",
        header: "Status",
    }));
    var __VLS_128 = __VLS_127.apply(void 0, __spreadArray([{
            field: "status",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_127), false));
    var __VLS_131 = __VLS_129.slots.default;
    {
        var __VLS_132 = __VLS_129.slots.body;
        var data = __VLS_vSlot(__VLS_132)[0].data;
        var __VLS_133 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133({
            value: (data.status),
            severity: (data.status === 'active' ? 'info' : data.status === 'acknowledged' ? 'warning' : 'success'),
        }));
        var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([{
                value: (data.status),
                severity: (data.status === 'active' ? 'info' : data.status === 'acknowledged' ? 'warning' : 'success'),
            }], __VLS_functionalComponentArgsRest(__VLS_134), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_129;
    var __VLS_138 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138({
        field: "created_at",
        header: "Created",
    }));
    var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([{
            field: "created_at",
            header: "Created",
        }], __VLS_functionalComponentArgsRest(__VLS_139), false));
    var __VLS_143 = __VLS_141.slots.default;
    {
        var __VLS_144 = __VLS_141.slots.body;
        var data = __VLS_vSlot(__VLS_144)[0].data;
        (__VLS_ctx.formatDate(data.created_at));
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_141;
    var __VLS_145 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_146), false));
    var __VLS_150 = __VLS_148.slots.default;
    {
        var __VLS_151 = __VLS_148.slots.body;
        var data_1 = __VLS_vSlot(__VLS_151)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        if (data_1.status === 'active') {
            var __VLS_152 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", text: true, severity: "success" })));
            var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", text: true, severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_153), false));
            var __VLS_157 = void 0;
            var __VLS_158 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status === 'active'))
                            return;
                        __VLS_ctx.acknowledgeAlert(data_1.id);
                        // @ts-ignore
                        [acknowledgeAlert,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Acknowledge alert') }), null, null);
            var __VLS_155;
            var __VLS_156;
        }
        if (data_1.status !== 'resolved') {
            var __VLS_159 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", size: "small", text: true, severity: "help" })));
            var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", size: "small", text: true, severity: "help" })], __VLS_functionalComponentArgsRest(__VLS_160), false));
            var __VLS_164 = void 0;
            var __VLS_165 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status !== 'resolved'))
                            return;
                        __VLS_ctx.resolveAlert(data_1.id);
                        // @ts-ignore
                        [vTooltip, resolveAlert,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Resolve alert') }), null, null);
            var __VLS_162;
            var __VLS_163;
        }
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_148;
    // @ts-ignore
    [];
    var __VLS_84;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_77;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
