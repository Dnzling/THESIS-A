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
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
// State
var showGenerated = (0, vue_1.ref)(false);
var selectedPeriod = (0, vue_1.ref)(null);
var payDate = (0, vue_1.ref)(new Date());
var selectedEmployees = (0, vue_1.ref)([]);
// Data
var periods = (0, vue_1.ref)([
    { label: 'December 1-15, 2024', value: 'dec-1-15' },
    { label: 'December 16-31, 2024', value: 'dec-16-31' }
]);
var employees = (0, vue_1.ref)([
    { id: 1, name: 'John Cruz', role_name: 'Supervisor', department: 'Operations', basicSalary: 8000, branch: 'Dasmarinas' },
    { id: 2, name: 'Maria Santos', role_name: 'Manager', department: 'Warehouse', basicSalary: 16500, branch: 'Dasmarinas' },
    { id: 3, name: 'Carlos Lim', role_name: 'Executive', department: 'Sales', basicSalary: 20000, branch: 'Dasmarinas' }
]);
// Computed
var totalNetPay = (0, vue_1.computed)(function () {
    return selectedEmployees.value.reduce(function (sum, emp) { return sum + emp.basicSalary; }, 0);
});
// Methods
var formatCurrency = function (amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
};
var generatePayroll = function () {
    console.log(employees.value[1]);
    if (!selectedPeriod.value) {
        alert('Please select a pay period');
        return;
    }
    if (selectedEmployees.value.length === 0) {
        alert('Please select at least one employee');
        return;
    }
    showGenerated.value = true;
    alert("Payroll generated for ".concat(selectedEmployees.value.length, " employees"));
    // Navigate to payroll list 
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
if (!__VLS_ctx.showGenerated) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = __VLS_3.slots.default;
    {
        var __VLS_6 = __VLS_3.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4 mb-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_7 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        Select;
        // @ts-ignore
        var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ modelValue: (__VLS_ctx.selectedPeriod), options: (__VLS_ctx.periods), optionLabel: "label", placeholder: "Select period" }, { class: "w-full" })));
        var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.selectedPeriod), options: (__VLS_ctx.periods), optionLabel: "label", placeholder: "Select period" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_12 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
        DatePicker;
        // @ts-ignore
        var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ modelValue: (__VLS_ctx.payDate) }, { class: "w-full" })));
        var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.payDate) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_17 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign(__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll" }), { class: "ml-auto" }), { severity: "success" })));
        var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll" }), { class: "ml-auto" }), { severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
        var __VLS_22 = void 0;
        var __VLS_23 = ({ click: {} },
            { onClick: (__VLS_ctx.generatePayroll) });
        /** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
        var __VLS_20;
        var __VLS_21;
        var __VLS_24 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24({
            value: (__VLS_ctx.employees),
            selectionMode: "multiple",
            selection: (__VLS_ctx.selectedEmployees),
            showGridlines: true,
        }));
        var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.employees),
                selectionMode: "multiple",
                selection: (__VLS_ctx.selectedEmployees),
                showGridlines: true,
            }], __VLS_functionalComponentArgsRest(__VLS_25), false));
        var __VLS_29 = __VLS_27.slots.default;
        var __VLS_30 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30({
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
        }));
        var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([{
                selectionMode: "multiple",
                headerStyle: "width: 3rem",
            }], __VLS_functionalComponentArgsRest(__VLS_31), false));
        var __VLS_35 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35({
            field: "name",
            header: "Employee",
        }));
        var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([{
                field: "name",
                header: "Employee",
            }], __VLS_functionalComponentArgsRest(__VLS_36), false));
        var __VLS_40 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40({
            field: "role_name",
            header: "Position",
        }));
        var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([{
                field: "role_name",
                header: "Position",
            }], __VLS_functionalComponentArgsRest(__VLS_41), false));
        var __VLS_45 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45({
            field: "department",
            header: "Department",
        }));
        var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([{
                field: "department",
                header: "Department",
            }], __VLS_functionalComponentArgsRest(__VLS_46), false));
        var __VLS_50 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50({
            field: "branch",
            header: "Branch",
        }));
        var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([{
                field: "branch",
                header: "Branch",
            }], __VLS_functionalComponentArgsRest(__VLS_51), false));
        var __VLS_55 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
            field: "basicSalary",
            header: "Basic Salary",
        }));
        var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
                field: "basicSalary",
                header: "Basic Salary",
            }], __VLS_functionalComponentArgsRest(__VLS_56), false));
        // @ts-ignore
        [showGenerated, selectedPeriod, periods, payDate, generatePayroll, employees, selectedEmployees,];
        var __VLS_27;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_3;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ 'onClick': {} }, { label: "Back", severity: "contrast" })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back", severity: "contrast" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(!__VLS_ctx.showGenerated))
                    return;
                __VLS_ctx.showGenerated = false;
                // @ts-ignore
                [showGenerated,];
            } });
    var __VLS_63;
    var __VLS_64;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67({}));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = __VLS_70.slots.default;
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73(__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "success" })));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Generate Payroll", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = void 0;
    var __VLS_79 = ({ click: {} },
        { onClick: (__VLS_ctx.generatePayroll) });
    var __VLS_76;
    var __VLS_77;
    {
        var __VLS_80 = __VLS_70.slots.title;
        // @ts-ignore
        [generatePayroll,];
    }
    {
        var __VLS_81 = __VLS_70.slots.content;
        var __VLS_82 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82({
            value: (__VLS_ctx.selectedEmployees),
            stripedRows: true,
        }));
        var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.selectedEmployees),
                stripedRows: true,
            }], __VLS_functionalComponentArgsRest(__VLS_83), false));
        var __VLS_87 = __VLS_85.slots.default;
        var __VLS_88 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88({
            field: "name",
            header: "Employee",
        }));
        var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([{
                field: "name",
                header: "Employee",
            }], __VLS_functionalComponentArgsRest(__VLS_89), false));
        var __VLS_93 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({
            field: "basicSalary",
            header: "Basic",
        }));
        var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{
                field: "basicSalary",
                header: "Basic",
            }], __VLS_functionalComponentArgsRest(__VLS_94), false));
        var __VLS_98 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({
            header: "Overtime",
        }));
        var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{
                header: "Overtime",
            }], __VLS_functionalComponentArgsRest(__VLS_99), false));
        var __VLS_103 = __VLS_101.slots.default;
        {
            var __VLS_104 = __VLS_101.slots.body;
            var __VLS_105 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
            InputNumber;
            // @ts-ignore
            var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105(__assign({ min: (0) }, { class: "w-24" })));
            var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([__assign({ min: (0) }, { class: "w-24" })], __VLS_functionalComponentArgsRest(__VLS_106), false));
            /** @type {__VLS_StyleScopedClasses['w-24']} */ ;
            // @ts-ignore
            [selectedEmployees,];
        }
        // @ts-ignore
        [];
        var __VLS_101;
        var __VLS_110 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110({
            header: "Allowances",
        }));
        var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([{
                header: "Allowances",
            }], __VLS_functionalComponentArgsRest(__VLS_111), false));
        var __VLS_115 = __VLS_113.slots.default;
        {
            var __VLS_116 = __VLS_113.slots.body;
            var __VLS_117 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
            InputNumber;
            // @ts-ignore
            var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ min: (0) }, { class: "w-24" })));
            var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ min: (0) }, { class: "w-24" })], __VLS_functionalComponentArgsRest(__VLS_118), false));
            /** @type {__VLS_StyleScopedClasses['w-24']} */ ;
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_113;
        var __VLS_122 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122({
            header: "Deductions",
        }));
        var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([{
                header: "Deductions",
            }], __VLS_functionalComponentArgsRest(__VLS_123), false));
        var __VLS_127 = __VLS_125.slots.default;
        {
            var __VLS_128 = __VLS_125.slots.body;
            var __VLS_129 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
            InputNumber;
            // @ts-ignore
            var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129(__assign({ min: (0) }, { class: "w-24" })));
            var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([__assign({ min: (0) }, { class: "w-24" })], __VLS_functionalComponentArgsRest(__VLS_130), false));
            /** @type {__VLS_StyleScopedClasses['w-24']} */ ;
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_125;
        var __VLS_134 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134({
            header: "Net Pay",
        }));
        var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([{
                header: "Net Pay",
            }], __VLS_functionalComponentArgsRest(__VLS_135), false));
        var __VLS_139 = __VLS_137.slots.default;
        (__VLS_ctx.formatCurrency(__VLS_ctx.basicSalary));
        // @ts-ignore
        [formatCurrency, basicSalary,];
        var __VLS_137;
        // @ts-ignore
        [];
        var __VLS_85;
        var __VLS_140 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Divider} */
        Divider;
        // @ts-ignore
        var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140({}));
        var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_141), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center gap-3 mt-4" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.formatCurrency(__VLS_ctx.totalNetPay));
        var __VLS_145 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145(__assign(__assign({ label: "Save as Draft" }, { class: "ml-auto" }), { severity: "secondary" })));
        var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([__assign(__assign({ label: "Save as Draft" }, { class: "ml-auto" }), { severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_146), false));
        /** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
        var __VLS_150 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign(__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll" }), { class: "" }), { severity: "info" })));
        var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: "Generate Payroll" }), { class: "" }), { severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_151), false));
        var __VLS_155 = void 0;
        var __VLS_156 = ({ click: {} },
            { onClick: (__VLS_ctx.generatePayroll) });
        /** @type {__VLS_StyleScopedClasses['']} */ ;
        var __VLS_153;
        var __VLS_154;
        // @ts-ignore
        [generatePayroll, formatCurrency, totalNetPay,];
    }
    // @ts-ignore
    [];
    var __VLS_70;
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
