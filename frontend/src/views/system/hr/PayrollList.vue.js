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
var _a, _b, _c;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var vue_router_1 = require("vue-router");
var GeneratePayrollDialog_vue_1 = require("../../../components/dialogs/GeneratePayrollDialog.vue");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
var lodash_1 = require("lodash");
// ==================== STATE ====================
var toast = (0, usetoast_1.useToast)();
var router = (0, vue_router_1.useRouter)();
var loading = (0, vue_1.ref)(false);
var loadingEmployees = (0, vue_1.ref)(false);
var showDeleteModal = (0, vue_1.ref)(false);
var showViewModal = (0, vue_1.ref)(false);
var batchToDelete = (0, vue_1.ref)(null);
var selectedBatch = (0, vue_1.ref)(null);
var showGenerateModal = (0, vue_1.ref)(false);
var isGenerating = (0, vue_1.ref)(false);
var isDeleting = (0, vue_1.ref)(false);
var authStore = (0, auth_1.useAuthStore)();
var payPeriods = (0, vue_1.ref)([]);
var statistics = (0, vue_1.ref)({
    totalPeriods: 0,
    totalPayroll: 0,
    averagePeriodPayroll: 0,
    periodsByStatus: {}
});
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    status: null,
    dateRange: null
});
// Options
var statusOptions = (0, vue_1.ref)(['draft', 'processing', 'locked', 'completed']);
// ==================== COMPUTED ====================
var filteredBatches = (0, vue_1.computed)(function () {
    var filtered = __spreadArray([], payPeriods.value, true);
    if (filters.value.search) {
        var searchLower_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (p) {
            return p.name.toLowerCase().includes(searchLower_1);
        });
    }
    if (filters.value.status) {
        filtered = filtered.filter(function (p) { return p.status === filters.value.status; });
    }
    if (filters.value.dateRange && filters.value.dateRange[0] && filters.value.dateRange[1]) {
        var start_1 = filters.value.dateRange[0];
        var end_1 = filters.value.dateRange[1];
        filtered = filtered.filter(function (p) {
            return new Date(p.start_date) >= start_1 && new Date(p.end_date) <= end_1;
        });
    }
    return filtered;
});
var stats = (0, vue_1.computed)(function () { return ({
    totalPeriods: payPeriods.value.length,
    totalPayroll: payPeriods.value.reduce(function (sum, p) { return sum + p.total_net_worth; }, 0)
}); });
// ==================== METHODS ====================
var fetchPayPeriods = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                params = {};
                if (filters.value.status) {
                    params.status = filters.value.status;
                }
                if (filters.value.dateRange && filters.value.dateRange[0]) {
                    params.year = filters.value.dateRange[0].getFullYear();
                    params.month = filters.value.dateRange[0].getMonth() + 1;
                }
                return [4 /*yield*/, axios_1.default.get('/api/payroll/pay-periods', { params: params })];
            case 2:
                response = _a.sent();
                if (response.data.success) {
                    payPeriods.value = response.data.data;
                    statistics.value = response.data.statistics;
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to fetch pay periods',
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
var fetchPeriodDetails = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingEmployees.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get("/api/payroll/pay-periods/".concat(id, "/payroll"), {
                        params: {
                            include_department_breakdown: true,
                            include_status_breakdown: true
                        }
                    })];
            case 2:
                response = _a.sent();
                if (response.data.success) {
                    selectedBatch.value = response.data.data.period;
                    selectedBatch.value.employees = response.data.data.employees;
                }
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to fetch period details',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                loadingEmployees.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var debouncedFetch = (0, lodash_1.debounce)(function () {
    fetchPayPeriods();
}, 300);
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    }).format(new Date(date));
};
var formatDateTime = function (date) {
    if (!date)
        return 'N/A';
    return new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
};
var formatDateRange = function (start, end) {
    return "".concat(formatDate(start), " - ").concat(formatDate(end));
};
var formatCurrency = function (amount) {
    if (!amount)
        return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
};
var getStatusSeverity = function (status) {
    var map = {
        'draft': 'secondary',
        'processing': 'info',
        'locked': 'warn',
        'completed': 'success',
        'approved': 'success',
        'paid': 'success',
        'cancelled': 'danger'
    };
    return map[status] || 'info';
};
var getEmployeeCountSeverity = function (period) {
    if (period.employees_count === 0)
        return 'danger';
    if (period.employees_count < 10)
        return 'warn';
    if (period.employees_count < 30)
        return 'info';
    return 'success';
};
var getPeriodType = function (period) {
    var start = new Date(period.start_date);
    var end = new Date(period.end_date);
    var days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    if (days <= 15)
        return 'Semi-monthly';
    if (days <= 31)
        return 'Monthly';
    return 'Custom Period';
};
var getDaysUntilPay = function (period) {
    var today = new Date();
    var payDate = new Date(period.pay_date);
    var days = Math.ceil((payDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    if (days < 0)
        return 'Past due';
    if (days === 0)
        return 'Today';
    if (days === 1)
        return 'Tomorrow';
    return "".concat(days, " days until pay");
};
var getPayDateWarningClass = function (period) {
    var today = new Date();
    var payDate = new Date(period.pay_date);
    var days = Math.ceil((payDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    if (days < 0)
        return 'text-red-500';
    if (days <= 3)
        return 'text-yellow-500 font-medium';
    return 'text-gray-500';
};
var getProcessingProgress = function (period) {
    // Mock progress based on dates
    var start = new Date(period.start_date);
    var end = new Date(period.end_date);
    var today = new Date();
    if (today < start)
        return 0;
    if (today > end)
        return 100;
    var total = end.getTime() - start.getTime();
    var passed = today.getTime() - start.getTime();
    return Math.round((passed / total) * 100);
};
var handleSort = function (event) {
    // Handle sort if needed
    console.log('Sort:', event);
};
var viewBatch = function (batch) { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        router.push({
            name: 'hr.payroll.view',
            params: { id: batch.id.toString() }
        });
        return [2 /*return*/];
    });
}); };
var editBatch = function (batch) {
    router.push({
        name: 'hr.payroll.edit',
        params: { id: batch.id.toString() }
    });
};
var exportBatch = function (batch) { return __awaiter(void 0, void 0, void 0, function () {
    var response, url, link, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get("/api/payroll/pay-periods/".concat(batch.id, "/export"), {
                        responseType: 'blob'
                    })
                    // Create download link
                ];
            case 1:
                response = _a.sent();
                url = window.URL.createObjectURL(new Blob([response.data]));
                link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', "payroll_".concat(batch.name.replace(/\s+/g, '_'), ".csv"));
                document.body.appendChild(link);
                link.click();
                link.remove();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Payroll exported successfully',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to export payroll',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var confirmDeleteBatch = function (batch) {
    batchToDelete.value = batch;
    showDeleteModal.value = true;
};
var deleteBatch = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!batchToDelete.value)
                    return [2 /*return*/];
                isDeleting.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.delete("api/payroll/pay-periods/".concat(batchToDelete.value.id))];
            case 2:
                _a.sent();
                payPeriods.value = payPeriods.value.filter(function (p) { var _a; return p.id !== ((_a = batchToDelete.value) === null || _a === void 0 ? void 0 : _a.id); });
                toast.add({
                    severity: 'success',
                    summary: 'Deleted',
                    detail: "Period \"".concat(batchToDelete.value.name, "\" has been deleted"),
                    life: 3000
                });
                showDeleteModal.value = false;
                batchToDelete.value = null;
                return [3 /*break*/, 5];
            case 3:
                error_4 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete pay period',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                isDeleting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var handlePayrollGenerated = function (newBatch) {
    isGenerating.value = false;
    showGenerateModal.value = false;
    fetchPayPeriods();
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: "Payroll batch generated successfully",
        life: 3000
    });
};
// ==================== WATCHERS ====================
(0, vue_1.watch)(function () { return filters.value.status; }, function () {
    fetchPayPeriods();
});
// ==================== LIFECYCLE ====================
(0, vue_1.onMounted)(function () {
    // Set axios default headers
    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
    fetchPayPeriods();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "payroll-batches p-4" }));
/** @type {__VLS_StyleScopedClasses['payroll-batches']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 mb-4 flex-wrap text-sm" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({
    iconPosition: "left",
}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{
        iconPosition: "left",
    }], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5 = __VLS_3.slots.default;
var __VLS_6;
/** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_7 = __VLS_asFunctionalComponent1(__VLS_6, new __VLS_6({}));
var __VLS_8 = __VLS_7.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_7), false));
var __VLS_11 = __VLS_9.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
var __VLS_9;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search period" })));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search period" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
var __VLS_17;
var __VLS_18 = ({ input: {} },
    { onInput: (__VLS_ctx.debouncedFetch) });
var __VLS_15;
var __VLS_16;
// @ts-ignore
[filters, debouncedFetch,];
var __VLS_3;
var __VLS_19;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true })));
var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_20), false));
var __VLS_24;
var __VLS_25 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchPayPeriods) });
var __VLS_22;
var __VLS_23;
var __VLS_26;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ 'onUpdate:modelValue': {} }, { modelValue: (__VLS_ctx.filters.dateRange), showIcon: true, showClear: true, selectionMode: "range", placeholder: "Date Range" })));
var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ 'onUpdate:modelValue': {} }, { modelValue: (__VLS_ctx.filters.dateRange), showIcon: true, showClear: true, selectionMode: "range", placeholder: "Date Range" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
var __VLS_31;
var __VLS_32 = ({ 'update:modelValue': {} },
    { 'onUpdate:modelValue': (__VLS_ctx.fetchPayPeriods) });
var __VLS_29;
var __VLS_30;
var __VLS_33;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "info" }), { class: "ml-auto" })));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "info" }), { class: "ml-auto" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
var __VLS_38;
var __VLS_39 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showGenerateModal = true;
            // @ts-ignore
            [filters, filters, statusOptions, fetchPayPeriods, fetchPayPeriods, showGenerateModal,];
        } });
/** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
var __VLS_36;
var __VLS_37;
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign(__assign(__assign({ 'onSort': {} }, { value: (__VLS_ctx.filteredBatches) }), { class: "w-full text-sm" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowHover: true, rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} periods", showGridlines: true, removableSort: true, responsiveLayout: "scroll", sortField: "periodName", sortOrder: (1), tableStyle: "min-width: 50rem" })));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onSort': {} }, { value: (__VLS_ctx.filteredBatches) }), { class: "w-full text-sm" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowHover: true, rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} periods", showGridlines: true, removableSort: true, responsiveLayout: "scroll", sortField: "periodName", sortOrder: (1), tableStyle: "min-width: 50rem" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
var __VLS_45;
var __VLS_46 = ({ sort: {} },
    { onSort: (__VLS_ctx.handleSort) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_47 = __VLS_43.slots.default;
var __VLS_48;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48(__assign(__assign({ field: "name", header: "Period" }, { class: "w-40" }), { sortable: true })));
var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([__assign(__assign({ field: "name", header: "Period" }, { class: "w-40" }), { sortable: true })], __VLS_functionalComponentArgsRest(__VLS_49), false));
/** @type {__VLS_StyleScopedClasses['w-40']} */ ;
var __VLS_53 = __VLS_51.slots.default;
{
    var __VLS_54 = __VLS_51.slots.body;
    var data = __VLS_vSlot(__VLS_54)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.getPeriodType(data));
    // @ts-ignore
    [filteredBatches, loading, handleSort, getPeriodType,];
}
// @ts-ignore
[];
var __VLS_51;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
    field: "start_date",
    header: "Start Date",
    sortable: true,
}));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
        field: "start_date",
        header: "Start Date",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_56), false));
var __VLS_60 = __VLS_58.slots.default;
{
    var __VLS_61 = __VLS_58.slots.body;
    var data = __VLS_vSlot(__VLS_61)[0].data;
    (__VLS_ctx.formatDate(data.start_date));
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_58;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62({
    field: "end_date",
    header: "End Date",
    sortable: true,
}));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([{
        field: "end_date",
        header: "End Date",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67 = __VLS_65.slots.default;
{
    var __VLS_68 = __VLS_65.slots.body;
    var data = __VLS_vSlot(__VLS_68)[0].data;
    (__VLS_ctx.formatDate(data.end_date));
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_65;
var __VLS_69;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69({
    field: "pay_date",
    header: "Pay Date",
    sortable: true,
}));
var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([{
        field: "pay_date",
        header: "Pay Date",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_70), false));
var __VLS_74 = __VLS_72.slots.default;
{
    var __VLS_75 = __VLS_72.slots.body;
    var data = __VLS_vSlot(__VLS_75)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatDate(data.pay_date));
    if (data.status !== 'completed') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: (__VLS_ctx.getPayDateWarningClass(data)) }));
        (__VLS_ctx.getDaysUntilPay(data));
    }
    // @ts-ignore
    [formatDate, getPayDateWarningClass, getDaysUntilPay,];
}
// @ts-ignore
[];
var __VLS_72;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({
    field: "created_by",
    header: "Created By",
    sortable: true,
}));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{
        field: "created_by",
        header: "Created By",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_77), false));
var __VLS_81 = __VLS_79.slots.default;
{
    var __VLS_82 = __VLS_79.slots.body;
    var data = __VLS_vSlot(__VLS_82)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.created_by);
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatDateTime(data.created_at));
    // @ts-ignore
    [formatDateTime,];
}
// @ts-ignore
[];
var __VLS_79;
var __VLS_83;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
    field: "employees_count",
    header: "Employees",
    sortable: true,
}));
var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
        field: "employees_count",
        header: "Employees",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_84), false));
var __VLS_88 = __VLS_86.slots.default;
{
    var __VLS_89 = __VLS_86.slots.body;
    var data = __VLS_vSlot(__VLS_89)[0].data;
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90({
        value: (data.employees_count),
        severity: (__VLS_ctx.getEmployeeCountSeverity(data)),
    }));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([{
            value: (data.employees_count),
            severity: (__VLS_ctx.getEmployeeCountSeverity(data)),
        }], __VLS_functionalComponentArgsRest(__VLS_91), false));
    if (data.status === 'processing') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    }
    // @ts-ignore
    [getEmployeeCountSeverity,];
}
// @ts-ignore
[];
var __VLS_86;
var __VLS_95;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95({
    field: "total_net_worth",
    header: "Net Payroll",
    sortable: true,
}));
var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([{
        field: "total_net_worth",
        header: "Net Payroll",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_96), false));
var __VLS_100 = __VLS_98.slots.default;
{
    var __VLS_101 = __VLS_98.slots.body;
    var data = __VLS_vSlot(__VLS_101)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatCurrency(data.total_net_worth));
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatCurrency(data.total_gross_worth));
    // @ts-ignore
    [formatCurrency, formatCurrency,];
}
// @ts-ignore
[];
var __VLS_98;
var __VLS_102;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({
    field: "status",
    header: "Status",
    sortable: true,
}));
var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{
        field: "status",
        header: "Status",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_103), false));
var __VLS_107 = __VLS_105.slots.default;
{
    var __VLS_108 = __VLS_105.slots.body;
    var data = __VLS_vSlot(__VLS_108)[0].data;
    var __VLS_109 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ severity: (__VLS_ctx.getStatusSeverity(data.status)), value: (data.status) }, { class: "capitalize" })));
    var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ severity: (__VLS_ctx.getStatusSeverity(data.status)), value: (data.status) }, { class: "capitalize" })], __VLS_functionalComponentArgsRest(__VLS_110), false));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    // @ts-ignore
    [getStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_105;
var __VLS_114;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ header: "Actions" }, { style: {} })));
var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_115), false));
var __VLS_119 = __VLS_117.slots.default;
{
    var __VLS_120 = __VLS_117.slots.body;
    var data_1 = __VLS_vSlot(__VLS_120)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_121 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true })));
    var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true })], __VLS_functionalComponentArgsRest(__VLS_122), false));
    var __VLS_126 = void 0;
    var __VLS_127 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewBatch(data_1);
                // @ts-ignore
                [viewBatch,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View batch details') }), null, null);
    var __VLS_124;
    var __VLS_125;
    var __VLS_128 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "success", text: true, disabled: (data_1.status === 'completed' || data_1.status === 'locked') })));
    var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "success", text: true, disabled: (data_1.status === 'completed' || data_1.status === 'locked') })], __VLS_functionalComponentArgsRest(__VLS_129), false));
    var __VLS_133 = void 0;
    var __VLS_134 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editBatch(data_1);
                // @ts-ignore
                [vTooltip, editBatch,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit batch') }), null, null);
    var __VLS_131;
    var __VLS_132;
    var __VLS_135 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135(__assign({ 'onClick': {} }, { icon: "pi pi-file-export", severity: "warning", text: true, disabled: (data_1.employees_count === 0) })));
    var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-file-export", severity: "warning", text: true, disabled: (data_1.employees_count === 0) })], __VLS_functionalComponentArgsRest(__VLS_136), false));
    var __VLS_140 = void 0;
    var __VLS_141 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.exportBatch(data_1);
                // @ts-ignore
                [vTooltip, exportBatch,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Export CSV') }), null, null);
    var __VLS_138;
    var __VLS_139;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_117;
{
    var __VLS_142 = __VLS_43.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-4xl mb-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    var __VLS_143 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign(__assign(__assign({ 'onClick': {} }, { label: "Create First Period", icon: "pi pi-plus" }), { class: "mt-4" }), { text: true })));
    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: "Create First Period", icon: "pi pi-plus" }), { class: "mt-4" }), { text: true })], __VLS_functionalComponentArgsRest(__VLS_144), false));
    var __VLS_148 = void 0;
    var __VLS_149 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showGenerateModal = true;
                // @ts-ignore
                [showGenerateModal,];
            } });
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    var __VLS_146;
    var __VLS_147;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_43;
var __VLS_44;
var __VLS_150;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign(__assign({ visible: (__VLS_ctx.showDeleteModal), header: "Confirm Delete" }, { style: ({ width: '400px' }) }), { modal: true })));
var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showDeleteModal), header: "Confirm Delete" }, { style: ({ width: '400px' }) }), { modal: true })], __VLS_functionalComponentArgsRest(__VLS_151), false));
var __VLS_155 = __VLS_153.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-yellow-500 mb-3" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
((_a = __VLS_ctx.batchToDelete) === null || _a === void 0 ? void 0 : _a.name);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.formatDate((_b = __VLS_ctx.batchToDelete) === null || _b === void 0 ? void 0 : _b.start_date));
(__VLS_ctx.formatDate((_c = __VLS_ctx.batchToDelete) === null || _c === void 0 ? void 0 : _c.end_date));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
{
    var __VLS_156 = __VLS_153.slots.footer;
    var __VLS_157 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true, disabled: (__VLS_ctx.isDeleting) })));
    var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true, disabled: (__VLS_ctx.isDeleting) })], __VLS_functionalComponentArgsRest(__VLS_158), false));
    var __VLS_162 = void 0;
    var __VLS_163 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDeleteModal = false;
                // @ts-ignore
                [formatDate, formatDate, showDeleteModal, showDeleteModal, batchToDelete, batchToDelete, batchToDelete, isDeleting,];
            } });
    var __VLS_160;
    var __VLS_161;
    var __VLS_164 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164(__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.isDeleting) })));
    var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.isDeleting) })], __VLS_functionalComponentArgsRest(__VLS_165), false));
    var __VLS_169 = void 0;
    var __VLS_170 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteBatch) });
    var __VLS_167;
    var __VLS_168;
    // @ts-ignore
    [isDeleting, deleteBatch,];
}
// @ts-ignore
[];
var __VLS_153;
var __VLS_171;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_172 = __VLS_asFunctionalComponent1(__VLS_171, new __VLS_171(__assign(__assign({ visible: (__VLS_ctx.showViewModal), header: "Pay Period Details" }, { style: ({ width: '800px' }) }), { modal: true, maximizable: true })));
var __VLS_173 = __VLS_172.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showViewModal), header: "Pay Period Details" }, { style: ({ width: '800px' }) }), { modal: true, maximizable: true })], __VLS_functionalComponentArgsRest(__VLS_172), false));
var __VLS_176 = __VLS_174.slots.default;
if (__VLS_ctx.selectedBatch) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_177 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177({}));
    var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_178), false));
    var __VLS_182 = __VLS_180.slots.default;
    {
        var __VLS_183 = __VLS_180.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDateRange(__VLS_ctx.selectedBatch.start_date, __VLS_ctx.selectedBatch.end_date));
        // @ts-ignore
        [showViewModal, selectedBatch, selectedBatch, selectedBatch, formatDateRange,];
    }
    // @ts-ignore
    [];
    var __VLS_180;
    var __VLS_184 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_185 = __VLS_asFunctionalComponent1(__VLS_184, new __VLS_184({}));
    var __VLS_186 = __VLS_185.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_185), false));
    var __VLS_189 = __VLS_187.slots.default;
    {
        var __VLS_190 = __VLS_187.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(__VLS_ctx.selectedBatch.pay_date));
        // @ts-ignore
        [formatDate, selectedBatch,];
    }
    // @ts-ignore
    [];
    var __VLS_187;
    var __VLS_191 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_192 = __VLS_asFunctionalComponent1(__VLS_191, new __VLS_191({}));
    var __VLS_193 = __VLS_192.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_192), false));
    var __VLS_196 = __VLS_194.slots.default;
    {
        var __VLS_197 = __VLS_194.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedBatch.employees_count);
        // @ts-ignore
        [selectedBatch,];
    }
    // @ts-ignore
    [];
    var __VLS_194;
    var __VLS_198 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198({}));
    var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_199), false));
    var __VLS_203 = __VLS_201.slots.default;
    {
        var __VLS_204 = __VLS_201.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatCurrency(__VLS_ctx.selectedBatch.total_net_worth));
        // @ts-ignore
        [formatCurrency, selectedBatch,];
    }
    // @ts-ignore
    [];
    var __VLS_201;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-medium text-lg mt-4" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    var __VLS_205 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_206 = __VLS_asFunctionalComponent1(__VLS_205, new __VLS_205(__assign(__assign({ value: (__VLS_ctx.selectedBatch.employees || []) }, { class: "w-full" }), { loading: (__VLS_ctx.loadingEmployees) })));
    var __VLS_207 = __VLS_206.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.selectedBatch.employees || []) }, { class: "w-full" }), { loading: (__VLS_ctx.loadingEmployees) })], __VLS_functionalComponentArgsRest(__VLS_206), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_210 = __VLS_208.slots.default;
    var __VLS_211 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_212 = __VLS_asFunctionalComponent1(__VLS_211, new __VLS_211(__assign({ field: "employee_number", header: "ID" }, { style: {} })));
    var __VLS_213 = __VLS_212.apply(void 0, __spreadArray([__assign({ field: "employee_number", header: "ID" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_212), false));
    var __VLS_216 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_217 = __VLS_asFunctionalComponent1(__VLS_216, new __VLS_216({
        field: "name",
        header: "Employee Name",
    }));
    var __VLS_218 = __VLS_217.apply(void 0, __spreadArray([{
            field: "name",
            header: "Employee Name",
        }], __VLS_functionalComponentArgsRest(__VLS_217), false));
    var __VLS_221 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_222 = __VLS_asFunctionalComponent1(__VLS_221, new __VLS_221({
        field: "department",
        header: "Department",
    }));
    var __VLS_223 = __VLS_222.apply(void 0, __spreadArray([{
            field: "department",
            header: "Department",
        }], __VLS_functionalComponentArgsRest(__VLS_222), false));
    var __VLS_226 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226({
        field: "payroll.net_salary",
        header: "Net Pay",
    }));
    var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([{
            field: "payroll.net_salary",
            header: "Net Pay",
        }], __VLS_functionalComponentArgsRest(__VLS_227), false));
    var __VLS_231 = __VLS_229.slots.default;
    {
        var __VLS_232 = __VLS_229.slots.body;
        var data = __VLS_vSlot(__VLS_232)[0].data;
        (__VLS_ctx.formatCurrency(data.payroll.net_salary));
        // @ts-ignore
        [formatCurrency, selectedBatch, loadingEmployees,];
    }
    // @ts-ignore
    [];
    var __VLS_229;
    var __VLS_233 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_234 = __VLS_asFunctionalComponent1(__VLS_233, new __VLS_233({
        field: "payroll.status",
        header: "Status",
    }));
    var __VLS_235 = __VLS_234.apply(void 0, __spreadArray([{
            field: "payroll.status",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_234), false));
    var __VLS_238 = __VLS_236.slots.default;
    {
        var __VLS_239 = __VLS_236.slots.body;
        var data = __VLS_vSlot(__VLS_239)[0].data;
        var __VLS_240 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_241 = __VLS_asFunctionalComponent1(__VLS_240, new __VLS_240({
            severity: (__VLS_ctx.getStatusSeverity(data.payroll.status)),
            value: (data.payroll.status),
            size: "small",
        }));
        var __VLS_242 = __VLS_241.apply(void 0, __spreadArray([{
                severity: (__VLS_ctx.getStatusSeverity(data.payroll.status)),
                value: (data.payroll.status),
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_241), false));
        // @ts-ignore
        [getStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_236;
    // @ts-ignore
    [];
    var __VLS_208;
}
{
    var __VLS_245 = __VLS_174.slots.footer;
    var __VLS_246 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_247 = __VLS_asFunctionalComponent1(__VLS_246, new __VLS_246(__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times" })));
    var __VLS_248 = __VLS_247.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times" })], __VLS_functionalComponentArgsRest(__VLS_247), false));
    var __VLS_251 = void 0;
    var __VLS_252 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showViewModal = false;
                // @ts-ignore
                [showViewModal,];
            } });
    var __VLS_249;
    var __VLS_250;
    var __VLS_253 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_254 = __VLS_asFunctionalComponent1(__VLS_253, new __VLS_253(__assign({ 'onClick': {} }, { label: "Export CSV", icon: "pi pi-file-excel", severity: "success" })));
    var __VLS_255 = __VLS_254.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export CSV", icon: "pi pi-file-excel", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_254), false));
    var __VLS_258 = void 0;
    var __VLS_259 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.exportBatch(__VLS_ctx.selectedBatch);
                // @ts-ignore
                [exportBatch, selectedBatch,];
            } });
    var __VLS_256;
    var __VLS_257;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_174;
var __VLS_260;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_261 = __VLS_asFunctionalComponent1(__VLS_260, new __VLS_260(__assign(__assign({ visible: (__VLS_ctx.showGenerateModal), header: "Generate Payroll" }, { style: ({ width: '600px' }) }), { modal: true, closable: (!__VLS_ctx.isGenerating), closeOnEscape: (!__VLS_ctx.isGenerating), dismissableMask: (!__VLS_ctx.isGenerating) })));
var __VLS_262 = __VLS_261.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showGenerateModal), header: "Generate Payroll" }, { style: ({ width: '600px' }) }), { modal: true, closable: (!__VLS_ctx.isGenerating), closeOnEscape: (!__VLS_ctx.isGenerating), dismissableMask: (!__VLS_ctx.isGenerating) })], __VLS_functionalComponentArgsRest(__VLS_261), false));
var __VLS_265 = __VLS_263.slots.default;
var __VLS_266 = GeneratePayrollDialog_vue_1.default;
// @ts-ignore
var __VLS_267 = __VLS_asFunctionalComponent1(__VLS_266, new __VLS_266(__assign({ 'onClose': {} }, { 'onGenerated': {} })));
var __VLS_268 = __VLS_267.apply(void 0, __spreadArray([__assign({ 'onClose': {} }, { 'onGenerated': {} })], __VLS_functionalComponentArgsRest(__VLS_267), false));
var __VLS_271;
var __VLS_272 = ({ close: {} },
    { onClose: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showGenerateModal = false;
            // @ts-ignore
            [showGenerateModal, showGenerateModal, isGenerating, isGenerating, isGenerating,];
        } });
var __VLS_273 = ({ generated: {} },
    { onGenerated: (__VLS_ctx.handlePayrollGenerated) });
var __VLS_269;
var __VLS_270;
// @ts-ignore
[handlePayrollGenerated,];
var __VLS_263;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
