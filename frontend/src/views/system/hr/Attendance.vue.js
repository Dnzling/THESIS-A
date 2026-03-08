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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
// Toast & Auth
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var showEditDialog = (0, vue_1.ref)(false);
var showDetailsDialog = (0, vue_1.ref)(false);
var attendanceData = (0, vue_1.ref)([]);
var selectedAttendance = (0, vue_1.ref)(null);
var editingAttendance = (0, vue_1.ref)(null);
// Filters
var filters = (0, vue_1.reactive)({
    search: '',
    status: null,
    dateRange: null
});
// Status Options
var statusFilterOptions = [
    { label: 'Present', value: 'present' },
    { label: 'Late', value: 'late' },
    { label: 'Absent', value: 'absent' },
    { label: 'On Leave', value: 'on_leave' },
    { label: 'Half Day', value: 'half_day' }
];
var statusOptions = [
    { label: 'Present', value: 'present' },
    { label: 'Late', value: 'late' },
    { label: 'Absent', value: 'absent' },
    { label: 'On Leave', value: 'on_leave' },
    { label: 'Half Day', value: 'half_day' }
];
// Stats
var summary = (0, vue_1.ref)({
    present: 0,
    late: 0,
    absent: 0,
    on_leave: 0,
    half_day: 0,
    total: 0
});
var stats = (0, vue_1.computed)(function () { return [
    {
        label: 'Present',
        value: summary.value.present,
        icon: 'pi pi-check',
        colorClass: 'text-green-600',
        bgClass: 'bg-green-50',
        iconClass: 'text-green-500'
    },
    {
        label: 'Late',
        value: summary.value.late,
        icon: 'pi pi-clock',
        colorClass: 'text-orange-600',
        bgClass: 'bg-orange-50',
        iconClass: 'text-orange-500'
    },
    {
        label: 'Absent',
        value: summary.value.absent,
        icon: 'pi pi-times',
        colorClass: 'text-red-600',
        bgClass: 'bg-red-50',
        iconClass: 'text-red-500'
    },
    {
        label: 'On Leave',
        value: summary.value.on_leave,
        icon: 'pi pi-calendar',
        colorClass: 'text-blue-600',
        bgClass: 'bg-blue-50',
        iconClass: 'text-blue-500'
    }
]; });
var hasActiveFilters = (0, vue_1.computed)(function () {
    return filters.search !== '' || filters.status !== null || filters.dateRange !== null;
});
// Helper Functions
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getStatusSeverity = function (status) {
    var map = {
        'present': 'success',
        'absent': 'danger',
        'on_leave': 'info',
        'half_day': 'warning',
        'late': 'danger'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'secondary';
};
var formatStatus = function (status) {
    var map = {
        'present': 'Present',
        'late': 'Late',
        'absent': 'Absent',
        'on_leave': 'On Leave',
        'half_day': 'Half Day',
        'holiday': 'Holiday'
    };
    return map[status] || status || 'Unknown';
};
var formatDate = function (dateString) {
    if (!dateString)
        return '--';
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    catch (_a) {
        return dateString;
    }
};
var formatTime = function (dateTimeString) {
    if (!dateTimeString)
        return '';
    try {
        var date = new Date(dateTimeString);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'Asia/Manila' // Add this
        });
    }
    catch (_a) {
        return '';
    }
};
var formatTime24 = function (dateTimeString) {
    if (!dateTimeString)
        return '';
    try {
        var date = new Date(dateTimeString);
        // Convert to local time first
        var localDate = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
        return localDate.toTimeString().slice(0, 5);
    }
    catch (_a) {
        return '';
    }
};
var minutesToHours = function (minutes) {
    var hours = Math.floor(minutes / 60);
    var mins = minutes % 60;
    return mins > 0 ? "".concat(hours, "h ").concat(mins, "m") : "".concat(hours, "h");
};
var formatDayOfWeek = function (dateString) {
    if (!dateString)
        return '--';
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', { weekday: 'short' });
    }
    catch (_a) {
        return '--';
    }
};
// Transform API data to UI format
var transformAttendance = function (item) {
    var employee = item.employee || {};
    var shift = item.shift || {};
    return {
        id: item.id,
        attendanceDate: formatDate(item.attendance_date),
        attendanceDateRaw: item.attendance_date,
        dayOfWeek: formatDayOfWeek(item.attendance_date),
        employee: {
            id: employee.id,
            name: employee.fname && employee.lname
                ? "".concat(employee.fname, " ").concat(employee.lname).trim()
                : 'Unknown',
            department: employee.department || 'N/A'
        },
        clockIn: formatTime(item.clock_in),
        clockInRaw: item.clock_in,
        clockInTime: formatTime(item.clock_in),
        clockOut: formatTime(item.clock_out),
        clockOutRaw: item.clock_out,
        clockOutTime: formatTime(item.clock_out),
        isLate: item.status === 'late',
        lateMinutes: item.late_minutes || 0,
        breakMinutes: item.break_minutes || 0,
        totalWorkedMinutes: item.total_worked_minutes || 0,
        totalHours: minutesToHours(item.total_worked_minutes || 0),
        overtimeMinutes: item.overtime_minutes || 0,
        overtimeHours: minutesToHours(item.overtime_minutes || 0),
        status: item.status,
        notes: item.notes || null,
        shift: shift.id ? {
            id: shift.id,
            name: shift.name || 'N/A',
            code: shift.code || '',
            startTime: shift.start_time || '',
            endTime: shift.end_time || ''
        } : null
    };
};
// Fetch attendance data
var fetchAttendance = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, records, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                params = {};
                if (filters.search)
                    params.search = filters.search;
                if (filters.status)
                    params.status = filters.status;
                if (filters.dateRange && filters.dateRange[0] && filters.dateRange[1]) {
                    params.start_date = filters.dateRange[0].toISOString().split('T')[0];
                    params.end_date = filters.dateRange[1].toISOString().split('T')[0];
                }
                return [4 /*yield*/, axios_1.default.get('api/attendances', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: params
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    records = response.data.data.data || response.data.data || [];
                    attendanceData.value = records.map(transformAttendance);
                    // Update summary
                    if (response.data.summary) {
                        summary.value = response.data.summary;
                    }
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Fetch attendance error:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch attendance',
                    life: 5000
                });
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// View details
var viewDetails = function (data) {
    selectedAttendance.value = data;
    showDetailsDialog.value = true;
};
// Edit from details
var editFromDetails = function () {
    if (selectedAttendance.value) {
        editAttendance(selectedAttendance.value);
        showDetailsDialog.value = false;
    }
};
// Edit attendance
var editAttendance = function (data) {
    editingAttendance.value = __assign(__assign({}, data), { attendanceDate: data.attendanceDate });
    showEditDialog.value = true;
};
// Save attendance
var saveAttendance = function () { return __awaiter(void 0, void 0, void 0, function () {
    var updateData, date, date, response, error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!editingAttendance.value)
                    return [2 /*return*/];
                saving.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 5, 6, 7]);
                updateData = {
                    status: editingAttendance.value.status,
                    break_minutes: editingAttendance.value.breakMinutes,
                    notes: editingAttendance.value.notes
                };
                // Handle clock_in time
                if (editingAttendance.value.clockInTime) {
                    date = new Date(editingAttendance.value.attendanceDateRaw);
                    updateData.clock_in = "".concat(date.toISOString().split('T')[0], " ").concat(editingAttendance.value.clockInTime, ":00");
                }
                // Handle clock_out time
                if (editingAttendance.value.clockOutTime) {
                    date = new Date(editingAttendance.value.attendanceDateRaw);
                    updateData.clock_out = "".concat(date.toISOString().split('T')[0], " ").concat(editingAttendance.value.clockOutTime, ":00");
                }
                return [4 /*yield*/, axios_1.default.put("api/attendances/".concat(editingAttendance.value.id), updateData, { headers: { 'Authorization': "Bearer ".concat(authStore.token) } })];
            case 2:
                response = _c.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attendance updated successfully',
                    life: 3000
                });
                showEditDialog.value = false;
                return [4 /*yield*/, fetchAttendance()];
            case 3:
                _c.sent();
                _c.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to update attendance',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 6:
                saving.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
// Clear filters
var clearFilters = function () {
    filters.search = '';
    filters.status = null;
    filters.dateRange = null;
    fetchAttendance();
};
// Watch for filter changes
(0, vue_1.watch)([function () { return filters.search; }, function () { return filters.status; }, function () { return filters.dateRange; }], function () {
    var timeoutId = setTimeout(function () { return fetchAttendance(); }, 300);
    return function () { return clearTimeout(timeoutId); };
}, { deep: true });
// Initial fetch
(0, vue_1.onMounted)(function () {
    fetchAttendance();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
for (var _i = 0, _o = __VLS_vFor((__VLS_ctx.stats)); _i < _o.length; _i++) {
    var stat = _o[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (stat.label) }, { class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (stat.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold" }, { class: (stat.colorClass) }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (stat.value);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-lg flex items-center justify-center" }, { class: (stat.bgClass) }));
    /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ([stat.icon, stat.iconClass]) }));
    // @ts-ignore
    [stats,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col md:flex-row justify-between items-start md:items-center gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
/** @type {__VLS_StyleScopedClasses['md:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row gap-4 w-full md:w-auto" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['md:w-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "p-input-icon-left w-full sm:w-64" }));
/** @type {__VLS_StyleScopedClasses['p-input-icon-left']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-64']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5 = __VLS_3.slots.default;
var __VLS_6;
/** @ts-ignore @type {typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_7 = __VLS_asFunctionalComponent1(__VLS_6, new __VLS_6(__assign({ class: "pi pi-search" })));
var __VLS_8 = __VLS_7.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_7), false));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
var __VLS_11;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_12 = __VLS_asFunctionalComponent1(__VLS_11, new __VLS_11(__assign(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "w-full" }), { size: "small" })));
var __VLS_13 = __VLS_12.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "w-full" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_12), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
// @ts-ignore
[filters,];
var __VLS_3;
var __VLS_16;
/** @ts-ignore @type {typeof __VLS_components.Select | typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_17 = __VLS_asFunctionalComponent1(__VLS_16, new __VLS_16(__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusFilterOptions), optionLabel: "label", size: "small", optionValue: "value", placeholder: "All Status", showClear: true }, { class: "w-full sm:w-40" })));
var __VLS_18 = __VLS_17.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusFilterOptions), optionLabel: "label", size: "small", optionValue: "value", placeholder: "All Status", showClear: true }, { class: "w-full sm:w-40" })], __VLS_functionalComponentArgsRest(__VLS_17), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-40']} */ ;
var __VLS_21 = __VLS_19.slots.default;
{
    var __VLS_22 = __VLS_19.slots.option;
    var slotProps = __VLS_vSlot(__VLS_22)[0];
    var __VLS_23 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23({
        value: (slotProps.option.label),
        severity: (__VLS_ctx.getStatusSeverity(slotProps.option.value)),
    }));
    var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([{
            value: (slotProps.option.label),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.option.value)),
        }], __VLS_functionalComponentArgsRest(__VLS_24), false));
    // @ts-ignore
    [filters, statusFilterOptions, getStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_19;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign(__assign({ modelValue: (__VLS_ctx.filters.dateRange), selectionMode: "range", showIcon: true, showClear: true, size: "small", placeholder: "Date Range" }, { class: "w-full sm:w-60" }), { dateFormat: "M dd, yy" })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.dateRange), selectionMode: "range", showIcon: true, showClear: true, size: "small", placeholder: "Date Range" }, { class: "w-full sm:w-60" }), { dateFormat: "M dd, yy" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-60']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
if (__VLS_ctx.hasActiveFilters) {
    var __VLS_33 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ 'onClick': {} }, { label: "Clear", icon: "pi pi-filter-slash", severity: "secondary", text: true, size: "small" })));
    var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear", icon: "pi pi-filter-slash", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
    var __VLS_38 = void 0;
    var __VLS_39 = ({ click: {} },
        { onClick: (__VLS_ctx.clearFilters) });
    var __VLS_36;
    var __VLS_37;
}
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "info", text: true, size: "small" })));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "info", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
var __VLS_45;
var __VLS_46 = ({ click: {} },
    { onClick: (__VLS_ctx.fetchAttendance) });
var __VLS_43;
var __VLS_44;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
var __VLS_47;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_48 = __VLS_asFunctionalComponent1(__VLS_47, new __VLS_47(__assign(__assign({ value: (__VLS_ctx.attendanceData) }, { class: "w-full text-sm" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries", rowHover: true, showGridlines: true, responsiveLayout: "scroll" })));
var __VLS_49 = __VLS_48.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.attendanceData) }, { class: "w-full text-sm" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries", rowHover: true, showGridlines: true, responsiveLayout: "scroll" })], __VLS_functionalComponentArgsRest(__VLS_48), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_52 = __VLS_50.slots.default;
var __VLS_53;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ field: "attendanceDateRaw", header: "Date", sortable: true }, { style: {} })));
var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ field: "attendanceDateRaw", header: "Date", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_54), false));
var __VLS_58 = __VLS_56.slots.default;
{
    var __VLS_59 = __VLS_56.slots.body;
    var data = __VLS_vSlot(__VLS_59)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.attendanceDate);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (data.dayOfWeek);
    // @ts-ignore
    [filters, hasActiveFilters, clearFilters, fetchAttendance, attendanceData, loading,];
}
// @ts-ignore
[];
var __VLS_56;
var __VLS_60;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ field: "employee.name", header: "Employee", sortable: true }, { style: {} })));
var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ field: "employee.name", header: "Employee", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_61), false));
var __VLS_65 = __VLS_63.slots.default;
{
    var __VLS_66 = __VLS_63.slots.body;
    var data = __VLS_vSlot(__VLS_66)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67(__assign({ label: (__VLS_ctx.getInitials(data.employee.name)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.employee.name)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_68), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.employee.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (data.employee.department);
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_63;
var __VLS_72;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign({ header: "Check In" }, { style: {} })));
var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign({ header: "Check In" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_73), false));
var __VLS_77 = __VLS_75.slots.default;
{
    var __VLS_78 = __VLS_75.slots.body;
    var data = __VLS_vSlot(__VLS_78)[0].data;
    if (data.clockInRaw) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (data.clockInTime);
        if (data.isLate) {
            var __VLS_79 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            Badge;
            // @ts-ignore
            var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ value: "Late", severity: "danger" }, { class: "ml-2" })));
            var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ value: "Late", severity: "danger" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_80), false));
            /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
        }
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_75;
var __VLS_84;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign({ header: "Check Out" }, { style: {} })));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign({ header: "Check Out" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_85), false));
var __VLS_89 = __VLS_87.slots.default;
{
    var __VLS_90 = __VLS_87.slots.body;
    var data = __VLS_vSlot(__VLS_90)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (data.clockOut || '--:--');
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
var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91(__assign({ header: "Hours" }, { style: {} })));
var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([__assign({ header: "Hours" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_92), false));
var __VLS_96 = __VLS_94.slots.default;
{
    var __VLS_97 = __VLS_94.slots.body;
    var data = __VLS_vSlot(__VLS_97)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.totalHours);
    if (data.overtimeMinutes > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-orange-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
        (data.overtimeHours);
    }
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
var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_99), false));
var __VLS_103 = __VLS_101.slots.default;
{
    var __VLS_104 = __VLS_101.slots.body;
    var data = __VLS_vSlot(__VLS_104)[0].data;
    var __VLS_105 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105({
        value: (__VLS_ctx.formatStatus(data.status)),
        severity: (__VLS_ctx.getStatusSeverity(data.status)),
    }));
    var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.formatStatus(data.status)),
            severity: (__VLS_ctx.getStatusSeverity(data.status)),
        }], __VLS_functionalComponentArgsRest(__VLS_106), false));
    // @ts-ignore
    [getStatusSeverity, formatStatus,];
}
// @ts-ignore
[];
var __VLS_101;
var __VLS_110;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ header: "Actions", exportable: (false) }, { style: {} })));
var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ header: "Actions", exportable: (false) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_111), false));
var __VLS_115 = __VLS_113.slots.default;
{
    var __VLS_116 = __VLS_113.slots.body;
    var data_1 = __VLS_vSlot(__VLS_116)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_117 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })));
    var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_118), false));
    var __VLS_122 = void 0;
    var __VLS_123 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDetails(data_1);
                // @ts-ignore
                [viewDetails,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View Details') }), null, null);
    var __VLS_120;
    var __VLS_121;
    var __VLS_124 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "contrast", text: true, rounded: true, size: "small" })));
    var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "contrast", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_125), false));
    var __VLS_129 = void 0;
    var __VLS_130 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editAttendance(data_1);
                // @ts-ignore
                [vTooltip, editAttendance,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
    var __VLS_127;
    var __VLS_128;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_113;
{
    var __VLS_131 = __VLS_50.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-4xl text-gray-300 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_50;
var __VLS_132;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_133 = __VLS_asFunctionalComponent1(__VLS_132, new __VLS_132(__assign(__assign({ visible: (__VLS_ctx.showEditDialog), modal: true, header: "Edit Attendance" }, { style: ({ width: '500px' }) }), { draggable: (false) })));
var __VLS_134 = __VLS_133.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showEditDialog), modal: true, header: "Edit Attendance" }, { style: ({ width: '500px' }) }), { draggable: (false) })], __VLS_functionalComponentArgsRest(__VLS_133), false));
var __VLS_137 = __VLS_135.slots.default;
if (__VLS_ctx.editingAttendance) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-3 bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_138 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138(__assign({ label: (__VLS_ctx.getInitials((_a = __VLS_ctx.editingAttendance.employee) === null || _a === void 0 ? void 0 : _a.name)), size: "large", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials((_b = __VLS_ctx.editingAttendance.employee) === null || _b === void 0 ? void 0 : _b.name)), size: "large", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_139), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    ((_c = __VLS_ctx.editingAttendance.employee) === null || _c === void 0 ? void 0 : _c.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    ((_d = __VLS_ctx.editingAttendance.employee) === null || _d === void 0 ? void 0 : _d.department);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_143 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign({ modelValue: (__VLS_ctx.editingAttendance.attendanceDate), disabled: true }, { class: "w-full" })));
    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editingAttendance.attendanceDate), disabled: true }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_144), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_148 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148(__assign({ modelValue: (__VLS_ctx.editingAttendance.clockInTime), type: "time" }, { class: "w-full" })));
    var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editingAttendance.clockInTime), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_149), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_153 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153(__assign({ modelValue: (__VLS_ctx.editingAttendance.clockOutTime), type: "time" }, { class: "w-full" })));
    var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editingAttendance.clockOutTime), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_154), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_158 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_159 = __VLS_asFunctionalComponent1(__VLS_158, new __VLS_158(__assign({ modelValue: (__VLS_ctx.editingAttendance.breakMinutes), min: (0) }, { class: "w-full" })));
    var __VLS_160 = __VLS_159.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editingAttendance.breakMinutes), min: (0) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_159), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_163 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select | typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163(__assign({ modelValue: (__VLS_ctx.editingAttendance.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
    var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editingAttendance.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_164), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_168 = __VLS_166.slots.default;
    {
        var __VLS_169 = __VLS_166.slots.option;
        var slotProps = __VLS_vSlot(__VLS_169)[0];
        var __VLS_170 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170({
            value: (slotProps.option.label),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.option.value)),
        }));
        var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([{
                value: (slotProps.option.label),
                severity: (__VLS_ctx.getStatusSeverity(slotProps.option.value)),
            }], __VLS_functionalComponentArgsRest(__VLS_171), false));
        // @ts-ignore
        [getStatusSeverity, getInitials, showEditDialog, editingAttendance, editingAttendance, editingAttendance, editingAttendance, editingAttendance, editingAttendance, editingAttendance, editingAttendance, editingAttendance, statusOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_166;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_175 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    Textarea;
    // @ts-ignore
    var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175(__assign(__assign({ modelValue: (__VLS_ctx.editingAttendance.notes), rows: "2" }, { class: "w-full" }), { placeholder: "Optional notes..." })));
    var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.editingAttendance.notes), rows: "2" }, { class: "w-full" }), { placeholder: "Optional notes..." })], __VLS_functionalComponentArgsRest(__VLS_176), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
{
    var __VLS_180 = __VLS_135.slots.footer;
    var __VLS_181 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_182 = __VLS_asFunctionalComponent1(__VLS_181, new __VLS_181(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_183 = __VLS_182.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_182), false));
    var __VLS_186 = void 0;
    var __VLS_187 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showEditDialog = false;
                // @ts-ignore
                [showEditDialog, editingAttendance,];
            } });
    var __VLS_184;
    var __VLS_185;
    var __VLS_188 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_189 = __VLS_asFunctionalComponent1(__VLS_188, new __VLS_188(__assign({ 'onClick': {} }, { label: "Save Changes", severity: "info", loading: (__VLS_ctx.saving) })));
    var __VLS_190 = __VLS_189.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Changes", severity: "info", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_189), false));
    var __VLS_193 = void 0;
    var __VLS_194 = ({ click: {} },
        { onClick: (__VLS_ctx.saveAttendance) });
    var __VLS_191;
    var __VLS_192;
    // @ts-ignore
    [saving, saveAttendance,];
}
// @ts-ignore
[];
var __VLS_135;
var __VLS_195;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195(__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), modal: true, header: ("Attendance Details - ".concat(((_f = (_e = __VLS_ctx.selectedAttendance) === null || _e === void 0 ? void 0 : _e.employee) === null || _f === void 0 ? void 0 : _f.name) || '')) }, { style: ({ width: '600px' }) }), { draggable: (false) })));
var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), modal: true, header: ("Attendance Details - ".concat(((_h = (_g = __VLS_ctx.selectedAttendance) === null || _g === void 0 ? void 0 : _g.employee) === null || _h === void 0 ? void 0 : _h.name) || '')) }, { style: ({ width: '600px' }) }), { draggable: (false) })], __VLS_functionalComponentArgsRest(__VLS_196), false));
var __VLS_200 = __VLS_198.slots.default;
if (__VLS_ctx.selectedAttendance) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4 p-4 bg-blue-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_201 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_202 = __VLS_asFunctionalComponent1(__VLS_201, new __VLS_201(__assign({ label: (__VLS_ctx.getInitials((_j = __VLS_ctx.selectedAttendance.employee) === null || _j === void 0 ? void 0 : _j.name)), size: "xlarge", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_203 = __VLS_202.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials((_k = __VLS_ctx.selectedAttendance.employee) === null || _k === void 0 ? void 0 : _k.name)), size: "xlarge", shape: "circle" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_202), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    ((_l = __VLS_ctx.selectedAttendance.employee) === null || _l === void 0 ? void 0 : _l.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    ((_m = __VLS_ctx.selectedAttendance.employee) === null || _m === void 0 ? void 0 : _m.department);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    var __VLS_206 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_207 = __VLS_asFunctionalComponent1(__VLS_206, new __VLS_206({
        value: (__VLS_ctx.formatStatus(__VLS_ctx.selectedAttendance.status)),
        severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedAttendance.status)),
    }));
    var __VLS_208 = __VLS_207.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.formatStatus(__VLS_ctx.selectedAttendance.status)),
            severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedAttendance.status)),
        }], __VLS_functionalComponentArgsRest(__VLS_207), false));
    if (__VLS_ctx.selectedAttendance.isLate) {
        var __VLS_211 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_212 = __VLS_asFunctionalComponent1(__VLS_211, new __VLS_211({
            value: "Late",
            severity: "danger",
        }));
        var __VLS_213 = __VLS_212.apply(void 0, __spreadArray([{
                value: "Late",
                severity: "danger",
            }], __VLS_functionalComponentArgsRest(__VLS_212), false));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedAttendance.attendanceDate);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedAttendance.dayOfWeek);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-lg" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    (__VLS_ctx.selectedAttendance.clockIn || '--:--');
    if (__VLS_ctx.selectedAttendance.isLate) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-red-500 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.selectedAttendance.lateMinutes);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-lg" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    (__VLS_ctx.selectedAttendance.clockOut || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-lg" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    (__VLS_ctx.selectedAttendance.totalHours);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.selectedAttendance.breakMinutes);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-orange-500" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
    (__VLS_ctx.selectedAttendance.overtimeHours);
    if (__VLS_ctx.selectedAttendance.shift) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_216 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_217 = __VLS_asFunctionalComponent1(__VLS_216, new __VLS_216({
            value: (__VLS_ctx.selectedAttendance.shift.name),
            severity: "info",
        }));
        var __VLS_218 = __VLS_217.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.selectedAttendance.shift.name),
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_217), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.selectedAttendance.shift.startTime);
        (__VLS_ctx.selectedAttendance.shift.endTime);
    }
    if (__VLS_ctx.selectedAttendance.notes) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-yellow-50 p-3 rounded-lg border border-yellow-200" }));
        /** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-yellow-200']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-yellow-700 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.selectedAttendance.notes);
    }
}
{
    var __VLS_221 = __VLS_198.slots.footer;
    var __VLS_222 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_223 = __VLS_asFunctionalComponent1(__VLS_222, new __VLS_222(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })));
    var __VLS_224 = __VLS_223.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_223), false));
    var __VLS_227 = void 0;
    var __VLS_228 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsDialog = false;
                // @ts-ignore
                [getStatusSeverity, getInitials, formatStatus, showDetailsDialog, showDetailsDialog, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance,];
            } });
    var __VLS_225;
    var __VLS_226;
    var __VLS_229 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_230 = __VLS_asFunctionalComponent1(__VLS_229, new __VLS_229(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })));
    var __VLS_231 = __VLS_230.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_230), false));
    var __VLS_234 = void 0;
    var __VLS_235 = ({ click: {} },
        { onClick: (__VLS_ctx.editFromDetails) });
    var __VLS_232;
    var __VLS_233;
    // @ts-ignore
    [editFromDetails,];
}
// @ts-ignore
[];
var __VLS_198;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
