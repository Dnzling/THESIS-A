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
var _a;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
// State
var loading = (0, vue_1.ref)(false);
var showViewDialog = (0, vue_1.ref)(false);
var showRejectDialog = (0, vue_1.ref)(false);
var selectedRequest = (0, vue_1.ref)(null);
var rejectReason = (0, vue_1.ref)('');
var currentMonth = (0, vue_1.ref)(new Date());
var activeTooltip = (0, vue_1.ref)(null);
var storeId = (0, vue_1.ref)(null);
var authStore = (0, auth_1.useAuthStore)();
// Data from API
var leaveRequests = (0, vue_1.ref)([]);
var employees = (0, vue_1.ref)([]);
var leaveCounts = (0, vue_1.ref)({
    pending: 0,
    approved: 0,
    rejected: 0,
    cancelled: 0,
    total: 0
});
// Set authorization header
axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
// Helper: Get initials from name
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
// Helper: Format date
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    catch (_a) {
        return date;
    }
};
// Helper: Format date and time
var formatDateTime = function (date) {
    if (!date)
        return 'N/A';
    try {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    catch (_a) {
        return date;
    }
};
// Helper: Format date range
var formatDateRange = function (start, end) {
    if (!start || !end)
        return '';
    var startDate = new Date(start);
    var endDate = new Date(end);
    if (start === end) {
        return startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    if (startDate.getMonth() === endDate.getMonth()) {
        return "".concat(startDate.getDate(), "-").concat(endDate.getDate(), " ").concat(endDate.toLocaleDateString('en-US', { month: 'short' }));
    }
    return "".concat(startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), " - ").concat(endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
};
// Helper: Get status severity
var getStatusSeverity = function (status) {
    var map = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'cancelled': 'secondary'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'info';
};
// Helper: Transform API data to UI format
var transformLeaveData = function (records) {
    return records.map(function (item) {
        var employee = item.employee || {};
        var approver = item.approver;
        var handoverTo = item.handover_to;
        return {
            // Original fields
            id: item.id,
            employee_id: item.employee_id,
            leave_type: item.leave_type,
            start_date: item.start_date,
            end_date: item.end_date,
            total_days: item.total_days,
            reason: item.reason,
            status: item.status,
            attachment_path: item.attachment_path,
            is_paid: item.is_paid,
            approved_by: item.approved_by,
            approved_at: item.approved_at,
            rejected_reason: item.rejected_reason,
            created_at: item.created_at,
            handover_notes: item.handover_notes,
            handover_to: handoverTo,
            // Transformed fields for UI
            employeeName: employee.fname && employee.lname
                ? "".concat(employee.fname, " ").concat(employee.lname).trim()
                : 'Unknown',
            department: employee.department || 'N/A',
            employeeId: employee.employee_number || "#".concat(employee.id),
            leaveType: item.leave_type
                ? item.leave_type.charAt(0).toUpperCase() + item.leave_type.slice(1).replace(/_/g, ' ')
                : 'Unknown',
            startDate: item.start_date,
            endDate: item.end_date,
            duration: item.total_days || 0,
            submittedDate: item.created_at,
            // Employee object for reference
            employee: employee,
            // Approver info
            approver: approver ? {
                id: approver.id,
                fname: approver.fname,
                lname: approver.lname,
                full_name: approver.full_name || "".concat(approver.fname, " ").concat(approver.lname).trim()
            } : null
        };
    });
};
// API Functions
var fetchLeaves = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, records, counts_1, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('api/leaves')];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    records = response.data.data.data || response.data.data || [];
                    // Transform API data to match UI format
                    leaveRequests.value = transformLeaveData(records);
                    // Update counts if provided by API
                    if (response.data.counts) {
                        leaveCounts.value = response.data.counts;
                    }
                    else {
                        counts_1 = {
                            pending: 0,
                            approved: 0,
                            rejected: 0,
                            cancelled: 0,
                            total: records.length
                        };
                        records.forEach(function (item) {
                            var _a;
                            var status = (_a = item.status) === null || _a === void 0 ? void 0 : _a.toLowerCase();
                            if (status === 'pending')
                                counts_1.pending++;
                            else if (status === 'approved')
                                counts_1.approved++;
                            else if (status === 'rejected')
                                counts_1.rejected++;
                            else if (status === 'cancelled')
                                counts_1.cancelled++;
                        });
                        leaveCounts.value = counts_1;
                    }
                    // Store store_id for future requests
                    if (response.data.store_id) {
                        storeId.value = response.data.store_id;
                    }
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Error fetching leaves:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load leave requests',
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
var fetchEmployees = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, records, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('api/employees')];
            case 1:
                response = _a.sent();
                if (response.data.success) {
                    records = response.data.data.data || response.data.data || [];
                    employees.value = records.map(function (emp) { return ({
                        id: emp.id,
                        fname: emp.fname,
                        lname: emp.lname,
                        name: "".concat(emp.fname, " ").concat(emp.lname).trim(),
                        department: emp.department || 'N/A',
                        employee_number: emp.employee_number || '',
                        leaveBalance: 15 // Default or from API
                    }); });
                }
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Error fetching employees:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var fetchLeaveBalances = function (employeeId) { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, balanceData_1, employee, totalRemaining, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                params = {};
                if (employeeId) {
                    params.employee_id = employeeId;
                }
                params.year = new Date().getFullYear();
                return [4 /*yield*/, axios_1.default.get('/api/leaves/balance', { params: params })];
            case 1:
                response = _a.sent();
                if (response.data.success) {
                    balanceData_1 = response.data.data;
                    employee = employees.value.find(function (e) { return e.id === balanceData_1.employee_id; });
                    if (employee) {
                        employee.leave_balances = balanceData_1.balance;
                        totalRemaining = Object.values(balanceData_1.balance).reduce(function (sum, type) { return sum + (type.remaining || 0); }, 0);
                        employee.leaveBalance = totalRemaining;
                    }
                }
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Error fetching leave balances:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var approveLeave = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var response, index, error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/leaves/".concat(id, "/approve"), {
                        notes: 'Approved via UI'
                    })];
            case 1:
                response = _c.sent();
                if (response.data.success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Leave request approved successfully',
                        life: 3000
                    });
                    index = leaveRequests.value.findIndex(function (r) { return r.id === id; });
                    if (index !== -1) {
                        leaveRequests.value[index].status = 'approved';
                        leaveRequests.value[index].approved_at = new Date().toISOString();
                    }
                    // Update counts
                    leaveCounts.value.pending--;
                    leaveCounts.value.approved++;
                    showViewDialog.value = false;
                    selectedRequest.value = null;
                }
                return [3 /*break*/, 3];
            case 2:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to approve leave',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var rejectLeave = function (id, reason) { return __awaiter(void 0, void 0, void 0, function () {
    var response, index, error_5;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/leaves/".concat(id, "/reject"), {
                        reason: reason
                    })];
            case 1:
                response = _c.sent();
                if (response.data.success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Leave request rejected',
                        life: 3000
                    });
                    index = leaveRequests.value.findIndex(function (r) { return r.id === id; });
                    if (index !== -1) {
                        leaveRequests.value[index].status = 'rejected';
                        leaveRequests.value[index].rejected_reason = reason;
                    }
                    // Update counts
                    leaveCounts.value.pending--;
                    leaveCounts.value.rejected++;
                    showRejectDialog.value = false;
                    showViewDialog.value = false;
                    selectedRequest.value = null;
                    rejectReason.value = '';
                }
                return [3 /*break*/, 3];
            case 2:
                error_5 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to reject leave',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var cancelLeave = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var response, index, oldStatus, error_6;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.post("/api/leaves/".concat(id, "/cancel"))];
            case 1:
                response = _c.sent();
                if (response.data.success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Leave request cancelled',
                        life: 3000
                    });
                    index = leaveRequests.value.findIndex(function (r) { return r.id === id; });
                    if (index !== -1) {
                        oldStatus = leaveRequests.value[index].status;
                        leaveRequests.value[index].status = 'cancelled';
                        if (oldStatus === 'pending')
                            leaveCounts.value.pending--;
                        else if (oldStatus === 'approved')
                            leaveCounts.value.approved--;
                        leaveCounts.value.cancelled++;
                    }
                }
                return [3 /*break*/, 3];
            case 2:
                error_6 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_6.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to cancel leave',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
// Computed properties
var pendingRequests = (0, vue_1.computed)(function () {
    return leaveRequests.value.filter(function (r) { return r.status === 'pending'; });
});
var pendingCount = (0, vue_1.computed)(function () { return pendingRequests.value.length; });
var onLeaveToday = (0, vue_1.computed)(function () {
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var todayStr = today.toISOString();
    return leaveRequests.value.filter(function (r) {
        return r.status === 'approved' && r.start_date <= todayStr && r.end_date >= todayStr;
    }).length;
});
var approvedThisMonth = (0, vue_1.computed)(function () {
    var thisMonth = new Date().getMonth();
    var thisYear = new Date().getFullYear();
    return leaveRequests.value.filter(function (r) {
        if (r.status !== 'approved')
            return false;
        var startDate = new Date(r.start_date);
        return startDate.getMonth() === thisMonth && startDate.getFullYear() === thisYear;
    }).length;
});
var todayLeaves = (0, vue_1.computed)(function () {
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var todayStr = today.toISOString();
    return leaveRequests.value
        .filter(function (r) {
        return r.status === 'approved' && r.start_date <= todayStr && r.end_date >= todayStr;
    })
        .map(function (r) { return ({
        id: r.id,
        employeeName: r.employeeName,
        department: r.department,
        leaveType: r.leaveType
    }); });
});
var lowBalanceEmployees = (0, vue_1.computed)(function () {
    return employees.value
        .map(function (emp) { return ({
        id: emp.id,
        name: emp.name,
        department: emp.department,
        total: 20,
        remaining: emp.leaveBalance,
        usedPercentage: ((20 - emp.leaveBalance) / 20) * 100
    }); })
        .filter(function (emp) { return emp.remaining < 10; })
        .sort(function (a, b) { return a.remaining - b.remaining; })
        .slice(0, 3);
});
var currentMonthLabel = (0, vue_1.computed)(function () {
    return currentMonth.value.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
var calendarDays = (0, vue_1.computed)(function () {
    var year = currentMonth.value.getFullYear();
    var month = currentMonth.value.getMonth();
    var firstDay = new Date(year, month, 1);
    var lastDay = new Date(year, month + 1, 0);
    var days = [];
    var startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
    // Previous month days
    for (var i = startOffset; i > 0; i--) {
        days.push({
            date: new Date(year, month, -i + 1),
            isCurrentMonth: false,
            hasLeave: false,
            leaves: []
        });
    }
    var _loop_3 = function (i) {
        var date = new Date(year, month, i);
        date.setHours(0, 0, 0, 0);
        // Find leaves on this day
        var leavesOnDay = leaveRequests.value.filter(function (l) {
            if (l.status !== 'approved')
                return false;
            var startDate = new Date(l.start_date);
            startDate.setHours(0, 0, 0, 0);
            var endDate = new Date(l.end_date);
            endDate.setHours(23, 59, 59, 999);
            return date >= startDate && date <= endDate;
        }).map(function (l) { return ({
            id: l.id,
            employeeName: l.employeeName,
            leaveType: l.leaveType
        }); });
        days.push({
            date: date,
            isCurrentMonth: true,
            hasLeave: leavesOnDay.length > 0,
            leaves: leavesOnDay
        });
    };
    // Current month days
    for (var i = 1; i <= lastDay.getDate(); i++) {
        _loop_3(i);
    }
    // Next month days to fill the grid
    var remainingDays = 42 - days.length; // 6 rows x 7 days
    for (var i = 1; i <= remainingDays; i++) {
        days.push({
            date: new Date(year, month + 1, i),
            isCurrentMonth: false,
            hasLeave: false,
            leaves: []
        });
    }
    return days;
});
// Actions
var viewRequest = function (request) {
    selectedRequest.value = request;
    showViewDialog.value = true;
};
var approveRequest = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!selectedRequest.value)
                    return [2 /*return*/];
                return [4 /*yield*/, approveLeave(selectedRequest.value.id)];
            case 1:
                _a.sent();
                return [2 /*return*/];
        }
    });
}); };
var openRejectDialog = function () {
    showViewDialog.value = false;
    showRejectDialog.value = true;
};
var rejectRequest = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!rejectReason.value.trim()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Warning',
                        detail: 'Please provide a reason for rejection',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                if (!selectedRequest.value)
                    return [2 /*return*/];
                return [4 /*yield*/, rejectLeave(selectedRequest.value.id, rejectReason.value)];
            case 1:
                _a.sent();
                return [2 /*return*/];
        }
    });
}); };
var previousMonth = function () {
    var date = new Date(currentMonth.value);
    date.setMonth(date.getMonth() - 1);
    currentMonth.value = date;
};
var nextMonth = function () {
    var date = new Date(currentMonth.value);
    date.setMonth(date.getMonth() + 1);
    currentMonth.value = date;
};
var showDayTooltip = function (day) {
    if (day.hasLeave) {
        activeTooltip.value = calendarDays.value.indexOf(day);
    }
};
var hideTooltip = function () {
    activeTooltip.value = null;
};
var viewDayDetails = function (day) {
    if (day.hasLeave) {
        console.log('Leaves on this day:', day.leaves);
    }
};
var viewAllBalances = function () {
    router.push({ name: 'hr.leave.balances' });
};
// Load data on mount 
(0, vue_1.onMounted)(function () {
    fetchLeaves();
    fetchEmployees();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 max-w-7xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow-md']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500 mb-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.pendingCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow-md']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500 mb-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.onLeaveToday);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow-md']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500 mb-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.approvedThisMonth);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2" }));
/** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b border-gray-100 bg-gray-50/50" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded-full" }));
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['px-2']} */ ;
/** @type {__VLS_StyleScopedClasses['py-1']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
(__VLS_ctx.pendingRequests.length);
if (__VLS_ctx.pendingRequests.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-12 text-center" }));
    /** @type {__VLS_StyleScopedClasses['p-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-4xl text-gray-300 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "divide-y divide-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['divide-y']} */ ;
    /** @type {__VLS_StyleScopedClasses['divide-gray-100']} */ ;
    var _loop_1 = function (req) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (req.id) }, { class: "p-4 hover:bg-gray-50/50 transition-colors" }));
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50/50']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between mb-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        var __VLS_0 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ label: (__VLS_ctx.getInitials(req.employeeName)), size: "large" }, { class: "bg-blue-100 text-blue-600 font-medium" })));
        var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(req.employeeName)), size: "large" }, { class: "bg-blue-100 text-blue-600 font-medium" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        (req.employeeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (req.department);
        var __VLS_5 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5({
            value: (req.leaveType),
            severity: "info",
            rounded: true,
        }));
        var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([{
                value: (req.leaveType),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_6), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4 text-sm mb-3 ml-14" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-14']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1 text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (__VLS_ctx.formatDateRange(req.startDate, req.endDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1 text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (req.duration);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "ml-14" }));
        /** @type {__VLS_StyleScopedClasses['ml-14']} */ ;
        var __VLS_10 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ 'onClick': {} }, { label: "View Details", icon: "pi pi-eye", size: "small", severity: "info", outlined: true })));
        var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View Details", icon: "pi pi-eye", size: "small", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_11), false));
        var __VLS_15 = void 0;
        var __VLS_16 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.pendingRequests.length === 0))
                        return;
                    __VLS_ctx.viewRequest(req);
                    // @ts-ignore
                    [pendingCount, onLeaveToday, approvedThisMonth, pendingRequests, pendingRequests, pendingRequests, getInitials, formatDateRange, viewRequest,];
                } });
        // @ts-ignore
        [];
    };
    var __VLS_13, __VLS_14;
    for (var _i = 0, _b = __VLS_vFor((__VLS_ctx.pendingRequests)); _i < _b.length; _i++) {
        var req = _b[_i][0];
        _loop_1(req);
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b border-gray-100 bg-gray-50/50" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
if (__VLS_ctx.todayLeaves.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-6" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sun text-3xl text-gray-300 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-sun']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.todayLeaves)); _c < _d.length; _c++) {
        var leave = _d[_c][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (leave.id) }, { class: "flex items-center gap-3 p-3 bg-gray-50 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        var __VLS_17 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ label: (__VLS_ctx.getInitials(leave.employeeName)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })));
        var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(leave.employeeName)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 min-w-0" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium text-gray-800 truncate" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
        (leave.employeeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (leave.department);
        var __VLS_22 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_23 = __VLS_asFunctionalComponent1(__VLS_22, new __VLS_22({
            value: (leave.leaveType),
            severity: "info",
            size: "small",
            rounded: true,
        }));
        var __VLS_24 = __VLS_23.apply(void 0, __spreadArray([{
                value: (leave.leaveType),
                severity: "info",
                size: "small",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_23), false));
        // @ts-ignore
        [getInitials, todayLeaves, todayLeaves,];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b border-gray-100 bg-gray-50/50" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 space-y-4" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
for (var _e = 0, _f = __VLS_vFor((__VLS_ctx.lowBalanceEmployees)); _e < _f.length; _e++) {
    var emp = _f[_e][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (emp.id) }, { class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between text-sm" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_27 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_28 = __VLS_asFunctionalComponent1(__VLS_27, new __VLS_27(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_29 = __VLS_28.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_28), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    (emp.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-blue-600 font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (emp.remaining);
    (emp.total);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full bg-gray-100 rounded-full h-1.5" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-500 h-1.5 rounded-full transition-all" }, { style: ({ width: emp.usedPercentage + '%' }) }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    // @ts-ignore
    [getInitials, lowBalanceEmployees,];
}
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign({ 'onClick': {} }, { label: "View All Balances", link: true, size: "small" }), { class: "w-full mt-2 text-blue-600" })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "View All Balances", link: true, size: "small" }), { class: "w-full mt-2 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ click: {} },
    { onClick: (__VLS_ctx.viewAllBalances) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
var __VLS_35;
var __VLS_36;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b border-gray-100 bg-gray-50/50" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-1 bg-gray-100 rounded-lg p-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-1']} */ ;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, size: "small" }), { class: "p-1 w-6 h-6" })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, size: "small" }), { class: "p-1 w-6 h-6" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
var __VLS_44;
var __VLS_45 = ({ click: {} },
    { onClick: (__VLS_ctx.previousMonth) });
/** @type {__VLS_StyleScopedClasses['p-1']} */ ;
/** @type {__VLS_StyleScopedClasses['w-6']} */ ;
/** @type {__VLS_StyleScopedClasses['h-6']} */ ;
var __VLS_42;
var __VLS_43;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium px-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['px-2']} */ ;
(__VLS_ctx.currentMonthLabel);
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, size: "small" }), { class: "p-1 w-6 h-6" })));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, size: "small" }), { class: "p-1 w-6 h-6" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51;
var __VLS_52 = ({ click: {} },
    { onClick: (__VLS_ctx.nextMonth) });
/** @type {__VLS_StyleScopedClasses['p-1']} */ ;
/** @type {__VLS_StyleScopedClasses['w-6']} */ ;
/** @type {__VLS_StyleScopedClasses['h-6']} */ ;
var __VLS_49;
var __VLS_50;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-7 gap-1 mb-2" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-7']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
for (var _g = 0, _h = __VLS_vFor((__VLS_ctx.weekDays)); _g < _h.length; _g++) {
    var day = _h[_g][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (day) }, { class: "text-center text-xs font-medium text-gray-500 py-1" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
    (day);
    // @ts-ignore
    [viewAllBalances, previousMonth, currentMonthLabel, nextMonth, weekDays,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-7 gap-1" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-7']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
var _loop_2 = function (day, index) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign(__assign(__assign({ onMouseenter: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showDayTooltip(day);
            // @ts-ignore
            [calendarDays, showDayTooltip,];
        } }, { onMouseleave: (__VLS_ctx.hideTooltip) }), { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.viewDayDetails(day);
            // @ts-ignore
            [hideTooltip, viewDayDetails,];
        } }), { key: (index) }), { class: "relative group" }));
    /** @type {__VLS_StyleScopedClasses['relative']} */ ;
    /** @type {__VLS_StyleScopedClasses['group']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "aspect-square flex items-center justify-center text-sm rounded-lg cursor-pointer transition-all" }, { class: ([
            day.isCurrentMonth ? 'text-gray-700' : 'text-gray-300',
            day.hasLeave ? 'bg-blue-50 text-blue-600 font-medium hover:bg-blue-100' : 'hover:bg-gray-50'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['aspect-square']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    (day.date.getDate());
    if (__VLS_ctx.activeTooltip === index && day.hasLeave) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-10 min-w-50" }));
        /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
        /** @type {__VLS_StyleScopedClasses['bottom-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['left-1/2']} */ ;
        /** @type {__VLS_StyleScopedClasses['transform']} */ ;
        /** @type {__VLS_StyleScopedClasses['-translate-x-1/2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['z-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['min-w-50']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-lg shadow-lg border border-gray-100 p-2 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['shadow-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-700 mb-1 px-2" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
        for (var _m = 0, _o = __VLS_vFor((day.leaves)); _m < _o.length; _m++) {
            var leave = _o[_m][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (leave.id) }, { class: "flex items-center justify-between gap-2 px-2 py-1 hover:bg-gray-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (leave.employeeName);
            var __VLS_53 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
                value: (leave.leaveType),
                severity: "info",
                size: "small",
                rounded: true,
            }));
            var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
                    value: (leave.leaveType),
                    severity: "info",
                    size: "small",
                    rounded: true,
                }], __VLS_functionalComponentArgsRest(__VLS_54), false));
            // @ts-ignore
            [activeTooltip,];
        }
    }
    // @ts-ignore
    [];
};
for (var _j = 0, _k = __VLS_vFor((__VLS_ctx.calendarDays)); _j < _k.length; _j++) {
    var _l = _k[_j], day = _l[0], index = _l[1];
    _loop_2(day, index);
}
var __VLS_58;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign(__assign(__assign({ visible: (__VLS_ctx.showViewDialog), modal: true }, { style: ({ width: '480px' }) }), { class: "rounded-xl" }), { closable: (true) })));
var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showViewDialog), modal: true }, { style: ({ width: '480px' }) }), { class: "rounded-xl" }), { closable: (true) })], __VLS_functionalComponentArgsRest(__VLS_59), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_63 = __VLS_61.slots.default;
{
    var __VLS_64 = __VLS_61.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-text text-blue-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file-text']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    // @ts-ignore
    [showViewDialog,];
}
if (__VLS_ctx.selectedRequest) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-5" }));
    /** @type {__VLS_StyleScopedClasses['space-y-5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4 pb-4 border-b border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedRequest.employeeName)), size: "xlarge" }, { class: "bg-blue-100 text-blue-600 font-medium text-xl" })));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedRequest.employeeName)), size: "xlarge" }, { class: "bg-blue-100 text-blue-600 font-medium text-xl" })], __VLS_functionalComponentArgsRest(__VLS_66), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-lg text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (__VLS_ctx.selectedRequest.employeeName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.selectedRequest.department);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.selectedRequest.employeeId);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70({
        value: (__VLS_ctx.selectedRequest.leaveType),
        severity: "info",
        rounded: true,
    }));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.selectedRequest.leaveType),
            severity: "info",
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_71), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (__VLS_ctx.selectedRequest.duration);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedRequest.startDate));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedRequest.endDate));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    (__VLS_ctx.selectedRequest.reason);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatDateTime(__VLS_ctx.selectedRequest.submittedDate));
}
{
    var __VLS_75 = __VLS_61.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 justify-end" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign(__assign({ 'onClick': {} }, { label: "Reject", icon: "pi pi-times", severity: "danger", outlined: true }), { class: "px-4" })));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Reject", icon: "pi pi-times", severity: "danger", outlined: true }), { class: "px-4" })], __VLS_functionalComponentArgsRest(__VLS_77), false));
    var __VLS_81 = void 0;
    var __VLS_82 = ({ click: {} },
        { onClick: (__VLS_ctx.openRejectDialog) });
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    var __VLS_79;
    var __VLS_80;
    var __VLS_83 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign(__assign({ 'onClick': {} }, { label: "Approve", icon: "pi pi-check", severity: "info" }), { class: "px-4" })));
    var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Approve", icon: "pi pi-check", severity: "info" }), { class: "px-4" })], __VLS_functionalComponentArgsRest(__VLS_84), false));
    var __VLS_88 = void 0;
    var __VLS_89 = ({ click: {} },
        { onClick: (__VLS_ctx.approveRequest) });
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    var __VLS_86;
    var __VLS_87;
    // @ts-ignore
    [getInitials, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, selectedRequest, formatDate, formatDate, formatDateTime, openRejectDialog, approveRequest,];
}
// @ts-ignore
[];
var __VLS_61;
var __VLS_90;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign(__assign(__assign({ visible: (__VLS_ctx.showRejectDialog), modal: true }, { style: ({ width: '380px' }) }), { class: "rounded-xl" }), { closable: (false) })));
var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showRejectDialog), modal: true }, { style: ({ width: '380px' }) }), { class: "rounded-xl" }), { closable: (false) })], __VLS_functionalComponentArgsRest(__VLS_91), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_95 = __VLS_93.slots.default;
{
    var __VLS_96 = __VLS_93.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-red-600" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    // @ts-ignore
    [showRejectDialog,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-3 bg-red-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-red-400" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-400']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-red-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
((_a = __VLS_ctx.selectedRequest) === null || _a === void 0 ? void 0 : _a.employeeName);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-medium text-gray-700 block mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_97;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign(__assign({ modelValue: (__VLS_ctx.rejectReason), rows: "3" }, { class: "w-full" }), { placeholder: "Please provide a reason..." })));
var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.rejectReason), rows: "3" }, { class: "w-full" }), { placeholder: "Please provide a reason..." })], __VLS_functionalComponentArgsRest(__VLS_98), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_102 = __VLS_93.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 justify-end" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    var __VLS_103 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true }), { class: "px-4" })));
    var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true }), { class: "px-4" })], __VLS_functionalComponentArgsRest(__VLS_104), false));
    var __VLS_108 = void 0;
    var __VLS_109 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showRejectDialog = false;
                // @ts-ignore
                [selectedRequest, showRejectDialog, rejectReason,];
            } });
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    var __VLS_106;
    var __VLS_107;
    var __VLS_110 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign(__assign({ 'onClick': {} }, { label: "Reject Request", severity: "danger" }), { class: "px-4" })));
    var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Reject Request", severity: "danger" }), { class: "px-4" })], __VLS_functionalComponentArgsRest(__VLS_111), false));
    var __VLS_115 = void 0;
    var __VLS_116 = ({ click: {} },
        { onClick: (__VLS_ctx.rejectRequest) });
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    var __VLS_113;
    var __VLS_114;
    // @ts-ignore
    [rejectRequest,];
}
// @ts-ignore
[];
var __VLS_93;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
