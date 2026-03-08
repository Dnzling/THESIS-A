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
var usetoast_1 = require("primevue/usetoast");
var UploadSection_vue_1 = require("../shared/UploadSection.vue");
var toast = (0, usetoast_1.useToast)();
var props = defineProps();
var emit = defineEmits();
// Local form data
var localForm = (0, vue_1.ref)(__assign(__assign({}, props.formData), { additionalNotes: '' }));
// Update field and emit immediately
var updateField = function (field, value) {
    localForm.value[field] = value;
    emit('update:formData', __assign({}, localForm.value));
};
// Validation
var isStepValid = (0, vue_1.computed)(function () {
    return localForm.value.registrationPermit &&
        localForm.value.taxCertificate &&
        localForm.value.mayorPermit;
});
// File handling
var handleFileUpload = function (file, field) {
    // Validate file
    if (file.size > 5 * 1024 * 1024) {
        toast.add({
            severity: 'error',
            summary: 'File too large',
            detail: 'Please upload files smaller than 5MB',
            life: 3000
        });
        return;
    }
    var validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!validTypes.includes(file.type)) {
        toast.add({
            severity: 'error',
            summary: 'Invalid file type',
            detail: 'Please upload JPG, PNG, or PDF files only',
            life: 3000
        });
        return;
    }
    updateField(field, file);
};
var handleFileRemove = function (field) {
    updateField(field, null);
};
var handleNext = function () {
    if (isStepValid.value) {
        emit('next');
        window.scrollTo(0, 0);
    }
};
var handlePrev = function () {
    window.scrollTo(0, 0);
    emit('prev');
    window.scrollTo(0, 0);
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
var __VLS_0 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Business Registration Permit *", description: "Upload your DTI/SEC/CDA registration certificate", file: (__VLS_ctx.localForm.registrationPermit), accept: ".jpg,.jpeg,.png,.pdf", required: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Business Registration Permit *", description: "Upload your DTI/SEC/CDA registration certificate", file: (__VLS_ctx.localForm.registrationPermit), accept: ".jpg,.jpeg,.png,.pdf", required: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'registrationPermit'); }) });
var __VLS_7 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('registrationPermit'); }) });
var __VLS_3;
var __VLS_4;
var __VLS_8 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_9 = __VLS_asFunctionalComponent1(__VLS_8, new __VLS_8(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "BIR Tax Certificate *", description: "Upload your BIR Certificate of Registration", file: (__VLS_ctx.localForm.taxCertificate), accept: ".jpg,.jpeg,.png,.pdf", required: true })));
var __VLS_10 = __VLS_9.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "BIR Tax Certificate *", description: "Upload your BIR Certificate of Registration", file: (__VLS_ctx.localForm.taxCertificate), accept: ".jpg,.jpeg,.png,.pdf", required: true })], __VLS_functionalComponentArgsRest(__VLS_9), false));
var __VLS_13;
var __VLS_14 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'taxCertificate'); }) });
var __VLS_15 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('taxCertificate'); }) });
var __VLS_11;
var __VLS_12;
var __VLS_16 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_17 = __VLS_asFunctionalComponent1(__VLS_16, new __VLS_16(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Mayor's/Business Permit *", description: "Upload your current Mayor's Permit", file: (__VLS_ctx.localForm.mayorPermit), accept: ".jpg,.jpeg,.png,.pdf", required: true })));
var __VLS_18 = __VLS_17.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Mayor's/Business Permit *", description: "Upload your current Mayor's Permit", file: (__VLS_ctx.localForm.mayorPermit), accept: ".jpg,.jpeg,.png,.pdf", required: true })], __VLS_functionalComponentArgsRest(__VLS_17), false));
var __VLS_21;
var __VLS_22 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'mayorPermit'); }) });
var __VLS_23 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('mayorPermit'); }) });
var __VLS_19;
var __VLS_20;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-8" }));
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.textarea, __VLS_intrinsics.textarea)(__assign(__assign({ value: (__VLS_ctx.localForm.additionalNotes), rows: "3" }, { class: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" }), { placeholder: "Any additional information about your documents..." }));
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between mt-8 pt-6 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
var __VLS_24;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "contrast", variant: "outlined", label: "Previous" })));
var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "contrast", variant: "outlined", label: "Previous" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
var __VLS_29;
var __VLS_30 = ({ click: {} },
    { onClick: (__VLS_ctx.handlePrev) });
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
var __VLS_27;
var __VLS_28;
var __VLS_31;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "success", label: "Submit & Review", disabled: (!__VLS_ctx.isStepValid) })));
var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "success", label: "Submit & Review", disabled: (!__VLS_ctx.isStepValid) })], __VLS_functionalComponentArgsRest(__VLS_32), false));
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
// @ts-ignore
[handleNext, localForm, localForm, localForm, localForm, handleFileUpload, handleFileUpload, handleFileUpload, handleFileRemove, handleFileRemove, handleFileRemove, handlePrev, isStepValid,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
