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
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var auth_1 = require("../../../stores/auth");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var button_1 = require("primevue/button");
var tag_1 = require("primevue/tag");
var inputtext_1 = require("primevue/inputtext");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var select_1 = require("primevue/select");
var avatar_1 = require("primevue/avatar");
var progressbar_1 = require("primevue/progressbar");
var dialog_1 = require("primevue/dialog");
var badge_1 = require("primevue/badge");
var metergroup_1 = require("primevue/metergroup");
var chart_1 = require("primevue/chart");
var router = (0, vue_router_1.useRouter)();
// State
var showAddEmployeeDialog = (0, vue_1.ref)(false);
var employeeSearch = (0, vue_1.ref)('');
var employeeFilter = (0, vue_1.ref)(null);
var authStore = (0, auth_1.useAuthStore)();
var firstname = (0, vue_1.ref)(authStore.user.first_name);
var hrStats = (0, vue_1.ref)({
    totalEmployees: 150,
    activeEmployees: 142,
    onTime: 120,
    dayOff: 15,
    sickLeave: 7
});
var currentDate = (0, vue_1.computed)(function () {
    return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});
// Compact leave balances
var leaveBalancesCompact = (0, vue_1.computed)(function () { return [
    { name: 'Vacation', used: leaveBalance.value.vacation.used, total: leaveBalance.value.vacation.total, icon: 'pi pi-sun', color: '#3b82f6' },
    { name: 'Sick', used: leaveBalance.value.sick.used, total: leaveBalance.value.sick.total, icon: 'pi pi-heart', color: '#ef4444' },
    { name: 'Emergency', used: leaveBalance.value.emergency.used, total: leaveBalance.value.emergency.total, icon: 'pi pi-exclamation-triangle', color: '#f59e0b' }
]; });
// Format numbers in abbreviated form (e.g., 45K instead of 45,000)
var formatAbbr = function (num) {
    if (num >= 1000000)
        return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000)
        return (num / 1000).toFixed(1) + 'K';
    return num.toString();
};
var meterValues = (0, vue_1.computed)(function () {
    var total = hrStats.value.onTime + hrStats.value.dayOff + hrStats.value.sickLeave;
    return [
        { label: 'On Time', value: Math.round((hrStats.value.onTime / total) * 100), color: '#462FA1' },
        { label: 'Day Off', value: Math.round((hrStats.value.dayOff / total) * 100), color: '#eab308' },
        { label: 'Sick Leave', value: Math.round((hrStats.value.sickLeave / total) * 100), color: '#ef4444' }
    ];
});
// Attendance Stats
var attendanceStats = (0, vue_1.ref)({
    present: 42,
    late: 3,
    absent: 3,
    presentPercentage: 87.5,
    latePercentage: 6.25,
    absentPercentage: 6.25
});
// Employee Data
var employees = (0, vue_1.ref)([
    {
        id: 1,
        name: 'John Smith',
        employeeId: 'EMP-001',
        position: 'Store Manager',
        department: 'Operations',
        status: 'Active',
        hireDate: '2022-03-15',
        email: 'john.smith@company.com',
        phone: '+639123456789'
    },
    {
        id: 2,
        name: 'Sarah Johnson',
        employeeId: 'EMP-002',
        position: 'Sales Supervisor',
        department: 'Sales',
        status: 'Active',
        hireDate: '2022-06-20',
        email: 'sarah.j@company.com',
        phone: '+639234567890'
    },
    {
        id: 3,
        name: 'Michael Chen',
        employeeId: 'EMP-003',
        position: 'Inventory Specialist',
        department: 'Warehouse',
        status: 'Active',
        hireDate: '2023-01-10',
        email: 'michael.c@company.com',
        phone: '+639345678901'
    },
    {
        id: 4,
        name: 'Lisa Rodriguez',
        employeeId: 'EMP-004',
        position: 'HR Manager',
        department: 'Human Resources',
        status: 'Active',
        hireDate: '2021-08-05',
        email: 'lisa.r@company.com',
        phone: '+639456789012'
    },
    {
        id: 5,
        name: 'David Wilson',
        employeeId: 'EMP-005',
        position: 'Accountant',
        department: 'Finance',
        status: 'On Leave',
        hireDate: '2022-11-30',
        email: 'david.w@company.com',
        phone: '+639567890123'
    },
    {
        id: 6,
        name: 'Emma Garcia',
        employeeId: 'EMP-006',
        position: 'Marketing Officer',
        department: 'Marketing',
        status: 'Active',
        hireDate: '2023-02-14',
        email: 'emma.g@company.com',
        phone: '+639678901234'
    },
    {
        id: 7,
        name: 'Robert Kim',
        employeeId: 'EMP-007',
        position: 'IT Support',
        department: 'IT',
        status: 'Active',
        hireDate: '2022-09-25',
        email: 'robert.k@company.com',
        phone: '+639789012345'
    },
    {
        id: 8,
        name: 'Maria Santos',
        employeeId: 'EMP-008',
        position: 'Purchasing Officer',
        department: 'Procurement',
        status: 'Active',
        hireDate: '2021-12-01',
        email: 'maria.s@company.com',
        phone: '+639890123456'
    }
]);
// New Employee
var newEmployee = (0, vue_1.ref)({
    firstName: '',
    lastName: '',
    position: null,
    department: null,
    email: '',
    phone: '',
    hireDate: null,
    salary: 0,
    address: ''
});
// Filter Options
var employeeFilterOptions = (0, vue_1.ref)([
    { name: 'All Employees', value: 'all' },
    { name: 'Active Only', value: 'active' },
    { name: 'On Leave', value: 'leave' },
    { name: 'By Department', value: 'department' }
]);
var positionOptions = (0, vue_1.ref)([
    { name: 'Store Manager', value: 'store-manager' },
    { name: 'Sales Supervisor', value: 'sales-supervisor' },
    { name: 'Sales Associate', value: 'sales-associate' },
    { name: 'Inventory Specialist', value: 'inventory-specialist' },
    { name: 'Warehouse Staff', value: 'warehouse-staff' },
    { name: 'HR Manager', value: 'hr-manager' },
    { name: 'Accountant', value: 'accountant' },
    { name: 'Marketing Officer', value: 'marketing-officer' },
    { name: 'IT Support', value: 'it-support' },
    { name: 'Purchasing Officer', value: 'purchasing-officer' }
]);
var departmentOptions = (0, vue_1.ref)([
    { name: 'Operations', value: 'operations' },
    { name: 'Sales', value: 'sales' },
    { name: 'Warehouse', value: 'warehouse' },
    { name: 'Human Resources', value: 'hr' },
    { name: 'Finance', value: 'finance' },
    { name: 'Marketing', value: 'marketing' },
    { name: 'IT', value: 'it' },
    { name: 'Procurement', value: 'procurement' }
]);
// Upcoming Holidays
var upcomingHolidays = (0, vue_1.ref)([
    {
        id: 1,
        name: 'New Year\'s Day',
        date: '2024-01-01',
        type: 'Regular Holiday'
    },
    {
        id: 2,
        name: 'Chinese New Year',
        date: '2024-02-10',
        type: 'Special Holiday'
    },
    {
        id: 3,
        name: 'Good Friday',
        date: '2024-03-29',
        type: 'Regular Holiday'
    },
    {
        id: 4,
        name: 'Independence Day',
        date: '2024-06-12',
        type: 'Regular Holiday'
    }
]);
// Upcoming Birthdays
var upcomingBirthdays = (0, vue_1.ref)([
    {
        id: 1,
        name: 'John Smith',
        position: 'Store Manager',
        birthday: '1990-01-20',
        daysUntil: 3
    },
    {
        id: 2,
        name: 'Sarah Johnson',
        position: 'Sales Supervisor',
        birthday: '1988-01-22',
        daysUntil: 5
    },
    {
        id: 3,
        name: 'Michael Chen',
        position: 'Inventory Specialist',
        birthday: '1992-01-25',
        daysUntil: 8
    },
    {
        id: 4,
        name: 'Lisa Rodriguez',
        position: 'HR Manager',
        birthday: '1985-01-28',
        daysUntil: 11
    }
]);
// Leave Balance
var leaveBalance = (0, vue_1.ref)({
    vacation: { used: 8, total: 15 },
    sick: { used: 3, total: 10 },
    emergency: { used: 1, total: 5 }
});
// Recent Activities
var recentActivities = (0, vue_1.ref)([
    {
        id: 1,
        description: 'New employee onboarding completed',
        employee: 'John Smith',
        time: 'Today, 10:30 AM',
        status: 'Completed',
        icon: 'pi pi-user-plus',
        iconColor: 'text-green-600',
        iconBg: 'bg-green-100'
    },
    {
        id: 2,
        description: 'Leave request submitted',
        employee: 'Sarah Johnson',
        time: 'Today, 9:45 AM',
        status: 'Pending',
        icon: 'pi pi-calendar',
        iconColor: 'text-yellow-600',
        iconBg: 'bg-yellow-100'
    },
    {
        id: 3,
        description: 'Performance review scheduled',
        employee: 'Michael Chen',
        time: 'Yesterday, 3:15 PM',
        status: 'Scheduled',
        icon: 'pi pi-chart-bar',
        iconColor: 'text-blue-600',
        iconBg: 'bg-blue-100'
    },
    {
        id: 4,
        description: 'Salary adjustment approved',
        employee: 'David Wilson',
        time: 'Jan 12, 2:30 PM',
        status: 'Approved',
        icon: 'pi pi-money-bill',
        iconColor: 'text-green-600',
        iconBg: 'bg-green-100'
    }
]);
// Pending Requests
var pendingRequests = (0, vue_1.ref)([
    {
        id: 1,
        employee: 'Robert Kim',
        type: 'Leave Request',
        date: 'Jan 15-17, 2024',
        days: 3
    },
    {
        id: 2,
        employee: 'Emma Garcia',
        type: 'Overtime Request',
        date: 'Jan 14, 2024',
        hours: 4
    },
    {
        id: 3,
        employee: 'Maria Santos',
        type: 'Salary Advance',
        date: 'Jan 13, 2024',
        amount: 10000
    },
    {
        id: 4,
        employee: 'John Smith',
        type: 'Training Request',
        date: 'Jan 20, 2024',
        course: 'Leadership'
    }
]);
// Computed Properties
var filteredEmployees = (0, vue_1.computed)(function () {
    var filtered = employees.value;
    // Search filter
    if (employeeSearch.value) {
        var term_1 = employeeSearch.value.toLowerCase();
        filtered = filtered.filter(function (emp) {
            return emp.name.toLowerCase().includes(term_1) ||
                emp.position.toLowerCase().includes(term_1) ||
                emp.department.toLowerCase().includes(term_1) ||
                emp.employeeId.toLowerCase().includes(term_1);
        });
    }
    // Status filter
    if (employeeFilter.value === 'active') {
        filtered = filtered.filter(function (emp) { return emp.status === 'Active'; });
    }
    else if (employeeFilter.value === 'leave') {
        filtered = filtered.filter(function (emp) { return emp.status === 'On Leave'; });
    }
    return filtered;
});
var today = (0, vue_1.computed)(function () {
    return new Date().toISOString().split('T')[0];
});
// Helper Functions
var formatCurrency = function (amount) {
    return amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
var formatDate = function (dateString) {
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    catch (e) {
        return dateString;
    }
};
var formatBirthdayDate = function (dateString) {
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    }
    catch (e) {
        return dateString;
    }
};
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getEmployeeStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'active': return 'success';
        case 'on leave': return 'warning';
        case 'inactive': return 'danger';
        case 'probation': return 'info';
        default: return 'secondary';
    }
};
var getActivityStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'scheduled': return 'info';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
};
// Action Functions
var viewEmployee = function (employee) {
    router.push("/hr/employees/".concat(employee.id));
};
var editEmployee = function (employee) {
    console.log('Edit employee:', employee);
    // Navigate to edit page
};
var markAttendance = function () {
    console.log('Mark attendance');
    router.push('/hr/attendance');
};
var processPayroll = function () {
    console.log('Process payroll');
    router.push('/hr/payroll');
};
var approveLeaveRequests = function () {
    console.log('Approve leave requests');
    router.push('/hr/leave-requests');
};
var viewAllActivities = function () {
    router.push('/hr/activities');
};
var approveRequest = function (request) {
    console.log('Approve request:', request);
    // Implement approval logic
};
var rejectRequest = function (request) {
    console.log('Reject request:', request);
    // Implement rejection logic
};
var addEmployee = function () {
    var _a, _b;
    var newId = Math.max.apply(Math, employees.value.map(function (e) { return e.id; })) + 1;
    var employee = {
        id: newId,
        name: "".concat(newEmployee.value.firstName, " ").concat(newEmployee.value.lastName),
        employeeId: "EMP-".concat(String(newId).padStart(3, '0')),
        position: ((_a = newEmployee.value.position) === null || _a === void 0 ? void 0 : _a.name) || '',
        department: ((_b = newEmployee.value.department) === null || _b === void 0 ? void 0 : _b.name) || '',
        status: 'Active',
        hireDate: newEmployee.value.hireDate || new Date().toISOString().split('T')[0],
        email: newEmployee.value.email,
        phone: newEmployee.value.phone
    };
    employees.value.unshift(employee);
    showAddEmployeeDialog.value = false;
    resetNewEmployee();
};
var resetNewEmployee = function () {
    newEmployee.value = {
        firstName: '',
        lastName: '',
        position: null,
        department: null,
        email: '',
        phone: '',
        hireDate: null,
        salary: 0,
        address: ''
    };
};
var generateReports = function () {
    console.log('Generate HR reports');
    router.push('/hr/reports');
};
// Monthly income data
var income2024 = (0, vue_1.ref)([45000, 52000, 48000, 61000, 58000, 72000, 68000, 75000, 71000, 80000, 78000, 85000]);
var income2025 = (0, vue_1.ref)([48000, 55000, 52000, 65000, 62000, 76000, 72000, 79000, 75000, 84000, 82000, 90000]);
var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
// Calculate totals
var total2024 = (0, vue_1.computed)(function () { return income2024.value.reduce(function (a, b) { return a + b; }, 0); });
var total2025 = (0, vue_1.computed)(function () { return income2025.value.reduce(function (a, b) { return a + b; }, 0); });
var growthPercentage = (0, vue_1.computed)(function () {
    return (((total2025.value - total2024.value) / total2024.value) * 100).toFixed(1);
});
// Format numbers
var formatNumber = function (num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};
// Chart configuration
var chartData = (0, vue_1.ref)({
    labels: months,
    datasets: [
        {
            label: '2024',
            data: income2024.value,
            fill: false,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            borderWidth: 2
        },
        {
            label: '2025',
            data: income2025.value,
            fill: false,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            borderWidth: 2
        }
    ]
});
var chartOptions = (0, vue_1.ref)({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    return "".concat(context.dataset.label, ": $").concat(formatNumber(context.raw));
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: false,
            ticks: {
                callback: function (value) {
                    return '$' + formatNumber(value);
                }
            },
            grid: {
                color: 'rgba(0, 0, 0, 0.05)'
            }
        },
        x: {
            grid: {
                color: 'rgba(0, 0, 0, 0.05)'
            }
        }
    }
});
(0, vue_1.onMounted)(function () {
    console.log('HR Index page loaded');
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-2xl font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
(__VLS_ctx.firstname);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.currentDate);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 w-full sm:w-auto" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-auto']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onClick': {} }, { label: "Reports", icon: "pi pi-download", severity: "info", size: "small" }), { class: "flex-1 sm:flex-none" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Reports", icon: "pi pi-download", severity: "info", size: "small" }), { class: "flex-1 sm:flex-none" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.generateReports) });
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-none']} */ ;
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-4 gap-3" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-medium text-gray-500 uppercase tracking-wider" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
/** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-blue-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.hrStats.totalEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-green-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.hrStats.activeEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-medium text-gray-500 uppercase tracking-wider" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
/** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-check text-green-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-user-check']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.attendanceStats.present);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.attendanceStats.presentPercentage);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-medium text-gray-500 uppercase tracking-wider" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
/** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar-times text-yellow-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-calendar-times']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.hrStats.dayOff);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.hrStats.sickLeave);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-medium text-gray-500 uppercase tracking-wider" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
/** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-orange-500 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.pendingRequests.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Tag} */
tag_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
    value: ("".concat(Math.round((__VLS_ctx.hrStats.onTime / __VLS_ctx.hrStats.totalEmployees) * 100), "%")),
    severity: "success",
    size: "small",
}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{
        value: ("".concat(Math.round((__VLS_ctx.hrStats.onTime / __VLS_ctx.hrStats.totalEmployees) * 100), "%")),
        severity: "success",
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.MeterGroup} */
metergroup_1.default;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12({
    value: (__VLS_ctx.meterValues),
    max: (100),
}));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.meterValues),
        max: (100),
    }], __VLS_functionalComponentArgsRest(__VLS_13), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-2 mt-3 text-center text-xs" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
(__VLS_ctx.hrStats.onTime);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
(__VLS_ctx.hrStats.dayOff);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
(__VLS_ctx.hrStats.sickLeave);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold mb-3" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.leaveBalancesCompact)); _i < _a.length; _i++) {
    var leave = _a[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (leave.name) }, { class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign(__assign({ class: (leave.icon) }, { style: ({ color: leave.color }) }), { class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between text-xs mb-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (leave.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (leave.used);
    (leave.total);
    var __VLS_17 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.ProgressBar} */
    progressbar_1.default;
    // @ts-ignore
    var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ value: ((leave.used / leave.total) * 100), showValue: (false) }, { class: "h-1.5" })));
    var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ value: ((leave.used / leave.total) * 100), showValue: (false) }, { class: "h-1.5" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
    /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
    // @ts-ignore
    [firstname, currentDate, generateReports, hrStats, hrStats, hrStats, hrStats, hrStats, hrStats, hrStats, hrStats, hrStats, attendanceStats, attendanceStats, pendingRequests, meterValues, leaveBalancesCompact,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow lg:col-span-2" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-2 h-2 rounded-full bg-blue-500" }));
/** @type {__VLS_StyleScopedClasses['w-2']} */ ;
/** @type {__VLS_StyleScopedClasses['h-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-2 h-2 rounded-full bg-emerald-500" }));
/** @type {__VLS_StyleScopedClasses['w-2']} */ ;
/** @type {__VLS_StyleScopedClasses['h-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-emerald-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_22;
/** @ts-ignore @type {typeof __VLS_components.Chart} */
chart_1.default;
// @ts-ignore
var __VLS_23 = __VLS_asFunctionalComponent1(__VLS_22, new __VLS_22(__assign({ type: "line", data: (__VLS_ctx.chartData), options: (__VLS_ctx.chartOptions) }, { class: "h-65" })));
var __VLS_24 = __VLS_23.apply(void 0, __spreadArray([__assign({ type: "line", data: (__VLS_ctx.chartData), options: (__VLS_ctx.chartOptions) }, { class: "h-65" })], __VLS_functionalComponentArgsRest(__VLS_23), false));
/** @type {__VLS_StyleScopedClasses['h-65']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-2 mt-3 text-center" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 p-2 rounded" }));
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.formatAbbr(__VLS_ctx.total2024));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-emerald-50 p-2 rounded" }));
/** @type {__VLS_StyleScopedClasses['bg-emerald-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.formatAbbr(__VLS_ctx.total2025));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-bold text-emerald-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-emerald-600']} */ ;
(__VLS_ctx.growthPercentage);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mb-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
var __VLS_27;
/** @ts-ignore @type {typeof __VLS_components.Badge} */
badge_1.default;
// @ts-ignore
var __VLS_28 = __VLS_asFunctionalComponent1(__VLS_27, new __VLS_27({
    value: (__VLS_ctx.pendingRequests.length),
    severity: "warning",
    size: "small",
}));
var __VLS_29 = __VLS_28.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.pendingRequests.length),
        severity: "warning",
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_28), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
var _loop_1 = function (request) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (request.id) }, { class: "flex items-center justify-between p-2 bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ label: (__VLS_ctx.getInitials(request.employee)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-xs" })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(request.employee)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (request.employee);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (request.type);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_37 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", rounded: true, text: true, severity: "success" })));
    var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", rounded: true, text: true, severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_38), false));
    var __VLS_42 = void 0;
    var __VLS_43 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.approveRequest(request);
                // @ts-ignore
                [pendingRequests, pendingRequests, chartData, chartOptions, formatAbbr, formatAbbr, total2024, total2025, growthPercentage, getInitials, approveRequest,];
            } });
    var __VLS_44 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44(__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", rounded: true, text: true, severity: "danger" })));
    var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", rounded: true, text: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_45), false));
    var __VLS_49 = void 0;
    var __VLS_50 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.rejectRequest(request);
                // @ts-ignore
                [rejectRequest,];
            } });
    // @ts-ignore
    [];
};
var __VLS_40, __VLS_41, __VLS_47, __VLS_48;
for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.pendingRequests.slice(0, 3))); _b < _c.length; _b++) {
    var request = _c[_b][0];
    _loop_1(request);
}
var __VLS_51;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign(__assign({ 'onClick': {} }, { label: "View all", link: true, size: "small" }), { class: "w-full text-xs" })));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "View all", link: true, size: "small" }), { class: "w-full text-xs" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
var __VLS_56;
var __VLS_57 = ({ click: {} },
    { onClick: (__VLS_ctx.viewAllActivities) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_54;
var __VLS_55;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold mb-3" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
for (var _d = 0, _e = __VLS_vFor((__VLS_ctx.recentActivities.slice(0, 3))); _d < _e.length; _d++) {
    var activity = _e[_d][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (activity.id) }, { class: "flex items-start gap-2 p-2 hover:bg-gray-50 rounded" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-6 h-6 rounded-full flex items-center justify-center ".concat(activity.iconBg)) }));
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("".concat(activity.icon, " text-xs ").concat(activity.iconColor)) }));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 min-w-0" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-medium truncate" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
    (activity.description);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (activity.time);
    var __VLS_58 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ value: (activity.status), severity: (__VLS_ctx.getActivityStatusSeverity(activity.status)), size: "small" }, { class: "text-xs" })));
    var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ value: (activity.status), severity: (__VLS_ctx.getActivityStatusSeverity(activity.status)), size: "small" }, { class: "text-xs" })], __VLS_functionalComponentArgsRest(__VLS_59), false));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    // @ts-ignore
    [viewAllActivities, recentActivities, getActivityStatusSeverity,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white p-4 rounded-xl box-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold mb-3" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
for (var _f = 0, _g = __VLS_vFor((__VLS_ctx.upcomingBirthdays.slice(0, 3))); _f < _g.length; _f++) {
    var emp = _g[_f][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (emp.id) }, { class: "flex items-center gap-2 p-2 hover:bg-gray-50 rounded" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    var __VLS_63 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "small", shape: "circle" }, { class: "bg-pink-100 text-pink-800 text-xs" })));
    var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "small", shape: "circle" }, { class: "bg-pink-100 text-pink-800 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_64), false));
    /** @type {__VLS_StyleScopedClasses['bg-pink-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-pink-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (emp.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatBirthdayDate(emp.birthday));
    var __VLS_68 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68(__assign({ value: ("".concat(emp.daysUntil, "d")), severity: "info", size: "small" }, { class: "text-xs" })));
    var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([__assign({ value: ("".concat(emp.daysUntil, "d")), severity: "info", size: "small" }, { class: "text-xs" })], __VLS_functionalComponentArgsRest(__VLS_69), false));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    // @ts-ignore
    [getInitials, upcomingBirthdays, formatBirthdayDate,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl box-shadow overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['box-shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row gap-2 justify-between items-start sm:items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 w-full sm:w-auto" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-auto']} */ ;
var __VLS_73;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
iconfield_1.default;
// @ts-ignore
var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73(__assign({ iconPosition: "left" }, { class: "w-full sm:w-auto" })));
var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([__assign({ iconPosition: "left" }, { class: "w-full sm:w-auto" })], __VLS_functionalComponentArgsRest(__VLS_74), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-auto']} */ ;
var __VLS_78 = __VLS_76.slots.default;
var __VLS_79;
/** @ts-ignore @type {typeof __VLS_components.InputIcon} */
inputicon_1.default;
// @ts-ignore
var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ class: "pi pi-search text-xs" })));
var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ class: "pi pi-search text-xs" })], __VLS_functionalComponentArgsRest(__VLS_80), false));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_84;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign({ modelValue: (__VLS_ctx.employeeSearch), placeholder: "Search..." }, { class: "w-full sm:w-48 h-8 text-sm" })));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeSearch), placeholder: "Search..." }, { class: "w-full sm:w-48 h-8 text-sm" })], __VLS_functionalComponentArgsRest(__VLS_85), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-48']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
// @ts-ignore
[employeeSearch,];
var __VLS_76;
var __VLS_89;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89(__assign({ modelValue: (__VLS_ctx.employeeFilter), options: (__VLS_ctx.employeeFilterOptions), optionLabel: "name", placeholder: "Filter" }, { class: "w-24 h-8 text-sm" })));
var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeFilter), options: (__VLS_ctx.employeeFilterOptions), optionLabel: "name", placeholder: "Filter" }, { class: "w-24 h-8 text-sm" })], __VLS_functionalComponentArgsRest(__VLS_90), false));
/** @type {__VLS_StyleScopedClasses['w-24']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "block sm:hidden p-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
for (var _h = 0, _j = __VLS_vFor((__VLS_ctx.filteredEmployees.slice(0, 3))); _h < _j.length; _h++) {
    var emp = _j[_h][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (emp.id) }, { class: "border-b last:border-0 py-2" }));
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['last:border-0']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_94 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal", shape: "circle" }, { class: "bg-blue-100 text-blue-800" })));
    var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal", shape: "circle" }, { class: "bg-blue-100 text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_95), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (emp.name);
    var __VLS_99 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ value: (emp.status), severity: (__VLS_ctx.getEmployeeStatusSeverity(emp.status)), size: "small" }, { class: "text-xs" })));
    var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ value: (emp.status), severity: (__VLS_ctx.getEmployeeStatusSeverity(emp.status)), size: "small" }, { class: "text-xs" })], __VLS_functionalComponentArgsRest(__VLS_100), false));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (emp.position);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (emp.employeeId);
    // @ts-ignore
    [getInitials, employeeFilter, employeeFilterOptions, filteredEmployees, getEmployeeStatusSeverity,];
}
var __VLS_104;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104(__assign(__assign({ 'onClick': {} }, { label: "View all employees", link: true, size: "small" }), { class: "w-full mt-2 text-xs" })));
var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "View all employees", link: true, size: "small" }), { class: "w-full mt-2 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_105), false));
var __VLS_109;
var __VLS_110 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push('/hr/employees');
            // @ts-ignore
            [router,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_107;
var __VLS_108;
var __VLS_111;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
datatable_1.default;
// @ts-ignore
var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111(__assign({ value: (__VLS_ctx.filteredEmployees), dataKey: "id", rows: (3), paginator: (false) }, { class: "hidden sm:block text-sm" })));
var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.filteredEmployees), dataKey: "id", rows: (3), paginator: (false) }, { class: "hidden sm:block text-sm" })], __VLS_functionalComponentArgsRest(__VLS_112), false));
/** @type {__VLS_StyleScopedClasses['hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_116 = __VLS_114.slots.default;
var __VLS_117;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ field: "id", header: "ID", sortable: true }, { style: {} })));
var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ field: "id", header: "ID", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_118), false));
var __VLS_122 = __VLS_120.slots.default;
{
    var __VLS_123 = __VLS_120.slots.body;
    var slotProps = __VLS_vSlot(__VLS_123)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (slotProps.data.id);
    // @ts-ignore
    [filteredEmployees,];
}
// @ts-ignore
[];
var __VLS_120;
var __VLS_124;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ field: "name", header: "Employee", sortable: true }, { style: {} })));
var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ field: "name", header: "Employee", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_125), false));
var __VLS_129 = __VLS_127.slots.default;
{
    var __VLS_130 = __VLS_127.slots.body;
    var slotProps = __VLS_vSlot(__VLS_130)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_131 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ label: (__VLS_ctx.getInitials(slotProps.data.name)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-xs" })));
    var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(slotProps.data.name)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-xs" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (slotProps.data.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (slotProps.data.position);
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_127;
var __VLS_136;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136(__assign({ field: "department", header: "Department", sortable: true }, { style: {} })));
var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([__assign({ field: "department", header: "Department", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_137), false));
var __VLS_141 = __VLS_139.slots.default;
{
    var __VLS_142 = __VLS_139.slots.body;
    var slotProps = __VLS_vSlot(__VLS_142)[0];
    var __VLS_143 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign({ value: (slotProps.data.department), severity: "info", size: "small" }, { class: "text-xs" })));
    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign({ value: (slotProps.data.department), severity: "info", size: "small" }, { class: "text-xs" })], __VLS_functionalComponentArgsRest(__VLS_144), false));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_139;
var __VLS_148;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_149), false));
var __VLS_153 = __VLS_151.slots.default;
{
    var __VLS_154 = __VLS_151.slots.body;
    var slotProps = __VLS_vSlot(__VLS_154)[0];
    var __VLS_155 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_156 = __VLS_asFunctionalComponent1(__VLS_155, new __VLS_155(__assign({ value: (slotProps.data.status), severity: (__VLS_ctx.getEmployeeStatusSeverity(slotProps.data.status)), size: "small" }, { class: "text-xs" })));
    var __VLS_157 = __VLS_156.apply(void 0, __spreadArray([__assign({ value: (slotProps.data.status), severity: (__VLS_ctx.getEmployeeStatusSeverity(slotProps.data.status)), size: "small" }, { class: "text-xs" })], __VLS_functionalComponentArgsRest(__VLS_156), false));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    // @ts-ignore
    [getEmployeeStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_151;
var __VLS_160;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ header: "Actions" }, { style: {} })));
var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_161), false));
var __VLS_165 = __VLS_163.slots.default;
{
    var __VLS_166 = __VLS_163.slots.body;
    var slotProps_1 = __VLS_vSlot(__VLS_166)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_167 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "info" })));
    var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_168), false));
    var __VLS_172 = void 0;
    var __VLS_173 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewEmployee(slotProps_1.data);
                // @ts-ignore
                [viewEmployee,];
            } });
    var __VLS_170;
    var __VLS_171;
    var __VLS_174 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_175 = __VLS_asFunctionalComponent1(__VLS_174, new __VLS_174(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })));
    var __VLS_176 = __VLS_175.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_175), false));
    var __VLS_179 = void 0;
    var __VLS_180 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editEmployee(slotProps_1.data);
                // @ts-ignore
                [editEmployee,];
            } });
    var __VLS_177;
    var __VLS_178;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_163;
// @ts-ignore
[];
var __VLS_114;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-2 border-t text-center sm:hidden" }));
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.filteredEmployees.length);
var __VLS_181;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_182 = __VLS_asFunctionalComponent1(__VLS_181, new __VLS_181(__assign(__assign(__assign({ visible: (__VLS_ctx.showAddEmployeeDialog), header: "Add Employee" }, { style: ({ width: '90%', maxWidth: '500px' }) }), { modal: (true) }), { class: "p-fluid" })));
var __VLS_183 = __VLS_182.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showAddEmployeeDialog), header: "Add Employee" }, { style: ({ width: '90%', maxWidth: '500px' }) }), { modal: (true) }), { class: "p-fluid" })], __VLS_functionalComponentArgsRest(__VLS_182), false));
/** @type {__VLS_StyleScopedClasses['p-fluid']} */ ;
var __VLS_186 = __VLS_184.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-2" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-xs font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_187;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_188 = __VLS_asFunctionalComponent1(__VLS_187, new __VLS_187(__assign({ modelValue: (__VLS_ctx.newEmployee.firstName), placeholder: "First name" }, { class: "text-sm" })));
var __VLS_189 = __VLS_188.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newEmployee.firstName), placeholder: "First name" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_188), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-xs font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_192;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_193 = __VLS_asFunctionalComponent1(__VLS_192, new __VLS_192(__assign({ modelValue: (__VLS_ctx.newEmployee.lastName), placeholder: "Last name" }, { class: "text-sm" })));
var __VLS_194 = __VLS_193.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newEmployee.lastName), placeholder: "Last name" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_193), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-xs font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_197;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_198 = __VLS_asFunctionalComponent1(__VLS_197, new __VLS_197(__assign({ modelValue: (__VLS_ctx.newEmployee.email), placeholder: "Email" }, { class: "text-sm" })));
var __VLS_199 = __VLS_198.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newEmployee.email), placeholder: "Email" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_198), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-2" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-xs font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_202;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_203 = __VLS_asFunctionalComponent1(__VLS_202, new __VLS_202(__assign({ modelValue: (__VLS_ctx.newEmployee.position), options: (__VLS_ctx.positionOptions), optionLabel: "name", placeholder: "Select" }, { class: "text-sm" })));
var __VLS_204 = __VLS_203.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newEmployee.position), options: (__VLS_ctx.positionOptions), optionLabel: "name", placeholder: "Select" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_203), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-xs font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_207;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_208 = __VLS_asFunctionalComponent1(__VLS_207, new __VLS_207(__assign({ modelValue: (__VLS_ctx.newEmployee.department), options: (__VLS_ctx.departmentOptions), optionLabel: "name", placeholder: "Select" }, { class: "text-sm" })));
var __VLS_209 = __VLS_208.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newEmployee.department), options: (__VLS_ctx.departmentOptions), optionLabel: "name", placeholder: "Select" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_208), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
{
    var __VLS_212 = __VLS_184.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 justify-end" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    var __VLS_213 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_214 = __VLS_asFunctionalComponent1(__VLS_213, new __VLS_213(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", size: "small" })));
    var __VLS_215 = __VLS_214.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_214), false));
    var __VLS_218 = void 0;
    var __VLS_219 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showAddEmployeeDialog = false;
                // @ts-ignore
                [filteredEmployees, showAddEmployeeDialog, showAddEmployeeDialog, newEmployee, newEmployee, newEmployee, newEmployee, newEmployee, positionOptions, departmentOptions,];
            } });
    var __VLS_216;
    var __VLS_217;
    var __VLS_220 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_221 = __VLS_asFunctionalComponent1(__VLS_220, new __VLS_220(__assign({ 'onClick': {} }, { label: "Add", size: "small" })));
    var __VLS_222 = __VLS_221.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_221), false));
    var __VLS_225 = void 0;
    var __VLS_226 = ({ click: {} },
        { onClick: (__VLS_ctx.addEmployee) });
    var __VLS_223;
    var __VLS_224;
    // @ts-ignore
    [addEmployee,];
}
// @ts-ignore
[];
var __VLS_184;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
