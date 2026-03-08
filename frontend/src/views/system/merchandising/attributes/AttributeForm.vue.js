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
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var checkbox_1 = require("primevue/checkbox");
var skeleton_1 = require("primevue/skeleton");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var isEditMode = (0, vue_1.computed)(function () { return !!route.params.id; });
var submitting = (0, vue_1.ref)(false);
var loadingData = (0, vue_1.ref)(false);
var form = (0, vue_1.reactive)({
    attribute_name: '',
    attribute_type: 'Text',
    description: '',
    unit: '',
    options: [''],
    option_colors: ['#3B82F6'],
    min_value: null,
    max_value: null,
    is_filterable: false,
    is_required: false,
    is_variant_option: false,
    show_on_product_page: true,
    display_order: 0
});
var errors = (0, vue_1.ref)({});
var attributeTypes = ['Text', 'Number', 'Select', 'Multi-select', 'Color'];
var loadAttribute = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, attribute, error_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!isEditMode.value)
                    return [2 /*return*/];
                loadingData.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getAttribute(Number(route.params.id))];
            case 2:
                response = _d.sent();
                attribute = response.data;
                Object.assign(form, {
                    attribute_name: attribute.attribute_name,
                    attribute_type: attribute.attribute_type,
                    description: attribute.description || '',
                    unit: attribute.unit || '',
                    options: attribute.options && attribute.options.length > 0 ? __spreadArray([], attribute.options, true) : [''],
                    option_colors: attribute.option_colors && attribute.option_colors.length > 0 ? __spreadArray([], attribute.option_colors, true) : ['#3B82F6'],
                    min_value: attribute.min_value,
                    max_value: attribute.max_value,
                    is_filterable: attribute.is_filterable || false,
                    is_required: attribute.is_required || false,
                    is_variant_option: attribute.is_variant_option || false,
                    show_on_product_page: (_a = attribute.show_on_product_page) !== null && _a !== void 0 ? _a : true,
                    display_order: attribute.display_order || 0
                });
                return [3 /*break*/, 5];
            case 3:
                error_1 = _d.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to load attribute',
                    life: 5000
                });
                router.push({ name: 'merchandising.attributes' });
                return [3 /*break*/, 5];
            case 4:
                loadingData.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var onAttributeTypeChange = function () {
    // Reset type-specific fields
    if (!['Select', 'Multi-select', 'Color'].includes(form.attribute_type)) {
        form.options = [''];
        form.option_colors = ['#3B82F6'];
    }
    if (form.attribute_type !== 'Number') {
        form.unit = '';
        form.min_value = null;
        form.max_value = null;
    }
};
var addOption = function () {
    form.options.push('');
    if (form.attribute_type === 'Color') {
        form.option_colors.push('#3B82F6');
    }
};
var removeOption = function (index) {
    if (form.options.length > 1) {
        form.options.splice(index, 1);
        if (form.attribute_type === 'Color') {
            form.option_colors.splice(index, 1);
        }
    }
};
var addPresetOptions = function (presets) {
    var _a;
    // Remove empty options
    form.options = form.options.filter(function (opt) { return opt.trim() !== ''; });
    // Add presets
    (_a = form.options).push.apply(_a, presets);
};
var validateForm = function () {
    errors.value = {};
    if (!form.attribute_name) {
        errors.value.attribute_name = 'Attribute name is required';
    }
    if (!form.attribute_type) {
        errors.value.attribute_type = 'Attribute type is required';
    }
    // Validate options for select types
    if (['Select', 'Multi-select', 'Color'].includes(form.attribute_type)) {
        var validOptions = form.options.filter(function (opt) { return opt.trim() !== ''; });
        if (validOptions.length === 0) {
            toast.add({
                severity: 'warn',
                summary: 'Validation Error',
                detail: 'Please add at least one option for this attribute type',
                life: 3000
            });
            return false;
        }
    }
    return Object.keys(errors.value).length === 0;
};
var handleSubmit = function () { return __awaiter(void 0, void 0, void 0, function () {
    var submitData, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!validateForm()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation Error',
                        detail: 'Please fill in all required fields',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                submitting.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 6, 7, 8]);
                submitData = __assign(__assign({}, form), { options: ['Select', 'Multi-select', 'Color'].includes(form.attribute_type)
                        ? form.options.filter(function (opt) { return opt.trim() !== ''; })
                        : null, option_colors: form.attribute_type === 'Color'
                        ? form.option_colors
                        : null, unit: form.attribute_type === 'Number' ? form.unit : null, min_value: form.attribute_type === 'Number' ? form.min_value : null, max_value: form.attribute_type === 'Number' ? form.max_value : null });
                if (!isEditMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateAttribute(Number(route.params.id), submitData)];
            case 2:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attribute updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createAttribute(submitData)];
            case 4:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Attribute created successfully',
                    life: 3000
                });
                _d.label = 5;
            case 5:
                router.push({ name: 'merchandising.attributes' });
                return [3 /*break*/, 8];
            case 6:
                error_2 = _d.sent();
                console.error('Form submission error:', error_2);
                if (((_a = error_2.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors.value = error_2.response.data.errors || {};
                }
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_2.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save attribute',
                    life: 5000
                });
                return [3 /*break*/, 8];
            case 7:
                submitting.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
(0, vue_1.onMounted)(function () {
    loadAttribute();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-4xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.isEditMode ? 'Edit Attribute' : 'Add New Attribute');
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.isEditMode ? 'Update attribute information' : 'Create a new product attribute for filtering and specifications');
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.attributes' });
            // @ts-ignore
            [isEditMode, isEditMode, router,];
        } });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.loadingData) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ height: "300px" }, { class: "rounded-lg" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ height: "300px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ height: "200px" }, { class: "rounded-lg" })));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ height: "200px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleSubmit) }));
    var __VLS_17 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ class: "mb-6" })));
    var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_22 = __VLS_20.slots.default;
    {
        var __VLS_23 = __VLS_20.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [loadingData, handleSubmit,];
    }
    {
        var __VLS_24 = __VLS_20.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
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
        var __VLS_25 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign({ id: "attribute_name", modelValue: (__VLS_ctx.form.attribute_name), placeholder: "e.g., Material, Color, Size, Finish" }, { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_name }) })));
        var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign({ id: "attribute_name", modelValue: (__VLS_ctx.form.attribute_name), placeholder: "e.g., Material, Color, Size, Finish" }, { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_name }) })], __VLS_functionalComponentArgsRest(__VLS_26), false));
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
        var __VLS_30 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign(__assign({ 'onChange': {} }, { id: "attribute_type", modelValue: (__VLS_ctx.form.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "Select attribute type" }), { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_type }) })));
        var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { id: "attribute_type", modelValue: (__VLS_ctx.form.attribute_type), options: (__VLS_ctx.attributeTypes), placeholder: "Select attribute type" }), { class: ({ 'p-invalid': __VLS_ctx.errors.attribute_type }) })], __VLS_functionalComponentArgsRest(__VLS_31), false));
        var __VLS_35 = void 0;
        var __VLS_36 = ({ change: {} },
            { onChange: (__VLS_ctx.onAttributeTypeChange) });
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        var __VLS_33;
        var __VLS_34;
        if (__VLS_ctx.errors.attribute_type) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.attribute_type);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs font-semibold text-blue-900 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.ul, __VLS_intrinsics.ul)(__assign({ class: "text-xs text-blue-800 space-y-1 list-disc list-inside" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['list-disc']} */ ;
        /** @type {__VLS_StyleScopedClasses['list-inside']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "description" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_37 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Textarea} */
        textarea_1.default;
        // @ts-ignore
        var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37({
            id: "description",
            modelValue: (__VLS_ctx.form.description),
            rows: "3",
            placeholder: "Optional description for this attribute...",
        }));
        var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([{
                id: "description",
                modelValue: (__VLS_ctx.form.description),
                rows: "3",
                placeholder: "Optional description for this attribute...",
            }], __VLS_functionalComponentArgsRest(__VLS_38), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [form, form, form, errors, errors, errors, errors, errors, errors, attributeTypes, onAttributeTypeChange,];
    }
    // @ts-ignore
    [];
    var __VLS_20;
    if (__VLS_ctx.form.attribute_type) {
        var __VLS_42 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ class: "mb-6" })));
        var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
        /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
        var __VLS_47 = __VLS_45.slots.default;
        {
            var __VLS_48 = __VLS_45.slots.title;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cog text-purple-600" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-cog']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            // @ts-ignore
            [form,];
        }
        {
            var __VLS_49 = __VLS_45.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
            /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
            if (__VLS_ctx.form.attribute_type === 'Number') {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "unit" }, { class: "text-sm font-semibold text-gray-700" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                var __VLS_50 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.InputText} */
                inputtext_1.default;
                // @ts-ignore
                var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50({
                    id: "unit",
                    modelValue: (__VLS_ctx.form.unit),
                    placeholder: "e.g., cm, kg, inches, lbs",
                }));
                var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([{
                        id: "unit",
                        modelValue: (__VLS_ctx.form.unit),
                        placeholder: "e.g., cm, kg, inches, lbs",
                    }], __VLS_functionalComponentArgsRest(__VLS_51), false));
                __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
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
                var __VLS_55 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
                inputnumber_1.default;
                // @ts-ignore
                var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
                    id: "min_value",
                    modelValue: (__VLS_ctx.form.min_value),
                    placeholder: "0",
                    minFractionDigits: (2),
                }));
                var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
                        id: "min_value",
                        modelValue: (__VLS_ctx.form.min_value),
                        placeholder: "0",
                        minFractionDigits: (2),
                    }], __VLS_functionalComponentArgsRest(__VLS_56), false));
                __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "max_value" }, { class: "text-sm font-semibold text-gray-700" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                var __VLS_60 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
                inputnumber_1.default;
                // @ts-ignore
                var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60({
                    id: "max_value",
                    modelValue: (__VLS_ctx.form.max_value),
                    placeholder: "1000",
                    minFractionDigits: (2),
                }));
                var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([{
                        id: "max_value",
                        modelValue: (__VLS_ctx.form.max_value),
                        placeholder: "1000",
                        minFractionDigits: (2),
                    }], __VLS_functionalComponentArgsRest(__VLS_61), false));
                __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            }
            if (['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.form.attribute_type)) {
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
                    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (index) }, { class: "flex gap-2 items-center" }));
                    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600 w-8" }));
                    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                    /** @type {__VLS_StyleScopedClasses['w-8']} */ ;
                    (index + 1);
                    if (__VLS_ctx.form.attribute_type === 'Color') {
                        __VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign({ type: "color" }, { class: "h-10 w-16 rounded border border-gray-300 cursor-pointer" }));
                        (__VLS_ctx.form.option_colors[index]);
                        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
                        /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
                        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                        /** @type {__VLS_StyleScopedClasses['border']} */ ;
                        /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
                        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
                    }
                    var __VLS_65 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.InputText} */
                    inputtext_1.default;
                    // @ts-ignore
                    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ modelValue: (__VLS_ctx.form.options[index]), placeholder: (__VLS_ctx.form.attribute_type === 'Color' ? 'Color name (e.g., Navy Blue)' : "Option ".concat(index + 1)) }, { class: "flex-1" })));
                    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.options[index]), placeholder: (__VLS_ctx.form.attribute_type === 'Color' ? 'Color name (e.g., Navy Blue)' : "Option ".concat(index + 1)) }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_66), false));
                    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                    if (__VLS_ctx.form.attribute_type === 'Color') {
                        var __VLS_70 = void 0;
                        /** @ts-ignore @type {typeof __VLS_components.InputText} */
                        inputtext_1.default;
                        // @ts-ignore
                        var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70(__assign(__assign({ modelValue: (__VLS_ctx.form.option_colors[index]), placeholder: "#000000" }, { class: "w-28 font-mono text-sm" }), { maxlength: "7" })));
                        var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.option_colors[index]), placeholder: "#000000" }, { class: "w-28 font-mono text-sm" }), { maxlength: "7" })], __VLS_functionalComponentArgsRest(__VLS_71), false));
                        /** @type {__VLS_StyleScopedClasses['w-28']} */ ;
                        /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
                        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                    }
                    var __VLS_75 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Button} */
                    button_1.default;
                    // @ts-ignore
                    var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.form.options.length === 1) })));
                    var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.form.options.length === 1) })], __VLS_functionalComponentArgsRest(__VLS_76), false));
                    var __VLS_80 = void 0;
                    var __VLS_81 = ({ click: {} },
                        { onClick: function () {
                                var _a = [];
                                for (var _i = 0; _i < arguments.length; _i++) {
                                    _a[_i] = arguments[_i];
                                }
                                var $event = _a[0];
                                if (!!(__VLS_ctx.loadingData))
                                    return;
                                if (!(__VLS_ctx.form.attribute_type))
                                    return;
                                if (!(['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.form.attribute_type)))
                                    return;
                                __VLS_ctx.removeOption(index);
                                // @ts-ignore
                                [form, form, form, form, form, form, form, form, form, form, form, form, form, removeOption,];
                            } });
                    // @ts-ignore
                    [];
                };
                var __VLS_78, __VLS_79;
                for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.form.options)); _i < _a.length; _i++) {
                    var _b = _a[_i], option = _b[0], index = _b[1];
                    _loop_1(option, index);
                }
                var __VLS_82 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82(__assign(__assign({ 'onClick': {} }, { label: "Add Option", icon: "pi pi-plus", severity: "secondary", outlined: true, size: "small" }), { class: "w-fit" })));
                var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Add Option", icon: "pi pi-plus", severity: "secondary", outlined: true, size: "small" }), { class: "w-fit" })], __VLS_functionalComponentArgsRest(__VLS_83), false));
                var __VLS_87 = void 0;
                var __VLS_88 = ({ click: {} },
                    { onClick: (__VLS_ctx.addOption) });
                /** @type {__VLS_StyleScopedClasses['w-fit']} */ ;
                var __VLS_85;
                var __VLS_86;
                __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                if (__VLS_ctx.form.attribute_type === 'Select' || __VLS_ctx.form.attribute_type === 'Multi-select') {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 border border-gray-200 rounded-lg p-4" }));
                    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
                    /** @type {__VLS_StyleScopedClasses['border']} */ ;
                    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
                    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
                    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-2" }));
                    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
                    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-2" }));
                    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
                    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                    if (__VLS_ctx.form.attribute_name.toLowerCase().includes('material')) {
                        var __VLS_89 = void 0;
                        /** @ts-ignore @type {typeof __VLS_components.Button} */
                        button_1.default;
                        // @ts-ignore
                        var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89(__assign({ 'onClick': {} }, { label: "+ Wood, Metal, Fabric, Leather, Plastic", size: "small", outlined: true })));
                        var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "+ Wood, Metal, Fabric, Leather, Plastic", size: "small", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_90), false));
                        var __VLS_94 = void 0;
                        var __VLS_95 = ({ click: {} },
                            { onClick: function () {
                                    var _a = [];
                                    for (var _i = 0; _i < arguments.length; _i++) {
                                        _a[_i] = arguments[_i];
                                    }
                                    var $event = _a[0];
                                    if (!!(__VLS_ctx.loadingData))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type))
                                        return;
                                    if (!(['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.form.attribute_type)))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type === 'Select' || __VLS_ctx.form.attribute_type === 'Multi-select'))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_name.toLowerCase().includes('material')))
                                        return;
                                    __VLS_ctx.addPresetOptions(['Wood', 'Metal', 'Fabric', 'Leather', 'Plastic']);
                                    // @ts-ignore
                                    [form, form, form, addOption, addPresetOptions,];
                                } });
                        var __VLS_92;
                        var __VLS_93;
                    }
                    if (__VLS_ctx.form.attribute_name.toLowerCase().includes('size')) {
                        var __VLS_96 = void 0;
                        /** @ts-ignore @type {typeof __VLS_components.Button} */
                        button_1.default;
                        // @ts-ignore
                        var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96(__assign({ 'onClick': {} }, { label: "+ Small, Medium, Large, XL", size: "small", outlined: true })));
                        var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "+ Small, Medium, Large, XL", size: "small", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_97), false));
                        var __VLS_101 = void 0;
                        var __VLS_102 = ({ click: {} },
                            { onClick: function () {
                                    var _a = [];
                                    for (var _i = 0; _i < arguments.length; _i++) {
                                        _a[_i] = arguments[_i];
                                    }
                                    var $event = _a[0];
                                    if (!!(__VLS_ctx.loadingData))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type))
                                        return;
                                    if (!(['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.form.attribute_type)))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type === 'Select' || __VLS_ctx.form.attribute_type === 'Multi-select'))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_name.toLowerCase().includes('size')))
                                        return;
                                    __VLS_ctx.addPresetOptions(['Small', 'Medium', 'Large', 'XL']);
                                    // @ts-ignore
                                    [form, addPresetOptions,];
                                } });
                        var __VLS_99;
                        var __VLS_100;
                    }
                    if (__VLS_ctx.form.attribute_name.toLowerCase().includes('condition')) {
                        var __VLS_103 = void 0;
                        /** @ts-ignore @type {typeof __VLS_components.Button} */
                        button_1.default;
                        // @ts-ignore
                        var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ 'onClick': {} }, { label: "+ New, Like New, Good, Fair", size: "small", outlined: true })));
                        var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "+ New, Like New, Good, Fair", size: "small", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_104), false));
                        var __VLS_108 = void 0;
                        var __VLS_109 = ({ click: {} },
                            { onClick: function () {
                                    var _a = [];
                                    for (var _i = 0; _i < arguments.length; _i++) {
                                        _a[_i] = arguments[_i];
                                    }
                                    var $event = _a[0];
                                    if (!!(__VLS_ctx.loadingData))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type))
                                        return;
                                    if (!(['Select', 'Multi-select', 'Color'].includes(__VLS_ctx.form.attribute_type)))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_type === 'Select' || __VLS_ctx.form.attribute_type === 'Multi-select'))
                                        return;
                                    if (!(__VLS_ctx.form.attribute_name.toLowerCase().includes('condition')))
                                        return;
                                    __VLS_ctx.addPresetOptions(['New', 'Like New', 'Good', 'Fair']);
                                    // @ts-ignore
                                    [form, addPresetOptions,];
                                } });
                        var __VLS_106;
                        var __VLS_107;
                    }
                }
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_45;
    }
    var __VLS_110 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ class: "mb-6" })));
    var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_111), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_115 = __VLS_113.slots.default;
    {
        var __VLS_116 = __VLS_113.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sliders-h text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-sliders-h']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_117 = __VLS_113.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "display_order" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_118 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118({
            id: "display_order",
            modelValue: (__VLS_ctx.form.display_order),
            min: (0),
            showButtons: true,
            buttonLayout: "horizontal",
            step: (1),
        }));
        var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([{
                id: "display_order",
                modelValue: (__VLS_ctx.form.display_order),
                min: (0),
                showButtons: true,
                buttonLayout: "horizontal",
                step: (1),
            }], __VLS_functionalComponentArgsRest(__VLS_119), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3 pt-3 border-t border-gray-200" }));
        /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
        var __VLS_123 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123(__assign({ modelValue: (__VLS_ctx.form.is_filterable), inputId: "is_filterable", binary: (true) }, { class: "mt-1" })));
        var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.is_filterable), inputId: "is_filterable", binary: (true) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_124), false));
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_filterable" }, { class: "text-sm font-semibold text-gray-900 cursor-pointer block" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-3 p-3 bg-orange-50 rounded-lg border border-orange-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-orange-200']} */ ;
        var __VLS_128 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128(__assign({ modelValue: (__VLS_ctx.form.is_required), inputId: "is_required", binary: (true) }, { class: "mt-1" })));
        var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.is_required), inputId: "is_required", binary: (true) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_129), false));
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_required" }, { class: "text-sm font-semibold text-gray-900 cursor-pointer block" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-3 p-3 bg-purple-50 rounded-lg border border-purple-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-purple-200']} */ ;
        var __VLS_133 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133(__assign({ modelValue: (__VLS_ctx.form.is_variant_option), inputId: "is_variant_option", binary: (true) }, { class: "mt-1" })));
        var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.is_variant_option), inputId: "is_variant_option", binary: (true) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_134), false));
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_variant_option" }, { class: "text-sm font-semibold text-gray-900 cursor-pointer block" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        var __VLS_138 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138(__assign({ modelValue: (__VLS_ctx.form.show_on_product_page), inputId: "show_on_product_page", binary: (true) }, { class: "mt-1" })));
        var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.show_on_product_page), inputId: "show_on_product_page", binary: (true) }, { class: "mt-1" })], __VLS_functionalComponentArgsRest(__VLS_139), false));
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "show_on_product_page" }, { class: "text-sm font-semibold text-gray-900 cursor-pointer block" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        // @ts-ignore
        [form, form, form, form, form,];
    }
    // @ts-ignore
    [];
    var __VLS_113;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-3 pt-6 border-t border-gray-200" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    var __VLS_143 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_144), false));
    var __VLS_148 = void 0;
    var __VLS_149 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.attributes' });
                // @ts-ignore
                [router,];
            } });
    var __VLS_146;
    var __VLS_147;
    var __VLS_150 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Attribute' : 'Create Attribute'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })));
    var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Attribute' : 'Create Attribute'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })], __VLS_functionalComponentArgsRest(__VLS_151), false));
    var __VLS_155 = void 0;
    var __VLS_156 = ({ click: {} },
        { onClick: (__VLS_ctx.handleSubmit) });
    var __VLS_153;
    var __VLS_154;
}
// @ts-ignore
[isEditMode, handleSubmit, submitting,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
