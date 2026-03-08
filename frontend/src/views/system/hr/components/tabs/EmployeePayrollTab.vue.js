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
var _a, _b, _c, _d, _e, _f, _g, _h, _j;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../../../../stores/auth");
var props = defineProps();
var emit = defineEmits();
// ==================== STATE ====================
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
var loading = (0, vue_1.ref)(false);
var showPayslipDialog = (0, vue_1.ref)(false);
var selectedPayslip = (0, vue_1.ref)(null);
// Year and Month selection
var currentYear = new Date().getFullYear();
var selectedYear = (0, vue_1.ref)(currentYear);
var selectedMonth = (0, vue_1.ref)(new Date().getMonth() + 1);
// Data
var payslipHistory = (0, vue_1.ref)([]);
var payrollSummary = (0, vue_1.ref)(null);
var pagination = (0, vue_1.ref)({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
    next_page_url: null,
    prev_page_url: null,
    links: []
});
// Generate year options (last 5 years)
var yearOptions = (0, vue_1.computed)(function () {
    var years = [];
    for (var i = 0; i < 5; i++) {
        years.push(currentYear - i);
    }
    return years.sort(function (a, b) { return b - a; });
});
// Month options
var monthOptions = (0, vue_1.ref)([
    { label: 'January', value: 1 },
    { label: 'February', value: 2 },
    { label: 'March', value: 3 },
    { label: 'April', value: 4 },
    { label: 'May', value: 5 },
    { label: 'June', value: 6 },
    { label: 'July', value: 7 },
    { label: 'August', value: 8 },
    { label: 'September', value: 9 },
    { label: 'October', value: 10 },
    { label: 'November', value: 11 },
    { label: 'December', value: 12 }
]);
// ==================== METHODS ====================
var fetchPayslipHistory = function () {
    var args_1 = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args_1[_i] = arguments[_i];
    }
    return __awaiter(void 0, __spreadArray([], args_1, true), void 0, function (page) {
        var response, responseData, error_1;
        if (page === void 0) { page = 1; }
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!props.employeeId) {
                        toast.add({
                            severity: 'warn',
                            summary: 'Warning',
                            detail: 'Employee ID not found',
                            life: 3000
                        });
                        return [2 /*return*/];
                    }
                    loading.value = true;
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, 4, 5]);
                    // Set auth token
                    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
                    return [4 /*yield*/, axios_1.default.get("/api/payroll/payslip/".concat(props.employeeId), {
                            params: {
                                year: selectedYear.value !== currentYear ? selectedYear.value : undefined,
                                month: selectedMonth.value,
                                page: page
                            }
                        })];
                case 2:
                    response = _a.sent();
                    if (response.data.success) {
                        responseData = response.data.data;
                        // Set payslip history from the paginated data
                        payslipHistory.value = responseData.data || [];
                        // Set pagination info
                        pagination.value = {
                            current_page: responseData.current_page,
                            last_page: responseData.last_page,
                            per_page: responseData.per_page,
                            total: responseData.total,
                            from: responseData.from,
                            to: responseData.to,
                            next_page_url: responseData.next_page_url,
                            prev_page_url: responseData.prev_page_url,
                            links: responseData.links || []
                        };
                        // Set summary from the main response
                        payrollSummary.value = response.data.summary;
                    }
                    return [3 /*break*/, 5];
                case 3:
                    error_1 = _a.sent();
                    console.error('Failed to fetch payslip history:', error_1);
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'Failed to fetch payslip history',
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
var changePage = function (page) {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchPayslipHistory(page);
    }
};
var getPayPeriodName = function (payslip) {
    var _a;
    // Try to get from pay_period object first
    if ((_a = payslip.pay_period) === null || _a === void 0 ? void 0 : _a.name) {
        return payslip.pay_period.name;
    }
    // Fallback: try to get from items or create a default name
    return "Period ".concat(payslip.pay_period_id || '');
};
var getEarnings = function (payslip) {
    var _a;
    return ((_a = payslip.items) === null || _a === void 0 ? void 0 : _a.earnings) || [];
};
var getDeductions = function (payslip) {
    var _a;
    return ((_a = payslip.items) === null || _a === void 0 ? void 0 : _a.deductions) || [];
};
var calculateGrossPay = function (payslip) {
    // Use pre-calculated gross_pay if available
    if (payslip.gross_pay) {
        return payslip.gross_pay;
    }
    // Otherwise calculate it
    return Number(payslip.base_salary || 0) +
        Number(payslip.overtime_amount || 0) +
        Number(payslip.bonuses_total || 0) +
        Number(payslip.allowances_total || 0);
};
var getStatusSeverity = function (status) {
    var map = {
        'draft': 'secondary',
        'processing': 'info',
        'approved': 'success',
        'paid': 'success',
        'cancelled': 'danger'
    };
    return map[status] || 'info';
};
var formatNumber = function (value) {
    if (value === null || value === undefined)
        return '0.00';
    var num = typeof value === 'string' ? parseFloat(value) : value;
    return num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
};
var formatDate = function (dateString) {
    if (!dateString)
        return 'N/A';
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
var formatDateRange = function (start, end) {
    if (!start || !end)
        return '';
    return "".concat(formatDate(start), " - ").concat(formatDate(end));
};
var viewPayslip = function (payslip) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get("/api/payrolls/".concat(payslip.id, "/payslip"))
                    // Merge with existing data
                ];
            case 1:
                response = _a.sent();
                // Merge with existing data
                selectedPayslip.value = __assign(__assign({}, payslip), response.data.payslip);
                showPayslipDialog.value = true;
                // Emit event to parent
                emit('view-payslip', payslip);
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                // If API fails, just show what we have
                selectedPayslip.value = payslip;
                showPayslipDialog.value = true;
                toast.add({
                    severity: 'warn',
                    summary: 'Warning',
                    detail: 'Showing basic payslip data',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var downloadPayslip = function (payslip) { return __awaiter(void 0, void 0, void 0, function () {
    var response, url, link, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!payslip)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                toast.add({
                    severity: 'info',
                    summary: 'Downloading',
                    detail: "Downloading payslip for ".concat(getPayPeriodName(payslip)),
                    life: 2000
                });
                // Emit event to parent
                emit('download-payslip', payslip);
                return [4 /*yield*/, axios_1.default.get("/api/payrolls/".concat(payslip.id, "/payslip/pdf"), {
                        responseType: 'blob'
                    })];
            case 2:
                response = _a.sent();
                url = window.URL.createObjectURL(new Blob([response.data]));
                link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', "payslip_".concat(getPayPeriodName(payslip).replace(/\s+/g, '_'), ".pdf"));
                document.body.appendChild(link);
                link.click();
                link.remove();
                return [3 /*break*/, 4];
            case 3:
                error_3 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to download payslip',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var printPayslip = function (payslip) { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        if (!payslip)
            return [2 /*return*/];
        try {
            // Emit event to parent
            emit('print-payslip', payslip);
            // Open print-friendly version
            window.open("/api/payrolls/".concat(payslip.id, "/payslip/print"), '_blank');
        }
        catch (error) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to print payslip',
                life: 3000
            });
        }
        return [2 /*return*/];
    });
}); };
var generatePayslip = function () {
    if (!selectedYear.value || !selectedMonth.value) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please select year and month',
            life: 3000
        });
        return;
    }
    // Emit event to parent
    emit('generate-payslip', selectedYear.value, selectedMonth.value);
    toast.add({
        severity: 'info',
        summary: 'Generating',
        detail: "Generating payslip for ".concat(selectedMonth.value, "/").concat(selectedYear.value),
        life: 2000
    });
    // After generation, refresh the list
    setTimeout(function () {
        fetchPayslipHistory();
    }, 1000);
};
var exportAllPayslips = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, url, link, error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!payslipHistory.value.length)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                // Emit event to parent
                emit('export-all', selectedYear.value, selectedMonth.value);
                return [4 /*yield*/, axios_1.default.get('/api/payrolls/export', {
                        params: {
                            employee_id: props.employeeId,
                            year: selectedYear.value,
                            month: selectedMonth.value
                        },
                        responseType: 'blob'
                    })];
            case 2:
                response = _a.sent();
                url = window.URL.createObjectURL(new Blob([response.data]));
                link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', "payslips_".concat(props.employeeId, "_").concat(selectedYear.value, ".csv"));
                document.body.appendChild(link);
                link.click();
                link.remove();
                toast.add({
                    severity: 'success',
                    summary: 'Exported',
                    detail: 'Payslips exported successfully',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 3:
                error_4 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to export payslips',
                    life: 3000
                });
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
// ==================== WATCHERS ====================
(0, vue_1.watch)(function () { return props.employeeId; }, function (newId) {
    if (newId) {
        fetchPayslipHistory();
    }
}, { immediate: true });
(0, vue_1.watch)(selectedYear, function () {
    fetchPayslipHistory();
});
(0, vue_1.watch)(selectedMonth, function () {
    fetchPayslipHistory();
});
// ==================== EXPOSE ====================
var __VLS_exposed = {
    fetchPayslipHistory: fetchPayslipHistory,
    refresh: fetchPayslipHistory
};
defineExpose(__VLS_exposed);
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedYear), options: (__VLS_ctx.yearOptions), placeholder: "Select Year" }), { class: "w-32" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedYear), options: (__VLS_ctx.yearOptions), placeholder: "Select Year" }), { class: "w-32" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchPayslipHistory) });
/** @type {__VLS_StyleScopedClasses['w-32']} */ ;
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedMonth), options: (__VLS_ctx.monthOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Month" }), { class: "w-40" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedMonth), options: (__VLS_ctx.monthOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Month" }), { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchPayslipHistory) });
/** @type {__VLS_StyleScopedClasses['w-40']} */ ;
var __VLS_10;
var __VLS_11;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onClick': {} }, { label: "Generate", icon: "pi pi-file-pdf", severity: "info", size: "small", disabled: (!__VLS_ctx.selectedYear || !__VLS_ctx.selectedMonth) })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Generate", icon: "pi pi-file-pdf", severity: "info", size: "small", disabled: (!__VLS_ctx.selectedYear || !__VLS_ctx.selectedMonth) })], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19;
var __VLS_20 = ({ click: {} },
    { onClick: (__VLS_ctx.generatePayslip) });
var __VLS_17;
var __VLS_18;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ 'onClick': {} }, { label: "Export All", icon: "pi pi-download", severity: "success", size: "small", outlined: true, disabled: (!__VLS_ctx.payslipHistory.length) })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export All", icon: "pi pi-download", severity: "success", size: "small", outlined: true, disabled: (!__VLS_ctx.payslipHistory.length) })], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26;
var __VLS_27 = ({ click: {} },
    { onClick: (__VLS_ctx.exportAllPayslips) });
var __VLS_24;
var __VLS_25;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 rounded-lg p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-blue-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-700 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.formatNumber(((_a = __VLS_ctx.payrollSummary) === null || _a === void 0 ? void 0 : _a.total_gross) || 0));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-blue-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(((_b = __VLS_ctx.payrollSummary) === null || _b === void 0 ? void 0 : _b.payroll_count) || 0);
(((_c = __VLS_ctx.payrollSummary) === null || _c === void 0 ? void 0 : _c.payroll_count) !== 1 ? 's' : '');
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 rounded-lg p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-green-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-green-700 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.formatNumber(((_d = __VLS_ctx.payrollSummary) === null || _d === void 0 ? void 0 : _d.total_net) || 0));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-green-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-50 rounded-lg p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-orange-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-orange-700 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.formatNumber(((_e = __VLS_ctx.payrollSummary) === null || _e === void 0 ? void 0 : _e.average_monthly) || 0));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-orange-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(((_f = __VLS_ctx.payrollSummary) === null || _f === void 0 ? void 0 : _f.month_count) || 0);
(((_g = __VLS_ctx.payrollSummary) === null || _g === void 0 ? void 0 : _g.month_count) !== 1 ? 's' : '');
if (__VLS_ctx.pagination.total > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.pagination.from);
    (__VLS_ctx.pagination.to);
    (__VLS_ctx.pagination.total);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, rounded: true, size: "small", disabled: (!__VLS_ctx.pagination.prev_page_url) })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, rounded: true, size: "small", disabled: (!__VLS_ctx.pagination.prev_page_url) })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.pagination.total > 0))
                    return;
                __VLS_ctx.changePage(__VLS_ctx.pagination.current_page - 1);
                // @ts-ignore
                [selectedYear, selectedYear, yearOptions, fetchPayslipHistory, fetchPayslipHistory, selectedMonth, selectedMonth, monthOptions, generatePayslip, payslipHistory, exportAllPayslips, formatNumber, formatNumber, formatNumber, payrollSummary, payrollSummary, payrollSummary, payrollSummary, payrollSummary, payrollSummary, payrollSummary, pagination, pagination, pagination, pagination, pagination, pagination, changePage,];
            } });
    var __VLS_31;
    var __VLS_32;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "px-3 py-1 bg-gray-100 rounded-md" }));
    /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-md']} */ ;
    (__VLS_ctx.pagination.current_page);
    (__VLS_ctx.pagination.last_page);
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, rounded: true, size: "small", disabled: (!__VLS_ctx.pagination.next_page_url) })));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, rounded: true, size: "small", disabled: (!__VLS_ctx.pagination.next_page_url) })], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = void 0;
    var __VLS_41 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.pagination.total > 0))
                    return;
                __VLS_ctx.changePage(__VLS_ctx.pagination.current_page + 1);
                // @ts-ignore
                [pagination, pagination, pagination, pagination, changePage,];
            } });
    var __VLS_38;
    var __VLS_39;
}
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign(__assign({ value: (__VLS_ctx.payslipHistory), paginator: (false) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), dataKey: "id", globalFilterFields: (['pay_period?.name', 'status']) })));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.payslipHistory), paginator: (false) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), dataKey: "id", globalFilterFields: (['pay_period?.name', 'status']) })], __VLS_functionalComponentArgsRest(__VLS_43), false));
/** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
var __VLS_47 = __VLS_45.slots.default;
var __VLS_48;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
    field: "pay_period?.name",
    header: "Pay Period",
    sortable: true,
}));
var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
        field: "pay_period?.name",
        header: "Pay Period",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_49), false));
var __VLS_53 = __VLS_51.slots.default;
{
    var __VLS_54 = __VLS_51.slots.body;
    var data = __VLS_vSlot(__VLS_54)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.getPayPeriodName(data) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatDateRange((_h = data.pay_period) === null || _h === void 0 ? void 0 : _h.start_date, (_j = data.pay_period) === null || _j === void 0 ? void 0 : _j.end_date));
    // @ts-ignore
    [payslipHistory, loading, getPayPeriodName, formatDateRange,];
}
// @ts-ignore
[];
var __VLS_51;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
    field: "base_salary",
    header: "Base Salary",
    sortable: true,
}));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
        field: "base_salary",
        header: "Base Salary",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_56), false));
var __VLS_60 = __VLS_58.slots.default;
{
    var __VLS_61 = __VLS_58.slots.body;
    var data = __VLS_vSlot(__VLS_61)[0].data;
    (__VLS_ctx.formatNumber(data.base_salary));
    // @ts-ignore
    [formatNumber,];
}
// @ts-ignore
[];
var __VLS_58;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62({
    field: "overtime_amount",
    header: "Overtime",
    sortable: true,
}));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([{
        field: "overtime_amount",
        header: "Overtime",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67 = __VLS_65.slots.default;
{
    var __VLS_68 = __VLS_65.slots.body;
    var data = __VLS_vSlot(__VLS_68)[0].data;
    if (data.overtime_amount > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        (__VLS_ctx.formatNumber(data.overtime_amount));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500 block" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        (data.overtime_hours);
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [formatNumber,];
}
// @ts-ignore
[];
var __VLS_65;
var __VLS_69;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69({
    field: "allowances_total",
    header: "Allowances",
    sortable: true,
}));
var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([{
        field: "allowances_total",
        header: "Allowances",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_70), false));
var __VLS_74 = __VLS_72.slots.default;
{
    var __VLS_75 = __VLS_72.slots.body;
    var data = __VLS_vSlot(__VLS_75)[0].data;
    if (data.allowances_total > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        (__VLS_ctx.formatNumber(data.allowances_total));
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [formatNumber,];
}
// @ts-ignore
[];
var __VLS_72;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({
    field: "gross_pay",
    header: "Gross Pay",
    sortable: true,
}));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{
        field: "gross_pay",
        header: "Gross Pay",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_77), false));
var __VLS_81 = __VLS_79.slots.default;
{
    var __VLS_82 = __VLS_79.slots.body;
    var data = __VLS_vSlot(__VLS_82)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.formatNumber(__VLS_ctx.calculateGrossPay(data)));
    // @ts-ignore
    [formatNumber, calculateGrossPay,];
}
// @ts-ignore
[];
var __VLS_79;
var __VLS_83;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
    field: "deductions_total",
    header: "Deductions",
    sortable: true,
}));
var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
        field: "deductions_total",
        header: "Deductions",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_84), false));
var __VLS_88 = __VLS_86.slots.default;
{
    var __VLS_89 = __VLS_86.slots.body;
    var data = __VLS_vSlot(__VLS_89)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatNumber(data.deductions_total + data.tax_amount));
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatNumber(data.tax_amount));
    // @ts-ignore
    [formatNumber, formatNumber,];
}
// @ts-ignore
[];
var __VLS_86;
var __VLS_90;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90({
    field: "net_salary",
    header: "Net Pay",
    sortable: true,
}));
var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([{
        field: "net_salary",
        header: "Net Pay",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_91), false));
var __VLS_95 = __VLS_93.slots.default;
{
    var __VLS_96 = __VLS_93.slots.body;
    var data = __VLS_vSlot(__VLS_96)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatNumber(data.net_salary));
    // @ts-ignore
    [formatNumber,];
}
// @ts-ignore
[];
var __VLS_93;
var __VLS_97;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({
    field: "status",
    header: "Status",
    sortable: true,
}));
var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{
        field: "status",
        header: "Status",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_98), false));
var __VLS_102 = __VLS_100.slots.default;
{
    var __VLS_103 = __VLS_100.slots.body;
    var data = __VLS_vSlot(__VLS_103)[0].data;
    var __VLS_104 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104(__assign({ value: (data.status), severity: (__VLS_ctx.getStatusSeverity(data.status)), rounded: true }, { class: "capitalize" })));
    var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([__assign({ value: (data.status), severity: (__VLS_ctx.getStatusSeverity(data.status)), rounded: true }, { class: "capitalize" })], __VLS_functionalComponentArgsRest(__VLS_105), false));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    if (data.approved_at) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500 block text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        (__VLS_ctx.formatDate(data.approved_at));
    }
    // @ts-ignore
    [getStatusSeverity, formatDate,];
}
// @ts-ignore
[];
var __VLS_100;
var __VLS_109;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109({
    field: "payment_date",
    header: "Release Date",
    sortable: true,
}));
var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([{
        field: "payment_date",
        header: "Release Date",
        sortable: true,
    }], __VLS_functionalComponentArgsRest(__VLS_110), false));
var __VLS_114 = __VLS_112.slots.default;
{
    var __VLS_115 = __VLS_112.slots.body;
    var data = __VLS_vSlot(__VLS_115)[0].data;
    if (data.payment_date) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        (__VLS_ctx.formatDate(data.payment_date));
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_112;
var __VLS_116;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116(__assign({ header: "Actions" }, { style: {} })));
var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_117), false));
var __VLS_121 = __VLS_119.slots.default;
{
    var __VLS_122 = __VLS_119.slots.body;
    var data_1 = __VLS_vSlot(__VLS_122)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_123 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })));
    var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_124), false));
    var __VLS_128 = void 0;
    var __VLS_129 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewPayslip(data_1);
                // @ts-ignore
                [viewPayslip,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View payslip') }), null, null);
    var __VLS_126;
    var __VLS_127;
    var __VLS_130 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_131 = __VLS_asFunctionalComponent1(__VLS_130, new __VLS_130(__assign({ 'onClick': {} }, { icon: "pi pi-download", text: true, rounded: true, severity: "success", size: "small", disabled: (data_1.status !== 'paid' && data_1.status !== 'approved') })));
    var __VLS_132 = __VLS_131.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-download", text: true, rounded: true, severity: "success", size: "small", disabled: (data_1.status !== 'paid' && data_1.status !== 'approved') })], __VLS_functionalComponentArgsRest(__VLS_131), false));
    var __VLS_135 = void 0;
    var __VLS_136 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.downloadPayslip(data_1);
                // @ts-ignore
                [vTooltip, downloadPayslip,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Download PDF') }), null, null);
    var __VLS_133;
    var __VLS_134;
    var __VLS_137 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_138 = __VLS_asFunctionalComponent1(__VLS_137, new __VLS_137(__assign({ 'onClick': {} }, { icon: "pi pi-print", text: true, rounded: true, severity: "warning", size: "small" })));
    var __VLS_139 = __VLS_138.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-print", text: true, rounded: true, severity: "warning", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_138), false));
    var __VLS_142 = void 0;
    var __VLS_143 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.printPayslip(data_1);
                // @ts-ignore
                [vTooltip, printPayslip,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Print') }), null, null);
    var __VLS_140;
    var __VLS_141;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_119;
{
    var __VLS_144 = __VLS_45.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-pdf text-4xl mb-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file-pdf']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_45;
var __VLS_145;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145(__assign(__assign({ visible: (__VLS_ctx.showPayslipDialog), header: "Payslip Details" }, { style: ({ width: '700px' }) }), { modal: true, closable: (true) })));
var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showPayslipDialog), header: "Payslip Details" }, { style: ({ width: '700px' }) }), { modal: true, closable: (true) })], __VLS_functionalComponentArgsRest(__VLS_146), false));
var __VLS_150 = __VLS_148.slots.default;
if (__VLS_ctx.selectedPayslip) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedPayslip.employee_name || __VLS_ctx.employeeName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.getPayPeriodName(__VLS_ctx.selectedPayslip));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium mb-2" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _i = 0, _k = __VLS_vFor((__VLS_ctx.getEarnings(__VLS_ctx.selectedPayslip))); _i < _k.length; _i++) {
        var item = _k[_i][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (item.id) }, { class: "flex justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (item.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatNumber(item.amount));
        // @ts-ignore
        [formatNumber, getPayPeriodName, showPayslipDialog, selectedPayslip, selectedPayslip, selectedPayslip, selectedPayslip, employeeName, getEarnings,];
    }
    if (__VLS_ctx.getEarnings(__VLS_ctx.selectedPayslip).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-gray-400 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium mb-2" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _l = 0, _m = __VLS_vFor((__VLS_ctx.getDeductions(__VLS_ctx.selectedPayslip))); _l < _m.length; _l++) {
        var item = _m[_l][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (item.id) }, { class: "flex justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (item.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium text-red-600" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        (__VLS_ctx.formatNumber(item.amount));
        // @ts-ignore
        [formatNumber, selectedPayslip, selectedPayslip, getEarnings, getDeductions,];
    }
    if (__VLS_ctx.getDeductions(__VLS_ctx.selectedPayslip).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-gray-400 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between font-bold" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.formatNumber(__VLS_ctx.calculateGrossPay(__VLS_ctx.selectedPayslip)));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between font-bold mt-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatNumber(__VLS_ctx.selectedPayslip.deductions_total +
        __VLS_ctx.selectedPayslip.tax_amount));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between font-bold mt-2 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatNumber(__VLS_ctx.selectedPayslip.net_salary));
}
{
    var __VLS_151 = __VLS_148.slots.footer;
    var __VLS_152 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152(__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times" })));
    var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times" })], __VLS_functionalComponentArgsRest(__VLS_153), false));
    var __VLS_157 = void 0;
    var __VLS_158 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showPayslipDialog = false;
                // @ts-ignore
                [formatNumber, formatNumber, formatNumber, calculateGrossPay, showPayslipDialog, selectedPayslip, selectedPayslip, selectedPayslip, selectedPayslip, selectedPayslip, getDeductions,];
            } });
    var __VLS_155;
    var __VLS_156;
    var __VLS_159 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ 'onClick': {} }, { label: "Download PDF", icon: "pi pi-download", severity: "success" })));
    var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Download PDF", icon: "pi pi-download", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_160), false));
    var __VLS_164 = void 0;
    var __VLS_165 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.downloadPayslip(__VLS_ctx.selectedPayslip);
                // @ts-ignore
                [downloadPayslip, selectedPayslip,];
            } });
    var __VLS_162;
    var __VLS_163;
    var __VLS_166 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_167 = __VLS_asFunctionalComponent1(__VLS_166, new __VLS_166(__assign({ 'onClick': {} }, { label: "Print", icon: "pi pi-print", severity: "info" })));
    var __VLS_168 = __VLS_167.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Print", icon: "pi pi-print", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_167), false));
    var __VLS_171 = void 0;
    var __VLS_172 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.printPayslip(__VLS_ctx.selectedPayslip);
                // @ts-ignore
                [printPayslip, selectedPayslip,];
            } });
    var __VLS_169;
    var __VLS_170;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_148;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    setup: function () { return (__VLS_exposed); },
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
