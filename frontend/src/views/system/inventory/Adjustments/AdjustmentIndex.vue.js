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
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(false);
var adjustments = (0, vue_1.ref)([]);
var pagination = (0, vue_1.reactive)({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
});
var filters = (0, vue_1.reactive)({
    status: null,
    search: '',
    start_date: null
});
var statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Submitted', value: 'submitted' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' }
];
var statusSeverity = function (status) {
    var severities = {
        draft: 'secondary',
        submitted: 'info',
        approved: 'success',
        rejected: 'danger'
    };
    return severities[status] || 'secondary';
};
var formatDate = function (date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
var capitalizeFirstLetter = function (string) {
    if (!string)
        return 'N/A';
    return string.charAt(0).toUpperCase() + string.slice(1);
};
var formatStatus = function (status) {
    if (!status)
        return 'N/A';
    // Replace underscores with spaces and capitalize each word
    return status.split('_')
        .map(function (word) { return word.charAt(0).toUpperCase() + word.slice(1); })
        .join(' ');
};
var loadAdjustments = function () {
    var args_1 = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args_1[_i] = arguments[_i];
    }
    return __awaiter(void 0, __spreadArray([], args_1, true), void 0, function (page) {
        var params, response, paginatedData, error_1;
        var _a, _b, _c, _d;
        if (page === void 0) { page = pagination.current_page; }
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    loading.value = true;
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 3, 4, 5]);
                    params = {
                        page: page,
                        per_page: pagination.per_page
                    };
                    if (filters.status)
                        params.status = filters.status;
                    if (filters.search)
                        params.search = filters.search;
                    if (filters.start_date)
                        params.start_date = filters.start_date.toISOString().split('T')[0];
                    return [4 /*yield*/, axios_1.default.get('/api/inventory/adjustments', { params: params })
                        // Handle Laravel pagination format
                    ];
                case 2:
                    response = _e.sent();
                    // Handle Laravel pagination format
                    if (((_a = response.data) === null || _a === void 0 ? void 0 : _a.success) && ((_b = response.data) === null || _b === void 0 ? void 0 : _b.data)) {
                        paginatedData = response.data.data;
                        // The actual adjustments array is in paginatedData.data
                        adjustments.value = paginatedData.data || [];
                        // Update pagination metadata
                        pagination.current_page = paginatedData.current_page || page;
                        pagination.last_page = paginatedData.last_page || 1;
                        pagination.per_page = paginatedData.per_page || pagination.per_page;
                        pagination.total = paginatedData.total || 0;
                        pagination.from = paginatedData.from || 0;
                        pagination.to = paginatedData.to || 0;
                    }
                    // Handle direct array response (fallback)
                    else if (Array.isArray(response.data)) {
                        adjustments.value = response.data;
                        pagination.total = response.data.length;
                    }
                    // Handle empty response
                    else {
                        adjustments.value = [];
                    }
                    return [3 /*break*/, 5];
                case 3:
                    error_1 = _e.sent();
                    console.error('Failed to load adjustments', error_1);
                    adjustments.value = [];
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: ((_d = (_c = error_1.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) || 'Failed to load adjustments',
                        life: 3000
                    });
                    return [3 /*break*/, 5];
                case 4:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 5: return [2 /*return*/];
            }
        });
    });
};
var onPageChange = function (event) {
    pagination.current_page = event.page + 1;
    pagination.per_page = event.rows;
    loadAdjustments();
};
var submitAdjustment = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/inventory/adjustments/".concat(id, "/submit"))];
            case 1:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Adjustment submitted successfully',
                    life: 2000
                });
                loadAdjustments(pagination.current_page);
                return [3 /*break*/, 3];
            case 2:
                error_2 = _c.sent();
                console.error('Failed to submit adjustment', error_2);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to submit adjustment',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var resetFilters = function () {
    filters.status = null;
    filters.search = '';
    filters.start_date = null;
    loadAdjustments(1);
};
(0, vue_1.onMounted)(function () {
    loadAdjustments();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 bg-gray-50 min-h-screen" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6 flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Create Adjustment", icon: "pi pi-plus", severity: "success" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Adjustment", icon: "pi pi-plus", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'inventory.adjustments.create' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ class: "mb-6" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Statuses", showClear: true })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Statuses", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ change: {} },
        { onChange: (function () { return __VLS_ctx.loadAdjustments(1); }) });
    var __VLS_17;
    var __VLS_18;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = __VLS_24.slots.default;
    var __VLS_27 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_28 = __VLS_asFunctionalComponent1(__VLS_27, new __VLS_27(__assign({ class: "pi pi-search" })));
    var __VLS_29 = __VLS_28.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_28), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search reference no..." })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onKeyup': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search reference no..." })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ keyup: {} },
        { onKeyup: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.loadAdjustments(1);
                // @ts-ignore
                [filters, filters, statusOptions, loadAdjustments, loadAdjustments,];
            } });
    var __VLS_35;
    var __VLS_36;
    // @ts-ignore
    [];
    var __VLS_24;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Calendar} */
    Calendar;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.filters.start_date), dateFormat: "yy-mm-dd", placeholder: "From Date" })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.filters.start_date), dateFormat: "yy-mm-dd", placeholder: "From Date" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ dateSelect: {} },
        { onDateSelect: (function () { return __VLS_ctx.loadAdjustments(1); }) });
    var __VLS_42;
    var __VLS_43;
    var __VLS_46 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset" })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    var __VLS_51 = void 0;
    var __VLS_52 = ({ click: {} },
        { onClick: (__VLS_ctx.resetFilters) });
    var __VLS_49;
    var __VLS_50;
    // @ts-ignore
    [filters, loadAdjustments, resetFilters,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_53;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({}));
var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_54), false));
var __VLS_58 = __VLS_56.slots.default;
{
    var __VLS_59 = __VLS_56.slots.content;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.adjustments), loading: (__VLS_ctx.loading), paginator: true, rows: (__VLS_ctx.pagination.per_page), totalRecords: (__VLS_ctx.pagination.total), first: ((__VLS_ctx.pagination.current_page - 1) * __VLS_ctx.pagination.per_page), dataKey: "id" }), { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.adjustments), loading: (__VLS_ctx.loading), paginator: true, rows: (__VLS_ctx.pagination.per_page), totalRecords: (__VLS_ctx.pagination.total), first: ((__VLS_ctx.pagination.current_page - 1) * __VLS_ctx.pagination.per_page), dataKey: "id" }), { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ page: {} },
        { onPage: (__VLS_ctx.onPageChange) });
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_67 = __VLS_63.slots.default;
    {
        var __VLS_68 = __VLS_63.slots.empty;
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
        [adjustments, loading, pagination, pagination, pagination, pagination, onPageChange,];
    }
    var __VLS_69 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ field: "reference_no", header: "Reference No." }, { style: {} })));
    var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ field: "reference_no", header: "Reference No." }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_70), false));
    var __VLS_74 = __VLS_72.slots.default;
    {
        var __VLS_75 = __VLS_72.slots.body;
        var data = __VLS_vSlot(__VLS_75)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.adjustment_number);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_72;
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ field: "reason", header: "Reason" }, { style: {} })));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ field: "reason", header: "Reason" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_77), false));
    var __VLS_81 = __VLS_79.slots.default;
    {
        var __VLS_82 = __VLS_79.slots.body;
        var data = __VLS_vSlot(__VLS_82)[0].data;
        (__VLS_ctx.capitalizeFirstLetter(data.reason || 'N/A'));
        // @ts-ignore
        [capitalizeFirstLetter,];
    }
    // @ts-ignore
    [];
    var __VLS_79;
    var __VLS_83 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign({ field: "adjustment_date", header: "Date" }, { style: {} })));
    var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign({ field: "adjustment_date", header: "Date" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_84), false));
    var __VLS_88 = __VLS_86.slots.default;
    {
        var __VLS_89 = __VLS_86.slots.body;
        var data = __VLS_vSlot(__VLS_89)[0].data;
        (__VLS_ctx.formatDate(data.adjustment_date));
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_86;
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign({ field: "status", header: "Status" }, { style: {} })));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_91), false));
    var __VLS_95 = __VLS_93.slots.default;
    {
        var __VLS_96 = __VLS_93.slots.body;
        var data = __VLS_vSlot(__VLS_96)[0].data;
        var __VLS_97 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({
            value: (__VLS_ctx.formatStatus(data.status)),
            severity: (__VLS_ctx.statusSeverity(data.status)),
        }));
        var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.formatStatus(data.status)),
                severity: (__VLS_ctx.statusSeverity(data.status)),
            }], __VLS_functionalComponentArgsRest(__VLS_98), false));
        // @ts-ignore
        [formatStatus, statusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_93;
    var __VLS_102 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_103), false));
    var __VLS_107 = __VLS_105.slots.default;
    {
        var __VLS_108 = __VLS_105.slots.body;
        var data_1 = __VLS_vSlot(__VLS_108)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_109 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, severity: "info" })));
        var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_110), false));
        var __VLS_114 = void 0;
        var __VLS_115 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.router.push({ name: 'inventory.adjustments.detail', params: { id: data_1.id } });
                    // @ts-ignore
                    [router,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View details') }), null, null);
        var __VLS_112;
        var __VLS_113;
        if (data_1.status === 'draft') {
            var __VLS_116 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, severity: "warning" })));
            var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_117), false));
            var __VLS_121 = void 0;
            var __VLS_122 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status === 'draft'))
                            return;
                        __VLS_ctx.router.push({ name: 'inventory.adjustments.edit', params: { id: data_1.id } });
                        // @ts-ignore
                        [router, vTooltip,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Edit') }), null, null);
            var __VLS_119;
            var __VLS_120;
        }
        if (data_1.status === 'draft') {
            var __VLS_123 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", text: true, severity: "success" })));
            var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", text: true, severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_124), false));
            var __VLS_128 = void 0;
            var __VLS_129 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(data_1.status === 'draft'))
                            return;
                        __VLS_ctx.submitAdjustment(data_1.id);
                        // @ts-ignore
                        [vTooltip, submitAdjustment,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Submit') }), null, null);
            var __VLS_126;
            var __VLS_127;
        }
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_105;
    // @ts-ignore
    [];
    var __VLS_63;
    var __VLS_64;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_56;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
