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
var inputnumber_1 = require("primevue/inputnumber");
var toast = (0, usetoast_1.useToast)();
var props = defineProps();
var emit = defineEmits();
// ID Types
var idTypes = [
    { value: 'umid', label: 'UMID', icon: 'pi pi-id-card' },
    { value: 'driver', label: "Driver's License", icon: 'pi pi-car' },
    { value: 'passport', label: 'Passport', icon: 'pi pi-globe' },
    { value: 'national', label: 'National ID', icon: 'pi pi-flag' }
];
// Local form data - only sync from parent on creation
var localForm = (0, vue_1.ref)(__assign({}, props.formData));
// Update field and emit immediately
var updateField = function (field, value) {
    localForm.value[field] = value;
    emit('update:formData', __assign({}, localForm.value));
};
// Validation
var isStepValid = (0, vue_1.computed)(function () {
    return localForm.value.primaryIdType &&
        localForm.value.idFront &&
        localForm.value.selfiePhoto;
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
    var validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        toast.add({
            severity: 'error',
            summary: 'Invalid file type',
            detail: 'Please upload JPG or PNG files only',
            life: 3000
        });
        return;
    }
    updateField(field, file);
};
var handleFileRemove = function (field) {
    updateField(field, null);
};
// Update radio button
var handleIdTypeChange = function (value) {
    updateField('primaryIdType', value);
};
var handleNext = function () {
    if (isStepValid.value) {
        emit('next');
        window.scrollTo(0, 0);
    }
};
var handlePrev = function () {
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-8" }));
/** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.idTypes)); _i < _a.length; _i++) {
    var idType = _a[_i][0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ key: (idType.value) }, { class: ([
            'p-4 border rounded-lg cursor-pointer transition',
            __VLS_ctx.localForm.primaryIdType === idType.value
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-300 hover:border-gray-400'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign({ type: "radio", value: (idType.value) }, { class: "hidden" }));
    (__VLS_ctx.localForm.primaryIdType);
    /** @type {__VLS_StyleScopedClasses['hidden']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (idType.icon) }, { class: "text-2xl mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (idType.label);
    // @ts-ignore
    [handleNext, idTypes, localForm, localForm,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ modelValue: (__VLS_ctx.localForm.primaryIdNumber), type: "text", required: true, label: "Primary ID", placeholder: "1234-456-7890" }, { class: "w-full mb-6 py-2 " })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.localForm.primaryIdNumber), type: "text", required: true, label: "Primary ID", placeholder: "1234-456-7890" }, { class: "w-full mb-6 py-2 " })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
var __VLS_5 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "ID Front *", description: "Upload front side of your ID", file: (__VLS_ctx.localForm.idFront), required: true })));
var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "ID Front *", description: "Upload front side of your ID", file: (__VLS_ctx.localForm.idFront), required: true })], __VLS_functionalComponentArgsRest(__VLS_6), false));
var __VLS_10;
var __VLS_11 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'idFront'); }) });
var __VLS_12 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('idFront'); }) });
var __VLS_8;
var __VLS_9;
var __VLS_13 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "ID Back *", description: "Upload back side of your ID (if applicable)", file: (__VLS_ctx.localForm.idBack) })));
var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "ID Back *", description: "Upload back side of your ID (if applicable)", file: (__VLS_ctx.localForm.idBack) })], __VLS_functionalComponentArgsRest(__VLS_14), false));
var __VLS_18;
var __VLS_19 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'idBack'); }) });
var __VLS_20 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('idBack'); }) });
var __VLS_16;
var __VLS_17;
var __VLS_21 = UploadSection_vue_1.default;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Selfie with ID *", description: "Take a selfie while holding your ID", file: (__VLS_ctx.localForm.selfiePhoto), required: true })));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ 'onUpload': {} }, { 'onRemove': {} }), { title: "Selfie with ID *", description: "Take a selfie while holding your ID", file: (__VLS_ctx.localForm.selfiePhoto), required: true })], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26;
var __VLS_27 = ({ upload: {} },
    { onUpload: (function (file) { return __VLS_ctx.handleFileUpload(file, 'selfiePhoto'); }) });
var __VLS_28 = ({ remove: {} },
    { onRemove: (function () { return __VLS_ctx.handleFileRemove('selfiePhoto'); }) });
var __VLS_24;
var __VLS_25;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between mt-8 pt-6 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
var __VLS_29;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29(__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "contrast", variant: "outlined", label: "Previous" })));
var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { type: "button" }), { class: "w-1/5" }), { severity: "contrast", variant: "outlined", label: "Previous" })], __VLS_functionalComponentArgsRest(__VLS_30), false));
var __VLS_34;
var __VLS_35 = ({ click: {} },
    { onClick: (__VLS_ctx.handlePrev) });
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
var __VLS_32;
var __VLS_33;
var __VLS_36;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36(__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "contrast", disabled: (!__VLS_ctx.isStepValid), label: "Next" })));
var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([__assign(__assign({ type: "submit" }, { class: "w-1/5" }), { severity: "contrast", disabled: (!__VLS_ctx.isStepValid), label: "Next" })], __VLS_functionalComponentArgsRest(__VLS_37), false));
/** @type {__VLS_StyleScopedClasses['w-1/5']} */ ;
// @ts-ignore
[localForm, localForm, localForm, localForm, handleFileUpload, handleFileUpload, handleFileUpload, handleFileRemove, handleFileRemove, handleFileRemove, handlePrev, isStepValid,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
