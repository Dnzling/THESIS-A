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
var select_1 = require("primevue/select");
var inputtext_1 = require("primevue/inputtext");
var textarea_1 = require("primevue/textarea");
var businessTypes = [
    { value: 'retail', label: 'Retail Store' },
    { value: 'restaurant', label: 'Restaurant' },
    { value: 'service', label: 'Service Provider' },
    { value: 'wholesale', label: 'Wholesale' },
    { value: 'online', label: 'Online Store' },
    { value: 'other', label: 'Other' }
];
var cities = [
    { value: 'bacoor', label: 'Bacoor' },
    { value: 'carmona', label: 'Carmona' },
    { value: 'cavite-city', label: 'Cavite City' },
    { value: 'dasmarinas', label: 'Dasmariñas' },
    { value: 'general-mariano-alvarez', label: 'General Mariano Alvarez' },
    { value: 'general-trias', label: 'General Trias' },
    { value: 'imus', label: 'Imus' },
    { value: 'indang', label: 'Indang' },
    { value: 'kawit', label: 'Kawit' },
    { value: 'magallanes', label: 'Magallanes' },
    { value: 'mendez', label: 'Mendez' },
    { value: 'naic', label: 'Naic' },
    { value: 'novaleta', label: 'Noveleta' },
    { value: 'rosario', label: 'Rosario' },
    { value: 'silang', label: 'Silang' },
    { value: 'tagaytay', label: 'Tagaytay City' },
    { value: 'tanza', label: 'Tanza' },
    { value: 'ternate', label: 'Ternate' },
    { value: 'trece-martires', label: 'Trece Martires City' }
];
var props = defineProps();
var emit = defineEmits();
// Local form data - only update from parent on component creation
var localForm = (0, vue_1.ref)(__assign({}, props.formData));
// Validation
var handleNext = function () {
    emit('update:formData', localForm.value); // Emit final state
    window.scrollTo(0, 0);
    emit('next');
};
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800 mb-6" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleNext) }));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ modelValue: (__VLS_ctx.localForm.storeName), type: "text", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "Enter store name" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.storeName), type: "text", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "Enter store name" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_5;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ modelValue: (__VLS_ctx.localForm.businessType), options: (__VLS_ctx.businessTypes), optionLabel: "label", optionValue: "value", placeholder: "Select business type" }, { class: "w-full" })));
var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.businessType), options: (__VLS_ctx.businessTypes), optionLabel: "label", optionValue: "value", placeholder: "Select business type" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_10;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessNumber), type: "text", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., DTI-1234567" })));
var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessNumber), type: "text", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., DTI-1234567" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_15;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15(__assign(__assign({ modelValue: (__VLS_ctx.localForm.contactNumber), type: "tel", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., +639123456789" })));
var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.contactNumber), type: "tel", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., +639123456789" })], __VLS_functionalComponentArgsRest(__VLS_16), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "md:col-span-2" }));
/** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign(__assign({ modelValue: (__VLS_ctx.localForm.email), type: "email", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "business@example.com" })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.email), type: "email", required: true }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "business@example.com" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_25;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ type: "text" }, { class: "w-full" }), { value: "Cavite", readOnly: true, disabled: true })));
var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ type: "text" }, { class: "w-full" }), { value: "Cavite", readOnly: true, disabled: true })], __VLS_functionalComponentArgsRest(__VLS_26), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_30;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.city), options: (__VLS_ctx.cities), optionLabel: "label", optionValue: "value", placeholder: "Select city" }, { class: "w-full" })));
var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.city), options: (__VLS_ctx.cities), optionLabel: "label", optionValue: "value", placeholder: "Select city" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_31), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.latitude), type: "text" }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., 123.00001" })));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.latitude), type: "text" }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "e.g., 123.00001" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:ring-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.longitude), type: "text" }, { class: "w-full px-4 py-2 border" }), { placeholder: "e.g., 123.00001" })));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.longitude), type: "text" }, { class: "w-full px-4 py-2 border" }), { placeholder: "e.g., 123.00001" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45(__assign({ class: "h-11 mt-auto w-1/5" }, { icon: "pi pi-map-marker" })));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([__assign({ class: "h-11 mt-auto w-1/5" }, { icon: "pi pi-map-marker" })], __VLS_functionalComponentArgsRest(__VLS_46), false));
/** @type {__VLS_StyleScopedClasses['h-11']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "md:col-span-2" }));
/** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_50;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50(__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.address), required: true, rows: "3", cols: "30" }, { class: "w-full px-4 py-2" })));
var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.businessAddress.address), required: true, rows: "3", cols: "30" }, { class: "w-full px-4 py-2" })], __VLS_functionalComponentArgsRest(__VLS_51), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end mt-8 pt-6 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
var __VLS_55;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "contrast", label: "Next" })));
var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "contrast", label: "Next" })], __VLS_functionalComponentArgsRest(__VLS_56), false));
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
// @ts-ignore
[handleNext, localForm, localForm, localForm, localForm, localForm, localForm, localForm, localForm, localForm, businessTypes, cities,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
