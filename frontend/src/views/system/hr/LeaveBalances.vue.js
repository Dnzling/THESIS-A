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
var button_1 = require("primevue/button");
var avatar_1 = require("primevue/avatar");
var tag_1 = require("primevue/tag");
var progressbar_1 = require("primevue/progressbar");
var dialog_1 = require("primevue/dialog");
var select_1 = require("primevue/select");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var radiobutton_1 = require("primevue/radiobutton");
var paginator_1 = require("primevue/paginator");
// State
var showAdjustModal = (0, vue_1.ref)(false);
var showDetailsModal = (0, vue_1.ref)(false);
var showHistoryModal = (0, vue_1.ref)(false);
var selectedEmployee = (0, vue_1.ref)(null);
var paginationOffset = (0, vue_1.ref)(0);
var pageSize = (0, vue_1.ref)(10);
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    department: null,
    balance: null
});
// Form
var adjustForm = (0, vue_1.ref)({
    employeeId: null,
    leaveType: null,
    adjustmentType: 'add',
    days: 0,
    reason: ''
});
// Mock Data
var employees = (0, vue_1.ref)([
    {
        id: 1,
        employeeId: 'EMP-001',
        name: 'John Cruz',
        department: 'Production',
        balances: { Annual: 15, Sick: 8, Emergency: 3 },
        totalUsed: 4,
        totalQuota: 26,
        carryOver: 2
    },
    {
        id: 2,
        employeeId: 'EMP-002',
        name: 'Maria Santos',
        department: 'Warehouse',
        balances: { Annual: 6, Sick: 2, Emergency: 1 },
        totalUsed: 11,
        totalQuota: 20
    },
    {
        id: 3,
        employeeId: 'EMP-003',
        name: 'Carlos Lim',
        department: 'Sales',
        balances: { Annual: 2, Sick: 0, Emergency: 0 },
        totalUsed: 18,
        totalQuota: 20
    },
    {
        id: 4,
        employeeId: 'EMP-004',
        name: 'Anna Reyes',
        department: 'Finance',
        balances: { Annual: 12, Sick: 4, Emergency: 2 },
        totalUsed: 2,
        totalQuota: 18
    },
    {
        id: 5,
        employeeId: 'EMP-005',
        name: 'Michael Tan',
        department: 'IT',
        balances: { Annual: 18, Sick: 5, Emergency: 3 },
        totalUsed: 4,
        totalQuota: 26
    }
]);
var historyData = (0, vue_1.ref)([
    {
        id: 1,
        employeeId: 1,
        action: 'Leave Approved',
        type: 'Annual',
        days: 3,
        date: '2024-12-10',
        details: 'Annual leave approved for Dec 15-17',
        performedBy: 'HR Manager',
        icon: 'pi pi-check-circle'
    },
    {
        id: 2,
        employeeId: 1,
        action: 'Balance Adjusted',
        type: 'Annual',
        days: 2,
        date: '2024-11-15',
        details: 'Carry over from previous year',
        performedBy: 'Admin',
        icon: 'pi pi-sliders-h'
    }
]);
// Options
var departments = ['All Departments', 'Production', 'Warehouse', 'Sales', 'Finance', 'IT', 'HR'];
var leaveTypes = ['Annual', 'Sick', 'Emergency'];
var balanceFilters = [
    { label: 'All Balances', value: null },
    { label: 'Low (0-5 days)', value: 'low' },
    { label: 'Medium (6-15 days)', value: 'medium' },
    { label: 'High (16+ days)', value: 'high' }
];
var leaveTypeOptions = [
    { label: 'Annual Leave', value: 'Annual' },
    { label: 'Sick Leave', value: 'Sick' },
    { label: 'Emergency Leave', value: 'Emergency' }
];
// Computed
var employeeCount = (0, vue_1.computed)(function () { return employees.value.length; });
var averageRemaining = (0, vue_1.computed)(function () {
    var total = employees.value.reduce(function (sum, emp) {
        var remaining = Object.values(emp.balances).reduce(function (a, b) { return a + b; }, 0);
        return sum + remaining;
    }, 0);
    return Math.round(total / employees.value.length);
});
var lowBalanceCount = (0, vue_1.computed)(function () {
    return employees.value.filter(function (emp) {
        var total = Object.values(emp.balances).reduce(function (a, b) { return a + b; }, 0);
        return total < 5;
    }).length;
});
var totalUsed = (0, vue_1.computed)(function () {
    return employees.value.reduce(function (sum, emp) { return sum + emp.totalUsed; }, 0);
});
var filteredBalances = (0, vue_1.computed)(function () {
    var filtered = __spreadArray([], employees.value, true);
    // Search filter
    if (filters.value.search) {
        var search_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (emp) {
            return emp.name.toLowerCase().includes(search_1) ||
                emp.employeeId.toLowerCase().includes(search_1);
        });
    }
    // Department filter
    if (filters.value.department && filters.value.department !== 'All Departments') {
        filtered = filtered.filter(function (emp) { return emp.department === filters.value.department; });
    }
    // Balance filter
    if (filters.value.balance) {
        filtered = filtered.filter(function (emp) {
            var total = Object.values(emp.balances).reduce(function (a, b) { return a + b; }, 0);
            switch (filters.value.balance) {
                case 'low': return total < 5;
                case 'medium': return total >= 5 && total <= 15;
                case 'high': return total > 15;
                default: return true;
            }
        });
    }
    return filtered;
});
var paginationStart = (0, vue_1.computed)(function () { return paginationOffset.value + 1; });
var paginationEnd = (0, vue_1.computed)(function () { return Math.min(paginationOffset.value + pageSize.value, filteredBalances.value.length); });
var recentHistory = (0, vue_1.computed)(function () {
    return historyData.value
        .filter(function (h) { var _a; return h.employeeId === ((_a = selectedEmployee.value) === null || _a === void 0 ? void 0 : _a.id); })
        .slice(0, 3);
});
var fullHistory = (0, vue_1.computed)(function () {
    return historyData.value.filter(function (h) { var _a; return h.employeeId === ((_a = selectedEmployee.value) === null || _a === void 0 ? void 0 : _a.id); });
});
// Helper functions
var getInitials = function (name) {
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getBalance = function (employee, type) {
    return employee.balances[type] || 0;
};
var getUsagePercentage = function (employee, type) {
    var used = 20 - getBalance(employee, type);
    return (used / 20) * 100;
};
var getBalanceStatus = function (employee) {
    var total = Object.values(employee.balances).reduce(function (a, b) { return a + b; }, 0);
    if (total < 5)
        return 'Low Balance';
    if (total < 10)
        return 'Moderate';
    return 'Healthy';
};
var getBalanceSeverity = function (employee) {
    var total = Object.values(employee.balances).reduce(function (a, b) { return a + b; }, 0);
    if (total < 5)
        return 'warning';
    if (total < 10)
        return 'info';
    return 'success';
};
// Actions
var resetFilters = function () {
    filters.value = { search: '', department: null, balance: null };
};
var onPageChange = function (event) {
    paginationOffset.value = event.first;
};
var viewDetails = function (employee) {
    selectedEmployee.value = employee;
    showDetailsModal.value = true;
};
var adjustBalance = function (employee) {
    adjustForm.value.employeeId = employee.id;
    showAdjustModal.value = true;
};
var viewHistory = function (employee) {
    selectedEmployee.value = employee;
    showHistoryModal.value = true;
};
var closeAdjustModal = function () {
    showAdjustModal.value = false;
    adjustForm.value = { employeeId: null, leaveType: null, adjustmentType: 'add', days: 0, reason: '' };
};
var applyAdjustment = function () {
    // Validate form
    if (!adjustForm.value.employeeId || !adjustForm.value.leaveType || adjustForm.value.days <= 0) {
        alert('Please fill all required fields');
        return;
    }
    // Find employee
    var employee = employees.value.find(function (e) { return e.id === adjustForm.value.employeeId; });
    if (!employee)
        return;
    // Apply adjustment based on type
    switch (adjustForm.value.adjustmentType) {
        case 'add':
            employee.balances[adjustForm.value.leaveType] += adjustForm.value.days;
            break;
        case 'deduct':
            employee.balances[adjustForm.value.leaveType] = Math.max(0, employee.balances[adjustForm.value.leaveType] - adjustForm.value.days);
            break;
        case 'set':
            employee.balances[adjustForm.value.leaveType] = adjustForm.value.days;
            break;
    }
    // Add to history
    historyData.value.unshift({
        id: historyData.value.length + 1,
        employeeId: employee.id,
        action: 'Balance Adjusted',
        type: adjustForm.value.leaveType,
        days: adjustForm.value.days,
        date: new Date().toISOString().split('T')[0],
        details: "".concat(adjustForm.value.adjustmentType, " ").concat(adjustForm.value.days, " days. Reason: ").concat(adjustForm.value.reason || 'No reason provided'),
        performedBy: 'Current User',
        icon: 'pi pi-sliders-h'
    });
    closeAdjustModal();
};
var exportBalances = function () {
    console.log('Exporting balances...');
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 max-w-7xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "info", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.exportBalances) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Adjust Balance", icon: "pi pi-plus", severity: "info" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Adjust Balance", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showAdjustModal = true;
            // @ts-ignore
            [exportBalances, showAdjustModal,];
        } });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-blue-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.employeeCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-line text-green-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-chart-line']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.averageRemaining);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-yellow-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.lowBalanceCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-purple-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.totalUsed);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-3 items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 flex-wrap" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "relative" }));
/** @type {__VLS_StyleScopedClasses['relative']} */ ;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
/** @type {__VLS_StyleScopedClasses['absolute']} */ ;
/** @type {__VLS_StyleScopedClasses['left-3']} */ ;
/** @type {__VLS_StyleScopedClasses['top-1/2']} */ ;
/** @type {__VLS_StyleScopedClasses['-translate-y-1/2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee" }, { class: "pl-8 rounded-lg w-64" })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee" }, { class: "pl-8 rounded-lg w-64" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
/** @type {__VLS_StyleScopedClasses['pl-8']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
// @ts-ignore
[employeeCount, averageRemaining, lowBalanceCount, totalUsed, filters,];
var __VLS_17;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), showClear: true, placeholder: "All Departments" }, { class: "rounded-lg w-48" })));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), showClear: true, placeholder: "All Departments" }, { class: "rounded-lg w-48" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_30;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign({ modelValue: (__VLS_ctx.filters.balance), options: (__VLS_ctx.balanceFilters), showClear: true, placeholder: "Balance Range" }, { class: "rounded-lg w-48" })));
var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.balance), options: (__VLS_ctx.balanceFilters), showClear: true, placeholder: "Balance Range" }, { class: "rounded-lg w-48" })], __VLS_functionalComponentArgsRest(__VLS_31), false));
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { label: "Reset Filters", icon: "pi pi-filter-slash", severity: "info", outlined: true })));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reset Filters", icon: "pi pi-filter-slash", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_36), false));
var __VLS_40;
var __VLS_41 = ({ click: {} },
    { onClick: (__VLS_ctx.resetFilters) });
var __VLS_38;
var __VLS_39;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "overflow-x-auto" }));
/** @type {__VLS_StyleScopedClasses['overflow-x-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.table, __VLS_intrinsics.table)(__assign({ class: "w-full" }));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.thead, __VLS_intrinsics.thead)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ class: "bg-gray-50/50 border-b border-gray-100" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
for (var _i = 0, _g = __VLS_vFor((__VLS_ctx.leaveTypes)); _i < _g.length; _i++) {
    var type = _g[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }, { key: (type) }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (type);
    // @ts-ignore
    [filters, filters, departments, balanceFilters, resetFilters, leaveTypes,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-4 text-sm font-medium text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-left']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)(__assign({ class: "divide-y divide-gray-100" }));
/** @type {__VLS_StyleScopedClasses['divide-y']} */ ;
/** @type {__VLS_StyleScopedClasses['divide-gray-100']} */ ;
var _loop_1 = function (emp) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ key: (emp.id) }, { class: "hover:bg-gray-50/50 transition-colors" }));
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50/50']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-4" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "large" }, { class: "bg-blue-100 text-blue-600 font-medium" })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "large" }, { class: "bg-blue-100 text-blue-600 font-medium" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (emp.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (emp.employeeId);
    __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-4 text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (emp.department);
    for (var _r = 0, _s = __VLS_vFor((__VLS_ctx.leaveTypes)); _r < _s.length; _r++) {
        var type = _s[_r][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ key: (type) }, { class: "p-4" }));
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.getBalance(emp, type));
        if (__VLS_ctx.getBalance(emp, type) < 5) {
            var __VLS_47 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_48 = __VLS_asFunctionalComponent1(__VLS_47, new __VLS_47({
                value: "Low",
                severity: "warning",
                size: "small",
                rounded: true,
            }));
            var __VLS_49 = __VLS_48.apply(void 0, __spreadArray([{
                    value: "Low",
                    severity: "warning",
                    size: "small",
                    rounded: true,
                }], __VLS_functionalComponentArgsRest(__VLS_48), false));
        }
        var __VLS_52 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.ProgressBar} */
        progressbar_1.default;
        // @ts-ignore
        var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign({ value: (__VLS_ctx.getUsagePercentage(emp, type)), showValue: (false) }, { class: "h-1 w-20 mt-1" })));
        var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.getUsagePercentage(emp, type)), showValue: (false) }, { class: "h-1 w-20 mt-1" })], __VLS_functionalComponentArgsRest(__VLS_53), false));
        /** @type {__VLS_StyleScopedClasses['h-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['w-20']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        // @ts-ignore
        [leaveTypes, filteredBalances, getInitials, getBalance, getBalance, getUsagePercentage,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-4" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (emp.totalUsed);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (Math.round((emp.totalUsed / emp.totalQuota) *
        100));
    __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-4" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    var __VLS_57 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_58 = __VLS_asFunctionalComponent1(__VLS_57, new __VLS_57({
        value: (__VLS_ctx.getBalanceStatus(emp)),
        severity: (__VLS_ctx.getBalanceSeverity(emp)),
        rounded: true,
    }));
    var __VLS_59 = __VLS_58.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.getBalanceStatus(emp)),
            severity: (__VLS_ctx.getBalanceSeverity(emp)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_58), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-4" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_62 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info" })));
    var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_63), false));
    var __VLS_67 = void 0;
    var __VLS_68 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDetails(emp);
                // @ts-ignore
                [getBalanceStatus, getBalanceSeverity, viewDetails,];
            } });
    var __VLS_69 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info" })));
    var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_70), false));
    var __VLS_74 = void 0;
    var __VLS_75 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.adjustBalance(emp);
                // @ts-ignore
                [adjustBalance,];
            } });
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info" })));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_77), false));
    var __VLS_81 = void 0;
    var __VLS_82 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewHistory(emp);
                // @ts-ignore
                [viewHistory,];
            } });
    // @ts-ignore
    [];
};
var __VLS_65, __VLS_66, __VLS_72, __VLS_73, __VLS_79, __VLS_80;
for (var _h = 0, _j = __VLS_vFor((__VLS_ctx.filteredBalances)); _h < _j.length; _h++) {
    var emp = _j[_h][0];
    _loop_1(emp);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-t border-gray-100 flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.paginationStart);
(__VLS_ctx.paginationEnd);
(__VLS_ctx.filteredBalances.length);
var __VLS_83;
/** @ts-ignore @type {typeof __VLS_components.Paginator} */
paginator_1.default;
// @ts-ignore
var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign(__assign({ 'onPage': {} }, { first: (__VLS_ctx.paginationOffset), rows: (__VLS_ctx.pageSize), totalRecords: (__VLS_ctx.filteredBalances.length), template: "PrevPageLink PageLinks NextPageLink" }), { class: "bg-transparent border-0" })));
var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign(__assign({ 'onPage': {} }, { first: (__VLS_ctx.paginationOffset), rows: (__VLS_ctx.pageSize), totalRecords: (__VLS_ctx.filteredBalances.length), template: "PrevPageLink PageLinks NextPageLink" }), { class: "bg-transparent border-0" })], __VLS_functionalComponentArgsRest(__VLS_84), false));
var __VLS_88;
var __VLS_89 = ({ page: {} },
    { onPage: (__VLS_ctx.onPageChange) });
/** @type {__VLS_StyleScopedClasses['bg-transparent']} */ ;
/** @type {__VLS_StyleScopedClasses['border-0']} */ ;
var __VLS_86;
var __VLS_87;
var __VLS_90;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign(__assign({ visible: (__VLS_ctx.showAdjustModal), modal: true }, { style: ({ width: '450px' }) }), { class: "rounded-xl" })));
var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showAdjustModal), modal: true }, { style: ({ width: '450px' }) }), { class: "rounded-xl" })], __VLS_functionalComponentArgsRest(__VLS_91), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_95 = __VLS_93.slots.default;
{
    var __VLS_96 = __VLS_93.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sliders-h text-blue-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-sliders-h']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    // @ts-ignore
    [showAdjustModal, filteredBalances, filteredBalances, paginationStart, paginationEnd, paginationOffset, pageSize, onPageChange,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_97;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign({ modelValue: (__VLS_ctx.adjustForm.employeeId), options: (__VLS_ctx.employees), optionLabel: "name", optionValue: "id", placeholder: "Select employee" }, { class: "w-full" })));
var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.adjustForm.employeeId), options: (__VLS_ctx.employees), optionLabel: "name", optionValue: "id", placeholder: "Select employee" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_98), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_102;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102(__assign({ modelValue: (__VLS_ctx.adjustForm.leaveType), options: (__VLS_ctx.leaveTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select leave type" }, { class: "w-full" })));
var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.adjustForm.leaveType), options: (__VLS_ctx.leaveTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select leave type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_103), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_107;
/** @ts-ignore @type {typeof __VLS_components.RadioButton} */
radiobutton_1.default;
// @ts-ignore
var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107({
    modelValue: (__VLS_ctx.adjustForm.adjustmentType),
    inputId: "add",
    value: "add",
}));
var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.adjustForm.adjustmentType),
        inputId: "add",
        value: "add",
    }], __VLS_functionalComponentArgsRest(__VLS_108), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "add" }, { class: "text-sm" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_112;
/** @ts-ignore @type {typeof __VLS_components.RadioButton} */
radiobutton_1.default;
// @ts-ignore
var __VLS_113 = __VLS_asFunctionalComponent1(__VLS_112, new __VLS_112({
    modelValue: (__VLS_ctx.adjustForm.adjustmentType),
    inputId: "deduct",
    value: "deduct",
}));
var __VLS_114 = __VLS_113.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.adjustForm.adjustmentType),
        inputId: "deduct",
        value: "deduct",
    }], __VLS_functionalComponentArgsRest(__VLS_113), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "deduct" }, { class: "text-sm" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_117;
/** @ts-ignore @type {typeof __VLS_components.RadioButton} */
radiobutton_1.default;
// @ts-ignore
var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117({
    modelValue: (__VLS_ctx.adjustForm.adjustmentType),
    inputId: "set",
    value: "set",
}));
var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.adjustForm.adjustmentType),
        inputId: "set",
        value: "set",
    }], __VLS_functionalComponentArgsRest(__VLS_118), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "set" }, { class: "text-sm" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_122;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122(__assign({ modelValue: (__VLS_ctx.adjustForm.days), min: (0), max: (30) }, { class: "w-full" })));
var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.adjustForm.days), min: (0), max: (30) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_123), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_127;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127(__assign(__assign({ modelValue: (__VLS_ctx.adjustForm.reason), rows: "2" }, { class: "w-full" }), { placeholder: "Reason for adjustment..." })));
var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.adjustForm.reason), rows: "2" }, { class: "w-full" }), { placeholder: "Reason for adjustment..." })], __VLS_functionalComponentArgsRest(__VLS_128), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_132 = __VLS_93.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 justify-end" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    var __VLS_133 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_134), false));
    var __VLS_138 = void 0;
    var __VLS_139 = ({ click: {} },
        { onClick: (__VLS_ctx.closeAdjustModal) });
    var __VLS_136;
    var __VLS_137;
    var __VLS_140 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign({ 'onClick': {} }, { label: "Apply Adjustment", severity: "info" })));
    var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Apply Adjustment", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_141), false));
    var __VLS_145 = void 0;
    var __VLS_146 = ({ click: {} },
        { onClick: (__VLS_ctx.applyAdjustment) });
    var __VLS_143;
    var __VLS_144;
    // @ts-ignore
    [adjustForm, adjustForm, adjustForm, adjustForm, adjustForm, adjustForm, adjustForm, employees, leaveTypeOptions, closeAdjustModal, applyAdjustment,];
}
// @ts-ignore
[];
var __VLS_93;
var __VLS_147;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_148 = __VLS_asFunctionalComponent1(__VLS_147, new __VLS_147(__assign(__assign({ visible: (__VLS_ctx.showDetailsModal), modal: true }, { style: ({ width: '500px' }) }), { class: "rounded-xl" })));
var __VLS_149 = __VLS_148.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showDetailsModal), modal: true }, { style: ({ width: '500px' }) }), { class: "rounded-xl" })], __VLS_functionalComponentArgsRest(__VLS_148), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_152 = __VLS_150.slots.default;
{
    var __VLS_153 = __VLS_150.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_154 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_155 = __VLS_asFunctionalComponent1(__VLS_154, new __VLS_154(__assign({ label: (__VLS_ctx.getInitials(((_a = __VLS_ctx.selectedEmployee) === null || _a === void 0 ? void 0 : _a.name) || '')), size: "large" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_156 = __VLS_155.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(((_b = __VLS_ctx.selectedEmployee) === null || _b === void 0 ? void 0 : _b.name) || '')), size: "large" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_155), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    ((_c = __VLS_ctx.selectedEmployee) === null || _c === void 0 ? void 0 : _c.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    ((_d = __VLS_ctx.selectedEmployee) === null || _d === void 0 ? void 0 : _d.department);
    // @ts-ignore
    [getInitials, showDetailsModal, selectedEmployee, selectedEmployee, selectedEmployee,];
}
if (__VLS_ctx.selectedEmployee) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-5" }));
    /** @type {__VLS_StyleScopedClasses['space-y-5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    for (var _k = 0, _l = __VLS_vFor((__VLS_ctx.leaveTypes)); _k < _l.length; _k++) {
        var type = _l[_k][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (type) }, { class: "bg-gray-50 rounded-lg p-3" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        (type);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xl font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        (__VLS_ctx.getBalance(__VLS_ctx.selectedEmployee, type));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        var __VLS_159 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.ProgressBar} */
        progressbar_1.default;
        // @ts-ignore
        var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ value: (__VLS_ctx.getUsagePercentage(__VLS_ctx.selectedEmployee, type)), showValue: (false) }, { class: "h-1.5" })));
        var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.getUsagePercentage(__VLS_ctx.selectedEmployee, type)), showValue: (false) }, { class: "h-1.5" })], __VLS_functionalComponentArgsRest(__VLS_160), false));
        /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
        // @ts-ignore
        [leaveTypes, getBalance, getUsagePercentage, selectedEmployee, selectedEmployee, selectedEmployee,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-medium text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _m = 0, _o = __VLS_vFor((__VLS_ctx.recentHistory)); _m < _o.length; _m++) {
        var history_1 = _o[_m][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (history_1.id) }, { class: "flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (history_1.icon) }, { class: "text-blue-500 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (history_1.action);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (history_1.date);
        var __VLS_164 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164({
            value: (history_1.days),
            severity: "info",
            size: "small",
        }));
        var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([{
                value: (history_1.days),
                severity: "info",
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_165), false));
        // @ts-ignore
        [recentHistory,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.totalQuota);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.totalUsed);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.selectedEmployee.totalQuota -
        __VLS_ctx.selectedEmployee.totalUsed);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.carryOver || 0);
}
{
    var __VLS_169 = __VLS_150.slots.footer;
    var __VLS_170 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_171), false));
    var __VLS_175 = void 0;
    var __VLS_176 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsModal = false;
                // @ts-ignore
                [showDetailsModal, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee,];
            } });
    var __VLS_173;
    var __VLS_174;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_150;
var __VLS_177;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177(__assign(__assign(__assign({ visible: (__VLS_ctx.showHistoryModal), modal: true }, { style: ({ width: '500px' }) }), { class: "rounded-xl" }), { header: ("Leave History - ".concat((_e = __VLS_ctx.selectedEmployee) === null || _e === void 0 ? void 0 : _e.name)) })));
var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showHistoryModal), modal: true }, { style: ({ width: '500px' }) }), { class: "rounded-xl" }), { header: ("Leave History - ".concat((_f = __VLS_ctx.selectedEmployee) === null || _f === void 0 ? void 0 : _f.name)) })], __VLS_functionalComponentArgsRest(__VLS_178), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_182 = __VLS_180.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3 max-h-96 overflow-y-auto" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
/** @type {__VLS_StyleScopedClasses['max-h-96']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
for (var _p = 0, _q = __VLS_vFor((__VLS_ctx.fullHistory)); _p < _q.length; _p++) {
    var item = _q[_p][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (item.id) }, { class: "border border-gray-100 rounded-lg p-3 hover:bg-gray-50" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start mb-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (item.action);
    var __VLS_183 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_184 = __VLS_asFunctionalComponent1(__VLS_183, new __VLS_183(__assign({ value: (item.type), severity: "info", size: "small" }, { class: "ml-2" })));
    var __VLS_185 = __VLS_184.apply(void 0, __spreadArray([__assign({ value: (item.type), severity: "info", size: "small" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_184), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (item.date);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (item.details);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (item.performedBy);
    // @ts-ignore
    [selectedEmployee, showHistoryModal, fullHistory,];
}
// @ts-ignore
[];
var __VLS_180;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
