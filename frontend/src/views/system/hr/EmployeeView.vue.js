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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var axios_1 = require("axios");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../stores/auth");
// Import tab components
var EmployeeInfoTab_vue_1 = require("./components/tabs/EmployeeInfoTab.vue");
var EmployeeAttendanceTab_vue_1 = require("./components/tabs/EmployeeAttendanceTab.vue");
var EmployeeLeaveTab_vue_1 = require("./components/tabs/EmployeeLeaveTab.vue");
var EmployeePayrollTab_vue_1 = require("./components/tabs/EmployeePayrollTab.vue");
var authStore = (0, auth_1.useAuthStore)();
var router = (0, vue_router_1.useRouter)();
var route = (0, vue_router_1.useRoute)();
var toast = (0, usetoast_1.useToast)();
var employeeId = route.params.id;
// Refs for child components
var attendanceTabRef = (0, vue_1.ref)(null);
var leaveTabRef = (0, vue_1.ref)(null);
var payslipHistoryRef = (0, vue_1.ref)(null);
// Loading states
var loading = (0, vue_1.ref)(false);
var error = (0, vue_1.ref)('');
// State
var activeTab = (0, vue_1.ref)('info');
var employeeInfo = (0, vue_1.ref)({
    basic_info: {},
    employment_details: {},
    contact_info: {},
    leave_info: {},
    attendance: {},
    payroll: {},
    deductions: {},
    quick_stats: {}
});
// Computed Properties
var leaveBalance = (0, vue_1.computed)(function () {
    var _a, _b;
    return ((_b = (_a = employeeInfo.value.leave_info) === null || _a === void 0 ? void 0 : _a.summary) === null || _b === void 0 ? void 0 : _b.total_remaining) || 0;
});
var attendanceRate = (0, vue_1.computed)(function () {
    var _a;
    return ((_a = employeeInfo.value.quick_stats) === null || _a === void 0 ? void 0 : _a.attendance_rate) || 0;
});
// API Functions
var fetchEmployeeData = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                error.value = '';
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get("api/employees/".concat(employeeId, "/details"), {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    employeeInfo.value = response.data.data;
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _c.sent();
                error.value = ((_b = (_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch employee data';
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.value,
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
// Helper functions
var getInitials = function (name) {
    if (!name)
        return '';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var formatDate = function (date) {
    if (!date)
        return '—';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
var formatNumber = function (num) {
    return (num === null || num === void 0 ? void 0 : num.toLocaleString()) || '0';
};
var getStatusSeverity = function (status) {
    var map = {
        'active': 'success',
        'on_leave': 'info',
        'suspended': 'warning',
        'terminated': 'danger'
    };
    return map[status === null || status === void 0 ? void 0 : status.toLowerCase()] || 'info';
};
// Navigation
var goBack = function () {
    router.push('/hr/employees');
};
var editEmployee = function () {
    router.push("/hr/employees/".concat(employeeId, "/edit"));
};
var exportData = function () {
    window.open("api/employees/".concat(employeeId, "/export"), '_blank');
};
// Event Handlers
var handleAttendanceUpdate = function (data) {
    employeeInfo.value.attendance = data;
};
var handleAttendanceExport = function (params) {
    toast.add({
        severity: 'info',
        summary: 'Exporting',
        detail: "Exporting attendance for ".concat(params.month, "/").concat(params.year),
        life: 2000
    });
};
var handleLeaveUpdate = function (data) {
    employeeInfo.value.leave_info = data;
};
var handleViewLeaveDetails = function (leave) {
    console.log('Viewing leave details:', leave);
};
var handleViewPayslip = function (payslip) {
    console.log('Viewing payslip:', payslip);
};
var handleDownloadPayslip = function (payslip) {
    var _a;
    toast.add({
        severity: 'info',
        summary: 'Downloading',
        detail: "Downloading payslip for ".concat((_a = payslip.pay_period) === null || _a === void 0 ? void 0 : _a.name),
        life: 2000
    });
};
var handlePrintPayslip = function (payslip) {
    console.log('Printing payslip:', payslip);
};
var handleGeneratePayslip = function (year, month) {
    toast.add({
        severity: 'info',
        summary: 'Generating',
        detail: "Generating payslip for ".concat(month, "/").concat(year),
        life: 2000
    });
    setTimeout(function () {
        var _a;
        (_a = payslipHistoryRef.value) === null || _a === void 0 ? void 0 : _a.refresh();
    }, 1000);
};
var handleExportAll = function (year, month) {
    console.log('Exporting all payslips for:', year, month);
};
// Watchers
(0, vue_1.watch)(activeTab, function (newTab) {
    // Refresh data when switching to specific tabs
    if (newTab === 'attendance' && attendanceTabRef.value) {
        attendanceTabRef.value.refresh();
    }
    else if (newTab === 'leave' && leaveTabRef.value) {
        leaveTabRef.value.refresh();
    }
    else if (newTab === 'payslip' && payslipHistoryRef.value) {
        payslipHistoryRef.value.refresh();
    }
});
// Lifecycle
(0, vue_1.onMounted)(function () {
    fetchEmployeeData();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 max-w-7xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center items-center h-96" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-96']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.ProgressSpinner} */
    ProgressSpinner;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
}
else if (__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-50 border border-red-200 rounded-lg p-8 text-center" }));
    /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-red-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-red-500 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-medium text-red-800 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-red-600 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    (__VLS_ctx.error);
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ 'onClick': {} }, { label: "Try Again", icon: "pi pi-refresh", severity: "danger" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Try Again", icon: "pi pi-refresh", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    var __VLS_10 = void 0;
    var __VLS_11 = ({ click: {} },
        { onClick: (__VLS_ctx.fetchEmployeeData) });
    var __VLS_8;
    var __VLS_9;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true, severity: "info" })));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
    var __VLS_17 = void 0;
    var __VLS_18 = ({ click: {} },
        { onClick: (__VLS_ctx.goBack) });
    var __VLS_15;
    var __VLS_16;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_19 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info", outlined: true })));
    var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_20), false));
    var __VLS_24 = void 0;
    var __VLS_25 = ({ click: {} },
        { onClick: (__VLS_ctx.editEmployee) });
    var __VLS_22;
    var __VLS_23;
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "secondary", outlined: true })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export", icon: "pi pi-download", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    var __VLS_31 = void 0;
    var __VLS_32 = ({ click: {} },
        { onClick: (__VLS_ctx.exportData) });
    var __VLS_29;
    var __VLS_30;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "relative" }));
    /** @type {__VLS_StyleScopedClasses['relative']} */ ;
    var __VLS_33 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ label: (__VLS_ctx.getInitials((_a = __VLS_ctx.employeeInfo.basic_info) === null || _a === void 0 ? void 0 : _a.name)), size: "xlarge" }, { class: "bg-blue-100 text-blue-600 text-2xl font-medium" })));
    var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials((_b = __VLS_ctx.employeeInfo.basic_info) === null || _b === void 0 ? void 0 : _b.name)), size: "xlarge" }, { class: "bg-blue-100 text-blue-600 text-2xl font-medium" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white" }));
    /** @type {__VLS_StyleScopedClasses['absolute']} */ ;
    /** @type {__VLS_StyleScopedClasses['-bottom-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['-right-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-white']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    ((_c = __VLS_ctx.employeeInfo.basic_info) === null || _c === void 0 ? void 0 : _c.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    var __VLS_38 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38({
        value: (((_d = __VLS_ctx.employeeInfo.employment_details) === null || _d === void 0 ? void 0 : _d.status) || 'Active'),
        severity: (__VLS_ctx.getStatusSeverity((_e = __VLS_ctx.employeeInfo.employment_details) === null || _e === void 0 ? void 0 : _e.status)),
        rounded: true,
    }));
    var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([{
            value: (((_f = __VLS_ctx.employeeInfo.employment_details) === null || _f === void 0 ? void 0 : _f.status) || 'Active'),
            severity: (__VLS_ctx.getStatusSeverity((_g = __VLS_ctx.employeeInfo.employment_details) === null || _g === void 0 ? void 0 : _g.status)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_39), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (((_h = __VLS_ctx.employeeInfo.employment_details) === null || _h === void 0 ? void 0 : _h.role) || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    ((_j = __VLS_ctx.employeeInfo.basic_info) === null || _j === void 0 ? void 0 : _j.employee_number);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-4 gap-4 mt-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-r border-gray-100 pr-4" }));
    /** @type {__VLS_StyleScopedClasses['border-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['pr-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (((_k = __VLS_ctx.employeeInfo.employment_details) === null || _k === void 0 ? void 0 : _k.department) || '—');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-r border-gray-100 pr-4" }));
    /** @type {__VLS_StyleScopedClasses['border-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['pr-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.formatDate((_l = __VLS_ctx.employeeInfo.employment_details) === null || _l === void 0 ? void 0 : _l.hire_date));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-r border-gray-100 pr-4" }));
    /** @type {__VLS_StyleScopedClasses['border-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['pr-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.leaveBalance);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.attendanceRate);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
    var __VLS_43 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tabs | typeof __VLS_components.Tabs} */
    Tabs;
    // @ts-ignore
    var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43({
        value: (__VLS_ctx.activeTab),
    }));
    var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.activeTab),
        }], __VLS_functionalComponentArgsRest(__VLS_44), false));
    var __VLS_48 = __VLS_46.slots.default;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabList | typeof __VLS_components.TabList} */
    TabList;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ class: "px-6 pt-2" })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ class: "px-6 pt-2" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    /** @type {__VLS_StyleScopedClasses['px-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
    var __VLS_54 = __VLS_52.slots.default;
    var __VLS_55 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
        value: "info",
    }));
    var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
            value: "info",
        }], __VLS_functionalComponentArgsRest(__VLS_56), false));
    var __VLS_60 = __VLS_58.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [loading, error, error, fetchEmployeeData, goBack, editEmployee, exportData, getInitials, employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, employeeInfo, getStatusSeverity, formatDate, leaveBalance, attendanceRate, activeTab,];
    var __VLS_58;
    var __VLS_61 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61({
        value: "attendance",
    }));
    var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([{
            value: "attendance",
        }], __VLS_functionalComponentArgsRest(__VLS_62), false));
    var __VLS_66 = __VLS_64.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [];
    var __VLS_64;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67({
        value: "leave",
    }));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([{
            value: "leave",
        }], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = __VLS_70.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [];
    var __VLS_70;
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
    Tab;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73({
        value: "payslip",
    }));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([{
            value: "payslip",
        }], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = __VLS_76.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [];
    var __VLS_76;
    // @ts-ignore
    [];
    var __VLS_52;
    var __VLS_79 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanels | typeof __VLS_components.TabPanels} */
    TabPanels;
    // @ts-ignore
    var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ class: "p-6" })));
    var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ class: "p-6" })], __VLS_functionalComponentArgsRest(__VLS_80), false));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    var __VLS_84 = __VLS_82.slots.default;
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85({
        value: "info",
    }));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([{
            value: "info",
        }], __VLS_functionalComponentArgsRest(__VLS_86), false));
    var __VLS_90 = __VLS_88.slots.default;
    var __VLS_91 = EmployeeInfoTab_vue_1.default;
    // @ts-ignore
    var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91({
        employeeInfo: (__VLS_ctx.employeeInfo),
    }));
    var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([{
            employeeInfo: (__VLS_ctx.employeeInfo),
        }], __VLS_functionalComponentArgsRest(__VLS_92), false));
    // @ts-ignore
    [employeeInfo,];
    var __VLS_88;
    var __VLS_96 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96({
        value: "attendance",
    }));
    var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([{
            value: "attendance",
        }], __VLS_functionalComponentArgsRest(__VLS_97), false));
    var __VLS_101 = __VLS_99.slots.default;
    var __VLS_102 = EmployeeAttendanceTab_vue_1.default;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102(__assign(__assign({ 'onUpdate:attendance': {} }, { 'onExport': {} }), { employeeId: (__VLS_ctx.employeeId), initialData: (__VLS_ctx.employeeInfo.attendance), ref: "attendanceTabRef" })));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([__assign(__assign({ 'onUpdate:attendance': {} }, { 'onExport': {} }), { employeeId: (__VLS_ctx.employeeId), initialData: (__VLS_ctx.employeeInfo.attendance), ref: "attendanceTabRef" })], __VLS_functionalComponentArgsRest(__VLS_103), false));
    var __VLS_107 = void 0;
    var __VLS_108 = ({ 'update:attendance': {} },
        { 'onUpdate:attendance': (__VLS_ctx.handleAttendanceUpdate) });
    var __VLS_109 = ({ export: {} },
        { onExport: (__VLS_ctx.handleAttendanceExport) });
    var __VLS_110 = {};
    var __VLS_105;
    var __VLS_106;
    // @ts-ignore
    [employeeInfo, employeeId, handleAttendanceUpdate, handleAttendanceExport,];
    var __VLS_99;
    var __VLS_112 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_113 = __VLS_asFunctionalComponent1(__VLS_112, new __VLS_112({
        value: "leave",
    }));
    var __VLS_114 = __VLS_113.apply(void 0, __spreadArray([{
            value: "leave",
        }], __VLS_functionalComponentArgsRest(__VLS_113), false));
    var __VLS_117 = __VLS_115.slots.default;
    var __VLS_118 = EmployeeLeaveTab_vue_1.default;
    // @ts-ignore
    var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118(__assign(__assign({ 'onUpdate:leave': {} }, { 'onViewDetails': {} }), { employeeId: (__VLS_ctx.employeeId), initialData: (__VLS_ctx.employeeInfo.leave_info), ref: "leaveTabRef" })));
    var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([__assign(__assign({ 'onUpdate:leave': {} }, { 'onViewDetails': {} }), { employeeId: (__VLS_ctx.employeeId), initialData: (__VLS_ctx.employeeInfo.leave_info), ref: "leaveTabRef" })], __VLS_functionalComponentArgsRest(__VLS_119), false));
    var __VLS_123 = void 0;
    var __VLS_124 = ({ 'update:leave': {} },
        { 'onUpdate:leave': (__VLS_ctx.handleLeaveUpdate) });
    var __VLS_125 = ({ viewDetails: {} },
        { onViewDetails: (__VLS_ctx.handleViewLeaveDetails) });
    var __VLS_126 = {};
    var __VLS_121;
    var __VLS_122;
    // @ts-ignore
    [employeeInfo, employeeId, handleLeaveUpdate, handleViewLeaveDetails,];
    var __VLS_115;
    var __VLS_128 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
    TabPanel;
    // @ts-ignore
    var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128({
        value: "payslip",
    }));
    var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([{
            value: "payslip",
        }], __VLS_functionalComponentArgsRest(__VLS_129), false));
    var __VLS_133 = __VLS_131.slots.default;
    if ((_o = (_m = __VLS_ctx.employeeInfo) === null || _m === void 0 ? void 0 : _m.basic_info) === null || _o === void 0 ? void 0 : _o.id) {
        var __VLS_134 = EmployeePayrollTab_vue_1.default;
        // @ts-ignore
        var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134(__assign(__assign(__assign(__assign(__assign({ 'onViewPayslip': {} }, { 'onDownloadPayslip': {} }), { 'onPrintPayslip': {} }), { 'onGeneratePayslip': {} }), { 'onExportAll': {} }), { employeeId: ((_p = __VLS_ctx.employeeInfo.basic_info) === null || _p === void 0 ? void 0 : _p.id), employeeName: ((_r = (_q = __VLS_ctx.employeeInfo) === null || _q === void 0 ? void 0 : _q.basic_info) === null || _r === void 0 ? void 0 : _r.name), ref: "payslipHistoryRef" })));
        var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign(__assign({ 'onViewPayslip': {} }, { 'onDownloadPayslip': {} }), { 'onPrintPayslip': {} }), { 'onGeneratePayslip': {} }), { 'onExportAll': {} }), { employeeId: ((_s = __VLS_ctx.employeeInfo.basic_info) === null || _s === void 0 ? void 0 : _s.id), employeeName: ((_u = (_t = __VLS_ctx.employeeInfo) === null || _t === void 0 ? void 0 : _t.basic_info) === null || _u === void 0 ? void 0 : _u.name), ref: "payslipHistoryRef" })], __VLS_functionalComponentArgsRest(__VLS_135), false));
        var __VLS_139 = void 0;
        var __VLS_140 = ({ viewPayslip: {} },
            { onViewPayslip: (__VLS_ctx.handleViewPayslip) });
        var __VLS_141 = ({ downloadPayslip: {} },
            { onDownloadPayslip: (__VLS_ctx.handleDownloadPayslip) });
        var __VLS_142 = ({ printPayslip: {} },
            { onPrintPayslip: (__VLS_ctx.handlePrintPayslip) });
        var __VLS_143 = ({ generatePayslip: {} },
            { onGeneratePayslip: (__VLS_ctx.handleGeneratePayslip) });
        var __VLS_144 = ({ exportAll: {} },
            { onExportAll: (__VLS_ctx.handleExportAll) });
        var __VLS_145 = {};
        var __VLS_137;
        var __VLS_138;
    }
    // @ts-ignore
    [employeeInfo, employeeInfo, employeeInfo, handleViewPayslip, handleDownloadPayslip, handlePrintPayslip, handleGeneratePayslip, handleExportAll,];
    var __VLS_131;
    // @ts-ignore
    [];
    var __VLS_82;
    // @ts-ignore
    [];
    var __VLS_46;
}
// @ts-ignore
var __VLS_111 = __VLS_110, __VLS_127 = __VLS_126, __VLS_146 = __VLS_145;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
