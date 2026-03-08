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
var usetoast_1 = require("primevue/usetoast");
var useconfirm_1 = require("primevue/useconfirm");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
// State
var toast = (0, usetoast_1.useToast)();
var confirm = (0, useconfirm_1.useConfirm)();
var authStore = (0, auth_1.useAuthStore)();
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var showFormDialog = (0, vue_1.ref)(false);
var showViewDialog = (0, vue_1.ref)(false);
var isEditMode = (0, vue_1.ref)(false);
var departments = (0, vue_1.ref)([]);
var employees = (0, vue_1.ref)([]);
var selectedDepartment = (0, vue_1.ref)(null);
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    status: null
});
var statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' }
];
// Form Data
var formData = (0, vue_1.ref)({
    name: '',
    code: '',
    description: '',
    manager_id: null,
    status: 'active'
});
// Computed
var filteredDepartments = (0, vue_1.computed)(function () {
    return departments.value.filter(function (dept) {
        var _a;
        var matchesSearch = !filters.value.search ||
            dept.name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
            ((_a = dept.code) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(filters.value.search.toLowerCase()));
        var matchesStatus = !filters.value.status || dept.status === filters.value.status;
        return matchesSearch && matchesStatus;
    });
});
var stats = (0, vue_1.computed)(function () {
    return {
        total: departments.value.length,
        active: departments.value.filter(function (d) { return d.status === 'active'; }).length,
        inactive: departments.value.filter(function (d) { return d.status === 'inactive'; }).length,
        totalEmployees: departments.value.reduce(function (sum, d) { return sum + (d.employee_count || 0); }, 0)
    };
});
// Methods
var fetchDepartments = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('/api/departments', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    departments.value = response.data.data;
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to fetch departments',
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
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.get('/api/employees', {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) },
                        params: { per_page: 1000 }
                    })];
            case 1:
                response = _a.sent();
                if (response.data.success) {
                    employees.value = (response.data.data.data || response.data.data).map(function (emp) { return ({
                        id: emp.id,
                        full_name: "".concat(emp.fname, " ").concat(emp.lname)
                    }); });
                }
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to fetch employees:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var openAddDialog = function () {
    isEditMode.value = false;
    resetForm();
    showFormDialog.value = true;
};
var editDepartment = function (dept) {
    isEditMode.value = true;
    formData.value = {
        name: dept.name,
        code: dept.code || '',
        description: dept.description || '',
        manager_id: dept.manager_id || null,
        status: dept.status
    };
    selectedDepartment.value = dept;
    showFormDialog.value = true;
};
var editFromView = function () {
    if (selectedDepartment.value) {
        showViewDialog.value = false;
        editDepartment(selectedDepartment.value);
    }
};
var viewDepartment = function (dept) {
    selectedDepartment.value = dept;
    showViewDialog.value = true;
};
var saveDepartment = function () { return __awaiter(void 0, void 0, void 0, function () {
    var url, method, response, error_3;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!formData.value.name.trim()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation Error',
                        detail: 'Department name is required',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                saving.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 5, 6, 7]);
                url = isEditMode.value ? "/api/departments/".concat((_a = selectedDepartment.value) === null || _a === void 0 ? void 0 : _a.id) : '/api/departments';
                method = isEditMode.value ? 'put' : 'post';
                return [4 /*yield*/, axios_1.default[method](url, formData.value, {
                        headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _d.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "Department ".concat(isEditMode.value ? 'updated' : 'created', " successfully"),
                    life: 3000
                });
                showFormDialog.value = false;
                return [4 /*yield*/, fetchDepartments()];
            case 3:
                _d.sent();
                _d.label = 4;
            case 4: return [3 /*break*/, 7];
            case 5:
                error_3 = _d.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_3.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || "Failed to ".concat(isEditMode.value ? 'update' : 'create', " department"),
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
var confirmDelete = function (dept) {
    confirm.require({
        message: "Are you sure you want to delete \"".concat(dept.name, "\"?"),
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: function () { return __awaiter(void 0, void 0, void 0, function () {
            var response, error_4;
            var _a, _b;
            return __generator(this, function (_c) {
                switch (_c.label) {
                    case 0:
                        _c.trys.push([0, 4, , 5]);
                        return [4 /*yield*/, axios_1.default.delete("/api/departments/".concat(dept.id), {
                                headers: { 'Authorization': "Bearer ".concat(authStore.token) }
                            })];
                    case 1:
                        response = _c.sent();
                        if (!response.data.success) return [3 /*break*/, 3];
                        toast.add({
                            severity: 'success',
                            summary: 'Deleted',
                            detail: 'Department deleted successfully',
                            life: 3000
                        });
                        return [4 /*yield*/, fetchDepartments()];
                    case 2:
                        _c.sent();
                        _c.label = 3;
                    case 3: return [3 /*break*/, 5];
                    case 4:
                        error_4 = _c.sent();
                        toast.add({
                            severity: 'error',
                            summary: 'Error',
                            detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete department',
                            life: 3000
                        });
                        return [3 /*break*/, 5];
                    case 5: return [2 /*return*/];
                }
            });
        }); }
    });
};
var resetForm = function () {
    formData.value = {
        name: '',
        code: '',
        description: '',
        manager_id: null,
        status: 'active'
    };
};
var clearFilters = function () {
    filters.value.search = '';
    filters.value.status = null;
};
var formatDate = function (dateString) {
    if (!dateString)
        return 'N/A';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    catch (_a) {
        return dateString;
    }
};
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase().slice(0, 2);
};
// Lifecycle
(0, vue_1.onMounted)(function () {
    fetchDepartments();
    fetchEmployees();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Add Department", icon: "pi pi-plus", severity: "info" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Department", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.openAddDialog) });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.stats.total);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-building text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-building']} */ ;
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
(__VLS_ctx.stats.active);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-lg flex items-center justify-between bg-green-50" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-purple-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
(__VLS_ctx.stats.totalEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-lg flex items-center justify-center bg-purple-50" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-purple-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
(__VLS_ctx.stats.inactive);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-ban text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-ban']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 flex-wrap" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ iconPosition: "left" }, { class: "flex-1" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ iconPosition: "left" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
var __VLS_12 = __VLS_10.slots.default;
var __VLS_13;
/** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13({}));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_14), false));
var __VLS_18 = __VLS_16.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
// @ts-ignore
[openAddDialog, stats, stats, stats, stats,];
var __VLS_16;
var __VLS_19;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search departments..." }, { class: "w-full" }), { size: "small" })));
var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search departments..." }, { class: "w-full" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_20), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
// @ts-ignore
[filters,];
var __VLS_10;
var __VLS_24;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign(__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true }, { class: "w-48" }), { size: "small" })));
var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true }, { class: "w-48" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
if (__VLS_ctx.filters.search || __VLS_ctx.filters.status) {
    var __VLS_29 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29(__assign({ 'onClick': {} }, { label: "Clear", icon: "pi pi-filter-slash", severity: "secondary", text: true, size: "small" })));
    var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear", icon: "pi pi-filter-slash", severity: "secondary", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_30), false));
    var __VLS_34 = void 0;
    var __VLS_35 = ({ click: {} },
        { onClick: (__VLS_ctx.clearFilters) });
    var __VLS_32;
    var __VLS_33;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
var __VLS_36;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36(__assign({ value: (__VLS_ctx.filteredDepartments), loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} departments", rowHover: true, showGridlines: true, sortMode: "multiple" }, { class: "text-sm" })));
var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.filteredDepartments), loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} departments", rowHover: true, showGridlines: true, sortMode: "multiple" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_37), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_41 = __VLS_39.slots.default;
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ field: "name", header: "Department Name", sortable: true }, { style: {} })));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ field: "name", header: "Department Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_43), false));
var __VLS_47 = __VLS_45.slots.default;
{
    var __VLS_48 = __VLS_45.slots.body;
    var data = __VLS_vSlot(__VLS_48)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-building text-blue-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-building']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.name);
    if (data.code) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (data.code);
    }
    // @ts-ignore
    [filters, filters, filters, statusOptions, clearFilters, filteredDepartments, loading,];
}
// @ts-ignore
[];
var __VLS_45;
var __VLS_49;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ field: "description", header: "Description" }, { style: {} })));
var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ field: "description", header: "Description" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_50), false));
var __VLS_54 = __VLS_52.slots.default;
{
    var __VLS_55 = __VLS_52.slots.body;
    var data = __VLS_vSlot(__VLS_55)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (data.description || 'N/A');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_52;
var __VLS_56;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign({ field: "manager_name", header: "Manager", sortable: true }, { style: {} })));
var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign({ field: "manager_name", header: "Manager", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_57), false));
var __VLS_61 = __VLS_59.slots.default;
{
    var __VLS_62 = __VLS_59.slots.body;
    var data = __VLS_vSlot(__VLS_62)[0].data;
    if (data.manager_name) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_63 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63(__assign({ label: (__VLS_ctx.getInitials(data.manager_name)), size: "small", shape: "circle" }, { class: "bg-purple-100 text-purple-600" })));
        var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.manager_name)), size: "small", shape: "circle" }, { class: "bg-purple-100 text-purple-600" })], __VLS_functionalComponentArgsRest(__VLS_64), false));
        /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (data.manager_name);
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_59;
var __VLS_68;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68(__assign({ field: "employee_count", header: "Employees", sortable: true }, { style: {} })));
var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([__assign({ field: "employee_count", header: "Employees", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_69), false));
var __VLS_73 = __VLS_71.slots.default;
{
    var __VLS_74 = __VLS_71.slots.body;
    var data = __VLS_vSlot(__VLS_74)[0].data;
    var __VLS_75 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75({
        value: (data.employee_count || 0),
        severity: "info",
    }));
    var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([{
            value: (data.employee_count || 0),
            severity: "info",
        }], __VLS_functionalComponentArgsRest(__VLS_76), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_71;
var __VLS_80;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_81), false));
var __VLS_85 = __VLS_83.slots.default;
{
    var __VLS_86 = __VLS_83.slots.body;
    var data = __VLS_vSlot(__VLS_86)[0].data;
    var __VLS_87 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
        value: (data.status),
        severity: (data.status === 'active' ? 'success' : 'secondary'),
    }));
    var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
            value: (data.status),
            severity: (data.status === 'active' ? 'success' : 'secondary'),
        }], __VLS_functionalComponentArgsRest(__VLS_88), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_83;
var __VLS_92;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92(__assign({ field: "created_at", header: "Created Date", sortable: true }, { style: {} })));
var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([__assign({ field: "created_at", header: "Created Date", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_93), false));
var __VLS_97 = __VLS_95.slots.default;
{
    var __VLS_98 = __VLS_95.slots.body;
    var data = __VLS_vSlot(__VLS_98)[0].data;
    (__VLS_ctx.formatDate(data.created_at));
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_95;
var __VLS_99;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ header: "Actions", exportable: (false) }, { style: {} })));
var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ header: "Actions", exportable: (false) }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_100), false));
var __VLS_104 = __VLS_102.slots.default;
{
    var __VLS_105 = __VLS_102.slots.body;
    var data_1 = __VLS_vSlot(__VLS_105)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    var __VLS_106 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })));
    var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_107), false));
    var __VLS_111 = void 0;
    var __VLS_112 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDepartment(data_1);
                // @ts-ignore
                [viewDepartment,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View Details') }), null, null);
    var __VLS_109;
    var __VLS_110;
    var __VLS_113 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_114 = __VLS_asFunctionalComponent1(__VLS_113, new __VLS_113(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "contrast", text: true, rounded: true, size: "small" })));
    var __VLS_115 = __VLS_114.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "contrast", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_114), false));
    var __VLS_118 = void 0;
    var __VLS_119 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editDepartment(data_1);
                // @ts-ignore
                [vTooltip, editDepartment,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
    var __VLS_116;
    var __VLS_117;
    var __VLS_120 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
    var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_121), false));
    var __VLS_125 = void 0;
    var __VLS_126 = ({ click: {} },
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
    var __VLS_123;
    var __VLS_124;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_102;
{
    var __VLS_127 = __VLS_39.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-building text-4xl text-gray-300 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-building']} */ ;
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
var __VLS_39;
var __VLS_128;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128(__assign(__assign({ visible: (__VLS_ctx.showFormDialog), modal: true, header: (__VLS_ctx.isEditMode ? 'Edit Department' : 'Add Department') }, { style: ({ width: '500px' }) }), { draggable: (false) })));
var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showFormDialog), modal: true, header: (__VLS_ctx.isEditMode ? 'Edit Department' : 'Add Department') }, { style: ({ width: '500px' }) }), { draggable: (false) })], __VLS_functionalComponentArgsRest(__VLS_129), false));
var __VLS_133 = __VLS_131.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_134;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134(__assign({ modelValue: (__VLS_ctx.formData.name), placeholder: "Enter department name" }, { class: "w-full" })));
var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.name), placeholder: "Enter department name" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_135), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_139;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139(__assign({ modelValue: (__VLS_ctx.formData.code), placeholder: "e.g., HR, IT, FIN" }, { class: "w-full" })));
var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.code), placeholder: "e.g., HR, IT, FIN" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_140), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_144;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144(__assign({ modelValue: (__VLS_ctx.formData.description), rows: "3", placeholder: "Enter department description" }, { class: "w-full" })));
var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.description), rows: "3", placeholder: "Enter department description" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_145), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_149;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_150 = __VLS_asFunctionalComponent1(__VLS_149, new __VLS_149(__assign({ modelValue: (__VLS_ctx.formData.manager_id), options: (__VLS_ctx.employees), optionLabel: "full_name", optionValue: "id", placeholder: "Select manager", showClear: true, filter: true }, { class: "w-full" })));
var __VLS_151 = __VLS_150.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.manager_id), options: (__VLS_ctx.employees), optionLabel: "full_name", optionValue: "id", placeholder: "Select manager", showClear: true, filter: true }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_150), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_154;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_155 = __VLS_asFunctionalComponent1(__VLS_154, new __VLS_154(__assign({ modelValue: (__VLS_ctx.formData.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "Select status" }, { class: "w-full" })));
var __VLS_156 = __VLS_155.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "Select status" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_155), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_159 = __VLS_131.slots.footer;
    var __VLS_160 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_161), false));
    var __VLS_165 = void 0;
    var __VLS_166 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showFormDialog = false;
                // @ts-ignore
                [statusOptions, showFormDialog, showFormDialog, isEditMode, formData, formData, formData, formData, formData, employees,];
            } });
    var __VLS_163;
    var __VLS_164;
    var __VLS_167 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update' : 'Create'), severity: "info", loading: (__VLS_ctx.saving) })));
    var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update' : 'Create'), severity: "info", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_168), false));
    var __VLS_172 = void 0;
    var __VLS_173 = ({ click: {} },
        { onClick: (__VLS_ctx.saveDepartment) });
    var __VLS_170;
    var __VLS_171;
    // @ts-ignore
    [isEditMode, saving, saveDepartment,];
}
// @ts-ignore
[];
var __VLS_131;
var __VLS_174;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_175 = __VLS_asFunctionalComponent1(__VLS_174, new __VLS_174(__assign(__assign({ visible: (__VLS_ctx.showViewDialog), modal: true, header: "Department Details" }, { style: ({ width: '600px' }) }), { draggable: (false) })));
var __VLS_176 = __VLS_175.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showViewDialog), modal: true, header: "Department Details" }, { style: ({ width: '600px' }) }), { draggable: (false) })], __VLS_functionalComponentArgsRest(__VLS_175), false));
var __VLS_179 = __VLS_177.slots.default;
if (__VLS_ctx.selectedDepartment) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-blue-900" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-900']} */ ;
    (__VLS_ctx.selectedDepartment.name);
    if (__VLS_ctx.selectedDepartment.code) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-blue-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.selectedDepartment.code);
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
    var __VLS_180 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180({
        value: (__VLS_ctx.selectedDepartment.status),
        severity: (__VLS_ctx.selectedDepartment.status === 'active' ? 'success' : 'secondary'),
    }));
    var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.selectedDepartment.status),
            severity: (__VLS_ctx.selectedDepartment.status === 'active' ? 'success' : 'secondary'),
        }], __VLS_functionalComponentArgsRest(__VLS_181), false));
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
    (__VLS_ctx.selectedDepartment.employee_count || 0);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    if (__VLS_ctx.selectedDepartment.manager_name) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        var __VLS_185 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        Avatar;
        // @ts-ignore
        var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedDepartment.manager_name)), size: "normal", shape: "circle" }, { class: "bg-purple-100 text-purple-600" })));
        var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedDepartment.manager_name)), size: "normal", shape: "circle" }, { class: "bg-purple-100 text-purple-600" })], __VLS_functionalComponentArgsRest(__VLS_186), false));
        /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedDepartment.manager_name);
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    if (__VLS_ctx.selectedDepartment.description) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-3 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.selectedDepartment.description);
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
    (__VLS_ctx.formatDate(__VLS_ctx.selectedDepartment.created_at));
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
    (__VLS_ctx.formatDate(__VLS_ctx.selectedDepartment.updated_at));
}
{
    var __VLS_190 = __VLS_177.slots.footer;
    var __VLS_191 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_192 = __VLS_asFunctionalComponent1(__VLS_191, new __VLS_191(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })));
    var __VLS_193 = __VLS_192.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_192), false));
    var __VLS_196 = void 0;
    var __VLS_197 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showViewDialog = false;
                // @ts-ignore
                [getInitials, formatDate, formatDate, showViewDialog, showViewDialog, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment, selectedDepartment,];
            } });
    var __VLS_194;
    var __VLS_195;
    var __VLS_198 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })));
    var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_199), false));
    var __VLS_203 = void 0;
    var __VLS_204 = ({ click: {} },
        { onClick: (__VLS_ctx.editFromView) });
    var __VLS_201;
    var __VLS_202;
    // @ts-ignore
    [editFromView,];
}
// @ts-ignore
[];
var __VLS_177;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
