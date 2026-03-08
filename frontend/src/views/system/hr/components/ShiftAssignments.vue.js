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
var props = defineProps();
var emit = defineEmits();
// State
var filters = (0, vue_1.ref)({
    search: '',
    type: null,
    status: null
});
var showCreateDialog = (0, vue_1.ref)(false);
var showBulkDialog = (0, vue_1.ref)(false);
var showDeleteDialog = (0, vue_1.ref)(false);
var editingId = (0, vue_1.ref)(null);
var saving = (0, vue_1.ref)(false);
var bulkSaving = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var form = (0, vue_1.ref)({
    employee_id: null,
    shift_id: null,
    template_id: null,
    start_date: null,
    end_date: null,
    assignment_type: null,
    notes: ''
});
var bulkForm = (0, vue_1.ref)({
    employee_ids: [],
    shift_id: null,
    template_id: null,
    start_date: null,
    end_date: null,
    assignment_type: null
});
// Options
var assignmentTypeOptions = [
    { label: 'Permanent', value: 'permanent' },
    { label: 'Temporary', value: 'temporary' },
    { label: 'Cover', value: 'cover' }
];
var statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' }
];
// Computed
var filteredAssignments = (0, vue_1.computed)(function () {
    var filtered = props.assignments;
    if (filters.value.search) {
        var search_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (a) { var _a; return (_a = a.employee_name) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(search_1); });
    }
    if (filters.value.type) {
        filtered = filtered.filter(function (a) { return a.assignment_type === filters.value.type; });
    }
    if (filters.value.status === 'active') {
        filtered = filtered.filter(function (a) { return a.is_active; });
    }
    else if (filters.value.status === 'inactive') {
        filtered = filtered.filter(function (a) { return !a.is_active; });
    }
    return filtered;
});
// Methods
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var formatDate = function (date) {
    if (!date)
        return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
var getTypeSeverity = function (type) {
    var map = {
        'permanent': 'success',
        'temporary': 'warning',
        'cover': 'info'
    };
    return map[type] || 'secondary';
};
var editAssignment = function (assignment) {
    editingId.value = assignment.id;
    form.value = {
        employee_id: assignment.employee_id,
        shift_id: assignment.shift_id,
        template_id: assignment.template_id,
        start_date: new Date(assignment.start_date),
        end_date: assignment.end_date ? new Date(assignment.end_date) : null,
        assignment_type: assignment.assignment_type,
        notes: assignment.notes || ''
    };
    showCreateDialog.value = true;
};
var confirmDelete = function (assignment) {
    editingId.value = assignment.id;
    showDeleteDialog.value = true;
};
var resetForm = function () {
    editingId.value = null;
    form.value = {
        employee_id: null,
        shift_id: null,
        template_id: null,
        start_date: null,
        end_date: null,
        assignment_type: null,
        notes: ''
    };
};
var saveAssignment = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                saving.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 6, 7]);
                if (!editingId.value) return [3 /*break*/, 3];
                return [4 /*yield*/, emit('update', editingId.value, form.value)];
            case 2:
                _a.sent();
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, emit('create', form.value)];
            case 4:
                _a.sent();
                _a.label = 5;
            case 5:
                showCreateDialog.value = false;
                resetForm();
                return [3 /*break*/, 7];
            case 6:
                saving.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var saveBulkAssign = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                bulkSaving.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, emit('bulk-assign', bulkForm.value)];
            case 2:
                _a.sent();
                showBulkDialog.value = false;
                bulkForm.value = {
                    employee_ids: [],
                    shift_id: null,
                    template_id: null,
                    start_date: null,
                    end_date: null,
                    assignment_type: null
                };
                return [3 /*break*/, 4];
            case 3:
                bulkSaving.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); };
var deleteAssignment = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!editingId.value)
                    return [2 /*return*/];
                deleting.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, emit('delete', editingId.value)];
            case 2:
                _a.sent();
                showDeleteDialog.value = false;
                editingId.value = null;
                return [3 /*break*/, 4];
            case 3:
                deleting.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); };
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Bulk Assign", icon: "pi pi-plus", severity: "secondary", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Assign", icon: "pi pi-plus", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showBulkDialog = true;
            // @ts-ignore
            [showBulkDialog,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "New Assignment", icon: "pi pi-plus", severity: "info" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "New Assignment", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showCreateDialog = true;
            // @ts-ignore
            [showCreateDialog,];
        } });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ class: "pi pi-search" })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25({
    modelValue: (__VLS_ctx.filters.search),
    placeholder: "Search employee...",
    size: "small",
}));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.search),
        placeholder: "Search employee...",
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_26), false));
// @ts-ignore
[filters,];
var __VLS_17;
var __VLS_30;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30({
    modelValue: (__VLS_ctx.filters.type),
    options: (__VLS_ctx.assignmentTypeOptions),
    placeholder: "All Types",
    showClear: true,
    size: "small",
}));
var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.type),
        options: (__VLS_ctx.assignmentTypeOptions),
        placeholder: "All Types",
        showClear: true,
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_31), false));
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35({
    modelValue: (__VLS_ctx.filters.status),
    options: (__VLS_ctx.statusOptions),
    placeholder: "All Status",
    showClear: true,
    size: "small",
}));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.status),
        options: (__VLS_ctx.statusOptions),
        placeholder: "All Status",
        showClear: true,
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_36), false));
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ value: (__VLS_ctx.filteredAssignments), loading: (__VLS_ctx.loading), paginator: (true), rows: (10) }, { class: "text-sm" })));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.filteredAssignments), loading: (__VLS_ctx.loading), paginator: (true), rows: (10) }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_45 = __VLS_43.slots.default;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46({
    field: "employee_name",
    header: "Employee",
    sortable: (true),
}));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([{
        field: "employee_name",
        header: "Employee",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51 = __VLS_49.slots.default;
{
    var __VLS_52 = __VLS_49.slots.body;
    var data = __VLS_vSlot(__VLS_52)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ label: (__VLS_ctx.getInitials(data.employee_name)), size: "small" }, { class: "bg-gray-200" })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.employee_name)), size: "small" }, { class: "bg-gray-200" })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    /** @type {__VLS_StyleScopedClasses['bg-gray-200']} */ ;
    (data.employee_name);
    // @ts-ignore
    [filters, filters, assignmentTypeOptions, statusOptions, filteredAssignments, loading, getInitials,];
}
// @ts-ignore
[];
var __VLS_49;
var __VLS_58;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
    field: "shift_name",
    header: "Shift",
    sortable: (true),
}));
var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
        field: "shift_name",
        header: "Shift",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_59), false));
var __VLS_63;
/** @ts-ignore @type {typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({
    field: "template_name",
    header: "Template",
}));
var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{
        field: "template_name",
        header: "Template",
    }], __VLS_functionalComponentArgsRest(__VLS_64), false));
var __VLS_68;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
    field: "start_date",
    header: "Start Date",
}));
var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
        field: "start_date",
        header: "Start Date",
    }], __VLS_functionalComponentArgsRest(__VLS_69), false));
var __VLS_73 = __VLS_71.slots.default;
{
    var __VLS_74 = __VLS_71.slots.body;
    var data = __VLS_vSlot(__VLS_74)[0].data;
    (__VLS_ctx.formatDate(data.start_date));
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_71;
var __VLS_75;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75({
    field: "end_date",
    header: "End Date",
}));
var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([{
        field: "end_date",
        header: "End Date",
    }], __VLS_functionalComponentArgsRest(__VLS_76), false));
var __VLS_80 = __VLS_78.slots.default;
{
    var __VLS_81 = __VLS_78.slots.body;
    var data = __VLS_vSlot(__VLS_81)[0].data;
    (data.end_date ? __VLS_ctx.formatDate(data.end_date) : 'Ongoing');
    // @ts-ignore
    [formatDate,];
}
// @ts-ignore
[];
var __VLS_78;
var __VLS_82;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82({
    field: "assignment_type",
    header: "Type",
    sortable: (true),
}));
var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([{
        field: "assignment_type",
        header: "Type",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_83), false));
var __VLS_87 = __VLS_85.slots.default;
{
    var __VLS_88 = __VLS_85.slots.body;
    var data = __VLS_vSlot(__VLS_88)[0].data;
    var __VLS_89 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89({
        value: (data.assignment_type),
        severity: (__VLS_ctx.getTypeSeverity(data.assignment_type)),
        rounded: true,
    }));
    var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([{
            value: (data.assignment_type),
            severity: (__VLS_ctx.getTypeSeverity(data.assignment_type)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_90), false));
    // @ts-ignore
    [getTypeSeverity,];
}
// @ts-ignore
[];
var __VLS_85;
var __VLS_94;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94({
    field: "status_badge",
    header: "Status",
}));
var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([{
        field: "status_badge",
        header: "Status",
    }], __VLS_functionalComponentArgsRest(__VLS_95), false));
var __VLS_99 = __VLS_97.slots.default;
{
    var __VLS_100 = __VLS_97.slots.body;
    var data = __VLS_vSlot(__VLS_100)[0].data;
    var __VLS_101 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101({
        value: (data.status_badge.label),
        severity: (data.status_badge.color),
        rounded: true,
    }));
    var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([{
            value: (data.status_badge.label),
            severity: (data.status_badge.color),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_102), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_97;
var __VLS_106;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ header: "Actions" }, { style: {} })));
var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_107), false));
var __VLS_111 = __VLS_109.slots.default;
{
    var __VLS_112 = __VLS_109.slots.body;
    var data_1 = __VLS_vSlot(__VLS_112)[0].data;
    var __VLS_113 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_114 = __VLS_asFunctionalComponent1(__VLS_113, new __VLS_113(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })));
    var __VLS_115 = __VLS_114.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true, rounded: true, severity: "info", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_114), false));
    var __VLS_118 = void 0;
    var __VLS_119 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editAssignment(data_1);
                // @ts-ignore
                [editAssignment,];
            } });
    var __VLS_116;
    var __VLS_117;
    var __VLS_120 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })));
    var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_121), false));
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
                [confirmDelete,];
            } });
    var __VLS_123;
    var __VLS_124;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_109;
// @ts-ignore
[];
var __VLS_43;
var __VLS_127;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127(__assign(__assign({ 'onHide': {} }, { visible: (__VLS_ctx.showCreateDialog), header: (__VLS_ctx.editingId ? 'Edit Assignment' : 'Create Assignment'), modal: true }), { style: ({ width: '500px' }) })));
var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([__assign(__assign({ 'onHide': {} }, { visible: (__VLS_ctx.showCreateDialog), header: (__VLS_ctx.editingId ? 'Edit Assignment' : 'Create Assignment'), modal: true }), { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_128), false));
var __VLS_132;
var __VLS_133 = ({ hide: {} },
    { onHide: (__VLS_ctx.resetForm) });
var __VLS_134 = __VLS_130.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 p-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_135;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135(__assign(__assign({ modelValue: (__VLS_ctx.form.employee_id), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employee" }, { class: "w-full" }), { disabled: (!!__VLS_ctx.editingId) })));
var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.employee_id), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employee" }, { class: "w-full" }), { disabled: (!!__VLS_ctx.editingId) })], __VLS_functionalComponentArgsRest(__VLS_136), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_140;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign({ modelValue: (__VLS_ctx.form.shift_id), options: (__VLS_ctx.shifts), optionLabel: "label", optionValue: "value", placeholder: "Select shift" }, { class: "w-full" })));
var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.shift_id), options: (__VLS_ctx.shifts), optionLabel: "label", optionValue: "value", placeholder: "Select shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_141), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_145;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145(__assign(__assign({ modelValue: (__VLS_ctx.form.template_id), options: (__VLS_ctx.templates), optionLabel: "label", optionValue: "value", placeholder: "Select template" }, { class: "w-full" }), { showClear: true })));
var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.template_id), options: (__VLS_ctx.templates), optionLabel: "label", optionValue: "value", placeholder: "Select template" }, { class: "w-full" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_146), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_150;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign({ modelValue: (__VLS_ctx.form.start_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })));
var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.start_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_151), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_155;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_156 = __VLS_asFunctionalComponent1(__VLS_155, new __VLS_155(__assign({ modelValue: (__VLS_ctx.form.end_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })));
var __VLS_157 = __VLS_156.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.end_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_156), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_160;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ modelValue: (__VLS_ctx.form.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), placeholder: "Select type" }, { class: "w-full" })));
var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), placeholder: "Select type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_161), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_165;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_166 = __VLS_asFunctionalComponent1(__VLS_165, new __VLS_165(__assign({ modelValue: (__VLS_ctx.form.notes), rows: "3" }, { class: "w-full" })));
var __VLS_167 = __VLS_166.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.notes), rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_166), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_170 = __VLS_130.slots.footer;
    var __VLS_171 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_172 = __VLS_asFunctionalComponent1(__VLS_171, new __VLS_171(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })));
    var __VLS_173 = __VLS_172.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_172), false));
    var __VLS_176 = void 0;
    var __VLS_177 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showCreateDialog = false;
                // @ts-ignore
                [showCreateDialog, showCreateDialog, assignmentTypeOptions, editingId, editingId, resetForm, form, form, form, form, form, form, form, employees, shifts, templates,];
            } });
    var __VLS_174;
    var __VLS_175;
    var __VLS_178 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_179 = __VLS_asFunctionalComponent1(__VLS_178, new __VLS_178(__assign({ 'onClick': {} }, { label: "Save", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.saving) })));
    var __VLS_180 = __VLS_179.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_179), false));
    var __VLS_183 = void 0;
    var __VLS_184 = ({ click: {} },
        { onClick: (__VLS_ctx.saveAssignment) });
    var __VLS_181;
    var __VLS_182;
    // @ts-ignore
    [saving, saveAssignment,];
}
// @ts-ignore
[];
var __VLS_130;
var __VLS_131;
var __VLS_185;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign({ visible: (__VLS_ctx.showBulkDialog), header: "Bulk Assign Employees", modal: true }, { style: ({ width: '600px' }) })));
var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showBulkDialog), header: "Bulk Assign Employees", modal: true }, { style: ({ width: '600px' }) })], __VLS_functionalComponentArgsRest(__VLS_186), false));
var __VLS_190 = __VLS_188.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 p-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_191;
/** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
MultiSelect;
// @ts-ignore
var __VLS_192 = __VLS_asFunctionalComponent1(__VLS_191, new __VLS_191(__assign(__assign({ modelValue: (__VLS_ctx.bulkForm.employee_ids), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employees" }, { class: "w-full" }), { filter: true })));
var __VLS_193 = __VLS_192.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.bulkForm.employee_ids), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employees" }, { class: "w-full" }), { filter: true })], __VLS_functionalComponentArgsRest(__VLS_192), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_196;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_197 = __VLS_asFunctionalComponent1(__VLS_196, new __VLS_196(__assign({ modelValue: (__VLS_ctx.bulkForm.shift_id), options: (__VLS_ctx.shifts), optionLabel: "label", optionValue: "value", placeholder: "Select shift" }, { class: "w-full" })));
var __VLS_198 = __VLS_197.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkForm.shift_id), options: (__VLS_ctx.shifts), optionLabel: "label", optionValue: "value", placeholder: "Select shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_197), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_201;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_202 = __VLS_asFunctionalComponent1(__VLS_201, new __VLS_201(__assign({ modelValue: (__VLS_ctx.bulkForm.start_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })));
var __VLS_203 = __VLS_202.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkForm.start_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_202), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_206;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_207 = __VLS_asFunctionalComponent1(__VLS_206, new __VLS_206(__assign({ modelValue: (__VLS_ctx.bulkForm.end_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })));
var __VLS_208 = __VLS_207.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkForm.end_date), dateFormat: "yy-mm-dd" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_207), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_211;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_212 = __VLS_asFunctionalComponent1(__VLS_211, new __VLS_211(__assign({ modelValue: (__VLS_ctx.bulkForm.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), placeholder: "Select type" }, { class: "w-full" })));
var __VLS_213 = __VLS_212.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkForm.assignment_type), options: (__VLS_ctx.assignmentTypeOptions), placeholder: "Select type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_212), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_216 = __VLS_188.slots.footer;
    var __VLS_217 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_218 = __VLS_asFunctionalComponent1(__VLS_217, new __VLS_217(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })));
    var __VLS_219 = __VLS_218.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_218), false));
    var __VLS_222 = void 0;
    var __VLS_223 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showBulkDialog = false;
                // @ts-ignore
                [showBulkDialog, showBulkDialog, assignmentTypeOptions, employees, shifts, bulkForm, bulkForm, bulkForm, bulkForm, bulkForm,];
            } });
    var __VLS_220;
    var __VLS_221;
    var __VLS_224 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_225 = __VLS_asFunctionalComponent1(__VLS_224, new __VLS_224(__assign({ 'onClick': {} }, { label: "Assign", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.bulkSaving) })));
    var __VLS_226 = __VLS_225.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Assign", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.bulkSaving) })], __VLS_functionalComponentArgsRest(__VLS_225), false));
    var __VLS_229 = void 0;
    var __VLS_230 = ({ click: {} },
        { onClick: (__VLS_ctx.saveBulkAssign) });
    var __VLS_227;
    var __VLS_228;
    // @ts-ignore
    [bulkSaving, saveBulkAssign,];
}
// @ts-ignore
[];
var __VLS_188;
var __VLS_231;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_232 = __VLS_asFunctionalComponent1(__VLS_231, new __VLS_231(__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: true }, { style: ({ width: '350px' }) })));
var __VLS_233 = __VLS_232.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: true }, { style: ({ width: '350px' }) })], __VLS_functionalComponentArgsRest(__VLS_232), false));
var __VLS_236 = __VLS_234.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-2" }));
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
{
    var __VLS_237 = __VLS_234.slots.footer;
    var __VLS_238 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_239 = __VLS_asFunctionalComponent1(__VLS_238, new __VLS_238(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })));
    var __VLS_240 = __VLS_239.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_239), false));
    var __VLS_243 = void 0;
    var __VLS_244 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDeleteDialog = false;
                // @ts-ignore
                [showDeleteDialog, showDeleteDialog,];
            } });
    var __VLS_241;
    var __VLS_242;
    var __VLS_245 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_246 = __VLS_asFunctionalComponent1(__VLS_245, new __VLS_245(__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_247 = __VLS_246.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", icon: "pi pi-trash", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_246), false));
    var __VLS_250 = void 0;
    var __VLS_251 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteAssignment) });
    var __VLS_248;
    var __VLS_249;
    // @ts-ignore
    [deleting, deleteAssignment,];
}
// @ts-ignore
[];
var __VLS_234;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
