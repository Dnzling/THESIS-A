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
var usetoast_1 = require("primevue/usetoast");
var vue_router_1 = require("vue-router");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
var props = defineProps();
// ==================== STATE ====================
var toast = (0, usetoast_1.useToast)();
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var authStore = (0, auth_1.useAuthStore)();
var loading = (0, vue_1.ref)(false);
var bulkSubmitting = (0, vue_1.ref)(false);
var saveAllLoading = (0, vue_1.ref)(false);
var selectedItems = (0, vue_1.ref)([]);
var payrollItems = (0, vue_1.ref)([]);
var batchInfo = (0, vue_1.ref)(null);
// Resolve pay period ID from prop or route param
var periodId = (0, vue_1.computed)(function () { return props.batchId || route.params.id; });
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    branch: null,
    department: null,
    status: null
});
var branches = (0, vue_1.ref)([]);
var departments = (0, vue_1.ref)([]);
var statusOptions = (0, vue_1.ref)(['draft', 'approved', 'paid', 'cancelled']);
// ==================== COMPUTED ====================
var filteredPayrollItems = (0, vue_1.computed)(function () {
    return payrollItems.value.filter(function (item) {
        var matchesSearch = !filters.value.search ||
            item.employeeName.toLowerCase().includes(filters.value.search.toLowerCase()) ||
            item.employeeId.toLowerCase().includes(filters.value.search.toLowerCase());
        var matchesBranch = !filters.value.branch || item.branch === filters.value.branch;
        var matchesDept = !filters.value.department || item.department === filters.value.department;
        var matchesStatus = !filters.value.status || item.status === filters.value.status;
        return matchesSearch && matchesBranch && matchesDept && matchesStatus;
    });
});
var hasSelectedItems = (0, vue_1.computed)(function () { return selectedItems.value.length > 0; });
// ==================== METHODS ====================
var fetchPayrollData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payrollRes, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!periodId.value)
                    return [2 /*return*/];
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
                return [4 /*yield*/, Promise.all([
                        axios_1.default.get('/api/payroll', { params: { pay_period_id: periodId.value } }),
                        fetchBatchInfo()
                    ])];
            case 2:
                payrollRes = (_a.sent())[0];
                if (payrollRes.data.success) {
                    payrollItems.value = transformPayrollData(payrollRes.data.data);
                    extractFilterOptions(payrollItems.value);
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch payroll data', life: 3000 });
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var fetchBatchInfo = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get("/api/payroll/pay-periods/".concat(periodId.value))];
            case 1:
                response = _a.sent();
                if (response.data.success) {
                    batchInfo.value = response.data.data;
                }
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to fetch batch info:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var transformPayrollData = function (apiData) {
    return apiData.map(function (item) {
        var _a, _b, _c, _d;
        var govDeductions = {
            tax: parseFloat(item.tax_amount) || 0,
            sss: 0,
            philhealth: 0,
            pagibig: 0
        };
        var grossPay = (parseFloat(item.base_salary) || 0)
            + (parseFloat(item.overtime_amount) || 0)
            + (parseFloat(item.bonuses_total) || 0)
            + (parseFloat(item.allowances_total) || 0);
        return {
            id: ((_a = item.id) === null || _a === void 0 ? void 0 : _a.toString()) || '',
            payroll_id: item.id,
            employeeId: ((_b = item.employee) === null || _b === void 0 ? void 0 : _b.employee_number) || '',
            employeeName: item.employee ? "".concat(item.employee.fname, " ").concat(item.employee.lname) : '',
            branch: ((_c = item.employee) === null || _c === void 0 ? void 0 : _c.branch) || 'N/A',
            department: ((_d = item.employee) === null || _d === void 0 ? void 0 : _d.department) || 'N/A',
            baseSalary: parseFloat(item.base_salary) || 0,
            salaryPerHour: item.base_salary ? parseFloat(item.base_salary) / 160 : 0,
            basicPay: parseFloat(item.base_salary) || 0,
            overtimePay: parseFloat(item.overtime_amount) || 0,
            allowanceAmount: parseFloat(item.allowances_total) || 0,
            bonusPay: parseFloat(item.bonuses_total) || 0,
            governmentDeductions: govDeductions,
            lateDeductions: parseFloat(item.late_deduction) || 0,
            leaveDeductions: 0,
            otherDeductions: 0,
            grossPay: grossPay,
            totalDeductions: (parseFloat(item.deductions_total) || 0) + govDeductions.tax,
            netPay: parseFloat(item.net_salary) || 0,
            status: item.status || 'draft',
            saving: false,
            submitting: false
        };
    });
};
var extractFilterOptions = function (items) {
    var branchSet = new Set();
    var deptSet = new Set();
    items.forEach(function (item) {
        if (item.branch && item.branch !== 'N/A')
            branchSet.add(item.branch);
        if (item.department && item.department !== 'N/A')
            deptSet.add(item.department);
    });
    branches.value = Array.from(branchSet).sort();
    departments.value = Array.from(deptSet).sort();
};
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(new Date(date));
};
var formatCurrency = function (value) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(value || 0);
};
var getStatusSeverity = function (status) {
    var map = {
        draft: 'secondary', approved: 'success', paid: 'success', cancelled: 'danger'
    };
    return map[status] || 'info';
};
var recalculateTotals = function (item) {
    item.grossPay = item.basicPay + item.overtimePay + item.allowanceAmount + item.bonusPay;
    var govTotal = Object.values(item.governmentDeductions).reduce(function (a, b) { return a + b; }, 0);
    item.totalDeductions = govTotal + item.lateDeductions + item.leaveDeductions + item.otherDeductions;
    item.netPay = item.grossPay - item.totalDeductions;
    item.dirty = true; // mark as modified
};
var saveAllChanges = function () { return __awaiter(void 0, void 0, void 0, function () {
    var draftItems, savedCount, errorCount;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                draftItems = payrollItems.value.filter(function (i) { return i.status === 'draft' && i.dirty; });
                if (draftItems.length === 0) {
                    toast.add({ severity: 'info', summary: 'Nothing to Save', detail: 'No unsaved changes detected', life: 2000 });
                    return [2 /*return*/];
                }
                saveAllLoading.value = true;
                savedCount = 0;
                errorCount = 0;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, Promise.all(draftItems.map(function (item) { return __awaiter(void 0, void 0, void 0, function () {
                        var response, _a;
                        return __generator(this, function (_b) {
                            switch (_b.label) {
                                case 0:
                                    _b.trys.push([0, 2, , 3]);
                                    return [4 /*yield*/, axios_1.default.put("/api/payroll/".concat(item.payroll_id), {
                                            allowances_total: item.allowanceAmount,
                                            bonuses_total: item.bonusPay,
                                            late_deduction: item.lateDeductions,
                                            deductions_total: item.totalDeductions - item.governmentDeductions.tax,
                                        })];
                                case 1:
                                    response = _b.sent();
                                    if (response.data.success) {
                                        item.netPay = parseFloat(response.data.data.net_salary) || item.netPay;
                                        item.dirty = false; // clear dirty flag after successful save
                                        savedCount++;
                                    }
                                    return [3 /*break*/, 3];
                                case 2:
                                    _a = _b.sent();
                                    errorCount++;
                                    return [3 /*break*/, 3];
                                case 3: return [2 /*return*/];
                            }
                        });
                    }); }))];
            case 2:
                _a.sent();
                if (savedCount > 0) {
                    toast.add({
                        severity: 'success',
                        summary: 'All Changes Saved',
                        detail: "".concat(savedCount, " payroll record").concat(savedCount > 1 ? 's' : '', " saved successfully").concat(errorCount > 0 ? " (".concat(errorCount, " failed)") : ''),
                        life: 3000
                    });
                }
                else {
                    toast.add({ severity: 'error', summary: 'Save Failed', detail: 'Failed to save payroll changes', life: 3000 });
                }
                return [3 /*break*/, 4];
            case 3:
                saveAllLoading.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); };
var savePayrollItem = function (item) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                item.saving = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.put("/api/payroll/".concat(item.payroll_id), {
                        allowances_total: item.allowanceAmount,
                        bonuses_total: item.bonusPay,
                        late_deduction: item.lateDeductions,
                        deductions_total: item.totalDeductions - item.governmentDeductions.tax,
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    // Sync net pay from server
                    item.netPay = parseFloat(response.data.data.net_salary) || item.netPay;
                    toast.add({ severity: 'success', summary: 'Saved', detail: "".concat(item.employeeName, "'s payroll saved"), life: 2000 });
                }
                return [3 /*break*/, 5];
            case 3:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3 === null || error_3 === void 0 ? void 0 : error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to save payroll',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                item.saving = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var submitForApproval = function (item) { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                item.submitting = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.post("/api/payroll/".concat(item.payroll_id, "/approve"))];
            case 2:
                _c.sent();
                item.status = 'approved';
                toast.add({ severity: 'success', summary: 'Approved', detail: "".concat(item.employeeName, "'s payroll approved"), life: 3000 });
                return [3 /*break*/, 5];
            case 3:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4 === null || error_4 === void 0 ? void 0 : error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to submit for approval',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                item.submitting = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var submitBatchForApproval = function () { return __awaiter(void 0, void 0, void 0, function () {
    var eligibleItems, ids, response, error_5;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                eligibleItems = selectedItems.value.filter(function (i) { return i.status === 'draft'; });
                if (eligibleItems.length === 0) {
                    toast.add({ severity: 'warn', summary: 'No Eligible Items', detail: 'Selected payrolls must be in draft status', life: 3000 });
                    return [2 /*return*/];
                }
                bulkSubmitting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                ids = eligibleItems.map(function (i) { return i.payroll_id; });
                return [4 /*yield*/, axios_1.default.post('/api/payroll/bulk-approve', { payroll_ids: ids })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    eligibleItems.forEach(function (item) { item.status = 'approved'; });
                    selectedItems.value = [];
                    toast.add({ severity: 'success', summary: 'Bulk Approved', detail: response.data.message, life: 3000 });
                }
                return [3 /*break*/, 5];
            case 3:
                error_5 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_5 === null || error_5 === void 0 ? void 0 : error_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to bulk approve payrolls',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                bulkSubmitting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var printPayslip = function (item) {
    var _a;
    var period = batchInfo.value;
    var html = "\n    <!DOCTYPE html>\n    <html>\n    <head>\n      <title>Payslip - ".concat(item.employeeName, "</title>\n      <style>\n        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #333; }\n        h2 { text-align: center; margin-bottom: 4px; }\n        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 11px; }\n        .section { margin-bottom: 12px; }\n        .section-title { font-weight: bold; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #333; margin-bottom: 6px; }\n        table { width: 100%; border-collapse: collapse; }\n        td { padding: 4px 8px; }\n        td:last-child { text-align: right; }\n        .total-row td { font-weight: bold; border-top: 2px solid #333; }\n        .net-row td { font-weight: bold; font-size: 14px; background: #e8f5e9; }\n        .header-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; font-size: 11px; }\n        .label { color: #666; }\n        @media print { body { margin: 10px; } }\n      </style>\n    </head>\n    <body>\n      <h2>PAYSLIP</h2>\n      <div class=\"subtitle\">").concat((_a = period === null || period === void 0 ? void 0 : period.name) !== null && _a !== void 0 ? _a : '', " &nbsp;|&nbsp; ").concat(period ? formatDate(period.start_date) + ' – ' + formatDate(period.end_date) : '', "</div>\n      <div class=\"header-grid\">\n        <div><span class=\"label\">Employee:</span> <strong>").concat(item.employeeName, "</strong></div>\n        <div><span class=\"label\">Employee #:</span> ").concat(item.employeeId, "</div>\n        <div><span class=\"label\">Department:</span> ").concat(item.department, "</div>\n        <div><span class=\"label\">Branch:</span> ").concat(item.branch, "</div>\n        <div><span class=\"label\">Pay Date:</span> ").concat(period ? formatDate(period.pay_date) : 'N/A', "</div>\n        <div><span class=\"label\">Status:</span> ").concat(item.status.toUpperCase(), "</div>\n      </div>\n      <div class=\"section\">\n        <div class=\"section-title\">EARNINGS</div>\n        <table>\n          <tr><td>Basic Salary</td><td>").concat(formatCurrency(item.baseSalary), "</td></tr>\n          <tr><td>Overtime Pay</td><td>").concat(formatCurrency(item.overtimePay), "</td></tr>\n          <tr><td>Bonus Pay</td><td>").concat(formatCurrency(item.bonusPay), "</td></tr>\n          <tr><td>Allowances</td><td>").concat(formatCurrency(item.allowanceAmount), "</td></tr>\n          <tr class=\"total-row\"><td>Gross Pay</td><td>").concat(formatCurrency(item.grossPay), "</td></tr>\n        </table>\n      </div>\n      <div class=\"section\">\n        <div class=\"section-title\">DEDUCTIONS</div>\n        <table>\n          <tr><td>Income Tax</td><td>- ").concat(formatCurrency(item.governmentDeductions.tax), "</td></tr>\n          <tr><td>SSS</td><td>- ").concat(formatCurrency(item.governmentDeductions.sss), "</td></tr>\n          <tr><td>PhilHealth</td><td>- ").concat(formatCurrency(item.governmentDeductions.philhealth), "</td></tr>\n          <tr><td>Pag-IBIG</td><td>- ").concat(formatCurrency(item.governmentDeductions.pagibig), "</td></tr>\n          <tr><td>Late Deductions</td><td>- ").concat(formatCurrency(item.lateDeductions), "</td></tr>\n          <tr><td>Leave Deductions</td><td>- ").concat(formatCurrency(item.leaveDeductions), "</td></tr>\n          <tr class=\"total-row\"><td>Total Deductions</td><td>- ").concat(formatCurrency(item.totalDeductions), "</td></tr>\n        </table>\n      </div>\n      <div class=\"section\">\n        <table>\n          <tr class=\"net-row\"><td>NET PAY</td><td>").concat(formatCurrency(item.netPay), "</td></tr>\n        </table>\n      </div>\n      <div style=\"margin-top:40px; display:grid; grid-template-columns:1fr 1fr; gap:20px; font-size:11px;\">\n        <div style=\"border-top:1px solid #333; padding-top:4px; text-align:center;\">Prepared by</div>\n        <div style=\"border-top:1px solid #333; padding-top:4px; text-align:center;\">Received by</div>\n      </div>\n    </body>\n    </html>\n  ");
    var win = window.open('', '_blank', 'width=700,height=900');
    if (win) {
        win.document.write(html);
        win.document.close();
        win.focus();
        setTimeout(function () { return win.print(); }, 500);
    }
};
var goBack = function () {
    router.push({ name: 'hr.payroll' });
};
// ==================== LIFECYCLE ====================
(0, vue_1.onMounted)(function () {
    axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
    fetchPayrollData();
});
var __VLS_ctx = __assign(__assign(__assign(__assign({}, {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['p-datatable']} */ ;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['p-inputnumber']} */ ;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['p-datatable']} */ ;
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['p-inputnumber']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "payroll-list" }));
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
if (__VLS_ctx.batchInfo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4 p-3 bg-gray-50 rounded-lg border" }));
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.batchInfo.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.batchInfo.start_date));
    (__VLS_ctx.formatDate(__VLS_ctx.batchInfo.end_date));
    (__VLS_ctx.formatDate(__VLS_ctx.batchInfo.pay_date));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.batchInfo.status)), value: (__VLS_ctx.batchInfo.status) }, { class: "capitalize" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.batchInfo.status)), value: (__VLS_ctx.batchInfo.status) }, { class: "capitalize" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true, size: "small" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    var __VLS_10 = void 0;
    var __VLS_11 = ({ click: {} },
        { onClick: (__VLS_ctx.goBack) });
    var __VLS_8;
    var __VLS_9;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 mb-4 flex-wrap" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12({
    iconPosition: "left",
}));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([{
        iconPosition: "left",
    }], __VLS_functionalComponentArgsRest(__VLS_13), false));
var __VLS_17 = __VLS_15.slots.default;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18({}));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_19), false));
var __VLS_23 = __VLS_21.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
// @ts-ignore
[batchInfo, batchInfo, batchInfo, batchInfo, batchInfo, batchInfo, batchInfo, formatDate, formatDate, formatDate, getStatusSeverity, goBack,];
var __VLS_21;
var __VLS_24;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "w-full" })));
var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
// @ts-ignore
[filters,];
var __VLS_15;
var __VLS_29;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29(__assign({ modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }, { class: "w-48" })));
var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_30), false));
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_34;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34(__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }, { class: "w-48" })));
var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_35), false));
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }, { class: "w-48" })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_44;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44(__assign({ 'onClick': {} }, { label: "Save All Changes", icon: "pi pi-save", severity: "success", disabled: (__VLS_ctx.loading || __VLS_ctx.saveAllLoading), loading: (__VLS_ctx.saveAllLoading) })));
var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save All Changes", icon: "pi pi-save", severity: "success", disabled: (__VLS_ctx.loading || __VLS_ctx.saveAllLoading), loading: (__VLS_ctx.saveAllLoading) })], __VLS_functionalComponentArgsRest(__VLS_45), false));
var __VLS_49;
var __VLS_50 = ({ click: {} },
    { onClick: (__VLS_ctx.saveAllChanges) });
var __VLS_47;
var __VLS_48;
var __VLS_51;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign(__assign(__assign({ 'onClick': {} }, { label: "Bulk Submit for Approval" }), { class: "ml-auto" }), { icon: "pi pi-send", severity: "info", disabled: (!__VLS_ctx.hasSelectedItems || __VLS_ctx.bulkSubmitting), loading: (__VLS_ctx.bulkSubmitting) })));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: "Bulk Submit for Approval" }), { class: "ml-auto" }), { icon: "pi pi-send", severity: "info", disabled: (!__VLS_ctx.hasSelectedItems || __VLS_ctx.bulkSubmitting), loading: (__VLS_ctx.bulkSubmitting) })], __VLS_functionalComponentArgsRest(__VLS_52), false));
var __VLS_56;
var __VLS_57 = ({ click: {} },
    { onClick: (__VLS_ctx.submitBatchForApproval) });
/** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
var __VLS_54;
var __VLS_55;
var __VLS_58;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
    value: (__VLS_ctx.filteredPayrollItems),
    paginator: (true),
    rows: (10),
    rowsPerPageOptions: ([10, 20, 50]),
    tableStyle: "min-width: 155rem",
    loading: (__VLS_ctx.loading),
    paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
    currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} employees",
    sortMode: "multiple",
    rowHover: true,
    scrollable: true,
    scrollHeight: "calc(100vh - 300px)",
    selection: (__VLS_ctx.selectedItems),
    removableSort: true,
    selectionMode: "multiple",
}));
var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.filteredPayrollItems),
        paginator: (true),
        rows: (10),
        rowsPerPageOptions: ([10, 20, 50]),
        tableStyle: "min-width: 155rem",
        loading: (__VLS_ctx.loading),
        paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
        currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} employees",
        sortMode: "multiple",
        rowHover: true,
        scrollable: true,
        scrollHeight: "calc(100vh - 300px)",
        selection: (__VLS_ctx.selectedItems),
        removableSort: true,
        selectionMode: "multiple",
    }], __VLS_functionalComponentArgsRest(__VLS_59), false));
var __VLS_63 = __VLS_61.slots.default;
var __VLS_64;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64({
    selectionMode: "multiple",
    headerStyle: "width: 3rem",
}));
var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([{
        selectionMode: "multiple",
        headerStyle: "width: 3rem",
    }], __VLS_functionalComponentArgsRest(__VLS_65), false));
var __VLS_69;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ class: "text-xs" }, { field: "employeeName", header: "Employee", sortable: true })));
var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "employeeName", header: "Employee", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_70), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_74 = __VLS_72.slots.default;
{
    var __VLS_75 = __VLS_72.slots.body;
    var data = __VLS_vSlot(__VLS_75)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.employeeName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (data.employeeId);
    // @ts-ignore
    [filters, filters, filters, branches, departments, statusOptions, loading, loading, saveAllLoading, saveAllLoading, saveAllChanges, hasSelectedItems, bulkSubmitting, bulkSubmitting, submitBatchForApproval, filteredPayrollItems, selectedItems,];
}
// @ts-ignore
[];
var __VLS_72;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ class: "text-xs" }, { field: "branch", header: "Branch", sortable: true })));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "branch", header: "Branch", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_77), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_81;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign({ class: "text-xs" }, { field: "department", header: "Department", sortable: true })));
var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "department", header: "Department", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_82), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_86;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_87 = __VLS_asFunctionalComponent1(__VLS_86, new __VLS_86(__assign({ class: "text-xs" }, { field: "baseSalary", header: "Base Salary", sortable: true })));
var __VLS_88 = __VLS_87.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "baseSalary", header: "Base Salary", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_87), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_91 = __VLS_89.slots.default;
{
    var __VLS_92 = __VLS_89.slots.body;
    var data_1 = __VLS_vSlot(__VLS_92)[0].data;
    var __VLS_93 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_1.baseSalary), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_1.baseSalary), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_94), false));
    var __VLS_98 = void 0;
    var __VLS_99 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_1);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_96;
    var __VLS_97;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_89;
var __VLS_100;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_101 = __VLS_asFunctionalComponent1(__VLS_100, new __VLS_100(__assign({ class: "text-xs" }, { field: "salaryPerHour", header: "Rate/Hour", sortable: true })));
var __VLS_102 = __VLS_101.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "salaryPerHour", header: "Rate/Hour", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_101), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_105 = __VLS_103.slots.default;
{
    var __VLS_106 = __VLS_103.slots.body;
    var data = __VLS_vSlot(__VLS_106)[0].data;
    (__VLS_ctx.formatCurrency(data.salaryPerHour));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_103;
var __VLS_107;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107(__assign({ class: "text-xs" }, { field: "basicPay", header: "Basic Pay", sortable: true })));
var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "basicPay", header: "Basic Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_108), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_112 = __VLS_110.slots.default;
{
    var __VLS_113 = __VLS_110.slots.body;
    var data_2 = __VLS_vSlot(__VLS_113)[0].data;
    var __VLS_114 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_2.basicPay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_2.basicPay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_115), false));
    var __VLS_119 = void 0;
    var __VLS_120 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_2);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_117;
    var __VLS_118;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_110;
var __VLS_121;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121(__assign({ class: "text-xs" }, { field: "overtimePay", header: "OT Pay", sortable: true })));
var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "overtimePay", header: "OT Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_122), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_126 = __VLS_124.slots.default;
{
    var __VLS_127 = __VLS_124.slots.body;
    var data_3 = __VLS_vSlot(__VLS_127)[0].data;
    var __VLS_128 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_3.overtimePay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_3.overtimePay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_129), false));
    var __VLS_133 = void 0;
    var __VLS_134 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_3);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_131;
    var __VLS_132;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_124;
var __VLS_135;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135(__assign({ class: "text-xs" }, { field: "allowanceAmount", header: "Allowance", sortable: true })));
var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "allowanceAmount", header: "Allowance", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_136), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_140 = __VLS_138.slots.default;
{
    var __VLS_141 = __VLS_138.slots.body;
    var data_4 = __VLS_vSlot(__VLS_141)[0].data;
    var __VLS_142 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_143 = __VLS_asFunctionalComponent1(__VLS_142, new __VLS_142(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_4.allowanceAmount), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_144 = __VLS_143.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_4.allowanceAmount), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_143), false));
    var __VLS_147 = void 0;
    var __VLS_148 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_4);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_145;
    var __VLS_146;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_138;
var __VLS_149;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_150 = __VLS_asFunctionalComponent1(__VLS_149, new __VLS_149(__assign(__assign({ class: "text-xs font-semibold" }, { header: "Gov't Deductions" }), { style: {} })));
var __VLS_151 = __VLS_150.apply(void 0, __spreadArray([__assign(__assign({ class: "text-xs font-semibold" }, { header: "Gov't Deductions" }), { style: {} })], __VLS_functionalComponentArgsRest(__VLS_150), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
var __VLS_154 = __VLS_152.slots.default;
{
    var __VLS_155 = __VLS_152.slots.body;
    var data_5 = __VLS_vSlot(__VLS_155)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs space-y-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-16" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    var __VLS_156 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_157 = __VLS_asFunctionalComponent1(__VLS_156, new __VLS_156(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.tax), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_158 = __VLS_157.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.tax), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_157), false));
    var __VLS_161 = void 0;
    var __VLS_162 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_5);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_159;
    var __VLS_160;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-16" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    var __VLS_163 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.sss), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.sss), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_164), false));
    var __VLS_168 = void 0;
    var __VLS_169 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_5);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_166;
    var __VLS_167;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-16" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    var __VLS_170 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.philhealth), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.philhealth), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_171), false));
    var __VLS_175 = void 0;
    var __VLS_176 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_5);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_173;
    var __VLS_174;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-16" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    var __VLS_177 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.pagibig), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_5.governmentDeductions.pagibig), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_178), false));
    var __VLS_182 = void 0;
    var __VLS_183 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_5);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_180;
    var __VLS_181;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_152;
var __VLS_184;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_185 = __VLS_asFunctionalComponent1(__VLS_184, new __VLS_184(__assign({ class: "text-xs" }, { field: "lateDeductions", header: "Late", sortable: true })));
var __VLS_186 = __VLS_185.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "lateDeductions", header: "Late", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_185), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_189 = __VLS_187.slots.default;
{
    var __VLS_190 = __VLS_187.slots.body;
    var data_6 = __VLS_vSlot(__VLS_190)[0].data;
    var __VLS_191 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_192 = __VLS_asFunctionalComponent1(__VLS_191, new __VLS_191(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_6.lateDeductions), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_193 = __VLS_192.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_6.lateDeductions), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_192), false));
    var __VLS_196 = void 0;
    var __VLS_197 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_6);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_194;
    var __VLS_195;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_187;
var __VLS_198;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198(__assign({ class: "text-xs" }, { field: "leaveDeductions", header: "Leave", sortable: true })));
var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "leaveDeductions", header: "Leave", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_199), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_203 = __VLS_201.slots.default;
{
    var __VLS_204 = __VLS_201.slots.body;
    var data_7 = __VLS_vSlot(__VLS_204)[0].data;
    var __VLS_205 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_206 = __VLS_asFunctionalComponent1(__VLS_205, new __VLS_205(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_7.leaveDeductions), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_207 = __VLS_206.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_7.leaveDeductions), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_206), false));
    var __VLS_210 = void 0;
    var __VLS_211 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_7);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_208;
    var __VLS_209;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_201;
var __VLS_212;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_213 = __VLS_asFunctionalComponent1(__VLS_212, new __VLS_212(__assign({ class: "text-xs" }, { field: "bonusPay", header: "Bonus Pay", sortable: true })));
var __VLS_214 = __VLS_213.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "bonusPay", header: "Bonus Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_213), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_217 = __VLS_215.slots.default;
{
    var __VLS_218 = __VLS_215.slots.body;
    var data_8 = __VLS_vSlot(__VLS_218)[0].data;
    var __VLS_219 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_220 = __VLS_asFunctionalComponent1(__VLS_219, new __VLS_219(__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_8.bonusPay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })));
    var __VLS_221 = __VLS_220.apply(void 0, __spreadArray([__assign({ 'onBlur': {} }, { size: "small", fluid: true, modelValue: (data_8.bonusPay), mode: "currency", currency: "PHP", locale: "en-PH", min: (0) })], __VLS_functionalComponentArgsRest(__VLS_220), false));
    var __VLS_224 = void 0;
    var __VLS_225 = ({ blur: {} },
        { onBlur: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.recalculateTotals(data_8);
                // @ts-ignore
                [recalculateTotals,];
            } });
    var __VLS_222;
    var __VLS_223;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_215;
var __VLS_226;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226(__assign({ class: "text-xs" }, { field: "grossPay", header: "Gross Pay", sortable: true })));
var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "grossPay", header: "Gross Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_227), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_231 = __VLS_229.slots.default;
{
    var __VLS_232 = __VLS_229.slots.body;
    var data = __VLS_vSlot(__VLS_232)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.formatCurrency(data.grossPay));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_229;
var __VLS_233;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_234 = __VLS_asFunctionalComponent1(__VLS_233, new __VLS_233(__assign({ class: "text-xs" }, { field: "totalDeductions", header: "Total Deductions", sortable: true })));
var __VLS_235 = __VLS_234.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "totalDeductions", header: "Total Deductions", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_234), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_238 = __VLS_236.slots.default;
{
    var __VLS_239 = __VLS_236.slots.body;
    var data = __VLS_vSlot(__VLS_239)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatCurrency(data.totalDeductions));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_236;
var __VLS_240;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_241 = __VLS_asFunctionalComponent1(__VLS_240, new __VLS_240(__assign({ class: "text-xs" }, { field: "netPay", header: "Net Pay", sortable: true })));
var __VLS_242 = __VLS_241.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "netPay", header: "Net Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_241), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_245 = __VLS_243.slots.default;
{
    var __VLS_246 = __VLS_243.slots.body;
    var data = __VLS_vSlot(__VLS_246)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatCurrency(data.netPay));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_243;
var __VLS_247;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247(__assign({ class: "text-xs" }, { field: "status", header: "Status", sortable: true })));
var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "status", header: "Status", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_248), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_252 = __VLS_250.slots.default;
{
    var __VLS_253 = __VLS_250.slots.body;
    var data = __VLS_vSlot(__VLS_253)[0].data;
    var __VLS_254 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_255 = __VLS_asFunctionalComponent1(__VLS_254, new __VLS_254({
        severity: (__VLS_ctx.getStatusSeverity(data.status)),
        value: (data.status),
    }));
    var __VLS_256 = __VLS_255.apply(void 0, __spreadArray([{
            severity: (__VLS_ctx.getStatusSeverity(data.status)),
            value: (data.status),
        }], __VLS_functionalComponentArgsRest(__VLS_255), false));
    // @ts-ignore
    [getStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_250;
var __VLS_259;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_260 = __VLS_asFunctionalComponent1(__VLS_259, new __VLS_259(__assign(__assign({ class: "text-xs" }, { header: "Actions" }), { style: {} })));
var __VLS_261 = __VLS_260.apply(void 0, __spreadArray([__assign(__assign({ class: "text-xs" }, { header: "Actions" }), { style: {} })], __VLS_functionalComponentArgsRest(__VLS_260), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_264 = __VLS_262.slots.default;
{
    var __VLS_265 = __VLS_262.slots.body;
    var data_9 = __VLS_vSlot(__VLS_265)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_266 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_267 = __VLS_asFunctionalComponent1(__VLS_266, new __VLS_266(__assign({ 'onClick': {} }, { icon: "pi pi-save", severity: "success", text: true, size: "small", loading: (data_9.saving), disabled: (data_9.status !== 'draft') })));
    var __VLS_268 = __VLS_267.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-save", severity: "success", text: true, size: "small", loading: (data_9.saving), disabled: (data_9.status !== 'draft') })], __VLS_functionalComponentArgsRest(__VLS_267), false));
    var __VLS_271 = void 0;
    var __VLS_272 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.savePayrollItem(data_9);
                // @ts-ignore
                [savePayrollItem,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Save changes') }), null, null);
    var __VLS_269;
    var __VLS_270;
    var __VLS_273 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_274 = __VLS_asFunctionalComponent1(__VLS_273, new __VLS_273(__assign({ 'onClick': {} }, { icon: "pi pi-send", severity: "info", text: true, size: "small", loading: (data_9.submitting), disabled: (data_9.status !== 'draft') })));
    var __VLS_275 = __VLS_274.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-send", severity: "info", text: true, size: "small", loading: (data_9.submitting), disabled: (data_9.status !== 'draft') })], __VLS_functionalComponentArgsRest(__VLS_274), false));
    var __VLS_278 = void 0;
    var __VLS_279 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.submitForApproval(data_9);
                // @ts-ignore
                [vTooltip, submitForApproval,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Submit for approval') }), null, null);
    var __VLS_276;
    var __VLS_277;
    var __VLS_280 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_281 = __VLS_asFunctionalComponent1(__VLS_280, new __VLS_280(__assign({ 'onClick': {} }, { icon: "pi pi-print", severity: "secondary", text: true, size: "small" })));
    var __VLS_282 = __VLS_281.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-print", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_281), false));
    var __VLS_285 = void 0;
    var __VLS_286 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.printPayslip(data_9);
                // @ts-ignore
                [vTooltip, printPayslip,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Print payslip') }), null, null);
    var __VLS_283;
    var __VLS_284;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_262;
{
    var __VLS_287 = __VLS_61.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file text-4xl mb-2 block" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_61;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeProps: {},
});
exports.default = {};
