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
var auth_1 = require("../../../../stores/auth");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var attributes = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var dialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var editMode = (0, vue_1.ref)(false);
var currentAttribute = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var filters = (0, vue_1.reactive)({
    attribute_type: null,
    is_filterable: null
});
var formData = (0, vue_1.reactive)({
    attribute_name: '',
    attribute_type: 'Text',
    description: '',
    unit: '',
    options: [''],
    min_value: null,
    max_value: null,
    is_filterable: false,
    is_required: false,
    is_variant_option: false,
    display_order: 0
});
var errors = (0, vue_1.ref)({});
var attributeTypes = ['Text', 'Number', 'Select', 'Multi-select', 'Color'];
var filterableOptions = [
    { label: 'Filterable Only', value: true },
    { label: 'Non-filterable', value: false }
];
// Methods
var loadAttributes = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                params = __assign({}, filters);
                if (searchQuery.value)
                    params.search = searchQuery.value;
                return [4 /*yield*/, merchandising_service_1.default.getAttributes(params)];
            case 2:
                response = _a.sent();
                attributes.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load attributes',
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
var onSearch = function () {
    loadAttributes();
};
var openCreateDialog = function () {
    resetForm();
    editMode.value = false;
    dialogVisible.value = true;
};
var editAttribute = function (attribute) {
    currentAttribute.value = attribute;
    Object.assign(formData, {
        attribute_name: attribute.attribute_name,
        attribute_type: attribute.attribute_type,
        description: attribute.description || '',
        unit: attribute.unit || '',
        options: attribute.options && attribute.options.length > 0 ? __spreadArray([], attribute.options, true) : [''],
        min_value: attribute.min_value,
        max_value: attribute.max_value,
        is_filterable: attribute.is_filterable || false,
        is_required: attribute.is_required || false,
        is_variant_option: attribute.is_variant_option || false,
        display_order: attribute.display_order || 0
    });
    editMode.value = true;
    dialogVisible.value = true;
};
var onAttributeTypeChange = function () {
    // Reset options when changing to non-select types
    if (!['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)) {
        formData.options = [''];
    }
    // Reset unit and min/max when not Number
    if (formData.attribute_type !== 'Number') {
        formData.unit = '';
        formData.min_value = null;
        formData.max_value = null;
    }
};
var addOption = function () {
    formData.options.push('');
};
var removeOption = function (index) {
    if (formData.options.length > 1) {
        formData.options.splice(index, 1);
    }
};
var saveAttribute = function () { return __awaiter(void 0, void 0, void 0, function () {
    var cleanedData, error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!validate())
                    return [2 /*return*/];
                saving.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 6, 7, 8]);
                cleanedData = __assign(__assign({}, formData), { options: ['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)
                        ? formData.options.filter(function (opt) { return opt.trim() !== ''; })
                        : null });
                if (!editMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateAttribute(currentAttribute.value.id, cleanedData)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attribute updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createAttribute(cleanedData)];
            case 4:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attribute created successfully',
                    life: 3000
                });
                _c.label = 5;
            case 5:
                dialogVisible.value = false;
                loadAttributes();
                return [3 /*break*/, 8];
            case 6:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to save attribute',
                    life: 3000
                });
                return [3 /*break*/, 8];
            case 7:
                saving.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var confirmDelete = function (attribute) {
    currentAttribute.value = attribute;
    deleteDialogVisible.value = true;
};
var deleteAttribute = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteAttribute(currentAttribute.value.id)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attribute deleted successfully',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadAttributes();
                return [3 /*break*/, 5];
            case 3:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete attribute',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                deleting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var validate = function () {
    errors.value = {};
    if (!formData.attribute_name) {
        errors.value.attribute_name = 'Attribute name is required';
    }
    if (!formData.attribute_type) {
        errors.value.attribute_type = 'Attribute type is required';
    }
    // Validate options for select types
    if (['Select', 'Multi-select', 'Color'].includes(formData.attribute_type)) {
        var validOptions = formData.options.filter(function (opt) { return opt.trim() !== ''; });
        if (validOptions.length === 0) {
            toast.add({
                severity: 'warn',
                summary: 'Validation Error',
                detail: 'Please add at least one option',
                life: 3000
            });
            return false;
        }
    }
    return Object.keys(errors.value).length === 0;
};
var resetForm = function () {
    formData.attribute_name = '';
    formData.attribute_type = 'Text';
    formData.description = '';
    formData.unit = '';
    formData.options = [''];
    formData.min_value = null;
    formData.max_value = null;
    formData.is_filterable = false;
    formData.is_required = false;
    formData.is_variant_option = false;
    formData.display_order = 0;
    errors.value = {};
};
var getAttributeTypeSeverity = function (type) {
    var severities = {
        'Text': 'info',
        'Number': 'success',
        'Select': 'warning',
        'Multi-select': 'danger',
        'Color': 'secondary'
    };
    return severities[type] || 'info';
};
(0, vue_1.onMounted)(function () {
    loadAttributes();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
if (__VLS_ctx.authStore.hasPermission('merchandising.attributes.create')) {
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Add Attribute", icon: "pi pi-plus" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Attribute", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreateDialog) });
    var __VLS_3;
    var __VLS_4;
}
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = __VLS_17.slots.default;
    var __VLS_20 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ class: "pi pi-search" })));
    var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_25 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search attributes..." }), { class: "w-full" })));
    var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search attributes..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
    var __VLS_30 = void 0;
    var __VLS_31 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_28;
    var __VLS_29;
    // @ts-ignore
    [authStore, openCreateDialog, searchQuery, onSearch,];
    var __VLS_17;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "All Types", showClear: true })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "All Types", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAttributes) });
    var __VLS_35;
    var __VLS_36;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_filterable), options: (__VLS_ctx.filterableOptions), optionLabel: "label", optionValue: "value", placeholder: "All Attributes", showClear: true })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_filterable), options: (__VLS_ctx.filterableOptions), optionLabel: "label", optionValue: "value", placeholder: "All Attributes", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ change: {} },
        { onChange: (__VLS_ctx.loadAttributes) });
    var __VLS_42;
    var __VLS_43;
    // @ts-ignore
    [filters, filters, attributeTypes, loadAttributes, loadAttributes, filterableOptions,];
}
// @ts-ignore
[];
var __VLS_10;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _b = __VLS_vFor((5)); _i < _b.length; _i++) {
        var i = _b[_i][0];
        var __VLS_46 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ key: (i), height: "100px" }, { class: "rounded-lg" })));
        var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ key: (i), height: "100px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.attributes.length > 0) {
    var __VLS_51 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51({}));
    var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_52), false));
    var __VLS_56 = __VLS_54.slots.default;
    {
        var __VLS_57 = __VLS_54.slots.content;
        var __VLS_58 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ value: (__VLS_ctx.attributes), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" })));
        var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.attributes), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" })], __VLS_functionalComponentArgsRest(__VLS_59), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_63 = __VLS_61.slots.default;
        {
            var __VLS_64 = __VLS_61.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sliders-h text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-sliders-h']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            // @ts-ignore
            [attributes, attributes,];
        }
        var __VLS_65 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({
            field: "attribute_name",
            header: "Attribute Name",
            sortable: true,
        }));
        var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{
                field: "attribute_name",
                header: "Attribute Name",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_66), false));
        var __VLS_70 = __VLS_68.slots.default;
        {
            var __VLS_71 = __VLS_68.slots.body;
            var data = __VLS_vSlot(__VLS_71)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (data.attribute_name);
            if (data.description) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                (data.description);
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_68;
        var __VLS_72 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72({
            field: "attribute_type",
            header: "Type",
            sortable: true,
        }));
        var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([{
                field: "attribute_type",
                header: "Type",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_73), false));
        var __VLS_77 = __VLS_75.slots.default;
        {
            var __VLS_78 = __VLS_75.slots.body;
            var data = __VLS_vSlot(__VLS_78)[0].data;
            var __VLS_79 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79({
                value: (data.attribute_type),
                severity: (__VLS_ctx.getAttributeTypeSeverity(data.attribute_type)),
            }));
            var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([{
                    value: (data.attribute_type),
                    severity: (__VLS_ctx.getAttributeTypeSeverity(data.attribute_type)),
                }], __VLS_functionalComponentArgsRest(__VLS_80), false));
            // @ts-ignore
            [getAttributeTypeSeverity,];
        }
        // @ts-ignore
        [];
        var __VLS_75;
        var __VLS_84 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84({
            field: "unit",
            header: "Unit",
        }));
        var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([{
                field: "unit",
                header: "Unit",
            }], __VLS_functionalComponentArgsRest(__VLS_85), false));
        var __VLS_89 = __VLS_87.slots.default;
        {
            var __VLS_90 = __VLS_87.slots.body;
            var data = __VLS_vSlot(__VLS_90)[0].data;
            if (data.unit) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                (data.unit);
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400 italic" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                /** @type {__VLS_StyleScopedClasses['italic']} */ ;
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_87;
        var __VLS_91 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91({
            field: "options",
            header: "Options",
        }));
        var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([{
                field: "options",
                header: "Options",
            }], __VLS_functionalComponentArgsRest(__VLS_92), false));
        var __VLS_96 = __VLS_94.slots.default;
        {
            var __VLS_97 = __VLS_94.slots.body;
            var data = __VLS_vSlot(__VLS_97)[0].data;
            if (data.options && data.options.length > 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-1" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
                for (var _c = 0, _d = __VLS_vFor((data.options.slice(0, 3))); _c < _d.length; _c++) {
                    var _e = _d[_c], option = _e[0], index = _e[1];
                    var __VLS_98 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    Tag;
                    // @ts-ignore
                    var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({
                        key: (index),
                        value: (option),
                        severity: "info",
                        size: "small",
                    }));
                    var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{
                            key: (index),
                            value: (option),
                            severity: "info",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_99), false));
                    // @ts-ignore
                    [];
                }
                if (data.options.length > 3) {
                    var __VLS_103 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    Tag;
                    // @ts-ignore
                    var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103({
                        value: ("+".concat(data.options.length - 3)),
                        severity: "secondary",
                        size: "small",
                    }));
                    var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([{
                            value: ("+".concat(data.options.length - 3)),
                            severity: "secondary",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_104), false));
                }
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400 italic" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                /** @type {__VLS_StyleScopedClasses['italic']} */ ;
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_94;
        var __VLS_108 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108({
            field: "is_filterable",
            header: "Filterable",
        }));
        var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([{
                field: "is_filterable",
                header: "Filterable",
            }], __VLS_functionalComponentArgsRest(__VLS_109), false));
        var __VLS_113 = __VLS_111.slots.default;
        {
            var __VLS_114 = __VLS_111.slots.body;
            var data = __VLS_vSlot(__VLS_114)[0].data;
            var __VLS_115 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115({
                value: (data.is_filterable ? 'Yes' : 'No'),
                severity: (data.is_filterable ? 'success' : 'secondary'),
                size: "small",
            }));
            var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([{
                    value: (data.is_filterable ? 'Yes' : 'No'),
                    severity: (data.is_filterable ? 'success' : 'secondary'),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_116), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_111;
        var __VLS_120 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120({
            field: "is_required",
            header: "Required",
        }));
        var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([{
                field: "is_required",
                header: "Required",
            }], __VLS_functionalComponentArgsRest(__VLS_121), false));
        var __VLS_125 = __VLS_123.slots.default;
        {
            var __VLS_126 = __VLS_123.slots.body;
            var data = __VLS_vSlot(__VLS_126)[0].data;
            var __VLS_127 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127({
                value: (data.is_required ? 'Yes' : 'No'),
                severity: (data.is_required ? 'warning' : 'secondary'),
                size: "small",
            }));
            var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([{
                    value: (data.is_required ? 'Yes' : 'No'),
                    severity: (data.is_required ? 'warning' : 'secondary'),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_128), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_123;
        var __VLS_132 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_133 = __VLS_asFunctionalComponent1(__VLS_132, new __VLS_132({
            field: "display_order",
            header: "Order",
            sortable: true,
        }));
        var __VLS_134 = __VLS_133.apply(void 0, __spreadArray([{
                field: "display_order",
                header: "Order",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_133), false));
        var __VLS_137 = __VLS_135.slots.default;
        {
            var __VLS_138 = __VLS_135.slots.body;
            var data = __VLS_vSlot(__VLS_138)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            (data.display_order);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_135;
        var __VLS_139 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139({
            field: "products_count",
            header: "Usage",
            sortable: true,
        }));
        var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([{
                field: "products_count",
                header: "Usage",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_140), false));
        var __VLS_144 = __VLS_142.slots.default;
        {
            var __VLS_145 = __VLS_142.slots.body;
            var data = __VLS_vSlot(__VLS_145)[0].data;
            var __VLS_146 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            Badge;
            // @ts-ignore
            var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146({
                value: (data.products_count || 0),
                severity: "info",
            }));
            var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([{
                    value: (data.products_count || 0),
                    severity: "info",
                }], __VLS_functionalComponentArgsRest(__VLS_147), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_142;
        var __VLS_151 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151({
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }));
        var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([{
                header: "Actions",
                frozen: (true),
                alignFrozen: "right",
            }], __VLS_functionalComponentArgsRest(__VLS_152), false));
        var __VLS_156 = __VLS_154.slots.default;
        {
            var __VLS_157 = __VLS_154.slots.body;
            var data_1 = __VLS_vSlot(__VLS_157)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_158 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_159 = __VLS_asFunctionalComponent1(__VLS_158, new __VLS_158(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })));
            var __VLS_160 = __VLS_159.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_159), false));
            var __VLS_163 = void 0;
            var __VLS_164 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.attributes.length > 0))
                            return;
                        __VLS_ctx.editAttribute(data_1);
                        // @ts-ignore
                        [editAttribute,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_161;
            var __VLS_162;
            var __VLS_165 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_166 = __VLS_asFunctionalComponent1(__VLS_165, new __VLS_165(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small", disabled: (data_1.products_count > 0) })));
            var __VLS_167 = __VLS_166.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small", disabled: (data_1.products_count > 0) })], __VLS_functionalComponentArgsRest(__VLS_166), false));
            var __VLS_170 = void 0;
            var __VLS_171 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.attributes.length > 0))
                            return;
                        __VLS_ctx.confirmDelete(data_1);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            var __VLS_168;
            var __VLS_169;
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_154;
        // @ts-ignore
        [];
        var __VLS_61;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_54;
}
else {
    var __VLS_172 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_173 = __VLS_asFunctionalComponent1(__VLS_172, new __VLS_172({}));
    var __VLS_174 = __VLS_173.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_173), false));
    var __VLS_177 = __VLS_175.slots.default;
    {
        var __VLS_178 = __VLS_175.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sliders-h text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-sliders-h']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        var __VLS_179 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_180 = __VLS_asFunctionalComponent1(__VLS_179, new __VLS_179(__assign(__assign({ 'onClick': {} }, { label: "Create Your First Attribute", icon: "pi pi-plus" }), { class: "mt-4" })));
        var __VLS_181 = __VLS_180.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create Your First Attribute", icon: "pi pi-plus" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_180), false));
        var __VLS_184 = void 0;
        var __VLS_185 = ({ click: {} },
            { onClick: (__VLS_ctx.openCreateDialog) });
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        var __VLS_182;
        var __VLS_183;
        // @ts-ignore
        [openCreateDialog,];
    }
    // @ts-ignore
    [];
    var __VLS_175;
}
var __VLS_186;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_187 = __VLS_asFunctionalComponent1(__VLS_186, new __VLS_186(__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Attribute' : 'Create Attribute'), modal: (true) }, { class: "w-full max-w-2xl" })));
var __VLS_188 = __VLS_187.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Attribute' : 'Create Attribute'), modal: (true) }, { class: "w-full max-w-2xl" })], __VLS_functionalComponentArgsRest(__VLS_187), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-2xl']} */ ;
var __VLS_191 = __VLS_189.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "attribute_name" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_192;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_193 = __VLS_asFunctionalComponent1(__VLS_192, new __VLS_192(__assign({ id: "attribute_name", modelValue: (__VLS_ctx.formData.attribute_name), placeholder: "e.g., Material, Color, Size" }, { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_name }) })));
var __VLS_194 = __VLS_193.apply(void 0, __spreadArray([__assign({ id: "attribute_name", modelValue: (__VLS_ctx.formData.attribute_name), placeholder: "e.g., Material, Color, Size" }, { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_name }) })], __VLS_functionalComponentArgsRest(__VLS_193), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.attribute_name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.attribute_name);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "attribute_type" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_197;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_198 = __VLS_asFunctionalComponent1(__VLS_197, new __VLS_197(__assign(__assign({ 'onChange': {} }, { id: "attribute_type", modelValue: (__VLS_ctx.formData.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "Select type" }), { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_type }) })));
var __VLS_199 = __VLS_198.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { id: "attribute_type", modelValue: (__VLS_ctx.formData.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "Select type" }), { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_type }) })], __VLS_functionalComponentArgsRest(__VLS_198), false));
var __VLS_202;
var __VLS_203 = ({ change: {} },
    { onChange: (__VLS_ctx.onAttributeTypeChange) });
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_200;
var __VLS_201;
if (__VLS_ctx.errors.attribute_type) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.attribute_type);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "description" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_204;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_205 = __VLS_asFunctionalComponent1(__VLS_204, new __VLS_204({
    id: "description",
    modelValue: (__VLS_ctx.formData.description),
    rows: "2",
    placeholder: "Optional description...",
}));
var __VLS_206 = __VLS_205.apply(void 0, __spreadArray([{
        id: "description",
        modelValue: (__VLS_ctx.formData.description),
        rows: "2",
        placeholder: "Optional description...",
    }], __VLS_functionalComponentArgsRest(__VLS_205), false));
if (__VLS_ctx.formData.attribute_type === 'Number') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "unit" }, { class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_209 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_210 = __VLS_asFunctionalComponent1(__VLS_209, new __VLS_209({
        id: "unit",
        modelValue: (__VLS_ctx.formData.unit),
        placeholder: "e.g., cm, kg, inches",
    }));
    var __VLS_211 = __VLS_210.apply(void 0, __spreadArray([{
            id: "unit",
            modelValue: (__VLS_ctx.formData.unit),
            placeholder: "e.g., cm, kg, inches",
        }], __VLS_functionalComponentArgsRest(__VLS_210), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
}
if (['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.formData.attribute_type)) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    var _loop_1 = function (option, index) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (index) }, { class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_214 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        InputText;
        // @ts-ignore
        var __VLS_215 = __VLS_asFunctionalComponent1(__VLS_214, new __VLS_214(__assign({ modelValue: (__VLS_ctx.formData.options[index]), placeholder: "Option value" }, { class: "flex-1" })));
        var __VLS_216 = __VLS_215.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.formData.options[index]), placeholder: "Option value" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_215), false));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        var __VLS_219 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_220 = __VLS_asFunctionalComponent1(__VLS_219, new __VLS_219(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", outlined: true, disabled: (__VLS_ctx.formData.options.length === 1) })));
        var __VLS_221 = __VLS_220.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", outlined: true, disabled: (__VLS_ctx.formData.options.length === 1) })], __VLS_functionalComponentArgsRest(__VLS_220), false));
        var __VLS_224 = void 0;
        var __VLS_225 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.formData.attribute_type)))
                        return;
                    __VLS_ctx.removeOption(index);
                    // @ts-ignore
                    [attributeTypes, dialogVisible, editMode, formData, formData, formData, formData, formData, formData, formData, formData, formData, errors, errors, errors, errors, errors, errors, onAttributeTypeChange, removeOption,];
                } });
        // @ts-ignore
        [];
    };
    var __VLS_222, __VLS_223;
    for (var _f = 0, _g = __VLS_vFor((__VLS_ctx.formData.options)); _f < _g.length; _f++) {
        var _h = _g[_f], option = _h[0], index = _h[1];
        _loop_1(option, index);
    }
    var __VLS_226 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226(__assign({ 'onClick': {} }, { label: "Add Option", icon: "pi pi-plus", severity: "secondary", outlined: true, size: "small" })));
    var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Option", icon: "pi pi-plus", severity: "secondary", outlined: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_227), false));
    var __VLS_231 = void 0;
    var __VLS_232 = ({ click: {} },
        { onClick: (__VLS_ctx.addOption) });
    var __VLS_229;
    var __VLS_230;
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
}
if (__VLS_ctx.formData.attribute_type === 'Number') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "min_value" }, { class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_233 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_234 = __VLS_asFunctionalComponent1(__VLS_233, new __VLS_233({
        id: "min_value",
        modelValue: (__VLS_ctx.formData.min_value),
        placeholder: "0",
    }));
    var __VLS_235 = __VLS_234.apply(void 0, __spreadArray([{
            id: "min_value",
            modelValue: (__VLS_ctx.formData.min_value),
            placeholder: "0",
        }], __VLS_functionalComponentArgsRest(__VLS_234), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "max_value" }, { class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_238 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_239 = __VLS_asFunctionalComponent1(__VLS_238, new __VLS_238({
        id: "max_value",
        modelValue: (__VLS_ctx.formData.max_value),
        placeholder: "100",
    }));
    var __VLS_240 = __VLS_239.apply(void 0, __spreadArray([{
            id: "max_value",
            modelValue: (__VLS_ctx.formData.max_value),
            placeholder: "100",
        }], __VLS_functionalComponentArgsRest(__VLS_239), false));
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "display_order" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_243;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_244 = __VLS_asFunctionalComponent1(__VLS_243, new __VLS_243({
    id: "display_order",
    modelValue: (__VLS_ctx.formData.display_order),
    min: (0),
    showButtons: true,
}));
var __VLS_245 = __VLS_244.apply(void 0, __spreadArray([{
        id: "display_order",
        modelValue: (__VLS_ctx.formData.display_order),
        min: (0),
        showButtons: true,
    }], __VLS_functionalComponentArgsRest(__VLS_244), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3 pt-3 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-3']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_248;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_249 = __VLS_asFunctionalComponent1(__VLS_248, new __VLS_248({
    modelValue: (__VLS_ctx.formData.is_filterable),
    inputId: "is_filterable",
    binary: (true),
}));
var __VLS_250 = __VLS_249.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.formData.is_filterable),
        inputId: "is_filterable",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_249), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_filterable" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_253;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_254 = __VLS_asFunctionalComponent1(__VLS_253, new __VLS_253({
    modelValue: (__VLS_ctx.formData.is_required),
    inputId: "is_required",
    binary: (true),
}));
var __VLS_255 = __VLS_254.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.formData.is_required),
        inputId: "is_required",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_254), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_required" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_258;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_259 = __VLS_asFunctionalComponent1(__VLS_258, new __VLS_258({
    modelValue: (__VLS_ctx.formData.is_variant_option),
    inputId: "is_variant_option",
    binary: (true),
}));
var __VLS_260 = __VLS_259.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.formData.is_variant_option),
        inputId: "is_variant_option",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_259), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_variant_option" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
{
    var __VLS_263 = __VLS_189.slots.footer;
    var __VLS_264 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_265 = __VLS_asFunctionalComponent1(__VLS_264, new __VLS_264(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_266 = __VLS_265.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_265), false));
    var __VLS_269 = void 0;
    var __VLS_270 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.dialogVisible = false;
                // @ts-ignore
                [dialogVisible, formData, formData, formData, formData, formData, formData, formData, addOption,];
            } });
    var __VLS_267;
    var __VLS_268;
    var __VLS_271 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_272 = __VLS_asFunctionalComponent1(__VLS_271, new __VLS_271(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })));
    var __VLS_273 = __VLS_272.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_272), false));
    var __VLS_276 = void 0;
    var __VLS_277 = ({ click: {} },
        { onClick: (__VLS_ctx.saveAttribute) });
    var __VLS_274;
    var __VLS_275;
    // @ts-ignore
    [editMode, saving, saveAttribute,];
}
// @ts-ignore
[];
var __VLS_189;
var __VLS_278;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_279 = __VLS_asFunctionalComponent1(__VLS_278, new __VLS_278(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_280 = __VLS_279.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_279), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_283 = __VLS_281.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-red-600" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
if (((_a = __VLS_ctx.currentAttribute) === null || _a === void 0 ? void 0 : _a.products_count) > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-red-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.currentAttribute.products_count);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_284 = __VLS_281.slots.footer;
    var __VLS_285 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_286 = __VLS_asFunctionalComponent1(__VLS_285, new __VLS_285(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_287 = __VLS_286.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_286), false));
    var __VLS_290 = void 0;
    var __VLS_291 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteDialogVisible = false;
                // @ts-ignore
                [deleteDialogVisible, deleteDialogVisible, currentAttribute, currentAttribute,];
            } });
    var __VLS_288;
    var __VLS_289;
    var __VLS_292 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_293 = __VLS_asFunctionalComponent1(__VLS_292, new __VLS_292(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_294 = __VLS_293.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_293), false));
    var __VLS_297 = void 0;
    var __VLS_298 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteAttribute) });
    var __VLS_295;
    var __VLS_296;
    // @ts-ignore
    [deleting, deleteAttribute,];
}
// @ts-ignore
[];
var __VLS_281;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
