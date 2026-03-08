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
var axios_1 = require("axios");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../../../stores/auth");
var props = defineProps();
var emit = defineEmits(['update:attendance', 'export', 'edit']);
var authStore = (0, auth_1.useAuthStore)();
var toast = (0, usetoast_1.useToast)();
// State
var loading = (0, vue_1.ref)(false);
var attendanceHistory = (0, vue_1.ref)([]);
var employeeInfo = (0, vue_1.ref)({
    id: null,
    employee_id: null,
    name: '',
    department: null,
    position: null
});
var summary = (0, vue_1.ref)({
    total_days: 0,
    present: 0,
    absent: 0,
    late: 0,
    half_day: 0,
    on_leave: 0,
    holiday: 0,
    total_worked_minutes: 0,
    total_worked_hours: 0,
    total_late_minutes: 0,
    total_overtime_minutes: 0,
    total_night_differential_minutes: 0
});
// Date range state
var dateRange = (0, vue_1.ref)({
    startDate: null,
    endDate: null
});
// Quick month selection
var selectedQuickMonth = (0, vue_1.ref)('current');
var quickMonths = [
    { label: 'Current Month', value: 'current' },
    { label: 'Last Month', value: 'last' },
    { label: 'Last 3 Months', value: 'last3' },
    { label: 'Year to Date', value: 'ytd' },
    { label: 'All Time', value: 'all' }
];
// Details dialog
var showDetailsDialog = (0, vue_1.ref)(false);
var selectedAttendance = (0, vue_1.ref)(null);
// Computed properties
var isDateRangeValid = (0, vue_1.computed)(function () {
    return dateRange.value.startDate && dateRange.value.endDate;
});
var dateRangeDisplay = (0, vue_1.computed)(function () {
    if (dateRange.value.startDate && dateRange.value.endDate) {
        var start = formatDateForDisplay(dateRange.value.startDate);
        var end = formatDateForDisplay(dateRange.value.endDate);
        return "".concat(start, " - ").concat(end);
    }
    return 'Select date range';
});
// Helper Functions
var formatDateForDisplay = function (date) {
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
var formatMinutes = function (minutes) {
    if (!minutes || minutes === 0)
        return '0 min';
    var hrs = Math.floor(minutes / 60);
    var mins = minutes % 60;
    return hrs > 0 ? "".concat(hrs, "h ").concat(mins, "m") : "".concat(mins, "m");
};
var formatShiftTime = function (timeString) {
    if (!timeString)
        return '—';
    var date = new Date(timeString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};
var getAttendanceSeverity = function (status) {
    var map = {
        'present': 'success',
        'late': 'warning',
        'absent': 'danger',
        'half_day': 'warn',
        'on_leave': 'info',
        'holiday': 'secondary'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'secondary';
};
var canEditAttendance = function (attendance) {
    if (!attendance)
        return false;
    // Add your edit permission logic here
    // For example, only allow editing if not clocked out or if user has admin role
    return true; // or implement your logic
};
// Date calculation functions
var getDateRangeFromQuickMonth = function (type) {
    var now = new Date();
    var start = new Date();
    var end = new Date();
    switch (type) {
        case 'current':
            start.setDate(1);
            end.setMonth(now.getMonth() + 1, 0);
            break;
        case 'last':
            start.setMonth(now.getMonth() - 1, 1);
            end.setMonth(now.getMonth(), 0);
            break;
        case 'last3':
            start.setMonth(now.getMonth() - 3, 1);
            end.setDate(now.getDate());
            break;
        case 'ytd':
            start.setMonth(0, 1);
            end.setDate(now.getDate());
            break;
        case 'all':
            return { startDate: null, endDate: null };
    }
    return {
        startDate: start,
        endDate: end
    };
};
// API Functions
var fetchAttendanceData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, err_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!props.employeeId)
                    return [2 /*return*/];
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                params = {
                    employee_id: props.employeeId
                };
                // Add date filters
                if (dateRange.value.startDate) {
                    params.start_date = formatDateForAPI(dateRange.value.startDate);
                }
                if (dateRange.value.endDate) {
                    params.end_date = formatDateForAPI(dateRange.value.endDate);
                }
                return [4 /*yield*/, axios_1.default.get('/api/attendance/by-employee-number', {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        },
                        params: params
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    // Update employee info
                    employeeInfo.value = response.data.data.employee;
                    // Update summary
                    summary.value = response.data.data.summary;
                    // Update attendance history
                    attendanceHistory.value = response.data.data.attendances || [];
                    // Update date range display
                    if (response.data.data.date_range) {
                        dateRange.value.startDate = response.data.data.date_range.start_date
                            ? new Date(response.data.data.date_range.start_date)
                            : null;
                        dateRange.value.endDate = response.data.data.date_range.end_date
                            ? new Date(response.data.data.date_range.end_date)
                            : null;
                    }
                    emit('update:attendance', response.data.data);
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch attendance data',
                    life: 3000
                });
                console.error('Attendance fetch error:', err_1);
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// Helper to format date for API
var formatDateForAPI = function (date) {
    return date.toISOString().split('T')[0];
};
// Date filter handlers
var handleDateSelect = function () {
    // Auto-apply if both dates are selected
    if (dateRange.value.startDate && dateRange.value.endDate) {
        applyDateFilter();
    }
};
var applyDateFilter = function () {
    if (dateRange.value.startDate && dateRange.value.endDate) {
        selectedQuickMonth.value = '';
        fetchAttendanceData();
    }
    else {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please select both start and end dates',
            life: 3000
        });
    }
};
var resetDateFilter = function () {
    // Reset to current month
    var now = new Date();
    dateRange.value = {
        startDate: new Date(now.getFullYear(), now.getMonth(), 1),
        endDate: new Date(now.getFullYear(), now.getMonth() + 1, 0)
    };
    selectedQuickMonth.value = 'current';
    fetchAttendanceData();
};
var selectQuickMonth = function (type) {
    selectedQuickMonth.value = type;
    var range = getDateRangeFromQuickMonth(type);
    dateRange.value.startDate = range.startDate;
    dateRange.value.endDate = range.endDate;
    if (type !== 'all' || (range.startDate && range.endDate)) {
        fetchAttendanceData();
    }
    else {
        // For 'all' type, fetch without date filters
        dateRange.value.startDate = null;
        dateRange.value.endDate = null;
        fetchAttendanceData();
    }
};
// Export function
var exportAttendance = function () {
    if (!props.employeeId)
        return;
    var params = new URLSearchParams();
    params.append('employee_id', props.employeeId.toString());
    if (dateRange.value.startDate) {
        params.append('start_date', formatDateForAPI(dateRange.value.startDate));
    }
    if (dateRange.value.endDate) {
        params.append('end_date', formatDateForAPI(dateRange.value.endDate));
    }
    var url = "/api/attendances/export?".concat(params.toString());
    window.open(url, '_blank');
    emit('export', {
        employeeId: props.employeeId,
        startDate: dateRange.value.startDate,
        endDate: dateRange.value.endDate
    });
};
// View details
var viewAttendanceDetails = function (attendance) {
    selectedAttendance.value = attendance;
    showDetailsDialog.value = true;
};
var editAttendance = function (attendance) {
    showDetailsDialog.value = false;
    emit('edit', attendance);
};
// Expose methods for parent component
var __VLS_exposed = {
    refresh: fetchAttendanceData,
    dateRange: dateRange,
    selectedQuickMonth: selectedQuickMonth,
    employeeInfo: employeeInfo,
    summary: summary
};
defineExpose(__VLS_exposed);
// Watchers
(0, vue_1.watch)(function () { return props.employeeId; }, function () {
    if (props.employeeId) {
        resetDateFilter();
    }
});
// Initialize
(0, vue_1.onMounted)(function () {
    // Set default date range to current month
    var now = new Date();
    dateRange.value.startDate = new Date(now.getFullYear(), now.getMonth(), 1);
    dateRange.value.endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    if (props.initialData) {
        // Handle initial data if provided
        if (props.initialData.attendances) {
            attendanceHistory.value = props.initialData.attendances;
        }
        if (props.initialData.summary) {
            summary.value = props.initialData.summary;
        }
        if (props.initialData.employee) {
            employeeInfo.value = props.initialData.employee;
        }
    }
    else {
        fetchAttendanceData();
    }
});
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-5 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-5']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
(__VLS_ctx.summary.total_days || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-green-600" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
(__VLS_ctx.summary.present || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-red-600" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
(__VLS_ctx.summary.absent || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-orange-600" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
(__VLS_ctx.summary.late || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 rounded-lg p-3" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.summary.total_worked_hours || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50/50 rounded-lg p-2" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-amber-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-amber-600']} */ ;
(__VLS_ctx.summary.half_day || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50/50 rounded-lg p-2" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-purple-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
(__VLS_ctx.summary.on_leave || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50/50 rounded-lg p-2" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-indigo-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
(__VLS_ctx.summary.holiday || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50/50 rounded-lg p-2" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
((__VLS_ctx.summary.total_overtime_minutes / 60).toFixed(1) || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.dateRange.startDate), dateFormat: "yy-mm-dd", maxDate: (__VLS_ctx.dateRange.endDate || new Date()), placeholder: "Start Date" }), { class: "w-36" }), { size: "small" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.dateRange.startDate), dateFormat: "yy-mm-dd", maxDate: (__VLS_ctx.dateRange.endDate || new Date()), placeholder: "Start Date" }), { class: "w-36" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ dateSelect: {} },
    { onDateSelect: (__VLS_ctx.handleDateSelect) });
/** @type {__VLS_StyleScopedClasses['w-36']} */ ;
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.dateRange.endDate), dateFormat: "yy-mm-dd", minDate: (__VLS_ctx.dateRange.startDate), maxDate: (new Date()), placeholder: "End Date" }), { class: "w-36" }), { size: "small" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.dateRange.endDate), dateFormat: "yy-mm-dd", minDate: (__VLS_ctx.dateRange.startDate), maxDate: (new Date()), placeholder: "End Date" }), { class: "w-36" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ dateSelect: {} },
    { onDateSelect: (__VLS_ctx.handleDateSelect) });
/** @type {__VLS_StyleScopedClasses['w-36']} */ ;
var __VLS_10;
var __VLS_11;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onClick': {} }, { label: "Apply Filter", icon: "pi pi-filter", size: "small", disabled: (!__VLS_ctx.isDateRangeValid) })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Apply Filter", icon: "pi pi-filter", size: "small", disabled: (!__VLS_ctx.isDateRangeValid) })], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19;
var __VLS_20 = ({ click: {} },
    { onClick: (__VLS_ctx.applyDateFilter) });
var __VLS_17;
var __VLS_18;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-refresh", severity: "secondary", text: true, size: "small" })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-refresh", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26;
var __VLS_27 = ({ click: {} },
    { onClick: (__VLS_ctx.resetDateFilter) });
var __VLS_24;
var __VLS_25;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "secondary", outlined: true, size: "small" })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "secondary", outlined: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
var __VLS_33;
var __VLS_34 = ({ click: {} },
    { onClick: (__VLS_ctx.exportAttendance) });
var __VLS_31;
var __VLS_32;
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { label: "Refresh", icon: "pi pi-refresh", severity: "secondary", text: true, size: "small" })));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Refresh", icon: "pi pi-refresh", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
var __VLS_40;
var __VLS_41 = ({ click: {} },
    { onClick: (__VLS_ctx.fetchAttendanceData) });
var __VLS_38;
var __VLS_39;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 pb-2 overflow-x-auto" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-2']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-x-auto']} */ ;
var _loop_1 = function (month) {
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ 'onClick': {} }, { key: (month.value), label: (month.label), size: "small", severity: (__VLS_ctx.selectedQuickMonth === month.value ? 'primary' : 'secondary'), outlined: (__VLS_ctx.selectedQuickMonth !== month.value) })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { key: (month.value), label: (month.label), size: "small", severity: (__VLS_ctx.selectedQuickMonth === month.value ? 'primary' : 'secondary'), outlined: (__VLS_ctx.selectedQuickMonth !== month.value) })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    var __VLS_47 = void 0;
    var __VLS_48 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.selectQuickMonth(month.value);
                // @ts-ignore
                [summary, summary, summary, summary, summary, summary, summary, summary, summary, dateRange, dateRange, dateRange, dateRange, handleDateSelect, handleDateSelect, isDateRangeValid, applyDateFilter, resetDateFilter, exportAttendance, fetchAttendanceData, quickMonths, selectedQuickMonth, selectedQuickMonth, selectQuickMonth,];
            } });
    // @ts-ignore
    [];
};
var __VLS_45, __VLS_46;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.quickMonths)); _i < _a.length; _i++) {
    var month = _a[_i][0];
    _loop_1(month);
}
if (__VLS_ctx.employeeInfo.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user text-blue-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-user']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium text-blue-700" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
    (__VLS_ctx.employeeInfo.name);
    if (__VLS_ctx.employeeInfo.employee_id) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-blue-600 ml-2" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
        (__VLS_ctx.employeeInfo.employee_id);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "ml-auto text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.dateRangeDisplay);
}
var __VLS_49;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign({ value: (__VLS_ctx.attendanceHistory), paginator: (true), rows: (15), rowsPerPageOptions: ([10, 15, 20, 50]) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), stripedRows: true, showGridlines: true, sortField: "date", sortOrder: (-1) })));
var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.attendanceHistory), paginator: (true), rows: (15), rowsPerPageOptions: ([10, 15, 20, 50]) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), stripedRows: true, showGridlines: true, sortField: "date", sortOrder: (-1) })], __VLS_functionalComponentArgsRest(__VLS_50), false));
/** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
var __VLS_54 = __VLS_52.slots.default;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign({ field: "date_formatted", header: "Date", sortable: (true) }, { style: {} })));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign({ field: "date_formatted", header: "Date", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_56), false));
var __VLS_60 = __VLS_58.slots.default;
{
    var __VLS_61 = __VLS_58.slots.body;
    var data = __VLS_vSlot(__VLS_61)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.date_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (data.day);
    // @ts-ignore
    [employeeInfo, employeeInfo, employeeInfo, employeeInfo, dateRangeDisplay, attendanceHistory, loading,];
}
// @ts-ignore
[];
var __VLS_58;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62(__assign({ field: "clock_in", header: "Time In", sortable: (true) }, { style: {} })));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([__assign({ field: "clock_in", header: "Time In", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67 = __VLS_65.slots.default;
{
    var __VLS_68 = __VLS_65.slots.body;
    var data = __VLS_vSlot(__VLS_68)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ({ 'text-orange-600 font-semibold': data.is_late }) }));
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (data.clock_in || '—');
    if (data.late_minutes > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-orange-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        (__VLS_ctx.formatMinutes(data.late_minutes));
    }
    // @ts-ignore
    [formatMinutes,];
}
// @ts-ignore
[];
var __VLS_65;
var __VLS_69;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ field: "clock_out", header: "Time Out", sortable: (true) }, { style: {} })));
var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ field: "clock_out", header: "Time Out", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_70), false));
var __VLS_74 = __VLS_72.slots.default;
{
    var __VLS_75 = __VLS_72.slots.body;
    var data = __VLS_vSlot(__VLS_75)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.clock_out || '—');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_72;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ field: "status", header: "Status", sortable: (true) }, { style: {} })));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_77), false));
var __VLS_81 = __VLS_79.slots.default;
{
    var __VLS_82 = __VLS_79.slots.body;
    var data = __VLS_vSlot(__VLS_82)[0].data;
    var __VLS_83 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
        value: (data.status_label),
        severity: (__VLS_ctx.getAttendanceSeverity(data.status)),
        rounded: true,
    }));
    var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
            value: (data.status_label),
            severity: (__VLS_ctx.getAttendanceSeverity(data.status)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_84), false));
    // @ts-ignore
    [getAttendanceSeverity,];
}
// @ts-ignore
[];
var __VLS_79;
var __VLS_88;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ field: "shift_name", header: "Shift", sortable: (true) }, { style: {} })));
var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ field: "shift_name", header: "Shift", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_89), false));
var __VLS_93 = __VLS_91.slots.default;
{
    var __VLS_94 = __VLS_91.slots.body;
    var data = __VLS_vSlot(__VLS_94)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.shift_name || 'No Shift');
    if (data.shift_start && data.shift_end) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.formatShiftTime(data.shift_start));
        (__VLS_ctx.formatShiftTime(data.shift_end));
    }
    // @ts-ignore
    [formatShiftTime, formatShiftTime,];
}
// @ts-ignore
[];
var __VLS_91;
var __VLS_95;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign({ field: "total_worked_hours", header: "Worked", sortable: (true) }, { style: {} })));
var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign({ field: "total_worked_hours", header: "Worked", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_96), false));
var __VLS_100 = __VLS_98.slots.default;
{
    var __VLS_101 = __VLS_98.slots.body;
    var data = __VLS_vSlot(__VLS_101)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.total_worked_hours.toFixed(2));
    if (data.break_minutes > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.formatMinutes(data.break_minutes));
    }
    // @ts-ignore
    [formatMinutes,];
}
// @ts-ignore
[];
var __VLS_98;
var __VLS_102;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102(__assign({ field: "overtime_minutes", header: "OT", sortable: (true) }, { style: {} })));
var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([__assign({ field: "overtime_minutes", header: "OT", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_103), false));
var __VLS_107 = __VLS_105.slots.default;
{
    var __VLS_108 = __VLS_105.slots.body;
    var data = __VLS_vSlot(__VLS_108)[0].data;
    if (data.overtime_minutes > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-blue-600 font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        ((data.overtime_minutes / 60).toFixed(1));
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_105;
var __VLS_109;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ field: "night_differential_minutes", header: "Night Diff", sortable: (true) }, { style: {} })));
var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ field: "night_differential_minutes", header: "Night Diff", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_110), false));
var __VLS_114 = __VLS_112.slots.default;
{
    var __VLS_115 = __VLS_112.slots.body;
    var data = __VLS_vSlot(__VLS_115)[0].data;
    if (data.night_differential_minutes > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-purple-600 font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        ((data.night_differential_minutes / 60).toFixed(1));
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_112;
var __VLS_116;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116(__assign({ field: "clock_in_method", header: "Method" }, { style: {} })));
var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([__assign({ field: "clock_in_method", header: "Method" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_117), false));
var __VLS_121 = __VLS_119.slots.default;
{
    var __VLS_122 = __VLS_119.slots.body;
    var data = __VLS_vSlot(__VLS_122)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "capitalize" }));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    (data.clock_in_method || '—');
    if (data.clock_out_method) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500 block text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        (data.clock_out_method);
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_119;
var __VLS_123;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123(__assign({ field: "notes", header: "Notes" }, { style: {} })));
var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([__assign({ field: "notes", header: "Notes" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_124), false));
var __VLS_128 = __VLS_126.slots.default;
{
    var __VLS_129 = __VLS_126.slots.body;
    var data = __VLS_vSlot(__VLS_129)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (data.notes || '—');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_126;
var __VLS_130;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_131 = __VLS_asFunctionalComponent1(__VLS_130, new __VLS_130(__assign({ header: "Actions" }, { style: {} })));
var __VLS_132 = __VLS_131.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_131), false));
var __VLS_135 = __VLS_133.slots.default;
{
    var __VLS_136 = __VLS_133.slots.body;
    var data_1 = __VLS_vSlot(__VLS_136)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_137 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_138 = __VLS_asFunctionalComponent1(__VLS_137, new __VLS_137(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, size: "small" })));
    var __VLS_139 = __VLS_138.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_138), false));
    var __VLS_142 = void 0;
    var __VLS_143 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewAttendanceDetails(data_1);
                // @ts-ignore
                [viewAttendanceDetails,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View Details') }), null, null);
    var __VLS_140;
    var __VLS_141;
    if (__VLS_ctx.canEditAttendance(data_1)) {
        var __VLS_144 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, size: "small", severity: "info" })));
        var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_145), false));
        var __VLS_149 = void 0;
        var __VLS_150 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canEditAttendance(data_1)))
                        return;
                    __VLS_ctx.editAttendance(data_1);
                    // @ts-ignore
                    [vTooltip, canEditAttendance, editAttendance,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Edit') }), null, null);
        var __VLS_147;
        var __VLS_148;
    }
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_133;
// @ts-ignore
[];
var __VLS_52;
if (!__VLS_ctx.loading && __VLS_ctx.attendanceHistory.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-4xl mb-2 block text-gray-300" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
}
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _b = 0, _c = __VLS_vFor((5)); _b < _c.length; _b++) {
        var i = _c[_b][0];
        var __VLS_151 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton | typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151({
            key: (i),
            height: "3rem",
        }));
        var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([{
                key: (i),
                height: "3rem",
            }], __VLS_functionalComponentArgsRest(__VLS_152), false));
        // @ts-ignore
        [attendanceHistory, loading, loading,];
    }
}
var __VLS_156;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_157 = __VLS_asFunctionalComponent1(__VLS_156, new __VLS_156(__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: "Attendance Details" }, { style: ({ width: '450px' }) }), { modal: (true) })));
var __VLS_158 = __VLS_157.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: "Attendance Details" }, { style: ({ width: '450px' }) }), { modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_157), false));
var __VLS_161 = __VLS_159.slots.default;
if (__VLS_ctx.selectedAttendance) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedAttendance.date_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedAttendance.day);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_162 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_163 = __VLS_asFunctionalComponent1(__VLS_162, new __VLS_162({
        value: (__VLS_ctx.selectedAttendance.status_label),
        severity: (__VLS_ctx.getAttendanceSeverity(__VLS_ctx.selectedAttendance.status)),
    }));
    var __VLS_164 = __VLS_163.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.selectedAttendance.status_label),
            severity: (__VLS_ctx.getAttendanceSeverity(__VLS_ctx.selectedAttendance.status)),
        }], __VLS_functionalComponentArgsRest(__VLS_163), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }, { class: ({ 'text-orange-600': __VLS_ctx.selectedAttendance.is_late }) }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    (__VLS_ctx.selectedAttendance.clock_in || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedAttendance.clock_in_method || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedAttendance.clock_out || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedAttendance.clock_out_method || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-2" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-orange-600" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    (__VLS_ctx.formatMinutes(__VLS_ctx.selectedAttendance.late_minutes));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatMinutes(__VLS_ctx.selectedAttendance.break_minutes));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatMinutes(__VLS_ctx.selectedAttendance.overtime_minutes));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedAttendance.shift_name || 'No Shift');
    if (__VLS_ctx.selectedAttendance.shift_start) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (__VLS_ctx.formatShiftTime(__VLS_ctx.selectedAttendance.shift_start));
        (__VLS_ctx.formatShiftTime(__VLS_ctx.selectedAttendance.shift_end));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (__VLS_ctx.selectedAttendance.total_worked_hours.toFixed(2));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedAttendance.total_worked_minutes);
    if (__VLS_ctx.selectedAttendance.notes) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.selectedAttendance.notes);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 pt-2 border-t" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    (__VLS_ctx.selectedAttendance.id);
}
{
    var __VLS_167 = __VLS_159.slots.footer;
    var __VLS_168 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_169 = __VLS_asFunctionalComponent1(__VLS_168, new __VLS_168(__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", text: true })));
    var __VLS_170 = __VLS_169.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_169), false));
    var __VLS_173 = void 0;
    var __VLS_174 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsDialog = false;
                // @ts-ignore
                [formatMinutes, formatMinutes, formatMinutes, getAttendanceSeverity, formatShiftTime, formatShiftTime, showDetailsDialog, showDetailsDialog, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance,];
            } });
    var __VLS_171;
    var __VLS_172;
    if (__VLS_ctx.canEditAttendance(__VLS_ctx.selectedAttendance)) {
        var __VLS_175 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", autofocus: true })));
        var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", autofocus: true })], __VLS_functionalComponentArgsRest(__VLS_176), false));
        var __VLS_180 = void 0;
        var __VLS_181 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canEditAttendance(__VLS_ctx.selectedAttendance)))
                        return;
                    __VLS_ctx.editAttendance(__VLS_ctx.selectedAttendance);
                    // @ts-ignore
                    [canEditAttendance, editAttendance, selectedAttendance, selectedAttendance,];
                } });
        var __VLS_178;
        var __VLS_179;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_159;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    setup: function () { return (__VLS_exposed); },
    emits: {},
    __typeProps: {},
});
exports.default = {};
