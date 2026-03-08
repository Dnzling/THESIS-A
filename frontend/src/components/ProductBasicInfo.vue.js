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
var inputtext_1 = require("primevue/inputtext");
var dropdown_1 = require("primevue/dropdown");
var textarea_1 = require("primevue/textarea");
var inputnumber_1 = require("primevue/inputnumber");
var checkbox_1 = require("primevue/checkbox");
var editor_1 = require("primevue/editor");
var props = defineProps({
    formData: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        default: function () { return []; }
    },
    suppliers: {
        type: Array,
        default: function () { return []; }
    }
});
var emit = defineEmits(['update:formData', 'validation']);
// Local copy of form data
var localForm = (0, vue_1.ref)(__assign({}, props.formData));
// Error tracking
var errors = (0, vue_1.ref)({});
// Dropdown options
var productTypes = (0, vue_1.ref)([
    { label: 'Furniture', value: 'furniture' },
    { label: 'Accessory', value: 'accessory' },
    { label: 'Decor', value: 'decor' },
    { label: 'Lighting', value: 'lighting' }
]);
var warrantyOptions = (0, vue_1.ref)([
    { label: '6 months', value: 6 },
    { label: '12 months', value: 12 },
    { label: '18 months', value: 18 },
    { label: '24 months', value: 24 },
    { label: '36 months', value: 36 },
    { label: 'Lifetime', value: 0 }
]);
// Computed profit calculations
var profitAmount = (0, vue_1.computed)(function () {
    return localForm.value.sellingPrice - localForm.value.costPrice;
});
var profitMargin = (0, vue_1.computed)(function () {
    if (!localForm.value.sellingPrice)
        return 0;
    return ((profitAmount.value / localForm.value.sellingPrice) * 100);
});
// Validation rules
var validationRules = {
    productName: [
        { required: true, message: 'Product name is required' },
        { min: 3, message: 'Minimum 3 characters' },
        { max: 200, message: 'Maximum 200 characters' }
    ],
    sku: [
        { required: true, message: 'SKU is required' },
        { pattern: /^[A-Z0-9-]+$/, message: 'Only uppercase letters, numbers, and hyphens' }
    ],
    categoryId: [
        { required: true, message: 'Category is required' }
    ],
    costPrice: [
        { required: true, message: 'Cost price is required' },
        { min: 0, message: 'Must be positive' }
    ],
    sellingPrice: [
        { required: true, message: 'Selling price is required' },
        { min: 0, message: 'Must be positive' },
        {
            validator: function (value) { return value >= localForm.value.costPrice; },
            message: 'Selling price must be ≥ cost price'
        }
    ]
};
// Validation function
var validateField = function (field) {
    var rules = validationRules[field];
    if (!rules)
        return true;
    var value = localForm.value[field];
    var isValid = true;
    var errorMessage = '';
    for (var _i = 0, rules_1 = rules; _i < rules_1.length; _i++) {
        var rule = rules_1[_i];
        if (rule.required && (!value && value !== 0)) {
            isValid = false;
            errorMessage = rule.message;
            break;
        }
        if (rule.min && value < rule.min) {
            isValid = false;
            errorMessage = rule.message;
            break;
        }
        if (rule.max && value > rule.max) {
            isValid = false;
            errorMessage = rule.message;
            break;
        }
        if (rule.pattern && !rule.pattern.test(value)) {
            isValid = false;
            errorMessage = rule.message;
            break;
        }
        if (rule.validator && !rule.validator(value)) {
            isValid = false;
            errorMessage = rule.message;
            break;
        }
    }
    if (isValid) {
        delete errors.value[field];
    }
    else {
        errors.value[field] = errorMessage;
    }
    validateAll();
    return isValid;
};
var validateAll = function () {
    var requiredFields = Object.keys(validationRules);
    var allValid = requiredFields.every(function (field) { return validateField(field); });
    emit('validation', allValid);
};
// Format number helper
var formatNumber = function (num) {
    return Number(num || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};
// Watch for changes and emit to parent
(0, vue_1.watch)(localForm, function (newValue) {
    emit('update:formData', newValue);
    validateAll();
}, { deep: true });
// Initialize validation
validateAll();
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-700 border-b pb-2" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.productName), placeholder: "e.g., Modern 3-Seater Sofa" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.productName }) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.productName), placeholder: "e.g., Modern 3-Seater Sofa" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.productName }) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ blur: {} },
    { onBlur: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.validateField('productName');
            // @ts-ignore
            [localForm, errors, validateField,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.errors.productName) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
    /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
    (__VLS_ctx.errors.productName);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.sku), placeholder: "e.g., SOFA-MOD-3S-2024" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.sku }) })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.sku), placeholder: "e.g., SOFA-MOD-3S-2024" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.sku }) })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ blur: {} },
    { onBlur: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.validateField('sku');
            // @ts-ignore
            [localForm, errors, errors, errors, validateField,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_10;
var __VLS_11;
if (__VLS_ctx.errors.sku) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
    /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
    (__VLS_ctx.errors.sku);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Dropdown} */
dropdown_1.default;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.localForm.categoryId), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "category_id", placeholder: "Select Category" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.categoryId }) })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.localForm.categoryId), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "category_id", placeholder: "Select Category" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.categoryId }) })], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19;
var __VLS_20 = ({ change: {} },
    { onChange: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.validateField('categoryId');
            // @ts-ignore
            [localForm, errors, errors, errors, validateField, categories,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_17;
var __VLS_18;
if (__VLS_ctx.errors.categoryId) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
    /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
    (__VLS_ctx.errors.categoryId);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Dropdown} */
dropdown_1.default;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ modelValue: (__VLS_ctx.localForm.productType), options: (__VLS_ctx.productTypes), placeholder: "Select Type" }, { class: "w-full" })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.productType), options: (__VLS_ctx.productTypes), placeholder: "Select Type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_26;
/** @ts-ignore @type {typeof __VLS_components.Dropdown} */
dropdown_1.default;
// @ts-ignore
var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign(__assign({ modelValue: (__VLS_ctx.localForm.supplierId), options: (__VLS_ctx.suppliers), optionLabel: "supplier_name", optionValue: "supplier_id", placeholder: "Select Supplier" }, { class: "w-full" }), { showClear: (true) })));
var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.supplierId), options: (__VLS_ctx.suppliers), optionLabel: "supplier_name", optionValue: "supplier_id", placeholder: "Select Supplier" }, { class: "w-full" }), { showClear: (true) })], __VLS_functionalComponentArgsRest(__VLS_27), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_31;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign(__assign({ modelValue: (__VLS_ctx.localForm.shortDescription), placeholder: "Brief description (max 150 characters)", rows: "2" }, { class: "w-full" }), { autoResize: (true) })));
var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.shortDescription), placeholder: "Brief description (max 150 characters)", rows: "2" }, { class: "w-full" }), { autoResize: (true) })], __VLS_functionalComponentArgsRest(__VLS_32), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.localForm.shortDescription.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_36;
/** @ts-ignore @type {typeof __VLS_components.Editor} */
editor_1.default;
// @ts-ignore
var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36(__assign({ modelValue: (__VLS_ctx.localForm.fullDescription), editorStyle: "height: 200px" }, { class: "border rounded" })));
var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.fullDescription), editorStyle: "height: 200px" }, { class: "border rounded" })], __VLS_functionalComponentArgsRest(__VLS_37), false));
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_41;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_42 = __VLS_asFunctionalComponent1(__VLS_41, new __VLS_41(__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.costPrice), mode: "currency", currency: "PHP", locale: "en-PH" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.costPrice }) })));
var __VLS_43 = __VLS_42.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.costPrice), mode: "currency", currency: "PHP", locale: "en-PH" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.costPrice }) })], __VLS_functionalComponentArgsRest(__VLS_42), false));
var __VLS_46;
var __VLS_47 = ({ blur: {} },
    { onBlur: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.validateField('costPrice');
            // @ts-ignore
            [localForm, localForm, localForm, localForm, localForm, localForm, errors, errors, errors, validateField, productTypes, suppliers,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_44;
var __VLS_45;
if (__VLS_ctx.errors.costPrice) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
    /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
    (__VLS_ctx.errors.costPrice);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_48;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48(__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.sellingPrice), mode: "currency", currency: "PHP", locale: "en-PH" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.sellingPrice }) })));
var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onBlur': {} }, { modelValue: (__VLS_ctx.localForm.sellingPrice), mode: "currency", currency: "PHP", locale: "en-PH" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.sellingPrice }) })], __VLS_functionalComponentArgsRest(__VLS_49), false));
var __VLS_53;
var __VLS_54 = ({ blur: {} },
    { onBlur: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.validateField('sellingPrice');
            // @ts-ignore
            [localForm, errors, errors, errors, validateField,];
        } });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_51;
var __VLS_52;
if (__VLS_ctx.errors.sellingPrice) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
    /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
    (__VLS_ctx.errors.sellingPrice);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign({ modelValue: (__VLS_ctx.localForm.discountPercentage), suffix: "%", min: (0), max: (100) }, { class: "w-full" })));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.discountPercentage), suffix: "%", min: (0), max: (100) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_56), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
var __VLS_60;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
checkbox_1.default;
// @ts-ignore
var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60({
    modelValue: (__VLS_ctx.localForm.vatInclusive),
    inputId: "vatInclusive",
    binary: (true),
}));
var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.localForm.vatInclusive),
        inputId: "vatInclusive",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_61), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "vatInclusive" }, { class: "ml-2 text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_65;
/** @ts-ignore @type {typeof __VLS_components.Dropdown} */
dropdown_1.default;
// @ts-ignore
var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ modelValue: (__VLS_ctx.localForm.warrantyMonths), options: (__VLS_ctx.warrantyOptions) }, { class: "w-full" })));
var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.warrantyMonths), options: (__VLS_ctx.warrantyOptions) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_66), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
(__VLS_ctx.formatNumber(__VLS_ctx.localForm.costPrice));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
(__VLS_ctx.formatNumber(__VLS_ctx.localForm.sellingPrice));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ([
        'font-bold',
        __VLS_ctx.profitMargin >= 30 ? 'text-green-600' :
            __VLS_ctx.profitMargin >= 20 ? 'text-yellow-600' :
                'text-red-600'
    ]) }));
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.profitMargin.toFixed(1));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium text-green-600" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
(__VLS_ctx.formatNumber(__VLS_ctx.profitAmount));
// @ts-ignore
[localForm, localForm, localForm, localForm, localForm, errors, errors, warrantyOptions, formatNumber, formatNumber, formatNumber, profitMargin, profitMargin, profitMargin, profitAmount,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    emits: {},
    props: {
        formData: {
            type: Object,
            required: true
        },
        categories: {
            type: Array,
            default: function () { return []; }
        },
        suppliers: {
            type: Array,
            default: function () { return []; }
        }
    },
});
exports.default = {};
