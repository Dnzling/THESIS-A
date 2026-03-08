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
var toast = (0, usetoast_1.useToast)();
// Async components
var StoreInfoStep = (0, vue_1.defineAsyncComponent)(function () { return Promise.resolve().then(function () { return require('./steps/StoreInfoStep.vue'); }); });
var OwnerIdStep = (0, vue_1.defineAsyncComponent)(function () { return Promise.resolve().then(function () { return require('./steps/OwnerIdStep.vue'); }); });
var BusinessDocsStep = (0, vue_1.defineAsyncComponent)(function () { return Promise.resolve().then(function () { return require('./steps/BusinessDocsStep.vue'); }); });
var ReviewStep = (0, vue_1.defineAsyncComponent)(function () { return Promise.resolve().then(function () { return require('./steps/ReviewStep.vue'); }); });
// Current step
var currentStep = (0, vue_1.ref)(1);
// Form data
var formData = (0, vue_1.ref)({
    // Step 1
    storeName: '',
    businessType: '',
    businessNumber: '',
    businessAddress: {
        address: '',
        city: '',
        longitude: '',
        latitude: '',
    },
    contactNumber: '',
    email: '',
    // Step 2
    primaryIdType: '',
    primaryIdNumber: '',
    idFront: null,
    idBack: null,
    selfiePhoto: null,
    // Step 3
    registrationPermit: null,
    taxCertificate: null,
    mayorPermit: null,
    additionalNotes: '',
    // Step 4
    termsAccepted: false,
    privacyAccepted: false
});
// Step components mapping
var stepComponents = {
    1: StoreInfoStep,
    2: OwnerIdStep,
    3: BusinessDocsStep,
    4: ReviewStep
};
// Get current component based on step
var currentStepComponent = (0, vue_1.computed)(function () {
    return stepComponents[currentStep.value];
});
var scrollToTop = function () {
    // Target your main content wrapper
    var container = document.querySelector('.bg-gray-50') ||
        document.querySelector('main') ||
        document.documentElement;
    if (container) {
        container.scrollTop = 0;
    }
};
// Navigation with scroll
var goToNextStep = function () {
    if (currentStep.value < 4) {
        currentStep.value++;
        (0, vue_1.nextTick)(scrollToTop); // Scroll after DOM updates
    }
};
var goToPrevStep = function () {
    if (currentStep.value > 1) {
        currentStep.value--;
        (0, vue_1.nextTick)(scrollToTop); // Scroll after DOM updates
    }
};
var goToStep = function (step) {
    if (step >= 1 && step <= 4) {
        currentStep.value = step;
        (0, vue_1.nextTick)(scrollToTop); // Scroll after DOM updates
    }
};
// Handle form updates from child components
var handleFormUpdate = function (data) {
    formData.value = __assign(__assign({}, formData.value), data);
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-h-screen bg-gray-50 py-8" }));
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['py-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mx-auto px-4 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center mb-10" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-10']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-900 mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-10" }));
/** @type {__VLS_StyleScopedClasses['mb-10']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center w-full max-w-4xl" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
        __VLS_ctx.currentStep >= 1 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'ml-2 text-sm font-medium',
        __VLS_ctx.currentStep >= 1 ? 'text-black' : 'text-gray-400'
    ]) }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'h-1 flex-1 mx-2',
        __VLS_ctx.currentStep >= 2 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['h-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
        __VLS_ctx.currentStep >= 2 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'ml-2 text-sm font-medium',
        __VLS_ctx.currentStep >= 2 ? 'text-black' : 'text-gray-400'
    ]) }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'h-1 flex-1 mx-2',
        __VLS_ctx.currentStep >= 3 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['h-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
        __VLS_ctx.currentStep >= 3 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'ml-2 text-sm font-medium',
        __VLS_ctx.currentStep >= 3 ? 'text-black' : 'text-gray-400'
    ]) }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'h-1 flex-1 mx-2',
        __VLS_ctx.currentStep >= 4 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['h-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
        __VLS_ctx.currentStep >= 4 ? 'bg-black' : 'bg-gray-300'
    ]) }));
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ([
        'ml-2 text-sm font-medium',
        __VLS_ctx.currentStep >= 4 ? 'text-black' : 'text-gray-400'
    ]) }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-lg shadow-lg p-6 mb-8" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.KeepAlive | typeof __VLS_components.KeepAlive} */
KeepAlive;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5 = __VLS_3.slots.default;
var __VLS_6 = (__VLS_ctx.currentStepComponent);
// @ts-ignore
var __VLS_7 = __VLS_asFunctionalComponent1(__VLS_6, new __VLS_6(__assign(__assign(__assign(__assign({ 'onUpdate:formData': {} }, { 'onNext': {} }), { 'onPrev': {} }), { 'onEditStep': {} }), { formData: (__VLS_ctx.formData) })));
var __VLS_8 = __VLS_7.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ 'onUpdate:formData': {} }, { 'onNext': {} }), { 'onPrev': {} }), { 'onEditStep': {} }), { formData: (__VLS_ctx.formData) })], __VLS_functionalComponentArgsRest(__VLS_7), false));
var __VLS_11;
var __VLS_12 = ({ 'update:formData': {} },
    { 'onUpdate:formData': (__VLS_ctx.handleFormUpdate) });
var __VLS_13 = ({ next: {} },
    { onNext: (__VLS_ctx.goToNextStep) });
var __VLS_14 = ({ prev: {} },
    { onPrev: (__VLS_ctx.goToPrevStep) });
var __VLS_15 = ({ editStep: {} },
    { onEditStep: (__VLS_ctx.goToStep) });
var __VLS_9;
var __VLS_10;
// @ts-ignore
[currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStep, currentStepComponent, formData, handleFormUpdate, goToNextStep, goToPrevStep, goToStep,];
var __VLS_3;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
