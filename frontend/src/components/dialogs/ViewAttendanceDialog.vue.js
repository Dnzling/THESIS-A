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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z, _0, _1, _2, _3, _4, _5, _6, _7, _8, _9, _10, _11, _12, _13, _14, _15, _16, _17, _18, _19;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../stores/auth");
var authStore = (0, auth_1.useAuthStore)();
var props = defineProps();
var emit = defineEmits();
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(false);
var selectedAttendance = (0, vue_1.ref)(null);
// Helper: Format datetime to time string (HH:MM AM/PM)
// Helper: Format datetime to time string (HH:MM AM/PM)
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
var formatTime = function (datetime) {
    if (!datetime)
        return 'N/A';
    return new Date(datetime).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};
var formatDateTime = function (datetime) {
    if (!datetime)
        return 'N/A';
    return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
};
var formatStatus = function (status) {
    var map = {
        'present': 'Present',
        'late': 'Late',
        'absent': 'Absent',
        'on_leave': 'On Leave',
        'half_day': 'Half Day',
        'holiday': 'Holiday',
        'rest_day': 'Rest Day'
    };
    return map[status] || status || 'Unknown';
};
var getStatusSeverity = function (status) {
    var map = {
        'present': 'success',
        'late': 'warning',
        'absent': 'danger',
        'on_leave': 'info',
        'half_day': 'warn',
        'holiday': 'secondary',
        'rest_day': 'contrast'
    };
    return map[status] || 'secondary';
};
// Helper: Convert minutes to hours and minutes string
var minutesToHours = function (minutes) {
    if (!minutes || minutes <= 0)
        return '0h 0m';
    var hours = Math.floor(minutes / 60);
    var mins = minutes % 60;
    return "".concat(hours, "h ").concat(mins, "m");
};
// Helper: Transform API response to UI format
var transformAttendance = function (data) {
    var _a, _b;
    var employee = data.employee || {};
    var shift = data.shift || {};
    var schedule = data.schedule || {};
    return {
        // Attendance Date
        attendance_date: formatDate(data.attendance_date),
        attendance_date_raw: data.attendance_date,
        // Employee basic info
        employee: __assign(__assign({}, employee), { full_name: "".concat(employee.fname || '', " ").concat(employee.lname || '').trim() || 'Unknown', department: employee.department || 'N/A', branch: 'Main Branch' // Add branch if available in API
         }),
        // Status badge
        status_badge: {
            label: data.status ? data.status.toUpperCase() : 'UNKNOWN',
            color: getStatusSeverity(data.status)
        },
        // Clock in details
        clock_in_details: {
            time: formatTime(data.clock_in),
            raw: data.clock_in,
            ip: data.clock_in_ip || 'N/A',
            location: data.clock_in_location || 'N/A',
            method: data.clock_in_method || 'N/A'
        },
        // Clock out details
        clock_out_details: {
            time: formatTime(data.clock_out),
            raw: data.clock_out,
            ip: data.clock_out_ip || 'N/A',
            location: data.clock_out_location || 'N/A',
            method: data.clock_out_method || 'N/A'
        },
        // Break details
        break_details: {
            break_start: formatTime(data.break_start),
            break_end: formatTime(data.break_end),
            break_hours: minutesToHours(data.break_minutes),
            break_minutes: data.break_minutes || 0
        },
        // Time summary
        time_summary: {
            total_worked_hours: minutesToHours(data.total_worked_minutes),
            total_worked_minutes: data.total_worked_minutes || 0,
            late_hours: minutesToHours(data.late_minutes),
            late_minutes: data.late_minutes || 0,
            early_departure_hours: minutesToHours(data.early_departure_minutes),
            early_departure_minutes: data.early_departure_minutes || 0,
            overtime_hours: minutesToHours(data.overtime_minutes),
            overtime_minutes: data.overtime_minutes || 0,
            night_differential_hours: minutesToHours(data.night_differential_minutes),
            night_differential_minutes: data.night_differential_minutes || 0
        },
        // Shift details
        shift_details: {
            id: shift.id,
            name: shift.name || 'N/A',
            code: shift.code || '',
            start_time: shift.start_time,
            end_time: shift.end_time,
            break_start: shift.break_start,
            break_end: shift.break_end,
            total_hours: shift.total_hours || '0',
            shift_type: shift.shift_type,
            week_days: shift.week_days || [],
            grace_period_minutes: shift.grace_period_minutes
        },
        // Schedule details
        schedule_details: {
            id: schedule.id,
            schedule_date: formatDate(schedule.schedule_date),
            generation_method: schedule.generation_method,
            status: schedule.status
        },
        // Approval info
        approval: {
            approved_by: data.approver ? {
                name: "".concat(data.approver.fname || '', " ").concat(data.approver.lname || '').trim(),
                role: ((_a = data.approver.role) === null || _a === void 0 ? void 0 : _a.name) || 'N/A'
            } : null,
            approved_at: data.approved_at ? formatDate(data.approved_at) : null
        },
        // Overtime request
        overtime_request: data.overtime_request ? {
            id: data.overtime_request.id,
            ot_start: formatTime(data.overtime_request.ot_start),
            ot_end: formatTime(data.overtime_request.ot_end),
            ot_minutes: data.overtime_request.ot_minutes,
            ot_type: data.overtime_request.ot_type,
            reason: data.overtime_request.reason,
            status: data.overtime_request.status
        } : null,
        // Employee details
        employee_details: {
            employee_number: employee.employee_number || 'N/A',
            email: ((_b = employee.user) === null || _b === void 0 ? void 0 : _b.email) || 'N/A',
            phone: employee.phone || 'N/A',
            hire_date: formatDate(employee.hire_date),
            employment_type: employee.employment_type || 'N/A',
            gender: employee.gender || 'N/A',
            date_of_birth: formatDate(employee.date_of_birth),
            address: employee.address || 'N/A'
        },
        // Notes
        notes: data.notes || null,
        // Timestamps
        timestamps: {
            created_at: formatDate(data.created_at),
            updated_at: formatDate(data.updated_at),
            raw_created_at: data.created_at,
            raw_updated_at: data.updated_at
        },
        // Raw data for editing
        raw: data,
        // Additional fields
        id: data.id,
        status: data.status,
        is_ot_approved: data.is_ot_approved,
        is_restday_work: data.is_restday_work
    };
};
// Fetch when ID changes or dialog opens
(0, vue_1.watch)([function () { return props.attendanceId; }, function () { return props.visible; }], function (_a) { return __awaiter(void 0, [_a], void 0, function (_b) {
    var newId = _b[0], newVisible = _b[1];
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!(newId && newVisible)) return [3 /*break*/, 2];
                return [4 /*yield*/, fetchAttendanceDetails(newId)];
            case 1:
                _c.sent();
                return [3 /*break*/, 3];
            case 2:
                if (!newVisible) {
                    selectedAttendance.value = null;
                }
                _c.label = 3;
            case 3: return [2 /*return*/];
        }
    });
}); });
var fetchAttendanceDetails = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                loading.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get("api/attendances/".concat(id), {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        },
                        params: { with_details: true }
                    })];
            case 2:
                response = _d.sent();
                if (response.data.success) {
                    // Transform the raw API response to UI format
                    selectedAttendance.value = transformAttendance(response.data.data);
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _d.sent();
                console.error('Fetch error:', ((_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) || err_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = err_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to fetch attendance details',
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
var closeDialog = function () {
    emit('update:visible', false);
};
var editFromDetails = function () {
    var _a;
    // Pass the raw data for editing
    if ((_a = selectedAttendance.value) === null || _a === void 0 ? void 0 : _a.raw) {
        emit('edit', selectedAttendance.value.raw);
    }
    else {
        emit('edit', selectedAttendance.value);
    }
    closeDialog();
};
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var onHide = function () {
    // Optional cleanup
};
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign(__assign(__assign(__assign({ 'onUpdate:visible': {} }, { 'onHide': {} }), { visible: (__VLS_ctx.visible), modal: true, header: ("Attendance Details - ".concat(((_b = (_a = __VLS_ctx.selectedAttendance) === null || _a === void 0 ? void 0 : _a.employee) === null || _b === void 0 ? void 0 : _b.full_name) || '')) }), { style: ({ width: '700px' }) }), { draggable: (false) }), { class: "attendance-details-dialog" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign(__assign({ 'onUpdate:visible': {} }, { 'onHide': {} }), { visible: (__VLS_ctx.visible), modal: true, header: ("Attendance Details - ".concat(((_d = (_c = __VLS_ctx.selectedAttendance) === null || _c === void 0 ? void 0 : _c.employee) === null || _d === void 0 ? void 0 : _d.full_name) || '')) }), { style: ({ width: '700px' }) }), { draggable: (false) }), { class: "attendance-details-dialog" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ 'update:visible': {} },
    { 'onUpdate:visible': function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.emit('update:visible', $event);
            // @ts-ignore
            [visible, selectedAttendance, emit,];
        } });
var __VLS_7 = ({ hide: {} },
    { onHide: (__VLS_ctx.onHide) });
var __VLS_8 = {};
/** @type {__VLS_StyleScopedClasses['attendance-details-dialog']} */ ;
var __VLS_9 = __VLS_3.slots.default;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col items-center justify-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-spin pi-spinner text-4xl text-primary-500 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spin']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spinner']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-primary-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
}
else if (__VLS_ctx.selectedAttendance) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ label: (__VLS_ctx.getInitials(((_e = __VLS_ctx.selectedAttendance.employee) === null || _e === void 0 ? void 0 : _e.full_name) || '')), size: "large", shape: "circle" }, { class: "bg-gray-100 text-gray-700 font-medium" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(((_f = __VLS_ctx.selectedAttendance.employee) === null || _f === void 0 ? void 0 : _f.full_name) || '')), size: "large", shape: "circle" }, { class: "bg-gray-100 text-gray-700 font-medium" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (((_g = __VLS_ctx.selectedAttendance.employee) === null || _g === void 0 ? void 0 : _g.full_name) || 'Unknown');
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (((_h = __VLS_ctx.selectedAttendance.employee) === null || _h === void 0 ? void 0 : _h.department) || 'N/A');
    (((_j = __VLS_ctx.selectedAttendance.employee) === null || _j === void 0 ? void 0 : _j.branch) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
    /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_15 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15({
        value: (((_k = __VLS_ctx.selectedAttendance.status_badge) === null || _k === void 0 ? void 0 : _k.label) || 'UNKNOWN'),
        severity: (((_l = __VLS_ctx.selectedAttendance.status_badge) === null || _l === void 0 ? void 0 : _l.color) || 'secondary'),
    }));
    var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([{
            value: (((_m = __VLS_ctx.selectedAttendance.status_badge) === null || _m === void 0 ? void 0 : _m.label) || 'UNKNOWN'),
            severity: (((_o = __VLS_ctx.selectedAttendance.status_badge) === null || _o === void 0 ? void 0 : _o.color) || 'secondary'),
        }], __VLS_functionalComponentArgsRest(__VLS_16), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.selectedAttendance.attendance_date);
    if (((_p = __VLS_ctx.selectedAttendance.time_summary) === null || _p === void 0 ? void 0 : _p.late_minutes) > 0) {
        var __VLS_20 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Badge} */
        Badge;
        // @ts-ignore
        var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20({
            value: ("Late: ".concat((_q = __VLS_ctx.selectedAttendance.time_summary) === null || _q === void 0 ? void 0 : _q.late_minutes, "m")),
            severity: "danger",
        }));
        var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([{
                value: ("Late: ".concat((_r = __VLS_ctx.selectedAttendance.time_summary) === null || _r === void 0 ? void 0 : _r.late_minutes, "m")),
                severity: "danger",
            }], __VLS_functionalComponentArgsRest(__VLS_21), false));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_s = __VLS_ctx.selectedAttendance.shift_details) === null || _s === void 0 ? void 0 : _s.name) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (((_t = __VLS_ctx.selectedAttendance.shift_details) === null || _t === void 0 ? void 0 : _t.code) || '');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_u = __VLS_ctx.selectedAttendance.shift_details) === null || _u === void 0 ? void 0 : _u.start_time) || '--:--');
    (((_v = __VLS_ctx.selectedAttendance.shift_details) === null || _v === void 0 ? void 0 : _v.end_time) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_w = __VLS_ctx.selectedAttendance.shift_details) === null || _w === void 0 ? void 0 : _w.break_start) || '--:--');
    (((_x = __VLS_ctx.selectedAttendance.shift_details) === null || _x === void 0 ? void 0 : _x.break_end) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_y = __VLS_ctx.selectedAttendance.shift_details) === null || _y === void 0 ? void 0 : _y.total_hours) || '0');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-medium text-gray-500 mb-3 flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_z = __VLS_ctx.selectedAttendance.clock_in_details) === null || _z === void 0 ? void 0 : _z.time) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm" }));
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (((_0 = __VLS_ctx.selectedAttendance.clock_in_details) === null || _0 === void 0 ? void 0 : _0.ip) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (((_1 = __VLS_ctx.selectedAttendance.clock_in_details) === null || _1 === void 0 ? void 0 : _1.location) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-medium text-gray-500 mb-3 flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_2 = __VLS_ctx.selectedAttendance.clock_out_details) === null || _2 === void 0 ? void 0 : _2.time) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm" }));
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (((_3 = __VLS_ctx.selectedAttendance.clock_out_details) === null || _3 === void 0 ? void 0 : _3.ip) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (((_4 = __VLS_ctx.selectedAttendance.clock_out_details) === null || _4 === void 0 ? void 0 : _4.location) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-medium text-gray-500 mb-3 flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-stopwatch text-primary-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-stopwatch']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-primary-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_5 = __VLS_ctx.selectedAttendance.break_details) === null || _5 === void 0 ? void 0 : _5.break_start) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_6 = __VLS_ctx.selectedAttendance.break_details) === null || _6 === void 0 ? void 0 : _6.break_end) || '--:--');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (((_7 = __VLS_ctx.selectedAttendance.break_details) === null || _7 === void 0 ? void 0 : _7.break_hours) || '0h 0m');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 sm:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['sm:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 rounded-lg p-3 text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-blue-700" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
    (((_8 = __VLS_ctx.selectedAttendance.time_summary) === null || _8 === void 0 ? void 0 : _8.total_worked_hours) || '0h 0m');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-yellow-50 rounded-lg p-3 text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-yellow-700" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-yellow-700']} */ ;
    (((_9 = __VLS_ctx.selectedAttendance.time_summary) === null || _9 === void 0 ? void 0 : _9.late_hours) || '0h 0m');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-50 rounded-lg p-3 text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-orange-700" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-700']} */ ;
    (((_10 = __VLS_ctx.selectedAttendance.time_summary) === null || _10 === void 0 ? void 0 : _10.early_departure_hours) ||
        '0h 0m');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 rounded-lg p-3 text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-green-700" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
    (((_11 = __VLS_ctx.selectedAttendance.time_summary) === null || _11 === void 0 ? void 0 : _11.overtime_hours) || '0h 0m');
    if ((_12 = __VLS_ctx.selectedAttendance.approval) === null || _12 === void 0 ? void 0 : _12.approved_by) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border rounded-lg p-4 bg-green-50" }));
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-medium text-gray-600 mb-3 flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        ((_14 = (_13 = __VLS_ctx.selectedAttendance.approval) === null || _13 === void 0 ? void 0 : _13.approved_by) === null || _14 === void 0 ? void 0 : _14.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        ((_16 = (_15 = __VLS_ctx.selectedAttendance.approval) === null || _15 === void 0 ? void 0 : _15.approved_by) === null || _16 === void 0 ? void 0 : _16.role);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        ((_17 = __VLS_ctx.selectedAttendance.approval) === null || _17 === void 0 ? void 0 : _17.approved_at);
    }
    if (__VLS_ctx.selectedAttendance.notes) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border rounded-lg p-4" }));
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "text-sm font-medium text-gray-500 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        (__VLS_ctx.selectedAttendance.notes);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 flex justify-between border-t pt-4" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (((_18 = __VLS_ctx.selectedAttendance.timestamps) === null || _18 === void 0 ? void 0 : _18.created_at) || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (((_19 = __VLS_ctx.selectedAttendance.timestamps) === null || _19 === void 0 ? void 0 : _19.updated_at) || 'N/A');
}
{
    var __VLS_25 = __VLS_3.slots.footer;
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", severity: "secondary" })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    var __VLS_31 = void 0;
    var __VLS_32 = ({ click: {} },
        { onClick: (__VLS_ctx.closeDialog) });
    var __VLS_29;
    var __VLS_30;
    if (__VLS_ctx.selectedAttendance && !__VLS_ctx.loading) {
        var __VLS_33 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ 'onClick': {} }, { label: "Edit Attendance", icon: "pi pi-pencil", severity: "contrast" })));
        var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit Attendance", icon: "pi pi-pencil", severity: "contrast" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
        var __VLS_38 = void 0;
        var __VLS_39 = ({ click: {} },
            { onClick: (__VLS_ctx.editFromDetails) });
        var __VLS_36;
        var __VLS_37;
    }
    // @ts-ignore
    [selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, selectedAttendance, onHide, loading, loading, getInitials, closeDialog, editFromDetails,];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_4;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
