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
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../stores/auth");
var select_1 = require("primevue/select");
var button_1 = require("primevue/button");
var checkbox_1 = require("primevue/checkbox");
var tag_1 = require("primevue/tag");
var props = defineProps();
var emit = defineEmits();
// ==================== STORES ====================
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// ==================== STATE ====================
var loadingData = (0, vue_1.ref)(false);
var submitted = (0, vue_1.ref)(false);
var generating = (0, vue_1.ref)(false);
var generationMethod = (0, vue_1.ref)('all');
var selectedEmployeeIds = (0, vue_1.ref)([]);
var generationErrors = (0, vue_1.ref)([]);
var formData = (0, vue_1.ref)({
    periodId: (_a = props.initialPeriodId) !== null && _a !== void 0 ? _a : null
});
var filters = (0, vue_1.ref)({
    branch: null,
    department: null,
    employmentType: null
});
var options = (0, vue_1.ref)({
    saveAsDraft: true,
    recalculate: false,
    sendNotification: false
});
// Data from API
var availablePayPeriods = (0, vue_1.ref)([]);
var allEmployees = (0, vue_1.ref)([]);
// Static options
var employmentTypeOptions = (0, vue_1.ref)([
    { label: 'Full Time', value: 'full_time' },
    { label: 'Part Time', value: 'part_time' },
    { label: 'Contract', value: 'contract' },
    { label: 'Intern', value: 'intern' },
]);
// ==================== COMPUTED ====================
/** Unique branches derived from employees */
var branches = (0, vue_1.computed)(function () {
    return __spreadArray([], new Set(allEmployees.value.map(function (e) { return e.branch; }).filter(Boolean)), true);
});
/** Unique departments derived from employees */
var departments = (0, vue_1.computed)(function () {
    return __spreadArray([], new Set(allEmployees.value.map(function (e) { return e.department; }).filter(Boolean)), true);
});
/** Active employees only */
var readyEmployees = (0, vue_1.computed)(function () {
    return allEmployees.value.filter(function (e) { return e.status === 'active'; });
});
var readyCount = (0, vue_1.computed)(function () { return readyEmployees.value.length; });
/** Employees filtered by the current filter selections */
var filteredEmployees = (0, vue_1.computed)(function () {
    if (generationMethod.value !== 'specific')
        return [];
    return readyEmployees.value.filter(function (emp) {
        var matchesBranch = !filters.value.branch || emp.branch === filters.value.branch;
        var matchesDept = !filters.value.department || emp.department === filters.value.department;
        var matchesType = !filters.value.employmentType || emp.employment_type === filters.value.employmentType;
        return matchesBranch && matchesDept && matchesType;
    });
});
/** Number of employees that will be processed */
var employeeCount = (0, vue_1.computed)(function () {
    if (generationMethod.value === 'all')
        return readyCount.value;
    return selectedEmployeeIds.value.length;
});
var showSummary = (0, vue_1.computed)(function () {
    return !!formData.value.periodId && employeeCount.value > 0;
});
var isValid = (0, vue_1.computed)(function () {
    if (!formData.value.periodId)
        return false;
    if (generationMethod.value === 'specific' && selectedEmployeeIds.value.length === 0)
        return false;
    return true;
});
/** Rough estimated gross (sum of monthly salaries) */
var estimatedGross = (0, vue_1.computed)(function () {
    if (generationMethod.value === 'all') {
        return readyEmployees.value.reduce(function (sum, e) { var _a; return sum + ((_a = e.monthly_salary) !== null && _a !== void 0 ? _a : 0); }, 0);
    }
    return allEmployees.value
        .filter(function (e) { return selectedEmployeeIds.value.includes(e.id); })
        .reduce(function (sum, e) { var _a; return sum + ((_a = e.monthly_salary) !== null && _a !== void 0 ? _a : 0); }, 0);
});
/** Rough estimated net (gross minus ~20% for taxes/deductions) */
var estimatedNet = (0, vue_1.computed)(function () { return estimatedGross.value * 0.8; });
// ==================== METHODS ====================
var formatCurrency = function (value) {
    if (!value)
        return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
};
/** Ensure auth token is set on axios */
var ensureAuthHeader = function () {
    var token = authStore.token || localStorage.getItem('auth_token');
    if (token) {
        axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(token);
    }
};
/** Fetch available pay periods from GET /api/payroll/periods */
var fetchPayPeriods = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                _d.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('/api/payroll/periods')];
            case 1:
                response = _d.sent();
                if (response.data.success) {
                    // Only show draft/processing periods (not locked/completed)
                    availablePayPeriods.value = response.data.data.filter(function (p) { return p.status === 'Draft' || p.status === 'Processing' || p.status === 'draft' || p.status === 'processing'; });
                }
                return [3 /*break*/, 3];
            case 2:
                error_1 = _d.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: (_c = (_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) !== null && _c !== void 0 ? _c : 'Failed to load pay periods',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
/** Fetch employees from GET /api/payroll/getEmployeesBasicSalary */
var fetchEmployees = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                _d.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('/api/payroll/getEmployeesBasicSalary')];
            case 1:
                response = _d.sent();
                if (response.data.success) {
                    allEmployees.value = response.data.data;
                }
                return [3 /*break*/, 3];
            case 2:
                error_2 = _d.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: (_c = (_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) !== null && _c !== void 0 ? _c : 'Failed to load employees',
                    life: 3000
                });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var handlePeriodChange = function () {
    // Reset errors when period changes
    generationErrors.value = [];
};
var applyFilters = function () {
    // Filters are applied via computed property; clear selection on filter change
    selectedEmployeeIds.value = [];
};
var selectAllFiltered = function () {
    selectedEmployeeIds.value = filteredEmployees.value.map(function (e) { return e.id; });
};
var toggleEmployee = function (emp) {
    var idx = selectedEmployeeIds.value.indexOf(emp.id);
    if (idx === -1) {
        selectedEmployeeIds.value.push(emp.id);
    }
    else {
        selectedEmployeeIds.value.splice(idx, 1);
    }
};
/** Call POST /api/payroll/generate */
var generatePayroll = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, _a, generated, errors, error_3, message;
    var _b, _c, _d, _e;
    return __generator(this, function (_f) {
        switch (_f.label) {
            case 0:
                submitted.value = true;
                generationErrors.value = [];
                if (!isValid.value) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation',
                        detail: formData.value.periodId
                            ? 'Please select at least one employee.'
                            : 'Please select a pay period.',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                generating.value = true;
                _f.label = 1;
            case 1:
                _f.trys.push([1, 3, 4, 5]);
                payload = {
                    pay_period_id: formData.value.periodId,
                    recalculate: options.value.recalculate,
                    initial_status: options.value.saveAsDraft ? 'draft' : 'processing',
                };
                if (generationMethod.value === 'specific') {
                    payload.employee_ids = selectedEmployeeIds.value;
                }
                return [4 /*yield*/, axios_1.default.post('/api/payroll/generate', payload)];
            case 2:
                response = _f.sent();
                if (response.data.success) {
                    _a = response.data.data, generated = _a.generated, errors = _a.errors;
                    if (errors && errors.length > 0) {
                        generationErrors.value = errors;
                    }
                    toast.add({
                        severity: (errors === null || errors === void 0 ? void 0 : errors.length) > 0 ? 'warn' : 'success',
                        summary: (errors === null || errors === void 0 ? void 0 : errors.length) > 0 ? 'Partial Success' : 'Success',
                        detail: "Payroll generated for ".concat(generated, " employee(s).").concat((errors === null || errors === void 0 ? void 0 : errors.length) > 0 ? " ".concat(errors.length, " error(s) occurred.") : ''),
                        life: 5000
                    });
                    emit('generated', response.data.data);
                }
                else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: (_b = response.data.message) !== null && _b !== void 0 ? _b : 'Failed to generate payroll',
                        life: 4000
                    });
                }
                return [3 /*break*/, 5];
            case 3:
                error_3 = _f.sent();
                message = (_e = (_d = (_c = error_3.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) !== null && _e !== void 0 ? _e : 'Failed to generate payroll';
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: message,
                    life: 4000
                });
                return [3 /*break*/, 5];
            case 4:
                generating.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// ==================== LIFECYCLE ====================
(0, vue_1.onMounted)(function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                ensureAuthHeader();
                loadingData.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, Promise.all([fetchPayPeriods(), fetchEmployees()])];
            case 2:
                _a.sent();
                return [3 /*break*/, 4];
            case 3:
                loadingData.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); });
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['employee-list']} */ ;
/** @type {__VLS_StyleScopedClasses['employee-list']} */ ;
/** @type {__VLS_StyleScopedClasses['employee-list']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "generate-payroll-modal" }));
/** @type {__VLS_StyleScopedClasses['generate-payroll-modal']} */ ;
if (__VLS_ctx.loadingData) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center py-10" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-10']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-spin pi-spinner text-3xl text-blue-500 mr-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spin']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-spinner']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4" }));
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "font-bold block mb-2" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.formData.periodId), options: (__VLS_ctx.availablePayPeriods), optionLabel: "period", optionValue: "id", placeholder: "Select Pay Period" }), { class: "w-full" }), { class: ({ 'p-invalid': !__VLS_ctx.formData.periodId && __VLS_ctx.submitted }) })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.formData.periodId), options: (__VLS_ctx.availablePayPeriods), optionLabel: "period", optionValue: "id", placeholder: "Select Pay Period" }), { class: "w-full" }), { class: ({ 'p-invalid': !__VLS_ctx.formData.periodId && __VLS_ctx.submitted }) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ change: {} },
        { onChange: (__VLS_ctx.handlePeriodChange) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    var __VLS_3;
    var __VLS_4;
    if (!__VLS_ctx.formData.periodId && __VLS_ctx.submitted) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    }
    if (__VLS_ctx.availablePayPeriods.length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-amber-500" }));
        /** @type {__VLS_StyleScopedClasses['text-amber-500']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4" }));
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "font-bold block mb-2" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign(__assign(__assign({ 'onClick': {} }, { label: ("All Active Employees (".concat(__VLS_ctx.readyCount, ")")) }), { class: ({ 'p-button-outlined': __VLS_ctx.generationMethod !== 'all' }) }), { severity: "info" }), { class: "flex-1" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ 'onClick': {} }, { label: ("All Active Employees (".concat(__VLS_ctx.readyCount, ")")) }), { class: ({ 'p-button-outlined': __VLS_ctx.generationMethod !== 'all' }) }), { severity: "info" }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.generationMethod = 'all';
                // @ts-ignore
                [loadingData, formData, formData, formData, availablePayPeriods, availablePayPeriods, submitted, submitted, handlePeriodChange, readyCount, generationMethod, generationMethod,];
            } });
    /** @type {__VLS_StyleScopedClasses['p-button-outlined']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    var __VLS_10;
    var __VLS_11;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign(__assign(__assign({ 'onClick': {} }, { label: "Specific Selection" }), { class: ({ 'p-button-outlined': __VLS_ctx.generationMethod !== 'specific' }) }), { severity: "info" }), { class: "flex-1" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ 'onClick': {} }, { label: "Specific Selection" }), { class: ({ 'p-button-outlined': __VLS_ctx.generationMethod !== 'specific' }) }), { severity: "info" }), { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.generationMethod = 'specific';
                // @ts-ignore
                [generationMethod, generationMethod,];
            } });
    /** @type {__VLS_StyleScopedClasses['p-button-outlined']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    var __VLS_17;
    var __VLS_18;
    if (__VLS_ctx.generationMethod === 'specific') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "filters-section border rounded p-3 mb-4" }));
        /** @type {__VLS_StyleScopedClasses['filters-section']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium mb-3" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block mb-1 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        var __VLS_21 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }), { class: "w-full" })));
        var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), placeholder: "All Branches", showClear: true }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
        var __VLS_26 = void 0;
        var __VLS_27 = ({ change: {} },
            { onChange: (__VLS_ctx.applyFilters) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_24;
        var __VLS_25;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block mb-1 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        var __VLS_28 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }), { class: "w-full" })));
        var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), placeholder: "All Departments", showClear: true }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
        var __VLS_33 = void 0;
        var __VLS_34 = ({ change: {} },
            { onChange: (__VLS_ctx.applyFilters) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_31;
        var __VLS_32;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block mb-1 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        var __VLS_35 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.employmentType), options: (__VLS_ctx.employmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true }), { class: "w-full" })));
        var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.employmentType), options: (__VLS_ctx.employmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
        var __VLS_40 = void 0;
        var __VLS_41 = ({ change: {} },
            { onChange: (__VLS_ctx.applyFilters) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_38;
        var __VLS_39;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-3" }));
        /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.filteredEmployees.length);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_42 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ 'onClick': {} }, { label: "Select All", severity: "info", text: true, size: "small" })));
        var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Select All", severity: "info", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
        var __VLS_47 = void 0;
        var __VLS_48 = ({ click: {} },
            { onClick: (__VLS_ctx.selectAllFiltered) });
        var __VLS_45;
        var __VLS_46;
        var __VLS_49 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ 'onClick': {} }, { label: "Clear All", severity: "secondary", text: true, size: "small" })));
        var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear All", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
        var __VLS_54 = void 0;
        var __VLS_55 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loadingData))
                        return;
                    if (!(__VLS_ctx.generationMethod === 'specific'))
                        return;
                    __VLS_ctx.selectedEmployeeIds = [];
                    // @ts-ignore
                    [generationMethod, filters, filters, filters, branches, applyFilters, applyFilters, applyFilters, departments, employmentTypeOptions, filteredEmployees, selectAllFiltered, selectedEmployeeIds,];
                } });
        var __VLS_52;
        var __VLS_53;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "employee-list border rounded max-h-48 overflow-y-auto" }));
        /** @type {__VLS_StyleScopedClasses['employee-list']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['max-h-48']} */ ;
        /** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
        var _loop_1 = function (emp) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loadingData))
                        return;
                    if (!(__VLS_ctx.generationMethod === 'specific'))
                        return;
                    __VLS_ctx.toggleEmployee(emp);
                    // @ts-ignore
                    [filteredEmployees, toggleEmployee,];
                } }, { key: (emp.id) }), { class: "flex items-center p-2 hover:bg-gray-50 border-b last:border-b-0 cursor-pointer" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
            /** @type {__VLS_StyleScopedClasses['last:border-b-0']} */ ;
            /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
            var __VLS_56 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
            checkbox_1.default;
            // @ts-ignore
            var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56({
                inputId: ('emp_' + emp.id),
                value: (emp.id),
                modelValue: (__VLS_ctx.selectedEmployeeIds),
            }));
            var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([{
                    inputId: ('emp_' + emp.id),
                    value: (emp.id),
                    modelValue: (__VLS_ctx.selectedEmployeeIds),
                }], __VLS_functionalComponentArgsRest(__VLS_57), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: ('emp_' + emp.id) }, { class: "ml-2 flex-1 cursor-pointer" }));
            /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (emp.full_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (emp.employee_number);
            (emp.branch);
            (emp.department);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
            /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
            var __VLS_61 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61({
                value: (emp.employment_type),
                severity: "info",
                size: "small",
            }));
            var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([{
                    value: (emp.employment_type),
                    severity: "info",
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_62), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            (__VLS_ctx.formatCurrency(emp.monthly_salary));
            // @ts-ignore
            [selectedEmployeeIds, formatCurrency,];
        };
        for (var _i = 0, _b = __VLS_vFor((__VLS_ctx.filteredEmployees)); _i < _b.length; _i++) {
            var emp = _b[_i][0];
            _loop_1(emp);
        }
        if (__VLS_ctx.filteredEmployees.length === 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-4 text-gray-500 text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        }
    }
    if (__VLS_ctx.showSummary) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "summary-section bg-blue-50 p-3 rounded mb-4" }));
        /** @type {__VLS_StyleScopedClasses['summary-section']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-bold mb-2" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-2 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.employeeCount);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.formatCurrency(__VLS_ctx.estimatedGross));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-bold text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        (__VLS_ctx.formatCurrency(__VLS_ctx.estimatedNet));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "options-section border rounded p-3 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['options-section']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium mb-2" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-2 rounded-lg" }, { class: (__VLS_ctx.options.saveAsDraft ? 'bg-gray-50' : 'bg-blue-50') }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_66 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
    checkbox_1.default;
    // @ts-ignore
    var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66({
        modelValue: (__VLS_ctx.options.saveAsDraft),
        inputId: "saveAsDraft",
        binary: true,
    }));
    var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.options.saveAsDraft),
            inputId: "saveAsDraft",
            binary: true,
        }], __VLS_functionalComponentArgsRest(__VLS_67), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "saveAsDraft" }, { class: "text-sm font-medium cursor-pointer select-none" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['select-none']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs mt-0.5" }, { class: (__VLS_ctx.options.saveAsDraft ? 'text-gray-500' : 'text-blue-600') }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-0.5']} */ ;
    if (__VLS_ctx.options.saveAsDraft) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_71 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
    checkbox_1.default;
    // @ts-ignore
    var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
        modelValue: (__VLS_ctx.options.recalculate),
        inputId: "recalculate",
        binary: true,
    }));
    var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.options.recalculate),
            inputId: "recalculate",
            binary: true,
        }], __VLS_functionalComponentArgsRest(__VLS_72), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "recalculate" }, { class: "ml-2 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
    checkbox_1.default;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({
        modelValue: (__VLS_ctx.options.sendNotification),
        inputId: "sendNotification",
        binary: true,
    }));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.options.sendNotification),
            inputId: "sendNotification",
            binary: true,
        }], __VLS_functionalComponentArgsRest(__VLS_77), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "sendNotification" }, { class: "ml-2 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    if (__VLS_ctx.generationErrors.length > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-4 p-3 bg-red-50 border border-red-200 rounded" }));
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-red-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-red-700 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle mr-1" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
        (__VLS_ctx.generationErrors.length);
        __VLS_asFunctionalElement1(__VLS_intrinsics.ul, __VLS_intrinsics.ul)(__assign({ class: "text-sm text-red-600 list-disc list-inside" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['list-disc']} */ ;
        /** @type {__VLS_StyleScopedClasses['list-inside']} */ ;
        for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.generationErrors)); _c < _d.length; _c++) {
            var _e = _d[_c], err = _e[0], i = _e[1];
            __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({
                key: (i),
            });
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (err.employee);
            (err.error);
            // @ts-ignore
            [filteredEmployees, formatCurrency, formatCurrency, showSummary, employeeCount, estimatedGross, estimatedNet, options, options, options, options, options, options, generationErrors, generationErrors, generationErrors,];
        }
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-2 mt-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    var __VLS_81 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, disabled: (__VLS_ctx.generating) })));
    var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, disabled: (__VLS_ctx.generating) })], __VLS_functionalComponentArgsRest(__VLS_82), false));
    var __VLS_86 = void 0;
    var __VLS_87 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.$emit('close');
                // @ts-ignore
                [generating, $emit,];
            } });
    var __VLS_84;
    var __VLS_85;
    var __VLS_88 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "info", icon: "pi pi-cog", loading: (__VLS_ctx.generating), disabled: (!__VLS_ctx.isValid || __VLS_ctx.generating) })));
    var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "info", icon: "pi pi-cog", loading: (__VLS_ctx.generating), disabled: (!__VLS_ctx.isValid || __VLS_ctx.generating) })], __VLS_functionalComponentArgsRest(__VLS_89), false));
    var __VLS_93 = void 0;
    var __VLS_94 = ({ click: {} },
        { onClick: (__VLS_ctx.generatePayroll) });
    var __VLS_91;
    var __VLS_92;
}
// @ts-ignore
[generating, generating, isValid, generatePayroll,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
