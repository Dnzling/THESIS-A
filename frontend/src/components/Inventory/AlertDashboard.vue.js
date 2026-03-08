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
var _a, _b;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var axios_1 = require("../../axios");
var loading = (0, vue_1.ref)(false);
var alerts = (0, vue_1.ref)([]);
var totalRecords = (0, vue_1.ref)(0);
var stats = (0, vue_1.reactive)({ active: 0, critical: 0, acknowledged: 0, resolved: 0 });
var filters = (0, vue_1.reactive)({
    status: null,
    severity: null,
    type: null,
    search: ''
});
var statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Acknowledged', value: 'acknowledged' },
    { label: 'Resolved', value: 'resolved' }
];
var severityOptions = [
    { label: 'Critical', value: 'critical' },
    { label: 'High', value: 'high' },
    { label: 'Medium', value: 'medium' },
    { label: 'Low', value: 'low' }
];
var typeOptions = [
    { label: 'Low Stock', value: 'low_stock' },
    { label: 'Out of Stock', value: 'out_of_stock' },
    { label: 'Overstock', value: 'overstock' },
    { label: 'Expiring Soon', value: 'expiring_soon' }
];
var showConfigModal = (0, vue_1.ref)(false);
var showDetailsDialog = (0, vue_1.ref)(false);
var selectedAlert = (0, vue_1.ref)(null);
var actionNotes = (0, vue_1.ref)('');
var config = (0, vue_1.reactive)({
    low_stock_threshold: 30,
    out_of_stock_threshold: 0,
    overstock_threshold: 150
});
// Get severity styling
var getTypeSeverity = function (type) {
    var severities = {
        low_stock: 'warning',
        out_of_stock: 'danger',
        overstock: 'info',
        expiring_soon: 'warning'
    };
    return severities[type] || 'info';
};
var getSeveritySeverity = function (severity) {
    var severities = {
        critical: 'danger',
        high: 'warning',
        medium: 'info',
        low: 'success'
    };
    return severities[severity] || 'info';
};
var getStatusSeverity = function (status) {
    var severities = {
        active: 'danger',
        acknowledged: 'warning',
        resolved: 'success'
    };
    return severities[status] || 'info';
};
var fetchAlerts = function () {
    var args_1 = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args_1[_i] = arguments[_i];
    }
    return __awaiter(void 0, __spreadArray([], args_1, true), void 0, function (page) {
        var response, error_1;
        var _a;
        if (page === void 0) { page = 0; }
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    loading.value = true;
                    _b.label = 1;
                case 1:
                    _b.trys.push([1, 4, 5, 6]);
                    return [4 /*yield*/, axios_1.default.get('/api/inventory/alert-management', {
                            params: {
                                page: page + 1,
                                status: filters.status,
                                severity: filters.severity,
                                type: filters.type,
                                search: filters.search
                            }
                        })];
                case 2:
                    response = _b.sent();
                    alerts.value = response.data.data;
                    totalRecords.value = ((_a = response.data.meta) === null || _a === void 0 ? void 0 : _a.total) || 0;
                    return [4 /*yield*/, fetchStats()];
                case 3:
                    _b.sent();
                    return [3 /*break*/, 6];
                case 4:
                    error_1 = _b.sent();
                    console.error('Failed to fetch alerts:', error_1);
                    return [3 /*break*/, 6];
                case 5:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 6: return [2 /*return*/];
            }
        });
    });
};
var fetchStats = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('/api/inventory/alert-management/statistics')];
            case 1:
                response = _a.sent();
                stats.active = response.data.active || 0;
                stats.critical = response.data.critical || 0;
                stats.acknowledged = response.data.acknowledged || 0;
                stats.resolved = response.data.resolved || 0;
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to fetch stats:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var acknowledgeAlert = function (alert) { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/inventory/alert-management/".concat(alert.id, "/acknowledge"), {
                        notes: 'Alert acknowledged'
                    })];
            case 1:
                _a.sent();
                alert.status = 'acknowledged';
                stats.acknowledged++;
                stats.active = Math.max(0, stats.active - 1);
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Failed to acknowledge alert:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var resolveAlert = function (alert) { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/inventory/alert-management/".concat(alert.id, "/resolve"), {
                        notes: 'Alert resolved'
                    })];
            case 1:
                _a.sent();
                alert.status = 'resolved';
                stats.resolved++;
                stats.active = Math.max(0, stats.active - 1);
                return [3 /*break*/, 3];
            case 2:
                error_4 = _a.sent();
                console.error('Failed to resolve alert:', error_4);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var viewAlertDetails = function (alert) {
    selectedAlert.value = alert;
    actionNotes.value = '';
    showDetailsDialog.value = true;
};
var confirmAcknowledge = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!selectedAlert.value) return [3 /*break*/, 2];
                return [4 /*yield*/, acknowledgeAlert(selectedAlert.value)];
            case 1:
                _a.sent();
                showDetailsDialog.value = false;
                _a.label = 2;
            case 2: return [2 /*return*/];
        }
    });
}); };
var confirmResolve = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!selectedAlert.value) return [3 /*break*/, 2];
                return [4 /*yield*/, resolveAlert(selectedAlert.value)];
            case 1:
                _a.sent();
                showDetailsDialog.value = false;
                _a.label = 2;
            case 2: return [2 /*return*/];
        }
    });
}); };
var saveConfiguration = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_5;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.put('/api/inventory/configuration', config)];
            case 1:
                _a.sent();
                showConfigModal.value = false;
                return [3 /*break*/, 3];
            case 2:
                error_5 = _a.sent();
                console.error('Failed to save configuration:', error_5);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var onPageChange = function (event) {
    fetchAlerts(event.page);
};
(0, vue_1.onMounted)(function () {
    fetchAlerts();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col md:flex-row md:items-center md:justify-between gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['md:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['md:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: ("Refresh"), icon: "pi pi-refresh", loading: (__VLS_ctx.loading) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: ("Refresh"), icon: "pi pi-refresh", loading: (__VLS_ctx.loading) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.fetchAlerts) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Configure Rules", icon: "pi pi-cog", severity: "secondary" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Configure Rules", icon: "pi pi-cog", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showConfigModal = true;
            // @ts-ignore
            [loading, fetchAlerts, showConfigModal,];
        } });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-red-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.active);
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-circle text-4xl text-red-200" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-200']} */ ;
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-orange-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.critical);
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-bell text-4xl text-orange-200" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-bell']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-200']} */ ;
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
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_33 = __VLS_31.slots.default;
{
    var __VLS_34 = __VLS_31.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-blue-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.acknowledged);
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-4xl text-blue-200" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-200']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_31;
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ class: "hover:shadow-lg transition-shadow" })));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
var __VLS_40 = __VLS_38.slots.default;
{
    var __VLS_41 = __VLS_38.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-green-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.resolved);
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-4xl text-green-200" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-200']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_38;
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42({}));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_43), false));
var __VLS_47 = __VLS_45.slots.default;
{
    var __VLS_48 = __VLS_45.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All statuses" }), { class: "w-full" })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All statuses" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    var __VLS_54 = void 0;
    var __VLS_55 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchAlerts) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_52;
    var __VLS_53;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_56 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.severity), options: (__VLS_ctx.severityOptions), optionLabel: "label", optionValue: "value", placeholder: "All severities" }), { class: "w-full" })));
    var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.severity), options: (__VLS_ctx.severityOptions), optionLabel: "label", optionValue: "value", placeholder: "All severities" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_57), false));
    var __VLS_61 = void 0;
    var __VLS_62 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchAlerts) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_59;
    var __VLS_60;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_63 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeOptions), optionLabel: "label", optionValue: "value", placeholder: "All types" }), { class: "w-full" })));
    var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeOptions), optionLabel: "label", optionValue: "value", placeholder: "All types" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_64), false));
    var __VLS_68 = void 0;
    var __VLS_69 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchAlerts) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_66;
    var __VLS_67;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70(__assign(__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search..." }), { class: "w-full" })));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([__assign(__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_71), false));
    var __VLS_75 = void 0;
    var __VLS_76 = ({ keyup: {} },
        { onKeyup: (__VLS_ctx.fetchAlerts) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_73;
    var __VLS_74;
    // @ts-ignore
    [fetchAlerts, fetchAlerts, fetchAlerts, fetchAlerts, filters, filters, filters, filters, statusOptions, severityOptions, typeOptions,];
}
// @ts-ignore
[];
var __VLS_45;
var __VLS_77;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77({}));
var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_78), false));
var __VLS_82 = __VLS_80.slots.default;
{
    var __VLS_83 = __VLS_80.slots.title;
    // @ts-ignore
    [];
}
{
    var __VLS_84 = __VLS_80.slots.content;
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.alerts), loading: (__VLS_ctx.loading) }), { class: "p-datatable-sm" }), { stripedRows: true, responsiveLayout: "scroll", paginator: (true), rows: (10), totalRecords: (__VLS_ctx.totalRecords), lazy: (true) })));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.alerts), loading: (__VLS_ctx.loading) }), { class: "p-datatable-sm" }), { stripedRows: true, responsiveLayout: "scroll", paginator: (true), rows: (10), totalRecords: (__VLS_ctx.totalRecords), lazy: (true) })], __VLS_functionalComponentArgsRest(__VLS_86), false));
    var __VLS_90 = void 0;
    var __VLS_91 = ({ page: {} },
        { onPage: (__VLS_ctx.onPageChange) });
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_92 = __VLS_88.slots.default;
    {
        var __VLS_93 = __VLS_88.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-400 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        // @ts-ignore
        [loading, alerts, totalRecords, onPageChange,];
    }
    var __VLS_94 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94(__assign({ field: "sku", header: "SKU" }, { style: {} })));
    var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([__assign({ field: "sku", header: "SKU" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_95), false));
    var __VLS_99 = __VLS_97.slots.default;
    {
        var __VLS_100 = __VLS_97.slots.body;
        var data = __VLS_vSlot(__VLS_100)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-xs bg-gray-100 px-2 py-1 rounded" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        (data.sku);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_97;
    var __VLS_101 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101(__assign(__assign({ field: "product_name", header: "Product" }, { style: {} }), { sortable: true })));
    var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([__assign(__assign({ field: "product_name", header: "Product" }, { style: {} }), { sortable: true })], __VLS_functionalComponentArgsRest(__VLS_102), false));
    var __VLS_106 = __VLS_104.slots.default;
    {
        var __VLS_107 = __VLS_104.slots.body;
        var data = __VLS_vSlot(__VLS_107)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (data.product_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (data.branch_name);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_104;
    var __VLS_108 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108(__assign({ field: "alert_type", header: "Type" }, { style: {} })));
    var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([__assign({ field: "alert_type", header: "Type" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_109), false));
    var __VLS_113 = __VLS_111.slots.default;
    {
        var __VLS_114 = __VLS_111.slots.body;
        var data = __VLS_vSlot(__VLS_114)[0].data;
        var __VLS_115 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115({
            value: (data.alert_type),
            severity: (__VLS_ctx.getTypeSeverity(data.alert_type)),
        }));
        var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([{
                value: (data.alert_type),
                severity: (__VLS_ctx.getTypeSeverity(data.alert_type)),
            }], __VLS_functionalComponentArgsRest(__VLS_116), false));
        // @ts-ignore
        [getTypeSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_111;
    var __VLS_120 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120(__assign({ field: "severity", header: "Severity" }, { style: {} })));
    var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([__assign({ field: "severity", header: "Severity" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_121), false));
    var __VLS_125 = __VLS_123.slots.default;
    {
        var __VLS_126 = __VLS_123.slots.body;
        var data = __VLS_vSlot(__VLS_126)[0].data;
        var __VLS_127 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127({
            value: (data.severity),
            severity: (__VLS_ctx.getSeveritySeverity(data.severity)),
        }));
        var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([{
                value: (data.severity),
                severity: (__VLS_ctx.getSeveritySeverity(data.severity)),
            }], __VLS_functionalComponentArgsRest(__VLS_128), false));
        // @ts-ignore
        [getSeveritySeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_123;
    var __VLS_132 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_133 = __VLS_asFunctionalComponent1(__VLS_132, new __VLS_132(__assign({ header: "Stock Info" }, { style: {} })));
    var __VLS_134 = __VLS_133.apply(void 0, __spreadArray([__assign({ header: "Stock Info" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_133), false));
    var __VLS_137 = __VLS_135.slots.default;
    {
        var __VLS_138 = __VLS_135.slots.body;
        var data = __VLS_vSlot(__VLS_138)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.current_stock);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.reorder_point);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_135;
    var __VLS_139 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139(__assign({ field: "status", header: "Status" }, { style: {} })));
    var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_140), false));
    var __VLS_144 = __VLS_142.slots.default;
    {
        var __VLS_145 = __VLS_142.slots.body;
        var data = __VLS_vSlot(__VLS_145)[0].data;
        var __VLS_146 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Badge} */
        Badge;
        // @ts-ignore
        var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146({
            value: (data.status),
            severity: (__VLS_ctx.getStatusSeverity(data.status)),
        }));
        var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([{
                value: (data.status),
                severity: (__VLS_ctx.getStatusSeverity(data.status)),
            }], __VLS_functionalComponentArgsRest(__VLS_147), false));
        // @ts-ignore
        [getStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_142;
    var __VLS_151 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151(__assign(__assign({ header: "Actions" }, { style: {} }), { frozen: (true), alignFrozen: "right" })));
    var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([__assign(__assign({ header: "Actions" }, { style: {} }), { frozen: (true), alignFrozen: "right" })], __VLS_functionalComponentArgsRest(__VLS_152), false));
    var __VLS_156 = __VLS_154.slots.default;
    {
        var __VLS_157 = __VLS_154.slots.body;
        var data_1 = __VLS_vSlot(__VLS_157)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        if (data_1.status === 'active') {
            var __VLS_158 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_159 = __VLS_asFunctionalComponent1(__VLS_158, new __VLS_158(__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "warning", text: true, rounded: true })));
            var __VLS_160 = __VLS_159.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "warning", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_159), false));
            var __VLS_163 = void 0;
            var __VLS_164 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status === 'active'))
                            return;
                        __VLS_ctx.acknowledgeAlert(data_1);
                        // @ts-ignore
                        [acknowledgeAlert,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Acknowledge') }), null, null);
            var __VLS_161;
            var __VLS_162;
        }
        if (data_1.status !== 'resolved') {
            var __VLS_165 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_166 = __VLS_asFunctionalComponent1(__VLS_165, new __VLS_165(__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", severity: "success", text: true, rounded: true })));
            var __VLS_167 = __VLS_166.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", severity: "success", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_166), false));
            var __VLS_170 = void 0;
            var __VLS_171 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status !== 'resolved'))
                            return;
                        __VLS_ctx.resolveAlert(data_1);
                        // @ts-ignore
                        [vTooltip, resolveAlert,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Resolve') }), null, null);
            var __VLS_168;
            var __VLS_169;
        }
        var __VLS_172 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_173 = __VLS_asFunctionalComponent1(__VLS_172, new __VLS_172(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })));
        var __VLS_174 = __VLS_173.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_173), false));
        var __VLS_177 = void 0;
        var __VLS_178 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewAlertDetails(data_1);
                    // @ts-ignore
                    [vTooltip, viewAlertDetails,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View Details') }), null, null);
        var __VLS_175;
        var __VLS_176;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_154;
    // @ts-ignore
    [];
    var __VLS_88;
    var __VLS_89;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_80;
var __VLS_179;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_180 = __VLS_asFunctionalComponent1(__VLS_179, new __VLS_179(__assign({ visible: (__VLS_ctx.showConfigModal), header: "Configure Alert Rules", modal: (true) }, { style: {} })));
var __VLS_181 = __VLS_180.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showConfigModal), header: "Configure Alert Rules", modal: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_180), false));
var __VLS_184 = __VLS_182.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_185;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign({ modelValue: (__VLS_ctx.config.low_stock_threshold), min: (1), max: (100), placeholder: "Enter percentage", suffix: "%" }, { class: "w-full" })));
var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.config.low_stock_threshold), min: (1), max: (100), placeholder: "Enter percentage", suffix: "%" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_186), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_190;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_191 = __VLS_asFunctionalComponent1(__VLS_190, new __VLS_190(__assign({ modelValue: (__VLS_ctx.config.out_of_stock_threshold), min: (0), max: (10), placeholder: "Enter quantity" }, { class: "w-full" })));
var __VLS_192 = __VLS_191.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.config.out_of_stock_threshold), min: (0), max: (10), placeholder: "Enter quantity" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_191), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_195;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195(__assign({ modelValue: (__VLS_ctx.config.overstock_threshold), min: (100), max: (500), placeholder: "Enter percentage", suffix: "%" }, { class: "w-full" })));
var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.config.overstock_threshold), min: (100), max: (500), placeholder: "Enter percentage", suffix: "%" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_196), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_200 = __VLS_182.slots.footer;
    var __VLS_201 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_202 = __VLS_asFunctionalComponent1(__VLS_201, new __VLS_201(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_203 = __VLS_202.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_202), false));
    var __VLS_206 = void 0;
    var __VLS_207 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showConfigModal = false;
                // @ts-ignore
                [showConfigModal, showConfigModal, config, config, config,];
            } });
    var __VLS_204;
    var __VLS_205;
    var __VLS_208 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_209 = __VLS_asFunctionalComponent1(__VLS_208, new __VLS_208(__assign({ 'onClick': {} }, { label: "Save Configuration" })));
    var __VLS_210 = __VLS_209.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Configuration" })], __VLS_functionalComponentArgsRest(__VLS_209), false));
    var __VLS_213 = void 0;
    var __VLS_214 = ({ click: {} },
        { onClick: (__VLS_ctx.saveConfiguration) });
    var __VLS_211;
    var __VLS_212;
    // @ts-ignore
    [saveConfiguration,];
}
// @ts-ignore
[];
var __VLS_182;
var __VLS_215;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_216 = __VLS_asFunctionalComponent1(__VLS_215, new __VLS_215(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: "Alert Details", modal: (true) }, { style: {} })));
var __VLS_217 = __VLS_216.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDetailsDialog), header: "Alert Details", modal: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_216), false));
var __VLS_220 = __VLS_218.slots.default;
if (__VLS_ctx.selectedAlert) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.selectedAlert.sku);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.selectedAlert.product_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.selectedAlert.branch_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_221 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_222 = __VLS_asFunctionalComponent1(__VLS_221, new __VLS_221(__assign({ value: (__VLS_ctx.selectedAlert.alert_type), severity: (__VLS_ctx.getTypeSeverity(__VLS_ctx.selectedAlert.alert_type)) }, { class: "mt-1" })));
    var __VLS_223 = __VLS_222.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.selectedAlert.alert_type), severity: (__VLS_ctx.getTypeSeverity(__VLS_ctx.selectedAlert.alert_type)) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_222), false));
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-semibold text-gray-900 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.selectedAlert.current_stock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.selectedAlert.reorder_point);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.selectedAlert.maximum_stock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-amber-600" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-amber-600']} */ ;
    (__VLS_ctx.selectedAlert.safety_stock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_226 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    Textarea;
    // @ts-ignore
    var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226(__assign({ modelValue: (__VLS_ctx.actionNotes), placeholder: "Add notes about the action taken...", rows: "4" }, { class: "w-full" })));
    var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.actionNotes), placeholder: "Add notes about the action taken...", rows: "4" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_227), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
{
    var __VLS_231 = __VLS_218.slots.footer;
    var __VLS_232 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_233 = __VLS_asFunctionalComponent1(__VLS_232, new __VLS_232(__assign({ 'onClick': {} }, { label: "Cancel", text: true })));
    var __VLS_234 = __VLS_233.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", text: true })], __VLS_functionalComponentArgsRest(__VLS_233), false));
    var __VLS_237 = void 0;
    var __VLS_238 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsDialog = false;
                // @ts-ignore
                [getTypeSeverity, showDetailsDialog, showDetailsDialog, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, selectedAlert, actionNotes,];
            } });
    var __VLS_235;
    var __VLS_236;
    if (((_a = __VLS_ctx.selectedAlert) === null || _a === void 0 ? void 0 : _a.status) === 'active') {
        var __VLS_239 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_240 = __VLS_asFunctionalComponent1(__VLS_239, new __VLS_239(__assign({ 'onClick': {} }, { label: "Acknowledge", severity: "warning" })));
        var __VLS_241 = __VLS_240.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Acknowledge", severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_240), false));
        var __VLS_244 = void 0;
        var __VLS_245 = ({ click: {} },
            { onClick: (__VLS_ctx.confirmAcknowledge) });
        var __VLS_242;
        var __VLS_243;
    }
    if (((_b = __VLS_ctx.selectedAlert) === null || _b === void 0 ? void 0 : _b.status) !== 'resolved') {
        var __VLS_246 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_247 = __VLS_asFunctionalComponent1(__VLS_246, new __VLS_246(__assign({ 'onClick': {} }, { label: "Resolve", severity: "success" })));
        var __VLS_248 = __VLS_247.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Resolve", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_247), false));
        var __VLS_251 = void 0;
        var __VLS_252 = ({ click: {} },
            { onClick: (__VLS_ctx.confirmResolve) });
        var __VLS_249;
        var __VLS_250;
    }
    // @ts-ignore
    [selectedAlert, selectedAlert, confirmAcknowledge, confirmResolve,];
}
// @ts-ignore
[];
var __VLS_218;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
