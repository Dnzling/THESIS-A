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
var _a, _b, _c, _d, _e, _f, _g, _h;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var inventory_service_1 = require("../../../../services/inventory.service");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(false);
var processing = (0, vue_1.ref)(false);
var detail = (0, vue_1.ref)(null);
var adjustmentId = (0, vue_1.computed)(function () { return Number(route.params.id); });
// Check if user can approve/reject (pending_approval status)
var canAction = (0, vue_1.computed)(function () {
    var _a;
    return ((_a = detail.value) === null || _a === void 0 ? void 0 : _a.status) === 'pending_approval';
});
// Calculate totals
var totalDifference = (0, vue_1.computed)(function () {
    var _a;
    if (!((_a = detail.value) === null || _a === void 0 ? void 0 : _a.items))
        return 0;
    return detail.value.items.reduce(function (sum, item) { return sum + (item.difference || 0); }, 0);
});
var totalValueDifference = (0, vue_1.computed)(function () {
    var _a;
    if (!((_a = detail.value) === null || _a === void 0 ? void 0 : _a.items))
        return 0;
    return detail.value.items.reduce(function (sum, item) { return sum + (Number(item.value_difference) || 0); }, 0);
});
// Helper functions
var formatDate = function (date) {
    if (!date)
        return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
var formatStatus = function (status) {
    if (!status)
        return 'N/A';
    var statusMap = {
        'draft': 'Draft',
        'pending_approval': 'Pending Approval',
        'approved': 'Approved',
        'applied': 'Applied',
        'rejected': 'Rejected',
        'cancelled': 'Cancelled'
    };
    return statusMap[status] || status.split('_')
        .map(function (word) { return word.charAt(0).toUpperCase() + word.slice(1); })
        .join(' ');
};
var formatType = function (type) {
    if (!type)
        return '-';
    var typeMap = {
        'physical_count': 'Physical Count',
        'cycle_count': 'Cycle Count',
        'spot_check': 'Spot Check',
        'damage': 'Damage',
        'loss': 'Loss',
        'found': 'Found',
        'correction': 'Correction',
        'writeoff': 'Write Off'
    };
    return typeMap[type] || type.split('_')
        .map(function (word) { return word.charAt(0).toUpperCase() + word.slice(1); })
        .join(' ');
};
var formatReason = function (reason) {
    if (!reason)
        return '-';
    var reasonMap = {
        'physical_count': 'Physical Count Correction',
        'damaged': 'Damaged Goods',
        'expired': 'Expired Items',
        'theft': 'Theft/Loss',
        'wrong_delivery': 'Wrong Delivery',
        'quality_control': 'Quality Control',
        'sample': 'Sample/Demo Usage',
        'other': 'Other'
    };
    return reasonMap[reason] || reason.split('_')
        .map(function (word) { return word.charAt(0).toUpperCase() + word.slice(1); })
        .join(' ');
};
var getCreatedByName = function (createdBy) {
    if (!createdBy)
        return '-';
    return "".concat(createdBy.fname || '', " ").concat(createdBy.lname || '').trim() || "Employee #".concat(createdBy.id);
};
var getApprovedByName = function (approvedBy) {
    if (!approvedBy)
        return '-';
    return "".concat(approvedBy.fname || '', " ").concat(approvedBy.lname || '').trim() || "Employee #".concat(approvedBy.id);
};
var statusSeverity = function (status) {
    var severities = {
        'draft': 'secondary',
        'pending_approval': 'warning',
        'approved': 'info',
        'applied': 'success',
        'rejected': 'danger',
        'cancelled': 'danger'
    };
    return severities[status] || 'secondary';
};
// API calls
var loadDetail = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                loading.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getAdjustment(adjustmentId.value)
                    // Handle nested response structure
                ];
            case 2:
                response = _d.sent();
                // Handle nested response structure
                detail.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || response.data || null;
                if (!detail.value) {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'Adjustment not found',
                        life: 3000
                    });
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _d.sent();
                console.error('Failed to load adjustment detail', error_1);
                detail.value = null;
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to load adjustment details',
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
(0, vue_1.onMounted)(function () {
    loadDetail();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'inventory.adjustments' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Tag} */
Tag;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
    value: (__VLS_ctx.formatStatus((_a = __VLS_ctx.detail) === null || _a === void 0 ? void 0 : _a.status)),
    severity: (__VLS_ctx.statusSeverity((_b = __VLS_ctx.detail) === null || _b === void 0 ? void 0 : _b.status)),
}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.formatStatus((_c = __VLS_ctx.detail) === null || _c === void 0 ? void 0 : _c.status)),
        severity: (__VLS_ctx.statusSeverity((_d = __VLS_ctx.detail) === null || _d === void 0 ? void 0 : _d.status)),
    }], __VLS_functionalComponentArgsRest(__VLS_8), false));
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    Skeleton;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ height: "180px" }, { class: "rounded-lg" })));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ height: "180px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_17 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    Skeleton;
    // @ts-ignore
    var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ height: "250px" }, { class: "rounded-lg" })));
    var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ height: "250px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else if (__VLS_ctx.detail) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_22 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_23 = __VLS_asFunctionalComponent1(__VLS_22, new __VLS_22({}));
    var __VLS_24 = __VLS_23.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_23), false));
    var __VLS_27 = __VLS_25.slots.default;
    {
        var __VLS_28 = __VLS_25.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.detail.adjustment_number || '-');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatDate(__VLS_ctx.detail.adjustment_date));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatType(__VLS_ctx.detail.type));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_e = __VLS_ctx.detail.branch) === null || _e === void 0 ? void 0 : _e.name) || '-');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatReason(__VLS_ctx.detail.reason));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.getCreatedByName(__VLS_ctx.detail.created_by));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatDate(__VLS_ctx.detail.created_at));
        if (__VLS_ctx.detail.approved_by) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (__VLS_ctx.getApprovedByName(__VLS_ctx.detail.approved_by));
        }
        // @ts-ignore
        [formatStatus, detail, detail, detail, detail, detail, detail, detail, detail, detail, detail, detail, detail, statusSeverity, loading, formatDate, formatDate, formatType, formatReason, getCreatedByName, getApprovedByName,];
    }
    // @ts-ignore
    [];
    var __VLS_25;
    var __VLS_29 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29({}));
    var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_30), false));
    var __VLS_34 = __VLS_32.slots.default;
    {
        var __VLS_35 = __VLS_32.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-list text-emerald-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-list']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-emerald-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_36 = __VLS_32.slots.content;
        var __VLS_37 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign(__assign({ value: (__VLS_ctx.detail.items || []) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })));
        var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.detail.items || []) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })], __VLS_functionalComponentArgsRest(__VLS_38), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_42 = __VLS_40.slots.default;
        var __VLS_43 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43(__assign({ header: "Product" }, { style: {} })));
        var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([__assign({ header: "Product" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_44), false));
        var __VLS_48 = __VLS_46.slots.default;
        {
            var __VLS_49 = __VLS_46.slots.body;
            var data = __VLS_vSlot(__VLS_49)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (((_f = data.product) === null || _f === void 0 ? void 0 : _f.product_name) || 'Unknown');
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (((_g = data.product) === null || _g === void 0 ? void 0 : _g.sku) || 'N/A');
            // @ts-ignore
            [detail,];
        }
        // @ts-ignore
        [];
        var __VLS_46;
        var __VLS_50 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50(__assign({ header: "System Qty" }, { style: {} })));
        var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([__assign({ header: "System Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_51), false));
        var __VLS_55 = __VLS_53.slots.default;
        {
            var __VLS_56 = __VLS_53.slots.body;
            var data = __VLS_vSlot(__VLS_56)[0].data;
            (data.system_quantity);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_53;
        var __VLS_57 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_58 = __VLS_asFunctionalComponent1(__VLS_57, new __VLS_57(__assign({ header: "Actual Qty" }, { style: {} })));
        var __VLS_59 = __VLS_58.apply(void 0, __spreadArray([__assign({ header: "Actual Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_58), false));
        var __VLS_62 = __VLS_60.slots.default;
        {
            var __VLS_63 = __VLS_60.slots.body;
            var data = __VLS_vSlot(__VLS_63)[0].data;
            (data.actual_quantity);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_60;
        var __VLS_64 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64(__assign({ header: "Difference" }, { style: {} })));
        var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([__assign({ header: "Difference" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_65), false));
        var __VLS_69 = __VLS_67.slots.default;
        {
            var __VLS_70 = __VLS_67.slots.body;
            var data = __VLS_vSlot(__VLS_70)[0].data;
            var __VLS_71 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
                value: (data.difference > 0 ? '+' + data.difference : data.difference),
                severity: (data.difference > 0 ? 'success' : data.difference < 0 ? 'danger' : 'secondary'),
            }));
            var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
                    value: (data.difference > 0 ? '+' + data.difference : data.difference),
                    severity: (data.difference > 0 ? 'success' : data.difference < 0 ? 'danger' : 'secondary'),
                }], __VLS_functionalComponentArgsRest(__VLS_72), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_67;
        var __VLS_76 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ header: "Unit Cost" }, { style: {} })));
        var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ header: "Unit Cost" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_77), false));
        var __VLS_81 = __VLS_79.slots.default;
        {
            var __VLS_82 = __VLS_79.slots.body;
            var data = __VLS_vSlot(__VLS_82)[0].data;
            (Number(data.unit_cost).toFixed(2));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_79;
        var __VLS_83 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign({ header: "Value Diff" }, { style: {} })));
        var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign({ header: "Value Diff" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_84), false));
        var __VLS_88 = __VLS_86.slots.default;
        {
            var __VLS_89 = __VLS_86.slots.body;
            var data = __VLS_vSlot(__VLS_89)[0].data;
            (Number(data.value_difference).toFixed(2));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_86;
        var __VLS_90 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign({ field: "notes", header: "Notes" }, { style: {} })));
        var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign({ field: "notes", header: "Notes" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_91), false));
        var __VLS_95 = __VLS_93.slots.default;
        {
            var __VLS_96 = __VLS_93.slots.body;
            var data = __VLS_vSlot(__VLS_96)[0].data;
            (data.notes || '-');
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_93;
        // @ts-ignore
        [];
        var __VLS_40;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4 bg-gray-50 p-4 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xl font-bold" }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (((_h = __VLS_ctx.detail.items) === null || _h === void 0 ? void 0 : _h.length) || 0);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xl font-bold" }, { class: (__VLS_ctx.totalDifference > 0 ? 'text-green-600' : __VLS_ctx.totalDifference < 0 ? 'text-red-600' : '') }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.totalDifference > 0 ? '+' : '');
        (__VLS_ctx.totalDifference);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xl font-bold" }, { class: (__VLS_ctx.totalValueDifference > 0 ? 'text-green-600' : __VLS_ctx.totalValueDifference < 0 ? 'text-red-600' : '') }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (Number(__VLS_ctx.totalValueDifference).toFixed(2));
        // @ts-ignore
        [detail, totalDifference, totalDifference, totalDifference, totalDifference, totalValueDifference, totalValueDifference, totalValueDifference,];
    }
    // @ts-ignore
    [];
    var __VLS_32;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    var __VLS_97 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign(__assign({ 'onClick': {} }, { label: "Back to List", icon: "pi pi-arrow-left" }), { class: "mt-4" })));
    var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Back to List", icon: "pi pi-arrow-left" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_98), false));
    var __VLS_102 = void 0;
    var __VLS_103 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                if (!!(__VLS_ctx.detail))
                    return;
                __VLS_ctx.router.push({ name: 'inventory.adjustments' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    var __VLS_100;
    var __VLS_101;
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
