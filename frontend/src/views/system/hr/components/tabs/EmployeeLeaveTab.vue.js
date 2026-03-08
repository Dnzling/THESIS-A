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
var emit = defineEmits(['update:leave', 'view-details', 'edit', 'export']);
var authStore = (0, auth_1.useAuthStore)();
var toast = (0, usetoast_1.useToast)();
// State
var loading = (0, vue_1.ref)(false);
var leaveHistory = (0, vue_1.ref)([]);
var employeeInfo = (0, vue_1.ref)({
    id: null,
    name: '',
    employee_number: null,
    department: null,
    position: null
});
var statistics = (0, vue_1.ref)({
    total_leaves: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
    cancelled: 0,
    total_days: 0,
    sick_leaves: 0,
    vacation_leaves: 0,
    personal_leaves: 0
});
var leaveBalances = (0, vue_1.ref)({});
var upcomingLeaves = (0, vue_1.ref)([]);
// Filters
var selectedYear = (0, vue_1.ref)(new Date().getFullYear());
var filters = (0, vue_1.ref)({
    search: '',
    status: null,
    type: null
});
// Dialog states
var showDetailsDialog = (0, vue_1.ref)(false);
var showCancelDialog = (0, vue_1.ref)(false);
var selectedLeave = (0, vue_1.ref)(null);
var leaveToCancel = (0, vue_1.ref)(null);
// Options
var yearOptions = (0, vue_1.computed)(function () {
    var currentYear = new Date().getFullYear();
    return [currentYear - 1, currentYear, currentYear + 1].map(function (year) { return ({
        label: year.toString(),
        value: year
    }); });
});
var statusOptions = (0, vue_1.ref)([
    { label: 'Approved', value: 'approved' },
    { label: 'Pending', value: 'pending' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Cancelled', value: 'cancelled' }
]);
var typeOptions = (0, vue_1.ref)([
    { label: 'Vacation', value: 'vacation' },
    { label: 'Sick', value: 'sick' },
    { label: 'Personal', value: 'personal' },
    { label: 'Maternity', value: 'maternity' },
    { label: 'Paternity', value: 'paternity' },
    { label: 'Bereavement', value: 'bereavement' },
    { label: 'Others', value: 'others' }
]);
// Computed
var formattedBalances = (0, vue_1.computed)(function () {
    var formatted = {};
    Object.keys(leaveBalances.value).forEach(function (type) {
        var balance = leaveBalances.value[type];
        formatted[type] = {
            used: balance.used_days || 0,
            quota: balance.yearly_quota || 0,
            remaining: balance.remaining_days || 0,
            pending: balance.pending_days || 0,
            carried_over: balance.carried_over || 0
        };
    });
    return formatted;
});
var filteredLeaveHistory = (0, vue_1.computed)(function () {
    var filtered = __spreadArray([], leaveHistory.value, true);
    if (filters.value.search) {
        var searchTerm_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (item) {
            var _a, _b;
            return ((_a = item.reason) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(searchTerm_1)) ||
                ((_b = item.leave_type) === null || _b === void 0 ? void 0 : _b.toLowerCase().includes(searchTerm_1));
        });
    }
    if (filters.value.status) {
        filtered = filtered.filter(function (item) { return item.status === filters.value.status; });
    }
    if (filters.value.type) {
        filtered = filtered.filter(function (item) { return item.leave_type === filters.value.type; });
    }
    return filtered;
});
// Helper Functions
var formatDate = function (date) {
    if (!date)
        return '—';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
var formatTime = function (date) {
    if (!date)
        return '—';
    return new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
var getLeaveTypeSeverity = function (type) {
    var map = {
        'vacation': 'info',
        'sick': 'success',
        'personal': 'warning',
        'maternity': 'warning',
        'paternity': 'warning',
        'bereavement': 'secondary',
        'others': 'secondary'
    };
    return map[type === null || type === void 0 ? void 0 : type.toLowerCase()] || 'secondary';
};
var getLeaveStatusSeverity = function (status) {
    var map = {
        'approved': 'success',
        'pending': 'warning',
        'rejected': 'danger',
        'cancelled': 'secondary'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'info';
};
var canCancelLeave = function (leave) {
    return leave && (leave.status === 'pending' || leave.status === 'approved');
};
// Actions
var resetFilters = function () {
    filters.value = {
        search: '',
        status: null,
        type: null
    };
};
var applyFilters = function () {
    // Filters are applied automatically via computed property
    // This method can be used if you need to trigger any side effects
};
var viewLeaveDetails = function (leave) {
    selectedLeave.value = leave;
    showDetailsDialog.value = true;
    emit('view-details', leave);
};
var showNewLeaveDialog = function () {
    emit('edit', { employeeId: props.employeeId, action: 'new' });
};
var cancelLeave = function (leave) {
    leaveToCancel.value = leave;
    showCancelDialog.value = true;
};
var confirmCancelLeave = function () {
    showDetailsDialog.value = false;
    leaveToCancel.value = selectedLeave.value;
    showCancelDialog.value = true;
};
var executeCancelLeave = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!leaveToCancel.value)
                    return [2 /*return*/];
                _c.label = 1;
            case 1:
                _c.trys.push([1, 6, , 7]);
                return [4 /*yield*/, axios_1.default.put("/api/leaves/".concat(leaveToCancel.value.id, "/cancel"), {}, {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 2:
                response = _c.sent();
                if (!response.data.success) return [3 /*break*/, 5];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Leave request cancelled successfully',
                    life: 3000
                });
                // Refresh data
                return [4 /*yield*/, fetchLeaveData()];
            case 3:
                // Refresh data
                _c.sent();
                return [4 /*yield*/, fetchUpcomingLeaves()];
            case 4:
                _c.sent();
                showCancelDialog.value = false;
                showDetailsDialog.value = false;
                leaveToCancel.value = null;
                _c.label = 5;
            case 5: return [3 /*break*/, 7];
            case 6:
                err_1 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to cancel leave request',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 7: return [2 /*return*/];
        }
    });
}); };
var exportLeaves = function () {
    var params = new URLSearchParams();
    params.append('employee_id', props.employeeId.toString());
    params.append('year', selectedYear.value.toString());
    if (filters.value.status) {
        params.append('status', filters.value.status);
    }
    if (filters.value.type) {
        params.append('leave_type', filters.value.type);
    }
    var url = "/api/leaves/export?".concat(params.toString());
    window.open(url, '_blank');
    emit('export', {
        employeeId: props.employeeId,
        year: selectedYear.value,
        filters: filters.value
    });
};
// API Functions
var fetchLeaveData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, data, err_2;
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
                return [4 /*yield*/, axios_1.default.get("/api/users/".concat(props.employeeId, "/leaves"), {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        },
                        params: {
                            year: selectedYear.value
                        }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    data = response.data.data;
                    // Update all data from the response
                    employeeInfo.value = data.employee || employeeInfo.value;
                    statistics.value = data.statistics || statistics.value;
                    leaveBalances.value = data.balances || {};
                    // The leaves are paginated, so we need to extract the data array
                    if (data.leaves && data.leaves.data) {
                        leaveHistory.value = data.leaves.data;
                    }
                    else {
                        leaveHistory.value = [];
                    }
                    emit('update:leave', data);
                }
                return [3 /*break*/, 5];
            case 3:
                err_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = err_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch leave data',
                    life: 3000
                });
                console.error('Leave fetch error:', err_2);
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var fetchUpcomingLeaves = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!props.employeeId)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                return [4 /*yield*/, axios_1.default.get("/api/users/".concat(props.employeeId, "/leaves/upcoming"), {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        },
                        params: {
                            limit: 5
                        }
                    })];
            case 2:
                response = _a.sent();
                if (response.data.success) {
                    upcomingLeaves.value = response.data.data || [];
                }
                return [3 /*break*/, 4];
            case 3:
                err_3 = _a.sent();
                console.error('Failed to fetch upcoming leaves:', err_3);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
// Expose methods for parent component
var __VLS_exposed = {
    refresh: fetchLeaveData,
    filters: filters,
    selectedYear: selectedYear
};
defineExpose(__VLS_exposed);
// Watchers
(0, vue_1.watch)(function () { return props.employeeId; }, function () {
    if (props.employeeId) {
        fetchLeaveData();
        fetchUpcomingLeaves();
    }
});
(0, vue_1.watch)(selectedYear, function () {
    fetchLeaveData();
});
// Lifecycle
(0, vue_1.onMounted)(function () {
    if (props.initialData) {
        // Handle initial data if provided
        if (props.initialData.leaves) {
            leaveHistory.value = props.initialData.leaves.data || [];
        }
        if (props.initialData.statistics) {
            statistics.value = props.initialData.statistics;
        }
        if (props.initialData.balances) {
            leaveBalances.value = props.initialData.balances;
        }
        if (props.initialData.employee) {
            employeeInfo.value = props.initialData.employee;
        }
    }
    else {
        fetchLeaveData();
        fetchUpcomingLeaves();
    }
});
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.formattedBalances)); _i < _a.length; _i++) {
    var _b = _a[_i], balance = _b[0], type = _b[1];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (type) }, { class: "border border-gray-100 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 capitalize" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    (type);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-end gap-1 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xl font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (balance.used);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (balance.quota);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-green-600" }));
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    (balance.remaining);
    if (balance.pending > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-orange-600 ml-2" }));
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
        (balance.pending);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full bg-gray-100 rounded-full h-1.5 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-500 h-1.5 rounded-full" }, { style: ({ width: Math.min((balance.used / balance.quota) * 100, 100) + '%' }) }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-1.5']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    // @ts-ignore
    [formattedBalances,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-5 gap-3 bg-gray-50 p-3 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-5']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
(__VLS_ctx.statistics.total_leaves || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-green-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
(__VLS_ctx.statistics.approved || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-orange-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
(__VLS_ctx.statistics.pending || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-red-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
(__VLS_ctx.statistics.rejected || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.statistics.total_days || 0);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedYear), options: (__VLS_ctx.yearOptions), placeholder: "Select Year", size: "small" }), { style: {} })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.selectedYear), options: (__VLS_ctx.yearOptions), placeholder: "Select Year", size: "small" }), { style: {} })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchLeaveData) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
var __VLS_13;
/** @ts-ignore @type {typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13(__assign({ class: "pi pi-search" })));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_14), false));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18({
    modelValue: (__VLS_ctx.filters.search),
    placeholder: "Search reason...",
    size: "small",
}));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.search),
        placeholder: "Search reason...",
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_19), false));
// @ts-ignore
[statistics, statistics, statistics, statistics, statistics, selectedYear, yearOptions, fetchLeaveData, filters,];
var __VLS_10;
var __VLS_23;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "Status", size: "small", showClear: true })));
var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "Status", size: "small", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_24), false));
var __VLS_28;
var __VLS_29 = ({ change: {} },
    { onChange: (__VLS_ctx.applyFilters) });
var __VLS_26;
var __VLS_27;
var __VLS_30;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeOptions), placeholder: "Leave Type", size: "small", showClear: true })));
var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeOptions), placeholder: "Leave Type", size: "small", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_31), false));
var __VLS_35;
var __VLS_36 = ({ change: {} },
    { onChange: (__VLS_ctx.applyFilters) });
var __VLS_33;
var __VLS_34;
var __VLS_37;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "secondary", text: true, rounded: true, size: "small" })));
var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-refresh", severity: "secondary", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_38), false));
var __VLS_42;
var __VLS_43 = ({ click: {} },
    { onClick: (__VLS_ctx.resetFilters) });
__VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Reset Filters') }), null, null);
var __VLS_40;
var __VLS_41;
if (__VLS_ctx.upcomingLeaves.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-blue-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 overflow-x-auto pb-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-x-auto']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-1']} */ ;
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.upcomingLeaves)); _c < _d.length; _c++) {
        var leave = _d[_c][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (leave.id) }, { class: "bg-white rounded-lg p-2 shadow-sm min-w-50" }));
        /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['min-w-50']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        var __VLS_44 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44({
            value: (leave.leave_type_label),
            severity: (__VLS_ctx.getLeaveTypeSeverity(leave.leave_type)),
            size: "small",
        }));
        var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([{
                value: (leave.leave_type_label),
                severity: (__VLS_ctx.getLeaveTypeSeverity(leave.leave_type)),
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_45), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (leave.days_until);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (leave.start_date_formatted);
        (leave.end_date_formatted);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (leave.total_days);
        // @ts-ignore
        [filters, filters, statusOptions, applyFilters, applyFilters, typeOptions, resetFilters, vTooltip, upcomingLeaves, upcomingLeaves, getLeaveTypeSeverity,];
    }
}
if (__VLS_ctx.employeeInfo.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 border border-gray-200 rounded-lg p-2 flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-user']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.employeeInfo.name);
    if (__VLS_ctx.employeeInfo.department) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.employeeInfo.department);
    }
    if (__VLS_ctx.employeeInfo.position) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.employeeInfo.position);
    }
}
var __VLS_49;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign({ value: (__VLS_ctx.filteredLeaveHistory), paginator: (true), rows: (10), rowsPerPageOptions: ([10, 20, 50]) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), stripedRows: true, showGridlines: true, sortField: "created_at", sortOrder: (-1) })));
var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.filteredLeaveHistory), paginator: (true), rows: (10), rowsPerPageOptions: ([10, 20, 50]) }, { class: "p-datatable-sm" }), { loading: (__VLS_ctx.loading), stripedRows: true, showGridlines: true, sortField: "created_at", sortOrder: (-1) })], __VLS_functionalComponentArgsRest(__VLS_50), false));
/** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
var __VLS_54 = __VLS_52.slots.default;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign({ field: "created_at_formatted", header: "Date Filed", sortable: (true) }, { style: {} })));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign({ field: "created_at_formatted", header: "Date Filed", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_56), false));
var __VLS_60 = __VLS_58.slots.default;
{
    var __VLS_61 = __VLS_58.slots.body;
    var data = __VLS_vSlot(__VLS_61)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.created_at_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.formatTime(data.created_at));
    // @ts-ignore
    [employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, filteredLeaveHistory, loading, formatTime,];
}
// @ts-ignore
[];
var __VLS_58;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62(__assign({ field: "leave_type_label", header: "Leave Type", sortable: (true) }, { style: {} })));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([__assign({ field: "leave_type_label", header: "Leave Type", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67 = __VLS_65.slots.default;
{
    var __VLS_68 = __VLS_65.slots.body;
    var data = __VLS_vSlot(__VLS_68)[0].data;
    var __VLS_69 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69({
        value: (data.leave_type_label),
        severity: (__VLS_ctx.getLeaveTypeSeverity(data.leave_type)),
        rounded: true,
    }));
    var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([{
            value: (data.leave_type_label),
            severity: (__VLS_ctx.getLeaveTypeSeverity(data.leave_type)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_70), false));
    if (data.is_paid) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-green-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    }
    // @ts-ignore
    [getLeaveTypeSeverity,];
}
// @ts-ignore
[];
var __VLS_65;
var __VLS_74;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74(__assign({ header: "Date Range" }, { style: {} })));
var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([__assign({ header: "Date Range" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_75), false));
var __VLS_79 = __VLS_77.slots.default;
{
    var __VLS_80 = __VLS_77.slots.body;
    var data = __VLS_vSlot(__VLS_80)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.start_date_formatted);
    (data.end_date_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (data.duration);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_77;
var __VLS_81;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign({ field: "total_days", header: "Days", sortable: (true) }, { style: {} })));
var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign({ field: "total_days", header: "Days", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_82), false));
var __VLS_86 = __VLS_84.slots.default;
{
    var __VLS_87 = __VLS_84.slots.body;
    var data = __VLS_vSlot(__VLS_87)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.total_days);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_84;
var __VLS_88;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ field: "reason", header: "Reason" }, { style: {} })));
var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ field: "reason", header: "Reason" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_89), false));
var __VLS_93 = __VLS_91.slots.default;
{
    var __VLS_94 = __VLS_91.slots.body;
    var data = __VLS_vSlot(__VLS_94)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "truncate block" }, { title: (data.reason) }));
    /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    (data.reason || '—');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_91;
var __VLS_95;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign({ field: "status_label", header: "Status", sortable: (true) }, { style: {} })));
var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign({ field: "status_label", header: "Status", sortable: (true) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_96), false));
var __VLS_100 = __VLS_98.slots.default;
{
    var __VLS_101 = __VLS_98.slots.body;
    var data = __VLS_vSlot(__VLS_101)[0].data;
    var __VLS_102 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({
        value: (data.status_label),
        severity: (__VLS_ctx.getLeaveStatusSeverity(data.status)),
        rounded: true,
    }));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{
            value: (data.status_label),
            severity: (__VLS_ctx.getLeaveStatusSeverity(data.status)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_103), false));
    if (data.approved_by) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (data.approved_by.name);
    }
    // @ts-ignore
    [getLeaveStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_98;
var __VLS_107;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107(__assign({ field: "handover_to", header: "Handover" }, { style: {} })));
var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([__assign({ field: "handover_to", header: "Handover" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_108), false));
var __VLS_112 = __VLS_110.slots.default;
{
    var __VLS_113 = __VLS_110.slots.body;
    var data = __VLS_vSlot(__VLS_113)[0].data;
    if (data.handover_to) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (data.handover_to.name);
        if (data.handover_notes) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
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
var __VLS_110;
var __VLS_114;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ header: "Actions" }, { style: {} })));
var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_115), false));
var __VLS_119 = __VLS_117.slots.default;
{
    var __VLS_120 = __VLS_117.slots.body;
    var data_1 = __VLS_vSlot(__VLS_120)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_121 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, size: "small" })));
    var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_122), false));
    var __VLS_126 = void 0;
    var __VLS_127 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewLeaveDetails(data_1);
                // @ts-ignore
                [viewLeaveDetails,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View Details') }), null, null);
    var __VLS_124;
    var __VLS_125;
    if (__VLS_ctx.canCancelLeave(data_1)) {
        var __VLS_128 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128(__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, size: "small", severity: "danger" })));
        var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_129), false));
        var __VLS_133 = void 0;
        var __VLS_134 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canCancelLeave(data_1)))
                        return;
                    __VLS_ctx.cancelLeave(data_1);
                    // @ts-ignore
                    [vTooltip, canCancelLeave, cancelLeave,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Cancel Request') }), null, null);
        var __VLS_131;
        var __VLS_132;
    }
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_117;
// @ts-ignore
[];
var __VLS_52;
if (!__VLS_ctx.loading && __VLS_ctx.filteredLeaveHistory.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar-times text-4xl mb-2 block text-gray-300" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar-times']} */ ;
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
    for (var _e = 0, _f = __VLS_vFor((5)); _e < _f.length; _e++) {
        var i = _f[_e][0];
        var __VLS_135 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton | typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135({
            key: (i),
            height: "3rem",
        }));
        var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([{
                key: (i),
                height: "3rem",
            }], __VLS_functionalComponentArgsRest(__VLS_136), false));
        // @ts-ignore
        [filteredLeaveHistory, loading, loading,];
    }
}
var __VLS_140;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign(__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: ('Leave Request Details') }, { style: ({ width: '500px' }) }), { modal: (true) }), { class: "leave-details-dialog" })));
var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: ('Leave Request Details') }, { style: ({ width: '500px' }) }), { modal: (true) }), { class: "leave-details-dialog" })], __VLS_functionalComponentArgsRest(__VLS_141), false));
/** @type {__VLS_StyleScopedClasses['leave-details-dialog']} */ ;
var __VLS_145 = __VLS_143.slots.default;
if (__VLS_ctx.selectedLeave) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center p-3 rounded-lg" }, { class: ({
            'bg-green-50': __VLS_ctx.selectedLeave.status === 'approved',
            'bg-orange-50': __VLS_ctx.selectedLeave.status === 'pending',
            'bg-red-50': __VLS_ctx.selectedLeave.status === 'rejected',
            'bg-gray-50': __VLS_ctx.selectedLeave.status === 'cancelled'
        }) }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    var __VLS_146 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ value: (__VLS_ctx.selectedLeave.status_label), severity: (__VLS_ctx.getLeaveStatusSeverity(__VLS_ctx.selectedLeave.status)), rounded: true }, { class: "mt-1" })));
    var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.selectedLeave.status_label), severity: (__VLS_ctx.getLeaveStatusSeverity(__VLS_ctx.selectedLeave.status)), rounded: true }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_147), false));
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
    /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedLeave.leave_type_label);
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
    (__VLS_ctx.selectedLeave.created_at_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.formatTime(__VLS_ctx.selectedLeave.created_at));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedLeave.total_days);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedLeave.duration);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 text-center p-2 bg-white rounded border" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedLeave.start_date_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-arrow-right text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-arrow-right']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 text-center p-2 bg-white rounded border" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedLeave.end_date_formatted);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm bg-white p-2 rounded border" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    (__VLS_ctx.selectedLeave.reason || '—');
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    if (__VLS_ctx.selectedLeave.is_paid) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-green-600 font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-2 rounded" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    if (__VLS_ctx.selectedLeave.deduct_from_balance) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    }
    if (__VLS_ctx.selectedLeave.handover_to) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedLeave.handover_to.name);
        if (__VLS_ctx.selectedLeave.handover_to.position) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            (__VLS_ctx.selectedLeave.handover_to.position);
        }
        if (__VLS_ctx.selectedLeave.handover_notes) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-2 text-sm bg-white p-2 rounded border" }));
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            (JSON.stringify(__VLS_ctx.selectedLeave.handover_notes));
        }
    }
    if (__VLS_ctx.selectedLeave.approved_by) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedLeave.approved_by.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
        /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedLeave.approved_at_formatted);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    }
    if (__VLS_ctx.selectedLeave.rejected_reason) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-50 p-3 rounded" }));
        /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-red-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.selectedLeave.rejected_reason);
    }
    if (__VLS_ctx.selectedLeave.attachment_path) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: (__VLS_ctx.selectedLeave.attachment_path), target: "_blank" }, { class: "text-blue-600 flex items-center gap-1" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-pdf" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-file-pdf']} */ ;
    }
}
{
    var __VLS_151 = __VLS_143.slots.footer;
    var __VLS_152 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152(__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", text: true })));
    var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_153), false));
    var __VLS_157 = void 0;
    var __VLS_158 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsDialog = false;
                // @ts-ignore
                [formatTime, getLeaveStatusSeverity, showDetailsDialog, showDetailsDialog, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave, selectedLeave,];
            } });
    var __VLS_155;
    var __VLS_156;
    if (__VLS_ctx.selectedLeave && __VLS_ctx.canCancelLeave(__VLS_ctx.selectedLeave)) {
        var __VLS_159 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ 'onClick': {} }, { label: "Cancel Request", icon: "pi pi-times", severity: "danger" })));
        var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel Request", icon: "pi pi-times", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_160), false));
        var __VLS_164 = void 0;
        var __VLS_165 = ({ click: {} },
            { onClick: (__VLS_ctx.confirmCancelLeave) });
        var __VLS_162;
        var __VLS_163;
    }
    // @ts-ignore
    [canCancelLeave, selectedLeave, selectedLeave, confirmCancelLeave,];
}
// @ts-ignore
[];
var __VLS_143;
var __VLS_166;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_167 = __VLS_asFunctionalComponent1(__VLS_166, new __VLS_166(__assign(__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Confirm Cancellation" }, { style: ({ width: '400px' }) }), { modal: (true) })));
var __VLS_168 = __VLS_167.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Confirm Cancellation" }, { style: ({ width: '400px' }) }), { modal: (true) })], __VLS_functionalComponentArgsRest(__VLS_167), false));
var __VLS_171 = __VLS_169.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
{
    var __VLS_172 = __VLS_169.slots.footer;
    var __VLS_173 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_174 = __VLS_asFunctionalComponent1(__VLS_173, new __VLS_173(__assign({ 'onClick': {} }, { label: "No, Keep", icon: "pi pi-times", text: true })));
    var __VLS_175 = __VLS_174.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "No, Keep", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_174), false));
    var __VLS_178 = void 0;
    var __VLS_179 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showCancelDialog = false;
                // @ts-ignore
                [showCancelDialog, showCancelDialog,];
            } });
    var __VLS_176;
    var __VLS_177;
    var __VLS_180 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180(__assign({ 'onClick': {} }, { label: "Yes, Cancel", icon: "pi pi-check", severity: "danger" })));
    var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Yes, Cancel", icon: "pi pi-check", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_181), false));
    var __VLS_185 = void 0;
    var __VLS_186 = ({ click: {} },
        { onClick: (__VLS_ctx.executeCancelLeave) });
    var __VLS_183;
    var __VLS_184;
    // @ts-ignore
    [executeCancelLeave,];
}
// @ts-ignore
[];
var __VLS_169;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    setup: function () { return (__VLS_exposed); },
    emits: {},
    __typeProps: {},
});
exports.default = {};
