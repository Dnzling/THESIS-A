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
var lodash_1 = require("lodash");
var props = defineProps();
// ==================== STATE ====================
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
var loading = (0, vue_1.ref)(false);
var bulkSubmitting = (0, vue_1.ref)(false);
var selectedItems = (0, vue_1.ref)([]);
// Get batch ID from route params if not passed as prop
var batchId = (0, vue_1.computed)(function () { return props.batchId || route.params.id; });
// Data
var payrollItems = (0, vue_1.ref)([]);
var batchInfo = (0, vue_1.ref)(null);
var statistics = (0, vue_1.ref)({
    totalEmployees: 0,
    totalGross: 0,
    totalDeductions: 0,
    totalNet: 0,
    byDepartment: {},
    byStatus: {}
});
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    branch: null,
    department: null,
    status: null
});
// Options for filters
var branches = (0, vue_1.ref)([]);
var departments = (0, vue_1.ref)([]);
var statusOptions = (0, vue_1.ref)(['draft', 'calculated', 'processing', 'approved', 'paid', 'cancelled']);
// ==================== COMPUTED ====================
var hasDraftPayrolls = (0, vue_1.computed)(function () {
    return payrollItems.value.some(function (i) { return i.status === 'draft' || i.status === 'calculated'; });
});
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
// ==================== METHODS ====================
var fetchPayrollData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!batchId.value)
                    return [2 /*return*/];
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 5, 6, 7]);
                // Set auth token
                axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
                return [4 /*yield*/, axios_1.default.get('/api/payroll', {
                        params: {
                            pay_period_id: batchId.value
                        }
                    })];
            case 2:
                response = _a.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                // Transform API data to match component interface
                payrollItems.value = transformPayrollData(response.data.data);
                // Extract unique branches and departments for filters
                extractFilterOptions(payrollItems.value);
                // Calculate statistics
                calculateStatistics(payrollItems.value);
                // Fetch batch info separately if needed
                return [4 /*yield*/, fetchBatchInfo()];
            case 3:
                // Fetch batch info separately if needed
                _a.sent();
                _a.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_1 = _a.sent();
                console.error('Failed to fetch payroll data:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to fetch payroll data',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 6:
                loading.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var fetchBatchInfo = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get("/api/payroll/pay-periods/".concat(batchId.value))];
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
        // Calculate government deductions breakdown
        // This assumes you have these values in your API response
        // You may need to adjust based on your actual data structure
        var _a, _b, _c, _d;
        var governmentDeductions = {
            tax: item.tax_amount || 0,
            sss: item.deductions_total ? item.deductions_total * 0.4 : 0, // Example split
            philhealth: item.deductions_total ? item.deductions_total * 0.3 : 0,
            pagibig: item.deductions_total ? item.deductions_total * 0.1 : 0
        };
        var grossPay = [
            item.base_salary,
            item.overtime_amount,
            item.bonuses_total,
            item.allowances_total
        ].reduce(function (sum, value) { return sum + (parseFloat(value) || 0); }, 0);
        return {
            id: ((_a = item.id) === null || _a === void 0 ? void 0 : _a.toString()) || '',
            employeeId: ((_b = item.employee) === null || _b === void 0 ? void 0 : _b.employee_number) || '',
            employeeName: item.employee ? "".concat(item.employee.fname, " ").concat(item.employee.lname) : '',
            branch: ((_c = item.employee) === null || _c === void 0 ? void 0 : _c.branch) || 'N/A',
            department: ((_d = item.employee) === null || _d === void 0 ? void 0 : _d.department) || 'N/A',
            baseSalary: item.base_salary || 0,
            salaryPerHour: item.base_salary ? item.base_salary / 160 : 0,
            basicPay: item.base_salary || 0,
            overtimePay: item.overtime_amount || 0,
            allowanceAmount: item.allowances_total || 0,
            governmentDeductions: governmentDeductions,
            lateDeductions: 0, // You'll need to calculate this from attendance
            leaveDeductions: 0, // You'll need to calculate this from leaves
            otherDeductions: 0,
            grossPay: grossPay,
            totalDeductions: item.deductions_total || 0,
            netPay: item.net_salary || 0,
            status: item.status || 'draft',
            payroll_id: item.id
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
var calculateStatistics = function (items) {
    var totalGross = items.reduce(function (sum, item) { return sum + item.grossPay; }, 0);
    var totalDeductions = items.reduce(function (sum, item) { return sum + item.totalDeductions; }, 0);
    var totalNet = items.reduce(function (sum, item) { return sum + item.netPay; }, 0);
    // Group by department
    var byDepartment = {};
    items.forEach(function (item) {
        var dept = item.department || 'Unassigned';
        if (!byDepartment[dept]) {
            byDepartment[dept] = {
                count: 0,
                totalNet: 0
            };
        }
        byDepartment[dept].count++;
        byDepartment[dept].totalNet += item.netPay;
    });
    // Group by status
    var byStatus = {};
    items.forEach(function (item) {
        if (!byStatus[item.status]) {
            byStatus[item.status] = {
                count: 0,
                totalNet: 0
            };
        }
        byStatus[item.status].count++;
        byStatus[item.status].totalNet += item.netPay;
    });
    statistics.value = {
        totalEmployees: items.length,
        totalGross: totalGross,
        totalDeductions: totalDeductions,
        totalNet: totalNet,
        byDepartment: byDepartment,
        byStatus: byStatus
    };
};
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit'
    }).format(new Date(date));
};
var formatCurrency = function (value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
};
var getStatusSeverity = function (status) {
    var map = {
        'draft': 'secondary',
        'calculated': 'secondary',
        'processing': 'warn',
        'approved': 'success',
        'paid': 'success',
        'cancelled': 'danger',
        // pay period statuses
        'locked': 'info',
        'completed': 'success',
    };
    return map[status] || 'info';
};
var debouncedFetch = (0, lodash_1.debounce)(function () {
    // Client-side filtering only, no need to refetch
}, 300);
var applyFilters = function () {
    // Client-side filtering handled by computed property
    toast.add({
        severity: 'info',
        summary: 'Filters Applied',
        detail: "Showing ".concat(filteredPayrollItems.value.length, " of ").concat(payrollItems.value.length, " employees"),
        life: 3000
    });
};
var goBack = function () {
    router.push({ name: 'hr.payroll.list' });
};
var submitForApproval = function (item) { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                ;
                item.submitting = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 4, 5, 6]);
                return [4 /*yield*/, axios_1.default.post("/api/payroll/".concat(item.payroll_id, "/submit"))];
            case 2:
                _c.sent();
                item.status = 'processing';
                calculateStatistics(payrollItems.value);
                // Refresh batch info so period status badge updates
                return [4 /*yield*/, fetchBatchInfo()];
            case 3:
                // Refresh batch info so period status badge updates
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Submitted',
                    detail: "".concat(item.employeeName, "'s payroll submitted for approval"),
                    life: 3000
                });
                return [3 /*break*/, 6];
            case 4:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3 === null || error_3 === void 0 ? void 0 : error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to submit for approval',
                    life: 3000
                });
                return [3 /*break*/, 6];
            case 5:
                ;
                item.submitting = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
var bulkSubmitForApproval = function () { return __awaiter(void 0, void 0, void 0, function () {
    var eligibleItems, ids, response, error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (selectedItems.value.length === 0)
                    return [2 /*return*/];
                eligibleItems = selectedItems.value.filter(function (i) { return i.status === 'draft' || i.status === 'calculated'; });
                if (eligibleItems.length === 0) {
                    toast.add({
                        severity: 'warn',
                        summary: 'No Eligible Items',
                        detail: 'Selected payrolls must be in draft status to submit for approval',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                bulkSubmitting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 5, 6, 7]);
                ids = eligibleItems.map(function (i) { return i.payroll_id; }).filter(Boolean);
                return [4 /*yield*/, axios_1.default.post('/api/payroll/bulk-submit', { payroll_ids: ids })];
            case 2:
                response = _c.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                // Update local statuses
                eligibleItems.forEach(function (item) { item.status = 'processing'; });
                calculateStatistics(payrollItems.value);
                selectedItems.value = [];
                // Refresh batch info so period status badge updates
                return [4 /*yield*/, fetchBatchInfo()];
            case 3:
                // Refresh batch info so period status badge updates
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Submitted',
                    detail: response.data.message,
                    life: 3000
                });
                _c.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4 === null || error_4 === void 0 ? void 0 : error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to bulk submit payrolls',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 6:
                bulkSubmitting.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var printPayslip = function (item) {
    var _a;
    var period = batchInfo.value;
    var html = "\n    <!DOCTYPE html>\n    <html>\n    <head>\n      <title>Payslip - ".concat(item.employeeName, "</title>\n      <style>\n        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #333; }\n        h2 { text-align: center; margin-bottom: 4px; }\n        .subtitle { text-align: center; color: #666; margin-bottom: 16px; font-size: 11px; }\n        .section { margin-bottom: 12px; }\n        .section-title { font-weight: bold; background: #f0f0f0; padding: 4px 8px; border-left: 3px solid #333; margin-bottom: 6px; }\n        table { width: 100%; border-collapse: collapse; }\n        td { padding: 4px 8px; }\n        td:last-child { text-align: right; }\n        .divider { border-top: 1px solid #ccc; margin: 8px 0; }\n        .total-row td { font-weight: bold; border-top: 2px solid #333; }\n        .net-row td { font-weight: bold; font-size: 14px; background: #e8f5e9; }\n        .header-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; font-size: 11px; }\n        .header-grid div { padding: 2px 0; }\n        .label { color: #666; }\n        @media print { body { margin: 10px; } }\n      </style>\n    </head>\n    <body>\n      <h2>PAYSLIP</h2>\n      <div class=\"subtitle\">").concat((_a = period === null || period === void 0 ? void 0 : period.name) !== null && _a !== void 0 ? _a : '', " &nbsp;|&nbsp; ").concat(period ? formatDate(period.start_date) + ' – ' + formatDate(period.end_date) : '', "</div>\n\n      <div class=\"header-grid\">\n        <div><span class=\"label\">Employee:</span> <strong>").concat(item.employeeName, "</strong></div>\n        <div><span class=\"label\">Employee #:</span> ").concat(item.employeeId, "</div>\n        <div><span class=\"label\">Department:</span> ").concat(item.department, "</div>\n        <div><span class=\"label\">Branch:</span> ").concat(item.branch, "</div>\n        <div><span class=\"label\">Pay Date:</span> ").concat(period ? formatDate(period.pay_date) : 'N/A', "</div>\n        <div><span class=\"label\">Status:</span> ").concat(item.status.toUpperCase(), "</div>\n      </div>\n\n      <div class=\"section\">\n        <div class=\"section-title\">EARNINGS</div>\n        <table>\n          <tr><td>Basic Salary</td><td>").concat(formatCurrency(item.baseSalary), "</td></tr>\n          <tr><td>Overtime Pay</td><td>").concat(formatCurrency(item.overtimePay), "</td></tr>\n          <tr><td>Allowances</td><td>").concat(formatCurrency(item.allowanceAmount), "</td></tr>\n          <tr class=\"total-row\"><td>Gross Pay</td><td>").concat(formatCurrency(item.grossPay), "</td></tr>\n        </table>\n      </div>\n\n      <div class=\"section\">\n        <div class=\"section-title\">DEDUCTIONS</div>\n        <table>\n          <tr><td>Income Tax</td><td>- ").concat(formatCurrency(item.governmentDeductions.tax), "</td></tr>\n          <tr><td>SSS</td><td>- ").concat(formatCurrency(item.governmentDeductions.sss), "</td></tr>\n          <tr><td>PhilHealth</td><td>- ").concat(formatCurrency(item.governmentDeductions.philhealth), "</td></tr>\n          <tr><td>Pag-IBIG</td><td>- ").concat(formatCurrency(item.governmentDeductions.pagibig), "</td></tr>\n          <tr><td>Late Deductions</td><td>- ").concat(formatCurrency(item.lateDeductions), "</td></tr>\n          <tr><td>Leave Deductions</td><td>- ").concat(formatCurrency(item.leaveDeductions), "</td></tr>\n          <tr class=\"total-row\"><td>Total Deductions</td><td>- ").concat(formatCurrency(item.totalDeductions), "</td></tr>\n        </table>\n      </div>\n\n      <div class=\"section\">\n        <table>\n          <tr class=\"net-row\"><td>NET PAY</td><td>").concat(formatCurrency(item.netPay), "</td></tr>\n        </table>\n      </div>\n\n      <div style=\"margin-top:40px; display:grid; grid-template-columns:1fr 1fr; gap:20px; font-size:11px;\">\n        <div style=\"border-top:1px solid #333; padding-top:4px; text-align:center;\">Prepared by</div>\n        <div style=\"border-top:1px solid #333; padding-top:4px; text-align:center;\">Received by</div>\n      </div>\n\n      <div style=\"margin-top:30px; text-align:center; font-size:10px; color:#999;\">\n        Printed on: ").concat(new Date().toLocaleString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    }), "\n      </div>\n    </body>\n    </html>\n  ");
    var win = window.open('', '_blank', 'width=700,height=900');
    if (win) {
        win.document.write(html);
        win.document.close();
        win.focus();
        setTimeout(function () { return win.print(); }, 500);
    }
};
var exportPayroll = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, url, link, error_5;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                _b.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get("/api/payroll/pay-periods/".concat(batchId.value, "/export"), {
                        responseType: 'blob'
                    })];
            case 1:
                response = _b.sent();
                url = window.URL.createObjectURL(new Blob([response.data]));
                link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', "payroll_".concat((_a = batchInfo.value) === null || _a === void 0 ? void 0 : _a.name.replace(/\s+/g, '_'), ".csv"));
                document.body.appendChild(link);
                link.click();
                link.remove();
                toast.add({
                    severity: 'success',
                    summary: 'Exported',
                    detail: 'Payroll exported successfully',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 2:
                error_5 = _b.sent();
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
// ==================== WATCHERS ====================
(0, vue_1.watch)(function () { return batchId.value; }, function (newId) {
    if (newId) {
        fetchPayrollData();
    }
});
// ==================== LIFECYCLE ====================
(0, vue_1.onMounted)(function () {
    if (batchId.value) {
        fetchPayrollData();
    }
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "payroll-list p-4" }));
/** @type {__VLS_StyleScopedClasses['payroll-list']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
if (__VLS_ctx.batchInfo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4 p-3 bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
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
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ 'onClick': {} }, { label: "Back to Batches", icon: "pi pi-arrow-left", text: true })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back to Batches", icon: "pi pi-arrow-left", text: true })], __VLS_functionalComponentArgsRest(__VLS_6), false));
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
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ iconPosition: "left" }, { class: "flex-1" })));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ iconPosition: "left" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
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
var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }), { class: "w-full" })));
var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
var __VLS_29;
var __VLS_30 = ({ input: {} },
    { onInput: (__VLS_ctx.debouncedFetch) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
var __VLS_27;
var __VLS_28;
// @ts-ignore
[filters, debouncedFetch,];
var __VLS_15;
var __VLS_31;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }), { class: "w-48" })));
var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }), { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_32), false));
var __VLS_36;
var __VLS_37 = ({ change: {} },
    { onChange: (__VLS_ctx.applyFilters) });
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_34;
var __VLS_35;
var __VLS_38;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }), { class: "w-48" })));
var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }), { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_39), false));
var __VLS_43;
var __VLS_44 = ({ change: {} },
    { onChange: (__VLS_ctx.applyFilters) });
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_41;
var __VLS_42;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }), { class: "w-48" })));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }), { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_46), false));
var __VLS_50;
var __VLS_51 = ({ change: {} },
    { onChange: (__VLS_ctx.applyFilters) });
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_48;
var __VLS_49;
if (__VLS_ctx.hasDraftPayrolls) {
    var __VLS_52 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign({ 'onClick': {} }, { label: "Bulk Submit for Approval", icon: "pi pi-send", severity: "info", outlined: true, disabled: (__VLS_ctx.selectedItems.length === 0 || __VLS_ctx.loading), loading: (__VLS_ctx.bulkSubmitting) })));
    var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Submit for Approval", icon: "pi pi-send", severity: "info", outlined: true, disabled: (__VLS_ctx.selectedItems.length === 0 || __VLS_ctx.loading), loading: (__VLS_ctx.bulkSubmitting) })], __VLS_functionalComponentArgsRest(__VLS_53), false));
    var __VLS_57 = void 0;
    var __VLS_58 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkSubmitForApproval) });
    var __VLS_55;
    var __VLS_56;
}
var __VLS_59;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-file-excel", severity: "success", outlined: true, disabled: (__VLS_ctx.loading || __VLS_ctx.payrollItems.length === 0) })));
var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-file-excel", severity: "success", outlined: true, disabled: (__VLS_ctx.loading || __VLS_ctx.payrollItems.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_60), false));
var __VLS_64;
var __VLS_65 = ({ click: {} },
    { onClick: (__VLS_ctx.exportPayroll) });
var __VLS_62;
var __VLS_63;
var __VLS_66;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66({
    value: (__VLS_ctx.filteredPayrollItems),
    paginator: (true),
    rows: (10),
    rowsPerPageOptions: ([10, 20, 50]),
    tableStyle: "min-width: 110rem",
    loading: (__VLS_ctx.loading),
    removableSort: true,
    paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
    currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries",
    sortMode: "multiple",
    rowHover: true,
    selection: (__VLS_ctx.selectedItems),
    selectionMode: "multiple",
}));
var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.filteredPayrollItems),
        paginator: (true),
        rows: (10),
        rowsPerPageOptions: ([10, 20, 50]),
        tableStyle: "min-width: 110rem",
        loading: (__VLS_ctx.loading),
        removableSort: true,
        paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
        currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries",
        sortMode: "multiple",
        rowHover: true,
        selection: (__VLS_ctx.selectedItems),
        selectionMode: "multiple",
    }], __VLS_functionalComponentArgsRest(__VLS_67), false));
var __VLS_71 = __VLS_69.slots.default;
var __VLS_72;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72({
    selectionMode: "multiple",
    headerStyle: "width: 3rem",
}));
var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([{
        selectionMode: "multiple",
        headerStyle: "width: 3rem",
    }], __VLS_functionalComponentArgsRest(__VLS_73), false));
var __VLS_77;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77(__assign({ class: "text-xs" }, { field: "employeeName", header: "Employee", sortable: true })));
var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "employeeName", header: "Employee", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_78), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_82 = __VLS_80.slots.default;
{
    var __VLS_83 = __VLS_80.slots.body;
    var data = __VLS_vSlot(__VLS_83)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.employeeName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (data.employeeId);
    // @ts-ignore
    [filters, filters, filters, branches, applyFilters, applyFilters, applyFilters, departments, statusOptions, hasDraftPayrolls, selectedItems, selectedItems, loading, loading, loading, bulkSubmitting, bulkSubmitForApproval, payrollItems, exportPayroll, filteredPayrollItems,];
}
// @ts-ignore
[];
var __VLS_80;
var __VLS_84;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign({ class: "text-xs" }, { field: "branch", header: "Branch", sortable: true })));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "branch", header: "Branch", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_85), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_89 = __VLS_87.slots.default;
{
    var __VLS_90 = __VLS_87.slots.body;
    var data = __VLS_vSlot(__VLS_90)[0].data;
    (data.branch || 'N/A');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_87;
var __VLS_91;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91(__assign({ class: "text-xs" }, { field: "department", header: "Department", sortable: true })));
var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "department", header: "Department", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_92), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_96 = __VLS_94.slots.default;
{
    var __VLS_97 = __VLS_94.slots.body;
    var data = __VLS_vSlot(__VLS_97)[0].data;
    (data.department || 'N/A');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_94;
var __VLS_98;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98(__assign({ class: "text-xs" }, { field: "baseSalary", header: "Base Salary", sortable: true })));
var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "baseSalary", header: "Base Salary", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_99), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_103 = __VLS_101.slots.default;
{
    var __VLS_104 = __VLS_101.slots.body;
    var data = __VLS_vSlot(__VLS_104)[0].data;
    (__VLS_ctx.formatCurrency(data.baseSalary));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_101;
var __VLS_105;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105(__assign({ class: "text-xs" }, { field: "salaryPerHour", header: "Rate/Hour", sortable: true })));
var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "salaryPerHour", header: "Rate/Hour", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_106), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_110 = __VLS_108.slots.default;
{
    var __VLS_111 = __VLS_108.slots.body;
    var data = __VLS_vSlot(__VLS_111)[0].data;
    (__VLS_ctx.formatCurrency(data.salaryPerHour));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_108;
var __VLS_112;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_113 = __VLS_asFunctionalComponent1(__VLS_112, new __VLS_112(__assign({ class: "text-xs" }, { field: "overtimePay", header: "OT Pay", sortable: true })));
var __VLS_114 = __VLS_113.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "overtimePay", header: "OT Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_113), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_117 = __VLS_115.slots.default;
{
    var __VLS_118 = __VLS_115.slots.body;
    var data = __VLS_vSlot(__VLS_118)[0].data;
    (__VLS_ctx.formatCurrency(data.overtimePay));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_115;
var __VLS_119;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119(__assign({ class: "text-xs" }, { field: "allowanceAmount", header: "Allowance", sortable: true })));
var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "allowanceAmount", header: "Allowance", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_120), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_124 = __VLS_122.slots.default;
{
    var __VLS_125 = __VLS_122.slots.body;
    var data = __VLS_vSlot(__VLS_125)[0].data;
    (__VLS_ctx.formatCurrency(data.allowanceAmount));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_122;
var __VLS_126;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_127 = __VLS_asFunctionalComponent1(__VLS_126, new __VLS_126(__assign({ class: "text-xs font-semibold" }, { header: "Gov't Deductions" })));
var __VLS_128 = __VLS_127.apply(void 0, __spreadArray([__assign({ class: "text-xs font-semibold" }, { header: "Gov't Deductions" })], __VLS_functionalComponentArgsRest(__VLS_127), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
var __VLS_131 = __VLS_129.slots.default;
{
    var __VLS_132 = __VLS_129.slots.body;
    var data = __VLS_vSlot(__VLS_132)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatCurrency(data.governmentDeductions.tax));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatCurrency(data.governmentDeductions.sss));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatCurrency(data.governmentDeductions.philhealth));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatCurrency(data.governmentDeductions.pagibig));
    // @ts-ignore
    [formatCurrency, formatCurrency, formatCurrency, formatCurrency,];
}
// @ts-ignore
[];
var __VLS_129;
var __VLS_133;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133(__assign({ class: "text-xs" }, { field: "lateDeductions", header: "Late", sortable: true })));
var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "lateDeductions", header: "Late", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_134), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_138 = __VLS_136.slots.default;
{
    var __VLS_139 = __VLS_136.slots.body;
    var data = __VLS_vSlot(__VLS_139)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatCurrency(data.lateDeductions));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_136;
var __VLS_140;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign({ class: "text-xs" }, { field: "leaveDeductions", header: "Leave", sortable: true })));
var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "leaveDeductions", header: "Leave", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_141), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_145 = __VLS_143.slots.default;
{
    var __VLS_146 = __VLS_143.slots.body;
    var data = __VLS_vSlot(__VLS_146)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatCurrency(data.leaveDeductions));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_143;
var __VLS_147;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_148 = __VLS_asFunctionalComponent1(__VLS_147, new __VLS_147(__assign({ class: "text-xs" }, { field: "grossPay", header: "Gross Pay", sortable: true })));
var __VLS_149 = __VLS_148.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "grossPay", header: "Gross Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_148), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_152 = __VLS_150.slots.default;
{
    var __VLS_153 = __VLS_150.slots.body;
    var data = __VLS_vSlot(__VLS_153)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.formatCurrency(data.grossPay));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_150;
var __VLS_154;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_155 = __VLS_asFunctionalComponent1(__VLS_154, new __VLS_154(__assign({ class: "text-xs" }, { field: "totalDeductions", header: "Total Deductions", sortable: true })));
var __VLS_156 = __VLS_155.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "totalDeductions", header: "Total Deductions", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_155), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_159 = __VLS_157.slots.default;
{
    var __VLS_160 = __VLS_157.slots.body;
    var data = __VLS_vSlot(__VLS_160)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    (__VLS_ctx.formatCurrency(data.totalDeductions));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_157;
var __VLS_161;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_162 = __VLS_asFunctionalComponent1(__VLS_161, new __VLS_161(__assign({ class: "text-xs" }, { field: "netPay", header: "Net Pay", sortable: true })));
var __VLS_163 = __VLS_162.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "netPay", header: "Net Pay", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_162), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_166 = __VLS_164.slots.default;
{
    var __VLS_167 = __VLS_164.slots.body;
    var data = __VLS_vSlot(__VLS_167)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatCurrency(data.netPay));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_164;
var __VLS_168;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_169 = __VLS_asFunctionalComponent1(__VLS_168, new __VLS_168(__assign({ class: "text-xs" }, { field: "status", header: "Status", sortable: true })));
var __VLS_170 = __VLS_169.apply(void 0, __spreadArray([__assign({ class: "text-xs" }, { field: "status", header: "Status", sortable: true })], __VLS_functionalComponentArgsRest(__VLS_169), false));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_173 = __VLS_171.slots.default;
{
    var __VLS_174 = __VLS_171.slots.body;
    var data = __VLS_vSlot(__VLS_174)[0].data;
    var __VLS_175 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175(__assign({ severity: (__VLS_ctx.getStatusSeverity(data.status)), value: (data.status) }, { class: "capitalize" })));
    var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([__assign({ severity: (__VLS_ctx.getStatusSeverity(data.status)), value: (data.status) }, { class: "capitalize" })], __VLS_functionalComponentArgsRest(__VLS_176), false));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    // @ts-ignore
    [getStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_171;
var __VLS_180;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180(__assign({ header: "Actions" }, { style: {} })));
var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_181), false));
var __VLS_185 = __VLS_183.slots.default;
{
    var __VLS_186 = __VLS_183.slots.body;
    var data_1 = __VLS_vSlot(__VLS_186)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    if (data_1.status === 'draft' || data_1.status === 'calculated') {
        var __VLS_187 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_188 = __VLS_asFunctionalComponent1(__VLS_187, new __VLS_187(__assign({ 'onClick': {} }, { icon: "pi pi-send", severity: "info", text: true, loading: (data_1.submitting) })));
        var __VLS_189 = __VLS_188.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-send", severity: "info", text: true, loading: (data_1.submitting) })], __VLS_functionalComponentArgsRest(__VLS_188), false));
        var __VLS_192 = void 0;
        var __VLS_193 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(data_1.status === 'draft' || data_1.status === 'calculated'))
                        return;
                    __VLS_ctx.submitForApproval(data_1);
                    // @ts-ignore
                    [submitForApproval,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Submit for approval') }), null, null);
        var __VLS_190;
        var __VLS_191;
    }
    var __VLS_194 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_195 = __VLS_asFunctionalComponent1(__VLS_194, new __VLS_194(__assign({ 'onClick': {} }, { icon: "pi pi-print", severity: "secondary", text: true })));
    var __VLS_196 = __VLS_195.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-print", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_195), false));
    var __VLS_199 = void 0;
    var __VLS_200 = ({ click: {} },
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
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Print payslip') }), null, null);
    var __VLS_197;
    var __VLS_198;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_183;
{
    var __VLS_201 = __VLS_69.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file text-4xl mb-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_69;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeProps: {},
});
exports.default = {};
