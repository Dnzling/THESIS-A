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
var card_1 = require("primevue/card");
var inputtext_1 = require("primevue/inputtext");
var password_1 = require("primevue/password");
var button_1 = require("primevue/button");
var checkbox_1 = require("primevue/checkbox");
var message_1 = require("primevue/message");
var dialog_1 = require("primevue/dialog");
var progressspinner_1 = require("primevue/progressspinner");
var props = defineProps();
var emit = defineEmits();
// Router
// const router = useRouter()
var route = (0, vue_router_1.useRoute)();
// State
var formData = (0, vue_1.reactive)({
    email: '',
    password: '',
    rememberMe: false
});
var validationErrors = (0, vue_1.reactive)({});
var errorMessage = (0, vue_1.ref)('');
var successMessage = (0, vue_1.ref)('');
// Check for success messages from registration
(0, vue_1.onMounted)(function () {
    if (route.query.registered === 'true') {
        successMessage.value = 'Account created successfully! Please sign in.';
    }
    if (route.query.reset === 'true') {
        successMessage.value = 'Password reset successfully! Please sign in with your new password.';
    }
    // Load remembered user ID if exists
    var rememberedEmail = localStorage.getItem('rememberedEmail');
    if (rememberedEmail) {
        formData.email = rememberedEmail;
        formData.rememberMe = true;
    }
});
// Validation
var validateForm = function () {
    var isValid = true;
    // Clear previous errors
    validationErrors.email = '';
    validationErrors.password = '';
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    // User ID validation
    if (!formData.email.trim()) {
        validationErrors.email = 'Email is required';
        isValid = false;
    }
    else if (!emailRegex.test(formData.email)) {
        validationErrors.email = 'Please enter valid email address (e.g., user@example.com)';
        isValid = false;
    }
    // Password validation
    if (!formData.password) {
        validationErrors.password = 'Password is required';
        isValid = false;
    }
    else if (formData.password.length < 6) {
        validationErrors.password = 'Password must be at least 6 characters';
        isValid = false;
    }
    return isValid;
};
// Handle form submission
var handleSubmit = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        if (!validateForm()) {
            emit('error', 'Please fix the form errors');
            return [2 /*return*/];
        }
        emit('submit', formData);
        return [2 /*return*/];
    });
}); };
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['p-card']} */ ;
/** @type {__VLS_StyleScopedClasses['p-card']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-h-screen flex items-center justify-center bg-linear-to-br from-blue-50 to-indigo-100 p-4" }));
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-br']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['to-indigo-100']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ class: "w-full max-w-md shadow-2xl" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ class: "w-full max-w-md shadow-2xl" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-md']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-2xl']} */ ;
var __VLS_5 = __VLS_3.slots.default;
{
    var __VLS_6 = __VLS_3.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-left mb-2 mt-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-left']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-400 mt-1 text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
}
{
    var __VLS_7 = __VLS_3.slots.content;
    if (__VLS_ctx.errorMessage) {
        var __VLS_8 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Message | typeof __VLS_components.Message} */
        message_1.default;
        // @ts-ignore
        var __VLS_9 = __VLS_asFunctionalComponent1(__VLS_8, new __VLS_8(__assign(__assign({ 'onClose': {} }, { severity: "error", closable: (true) }), { class: "mb-6" })));
        var __VLS_10 = __VLS_9.apply(void 0, __spreadArray([__assign(__assign({ 'onClose': {} }, { severity: "error", closable: (true) }), { class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_9), false));
        var __VLS_13 = void 0;
        var __VLS_14 = ({ close: {} },
            { onClose: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.errorMessage))
                        return;
                    __VLS_ctx.errorMessage = '';
                    // @ts-ignore
                    [errorMessage, errorMessage,];
                } });
        /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
        var __VLS_15 = __VLS_11.slots.default;
        (__VLS_ctx.errorMessage);
        // @ts-ignore
        [errorMessage,];
        var __VLS_11;
        var __VLS_12;
    }
    if (__VLS_ctx.successMessage) {
        var __VLS_16 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Message | typeof __VLS_components.Message} */
        message_1.default;
        // @ts-ignore
        var __VLS_17 = __VLS_asFunctionalComponent1(__VLS_16, new __VLS_16(__assign(__assign({ 'onClose': {} }, { severity: "success", closable: (true) }), { class: "mb-6" })));
        var __VLS_18 = __VLS_17.apply(void 0, __spreadArray([__assign(__assign({ 'onClose': {} }, { severity: "success", closable: (true) }), { class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_17), false));
        var __VLS_21 = void 0;
        var __VLS_22 = ({ close: {} },
            { onClose: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.successMessage))
                        return;
                    __VLS_ctx.successMessage = '';
                    // @ts-ignore
                    [successMessage, successMessage,];
                } });
        /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
        var __VLS_23 = __VLS_19.slots.default;
        (__VLS_ctx.successMessage);
        // @ts-ignore
        [successMessage,];
        var __VLS_19;
        var __VLS_20;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleSubmit) }, { class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "email" }, { class: "block text-sm font-medium text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-inputgroup" }));
    /** @type {__VLS_StyleScopedClasses['p-inputgroup']} */ ;
    var __VLS_24 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign(__assign(__assign({ id: "email", modelValue: (__VLS_ctx.formData.email), type: "text", placeholder: "ex. john@example.com" }, { class: ({ 'p-invalid': __VLS_ctx.validationErrors.email }) }), { class: "w-full" }), { autocomplete: "email" })));
    var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign(__assign(__assign({ id: "email", modelValue: (__VLS_ctx.formData.email), type: "text", placeholder: "ex. john@example.com" }, { class: ({ 'p-invalid': __VLS_ctx.validationErrors.email }) }), { class: "w-full" }), { autocomplete: "email" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    if (__VLS_ctx.validationErrors.email) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
        /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
        (__VLS_ctx.validationErrors.email);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "password" }, { class: "block text-sm font-medium text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-inputgroup" }));
    /** @type {__VLS_StyleScopedClasses['p-inputgroup']} */ ;
    var __VLS_29 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Password} */
    password_1.default;
    // @ts-ignore
    var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29(__assign(__assign(__assign({ id: "password", modelValue: (__VLS_ctx.formData.password), feedback: (false), toggleMask: true, placeholder: "Enter your password" }, { class: ({ 'p-invalid': __VLS_ctx.validationErrors.password }) }), { class: "w-full" }), { autocomplete: "current-password" })));
    var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([__assign(__assign(__assign({ id: "password", modelValue: (__VLS_ctx.formData.password), feedback: (false), toggleMask: true, placeholder: "Enter your password" }, { class: ({ 'p-invalid': __VLS_ctx.validationErrors.password }) }), { class: "w-full" }), { autocomplete: "current-password" })], __VLS_functionalComponentArgsRest(__VLS_30), false));
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    if (__VLS_ctx.validationErrors.password) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "p-error" }));
        /** @type {__VLS_StyleScopedClasses['p-error']} */ ;
        (__VLS_ctx.validationErrors.password);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    var __VLS_34 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
    checkbox_1.default;
    // @ts-ignore
    var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34({
        modelValue: (__VLS_ctx.formData.rememberMe),
        inputId: "rememberMe",
        binary: (true),
    }));
    var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.formData.rememberMe),
            inputId: "rememberMe",
            binary: (true),
        }], __VLS_functionalComponentArgsRest(__VLS_35), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "rememberMe" }, { class: "ml-2 text-sm text-gray-700 cursor-pointer" }));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign({ type: "submit", label: "Log In", loading: (props.isSubmitting) }, { class: "w-full" }), { disabled: (props.isSubmitting), severity: "contrast" })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign({ type: "submit", label: "Log In", loading: (props.isSubmitting) }, { class: "w-full" }), { disabled: (props.isSubmitting), severity: "contrast" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [handleSubmit, formData, formData, formData, validationErrors, validationErrors, validationErrors, validationErrors, validationErrors, validationErrors,];
}
{
    var __VLS_44 = __VLS_3.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center pt-6 border-t border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-blue-600 hover:underline" }));
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:underline']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-blue-600 hover:underline" }));
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:underline']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45(__assign({ visible: (props.isSubmitting), modal: true, closable: (false), showHeader: (false) }, { style: ({ width: '350px' }) })));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([__assign({ visible: (props.isSubmitting), modal: true, closable: (false), showHeader: (false) }, { style: ({ width: '350px' }) })], __VLS_functionalComponentArgsRest(__VLS_46), false));
var __VLS_50 = __VLS_48.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col items-center justify-center p-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
var __VLS_51;
/** @ts-ignore @type {typeof __VLS_components.ProgressSpinner} */
progressspinner_1.default;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ style: {} }, { strokeWidth: "4", fill: "transparent", animationDuration: ".5s" })));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ style: {} }, { strokeWidth: "4", fill: "transparent", animationDuration: ".5s" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "mt-4 text-lg font-medium text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
// @ts-ignore
[];
var __VLS_48;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
