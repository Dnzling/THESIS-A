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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u, _v;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
var usetoast_1 = require("primevue/usetoast");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
// --- General State ---
var activeTab = (0, vue_1.ref)('coverage');
var selectedDate = (0, vue_1.ref)(new Date());
var loading = (0, vue_1.ref)(true);
var assignmentDialog = (0, vue_1.ref)(false);
var error = (0, vue_1.ref)('');
var shiftsData = (0, vue_1.ref)([]);
var authStore = (0, auth_1.useAuthStore)();
// --- Modal States ---
var errorDialogVisible = (0, vue_1.ref)(false);
var errorMessage = (0, vue_1.ref)('');
var confirmDialogVisible = (0, vue_1.ref)(false);
var confirmTitle = (0, vue_1.ref)('');
var confirmMessage = (0, vue_1.ref)('');
var confirmButtonLabel = (0, vue_1.ref)('Confirm');
var confirmButtonSeverity = (0, vue_1.ref)('danger');
var actionLoading = (0, vue_1.ref)(false);
var formLoading = (0, vue_1.ref)(false);
var pendingAction = (0, vue_1.ref)(null);
// --- Shifts State ---
var shiftsLoading = (0, vue_1.ref)(false);
var shiftFilters = (0, vue_1.ref)({
    search: '',
    department: null,
    shiftType: null,
    date: null
});
var departmentOptions = (0, vue_1.ref)([]);
var shiftTypeOptions = (0, vue_1.ref)([]);
var shiftOptions = (0, vue_1.ref)([]);
// --- Assignment State ---
var assignments = (0, vue_1.ref)([]);
var assignmentsLoading = (0, vue_1.ref)(false);
var assignmentFilters = (0, vue_1.ref)({ search: '', type: null });
var assignmentTypeOptions = (0, vue_1.ref)([
    { label: 'Permanent', value: 'permanent' },
    { label: 'Temporary', value: 'temporary' },
    { label: 'Cover', value: 'cover' }
]);
var assignmentDialogVisible = (0, vue_1.ref)(false);
var assignmentForm = (0, vue_1.ref)({
    employee_id: null,
    shift_id: null,
    start_date: null,
    end_date: null,
    assignment_type: null,
    notes: ''
});
var employeeOptions = (0, vue_1.ref)([]);
var myShiftOptions = (0, vue_1.ref)([]);
var receiverShiftOptions = (0, vue_1.ref)([]);
// --- Swap State ---
var swapRequests = (0, vue_1.ref)([]);
var swapLoading = (0, vue_1.ref)(false);
var swapFilters = (0, vue_1.ref)({ search: '', status: null });
var swapStatusOptions = (0, vue_1.ref)([
    { label: 'Pending', value: 'pending' },
    { label: 'Accepted', value: 'accepted' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Cancelled', value: 'cancelled' }
]);
var swapDialogVisible = (0, vue_1.ref)(false);
var swapForm = (0, vue_1.ref)({
    receiver_id: null,
    requestor_schedule_id: null,
    receiver_schedule_id: null,
    swap_type: null,
    reason: ''
});
var swapTypeOptions = (0, vue_1.ref)([
    { label: 'Full Swap (Same Date)', value: 'full_swap' },
    { label: 'Give Away (Transfer shift)', value: 'give_away' },
    { label: 'Pick Up (Take extra shift)', value: 'pick_up' }
]);
// --- Dashboard Stats ---
var dashboardStats = (0, vue_1.ref)({
    totalEmployees: 0,
    activeEmployees: 0,
    todayShifts: 0,
    onLeave: 0,
    pendingSwaps: 0,
    nightDiffEmployees: 0,
    unfilledShifts: 0
});
var departments = (0, vue_1.ref)([]);
// --- Shift Definitions State ---
var shiftDefinitions = (0, vue_1.ref)([]);
var shiftDefsLoading = (0, vue_1.ref)(false);
var shiftDefFilters = (0, vue_1.ref)({ search: '', type: null });
var editShiftDialogVisible = (0, vue_1.ref)(false);
var deleteShiftDialogVisible = (0, vue_1.ref)(false);
var selectedShiftForDelete = (0, vue_1.ref)(null);
var editShiftSaving = (0, vue_1.ref)(false);
var deletingShift = (0, vue_1.ref)(false);
var editShiftErrors = (0, vue_1.ref)({});
var editShiftForm = (0, vue_1.ref)({
    id: null,
    name: '',
    code: '',
    shift_type: 'fixed',
    start_time: '09:00',
    end_time: '18:00',
    break_start: '',
    break_end: '',
    total_hours: '8',
    week_days: [],
    grace_period_minutes: 15,
    has_night_diff: false,
    night_diff_rate: 1.10,
    min_employees_required: 1,
    color: '#3b82f6',
    description: '',
    is_active: true
});
var weekDayOptions = [
    { label: 'M', full: 'Monday', value: 'monday' },
    { label: 'T', full: 'Tuesday', value: 'tuesday' },
    { label: 'W', full: 'Wednesday', value: 'wednesday' },
    { label: 'T', full: 'Thursday', value: 'thursday' },
    { label: 'F', full: 'Friday', value: 'friday' },
    { label: 'S', full: 'Saturday', value: 'saturday' },
    { label: 'S', full: 'Sunday', value: 'sunday' }
];
// --- Computed: Filtered Shift Definitions ---
var filteredShiftDefinitions = (0, vue_1.computed)(function () {
    var filtered = shiftDefinitions.value;
    if (shiftDefFilters.value.search) {
        var s_1 = shiftDefFilters.value.search.toLowerCase();
        filtered = filtered.filter(function (sh) { var _a, _b; return ((_a = sh.name) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(s_1)) || ((_b = sh.code) === null || _b === void 0 ? void 0 : _b.toLowerCase().includes(s_1)); });
    }
    if (shiftDefFilters.value.type) {
        filtered = filtered.filter(function (sh) { return sh.shift_type === shiftDefFilters.value.type; });
    }
    return filtered;
});
// --- Watchers ---
(0, vue_1.watch)(activeTab, function (newTab) {
    if (newTab === 'assignments' && assignments.value.length === 0) {
        fetchAssignments();
    }
    if (newTab === 'shiftswap' && swapRequests.value.length === 0) {
        fetchSwapRequests();
    }
    if (newTab === 'coverage') {
        fetchCoverageData();
    }
    if (newTab === 'shifts' && shiftDefinitions.value.length === 0) {
        fetchShiftDefinitions();
    }
});
// Watch receiver selection to load their schedules
(0, vue_1.watch)(function () { return swapForm.value.receiver_id; }, function (receiverId) { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                receiverShiftOptions.value = [];
                if (!receiverId) return [3 /*break*/, 2];
                return [4 /*yield*/, fetchReceiverScheduleOptions(receiverId)];
            case 1:
                _a.sent();
                _a.label = 2;
            case 2: return [2 /*return*/];
        }
    });
}); });
// --- Helper Functions ---
var formatDateForAPI = function (date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    return "".concat(year, "-").concat(month, "-").concat(day);
};
var formatDate = function (date) {
    if (!date)
        return '';
    try {
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    catch (_a) {
        return date;
    }
};
var formatTime = function (time) { return time || '--:--'; };
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getShiftSeverity = function (type) {
    var map = {
        'Morning': 'info', 'Mid': 'warning', 'Evening': 'help', 'Night': 'secondary',
        'Morning Shift': 'info', 'Afternoon Shift': 'warning', 'Evening Shift': 'help', 'Night Shift': 'secondary'
    };
    return map[type] || 'info';
};
var getShiftSeverityFromColor = function (color) {
    if (!color)
        return 'info';
    if (color.includes('3b82f6') || color.includes('1e40af'))
        return 'info';
    if (color.includes('f59e0b') || color.includes('b45309'))
        return 'warning';
    if (color.includes('7c3aed') || color.includes('6d28d9'))
        return 'help';
    if (color.includes('10b981'))
        return 'success';
    return 'info';
};
var getStatusSeverity = function (status) {
    var map = {
        'scheduled': 'info', 'completed': 'success', 'cancelled': 'danger', 'absent': 'danger', 'pending': 'warning'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'secondary';
};
var getSwapStatusSeverity = function (status) {
    var map = {
        'pending': 'warning', 'accepted': 'success', 'rejected': 'danger', 'cancelled': 'secondary'
    };
    return map[status] || 'secondary';
};
var isToday = (0, vue_1.computed)(function () { return new Date().toDateString() === selectedDate.value.toDateString(); });
// --- Data Transformers ---
var transformShiftData = function (records) {
    return records.map(function (item) {
        var _a;
        var employee = item.employee || {};
        var shift = item.shift || {};
        return __assign(__assign({}, item), { employee: __assign(__assign({}, employee), { full_name: employee.fname && employee.lname ? "".concat(employee.fname, " ").concat(employee.lname).trim() : 'Unknown' }), shift: __assign({}, shift), schedule_time: "".concat(formatTime(shift.start_time), " - ").concat(formatTime(shift.end_time)), status_badge: { label: ((_a = item.status) === null || _a === void 0 ? void 0 : _a.toUpperCase()) || 'UNKNOWN', color: getStatusSeverity(item.status) } });
    });
};
// --- Computed Properties ---
var filteredShifts = (0, vue_1.computed)(function () {
    var filtered = shiftsData.value;
    if (shiftFilters.value.search) {
        var search_1 = shiftFilters.value.search.toLowerCase();
        filtered = filtered.filter(function (s) {
            var _a;
            return s.employee.full_name.toLowerCase().includes(search_1) ||
                ((_a = s.shift.name) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(search_1));
        });
    }
    if (shiftFilters.value.department)
        filtered = filtered.filter(function (s) { return s.employee.department === shiftFilters.value.department; });
    if (shiftFilters.value.shiftType)
        filtered = filtered.filter(function (s) { return s.shift.name === shiftFilters.value.shiftType; });
    if (shiftFilters.value.date) {
        var filterDate_1 = formatDateForAPI(shiftFilters.value.date);
        filtered = filtered.filter(function (s) { return s.schedule_date.startsWith(filterDate_1); });
    }
    return filtered;
});
var isMyRequest = function (data) {
    var _a, _b;
    var currentUserId = (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.id;
    return ((_b = data.requestor) === null || _b === void 0 ? void 0 : _b.user_id) === currentUserId || data.requestor_id === currentUserId;
};
// --- Error Handling ---
var showError = function (message) {
    errorMessage.value = message;
    errorDialogVisible.value = true;
};
// --- Confirmation Dialog ---
var showConfirmation = function (title, message, buttonLabel, buttonSeverity) {
    if (buttonSeverity === void 0) { buttonSeverity = 'danger'; }
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmButtonLabel.value = buttonLabel;
    confirmButtonSeverity.value = buttonSeverity;
    confirmDialogVisible.value = true;
};
var confirmDeleteAssignment = function (data) {
    var _a;
    pendingAction.value = { type: 'assignment', data: data };
    showConfirmation('Delete Assignment', "Are you sure you want to delete the assignment for ".concat((_a = data.employee) === null || _a === void 0 ? void 0 : _a.full_name, "?"), 'Delete', 'danger');
};
var confirmSwapAction = function (data, action) {
    var _a, _b;
    pendingAction.value = { type: 'swap', data: data, action: action };
    var actionMessages = {
        accept: {
            title: 'Accept Swap Request',
            message: "Are you sure you want to accept the shift swap request from ".concat((_a = data.requestor) === null || _a === void 0 ? void 0 : _a.full_name, "?"),
            button: 'Accept'
        },
        reject: {
            title: 'Reject Swap Request',
            message: "Are you sure you want to reject the shift swap request from ".concat((_b = data.requestor) === null || _b === void 0 ? void 0 : _b.full_name, "?"),
            button: 'Reject'
        },
        cancel: {
            title: 'Cancel Swap Request',
            message: 'Are you sure you want to cancel this shift swap request?',
            button: 'Cancel'
        }
    };
    var config = actionMessages[action];
    showConfirmation(config.title, config.message, config.button, action === 'accept' ? 'success' : 'danger');
};
var executeConfirmedAction = function () {
    var _a, _b;
    confirmDialogVisible.value = false;
    if (((_a = pendingAction.value) === null || _a === void 0 ? void 0 : _a.type) === 'assignment') {
        deleteAssignment(pendingAction.value.data);
    }
    else if (((_b = pendingAction.value) === null || _b === void 0 ? void 0 : _b.type) === 'swap') {
        executeSwapAction(pendingAction.value.action, pendingAction.value.data);
    }
};
// --- API Calls: Shifts ---
var fetchData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, records, err_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                error.value = '';
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('api/shift-schedules', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    records = response.data.data.data || response.data.data || [];
                    shiftsData.value = transformShiftData(records);
                    processDashboardStats();
                    processDepartmentsData();
                    extractFilterOptions();
                }
                else {
                    error.value = 'Failed to load data';
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _c.sent();
                console.error(err_1);
                error.value = ((_b = (_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Error connecting to server';
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var processDashboardStats = function () {
    var today = new Date().toISOString().split('T')[0];
    var todayShifts = shiftsData.value.filter(function (s) { var _a; return (_a = s.schedule_date) === null || _a === void 0 ? void 0 : _a.startsWith(today); });
    var uniqueEmployees = __spreadArray([], new Set(shiftsData.value.map(function (s) { return s.employee.id; })), true);
    var nightShifts = shiftsData.value.filter(function (s) { var _a; return (_a = s.shift.name) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes('night'); });
    dashboardStats.value = {
        totalEmployees: uniqueEmployees.length,
        activeEmployees: uniqueEmployees.length,
        todayShifts: todayShifts.length,
        onLeave: 0,
        pendingSwaps: dashboardStats.value.pendingSwaps,
        nightDiffEmployees: nightShifts.length,
        unfilledShifts: 0
    };
};
var processDepartmentsData = function () {
    var deptMap = new Map();
    shiftsData.value.forEach(function (shift) {
        var dept = shift.employee.department || 'Unassigned';
        if (!deptMap.has(dept)) {
            deptMap.set(dept, { id: dept, name: dept, totalEmployees: 0, scheduled: 0, scheduledEmployees: [], unfilledCount: 0, coveragePercentage: 0 });
        }
        var d = deptMap.get(dept);
        if (shift.status === 'scheduled') {
            d.scheduled++;
            d.scheduledEmployees.push({ id: shift.employee.id, name: shift.employee.full_name, shiftType: shift.shift.name });
        }
    });
    deptMap.forEach(function (d) { return d.coveragePercentage = d.totalEmployees > 0 ? (d.scheduled / d.totalEmployees) * 100 : 0; });
    departments.value = Array.from(deptMap.values());
};
var extractFilterOptions = function () {
    var depts = __spreadArray([], new Set(shiftsData.value.map(function (s) { return s.employee.department; }).filter(Boolean)), true);
    departmentOptions.value = depts.map(function (d) { return ({ label: d, value: d }); });
    var shifts = __spreadArray([], new Set(shiftsData.value.map(function (s) { return s.shift.name; }).filter(Boolean)), true);
    shiftTypeOptions.value = shifts.map(function (s) { return ({ label: s, value: s }); });
    shiftOptions.value = shifts.map(function (s, index) { return ({ label: s, value: index + 1 }); });
};
var fetchCoverageData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var dateStr, filtered, deptMap;
    return __generator(this, function (_a) {
        dateStr = formatDateForAPI(selectedDate.value);
        filtered = shiftsData.value.filter(function (s) { var _a; return (_a = s.schedule_date) === null || _a === void 0 ? void 0 : _a.startsWith(dateStr); });
        deptMap = new Map();
        filtered.forEach(function (shift) {
            var dept = shift.employee.department || 'Unassigned';
            if (!deptMap.has(dept)) {
                deptMap.set(dept, { id: dept, name: dept, totalEmployees: 0, scheduled: 0, scheduledEmployees: [], unfilledCount: 0, coveragePercentage: 0 });
            }
            var d = deptMap.get(dept);
            if (shift.status === 'scheduled') {
                d.scheduled++;
                d.scheduledEmployees.push({ id: shift.employee.id, name: shift.employee.full_name, shiftType: shift.shift.name });
            }
        });
        deptMap.forEach(function (d) { return d.coveragePercentage = d.totalEmployees > 0 ? (d.scheduled / d.totalEmployees) * 100 : 0; });
        departments.value = Array.from(deptMap.values());
        return [2 /*return*/];
    });
}); };
var filterShifts = function () {
    // Filtering is handled by computed property
};
// --- API Calls: Assignments ---
var fetchAssignments = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, err_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                assignmentsLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                params = {};
                if (assignmentFilters.value.type)
                    params.assignment_type = assignmentFilters.value.type;
                if (assignmentFilters.value.search)
                    params.fname = assignmentFilters.value.search;
                return [4 /*yield*/, axios_1.default.get('api/shift-assignments', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: params
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    assignments.value = response.data.data.data || response.data.data;
                }
                return [3 /*break*/, 5];
            case 3:
                err_2 = _c.sent();
                showError(((_b = (_a = err_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load assignments');
                return [3 /*break*/, 5];
            case 4:
                assignmentsLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var createAssignment = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, err_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!assignmentForm.value.employee_id || !assignmentForm.value.shift_id || !assignmentForm.value.start_date || !assignmentForm.value.assignment_type) {
                    showError('Please fill in all required fields');
                    return [2 /*return*/];
                }
                formLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                payload = {
                    employee_id: assignmentForm.value.employee_id,
                    shift_id: assignmentForm.value.shift_id,
                    start_date: formatDateForAPI(assignmentForm.value.start_date),
                    end_date: assignmentForm.value.end_date ? formatDateForAPI(assignmentForm.value.end_date) : null,
                    assignment_type: assignmentForm.value.assignment_type,
                    notes: assignmentForm.value.notes
                };
                return [4 /*yield*/, axios_1.default.post('api/shift-assignments', payload, {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    toast.add({ severity: 'success', summary: 'Success', detail: 'Assignment created successfully', life: 3000 });
                    assignmentDialogVisible.value = false;
                    resetAssignmentForm();
                    fetchAssignments();
                }
                return [3 /*break*/, 5];
            case 3:
                err_3 = _c.sent();
                showError(((_b = (_a = err_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to create assignment');
                return [3 /*break*/, 5];
            case 4:
                formLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var deleteAssignment = function (assignment) { return __awaiter(void 0, void 0, void 0, function () {
    var err_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                actionLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.delete("api/shift-assignments/".concat(assignment.id), {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Assignment removed successfully', life: 3000 });
                fetchAssignments();
                return [3 /*break*/, 5];
            case 3:
                err_4 = _c.sent();
                showError(((_b = (_a = err_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete assignment');
                return [3 /*break*/, 5];
            case 4:
                actionLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var resetAssignmentForm = function () {
    assignmentForm.value = {
        employee_id: null,
        shift_id: null,
        start_date: null,
        end_date: null,
        assignment_type: null,
        notes: ''
    };
};
var openAssignmentDialog = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                assignmentDialog.value = true;
                resetAssignmentForm();
                return [4 /*yield*/, fetchEmployeeOptions()];
            case 1:
                _a.sent();
                return [4 /*yield*/, fetchShiftOptions()];
            case 2:
                _a.sent();
                assignmentDialogVisible.value = true;
                assignmentDialog.value = false;
                return [2 /*return*/];
        }
    });
}); };
// --- API Calls: Swap Requests ---
var fetchSwapRequests = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, err_5;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                swapLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                params = {};
                if (swapFilters.value.status)
                    params.status = swapFilters.value.status;
                return [4 /*yield*/, axios_1.default.get('api/shift-swap-requests', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: params
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    swapRequests.value = response.data.data.data || response.data.data;
                    dashboardStats.value.pendingSwaps = swapRequests.value.filter(function (r) { return r.status === 'pending'; }).length;
                }
                return [3 /*break*/, 5];
            case 3:
                err_5 = _c.sent();
                showError(((_b = (_a = err_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load swap requests');
                return [3 /*break*/, 5];
            case 4:
                swapLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var executeSwapAction = function (action, data) { return __awaiter(void 0, void 0, void 0, function () {
    var headers, err_6;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                actionLoading.value = true;
                headers = { 'Authorization': "Bearer ".concat(authStore.token) };
                _c.label = 1;
            case 1:
                _c.trys.push([1, 8, 9, 10]);
                if (!(action === 'accept')) return [3 /*break*/, 3];
                return [4 /*yield*/, axios_1.default.put("api/shift-swap-requests/".concat(data.id, "/accept"), {}, { headers: headers })];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Accepted', detail: 'Swap request accepted successfully', life: 3000 });
                return [3 /*break*/, 7];
            case 3:
                if (!(action === 'reject')) return [3 /*break*/, 5];
                return [4 /*yield*/, axios_1.default.put("api/shift-swap-requests/".concat(data.id, "/reject"), {}, { headers: headers })];
            case 4:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Rejected', detail: 'Swap request rejected', life: 3000 });
                return [3 /*break*/, 7];
            case 5:
                if (!(action === 'cancel')) return [3 /*break*/, 7];
                return [4 /*yield*/, axios_1.default.put("api/shift-swap-requests/".concat(data.id, "/cancel"), {}, { headers: headers })];
            case 6:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Swap request cancelled', life: 3000 });
                _c.label = 7;
            case 7:
                fetchSwapRequests();
                return [3 /*break*/, 10];
            case 8:
                err_6 = _c.sent();
                showError(((_b = (_a = err_6.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || "Failed to ".concat(action, " swap request"));
                return [3 /*break*/, 10];
            case 9:
                actionLoading.value = false;
                return [7 /*endfinally*/];
            case 10: return [2 /*return*/];
        }
    });
}); };
var createSwapRequest = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, err_7;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!swapForm.value.receiver_id || !swapForm.value.requestor_schedule_id || !swapForm.value.receiver_schedule_id || !swapForm.value.swap_type) {
                    showError('Please fill in all required fields');
                    return [2 /*return*/];
                }
                formLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                payload = {
                    receiver_id: swapForm.value.receiver_id,
                    requestor_schedule_id: swapForm.value.requestor_schedule_id,
                    receiver_schedule_id: swapForm.value.receiver_schedule_id,
                    swap_type: swapForm.value.swap_type,
                    reason: swapForm.value.reason
                };
                return [4 /*yield*/, axios_1.default.post('api/shift-swap-requests', payload, {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    toast.add({ severity: 'success', summary: 'Success', detail: 'Swap request submitted successfully', life: 3000 });
                    swapDialogVisible.value = false;
                    resetSwapForm();
                    fetchSwapRequests();
                }
                return [3 /*break*/, 5];
            case 3:
                err_7 = _c.sent();
                showError(((_b = (_a = err_7.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to create swap request');
                return [3 /*break*/, 5];
            case 4:
                formLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var resetSwapForm = function () {
    swapForm.value = {
        receiver_id: null,
        requestor_schedule_id: null,
        receiver_schedule_id: null,
        swap_type: null,
        reason: ''
    };
    myShiftOptions.value = [];
    receiverShiftOptions.value = [];
};
var openSwapDialog = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                resetSwapForm();
                return [4 /*yield*/, fetchEmployeeOptions()];
            case 1:
                _a.sent();
                return [4 /*yield*/, fetchMyScheduleOptions()];
            case 2:
                _a.sent();
                swapDialogVisible.value = true;
                return [2 /*return*/];
        }
    });
}); };
// --- Shift Definitions API Calls ---
var fetchShiftDefinitions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_8;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                shiftDefsLoading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('api/shifts', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    shiftDefinitions.value = response.data.data.data || response.data.data || [];
                    shiftOptions.value = shiftDefinitions.value.map(function (s) { return ({
                        label: s.name,
                        value: s.id
                    }); });
                }
                return [3 /*break*/, 5];
            case 3:
                err_8 = _c.sent();
                showError(((_b = (_a = err_8.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load shifts');
                return [3 /*break*/, 5];
            case 4:
                shiftDefsLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var openEditShiftDialog = function (shift) {
    var _a;
    editShiftErrors.value = {};
    editShiftForm.value = {
        id: shift.id,
        name: shift.name || '',
        code: shift.code || '',
        shift_type: shift.shift_type || 'fixed',
        start_time: shift.start_time ? String(shift.start_time).substring(0, 5) : '09:00',
        end_time: shift.end_time ? String(shift.end_time).substring(0, 5) : '18:00',
        break_start: shift.break_start ? String(shift.break_start).substring(0, 5) : '',
        break_end: shift.break_end ? String(shift.break_end).substring(0, 5) : '',
        total_hours: String(shift.total_hours || '8'),
        week_days: shift.week_days || [],
        grace_period_minutes: (_a = shift.grace_period_minutes) !== null && _a !== void 0 ? _a : 15,
        has_night_diff: shift.has_night_diff || false,
        night_diff_rate: shift.night_diff_rate || 1.10,
        min_employees_required: shift.min_employees_required || 1,
        color: shift.color || '#3b82f6',
        description: shift.description || '',
        is_active: shift.is_active !== undefined ? shift.is_active : true
    };
    editShiftDialogVisible.value = true;
};
var updateShift = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, err_9;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!editShiftForm.value.id)
                    return [2 /*return*/];
                editShiftErrors.value = {};
                editShiftSaving.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                payload = {
                    name: editShiftForm.value.name,
                    code: editShiftForm.value.code,
                    shift_type: editShiftForm.value.shift_type,
                    start_time: editShiftForm.value.start_time,
                    end_time: editShiftForm.value.end_time,
                    total_hours: Number(editShiftForm.value.total_hours),
                    week_days: editShiftForm.value.week_days.length > 0 ? editShiftForm.value.week_days : null,
                    grace_period_minutes: editShiftForm.value.grace_period_minutes,
                    has_night_diff: editShiftForm.value.has_night_diff,
                    night_diff_rate: editShiftForm.value.has_night_diff ? editShiftForm.value.night_diff_rate : 1.10,
                    min_employees_required: editShiftForm.value.min_employees_required,
                    color: editShiftForm.value.color,
                    description: editShiftForm.value.description || null,
                    is_active: editShiftForm.value.is_active
                };
                if (editShiftForm.value.break_start)
                    payload.break_start = editShiftForm.value.break_start;
                if (editShiftForm.value.break_end)
                    payload.break_end = editShiftForm.value.break_end;
                return [4 /*yield*/, axios_1.default.put("api/shifts/".concat(editShiftForm.value.id), payload, {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _d.sent();
                if (response.data.success) {
                    toast.add({ severity: 'success', summary: 'Updated', detail: 'Shift updated successfully', life: 3000 });
                    editShiftDialogVisible.value = false;
                    fetchShiftDefinitions();
                }
                return [3 /*break*/, 5];
            case 3:
                err_9 = _d.sent();
                if (((_a = err_9.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    editShiftErrors.value = err_9.response.data.errors || {};
                }
                else {
                    showError(((_c = (_b = err_9.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to update shift');
                }
                return [3 /*break*/, 5];
            case 4:
                editShiftSaving.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var confirmDeleteShift = function (shift) {
    selectedShiftForDelete.value = shift;
    deleteShiftDialogVisible.value = true;
};
var deleteShift = function () { return __awaiter(void 0, void 0, void 0, function () {
    var err_10;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!selectedShiftForDelete.value)
                    return [2 /*return*/];
                deletingShift.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.delete("api/shifts/".concat(selectedShiftForDelete.value.id), {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Shift deleted successfully', life: 3000 });
                deleteShiftDialogVisible.value = false;
                selectedShiftForDelete.value = null;
                fetchShiftDefinitions();
                return [3 /*break*/, 5];
            case 3:
                err_10 = _c.sent();
                showError(((_b = (_a = err_10.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete shift');
                return [3 /*break*/, 5];
            case 4:
                deletingShift.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var toggleEditShiftDay = function (day) {
    var index = editShiftForm.value.week_days.indexOf(day);
    if (index === -1) {
        editShiftForm.value.week_days.push(day);
    }
    else {
        editShiftForm.value.week_days.splice(index, 1);
    }
};
// --- Helper API Calls ---
var fetchMyScheduleOptions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, schedules, userId_1, mySchedules, err_11;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('api/shift-schedules', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: { status: 'scheduled' }
                    })];
            case 1:
                response = _c.sent();
                if (response.data.success) {
                    schedules = ((_a = response.data.data) === null || _a === void 0 ? void 0 : _a.data) || response.data.data || [];
                    userId_1 = (_b = authStore.user) === null || _b === void 0 ? void 0 : _b.id;
                    mySchedules = schedules.filter(function (s) { var _a; return ((_a = s.employee) === null || _a === void 0 ? void 0 : _a.user_id) === userId_1; });
                    myShiftOptions.value = mySchedules.map(function (s) {
                        var _a;
                        return ({
                            label: "".concat(((_a = s.shift) === null || _a === void 0 ? void 0 : _a.name) || 'Shift', " \u2014 ").concat(formatDate(s.schedule_date)),
                            value: s.id
                        });
                    });
                }
                return [3 /*break*/, 3];
            case 2:
                err_11 = _c.sent();
                console.error('Failed to fetch my schedules', err_11);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var fetchReceiverScheduleOptions = function (receiverId) { return __awaiter(void 0, void 0, void 0, function () {
    var response, schedules, err_12;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                _b.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('api/shift-schedules', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: { employee_id: receiverId, status: 'scheduled' }
                    })];
            case 1:
                response = _b.sent();
                if (response.data.success) {
                    schedules = ((_a = response.data.data) === null || _a === void 0 ? void 0 : _a.data) || response.data.data || [];
                    receiverShiftOptions.value = schedules.map(function (s) {
                        var _a;
                        return ({
                            label: "".concat(((_a = s.shift) === null || _a === void 0 ? void 0 : _a.name) || 'Shift', " \u2014 ").concat(formatDate(s.schedule_date)),
                            value: s.id
                        });
                    });
                }
                return [3 /*break*/, 3];
            case 2:
                err_12 = _b.sent();
                console.error('Failed to fetch receiver schedules', err_12);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var fetchEmployeeOptions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, employees, err_13;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                _b.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('api/employees', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: { store_id: (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.store_id }
                    })];
            case 1:
                response = _b.sent();
                if (response.data.success) {
                    employees = response.data.data.data || response.data.data || [];
                    employeeOptions.value = employees.map(function (e) { return ({
                        label: "".concat(e.fname, " ").concat(e.lname),
                        value: e.id
                    }); });
                }
                return [3 /*break*/, 3];
            case 2:
                err_13 = _b.sent();
                console.error('Failed to fetch employees', err_13);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var fetchShiftOptions = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, shifts, err_14;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                _b.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('api/shifts', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: { store_id: (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.store_id }
                    })];
            case 1:
                response = _b.sent();
                if (response.data.success) {
                    shifts = response.data.data.data || response.data.data || [];
                    shiftOptions.value = shifts.map(function (s) { return ({
                        label: s.name,
                        value: s.id
                    }); });
                }
                return [3 /*break*/, 3];
            case 2:
                err_14 = _b.sent();
                console.error('Failed to fetch shifts', err_14);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
// --- Navigation ---
var goToCreateShift = function () { return router.push({ name: 'hr.shifts.create' }); };
var previousDay = function () {
    selectedDate.value = new Date(selectedDate.value.setDate(selectedDate.value.getDate() - 1));
    fetchCoverageData();
};
var nextDay = function () {
    selectedDate.value = new Date(selectedDate.value.setDate(selectedDate.value.getDate() + 1));
    fetchCoverageData();
};
var viewShiftDetails = function (shift) { return openEditShiftDialog(shift); };
var exportReport = function () {
    toast.add({ severity: 'info', summary: 'Export', detail: 'Generating report...', life: 3000 });
    // Implement export logic
};
// --- Lifecycle ---
(0, vue_1.onMounted)(function () {
    fetchData();
    fetchAssignments();
    fetchSwapRequests();
    fetchShiftDefinitions();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6 text-sm" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 ml-auto" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.exportReport) });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.activeTab === 'assignments') {
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { loading: (__VLS_ctx.assignmentDialog), label: "New Assignment", icon: "pi pi-plus", severity: "info" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { loading: (__VLS_ctx.assignmentDialog), label: "New Assignment", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: (__VLS_ctx.openAssignmentDialog) });
    var __VLS_10;
    var __VLS_11;
}
if (__VLS_ctx.activeTab === 'shiftswap') {
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onClick': {} }, { label: "Request Swap", icon: "pi pi-share-alt", severity: "info" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Request Swap", icon: "pi pi-share-alt", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ click: {} },
        { onClick: (__VLS_ctx.openSwapDialog) });
    var __VLS_17;
    var __VLS_18;
}
if (__VLS_ctx.activeTab !== 'coverage') {
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ 'onClick': {} }, { label: "Create Shift", icon: "pi pi-plus", severity: "info" })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Shift", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = void 0;
    var __VLS_27 = ({ click: {} },
        { onClick: (__VLS_ctx.goToCreateShift) });
    var __VLS_24;
    var __VLS_25;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-5 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-5']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.dashboardStats.totalEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.dashboardStats.activeEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.dashboardStats.todayShifts);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar text-green-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.dashboardStats.onLeave);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.dashboardStats.pendingSwaps);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-orange-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-orange-400 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.dashboardStats.nightDiffEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-moon text-purple-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-moon']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold mt-1 text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
(__VLS_ctx.dashboardStats.unfilledShifts);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-red-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-red-400 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-400']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center items-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-spin pi-spinner text-3xl text-blue-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spin']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spinner']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
}
else if (__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-50 border border-red-200 rounded-lg p-4 text-red-700" }));
    /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-red-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.error);
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign(__assign({ 'onClick': {} }, { label: "Retry", icon: "pi pi-refresh", severity: "danger", outlined: true, size: "small" }), { class: "mt-2" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Retry", icon: "pi pi-refresh", severity: "danger", outlined: true, size: "small" }), { class: "mt-2" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ click: {} },
        { onClick: (__VLS_ctx.fetchData) });
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    var __VLS_31;
    var __VLS_32;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tabs | typeof __VLS_components.Tabs} */
    Tabs;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35({
        value: (__VLS_ctx.activeTab),
    }));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.activeTab),
        }], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = __VLS_38.slots.default;
    var __VLS_41 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabList | typeof __VLS_components.TabList} */
    TabList;
    // @ts-ignore
    var __VLS_42 = __VLS_asFunctionalComponent1(__VLS_41, new __VLS_41(__assign({ class: "px-4 pt-2 border-b border-gray-100" })));
    var __VLS_43 = __VLS_42.apply(void 0, __spreadArray([__assign({ class: "px-4 pt-2 border-b border-gray-100" })], __VLS_functionalComponentArgsRest(__VLS_42), false));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    var __VLS_46 = __VLS_44.slots.default;
    var __VLS_47 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_48 = __VLS_asFunctionalComponent1(__VLS_47, new __VLS_47({
        value: "coverage",
    }));
    var __VLS_49 = __VLS_48.apply(void 0, __spreadArray([{
            value: "coverage",
        }], __VLS_functionalComponentArgsRest(__VLS_48), false));
    var __VLS_52 = __VLS_50.slots.default;
    // @ts-ignore
    [exportReport, activeTab, activeTab, activeTab, activeTab, assignmentDialog, openAssignmentDialog, openSwapDialog, goToCreateShift, dashboardStats, dashboardStats, dashboardStats, dashboardStats, dashboardStats, dashboardStats, dashboardStats, loading, error, error, fetchData,];
    var __VLS_50;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
        value: "assignments",
    }));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
            value: "assignments",
        }], __VLS_functionalComponentArgsRest(__VLS_54), false));
    var __VLS_58 = __VLS_56.slots.default;
    // @ts-ignore
    [];
    var __VLS_56;
    var __VLS_59 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59({
        value: "shiftswap",
    }));
    var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([{
            value: "shiftswap",
        }], __VLS_functionalComponentArgsRest(__VLS_60), false));
    var __VLS_64 = __VLS_62.slots.default;
    // @ts-ignore
    [];
    var __VLS_62;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({
        value: "shifts",
    }));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{
            value: "shifts",
        }], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = __VLS_68.slots.default;
    // @ts-ignore
    [];
    var __VLS_68;
    // @ts-ignore
    [];
    var __VLS_44;
    var __VLS_71 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanels | typeof __VLS_components.TabPanels} */
    TabPanels;
    // @ts-ignore
    var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71(__assign({ class: "p-4" })));
    var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([__assign({ class: "p-4" })], __VLS_functionalComponentArgsRest(__VLS_72), false));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    var __VLS_76 = __VLS_74.slots.default;
    var __VLS_77 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77({
        value: "coverage",
    }));
    var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([{
            value: "coverage",
        }], __VLS_functionalComponentArgsRest(__VLS_78), false));
    var __VLS_82 = __VLS_80.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_83 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, rounded: true, size: "small" })));
    var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_84), false));
    var __VLS_88 = void 0;
    var __VLS_89 = ({ click: {} },
        { onClick: (__VLS_ctx.previousDay) });
    var __VLS_86;
    var __VLS_87;
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
    DatePicker;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.selectedDate), dateFormat: "MM dd, yy" }), { class: "w-40" })));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.selectedDate), dateFormat: "MM dd, yy" }), { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_91), false));
    var __VLS_95 = void 0;
    var __VLS_96 = ({ dateSelect: {} },
        { onDateSelect: (__VLS_ctx.fetchCoverageData) });
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_93;
    var __VLS_94;
    var __VLS_97 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, rounded: true, size: "small" })));
    var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_98), false));
    var __VLS_102 = void 0;
    var __VLS_103 = ({ click: {} },
        { onClick: (__VLS_ctx.nextDay) });
    var __VLS_100;
    var __VLS_101;
    if (__VLS_ctx.isToday) {
        var __VLS_104 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104({
            value: "Today",
            severity: "info",
        }));
        var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([{
                value: "Today",
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_105), false));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    for (var _i = 0, _w = __VLS_vFor((__VLS_ctx.departments)); _i < _w.length; _i++) {
        var dept = _w[_i][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (dept.id) }, { class: "border border-gray-100 rounded-lg p-4" }));
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        (dept.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (dept.scheduled);
        (dept.totalEmployees);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400 ml-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full bg-gray-100 rounded-full h-2 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-500 h-2 rounded-full" }, { style: ({ width: dept.coveragePercentage + '%' }) }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2 mt-3" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
        for (var _x = 0, _y = __VLS_vFor((dept.scheduledEmployees)); _x < _y.length; _x++) {
            var emp = _y[_x][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (emp.id) }, { class: "flex items-center justify-between text-sm" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            var __VLS_109 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Avatar} */
            Avatar;
            // @ts-ignore
            var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "small" }, { class: "bg-blue-100 text-blue-600" })));
            var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "small" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_110), false));
            /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (emp.name);
            var __VLS_114 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114({
                value: (emp.shiftType),
                severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
                size: "small",
            }));
            var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([{
                    value: (emp.shiftType),
                    severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_115), false));
            // @ts-ignore
            [previousDay, selectedDate, fetchCoverageData, nextDay, isToday, departments, getInitials, getShiftSeverity,];
        }
        for (var _z = 0, _0 = __VLS_vFor((dept.unfilledCount)); _z < _0.length; _z++) {
            var n = _0[_z][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: ('unfilled-' + n) }, { class: "flex items-center gap-2 text-sm text-gray-400" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-plus-circle text-xs" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-plus-circle']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (n);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_80;
    var __VLS_119 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119({
        value: "assignments",
    }));
    var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([{
            value: "assignments",
        }], __VLS_functionalComponentArgsRest(__VLS_120), false));
    var __VLS_124 = __VLS_122.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_125 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_126 = __VLS_asFunctionalComponent1(__VLS_125, new __VLS_125({}));
    var __VLS_127 = __VLS_126.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_126), false));
    var __VLS_130 = __VLS_128.slots.default;
    var __VLS_131 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ class: "pi pi-search" })));
    var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_136 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.assignmentFilters.search), placeholder: "Search Employee...", size: "small" })));
    var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.assignmentFilters.search), placeholder: "Search Employee...", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_137), false));
    var __VLS_141 = void 0;
    var __VLS_142 = ({ input: {} },
        { onInput: (__VLS_ctx.fetchAssignments) });
    var __VLS_139;
    var __VLS_140;
    // @ts-ignore
    [assignmentFilters, fetchAssignments,];
    var __VLS_128;
    var __VLS_143 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.assignmentFilters.type), options: (__VLS_ctx.assignmentTypeOptions), optionLabel: "label", optionValue: "value", size: "small", placeholder: "Assignment Type", showClear: true })));
    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.assignmentFilters.type), options: (__VLS_ctx.assignmentTypeOptions), optionLabel: "label", optionValue: "value", size: "small", placeholder: "Assignment Type", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_144), false));
    var __VLS_148 = void 0;
    var __VLS_149 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchAssignments) });
    var __VLS_146;
    var __VLS_147;
    var __VLS_150 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign({ value: (__VLS_ctx.assignments), paginator: (true), rows: (10), loading: (__VLS_ctx.assignmentsLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })));
    var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.assignments), paginator: (true), rows: (10), loading: (__VLS_ctx.assignmentsLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_151), false));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    var __VLS_155 = __VLS_153.slots.default;
    var __VLS_156 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_157 = __VLS_asFunctionalComponent1(__VLS_156, new __VLS_156({
        field: "employee.full_name",
        header: "Employee",
        sortable: true,
    }));
    var __VLS_158 = __VLS_157.apply(void 0, __spreadArray([{
            field: "employee.full_name",
            header: "Employee",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_157), false));
    var __VLS_161 = __VLS_159.slots.default;
    {
        var __VLS_162 = __VLS_159.slots.body;
        var data = __VLS_vSlot(__VLS_162)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        (((_a = data.employee) === null || _a === void 0 ? void 0 : _a.fname) || 'N/A');
        (((_b = data.employee) === null || _b === void 0 ? void 0 : _b.lname) || 'N/A');
        // @ts-ignore
        [assignmentFilters, fetchAssignments, assignmentTypeOptions, assignments, assignmentsLoading,];
    }
    // @ts-ignore
    [];
    var __VLS_159;
    var __VLS_163 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163({
        field: "shift.name",
        header: "Shift",
        sortable: true,
    }));
    var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([{
            field: "shift.name",
            header: "Shift",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_164), false));
    var __VLS_168 = __VLS_166.slots.default;
    {
        var __VLS_169 = __VLS_166.slots.body;
        var data = __VLS_vSlot(__VLS_169)[0].data;
        var __VLS_170 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170({
            value: ((_c = data.shift) === null || _c === void 0 ? void 0 : _c.name),
            severity: (__VLS_ctx.getShiftSeverity((_d = data.shift) === null || _d === void 0 ? void 0 : _d.name)),
            rounded: true,
        }));
        var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([{
                value: ((_e = data.shift) === null || _e === void 0 ? void 0 : _e.name),
                severity: (__VLS_ctx.getShiftSeverity((_f = data.shift) === null || _f === void 0 ? void 0 : _f.name)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_171), false));
        // @ts-ignore
        [getShiftSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_166;
    var __VLS_175 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175({
        field: "start_date",
        header: "Start Date",
        sortable: true,
    }));
    var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([{
            field: "start_date",
            header: "Start Date",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_176), false));
    var __VLS_180 = __VLS_178.slots.default;
    {
        var __VLS_181 = __VLS_178.slots.body;
        var data = __VLS_vSlot(__VLS_181)[0].data;
        (__VLS_ctx.formatDate(data.start_date));
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_178;
    var __VLS_182 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_183 = __VLS_asFunctionalComponent1(__VLS_182, new __VLS_182({
        field: "end_date",
        header: "End Date",
        sortable: true,
    }));
    var __VLS_184 = __VLS_183.apply(void 0, __spreadArray([{
            field: "end_date",
            header: "End Date",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_183), false));
    var __VLS_187 = __VLS_185.slots.default;
    {
        var __VLS_188 = __VLS_185.slots.body;
        var data = __VLS_vSlot(__VLS_188)[0].data;
        (data.end_date ? __VLS_ctx.formatDate(data.end_date) : 'Permanent');
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_185;
    var __VLS_189 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_190 = __VLS_asFunctionalComponent1(__VLS_189, new __VLS_189({
        field: "assignment_type",
        header: "Type",
    }));
    var __VLS_191 = __VLS_190.apply(void 0, __spreadArray([{
            field: "assignment_type",
            header: "Type",
        }], __VLS_functionalComponentArgsRest(__VLS_190), false));
    var __VLS_194 = __VLS_192.slots.default;
    {
        var __VLS_195 = __VLS_192.slots.body;
        var data = __VLS_vSlot(__VLS_195)[0].data;
        var __VLS_196 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_197 = __VLS_asFunctionalComponent1(__VLS_196, new __VLS_196({
            value: (data.assignment_type),
            severity: (data.assignment_type === 'permanent' ? 'success' : 'warning'),
            rounded: true,
        }));
        var __VLS_198 = __VLS_197.apply(void 0, __spreadArray([{
                value: (data.assignment_type),
                severity: (data.assignment_type === 'permanent' ? 'success' : 'warning'),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_197), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_192;
    var __VLS_201 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_202 = __VLS_asFunctionalComponent1(__VLS_201, new __VLS_201(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_203 = __VLS_202.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_202), false));
    var __VLS_206 = __VLS_204.slots.default;
    {
        var __VLS_207 = __VLS_204.slots.body;
        var data_1 = __VLS_vSlot(__VLS_207)[0].data;
        var __VLS_208 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_209 = __VLS_asFunctionalComponent1(__VLS_208, new __VLS_208(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })));
        var __VLS_210 = __VLS_209.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_209), false));
        var __VLS_213 = void 0;
        var __VLS_214 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.error))
                        return;
                    __VLS_ctx.confirmDeleteAssignment(data_1);
                    // @ts-ignore
                    [confirmDeleteAssignment,];
                } });
        var __VLS_211;
        var __VLS_212;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_204;
    // @ts-ignore
    [];
    var __VLS_153;
    // @ts-ignore
    [];
    var __VLS_122;
    var __VLS_215 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_216 = __VLS_asFunctionalComponent1(__VLS_215, new __VLS_215({
        value: "shiftswap",
    }));
    var __VLS_217 = __VLS_216.apply(void 0, __spreadArray([{
            value: "shiftswap",
        }], __VLS_functionalComponentArgsRest(__VLS_216), false));
    var __VLS_220 = __VLS_218.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_221 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_222 = __VLS_asFunctionalComponent1(__VLS_221, new __VLS_221({}));
    var __VLS_223 = __VLS_222.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_222), false));
    var __VLS_226 = __VLS_224.slots.default;
    var __VLS_227 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_228 = __VLS_asFunctionalComponent1(__VLS_227, new __VLS_227(__assign({ class: "pi pi-search" })));
    var __VLS_229 = __VLS_228.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_228), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_232 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_233 = __VLS_asFunctionalComponent1(__VLS_232, new __VLS_232(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.swapFilters.search), placeholder: "Search...", size: "small" })));
    var __VLS_234 = __VLS_233.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.swapFilters.search), placeholder: "Search...", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_233), false));
    var __VLS_237 = void 0;
    var __VLS_238 = ({ input: {} },
        { onInput: (__VLS_ctx.fetchSwapRequests) });
    var __VLS_235;
    var __VLS_236;
    // @ts-ignore
    [swapFilters, fetchSwapRequests,];
    var __VLS_224;
    var __VLS_239 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_240 = __VLS_asFunctionalComponent1(__VLS_239, new __VLS_239(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.swapFilters.status), options: (__VLS_ctx.swapStatusOptions), optionLabel: "label", optionValue: "value", size: "small", placeholder: "Status", showClear: true })));
    var __VLS_241 = __VLS_240.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.swapFilters.status), options: (__VLS_ctx.swapStatusOptions), optionLabel: "label", optionValue: "value", size: "small", placeholder: "Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_240), false));
    var __VLS_244 = void 0;
    var __VLS_245 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchSwapRequests) });
    var __VLS_242;
    var __VLS_243;
    var __VLS_246 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_247 = __VLS_asFunctionalComponent1(__VLS_246, new __VLS_246(__assign({ value: (__VLS_ctx.swapRequests), paginator: (true), rows: (10), loading: (__VLS_ctx.swapLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })));
    var __VLS_248 = __VLS_247.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.swapRequests), paginator: (true), rows: (10), loading: (__VLS_ctx.swapLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_247), false));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    var __VLS_251 = __VLS_249.slots.default;
    var __VLS_252 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_253 = __VLS_asFunctionalComponent1(__VLS_252, new __VLS_252({
        header: "Requestor",
    }));
    var __VLS_254 = __VLS_253.apply(void 0, __spreadArray([{
            header: "Requestor",
        }], __VLS_functionalComponentArgsRest(__VLS_253), false));
    var __VLS_257 = __VLS_255.slots.default;
    {
        var __VLS_258 = __VLS_255.slots.body;
        var data = __VLS_vSlot(__VLS_258)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_259 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_260 = __VLS_asFunctionalComponent1(__VLS_259, new __VLS_259(__assign({ label: ((__VLS_ctx.getInitials((_g = data.requestor) === null || _g === void 0 ? void 0 : _g.fname) + __VLS_ctx.getInitials((_h = data.requestor) === null || _h === void 0 ? void 0 : _h.lname))), size: "small" }, { class: "bg-blue-100 text-blue-600" })));
        var __VLS_261 = __VLS_260.apply(void 0, __spreadArray([__assign({ label: ((__VLS_ctx.getInitials((_j = data.requestor) === null || _j === void 0 ? void 0 : _j.fname) + __VLS_ctx.getInitials((_k = data.requestor) === null || _k === void 0 ? void 0 : _k.lname))), size: "small" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_260), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        (((_l = data.requestor) === null || _l === void 0 ? void 0 : _l.fname) || 'N/A');
        (((_m = data.requestor) === null || _m === void 0 ? void 0 : _m.lname) || 'N/A');
        // @ts-ignore
        [getInitials, getInitials, swapFilters, fetchSwapRequests, swapStatusOptions, swapRequests, swapLoading,];
    }
    // @ts-ignore
    [];
    var __VLS_255;
    var __VLS_264 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_265 = __VLS_asFunctionalComponent1(__VLS_264, new __VLS_264({
        header: "Receiver",
    }));
    var __VLS_266 = __VLS_265.apply(void 0, __spreadArray([{
            header: "Receiver",
        }], __VLS_functionalComponentArgsRest(__VLS_265), false));
    var __VLS_269 = __VLS_267.slots.default;
    {
        var __VLS_270 = __VLS_267.slots.body;
        var data = __VLS_vSlot(__VLS_270)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_271 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_272 = __VLS_asFunctionalComponent1(__VLS_271, new __VLS_271(__assign({ label: ((__VLS_ctx.getInitials((_o = data.receiver) === null || _o === void 0 ? void 0 : _o.fname) + __VLS_ctx.getInitials((_p = data.receiver) === null || _p === void 0 ? void 0 : _p.lname))), size: "small" }, { class: "bg-green-100 text-green-600" })));
        var __VLS_273 = __VLS_272.apply(void 0, __spreadArray([__assign({ label: ((__VLS_ctx.getInitials((_q = data.receiver) === null || _q === void 0 ? void 0 : _q.fname) + __VLS_ctx.getInitials((_r = data.receiver) === null || _r === void 0 ? void 0 : _r.lname))), size: "small" }, { class: "bg-green-100 text-green-600" })], __VLS_functionalComponentArgsRest(__VLS_272), false));
        /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        (((_s = data.receiver) === null || _s === void 0 ? void 0 : _s.fname) || 'N/A');
        (((_t = data.receiver) === null || _t === void 0 ? void 0 : _t.lname) || 'N/A');
        // @ts-ignore
        [getInitials, getInitials,];
    }
    // @ts-ignore
    [];
    var __VLS_267;
    var __VLS_276 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_277 = __VLS_asFunctionalComponent1(__VLS_276, new __VLS_276({
        header: "Swap Details",
    }));
    var __VLS_278 = __VLS_277.apply(void 0, __spreadArray([{
            header: "Swap Details",
        }], __VLS_functionalComponentArgsRest(__VLS_277), false));
    var __VLS_281 = __VLS_279.slots.default;
    {
        var __VLS_282 = __VLS_279.slots.body;
        var data = __VLS_vSlot(__VLS_282)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        (data.swap_type);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        (__VLS_ctx.formatDate((_u = data.requestorSchedule) === null || _u === void 0 ? void 0 : _u.schedule_date));
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_279;
    var __VLS_283 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_284 = __VLS_asFunctionalComponent1(__VLS_283, new __VLS_283({
        field: "status",
        header: "Status",
    }));
    var __VLS_285 = __VLS_284.apply(void 0, __spreadArray([{
            field: "status",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_284), false));
    var __VLS_288 = __VLS_286.slots.default;
    {
        var __VLS_289 = __VLS_286.slots.body;
        var data = __VLS_vSlot(__VLS_289)[0].data;
        var __VLS_290 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_291 = __VLS_asFunctionalComponent1(__VLS_290, new __VLS_290({
            value: (data.status.toUpperCase()),
            severity: (__VLS_ctx.getSwapStatusSeverity(data.status)),
            rounded: true,
        }));
        var __VLS_292 = __VLS_291.apply(void 0, __spreadArray([{
                value: (data.status.toUpperCase()),
                severity: (__VLS_ctx.getSwapStatusSeverity(data.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_291), false));
        // @ts-ignore
        [getSwapStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_286;
    var __VLS_295 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_296 = __VLS_asFunctionalComponent1(__VLS_295, new __VLS_295({
        header: "Actions",
    }));
    var __VLS_297 = __VLS_296.apply(void 0, __spreadArray([{
            header: "Actions",
        }], __VLS_functionalComponentArgsRest(__VLS_296), false));
    var __VLS_300 = __VLS_298.slots.default;
    {
        var __VLS_301 = __VLS_298.slots.body;
        var data_2 = __VLS_vSlot(__VLS_301)[0].data;
        if (data_2.status === 'pending' && !__VLS_ctx.isMyRequest(data_2)) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            var __VLS_302 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_303 = __VLS_asFunctionalComponent1(__VLS_302, new __VLS_302(__assign({ 'onClick': {} }, { label: "Accept", icon: "pi pi-check", size: "small", severity: "success" })));
            var __VLS_304 = __VLS_303.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Accept", icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_303), false));
            var __VLS_307 = void 0;
            var __VLS_308 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.error))
                            return;
                        if (!(data_2.status === 'pending' && !__VLS_ctx.isMyRequest(data_2)))
                            return;
                        __VLS_ctx.confirmSwapAction(data_2, 'accept');
                        // @ts-ignore
                        [isMyRequest, confirmSwapAction,];
                    } });
            var __VLS_305;
            var __VLS_306;
            var __VLS_309 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_310 = __VLS_asFunctionalComponent1(__VLS_309, new __VLS_309(__assign({ 'onClick': {} }, { label: "Reject", icon: "pi pi-times", size: "small", severity: "danger", outlined: true })));
            var __VLS_311 = __VLS_310.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject", icon: "pi pi-times", size: "small", severity: "danger", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_310), false));
            var __VLS_314 = void 0;
            var __VLS_315 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.error))
                            return;
                        if (!(data_2.status === 'pending' && !__VLS_ctx.isMyRequest(data_2)))
                            return;
                        __VLS_ctx.confirmSwapAction(data_2, 'reject');
                        // @ts-ignore
                        [confirmSwapAction,];
                    } });
            var __VLS_312;
            var __VLS_313;
        }
        else if (data_2.status === 'pending' && __VLS_ctx.isMyRequest(data_2)) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            var __VLS_316 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_317 = __VLS_asFunctionalComponent1(__VLS_316, new __VLS_316(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-ban", size: "small", severity: "warning", outlined: true })));
            var __VLS_318 = __VLS_317.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-ban", size: "small", severity: "warning", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_317), false));
            var __VLS_321 = void 0;
            var __VLS_322 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.error))
                            return;
                        if (!!(data_2.status === 'pending' && !__VLS_ctx.isMyRequest(data_2)))
                            return;
                        if (!(data_2.status === 'pending' && __VLS_ctx.isMyRequest(data_2)))
                            return;
                        __VLS_ctx.confirmSwapAction(data_2, 'cancel');
                        // @ts-ignore
                        [isMyRequest, confirmSwapAction,];
                    } });
            var __VLS_319;
            var __VLS_320;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400 text-xs" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_298;
    // @ts-ignore
    [];
    var __VLS_249;
    // @ts-ignore
    [];
    var __VLS_218;
    var __VLS_323 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_324 = __VLS_asFunctionalComponent1(__VLS_323, new __VLS_323({
        value: "shifts",
    }));
    var __VLS_325 = __VLS_324.apply(void 0, __spreadArray([{
            value: "shifts",
        }], __VLS_functionalComponentArgsRest(__VLS_324), false));
    var __VLS_328 = __VLS_326.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_329 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_330 = __VLS_asFunctionalComponent1(__VLS_329, new __VLS_329({}));
    var __VLS_331 = __VLS_330.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_330), false));
    var __VLS_334 = __VLS_332.slots.default;
    var __VLS_335 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_336 = __VLS_asFunctionalComponent1(__VLS_335, new __VLS_335(__assign({ class: "pi pi-search" })));
    var __VLS_337 = __VLS_336.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_336), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_340 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_341 = __VLS_asFunctionalComponent1(__VLS_340, new __VLS_340({
        modelValue: (__VLS_ctx.shiftDefFilters.search),
        placeholder: "Search shifts...",
        size: "small",
    }));
    var __VLS_342 = __VLS_341.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.shiftDefFilters.search),
            placeholder: "Search shifts...",
            size: "small",
        }], __VLS_functionalComponentArgsRest(__VLS_341), false));
    // @ts-ignore
    [shiftDefFilters,];
    var __VLS_332;
    var __VLS_345 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_346 = __VLS_asFunctionalComponent1(__VLS_345, new __VLS_345({
        modelValue: (__VLS_ctx.shiftDefFilters.type),
        options: ([{ label: 'Fixed', value: 'fixed' }, { label: 'Rotating', value: 'rotating' }, { label: 'Flexible', value: 'flexible' }]),
        optionLabel: "label",
        optionValue: "value",
        size: "small",
        placeholder: "Shift Type",
        showClear: true,
    }));
    var __VLS_347 = __VLS_346.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.shiftDefFilters.type),
            options: ([{ label: 'Fixed', value: 'fixed' }, { label: 'Rotating', value: 'rotating' }, { label: 'Flexible', value: 'flexible' }]),
            optionLabel: "label",
            optionValue: "value",
            size: "small",
            placeholder: "Shift Type",
            showClear: true,
        }], __VLS_functionalComponentArgsRest(__VLS_346), false));
    var __VLS_350 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_351 = __VLS_asFunctionalComponent1(__VLS_350, new __VLS_350(__assign({ value: (__VLS_ctx.filteredShiftDefinitions), paginator: (true), rows: (10), loading: (__VLS_ctx.shiftDefsLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })));
    var __VLS_352 = __VLS_351.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.filteredShiftDefinitions), paginator: (true), rows: (10), loading: (__VLS_ctx.shiftDefsLoading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_351), false));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    var __VLS_355 = __VLS_353.slots.default;
    var __VLS_356 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_357 = __VLS_asFunctionalComponent1(__VLS_356, new __VLS_356({
        field: "name",
        header: "Name",
        sortable: true,
    }));
    var __VLS_358 = __VLS_357.apply(void 0, __spreadArray([{
            field: "name",
            header: "Name",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_357), false));
    var __VLS_361 = __VLS_359.slots.default;
    {
        var __VLS_362 = __VLS_359.slots.body;
        var data = __VLS_vSlot(__VLS_362)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-3 h-3 rounded-full inline-block" }, { style: ({ background: data.color || '#3b82f6' }) }));
        /** @type {__VLS_StyleScopedClasses['w-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['inline-block']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.name);
        // @ts-ignore
        [shiftDefFilters, filteredShiftDefinitions, shiftDefsLoading,];
    }
    // @ts-ignore
    [];
    var __VLS_359;
    var __VLS_363 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_364 = __VLS_asFunctionalComponent1(__VLS_363, new __VLS_363({
        field: "code",
        header: "Code",
        sortable: true,
    }));
    var __VLS_365 = __VLS_364.apply(void 0, __spreadArray([{
            field: "code",
            header: "Code",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_364), false));
    var __VLS_368 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_369 = __VLS_asFunctionalComponent1(__VLS_368, new __VLS_368({
        header: "Schedule",
    }));
    var __VLS_370 = __VLS_369.apply(void 0, __spreadArray([{
            header: "Schedule",
        }], __VLS_functionalComponentArgsRest(__VLS_369), false));
    var __VLS_373 = __VLS_371.slots.default;
    {
        var __VLS_374 = __VLS_371.slots.body;
        var data = __VLS_vSlot(__VLS_374)[0].data;
        (data.start_time ? String(data.start_time).substring(0, 5) : '--');
        (data.end_time ? String(data.end_time).substring(0, 5) : '--');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_371;
    var __VLS_375 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_376 = __VLS_asFunctionalComponent1(__VLS_375, new __VLS_375({
        field: "total_hours",
        header: "Hours",
    }));
    var __VLS_377 = __VLS_376.apply(void 0, __spreadArray([{
            field: "total_hours",
            header: "Hours",
        }], __VLS_functionalComponentArgsRest(__VLS_376), false));
    var __VLS_380 = __VLS_378.slots.default;
    {
        var __VLS_381 = __VLS_378.slots.body;
        var data = __VLS_vSlot(__VLS_381)[0].data;
        (data.total_hours);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_378;
    var __VLS_382 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_383 = __VLS_asFunctionalComponent1(__VLS_382, new __VLS_382({
        field: "shift_type",
        header: "Type",
        sortable: true,
    }));
    var __VLS_384 = __VLS_383.apply(void 0, __spreadArray([{
            field: "shift_type",
            header: "Type",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_383), false));
    var __VLS_387 = __VLS_385.slots.default;
    {
        var __VLS_388 = __VLS_385.slots.body;
        var data = __VLS_vSlot(__VLS_388)[0].data;
        var __VLS_389 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_390 = __VLS_asFunctionalComponent1(__VLS_389, new __VLS_389({
            value: (data.shift_type),
            severity: "info",
            rounded: true,
        }));
        var __VLS_391 = __VLS_390.apply(void 0, __spreadArray([{
                value: (data.shift_type),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_390), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_385;
    var __VLS_394 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_395 = __VLS_asFunctionalComponent1(__VLS_394, new __VLS_394({
        field: "grace_period_minutes",
        header: "Grace (min)",
    }));
    var __VLS_396 = __VLS_395.apply(void 0, __spreadArray([{
            field: "grace_period_minutes",
            header: "Grace (min)",
        }], __VLS_functionalComponentArgsRest(__VLS_395), false));
    var __VLS_399 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_400 = __VLS_asFunctionalComponent1(__VLS_399, new __VLS_399({
        field: "is_active",
        header: "Status",
    }));
    var __VLS_401 = __VLS_400.apply(void 0, __spreadArray([{
            field: "is_active",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_400), false));
    var __VLS_404 = __VLS_402.slots.default;
    {
        var __VLS_405 = __VLS_402.slots.body;
        var data = __VLS_vSlot(__VLS_405)[0].data;
        var __VLS_406 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_407 = __VLS_asFunctionalComponent1(__VLS_406, new __VLS_406({
            value: (data.is_active ? 'Active' : 'Inactive'),
            severity: (data.is_active ? 'success' : 'secondary'),
            rounded: true,
        }));
        var __VLS_408 = __VLS_407.apply(void 0, __spreadArray([{
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'secondary'),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_407), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_402;
    var __VLS_411 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_412 = __VLS_asFunctionalComponent1(__VLS_411, new __VLS_411(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_413 = __VLS_412.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_412), false));
    var __VLS_416 = __VLS_414.slots.default;
    {
        var __VLS_417 = __VLS_414.slots.body;
        var data_3 = __VLS_vSlot(__VLS_417)[0].data;
        var __VLS_418 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_419 = __VLS_asFunctionalComponent1(__VLS_418, new __VLS_418(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_420 = __VLS_419.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_419), false));
        var __VLS_423 = void 0;
        var __VLS_424 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.error))
                        return;
                    __VLS_ctx.openEditShiftDialog(data_3);
                    // @ts-ignore
                    [openEditShiftDialog,];
                } });
        var __VLS_421;
        var __VLS_422;
        var __VLS_425 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_426 = __VLS_asFunctionalComponent1(__VLS_425, new __VLS_425(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })));
        var __VLS_427 = __VLS_426.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_426), false));
        var __VLS_430 = void 0;
        var __VLS_431 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.error))
                        return;
                    __VLS_ctx.confirmDeleteShift(data_3);
                    // @ts-ignore
                    [confirmDeleteShift,];
                } });
        var __VLS_428;
        var __VLS_429;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_414;
    // @ts-ignore
    [];
    var __VLS_353;
    // @ts-ignore
    [];
    var __VLS_326;
    // @ts-ignore
    [];
    var __VLS_74;
    // @ts-ignore
    [];
    var __VLS_38;
}
var __VLS_432;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_433 = __VLS_asFunctionalComponent1(__VLS_432, new __VLS_432(__assign({ visible: (__VLS_ctx.assignmentDialogVisible), modal: true, header: "Create Assignment" }, { style: ({ width: '50vw' }) })));
var __VLS_434 = __VLS_433.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.assignmentDialogVisible), modal: true, header: "Create Assignment" }, { style: ({ width: '50vw' }) })], __VLS_functionalComponentArgsRest(__VLS_433), false));
var __VLS_437 = __VLS_435.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_438;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_439 = __VLS_asFunctionalComponent1(__VLS_438, new __VLS_438(__assign({ modelValue: (__VLS_ctx.assignmentForm.employee_id), options: (__VLS_ctx.employeeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Employee" }, { class: "w-full" })));
var __VLS_440 = __VLS_439.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.employee_id), options: (__VLS_ctx.employeeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Employee" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_439), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_443;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_444 = __VLS_asFunctionalComponent1(__VLS_443, new __VLS_443(__assign({ modelValue: (__VLS_ctx.assignmentForm.shift_id), options: (__VLS_ctx.shiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Shift" }, { class: "w-full" })));
var __VLS_445 = __VLS_444.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.shift_id), options: (__VLS_ctx.shiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_444), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_448;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_449 = __VLS_asFunctionalComponent1(__VLS_448, new __VLS_448(__assign({ modelValue: (__VLS_ctx.assignmentForm.start_date), showIcon: true }, { class: "w-full" })));
var __VLS_450 = __VLS_449.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.start_date), showIcon: true }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_449), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_453;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_454 = __VLS_asFunctionalComponent1(__VLS_453, new __VLS_453(__assign({ modelValue: (__VLS_ctx.assignmentForm.end_date), showIcon: true }, { class: "w-full" })));
var __VLS_455 = __VLS_454.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.end_date), showIcon: true }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_454), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_458;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_459 = __VLS_asFunctionalComponent1(__VLS_458, new __VLS_458(__assign({ modelValue: (__VLS_ctx.assignmentForm.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Type" }, { class: "w-full" })));
var __VLS_460 = __VLS_459.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_459), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_463;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_464 = __VLS_asFunctionalComponent1(__VLS_463, new __VLS_463(__assign({ modelValue: (__VLS_ctx.assignmentForm.notes), rows: "3" }, { class: "w-full" })));
var __VLS_465 = __VLS_464.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.assignmentForm.notes), rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_464), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_468 = __VLS_435.slots.footer;
    var __VLS_469 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_470 = __VLS_asFunctionalComponent1(__VLS_469, new __VLS_469(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_471 = __VLS_470.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_470), false));
    var __VLS_474 = void 0;
    var __VLS_475 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.assignmentDialogVisible = false;
                // @ts-ignore
                [assignmentTypeOptions, assignmentDialogVisible, assignmentDialogVisible, assignmentForm, assignmentForm, assignmentForm, assignmentForm, assignmentForm, assignmentForm, employeeOptions, shiftOptions,];
            } });
    var __VLS_472;
    var __VLS_473;
    var __VLS_476 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_477 = __VLS_asFunctionalComponent1(__VLS_476, new __VLS_476(__assign({ 'onClick': {} }, { label: "Create", icon: "pi pi-check", loading: (__VLS_ctx.formLoading) })));
    var __VLS_478 = __VLS_477.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create", icon: "pi pi-check", loading: (__VLS_ctx.formLoading) })], __VLS_functionalComponentArgsRest(__VLS_477), false));
    var __VLS_481 = void 0;
    var __VLS_482 = ({ click: {} },
        { onClick: (__VLS_ctx.createAssignment) });
    var __VLS_479;
    var __VLS_480;
    // @ts-ignore
    [formLoading, createAssignment,];
}
// @ts-ignore
[];
var __VLS_435;
var __VLS_483;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_484 = __VLS_asFunctionalComponent1(__VLS_483, new __VLS_483(__assign({ visible: (__VLS_ctx.swapDialogVisible), modal: true, header: "Request Shift Swap" }, { style: ({ width: '50vw' }) })));
var __VLS_485 = __VLS_484.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.swapDialogVisible), modal: true, header: "Request Shift Swap" }, { style: ({ width: '50vw' }) })], __VLS_functionalComponentArgsRest(__VLS_484), false));
var __VLS_488 = __VLS_486.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_489;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_490 = __VLS_asFunctionalComponent1(__VLS_489, new __VLS_489(__assign({ modelValue: (__VLS_ctx.swapForm.receiver_id), options: (__VLS_ctx.employeeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Employee" }, { class: "w-full" })));
var __VLS_491 = __VLS_490.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.receiver_id), options: (__VLS_ctx.employeeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Employee" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_490), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_494;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_495 = __VLS_asFunctionalComponent1(__VLS_494, new __VLS_494(__assign({ modelValue: (__VLS_ctx.swapForm.requestor_schedule_id), options: (__VLS_ctx.myShiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Your Shift" }, { class: "w-full" })));
var __VLS_496 = __VLS_495.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.requestor_schedule_id), options: (__VLS_ctx.myShiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Your Shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_495), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_499;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_500 = __VLS_asFunctionalComponent1(__VLS_499, new __VLS_499(__assign({ modelValue: (__VLS_ctx.swapForm.receiver_schedule_id), options: (__VLS_ctx.receiverShiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Receiver's Shift" }, { class: "w-full" })));
var __VLS_501 = __VLS_500.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.receiver_schedule_id), options: (__VLS_ctx.receiverShiftOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Receiver's Shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_500), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_504;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_505 = __VLS_asFunctionalComponent1(__VLS_504, new __VLS_504(__assign({ modelValue: (__VLS_ctx.swapForm.swap_type), options: (__VLS_ctx.swapTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Swap Type" }, { class: "w-full" })));
var __VLS_506 = __VLS_505.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.swap_type), options: (__VLS_ctx.swapTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select Swap Type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_505), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_509;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_510 = __VLS_asFunctionalComponent1(__VLS_509, new __VLS_509(__assign(__assign({ modelValue: (__VLS_ctx.swapForm.reason), rows: "3" }, { class: "w-full" }), { placeholder: "Explain why you want to swap..." })));
var __VLS_511 = __VLS_510.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.swapForm.reason), rows: "3" }, { class: "w-full" }), { placeholder: "Explain why you want to swap..." })], __VLS_functionalComponentArgsRest(__VLS_510), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_514 = __VLS_486.slots.footer;
    var __VLS_515 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_516 = __VLS_asFunctionalComponent1(__VLS_515, new __VLS_515(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_517 = __VLS_516.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_516), false));
    var __VLS_520 = void 0;
    var __VLS_521 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.swapDialogVisible = false;
                // @ts-ignore
                [employeeOptions, swapDialogVisible, swapDialogVisible, swapForm, swapForm, swapForm, swapForm, swapForm, myShiftOptions, receiverShiftOptions, swapTypeOptions,];
            } });
    var __VLS_518;
    var __VLS_519;
    var __VLS_522 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_523 = __VLS_asFunctionalComponent1(__VLS_522, new __VLS_522(__assign({ 'onClick': {} }, { label: "Submit Request", icon: "pi pi-send", loading: (__VLS_ctx.formLoading) })));
    var __VLS_524 = __VLS_523.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Submit Request", icon: "pi pi-send", loading: (__VLS_ctx.formLoading) })], __VLS_functionalComponentArgsRest(__VLS_523), false));
    var __VLS_527 = void 0;
    var __VLS_528 = ({ click: {} },
        { onClick: (__VLS_ctx.createSwapRequest) });
    var __VLS_525;
    var __VLS_526;
    // @ts-ignore
    [formLoading, createSwapRequest,];
}
// @ts-ignore
[];
var __VLS_486;
var __VLS_529;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_530 = __VLS_asFunctionalComponent1(__VLS_529, new __VLS_529(__assign(__assign({ visible: (__VLS_ctx.errorDialogVisible), modal: true, header: "Error" }, { style: ({ width: '40vw' }) }), { closable: (true) })));
var __VLS_531 = __VLS_530.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.errorDialogVisible), modal: true, header: "Error" }, { style: ({ width: '40vw' }) }), { closable: (true) })], __VLS_functionalComponentArgsRest(__VLS_530), false));
var __VLS_534 = __VLS_532.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle text-red-500 text-3xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-red-500" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "mt-1" }));
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.errorMessage);
{
    var __VLS_535 = __VLS_532.slots.footer;
    var __VLS_536 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_537 = __VLS_asFunctionalComponent1(__VLS_536, new __VLS_536(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_538 = __VLS_537.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_537), false));
    var __VLS_541 = void 0;
    var __VLS_542 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.errorDialogVisible = false;
                // @ts-ignore
                [errorDialogVisible, errorDialogVisible, errorMessage,];
            } });
    var __VLS_539;
    var __VLS_540;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_532;
var __VLS_543;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_544 = __VLS_asFunctionalComponent1(__VLS_543, new __VLS_543(__assign(__assign({ visible: (__VLS_ctx.confirmDialogVisible), modal: true, header: "Confirm Action" }, { style: ({ width: '40vw' }) }), { closable: (true) })));
var __VLS_545 = __VLS_544.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.confirmDialogVisible), modal: true, header: "Confirm Action" }, { style: ({ width: '40vw' }) }), { closable: (true) })], __VLS_functionalComponentArgsRest(__VLS_544), false));
var __VLS_548 = __VLS_546.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-orange-500 text-3xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
(__VLS_ctx.confirmTitle);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "mt-1" }));
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.confirmMessage);
{
    var __VLS_549 = __VLS_546.slots.footer;
    var __VLS_550 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_551 = __VLS_asFunctionalComponent1(__VLS_550, new __VLS_550(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_552 = __VLS_551.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_551), false));
    var __VLS_555 = void 0;
    var __VLS_556 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.confirmDialogVisible = false;
                // @ts-ignore
                [confirmDialogVisible, confirmDialogVisible, confirmTitle, confirmMessage,];
            } });
    var __VLS_553;
    var __VLS_554;
    var __VLS_557 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_558 = __VLS_asFunctionalComponent1(__VLS_557, new __VLS_557(__assign({ 'onClick': {} }, { label: (__VLS_ctx.confirmButtonLabel), severity: (__VLS_ctx.confirmButtonSeverity), loading: (__VLS_ctx.actionLoading) })));
    var __VLS_559 = __VLS_558.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.confirmButtonLabel), severity: (__VLS_ctx.confirmButtonSeverity), loading: (__VLS_ctx.actionLoading) })], __VLS_functionalComponentArgsRest(__VLS_558), false));
    var __VLS_562 = void 0;
    var __VLS_563 = ({ click: {} },
        { onClick: (__VLS_ctx.executeConfirmedAction) });
    var __VLS_560;
    var __VLS_561;
    // @ts-ignore
    [confirmButtonLabel, confirmButtonSeverity, actionLoading, executeConfirmedAction,];
}
// @ts-ignore
[];
var __VLS_546;
var __VLS_564;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_565 = __VLS_asFunctionalComponent1(__VLS_564, new __VLS_564(__assign({ visible: (__VLS_ctx.editShiftDialogVisible), modal: true, header: "Edit Shift" }, { style: ({ width: '55vw' }) })));
var __VLS_566 = __VLS_565.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.editShiftDialogVisible), modal: true, header: "Edit Shift" }, { style: ({ width: '55vw' }) })], __VLS_functionalComponentArgsRest(__VLS_565), false));
var __VLS_569 = __VLS_567.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 p-1" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['p-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_570;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_571 = __VLS_asFunctionalComponent1(__VLS_570, new __VLS_570(__assign(__assign({ modelValue: (__VLS_ctx.editShiftForm.name) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.editShiftErrors.name }) })));
var __VLS_572 = __VLS_571.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.editShiftForm.name) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.editShiftErrors.name }) })], __VLS_functionalComponentArgsRest(__VLS_571), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.editShiftErrors.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.editShiftErrors.name[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_575;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_576 = __VLS_asFunctionalComponent1(__VLS_575, new __VLS_575(__assign(__assign({ modelValue: (__VLS_ctx.editShiftForm.code) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.editShiftErrors.code }) })));
var __VLS_577 = __VLS_576.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.editShiftForm.code) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.editShiftErrors.code }) })], __VLS_functionalComponentArgsRest(__VLS_576), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.editShiftErrors.code) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.editShiftErrors.code[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_580;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_581 = __VLS_asFunctionalComponent1(__VLS_580, new __VLS_580(__assign({ modelValue: (__VLS_ctx.editShiftForm.shift_type), options: ([{ label: 'Fixed', value: 'fixed' }, { label: 'Rotating', value: 'rotating' }, { label: 'Flexible', value: 'flexible' }]), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
var __VLS_582 = __VLS_581.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.shift_type), options: ([{ label: 'Fixed', value: 'fixed' }, { label: 'Rotating', value: 'rotating' }, { label: 'Flexible', value: 'flexible' }]), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_581), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign({ type: "color" }, { class: "h-9 w-14 rounded border border-gray-300 cursor-pointer" }));
(__VLS_ctx.editShiftForm.color);
/** @type {__VLS_StyleScopedClasses['h-9']} */ ;
/** @type {__VLS_StyleScopedClasses['w-14']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
var __VLS_585;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_586 = __VLS_asFunctionalComponent1(__VLS_585, new __VLS_585(__assign({ modelValue: (__VLS_ctx.editShiftForm.color) }, { class: "flex-1" })));
var __VLS_587 = __VLS_586.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.color) }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_586), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_590;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_591 = __VLS_asFunctionalComponent1(__VLS_590, new __VLS_590(__assign({ modelValue: (__VLS_ctx.editShiftForm.start_time), type: "time" }, { class: "w-full" })));
var __VLS_592 = __VLS_591.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.start_time), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_591), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_595;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_596 = __VLS_asFunctionalComponent1(__VLS_595, new __VLS_595(__assign({ modelValue: (__VLS_ctx.editShiftForm.end_time), type: "time" }, { class: "w-full" })));
var __VLS_597 = __VLS_596.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.end_time), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_596), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_600;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_601 = __VLS_asFunctionalComponent1(__VLS_600, new __VLS_600(__assign({ modelValue: (__VLS_ctx.editShiftForm.break_start), type: "time" }, { class: "w-full" })));
var __VLS_602 = __VLS_601.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.break_start), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_601), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_605;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_606 = __VLS_asFunctionalComponent1(__VLS_605, new __VLS_605(__assign({ modelValue: (__VLS_ctx.editShiftForm.break_end), type: "time" }, { class: "w-full" })));
var __VLS_607 = __VLS_606.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.break_end), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_606), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_610;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_611 = __VLS_asFunctionalComponent1(__VLS_610, new __VLS_610(__assign({ modelValue: (__VLS_ctx.editShiftForm.total_hours) }, { class: "w-full" })));
var __VLS_612 = __VLS_611.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.total_hours) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_611), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_615;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_616 = __VLS_asFunctionalComponent1(__VLS_615, new __VLS_615(__assign({ modelValue: (__VLS_ctx.editShiftForm.grace_period_minutes), min: (0), max: (60) }, { class: "w-full" })));
var __VLS_617 = __VLS_616.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.grace_period_minutes), min: (0), max: (60) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_616), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var _loop_1 = function (day) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (day.value) }, { class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.toggleEditShiftDay(day.value);
            // @ts-ignore
            [editShiftDialogVisible, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftErrors, editShiftErrors, editShiftErrors, editShiftErrors, editShiftErrors, editShiftErrors, weekDayOptions, toggleEditShiftDay,];
        } }, { class: "border rounded-lg p-2 text-center cursor-pointer transition-colors text-xs" }), { class: (__VLS_ctx.editShiftForm.week_days.includes(day.value) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50 border-gray-200') }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (day.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-gray-400 mt-0.5" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-0.5']} */ ;
    (day.full.substring(0, 3));
    // @ts-ignore
    [editShiftForm,];
};
for (var _1 = 0, _2 = __VLS_vFor((__VLS_ctx.weekDayOptions)); _1 < _2.length; _1++) {
    var day = _2[_1][0];
    _loop_1(day);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_620;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_621 = __VLS_asFunctionalComponent1(__VLS_620, new __VLS_620(__assign({ modelValue: (__VLS_ctx.editShiftForm.min_employees_required), min: (1) }, { class: "w-full" })));
var __VLS_622 = __VLS_621.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.min_employees_required), min: (1) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_621), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field flex items-center gap-3 pt-6" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
var __VLS_625;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_626 = __VLS_asFunctionalComponent1(__VLS_625, new __VLS_625({
    modelValue: (__VLS_ctx.editShiftForm.has_night_diff),
    inputId: "editNightDiff",
    binary: true,
}));
var __VLS_627 = __VLS_626.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.editShiftForm.has_night_diff),
        inputId: "editNightDiff",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_626), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "editNightDiff" }, { class: "text-sm" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
if (__VLS_ctx.editShiftForm.has_night_diff) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
    /** @type {__VLS_StyleScopedClasses['field']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_630 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_631 = __VLS_asFunctionalComponent1(__VLS_630, new __VLS_630(__assign({ modelValue: (__VLS_ctx.editShiftForm.night_diff_rate), min: (1), max: (3), step: (0.01), minFractionDigits: (2), maxFractionDigits: (2) }, { class: "w-full" })));
    var __VLS_632 = __VLS_631.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.night_diff_rate), min: (1), max: (3), step: (0.01), minFractionDigits: (2), maxFractionDigits: (2) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_631), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_635;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_636 = __VLS_asFunctionalComponent1(__VLS_635, new __VLS_635({
    modelValue: (__VLS_ctx.editShiftForm.is_active),
    inputId: "editIsActive",
    binary: true,
}));
var __VLS_637 = __VLS_636.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.editShiftForm.is_active),
        inputId: "editIsActive",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_636), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "editIsActive" }, { class: "text-sm font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_640;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_641 = __VLS_asFunctionalComponent1(__VLS_640, new __VLS_640(__assign({ modelValue: (__VLS_ctx.editShiftForm.description), rows: "2" }, { class: "w-full" })));
var __VLS_642 = __VLS_641.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.editShiftForm.description), rows: "2" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_641), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_645 = __VLS_567.slots.footer;
    var __VLS_646 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_647 = __VLS_asFunctionalComponent1(__VLS_646, new __VLS_646(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_648 = __VLS_647.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_647), false));
    var __VLS_651 = void 0;
    var __VLS_652 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editShiftDialogVisible = false;
                // @ts-ignore
                [editShiftDialogVisible, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm, editShiftForm,];
            } });
    var __VLS_649;
    var __VLS_650;
    var __VLS_653 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_654 = __VLS_asFunctionalComponent1(__VLS_653, new __VLS_653(__assign({ 'onClick': {} }, { label: "Save Changes", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.editShiftSaving) })));
    var __VLS_655 = __VLS_654.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Changes", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.editShiftSaving) })], __VLS_functionalComponentArgsRest(__VLS_654), false));
    var __VLS_658 = void 0;
    var __VLS_659 = ({ click: {} },
        { onClick: (__VLS_ctx.updateShift) });
    var __VLS_656;
    var __VLS_657;
    // @ts-ignore
    [editShiftSaving, updateShift,];
}
// @ts-ignore
[];
var __VLS_567;
var __VLS_660;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_661 = __VLS_asFunctionalComponent1(__VLS_660, new __VLS_660(__assign({ visible: (__VLS_ctx.deleteShiftDialogVisible), modal: true, header: "Delete Shift" }, { style: ({ width: '35vw' }) })));
var __VLS_662 = __VLS_661.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteShiftDialogVisible), modal: true, header: "Delete Shift" }, { style: ({ width: '35vw' }) })], __VLS_functionalComponentArgsRest(__VLS_661), false));
var __VLS_665 = __VLS_663.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-red-500 text-3xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
((_v = __VLS_ctx.selectedShiftForDelete) === null || _v === void 0 ? void 0 : _v.name);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_666 = __VLS_663.slots.footer;
    var __VLS_667 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_668 = __VLS_asFunctionalComponent1(__VLS_667, new __VLS_667(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_669 = __VLS_668.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_668), false));
    var __VLS_672 = void 0;
    var __VLS_673 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteShiftDialogVisible = false;
                // @ts-ignore
                [deleteShiftDialogVisible, deleteShiftDialogVisible, selectedShiftForDelete,];
            } });
    var __VLS_670;
    var __VLS_671;
    var __VLS_674 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_675 = __VLS_asFunctionalComponent1(__VLS_674, new __VLS_674(__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.deletingShift) })));
    var __VLS_676 = __VLS_675.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.deletingShift) })], __VLS_functionalComponentArgsRest(__VLS_675), false));
    var __VLS_679 = void 0;
    var __VLS_680 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteShift) });
    var __VLS_677;
    var __VLS_678;
    // @ts-ignore
    [deletingShift, deleteShift,];
}
// @ts-ignore
[];
var __VLS_663;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
