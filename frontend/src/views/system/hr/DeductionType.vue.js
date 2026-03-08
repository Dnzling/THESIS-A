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
var _a, _b, _c, _d;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
// Toast
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// Set authorization header
axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(authStore.token);
// State
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var deductionTypes = (0, vue_1.ref)([]);
var showDialog = (0, vue_1.ref)(false);
var showDetailsDialog = (0, vue_1.ref)(false);
var showDeleteDialog = (0, vue_1.ref)(false);
var isEditing = (0, vue_1.ref)(false);
var selectedDeduction = (0, vue_1.ref)(null);
var errors = (0, vue_1.ref)({});
var formulaJson = (0, vue_1.ref)('');
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    category: null,
    calculationType: null,
    isActive: null
});
// Form data
var form = (0, vue_1.ref)({
    id: null,
    code: '',
    name: '',
    description: '',
    category: 'company',
    calculation_type: 'fixed',
    frequency: 'monthly',
    default_amount: 0,
    percentage_value: 0,
    percentage_basis: 'basic',
    min_amount: null,
    max_amount: null,
    formula_data: null,
    is_mandatory: false,
    is_taxable: false,
    is_active: true,
    show_on_payslip: true,
    sort_order: 0
});
// Options
var categoryOptions = [
    { label: 'Government', value: 'government' },
    { label: 'Company', value: 'company' },
    { label: 'Loan', value: 'loan' },
    { label: 'Benefit', value: 'benefit' },
    { label: 'Other', value: 'other' }
];
var calculationTypeOptions = [
    { label: 'Fixed Amount', value: 'fixed' },
    { label: 'Percentage', value: 'percentage' },
    { label: 'Formula', value: 'formula' }
];
var basisOptions = [
    { label: 'Basic Salary', value: 'basic' },
    { label: 'Gross Salary', value: 'gross' },
    { label: 'Taxable Income', value: 'taxable' }
];
var frequencyOptions = [
    { label: 'One-time', value: 'one-time' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Bi-monthly', value: 'bi-monthly' },
    { label: 'Quarterly', value: 'quarterly' },
    { label: 'Annual', value: 'annual' }
];
var statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];
// Computed
var activeCount = (0, vue_1.computed)(function () { return deductionTypes.value.filter(function (d) { return d.is_active; }).length; });
var mandatoryCount = (0, vue_1.computed)(function () { return deductionTypes.value.filter(function (d) { return d.is_mandatory; }).length; });
var taxableCount = (0, vue_1.computed)(function () { return deductionTypes.value.filter(function (d) { return d.is_taxable; }).length; });
// Helper functions
var formatNumber = function (num) {
    return (num === null || num === void 0 ? void 0 : num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) || '0.00';
};
var formatCategory = function (category) {
    var map = {
        government: 'Government',
        company: 'Company',
        loan: 'Loan',
        benefit: 'Benefit',
        other: 'Other'
    };
    return map[category] || category;
};
var formatCalculationType = function (type) {
    var map = {
        fixed: 'Fixed',
        percentage: 'Percentage',
        formula: 'Formula'
    };
    return map[type] || type;
};
var getCategorySeverity = function (category) {
    var map = {
        government: 'info',
        company: 'success',
        loan: 'warning',
        benefit: 'help',
        other: 'secondary'
    };
    return map[category] || 'info';
};
var getCalculationIcon = function (type) {
    var map = {
        fixed: 'pi pi-dollar',
        percentage: 'pi pi-percentage',
        formula: 'pi pi-calculator'
    };
    return map[type] || 'pi pi-list';
};
var formatDateTime = function (date) {
    if (!date)
        return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
var resetForm = function () {
    form.value = {
        id: null,
        code: '',
        name: '',
        description: '',
        category: 'company',
        calculation_type: 'fixed',
        frequency: 'monthly',
        default_amount: 0,
        percentage_value: 0,
        percentage_basis: 'basic',
        min_amount: null,
        max_amount: null,
        formula_data: null,
        is_mandatory: false,
        is_taxable: false,
        is_active: true,
        show_on_payslip: true,
        sort_order: 0
    };
    formulaJson.value = '';
    errors.value = {};
};
// API Functions
var fetchDeductionTypes = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                params = {};
                if (filters.value.search)
                    params.search = filters.value.search;
                if (filters.value.category)
                    params.category = filters.value.category;
                if (filters.value.calculationType)
                    params.calculation_type = filters.value.calculationType;
                if (filters.value.isActive !== null)
                    params.is_active = filters.value.isActive;
                params.with_counts = true;
                return [4 /*yield*/, axios_1.default.get('api/deductions/deduction-types', { params: params })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    deductionTypes.value = response.data.data;
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Error fetching deduction types:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch deduction types',
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
var saveDeductionType = function () { return __awaiter(void 0, void 0, void 0, function () {
    var data, response, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                saving.value = true;
                errors.value = {};
                _d.label = 1;
            case 1:
                _d.trys.push([1, 8, 9, 10]);
                data = __assign({}, form.value);
                // Handle formula JSON
                if (data.calculation_type === 'formula' && formulaJson.value) {
                    try {
                        data.formula_data = JSON.parse(formulaJson.value);
                    }
                    catch (e) {
                        errors.value.formula_data = ['Invalid JSON format'];
                        saving.value = false;
                        return [2 /*return*/];
                    }
                }
                response = void 0;
                if (!isEditing.value) return [3 /*break*/, 3];
                return [4 /*yield*/, axios_1.default.put("api/deductions/deduction-types/".concat(form.value.id), data)];
            case 2:
                response = _d.sent();
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, axios_1.default.post('api/deductions/deduction-types', data)];
            case 4:
                response = _d.sent();
                _d.label = 5;
            case 5:
                if (!response.data.success) return [3 /*break*/, 7];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: isEditing.value ? 'Deduction type updated successfully' : 'Deduction type created successfully',
                    life: 3000
                });
                closeDialog();
                return [4 /*yield*/, fetchDeductionTypes()];
            case 6:
                _d.sent();
                _d.label = 7;
            case 7: return [3 /*break*/, 10];
            case 8:
                error_2 = _d.sent();
                if (((_a = error_2.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors.value = error_2.response.data.errors || {};
                }
                else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: ((_c = (_b = error_2.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save deduction type',
                        life: 3000
                    });
                }
                return [3 /*break*/, 10];
            case 9:
                saving.value = false;
                return [7 /*endfinally*/];
            case 10: return [2 /*return*/];
        }
    });
}); };
var toggleActive = function (deduction) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                _c.trys.push([0, 4, , 5]);
                return [4 /*yield*/, axios_1.default.post("api/deductions/deduction-types/".concat(deduction.id, "/toggle-active"))];
            case 1:
                response = _c.sent();
                if (!response.data.success) return [3 /*break*/, 3];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "Deduction type ".concat(deduction.is_active ? 'deactivated' : 'activated', " successfully"),
                    life: 3000
                });
                return [4 /*yield*/, fetchDeductionTypes()];
            case 2:
                _c.sent();
                _c.label = 3;
            case 3: return [3 /*break*/, 5];
            case 4:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to toggle status',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 5: return [2 /*return*/];
        }
    });
}); };
var deleteDeductionType = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!selectedDeduction.value)
                    return [2 /*return*/];
                _c.label = 1;
            case 1:
                _c.trys.push([1, 5, , 6]);
                return [4 /*yield*/, axios_1.default.delete("api/deductions/deduction-types/".concat(selectedDeduction.value.id))];
            case 2:
                response = _c.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Deduction type deleted successfully',
                    life: 3000
                });
                showDeleteDialog.value = false;
                selectedDeduction.value = null;
                return [4 /*yield*/, fetchDeductionTypes()];
            case 3:
                _c.sent();
                _c.label = 4;
            case 4: return [3 /*break*/, 6];
            case 5:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete deduction type',
                    life: 3000
                });
                return [3 /*break*/, 6];
            case 6: return [2 /*return*/];
        }
    });
}); };
// Dialog handlers
var openCreateDialog = function () {
    resetForm();
    isEditing.value = false;
    showDialog.value = true;
};
var openEditDialog = function (deduction) {
    isEditing.value = true;
    form.value = {
        id: deduction.id,
        code: deduction.code,
        name: deduction.name,
        description: deduction.description || '',
        category: deduction.category,
        calculation_type: deduction.calculation_type,
        frequency: deduction.frequency || 'monthly',
        default_amount: deduction.default_amount || 0,
        percentage_value: deduction.percentage_value || 0,
        percentage_basis: deduction.percentage_basis || 'basic',
        min_amount: deduction.min_amount,
        max_amount: deduction.max_amount,
        formula_data: deduction.formula_data,
        is_mandatory: deduction.is_mandatory || false,
        is_taxable: deduction.is_taxable || false,
        is_active: deduction.is_active || false,
        show_on_payslip: deduction.show_on_payslip || false,
        sort_order: deduction.sort_order || 0
    };
    if (deduction.formula_data) {
        formulaJson.value = JSON.stringify(deduction.formula_data, null, 2);
    }
    showDialog.value = true;
};
var viewDetails = function (deduction) {
    selectedDeduction.value = deduction;
    showDetailsDialog.value = true;
};
var editFromDetails = function () {
    if (selectedDeduction.value) {
        openEditDialog(selectedDeduction.value);
        showDetailsDialog.value = false;
    }
};
var confirmDelete = function (deduction) {
    selectedDeduction.value = deduction;
    showDeleteDialog.value = true;
};
var closeDialog = function () {
    showDialog.value = false;
    resetForm();
};
// Lifecycle
(0, vue_1.onMounted)(function () {
    fetchDeductionTypes();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: " mx-auto" }));
/** @type {__VLS_StyleScopedClasses['']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Add Deduction Type", icon: "pi pi-plus", severity: "info" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Deduction Type", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.openCreateDialog) });
var __VLS_3;
var __VLS_4;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center jusstify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['jusstify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.deductionTypes.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-list text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-list']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-green-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
(__VLS_ctx.activeCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-orange-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
(__VLS_ctx.mandatoryCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-lock text-orange-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-lock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4" }));
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-purple-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
(__VLS_ctx.taxableCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-percentage text-purple-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-percentage']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
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
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search..." }), { class: "w-64" })));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search..." }), { class: "w-64" })], __VLS_functionalComponentArgsRest(__VLS_19), false));
var __VLS_23;
var __VLS_24 = ({ input: {} },
    { onInput: (__VLS_ctx.fetchDeductionTypes) });
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
var __VLS_21;
var __VLS_22;
// @ts-ignore
[openCreateDialog, deductionTypes, activeCount, mandatoryCount, taxableCount, filters, fetchDeductionTypes,];
var __VLS_10;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category), options: (__VLS_ctx.categoryOptions), optionLabel: "label", optionValue: "value", placeholder: "All Categories", showClear: true }), { class: "w-48" })));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category), options: (__VLS_ctx.categoryOptions), optionLabel: "label", optionValue: "value", placeholder: "All Categories", showClear: true }), { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
var __VLS_30;
var __VLS_31 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchDeductionTypes) });
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_28;
var __VLS_29;
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.calculationType), options: (__VLS_ctx.calculationTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "All Calculation Types", showClear: true }), { class: "w-48" })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.calculationType), options: (__VLS_ctx.calculationTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "All Calculation Types", showClear: true }), { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchDeductionTypes) });
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_35;
var __VLS_36;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.isActive), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true }), { class: "w-40" })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.isActive), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true }), { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
var __VLS_44;
var __VLS_45 = ({ change: {} },
    { onChange: (__VLS_ctx.fetchDeductionTypes) });
/** @type {__VLS_StyleScopedClasses['w-40']} */ ;
var __VLS_42;
var __VLS_43;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ value: (__VLS_ctx.deductionTypes), loading: (__VLS_ctx.loading), paginator: (true), rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries", rowHover: true, responsiveLayout: "scroll" }, { class: "text-sm" })));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.deductionTypes), loading: (__VLS_ctx.loading), paginator: (true), rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} entries", rowHover: true, responsiveLayout: "scroll" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_51 = __VLS_49.slots.default;
var __VLS_52;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign({ field: "code", header: "Code", sortable: true }, { style: {} })));
var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign({ field: "code", header: "Code", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_53), false));
var __VLS_57 = __VLS_55.slots.default;
{
    var __VLS_58 = __VLS_55.slots.body;
    var data = __VLS_vSlot(__VLS_58)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-xs bg-gray-100 px-2 py-1 rounded" }));
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    (data.code);
    // @ts-ignore
    [deductionTypes, filters, filters, filters, fetchDeductionTypes, fetchDeductionTypes, fetchDeductionTypes, categoryOptions, calculationTypeOptions, statusOptions, loading,];
}
// @ts-ignore
[];
var __VLS_55;
var __VLS_59;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ field: "name", header: "Name", sortable: true }, { style: {} })));
var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ field: "name", header: "Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_60), false));
var __VLS_64 = __VLS_62.slots.default;
{
    var __VLS_65 = __VLS_62.slots.body;
    var data = __VLS_vSlot(__VLS_65)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (data.name);
    if (data.description) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (data.description);
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_62;
var __VLS_66;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign({ field: "category", header: "Category", sortable: true }, { style: {} })));
var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign({ field: "category", header: "Category", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_67), false));
var __VLS_71 = __VLS_69.slots.default;
{
    var __VLS_72 = __VLS_69.slots.body;
    var data = __VLS_vSlot(__VLS_72)[0].data;
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73({
        value: (__VLS_ctx.formatCategory(data.category)),
        severity: (__VLS_ctx.getCategorySeverity(data.category)),
    }));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.formatCategory(data.category)),
            severity: (__VLS_ctx.getCategorySeverity(data.category)),
        }], __VLS_functionalComponentArgsRest(__VLS_74), false));
    // @ts-ignore
    [formatCategory, getCategorySeverity,];
}
// @ts-ignore
[];
var __VLS_69;
var __VLS_78;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78(__assign({ field: "calculation_type", header: "Calculation", sortable: true }, { style: {} })));
var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([__assign({ field: "calculation_type", header: "Calculation", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_79), false));
var __VLS_83 = __VLS_81.slots.default;
{
    var __VLS_84 = __VLS_81.slots.body;
    var data = __VLS_vSlot(__VLS_84)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.getCalculationIcon(data.calculation_type)) }, { class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatCalculationType(data.calculation_type));
    // @ts-ignore
    [getCalculationIcon, formatCalculationType,];
}
// @ts-ignore
[];
var __VLS_81;
var __VLS_85;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign({ header: "Amount / Rate" }, { style: {} })));
var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign({ header: "Amount / Rate" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_86), false));
var __VLS_90 = __VLS_88.slots.default;
{
    var __VLS_91 = __VLS_88.slots.body;
    var data = __VLS_vSlot(__VLS_91)[0].data;
    if (data.calculation_type === 'fixed') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatNumber(data.default_amount));
    }
    else if (data.calculation_type === 'percentage') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.percentage_value);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (data.percentage_basis);
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    }
    // @ts-ignore
    [formatNumber,];
}
// @ts-ignore
[];
var __VLS_88;
var __VLS_92;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92(__assign({ field: "frequency", header: "Frequency", sortable: true }, { style: {} })));
var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([__assign({ field: "frequency", header: "Frequency", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_93), false));
var __VLS_97 = __VLS_95.slots.default;
{
    var __VLS_98 = __VLS_95.slots.body;
    var data = __VLS_vSlot(__VLS_98)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "capitalize" }));
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    (data.frequency || 'Monthly');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_95;
var __VLS_99;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ header: "Flags" }, { style: {} })));
var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ header: "Flags" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_100), false));
var __VLS_104 = __VLS_102.slots.default;
{
    var __VLS_105 = __VLS_102.slots.body;
    var data = __VLS_vSlot(__VLS_105)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    if (data.is_mandatory) {
        var __VLS_106 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106({
            value: "Mandatory",
            severity: "warning",
            size: "small",
        }));
        var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([{
                value: "Mandatory",
                severity: "warning",
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_107), false));
    }
    if (data.is_taxable) {
        var __VLS_111 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111({
            value: "Taxable",
            severity: "help",
            size: "small",
        }));
        var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([{
                value: "Taxable",
                severity: "help",
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_112), false));
    }
    if (data.show_on_payslip) {
        var __VLS_116 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116({
            value: "Payslip",
            severity: "info",
            size: "small",
        }));
        var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([{
                value: "Payslip",
                severity: "info",
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_117), false));
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_102;
var __VLS_121;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121(__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })));
var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([__assign({ field: "is_active", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_122), false));
var __VLS_126 = __VLS_124.slots.default;
{
    var __VLS_127 = __VLS_124.slots.body;
    var data = __VLS_vSlot(__VLS_127)[0].data;
    var __VLS_128 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128({
        value: (data.is_active ? 'Active' : 'Inactive'),
        severity: (data.is_active ? 'success' : 'danger'),
    }));
    var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([{
            value: (data.is_active ? 'Active' : 'Inactive'),
            severity: (data.is_active ? 'success' : 'danger'),
        }], __VLS_functionalComponentArgsRest(__VLS_129), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_124;
var __VLS_133;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133(__assign({ header: "Actions" }, { style: {} })));
var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_134), false));
var __VLS_138 = __VLS_136.slots.default;
{
    var __VLS_139 = __VLS_136.slots.body;
    var data_1 = __VLS_vSlot(__VLS_139)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_140 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })));
    var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_141), false));
    var __VLS_145 = void 0;
    var __VLS_146 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.openEditDialog(data_1);
                // @ts-ignore
                [openEditDialog,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
    var __VLS_143;
    var __VLS_144;
    var __VLS_147 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_148 = __VLS_asFunctionalComponent1(__VLS_147, new __VLS_147(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "success", size: "small" })));
    var __VLS_149 = __VLS_148.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "success", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_148), false));
    var __VLS_152 = void 0;
    var __VLS_153 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDetails(data_1);
                // @ts-ignore
                [vTooltip, viewDetails,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View Details') }), null, null);
    var __VLS_150;
    var __VLS_151;
    var __VLS_154 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_155 = __VLS_asFunctionalComponent1(__VLS_154, new __VLS_154(__assign({ 'onClick': {} }, { icon: (data_1.is_active ? 'pi pi-pause' : 'pi pi-play'), text: true, rounded: true, severity: (data_1.is_active ? 'warning' : 'secondary'), size: "small" })));
    var __VLS_156 = __VLS_155.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: (data_1.is_active ? 'pi pi-pause' : 'pi pi-play'), text: true, rounded: true, severity: (data_1.is_active ? 'warning' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_155), false));
    var __VLS_159 = void 0;
    var __VLS_160 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.toggleActive(data_1);
                // @ts-ignore
                [vTooltip, toggleActive,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: (data_1.is_active ? 'Deactivate' : 'Activate') }), null, null);
    var __VLS_157;
    var __VLS_158;
    var __VLS_161 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_162 = __VLS_asFunctionalComponent1(__VLS_161, new __VLS_161(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small", disabled: (data_1.employee_deductions_count > 0) })));
    var __VLS_163 = __VLS_162.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small", disabled: (data_1.employee_deductions_count > 0) })], __VLS_functionalComponentArgsRest(__VLS_162), false));
    var __VLS_166 = void 0;
    var __VLS_167 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.confirmDelete(data_1);
                // @ts-ignore
                [vTooltip, confirmDelete,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
    var __VLS_164;
    var __VLS_165;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_136;
{
    var __VLS_168 = __VLS_49.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-300 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
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
var __VLS_49;
var __VLS_169;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_170 = __VLS_asFunctionalComponent1(__VLS_169, new __VLS_169(__assign(__assign(__assign({ visible: (__VLS_ctx.showDialog), header: (__VLS_ctx.isEditing ? 'Edit Deduction Type' : 'Create Deduction Type'), modal: true }, { style: ({ width: '600px' }) }), { closable: (true) }), { class: "deduction-dialog" })));
var __VLS_171 = __VLS_170.apply(void 0, __spreadArray([__assign(__assign(__assign({ visible: (__VLS_ctx.showDialog), header: (__VLS_ctx.isEditing ? 'Edit Deduction Type' : 'Create Deduction Type'), modal: true }, { style: ({ width: '600px' }) }), { closable: (true) }), { class: "deduction-dialog" })], __VLS_functionalComponentArgsRest(__VLS_170), false));
/** @type {__VLS_StyleScopedClasses['deduction-dialog']} */ ;
var __VLS_174 = __VLS_172.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2 md:col-span-1" }));
/** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
/** @type {__VLS_StyleScopedClasses['md:col-span-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_175;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175(__assign(__assign({ modelValue: (__VLS_ctx.form.code) }, { class: "w-full" }), { placeholder: "e.g., SSS", disabled: (__VLS_ctx.isEditing) })));
var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.code) }, { class: "w-full" }), { placeholder: "e.g., SSS", disabled: (__VLS_ctx.isEditing) })], __VLS_functionalComponentArgsRest(__VLS_176), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
if (__VLS_ctx.errors.code) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.code[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2 md:col-span-1" }));
/** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
/** @type {__VLS_StyleScopedClasses['md:col-span-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_180;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180(__assign(__assign({ modelValue: (__VLS_ctx.form.name) }, { class: "w-full" }), { placeholder: "e.g., SSS Contribution" })));
var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.name) }, { class: "w-full" }), { placeholder: "e.g., SSS Contribution" })], __VLS_functionalComponentArgsRest(__VLS_181), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
if (__VLS_ctx.errors.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.name[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_185;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign(__assign({ modelValue: (__VLS_ctx.form.description) }, { class: "w-full" }), { rows: "2", placeholder: "Optional description..." })));
var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.description) }, { class: "w-full" }), { rows: "2", placeholder: "Optional description..." })], __VLS_functionalComponentArgsRest(__VLS_186), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_190;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_191 = __VLS_asFunctionalComponent1(__VLS_190, new __VLS_190(__assign({ modelValue: (__VLS_ctx.form.category), options: (__VLS_ctx.categoryOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
var __VLS_192 = __VLS_191.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.category), options: (__VLS_ctx.categoryOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_191), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
if (__VLS_ctx.errors.category) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.category[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_195;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195(__assign({ modelValue: (__VLS_ctx.form.calculation_type), options: (__VLS_ctx.calculationTypeOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.calculation_type), options: (__VLS_ctx.calculationTypeOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_196), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
if (__VLS_ctx.errors.calculation_type) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.calculation_type[0]);
}
if (__VLS_ctx.form.calculation_type === 'fixed') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_200 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_201 = __VLS_asFunctionalComponent1(__VLS_200, new __VLS_200(__assign(__assign({ modelValue: (__VLS_ctx.form.default_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })));
    var __VLS_202 = __VLS_201.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.default_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })], __VLS_functionalComponentArgsRest(__VLS_201), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    if (__VLS_ctx.errors.default_amount) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.default_amount[0]);
    }
}
else if (__VLS_ctx.form.calculation_type === 'percentage') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_205 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_206 = __VLS_asFunctionalComponent1(__VLS_205, new __VLS_205(__assign(__assign({ modelValue: (__VLS_ctx.form.percentage_value), min: (0), max: (100), suffix: "%" }, { class: "w-full" }), { placeholder: "0" })));
    var __VLS_207 = __VLS_206.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.percentage_value), min: (0), max: (100), suffix: "%" }, { class: "w-full" }), { placeholder: "0" })], __VLS_functionalComponentArgsRest(__VLS_206), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    if (__VLS_ctx.errors.percentage_value) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.percentage_value[0]);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_210 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_211 = __VLS_asFunctionalComponent1(__VLS_210, new __VLS_210(__assign({ modelValue: (__VLS_ctx.form.percentage_basis), options: (__VLS_ctx.basisOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
    var __VLS_212 = __VLS_211.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.percentage_basis), options: (__VLS_ctx.basisOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_211), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    if (__VLS_ctx.errors.percentage_basis) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.percentage_basis[0]);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_215 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_216 = __VLS_asFunctionalComponent1(__VLS_215, new __VLS_215(__assign(__assign({ modelValue: (__VLS_ctx.form.min_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })));
    var __VLS_217 = __VLS_216.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.min_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })], __VLS_functionalComponentArgsRest(__VLS_216), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_220 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_221 = __VLS_asFunctionalComponent1(__VLS_220, new __VLS_220(__assign(__assign({ modelValue: (__VLS_ctx.form.max_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })));
    var __VLS_222 = __VLS_221.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.max_amount), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" }), { placeholder: "0.00" })], __VLS_functionalComponentArgsRest(__VLS_221), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
else if (__VLS_ctx.form.calculation_type === 'formula') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_225 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    Textarea;
    // @ts-ignore
    var __VLS_226 = __VLS_asFunctionalComponent1(__VLS_225, new __VLS_225(__assign(__assign({ modelValue: (__VLS_ctx.formulaJson) }, { class: "w-full font-mono text-sm" }), { rows: "4", placeholder: '{"formula": "basic_salary * 0.02", "variables": ["basic_salary"]}' })));
    var __VLS_227 = __VLS_226.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.formulaJson) }, { class: "w-full font-mono text-sm" }), { rows: "4", placeholder: '{"formula": "basic_salary * 0.02", "variables": ["basic_salary"]}' })], __VLS_functionalComponentArgsRest(__VLS_226), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_230;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_231 = __VLS_asFunctionalComponent1(__VLS_230, new __VLS_230(__assign({ modelValue: (__VLS_ctx.form.frequency), options: (__VLS_ctx.frequencyOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
var __VLS_232 = __VLS_231.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.frequency), options: (__VLS_ctx.frequencyOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_231), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4 pt-2" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_235;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_236 = __VLS_asFunctionalComponent1(__VLS_235, new __VLS_235({
    modelValue: (__VLS_ctx.form.is_mandatory),
    inputId: "mandatory",
    binary: true,
}));
var __VLS_237 = __VLS_236.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.form.is_mandatory),
        inputId: "mandatory",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_236), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "mandatory" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_240;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_241 = __VLS_asFunctionalComponent1(__VLS_240, new __VLS_240({
    modelValue: (__VLS_ctx.form.is_taxable),
    inputId: "taxable",
    binary: true,
}));
var __VLS_242 = __VLS_241.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.form.is_taxable),
        inputId: "taxable",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_241), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "taxable" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_245;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_246 = __VLS_asFunctionalComponent1(__VLS_245, new __VLS_245({
    modelValue: (__VLS_ctx.form.show_on_payslip),
    inputId: "payslip",
    binary: true,
}));
var __VLS_247 = __VLS_246.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.form.show_on_payslip),
        inputId: "payslip",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_246), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "payslip" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_250;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_251 = __VLS_asFunctionalComponent1(__VLS_250, new __VLS_250({
    modelValue: (__VLS_ctx.form.is_active),
    inputId: "active",
    binary: true,
}));
var __VLS_252 = __VLS_251.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.form.is_active),
        inputId: "active",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_251), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "active" }, { class: "text-sm text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
{
    var __VLS_255 = __VLS_172.slots.footer;
    var __VLS_256 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_257 = __VLS_asFunctionalComponent1(__VLS_256, new __VLS_256(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_258 = __VLS_257.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_257), false));
    var __VLS_261 = void 0;
    var __VLS_262 = ({ click: {} },
        { onClick: (__VLS_ctx.closeDialog) });
    var __VLS_259;
    var __VLS_260;
    var __VLS_263 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_264 = __VLS_asFunctionalComponent1(__VLS_263, new __VLS_263(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditing ? 'Update' : 'Create'), severity: "info", loading: (__VLS_ctx.saving) })));
    var __VLS_265 = __VLS_264.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditing ? 'Update' : 'Create'), severity: "info", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_264), false));
    var __VLS_268 = void 0;
    var __VLS_269 = ({ click: {} },
        { onClick: (__VLS_ctx.saveDeductionType) });
    var __VLS_266;
    var __VLS_267;
    // @ts-ignore
    [categoryOptions, calculationTypeOptions, showDialog, isEditing, isEditing, isEditing, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, form, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, basisOptions, formulaJson, frequencyOptions, closeDialog, saving, saveDeductionType,];
}
// @ts-ignore
[];
var __VLS_172;
var __VLS_270;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_271 = __VLS_asFunctionalComponent1(__VLS_270, new __VLS_270(__assign({ visible: (__VLS_ctx.showDetailsDialog), header: ('Deduction Type Details'), modal: true }, { style: ({ width: '500px' }) })));
var __VLS_272 = __VLS_271.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDetailsDialog), header: ('Deduction Type Details'), modal: true }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_271), false));
var __VLS_275 = __VLS_273.slots.default;
if (__VLS_ctx.selectedDeduction) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-4 pb-4 border-b border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-list text-blue-600 text-xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-list']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-lg text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (__VLS_ctx.selectedDeduction.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.selectedDeduction.code);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_276 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_277 = __VLS_asFunctionalComponent1(__VLS_276, new __VLS_276({
        value: (__VLS_ctx.formatCategory(__VLS_ctx.selectedDeduction.category)),
        severity: (__VLS_ctx.getCategorySeverity(__VLS_ctx.selectedDeduction.category)),
    }));
    var __VLS_278 = __VLS_277.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.formatCategory(__VLS_ctx.selectedDeduction.category)),
            severity: (__VLS_ctx.getCategorySeverity(__VLS_ctx.selectedDeduction.category)),
        }], __VLS_functionalComponentArgsRest(__VLS_277), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatCalculationType(__VLS_ctx.selectedDeduction.calculation_type));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium capitalize" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
    (__VLS_ctx.selectedDeduction.frequency || 'Monthly');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_281 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_282 = __VLS_asFunctionalComponent1(__VLS_281, new __VLS_281({
        value: (__VLS_ctx.selectedDeduction.is_active ? 'Active' : 'Inactive'),
        severity: (__VLS_ctx.selectedDeduction.is_active ? 'success' : 'danger'),
    }));
    var __VLS_283 = __VLS_282.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.selectedDeduction.is_active ? 'Active' : 'Inactive'),
            severity: (__VLS_ctx.selectedDeduction.is_active ? 'success' : 'danger'),
        }], __VLS_functionalComponentArgsRest(__VLS_282), false));
    if (__VLS_ctx.selectedDeduction.calculation_type === 'fixed') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xl font-semibold text-gray-800" }));
        /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        (__VLS_ctx.formatNumber(__VLS_ctx.selectedDeduction.default_amount));
    }
    else if (__VLS_ctx.selectedDeduction.calculation_type === 'percentage') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg space-y-2" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedDeduction.percentage_value);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium capitalize" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['capitalize']} */ ;
        (__VLS_ctx.selectedDeduction.percentage_basis);
        if (__VLS_ctx.selectedDeduction.min_amount) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (__VLS_ctx.formatNumber(__VLS_ctx.selectedDeduction.min_amount));
        }
        if (__VLS_ctx.selectedDeduction.max_amount) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (__VLS_ctx.formatNumber(__VLS_ctx.selectedDeduction.max_amount));
        }
    }
    if (__VLS_ctx.selectedDeduction.description) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        (__VLS_ctx.selectedDeduction.description);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    if (__VLS_ctx.selectedDeduction.is_mandatory) {
        var __VLS_286 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_287 = __VLS_asFunctionalComponent1(__VLS_286, new __VLS_286({
            value: "Mandatory",
            severity: "warning",
        }));
        var __VLS_288 = __VLS_287.apply(void 0, __spreadArray([{
                value: "Mandatory",
                severity: "warning",
            }], __VLS_functionalComponentArgsRest(__VLS_287), false));
    }
    if (__VLS_ctx.selectedDeduction.is_taxable) {
        var __VLS_291 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_292 = __VLS_asFunctionalComponent1(__VLS_291, new __VLS_291({
            value: "Taxable",
            severity: "help",
        }));
        var __VLS_293 = __VLS_292.apply(void 0, __spreadArray([{
                value: "Taxable",
                severity: "help",
            }], __VLS_functionalComponentArgsRest(__VLS_292), false));
    }
    if (__VLS_ctx.selectedDeduction.show_on_payslip) {
        var __VLS_296 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_297 = __VLS_asFunctionalComponent1(__VLS_296, new __VLS_296({
            value: "Show on Payslip",
            severity: "info",
        }));
        var __VLS_298 = __VLS_297.apply(void 0, __spreadArray([{
                value: "Show on Payslip",
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_297), false));
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400 pt-2 border-t" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.selectedDeduction.id);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (__VLS_ctx.formatDateTime(__VLS_ctx.selectedDeduction.created_at));
    if (__VLS_ctx.selectedDeduction.updated_at) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        (__VLS_ctx.formatDateTime(__VLS_ctx.selectedDeduction.updated_at));
    }
}
{
    var __VLS_301 = __VLS_273.slots.footer;
    var __VLS_302 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_303 = __VLS_asFunctionalComponent1(__VLS_302, new __VLS_302(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })));
    var __VLS_304 = __VLS_303.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_303), false));
    var __VLS_307 = void 0;
    var __VLS_308 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDetailsDialog = false;
                // @ts-ignore
                [formatCategory, getCategorySeverity, formatCalculationType, formatNumber, formatNumber, formatNumber, showDetailsDialog, showDetailsDialog, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, selectedDeduction, formatDateTime, formatDateTime,];
            } });
    var __VLS_305;
    var __VLS_306;
    var __VLS_309 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_310 = __VLS_asFunctionalComponent1(__VLS_309, new __VLS_309(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })));
    var __VLS_311 = __VLS_310.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_310), false));
    var __VLS_314 = void 0;
    var __VLS_315 = ({ click: {} },
        { onClick: (__VLS_ctx.editFromDetails) });
    var __VLS_312;
    var __VLS_313;
    // @ts-ignore
    [editFromDetails,];
}
// @ts-ignore
[];
var __VLS_273;
var __VLS_316;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_317 = __VLS_asFunctionalComponent1(__VLS_316, new __VLS_316(__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: true }, { style: ({ width: '400px' }) })));
var __VLS_318 = __VLS_317.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: true }, { style: ({ width: '400px' }) })], __VLS_functionalComponentArgsRest(__VLS_317), false));
var __VLS_321 = __VLS_319.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-start']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-shrink-0']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-red-600" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
((_a = __VLS_ctx.selectedDeduction) === null || _a === void 0 ? void 0 : _a.name);
if (((_b = __VLS_ctx.selectedDeduction) === null || _b === void 0 ? void 0 : _b.employee_deductions_count) > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-red-500 text-sm mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (__VLS_ctx.selectedDeduction.employee_deductions_count);
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
}
{
    var __VLS_322 = __VLS_319.slots.footer;
    var __VLS_323 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_324 = __VLS_asFunctionalComponent1(__VLS_323, new __VLS_323(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_325 = __VLS_324.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_324), false));
    var __VLS_328 = void 0;
    var __VLS_329 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDeleteDialog = false;
                // @ts-ignore
                [selectedDeduction, selectedDeduction, selectedDeduction, showDeleteDialog, showDeleteDialog,];
            } });
    var __VLS_326;
    var __VLS_327;
    var __VLS_330 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_331 = __VLS_asFunctionalComponent1(__VLS_330, new __VLS_330(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", disabled: (((_c = __VLS_ctx.selectedDeduction) === null || _c === void 0 ? void 0 : _c.employee_deductions_count) > 0) })));
    var __VLS_332 = __VLS_331.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", disabled: (((_d = __VLS_ctx.selectedDeduction) === null || _d === void 0 ? void 0 : _d.employee_deductions_count) > 0) })], __VLS_functionalComponentArgsRest(__VLS_331), false));
    var __VLS_335 = void 0;
    var __VLS_336 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteDeductionType) });
    var __VLS_333;
    var __VLS_334;
    // @ts-ignore
    [selectedDeduction, deleteDeductionType,];
}
// @ts-ignore
[];
var __VLS_319;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
