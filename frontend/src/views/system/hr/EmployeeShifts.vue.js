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
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
var _a, _b, _c, _d, _e;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var button_1 = require("primevue/button");
var avatar_1 = require("primevue/avatar");
var tag_1 = require("primevue/tag");
var inputtext_1 = require("primevue/inputtext");
var select_1 = require("primevue/select");
var dialog_1 = require("primevue/dialog");
var tabs_1 = require("primevue/tabs");
var tablist_1 = require("primevue/tablist");
var tab_1 = require("primevue/tab");
var tabpanels_1 = require("primevue/tabpanels");
var tabpanel_1 = require("primevue/tabpanel");
var progressbar_1 = require("primevue/progressbar");
var router = (0, vue_router_1.useRouter)();
var activeTab = (0, vue_1.ref)('branches');
var showEmployeeModal = (0, vue_1.ref)(false);
var selectedEmployee = (0, vue_1.ref)(null);
// Filters
var filters = (0, vue_1.ref)({
    search: '',
    branch: null,
    department: null,
    shiftType: null
});
// Mock Data
var branches = (0, vue_1.ref)([
    { label: 'Main Branch', value: 'main' },
    { label: 'North Branch', value: 'north' },
    { label: 'South Branch', value: 'south' },
    { label: 'East Branch', value: 'east' }
]);
var departments = (0, vue_1.ref)([
    { label: 'Production', value: 'production' },
    { label: 'Warehouse', value: 'warehouse' },
    { label: 'Sales', value: 'sales' },
    { label: 'Finance', value: 'finance' },
    { label: 'HR', value: 'hr' },
    { label: 'IT', value: 'it' }
]);
var shiftTypeFilters = (0, vue_1.ref)([
    { label: 'Morning Shift', value: 'Morning' },
    { label: 'Mid Shift', value: 'Mid' },
    { label: 'Evening Shift', value: 'Evening' },
    { label: 'Night Shift', value: 'Night' }
]);
var shiftTypes = (0, vue_1.ref)([
    { name: 'Morning', color: 'blue', count: 8 },
    { name: 'Mid', color: 'orange', count: 5 },
    { name: 'Evening', color: 'purple', count: 4 },
    { name: 'Night', color: 'indigo', count: 3 }
]);
var employees = (0, vue_1.ref)([
    {
        id: 1,
        employeeId: 'EMP-001',
        name: 'John Smith',
        department: 'production',
        departmentLabel: 'Production',
        branch: 'main',
        shiftType: 'Morning',
        shiftStart: '08:00',
        shiftEnd: '17:00',
        workingDays: ['M', 'T', 'W', 'T', 'F'],
        status: 'Active'
    },
    {
        id: 2,
        employeeId: 'EMP-002',
        name: 'Sarah Johnson',
        department: 'warehouse',
        departmentLabel: 'Warehouse',
        branch: 'north',
        shiftType: 'Mid',
        shiftStart: '12:00',
        shiftEnd: '21:00',
        workingDays: ['M', 'T', 'W', 'T', 'F'],
        status: 'Active'
    },
    {
        id: 3,
        employeeId: 'EMP-003',
        name: 'Michael Chen',
        department: 'sales',
        departmentLabel: 'Sales',
        branch: 'south',
        shiftType: 'Evening',
        shiftStart: '15:00',
        shiftEnd: '00:00',
        workingDays: ['T', 'W', 'T', 'F', 'S'],
        status: 'Active'
    },
    {
        id: 4,
        employeeId: 'EMP-004',
        name: 'Emily Davis',
        department: 'finance',
        departmentLabel: 'Finance',
        branch: 'main',
        shiftType: 'Morning',
        shiftStart: '09:00',
        shiftEnd: '18:00',
        workingDays: ['M', 'T', 'W', 'T', 'F'],
        status: 'Active'
    },
    {
        id: 5,
        employeeId: 'EMP-005',
        name: 'James Wilson',
        department: 'it',
        departmentLabel: 'IT',
        branch: 'east',
        shiftType: 'Night',
        shiftStart: '22:00',
        shiftEnd: '07:00',
        workingDays: ['M', 'T', 'W', 'T', 'F'],
        status: 'Active'
    }
]);
var upcomingShifts = (0, vue_1.ref)([
    { id: 1, date: '2024-12-18', type: 'Morning', start: '08:00', end: '17:00' },
    { id: 2, date: '2024-12-19', type: 'Morning', start: '08:00', end: '17:00' },
    { id: 3, date: '2024-12-20', type: 'Morning', start: '08:00', end: '17:00' }
]);
// Computed
var filteredBranches = (0, vue_1.computed)(function () {
    return branches.value.filter(function (b) {
        if (filters.value.branch && b.value !== filters.value.branch)
            return false;
        return true;
    });
});
var filteredDepartments = (0, vue_1.computed)(function () {
    return departments.value.filter(function (d) {
        if (filters.value.department && d.value !== filters.value.department)
            return false;
        return true;
    });
});
var totalEmployees = (0, vue_1.computed)(function () { return employees.value.length; });
var activeShifts = (0, vue_1.computed)(function () {
    return employees.value.filter(function (e) { return e.status === 'Active'; }).length;
});
var nightShiftCount = (0, vue_1.computed)(function () {
    return employees.value.filter(function (e) { return e.shiftType === 'Night'; }).length;
});
var partTimeCount = (0, vue_1.computed)(function () {
    return employees.value.filter(function (e) { return e.shiftType === 'Evening'; }).length;
});
// Methods
var getInitials = function (name) {
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getBranchLabel = function (branchValue) {
    var branch = branches.value.find(function (b) { return b.value === branchValue; });
    return (branch === null || branch === void 0 ? void 0 : branch.label) || branchValue;
};
var getEmployeesByBranch = function (branchValue) {
    var filtered = employees.value.filter(function (e) { return e.branch === branchValue; });
    // Apply search filter
    if (filters.value.search) {
        var search_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (e) {
            return e.name.toLowerCase().includes(search_1) ||
                e.employeeId.toLowerCase().includes(search_1);
        });
    }
    // Apply department filter
    if (filters.value.department) {
        filtered = filtered.filter(function (e) { return e.department === filters.value.department; });
    }
    // Apply shift type filter
    if (filters.value.shiftType) {
        filtered = filtered.filter(function (e) { return e.shiftType === filters.value.shiftType; });
    }
    return filtered;
};
var getEmployeesByDepartment = function (deptValue) {
    var filtered = employees.value.filter(function (e) { return e.department === deptValue; });
    // Apply search filter
    if (filters.value.search) {
        var search_2 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (e) {
            return e.name.toLowerCase().includes(search_2) ||
                e.employeeId.toLowerCase().includes(search_2);
        });
    }
    // Apply branch filter
    if (filters.value.branch) {
        filtered = filtered.filter(function (e) { return e.branch === filters.value.branch; });
    }
    // Apply shift type filter
    if (filters.value.shiftType) {
        filtered = filtered.filter(function (e) { return e.shiftType === filters.value.shiftType; });
    }
    return filtered;
};
var getShiftSeverity = function (type) {
    var map = {
        'Morning': 'info',
        'Mid': 'warning',
        'Evening': 'help',
        'Night': 'secondary'
    };
    return map[type] || 'info';
};
var getStatusSeverity = function (status) {
    return status === 'Active' ? 'success' : 'danger';
};
var getShiftCount = function (type) {
    return employees.value.filter(function (e) { return e.shiftType === type; }).length;
};
var getShiftPercentage = function (type) {
    var count = getShiftCount(type);
    return (count / employees.value.length) * 100;
};
var getDepartmentCount = function (deptValue) {
    return employees.value.filter(function (e) { return e.department === deptValue; }).length;
};
var getDepartmentShiftType = function (deptValue) {
    var deptEmployees = employees.value.filter(function (e) { return e.department === deptValue; });
    if (deptEmployees.length === 0)
        return 'No shifts';
    var shiftCounts = deptEmployees.reduce(function (acc, e) {
        acc[e.shiftType] = (acc[e.shiftType] || 0) + 1;
        return acc;
    }, {});
    var mostCommon = Object.entries(shiftCounts).sort(function (a, b) { return b[1] - a[1]; })[0];
    return mostCommon ? mostCommon[0] : 'Mixed';
};
var getBranchCount = function (branchValue) {
    return employees.value.filter(function (e) { return e.branch === branchValue; }).length;
};
var resetFilters = function () {
    filters.value = { search: '', branch: null, department: null, shiftType: null };
};
var viewEmployeeShifts = function (emp) {
    selectedEmployee.value = emp;
    showEmployeeModal.value = true;
};
var editEmployeeShift = function (emp) {
    router.push("/hr/shifts/edit/".concat(emp.id));
};
var viewShiftHistory = function (emp) {
    router.push("/hr/shifts/history/".concat(emp.id));
};
var goBack = function () {
    router.push('/hr/shifts');
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 max-w-7xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Back to Schedule", icon: "pi pi-arrow-left", severity: "info" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back to Schedule", icon: "pi pi-arrow-left", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.goBack) });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-3 items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 flex-wrap" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "relative" }));
/** @type {__VLS_StyleScopedClasses['relative']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
/** @type {__VLS_StyleScopedClasses['absolute']} */ ;
/** @type {__VLS_StyleScopedClasses['left-3']} */ ;
/** @type {__VLS_StyleScopedClasses['top-1/2']} */ ;
/** @type {__VLS_StyleScopedClasses['-translate-y-1/2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_13;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "pl-8 rounded-lg w-64" })));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search employee..." }, { class: "pl-8 rounded-lg w-64" })], __VLS_functionalComponentArgsRest(__VLS_14), false));
/** @type {__VLS_StyleScopedClasses['pl-8']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
// @ts-ignore
[goBack, filters,];
var __VLS_10;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign(__assign({ modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), optionLabel: "label", optionValue: "value", placeholder: "All Branches" }, { class: "rounded-lg w-48" }), { showClear: true })));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.branch), options: (__VLS_ctx.branches), optionLabel: "label", optionValue: "value", placeholder: "All Branches" }, { class: "rounded-lg w-48" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_19), false));
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_23;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23(__assign(__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), optionLabel: "label", optionValue: "value", placeholder: "All Departments" }, { class: "rounded-lg w-48" }), { showClear: true })));
var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departments), optionLabel: "label", optionValue: "value", placeholder: "All Departments" }, { class: "rounded-lg w-48" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_24), false));
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign(__assign({ modelValue: (__VLS_ctx.filters.shiftType), options: (__VLS_ctx.shiftTypeFilters), optionLabel: "label", optionValue: "value", placeholder: "Shift Type" }, { class: "rounded-lg w-48" }), { showClear: true })));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.shiftType), options: (__VLS_ctx.shiftTypeFilters), optionLabel: "label", optionValue: "value", placeholder: "Shift Type" }, { class: "rounded-lg w-48" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_29), false));
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['w-48']} */ ;
var __VLS_33;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-filter-slash", severity: "info", outlined: true })));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-filter-slash", severity: "info", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_34), false));
var __VLS_38;
var __VLS_39 = ({ click: {} },
    { onClick: (__VLS_ctx.resetFilters) });
var __VLS_36;
var __VLS_37;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.Tabs | typeof __VLS_components.Tabs} */
tabs_1.default;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40({
    value: (__VLS_ctx.activeTab),
}));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.activeTab),
    }], __VLS_functionalComponentArgsRest(__VLS_41), false));
var __VLS_45 = __VLS_43.slots.default;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.TabList | typeof __VLS_components.TabList} */
tablist_1.default;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46({}));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51 = __VLS_49.slots.default;
var __VLS_52;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52({
    value: "branches",
}));
var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([{
        value: "branches",
    }], __VLS_functionalComponentArgsRest(__VLS_53), false));
var __VLS_57 = __VLS_55.slots.default;
// @ts-ignore
[filters, filters, filters, branches, departments, shiftTypeFilters, resetFilters, activeTab,];
var __VLS_55;
var __VLS_58;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
    value: "departments",
}));
var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
        value: "departments",
    }], __VLS_functionalComponentArgsRest(__VLS_59), false));
var __VLS_63 = __VLS_61.slots.default;
// @ts-ignore
[];
var __VLS_61;
var __VLS_64;
/** @ts-ignore @type {typeof __VLS_components.Tab | typeof __VLS_components.Tab} */
tab_1.default;
// @ts-ignore
var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64({
    value: "summary",
}));
var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([{
        value: "summary",
    }], __VLS_functionalComponentArgsRest(__VLS_65), false));
var __VLS_69 = __VLS_67.slots.default;
// @ts-ignore
[];
var __VLS_67;
// @ts-ignore
[];
var __VLS_49;
var __VLS_70;
/** @ts-ignore @type {typeof __VLS_components.TabPanels | typeof __VLS_components.TabPanels} */
tabpanels_1.default;
// @ts-ignore
var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70({}));
var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_71), false));
var __VLS_75 = __VLS_73.slots.default;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({
    value: "branches",
}));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{
        value: "branches",
    }], __VLS_functionalComponentArgsRest(__VLS_77), false));
var __VLS_81 = __VLS_79.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
for (var _i = 0, _f = __VLS_vFor((__VLS_ctx.filteredBranches)); _i < _f.length; _i++) {
    var branch = _f[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (branch.value) }, { class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 bg-gray-50/50 border-b border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    (branch.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "overflow-x-auto" }));
    /** @type {__VLS_StyleScopedClasses['overflow-x-auto']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.table, __VLS_intrinsics.table)(__assign({ class: "w-full" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.thead, __VLS_intrinsics.thead)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ class: "bg-gray-50/50" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)(__assign({ class: "divide-y divide-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['divide-y']} */ ;
    /** @type {__VLS_StyleScopedClasses['divide-gray-100']} */ ;
    var _loop_1 = function (emp) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ key: (emp.id) }, { class: "hover:bg-gray-50/50" }));
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50/50']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_82 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        avatar_1.default;
        // @ts-ignore
        var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })));
        var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_83), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (emp.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (emp.employeeId);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (emp.department);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        var __VLS_87 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
            value: (emp.shiftType),
            severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
            rounded: true,
        }));
        var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
                value: (emp.shiftType),
                severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_88), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (emp.shiftStart);
        (emp.shiftEnd);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        for (var _y = 0, _z = __VLS_vFor((emp.workingDays)); _y < _z.length; _y++) {
            var day = _z[_y][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ key: (day) }, { class: "w-6 h-6 text-xs flex items-center justify-center bg-gray-100 rounded" }));
            /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-6']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            (day);
            // @ts-ignore
            [filteredBranches, getEmployeesByBranch, getInitials, getShiftSeverity,];
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        var __VLS_92 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92({
            value: (emp.status),
            severity: (__VLS_ctx.getStatusSeverity(emp.status)),
            rounded: true,
        }));
        var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([{
                value: (emp.status),
                severity: (__VLS_ctx.getStatusSeverity(emp.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_93), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        var __VLS_97 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_98), false));
        var __VLS_102 = void 0;
        var __VLS_103 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewEmployeeShifts(emp);
                    // @ts-ignore
                    [getStatusSeverity, viewEmployeeShifts,];
                } });
        var __VLS_104 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_105), false));
        var __VLS_109 = void 0;
        var __VLS_110 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editEmployeeShift(emp);
                    // @ts-ignore
                    [editEmployeeShift,];
                } });
        var __VLS_111 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111(__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_112), false));
        var __VLS_116 = void 0;
        var __VLS_117 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewShiftHistory(emp);
                    // @ts-ignore
                    [viewShiftHistory,];
                } });
        // @ts-ignore
        [];
    };
    var __VLS_100, __VLS_101, __VLS_107, __VLS_108, __VLS_114, __VLS_115;
    for (var _g = 0, _h = __VLS_vFor((__VLS_ctx.getEmployeesByBranch(branch.value))); _g < _h.length; _g++) {
        var emp = _h[_g][0];
        _loop_1(emp);
    }
    if (__VLS_ctx.getEmployeesByBranch(branch.value).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ colspan: "7" }, { class: "p-8 text-center text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [getEmployeesByBranch,];
}
// @ts-ignore
[];
var __VLS_79;
var __VLS_118;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118({
    value: "departments",
}));
var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([{
        value: "departments",
    }], __VLS_functionalComponentArgsRest(__VLS_119), false));
var __VLS_123 = __VLS_121.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
for (var _j = 0, _k = __VLS_vFor((__VLS_ctx.filteredDepartments)); _j < _k.length; _j++) {
    var dept = _k[_j][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (dept.value) }, { class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-4 bg-gray-50/50 border-b border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-b']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    (dept.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "overflow-x-auto" }));
    /** @type {__VLS_StyleScopedClasses['overflow-x-auto']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.table, __VLS_intrinsics.table)(__assign({ class: "w-full" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.thead, __VLS_intrinsics.thead)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ class: "bg-gray-50/50" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)(__assign({ class: "text-left p-3 text-sm font-medium text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)(__assign({ class: "divide-y divide-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['divide-y']} */ ;
    /** @type {__VLS_StyleScopedClasses['divide-gray-100']} */ ;
    var _loop_2 = function (emp) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)(__assign({ key: (emp.id) }, { class: "hover:bg-gray-50/50" }));
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50/50']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_124 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        avatar_1.default;
        // @ts-ignore
        var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })));
        var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(emp.name)), size: "normal" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_125), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (emp.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (emp.employeeId);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (__VLS_ctx.getBranchLabel(emp.branch));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        var __VLS_129 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129({
            value: (emp.shiftType),
            severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
            rounded: true,
        }));
        var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([{
                value: (emp.shiftType),
                severity: (__VLS_ctx.getShiftSeverity(emp.shiftType)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_130), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (emp.shiftStart);
        (emp.shiftEnd);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        for (var _0 = 0, _1 = __VLS_vFor((emp.workingDays)); _0 < _1.length; _0++) {
            var day = _1[_0][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ key: (day) }, { class: "w-6 h-6 text-xs flex items-center justify-center bg-gray-100 rounded" }));
            /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-6']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            (day);
            // @ts-ignore
            [getInitials, getShiftSeverity, filteredDepartments, getEmployeesByDepartment, getBranchLabel,];
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        var __VLS_134 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134({
            value: (emp.status),
            severity: (__VLS_ctx.getStatusSeverity(emp.status)),
            rounded: true,
        }));
        var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([{
                value: (emp.status),
                severity: (__VLS_ctx.getStatusSeverity(emp.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_135), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ class: "p-3" }));
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
        var __VLS_139 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_140), false));
        var __VLS_144 = void 0;
        var __VLS_145 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewEmployeeShifts(emp);
                    // @ts-ignore
                    [getStatusSeverity, viewEmployeeShifts,];
                } });
        var __VLS_146 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_147), false));
        var __VLS_151 = void 0;
        var __VLS_152 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editEmployeeShift(emp);
                    // @ts-ignore
                    [editEmployeeShift,];
                } });
        var __VLS_153 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153(__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info", size: "small" })));
        var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-history", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_154), false));
        var __VLS_158 = void 0;
        var __VLS_159 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewShiftHistory(emp);
                    // @ts-ignore
                    [viewShiftHistory,];
                } });
        // @ts-ignore
        [];
    };
    var __VLS_142, __VLS_143, __VLS_149, __VLS_150, __VLS_156, __VLS_157;
    for (var _l = 0, _m = __VLS_vFor((__VLS_ctx.getEmployeesByDepartment(dept.value))); _l < _m.length; _l++) {
        var emp = _m[_l][0];
        _loop_2(emp);
    }
    if (__VLS_ctx.getEmployeesByDepartment(dept.value).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)(__assign({ colspan: "7" }, { class: "p-8 text-center text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    }
    // @ts-ignore
    [getEmployeesByDepartment,];
}
// @ts-ignore
[];
var __VLS_121;
var __VLS_160;
/** @ts-ignore @type {typeof __VLS_components.TabPanel | typeof __VLS_components.TabPanel} */
tabpanel_1.default;
// @ts-ignore
var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160({
    value: "summary",
}));
var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([{
        value: "summary",
    }], __VLS_functionalComponentArgsRest(__VLS_161), false));
var __VLS_165 = __VLS_163.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
for (var _o = 0, _p = __VLS_vFor((__VLS_ctx.shiftTypes)); _o < _p.length; _o++) {
    var type = _p[_o][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (type.name) }, { class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between text-sm" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (type.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.getShiftCount(type.name));
    var __VLS_166 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.ProgressBar} */
    progressbar_1.default;
    // @ts-ignore
    var __VLS_167 = __VLS_asFunctionalComponent1(__VLS_166, new __VLS_166(__assign({ value: (__VLS_ctx.getShiftPercentage(type.name)), showValue: (false) }, { class: "h-2" })));
    var __VLS_168 = __VLS_167.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.getShiftPercentage(type.name)), showValue: (false) }, { class: "h-2" })], __VLS_functionalComponentArgsRest(__VLS_167), false));
    /** @type {__VLS_StyleScopedClasses['h-2']} */ ;
    // @ts-ignore
    [shiftTypes, getShiftCount, getShiftPercentage,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
for (var _q = 0, _r = __VLS_vFor((__VLS_ctx.departments)); _q < _r.length; _q++) {
    var dept = _r[_q][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (dept.value) }, { class: "flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (dept.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.getDepartmentCount(dept.value));
    var __VLS_171 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_172 = __VLS_asFunctionalComponent1(__VLS_171, new __VLS_171({
        value: (__VLS_ctx.getDepartmentShiftType(dept.value)),
        severity: "info",
        rounded: true,
        size: "small",
    }));
    var __VLS_173 = __VLS_172.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.getDepartmentShiftType(dept.value)),
            severity: "info",
            rounded: true,
            size: "small",
        }], __VLS_functionalComponentArgsRest(__VLS_172), false));
    // @ts-ignore
    [departments, getDepartmentCount, getDepartmentShiftType,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
for (var _s = 0, _t = __VLS_vFor((__VLS_ctx.branches)); _s < _t.length; _s++) {
    var branch = _t[_s][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (branch.value) }, { class: "flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (branch.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.getBranchCount(branch.value));
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400 ml-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['ml-1']} */ ;
    // @ts-ignore
    [branches, getBranchCount,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-3 bg-gray-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.totalEmployees);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-3 bg-gray-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-green-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
(__VLS_ctx.activeShifts);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-3 bg-gray-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-orange-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
(__VLS_ctx.nightShiftCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-3 bg-gray-50 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-semibold text-purple-600" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
(__VLS_ctx.partTimeCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
// @ts-ignore
[totalEmployees, activeShifts, nightShiftCount, partTimeCount,];
var __VLS_163;
// @ts-ignore
[];
var __VLS_73;
// @ts-ignore
[];
var __VLS_43;
var __VLS_176;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_177 = __VLS_asFunctionalComponent1(__VLS_176, new __VLS_176(__assign(__assign({ visible: (__VLS_ctx.showEmployeeModal), modal: true }, { style: ({ width: '600px' }) }), { class: "rounded-xl" })));
var __VLS_178 = __VLS_177.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showEmployeeModal), modal: true }, { style: ({ width: '600px' }) }), { class: "rounded-xl" })], __VLS_functionalComponentArgsRest(__VLS_177), false));
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
var __VLS_181 = __VLS_179.slots.default;
{
    var __VLS_182 = __VLS_179.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_183 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    avatar_1.default;
    // @ts-ignore
    var __VLS_184 = __VLS_asFunctionalComponent1(__VLS_183, new __VLS_183(__assign({ label: (__VLS_ctx.getInitials(((_a = __VLS_ctx.selectedEmployee) === null || _a === void 0 ? void 0 : _a.name) || '')), size: "large" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_185 = __VLS_184.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(((_b = __VLS_ctx.selectedEmployee) === null || _b === void 0 ? void 0 : _b.name) || '')), size: "large" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_184), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    ((_c = __VLS_ctx.selectedEmployee) === null || _c === void 0 ? void 0 : _c.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    ((_d = __VLS_ctx.selectedEmployee) === null || _d === void 0 ? void 0 : _d.department);
    ((_e = __VLS_ctx.selectedEmployee) === null || _e === void 0 ? void 0 : _e.employeeId);
    // @ts-ignore
    [getInitials, showEmployeeModal, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee,];
}
if (__VLS_ctx.selectedEmployee) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border border-gray-100 rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-medium text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_188 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_189 = __VLS_asFunctionalComponent1(__VLS_188, new __VLS_188(__assign({ value: (__VLS_ctx.selectedEmployee.shiftType), severity: (__VLS_ctx.getShiftSeverity(__VLS_ctx.selectedEmployee.shiftType)) }, { class: "mt-1" })));
    var __VLS_190 = __VLS_189.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.selectedEmployee.shiftType), severity: (__VLS_ctx.getShiftSeverity(__VLS_ctx.selectedEmployee.shiftType)) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_189), false));
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedEmployee.shiftStart);
    (__VLS_ctx.selectedEmployee.shiftEnd);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    for (var _u = 0, _v = __VLS_vFor((__VLS_ctx.selectedEmployee.workingDays)); _u < _v.length; _u++) {
        var day = _v[_u][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ key: (day) }, { class: "w-6 h-6 text-xs flex items-center justify-center bg-blue-50 text-blue-600 rounded" }));
        /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-6']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        (day);
        // @ts-ignore
        [getShiftSeverity, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee, selectedEmployee,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_193 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_194 = __VLS_asFunctionalComponent1(__VLS_193, new __VLS_193(__assign({ value: (__VLS_ctx.selectedEmployee.status), severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedEmployee.status)) }, { class: "mt-1" })));
    var __VLS_195 = __VLS_194.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.selectedEmployee.status), severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedEmployee.status)) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_194), false));
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-medium text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _w = 0, _x = __VLS_vFor((__VLS_ctx.upcomingShifts)); _w < _x.length; _w++) {
        var shift = _x[_w][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (shift.id) }, { class: "flex items-center justify-between p-3 bg-gray-50 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (shift.date);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (shift.type);
        (shift.start);
        (shift.end);
        var __VLS_198 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198({
            icon: "pi pi-pencil",
            text: true,
            rounded: true,
            severity: "info",
            size: "small",
        }));
        var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([{
                icon: "pi pi-pencil",
                text: true,
                rounded: true,
                severity: "info",
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_199), false));
        // @ts-ignore
        [getStatusSeverity, selectedEmployee, selectedEmployee, upcomingShifts,];
    }
    if (__VLS_ctx.upcomingShifts.length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center text-gray-400 py-4" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-4']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4 text-center" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
}
// @ts-ignore
[upcomingShifts,];
var __VLS_179;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
