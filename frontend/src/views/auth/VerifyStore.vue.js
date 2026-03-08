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
// import Verification from '../../components/VerifyStoreForm.vue'
var StoreRegistrationWizard_vue_1 = require("../../components/store-registration/StoreRegistrationWizard.vue");
var VerifyStoreStatus_vue_1 = require("../../components/VerifyStoreStatus.vue");
// Check if verification has been submitted
var hasSubmittedVerification = (0, vue_1.ref)();
var verificationData = (0, vue_1.ref)();
// Load verification status from localStorage on mount
(0, vue_1.onMounted)(function () {
    loadVerificationData();
});
var loadVerificationData = function () {
    var savedData = localStorage.getItem('storeVerification');
    if (savedData) {
        verificationData.value = JSON.parse(savedData);
        hasSubmittedVerification.value = false;
    }
};
// Handle when verification is submitted
var handleVerificationSubmitted = function (data) {
    verificationData.value = data;
    hasSubmittedVerification.value = true;
    // Optionally save to localStorage
    localStorage.setItem('storeVerification', JSON.stringify(data));
};
// Handle resubmission
var handleResubmit = function () {
    hasSubmittedVerification.value = false;
    verificationData.value = null;
    localStorage.removeItem('storeVerification');
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-h-screen bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
if (!__VLS_ctx.hasSubmittedVerification) {
    var __VLS_0 = StoreRegistrationWizard_vue_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onSubmitted': {} })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onSubmitted': {} })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ submitted: {} },
        { onSubmitted: (__VLS_ctx.handleVerificationSubmitted) });
    var __VLS_3;
    var __VLS_4;
}
else {
    var __VLS_7 = VerifyStoreStatus_vue_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onResubmit': {} }, { verificationData: (__VLS_ctx.verificationData) })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onResubmit': {} }, { verificationData: (__VLS_ctx.verificationData) })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ resubmit: {} },
        { onResubmit: (__VLS_ctx.handleResubmit) });
    var __VLS_10;
    var __VLS_11;
}
// @ts-ignore
[hasSubmittedVerification, handleVerificationSubmitted, verificationData, handleResubmit,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
