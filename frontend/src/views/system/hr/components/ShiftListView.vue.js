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
var __VLS_props = defineProps();
var __VLS_emit = defineEmits();
var formatDate = function (date) {
    if (!date)
        return '';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getShiftSeverityFromColor = function (color) {
    if (!color)
        return 'info';
    if (color.includes('3b82f6') || color.includes('1e40af'))
        return 'info'; // Blue
    if (color.includes('f59e0b') || color.includes('b45309'))
        return 'warning'; // Amber
    if (color.includes('7c3aed') || color.includes('6d28d9'))
        return 'help'; // Purple
    if (color.includes('10b981'))
        return 'success'; // Green
    return 'info';
};
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
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
var __VLS_12 = __VLS_asFunctionalComponent1(__VLS_11, new __VLS_11(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search", size: "small" })));
var __VLS_13 = __VLS_12.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_12), false));
var __VLS_16;
var __VLS_17 = ({ input: {} },
    { onInput: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('update:filters', __VLS_ctx.filters);
            // @ts-ignore
            [filters, filters, $emit,];
        } });
var __VLS_14;
var __VLS_15;
// @ts-ignore
[];
var __VLS_3;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departmentOptions), size: "small", placeholder: "All Departments", showClear: true })));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.department), options: (__VLS_ctx.departmentOptions), size: "small", placeholder: "All Departments", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_19), false));
var __VLS_23;
var __VLS_24 = ({ change: {} },
    { onChange: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('update:filters', __VLS_ctx.filters);
            // @ts-ignore
            [filters, filters, $emit, departmentOptions,];
        } });
var __VLS_21;
var __VLS_22;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.shiftType), options: (__VLS_ctx.shiftTypeOptions), size: "small", placeholder: "Shift Type", showClear: true })));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.shiftType), options: (__VLS_ctx.shiftTypeOptions), size: "small", placeholder: "Shift Type", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_26), false));
var __VLS_30;
var __VLS_31 = ({ change: {} },
    { onChange: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('update:filters', __VLS_ctx.filters);
            // @ts-ignore
            [filters, filters, $emit, shiftTypeOptions,];
        } });
var __VLS_28;
var __VLS_29;
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.filters.date), showIcon: true, showClear: true, placeholder: "Date", size: "small" })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onDateSelect': {} }, { modelValue: (__VLS_ctx.filters.date), showIcon: true, showClear: true, placeholder: "Date", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ dateSelect: {} },
    { onDateSelect: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$emit('update:filters', __VLS_ctx.filters);
            // @ts-ignore
            [filters, filters, $emit,];
        } });
var __VLS_35;
var __VLS_36;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ value: (__VLS_ctx.shifts), paginator: (true), rows: (10), loading: (__VLS_ctx.loading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.shifts), paginator: (true), rows: (10), loading: (__VLS_ctx.loading), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_44 = __VLS_42.slots.default;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45({
    field: "schedule_date",
    header: "Date",
    sortable: (true),
}));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([{
        field: "schedule_date",
        header: "Date",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_46), false));
var __VLS_50 = __VLS_48.slots.default;
{
    var __VLS_51 = __VLS_48.slots.body;
    var data = __VLS_vSlot(__VLS_51)[0].data;
    (__VLS_ctx.formatDate(data.schedule_date));
    // @ts-ignore
    [shifts, loading, formatDate,];
}
// @ts-ignore
[];
var __VLS_48;
var __VLS_52;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52({
    field: "employee.full_name",
    header: "Employee",
    sortable: (true),
}));
var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([{
        field: "employee.full_name",
        header: "Employee",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_53), false));
var __VLS_57 = __VLS_55.slots.default;
{
    var __VLS_58 = __VLS_55.slots.body;
    var data = __VLS_vSlot(__VLS_58)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_59 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ label: (__VLS_ctx.getInitials(data.employee.full_name)), size: "small" }, { class: "bg-gray-200" })));
    var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.employee.full_name)), size: "small" }, { class: "bg-gray-200" })], __VLS_functionalComponentArgsRest(__VLS_60), false));
    /** @type {__VLS_StyleScopedClasses['bg-gray-200']} */ ;
    (data.employee.full_name);
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_55;
var __VLS_64;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64({
    field: "employee.department",
    header: "Department",
    sortable: (true),
}));
var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([{
        field: "employee.department",
        header: "Department",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_65), false));
var __VLS_69 = __VLS_67.slots.default;
{
    var __VLS_70 = __VLS_67.slots.body;
    var data = __VLS_vSlot(__VLS_70)[0].data;
    (data.employee.department);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_67;
var __VLS_71;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
    field: "shift.name",
    header: "Shift",
}));
var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
        field: "shift.name",
        header: "Shift",
    }], __VLS_functionalComponentArgsRest(__VLS_72), false));
var __VLS_76 = __VLS_74.slots.default;
{
    var __VLS_77 = __VLS_74.slots.body;
    var data = __VLS_vSlot(__VLS_77)[0].data;
    var __VLS_78 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78({
        value: (data.shift.name),
        severity: (__VLS_ctx.getShiftSeverityFromColor(data.shift.color)),
        rounded: true,
    }));
    var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([{
            value: (data.shift.name),
            severity: (__VLS_ctx.getShiftSeverityFromColor(data.shift.color)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_79), false));
    // @ts-ignore
    [getShiftSeverityFromColor,];
}
// @ts-ignore
[];
var __VLS_74;
var __VLS_83;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
    header: "Schedule",
}));
var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
        header: "Schedule",
    }], __VLS_functionalComponentArgsRest(__VLS_84), false));
var __VLS_88 = __VLS_86.slots.default;
{
    var __VLS_89 = __VLS_86.slots.body;
    var data = __VLS_vSlot(__VLS_89)[0].data;
    (data.schedule_time);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_86;
var __VLS_90;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90({
    field: "status",
    header: "Status",
}));
var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([{
        field: "status",
        header: "Status",
    }], __VLS_functionalComponentArgsRest(__VLS_91), false));
var __VLS_95 = __VLS_93.slots.default;
{
    var __VLS_96 = __VLS_93.slots.body;
    var data = __VLS_vSlot(__VLS_96)[0].data;
    var __VLS_97 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({
        value: (data.status_badge.label),
        severity: (data.status_badge.color),
        rounded: true,
    }));
    var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{
            value: (data.status_badge.label),
            severity: (data.status_badge.color),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_98), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_93;
var __VLS_102;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({
    field: "assigned_by_name",
    header: "Assigned By",
}));
var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{
        field: "assigned_by_name",
        header: "Assigned By",
    }], __VLS_functionalComponentArgsRest(__VLS_103), false));
var __VLS_107;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107(__assign({ header: "Actions" }, { style: {} })));
var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_108), false));
var __VLS_112 = __VLS_110.slots.default;
{
    var __VLS_113 = __VLS_110.slots.body;
    var data_1 = __VLS_vSlot(__VLS_113)[0].data;
    var __VLS_114 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })));
    var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_115), false));
    var __VLS_119 = void 0;
    var __VLS_120 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.$emit('view-details', data_1);
                // @ts-ignore
                [$emit,];
            } });
    var __VLS_117;
    var __VLS_118;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_110;
// @ts-ignore
[];
var __VLS_42;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
