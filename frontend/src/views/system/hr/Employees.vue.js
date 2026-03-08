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
var auth_1 = require("../../../stores/auth");
var axios_1 = require("axios");
var vue_router_1 = require("vue-router");
// State
var authStore = (0, auth_1.useAuthStore)();
var router = (0, vue_router_1.useRouter)();
var searchQuery = (0, vue_1.ref)('');
var filterDepartment = (0, vue_1.ref)('');
var filterStatus = (0, vue_1.ref)('');
var showAddDialog = (0, vue_1.ref)(false);
var showViewDialog = (0, vue_1.ref)(false);
var isEditMode = (0, vue_1.ref)(false);
var selectedEmployee = (0, vue_1.ref)(null);
var employees = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var employeeStats = (0, vue_1.ref)([
    { label: 'Total Employees', value: 0, icon: 'pi pi-user' },
    { label: 'Active Employees', value: 0, icon: 'pi pi-check-circle' },
    { label: 'Departments', value: 0, icon: 'pi pi-building' }
]);
// Form data
var employeeForm = (0, vue_1.ref)({
    id: null,
    firstName: '',
    lastName: '',
    position: '',
    department: null,
    email: '',
    phone: '',
    status: { label: 'Active', value: 'active' }
});
// Departments for dropdown
var departments = (0, vue_1.ref)([
    { name: 'Store Management', value: 'store management' },
    { name: 'Store Operations', value: 'Store Operations' },
    { name: 'Sales', value: 'Sales' },
    { name: 'Logistics', value: 'Logistics' },
    { name: 'Human Resources', value: 'Human Resources' },
    { name: 'Finance', value: 'Finance' },
    { name: 'Marketing', value: 'Marketing' },
    { name: 'IT', value: 'IT' },
    { name: 'Procurement', value: 'Procurement' }
]);
// Status options
var statuses = (0, vue_1.ref)([
    { label: 'Active', value: 'Active' },
    { label: 'On Leave', value: 'On-Leave' },
    { label: 'Inactive', value: 'Inactive' }
]);
var fetchEmployeesAxios = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('api/employees', {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 2:
                response = _a.sent();
                employees.value = response.data.data;
                // Update stats based on your API response structure
                if (response.data.counts) {
                    employeeStats.value = [
                        {
                            label: 'Total Employees',
                            value: response.data.counts.total || 0,
                            icon: 'pi pi-user'
                        },
                        {
                            label: 'Active Employees',
                            value: response.data.counts.active || 0,
                            icon: 'pi pi-check-circle'
                        },
                        {
                            label: 'Departments',
                            value: response.data.counts.departments || 0,
                            icon: 'pi pi-building'
                        }
                    ];
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to fetch employees:', error_1);
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// Computed property for filtered employees
var filteredEmployees = (0, vue_1.computed)(function () {
    if (!employees.value)
        return [];
    var filtered = __spreadArray([], employees.value, true);
    // Search filter
    if (searchQuery.value) {
        var term_1 = searchQuery.value.toLowerCase();
        filtered = filtered.filter(function (emp) {
            var _a, _b, _c, _d, _e, _f, _g;
            return (((_a = emp.fname) === null || _a === void 0 ? void 0 : _a.toLowerCase()) || '').includes(term_1) ||
                (((_b = emp.lname) === null || _b === void 0 ? void 0 : _b.toLowerCase()) || '').includes(term_1) ||
                "".concat(emp.fname, " ").concat(emp.lname).toLowerCase().includes(term_1) ||
                (((_c = emp.email) === null || _c === void 0 ? void 0 : _c.toLowerCase()) || '').includes(term_1) ||
                (((_d = emp.role_name) === null || _d === void 0 ? void 0 : _d.toLowerCase()) || '').includes(term_1) ||
                (((_e = emp.employee_number) === null || _e === void 0 ? void 0 : _e.toLowerCase()) || '').includes(term_1) ||
                (((_f = emp.department) === null || _f === void 0 ? void 0 : _f.toLowerCase()) || '').includes(term_1) ||
                (((_g = emp.branch) === null || _g === void 0 ? void 0 : _g.toLowerCase()) || '').includes(term_1);
        });
    }
    // Department filter
    if (filterDepartment.value) {
        filtered = filtered.filter(function (emp) { var _a, _b; return ((_a = emp.department) === null || _a === void 0 ? void 0 : _a.toLowerCase()) === ((_b = filterDepartment.value) === null || _b === void 0 ? void 0 : _b.toLowerCase()); });
    }
    // Status filter
    if (filterStatus.value) {
        filtered = filtered.filter(function (emp) {
            return emp.status === filterStatus.value;
        });
    }
    return filtered;
});
// Computed properties
var dialogHeader = (0, vue_1.computed)(function () {
    return isEditMode.value ? 'Edit Employee' : 'Add New Employee';
});
// Reset filters
var resetFilters = function () {
    searchQuery.value = '';
    filterDepartment.value = '';
    filterStatus.value = '';
};
var hasActiveFilters = (0, vue_1.computed)(function () {
    return searchQuery.value !== '' || filterStatus.value !== null;
});
// Helper functions
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getStatusClass = function (status) {
    switch (status) {
        case 'Active': return 'bg-green-100 text-green-800';
        case 'On Leave': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
var formatDate = function (dateString) {
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    catch (_a) {
        return dateString;
    }
};
// Action functions
var viewEmployee = function (employee) {
    // selectedEmployee.value = employee
    // showViewDialog.value = true
    router.push("/hr/employees/view/".concat(employee.id));
};
var editEmployee = function (employee) {
    isEditMode.value = true;
    employeeForm.value = {
        id: employee.id,
        firstName: employee.fname,
        lastName: employee.lname,
        position: employee.role_name, // Using role_name as position
        department: departments.value.find(function (d) { return d.value === employee.department; }), // Find by value, not name
        email: employee.email,
        phone: employee.phone,
        status: statuses.value.find(function (s) { return s.value === employee.status; }) // Find by value to match your data
    };
    showAddDialog.value = true;
};
var saveEmployee = function () {
    var _a, _b;
    if (!employeeForm.value.firstName || !employeeForm.value.lastName ||
        !employeeForm.value.position || !employeeForm.value.department ||
        !employeeForm.value.email) {
        alert('Please fill in all required fields');
        return;
    }
    var employeeData = {
        fname: employeeForm.value.firstName,
        lname: employeeForm.value.lastName,
        role_name: employeeForm.value.position,
        department: employeeForm.value.department.value, // Use department.value instead of name
        email: employeeForm.value.email,
        phone: employeeForm.value.phone,
        status: ((_a = employeeForm.value.status) === null || _a === void 0 ? void 0 : _a.value) || 'Active', // Use status.value
        branch: 'Main Branch' // Add default branch or get from somewhere
    };
    if (isEditMode.value) {
        // Update existing employee
        var index = employees.value.findIndex(function (e) { return e.id === employeeForm.value.id; });
        if (index !== -1) {
            employees.value[index] = __assign(__assign(__assign({}, employees.value[index]), employeeData), { id: employeeForm.value.id // Preserve the ID
             });
        }
    }
    else {
        // Add new employee
        var newId = employees.value.length > 0
            ? Math.max.apply(Math, employees.value.map(function (e) { return e.id; })) + 1
            : 1;
        var newEmployee = __assign({ id: newId, employee_number: "EMP-".concat(String(newId).padStart(3, '0')), hireDate: new Date().toISOString() }, employeeData);
        (_b = employees.value) === null || _b === void 0 ? void 0 : _b.push(newEmployee);
    }
    cancelDialog();
};
var cancelDialog = function () {
    showAddDialog.value = false;
    isEditMode.value = false;
    employeeForm.value = {
        id: null,
        firstName: '',
        lastName: '',
        position: '',
        department: null,
        email: '',
        phone: '',
        status: { label: 'Active', value: 'active' }
    };
};
(0, vue_1.onMounted)(function () {
    fetchEmployeesAxios(),
        console.log('Employees page loaded');
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6 text-sm" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.employeeStats)); _i < _a.length; _i++) {
    var stat = _a[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (stat.label) }, { class: "bg-white rounded-lg border border-info-200 p-4 shadow-sm" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-info-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (stat.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-info-700 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-info-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (stat.value);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 rounded-full bg-info-100 flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-info-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ([stat.icon, 'text-info-600']) }));
    /** @type {__VLS_StyleScopedClasses['text-info-600']} */ ;
    // @ts-ignore
    [employeeStats,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 border-b" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col md:flex-row md:items-center justify-between space-y-3 md:space-y-0" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['md:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
/** @type {__VLS_StyleScopedClasses['md:space-y-0']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
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
var __VLS_12 = __VLS_asFunctionalComponent1(__VLS_11, new __VLS_11(__assign(__assign({ modelValue: (__VLS_ctx.searchQuery), placeholder: "Search" }, { class: "w-full md:w-64" }), { size: "small" })));
var __VLS_13 = __VLS_12.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.searchQuery), placeholder: "Search" }, { class: "w-full md:w-64" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_12), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['md:w-64']} */ ;
// @ts-ignore
[searchQuery,];
var __VLS_3;
var __VLS_16;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_17 = __VLS_asFunctionalComponent1(__VLS_16, new __VLS_16(__assign(__assign({ modelValue: (__VLS_ctx.filterDepartment), options: (__VLS_ctx.departments), showClear: true, optionLabel: "name", optionValue: "value", placeholder: "Department" }, { class: "w-full md:w-60" }), { size: "small" })));
var __VLS_18 = __VLS_17.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filterDepartment), options: (__VLS_ctx.departments), showClear: true, optionLabel: "name", optionValue: "value", placeholder: "Department" }, { class: "w-full md:w-60" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_17), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['md:w-60']} */ ;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ modelValue: (__VLS_ctx.filterStatus), options: (__VLS_ctx.statuses), showClear: true, optionLabel: "label", optionValue: "value", placeholder: "All Status" }, { class: "w-full md:w-40" }), { size: "small" })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filterStatus), options: (__VLS_ctx.statuses), showClear: true, optionLabel: "label", optionValue: "value", placeholder: "All Status" }, { class: "w-full md:w-40" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['md:w-40']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_26;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign(__assign(__assign({ 'onClick': {} }, { label: "Add Employee", icon: "pi pi-user-plus", severity: "info" }), { class: "ml-auto" }), { size: "small" })));
var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: "Add Employee", icon: "pi pi-user-plus", severity: "info" }), { class: "ml-auto" }), { size: "small" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
var __VLS_31;
var __VLS_32 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showAddDialog = true;
            // @ts-ignore
            [filterDepartment, departments, filterStatus, statuses, showAddDialog,];
        } });
/** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
var __VLS_29;
var __VLS_30;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4" }));
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
var __VLS_33;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign(__assign({ value: (__VLS_ctx.filteredEmployees) }, { class: "w-full" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} employees", rowHover: true, showGridlines: true, removableSort: true, sortMode: "multiple", tableStyle: "min-width: 50rem" })));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.filteredEmployees) }, { class: "w-full" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} employees", rowHover: true, showGridlines: true, removableSort: true, sortMode: "multiple", tableStyle: "min-width: 50rem" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
var __VLS_38 = __VLS_36.slots.default;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ field: "employee_number", header: "ID" }, { style: {} })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ field: "employee_number", header: "ID" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_40), false));
var __VLS_44 = __VLS_42.slots.default;
{
    var __VLS_45 = __VLS_42.slots.body;
    var slotProps = __VLS_vSlot(__VLS_45)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold text-sm" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (slotProps.data.employee_number);
    // @ts-ignore
    [filteredEmployees, loading,];
}
// @ts-ignore
[];
var __VLS_42;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ field: "name", header: "Employee" }, { style: {} })));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ field: "name", header: "Employee" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51 = __VLS_49.slots.default;
{
    var __VLS_52 = __VLS_49.slots.body;
    var slotProps = __VLS_vSlot(__VLS_52)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ label: (__VLS_ctx.getInitials(slotProps.data.fname, slotProps.data.lname)), size: "normal", shape: "circle" }, { class: "mr-3 bg-blue-100 text-blue-800" })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(slotProps.data.fname, slotProps.data.lname)), size: "normal", shape: "circle" }, { class: "mr-3 bg-blue-100 text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (slotProps.data.fname);
    (slotProps.data.lname);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (slotProps.data.role_name || 'No Role');
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_49;
var __VLS_58;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ field: "department", header: "Department" }, { style: {} })));
var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ field: "department", header: "Department" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_59), false));
var __VLS_63 = __VLS_61.slots.default;
{
    var __VLS_64 = __VLS_61.slots.body;
    var slotProps = __VLS_vSlot(__VLS_64)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded" }));
    /** @type {__VLS_StyleScopedClasses['px-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
    (slotProps.data.department || 'N/A');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_61;
var __VLS_65;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ field: "branch", header: "Branch" }, { style: {} })));
var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ field: "branch", header: "Branch" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_66), false));
var __VLS_70 = __VLS_68.slots.default;
{
    var __VLS_71 = __VLS_68.slots.body;
    var slotProps = __VLS_vSlot(__VLS_71)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (slotProps.data.branch || 'N/A');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_68;
var __VLS_72;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign({ field: "email", header: "Email" }, { style: {} })));
var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign({ field: "email", header: "Email" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_73), false));
var __VLS_77 = __VLS_75.slots.default;
{
    var __VLS_78 = __VLS_75.slots.body;
    var slotProps = __VLS_vSlot(__VLS_78)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm truncate" }, { title: (slotProps.data.email) }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
    (slotProps.data.email || 'No email');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_75;
var __VLS_79;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ field: "status", header: "Status" }, { style: {} })));
var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_80), false));
var __VLS_84 = __VLS_82.slots.default;
{
    var __VLS_85 = __VLS_82.slots.body;
    var slotProps = __VLS_vSlot(__VLS_85)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ("px-2 py-1 rounded text-xs font-medium ".concat(__VLS_ctx.getStatusClass(slotProps.data.status))) }));
    (slotProps.data.status);
    // @ts-ignore
    [getStatusClass,];
}
// @ts-ignore
[];
var __VLS_82;
var __VLS_86;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_87 = __VLS_asFunctionalComponent1(__VLS_86, new __VLS_86(__assign({ header: "Actions" }, { style: {} })));
var __VLS_88 = __VLS_87.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_87), false));
var __VLS_91 = __VLS_89.slots.default;
{
    var __VLS_92 = __VLS_89.slots.body;
    var slotProps_1 = __VLS_vSlot(__VLS_92)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-1']} */ ;
    var __VLS_93 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true })));
    var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_94), false));
    var __VLS_98 = void 0;
    var __VLS_99 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewEmployee(slotProps_1.data);
                // @ts-ignore
                [viewEmployee,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View Details') }), null, null);
    var __VLS_96;
    var __VLS_97;
    var __VLS_100 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_101 = __VLS_asFunctionalComponent1(__VLS_100, new __VLS_100(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })));
    var __VLS_102 = __VLS_101.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_101), false));
    var __VLS_105 = void 0;
    var __VLS_106 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editEmployee(slotProps_1.data);
                // @ts-ignore
                [vTooltip, editEmployee,];
            } });
    __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit Employee') }), null, null);
    var __VLS_103;
    var __VLS_104;
    // @ts-ignore
    [vTooltip,];
}
// @ts-ignore
[];
var __VLS_89;
{
    var __VLS_107 = __VLS_36.slots.empty;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users text-4xl text-gray-400 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-400 text-sm mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_108 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108(__assign({ 'onClick': {} }, { label: "Add New Employee", icon: "pi pi-plus", severity: "info" })));
    var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add New Employee", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_109), false));
    var __VLS_113 = void 0;
    var __VLS_114 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showAddDialog = true;
                // @ts-ignore
                [showAddDialog,];
            } });
    var __VLS_111;
    var __VLS_112;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_36;
var __VLS_115;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115(__assign({ modal: true, visible: (__VLS_ctx.showAddDialog), header: (__VLS_ctx.dialogHeader) }, { style: ({ width: '500px' }) })));
var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([__assign({ modal: true, visible: (__VLS_ctx.showAddDialog), header: (__VLS_ctx.dialogHeader) }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_116), false));
var __VLS_120 = __VLS_118.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_121;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121(__assign({ modelValue: (__VLS_ctx.employeeForm.firstName) }, { class: "w-full" })));
var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.firstName) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_122), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_126;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_127 = __VLS_asFunctionalComponent1(__VLS_126, new __VLS_126(__assign({ modelValue: (__VLS_ctx.employeeForm.lastName) }, { class: "w-full" })));
var __VLS_128 = __VLS_127.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.lastName) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_127), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_131;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ modelValue: (__VLS_ctx.employeeForm.position) }, { class: "w-full" })));
var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.position) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_136;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136(__assign({ modelValue: (__VLS_ctx.employeeForm.department), options: (__VLS_ctx.departments), optionLabel: "name" }, { class: "w-full" })));
var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.department), options: (__VLS_ctx.departments), optionLabel: "name" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_137), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_141;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_142 = __VLS_asFunctionalComponent1(__VLS_141, new __VLS_141(__assign({ modelValue: (__VLS_ctx.employeeForm.email) }, { class: "w-full" })));
var __VLS_143 = __VLS_142.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.email) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_142), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_146;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ modelValue: (__VLS_ctx.employeeForm.phone) }, { class: "w-full" })));
var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.phone) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_147), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
if (__VLS_ctx.isEditMode) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_151 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151(__assign({ modelValue: (__VLS_ctx.employeeForm.status), options: (__VLS_ctx.statuses), optionLabel: "label" }, { class: "w-full" })));
    var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.employeeForm.status), options: (__VLS_ctx.statuses), optionLabel: "label" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_152), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
{
    var __VLS_156 = __VLS_118.slots.footer;
    var __VLS_157 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_158), false));
    var __VLS_162 = void 0;
    var __VLS_163 = ({ click: {} },
        { onClick: (__VLS_ctx.cancelDialog) });
    var __VLS_160;
    var __VLS_161;
    var __VLS_164 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update' : 'Add Employee') })));
    var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update' : 'Add Employee') })], __VLS_functionalComponentArgsRest(__VLS_165), false));
    var __VLS_169 = void 0;
    var __VLS_170 = ({ click: {} },
        { onClick: (__VLS_ctx.saveEmployee) });
    var __VLS_167;
    var __VLS_168;
    // @ts-ignore
    [departments, statuses, showAddDialog, dialogHeader, employeeForm, employeeForm, employeeForm, employeeForm, employeeForm, employeeForm, employeeForm, isEditMode, isEditMode, cancelDialog, saveEmployee,];
}
// @ts-ignore
[];
var __VLS_118;
var __VLS_171;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_172 = __VLS_asFunctionalComponent1(__VLS_171, new __VLS_171(__assign({ modal: true, visible: (__VLS_ctx.showViewDialog), header: "Employee Details" }, { style: ({ width: '500px' }) })));
var __VLS_173 = __VLS_172.apply(void 0, __spreadArray([__assign({ modal: true, visible: (__VLS_ctx.showViewDialog), header: "Employee Details" }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_172), false));
var __VLS_176 = __VLS_174.slots.default;
if (__VLS_ctx.selectedEmployee) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
    var __VLS_177 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177(__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedEmployee.fname)), size: "xlarge", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-2xl" })));
    var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(__VLS_ctx.selectedEmployee.fname)), size: "xlarge", shape: "circle" }, { class: "bg-blue-100 text-blue-800 text-2xl" })], __VLS_functionalComponentArgsRest(__VLS_178), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-xl font-bold" }));
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    (__VLS_ctx.selectedEmployee.fname + __VLS_ctx.selectedEmployee.lname);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (__VLS_ctx.selectedEmployee.role_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.employee_number);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.department);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.email);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.phone || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: ("font-medium ".concat(__VLS_ctx.getStatusClass(__VLS_ctx.selectedEmployee.status))) }));
    (__VLS_ctx.selectedEmployee.status);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedEmployee.hireDate));
}
// @ts-ignore
[getInitials, getStatusClass, showViewDialog, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, formatDate,];
var __VLS_174;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
