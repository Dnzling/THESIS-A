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
var axios_1 = require("axios");
var router = (0, vue_router_1.useRouter)();
// OTP handling
var otpDigits = (0, vue_1.ref)(Array(6).fill(''));
var otpInputs = (0, vue_1.ref)([]);
var isLoading = (0, vue_1.ref)(false);
var errorMessage = (0, vue_1.ref)('');
var successMessage = (0, vue_1.ref)('');
var isVerified = (0, vue_1.ref)(false);
var resendCooldown = (0, vue_1.ref)(0);
var accessToken = (0, vue_1.ref)('');
// Compute full OTP from digits
var fullOtp = (0, vue_1.computed)(function () { return otpDigits.value.join(''); });
// Handle OTP input
var handleOtpInput = function (index, event) {
    var _a;
    var value = event.target.value;
    // Only allow numbers
    if (!/^\d*$/.test(value)) {
        otpDigits.value[index] = '';
        return;
    }
    // Auto-focus next input if a digit is entered
    if (value && index < 5) {
        (_a = otpInputs.value[index + 1]) === null || _a === void 0 ? void 0 : _a.focus();
    }
    // Clear error when user types
    errorMessage.value = '';
};
// Handle keyboard navigation
var handleOtpKeydown = function (index, event) {
    var _a, _b, _c;
    // Handle backspace
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        (_a = otpInputs.value[index - 1]) === null || _a === void 0 ? void 0 : _a.focus();
    }
    // Handle arrow keys
    if (event.key === 'ArrowLeft' && index > 0) {
        (_b = otpInputs.value[index - 1]) === null || _b === void 0 ? void 0 : _b.focus();
    }
    if (event.key === 'ArrowRight' && index < 5) {
        (_c = otpInputs.value[index + 1]) === null || _c === void 0 ? void 0 : _c.focus();
    }
};
// Handle paste
var handlePaste = function (event) {
    var _a;
    event.preventDefault();
    var pastedData = event.clipboardData.getData('text').trim();
    // Only accept numbers and exactly 6 digits
    if (/^\d{6}$/.test(pastedData)) {
        var digits = pastedData.split('');
        digits.forEach(function (digit, index) {
            if (index < 6) {
                otpDigits.value[index] = digit;
            }
        });
        (_a = otpInputs.value[5]) === null || _a === void 0 ? void 0 : _a.focus();
        errorMessage.value = '';
    }
};
var verifyOtp = function () { return __awaiter(void 0, void 0, void 0, function () {
    var enteredOtp, response_1, error_1;
    var _a, _b, _c, _d, _e, _f, _g;
    return __generator(this, function (_h) {
        switch (_h.label) {
            case 0:
                if (isVerified.value)
                    return [2 /*return*/];
                enteredOtp = fullOtp.value;
                // Check if all digits are filled
                if (enteredOtp.length !== 6) {
                    errorMessage.value = 'Please enter all 6 digits';
                    (_a = otpInputs.value[0]) === null || _a === void 0 ? void 0 : _a.focus();
                    return [2 /*return*/];
                }
                isLoading.value = true;
                errorMessage.value = '';
                successMessage.value = '';
                _h.label = 1;
            case 1:
                _h.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.post('/api/auth/verify-otp', {
                        otp: enteredOtp
                    }, {
                        headers: {
                            'Authorization': "Bearer ".concat(accessToken.value)
                        }
                    })];
            case 2:
                response_1 = _h.sent();
                console.log('Verification response:', response_1.data);
                // Check if verification was successful
                if (response_1.data.success) {
                    successMessage.value = response_1.data.message || 'Email verified successfully!';
                    isVerified.value = true;
                    // Clear the register_token after successful verification
                    localStorage.removeItem('register_token');
                    // Remove axios auth header since token is no longer needed
                    delete axios_1.default.defaults.headers.common['Authorization'];
                    // Redirect to login after delay
                    setTimeout(function () {
                        var _a;
                        router.push({
                            path: '/login',
                            query: {
                                registered: 'true',
                                email: ((_a = response_1.data.user) === null || _a === void 0 ? void 0 : _a.email) || ''
                            }
                        });
                    }, 2000);
                }
                else {
                    // Handle verification failure from API
                    errorMessage.value = response_1.data.message || 'Invalid verification code. Please try again.';
                    // Clear OTP for retry
                    otpDigits.value = Array(6).fill('');
                    (_b = otpInputs.value[0]) === null || _b === void 0 ? void 0 : _b.focus();
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _h.sent();
                console.error('Verification error:', error_1);
                // Handle different error cases
                if (((_c = error_1.response) === null || _c === void 0 ? void 0 : _c.status) === 422) {
                    errorMessage.value = error_1.response.data.message || 'Invalid OTP format';
                }
                else if (((_d = error_1.response) === null || _d === void 0 ? void 0 : _d.status) === 400) {
                    errorMessage.value = 'Invalid or expired verification code';
                }
                else if ((_f = (_e = error_1.response) === null || _e === void 0 ? void 0 : _e.data) === null || _f === void 0 ? void 0 : _f.message) {
                    errorMessage.value = error_1.response.data.message;
                }
                else {
                    errorMessage.value = 'Verification failed. Please try again.';
                }
                // Clear OTP for retry
                otpDigits.value = Array(6).fill('');
                (_g = otpInputs.value[0]) === null || _g === void 0 ? void 0 : _g.focus();
                return [3 /*break*/, 5];
            case 4:
                isLoading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
// Resend code functionality
var resendCode = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, interval_1, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (resendCooldown.value > 0)
                    return [2 /*return*/];
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, , 4]);
                return [4 /*yield*/, axios_1.default.post('/api/auth/resend-otp', {
                        headers: {
                            'Authorization': "Bearer ".concat(accessToken.value)
                        }
                    })
                    // Start 30-second cooldown
                ];
            case 2:
                response = _d.sent();
                // Start 30-second cooldown
                resendCooldown.value = 30;
                interval_1 = setInterval(function () {
                    resendCooldown.value--;
                    if (resendCooldown.value <= 0) {
                        clearInterval(interval_1);
                    }
                }, 1000);
                // Show success message
                successMessage.value = response.data.message || 'New verification code sent to your email!';
                // Clear message after 3 seconds
                setTimeout(function () {
                    successMessage.value = '';
                }, 3000);
                // Clear OTP inputs for fresh entry
                otpDigits.value = Array(6).fill('');
                (_a = otpInputs.value[0]) === null || _a === void 0 ? void 0 : _a.focus();
                return [3 /*break*/, 4];
            case 3:
                error_2 = _d.sent();
                console.error('Resend error:', error_2);
                errorMessage.value = ((_c = (_b = error_2.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to resend code. Please try again.';
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
// Go back to login
var goBack = function () {
    router.push('/login');
};
// Auto-focus first input on mount
(0, vue_1.onMounted)(function () {
    setTimeout(function () {
        var _a;
        (_a = otpInputs.value[0]) === null || _a === void 0 ? void 0 : _a.focus();
    }, 100);
    accessToken.value = localStorage.getItem('register_token');
    // Set axios authorization header
    if (accessToken.value) {
        axios_1.default.defaults.headers.common['Authorization'] = "Bearer ".concat(accessToken.value);
        console.log('Authorization header set with register_token');
    }
    else {
        console.warn('No register_token found in localStorage');
    }
    // Auto-resend countdown if needed
    if (resendCooldown.value > 0) {
        var interval_2 = setInterval(function () {
            resendCooldown.value--;
            if (resendCooldown.value <= 0) {
                clearInterval(interval_2);
            }
        }, 1000);
    }
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-br']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['to-indigo-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['py-12']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "sm:mx-auto sm:w-full sm:max-w-md" }));
/** @type {__VLS_StyleScopedClasses['sm:mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:max-w-md']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "mt-6 text-center text-3xl font-bold text-gray-900" }, { style: {} }));
/** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "mt-2 text-center text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-8 sm:mx-auto sm:w-full sm:max-w-md" }));
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:max-w-md']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['py-8']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-10']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.verifyOtp) }, { class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-3" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center space-x-2 mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var _loop_1 = function (digit, index) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign(__assign(__assign(__assign(__assign({ onInput: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.handleOtpInput(index, $event);
            // @ts-ignore
            [verifyOtp, otpDigits, handleOtpInput,];
        } }, { onKeydown: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.handleOtpKeydown(index, $event);
            // @ts-ignore
            [handleOtpKeydown,];
        } }), { onPaste: (__VLS_ctx.handlePaste) }), { key: (index), ref: "otpInputs", value: (__VLS_ctx.otpDigits[index]), type: "text", maxlength: "1" }), { class: "w-12 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition" }), { class: ({ 'border-red-300': __VLS_ctx.errorMessage }) }));
    /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['focus:border-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['focus:ring-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['focus:ring-blue-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['outline-none']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-red-300']} */ ;
    // @ts-ignore
    [otpDigits, handlePaste, errorMessage,];
};
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.otpDigits)); _i < _a.length; _i++) {
    var _b = _a[_i], digit = _b[0], index = _b[1];
    _loop_1(digit, index);
}
if (__VLS_ctx.errorMessage) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-red-600 text-sm text-center mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    (__VLS_ctx.errorMessage);
}
if (__VLS_ctx.successMessage) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 border border-green-200 rounded-md p-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-green-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-md']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "shrink-0" }));
    /** @type {__VLS_StyleScopedClasses['shrink-0']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.svg, __VLS_intrinsics.svg)(__assign({ class: "h-5 w-5 text-green-400" }, { fill: "currentColor", viewBox: "0 0 20 20" }));
    /** @type {__VLS_StyleScopedClasses['h-5']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-5']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.path)({
        'fill-rule': "evenodd",
        d: "M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z",
        'clip-rule': "evenodd",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "ml-3" }));
    /** @type {__VLS_StyleScopedClasses['ml-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-medium text-green-800" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-800']} */ ;
    (__VLS_ctx.successMessage);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ type: "submit", severity: "info", disabled: (__VLS_ctx.isLoading || __VLS_ctx.isVerified) }, { class: "w-full flex justify-center py-3 px-4 font-semibold" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ type: "submit", severity: "info", disabled: (__VLS_ctx.isLoading || __VLS_ctx.isVerified) }, { class: "w-full flex justify-center py-3 px-4 font-semibold" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['py-3']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
var __VLS_5 = __VLS_3.slots.default;
if (__VLS_ctx.isLoading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.svg, __VLS_intrinsics.svg)(__assign({ class: "animate-spin -ml-1 mr-3 h-5 w-5 text-white" }, { xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24" }));
    /** @type {__VLS_StyleScopedClasses['animate-spin']} */ ;
    /** @type {__VLS_StyleScopedClasses['-ml-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-5']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-5']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.circle, __VLS_intrinsics.circle)(__assign({ class: "opacity-25" }, { cx: "12", cy: "12", r: "10", stroke: "currentColor", 'stroke-width': "4" }));
    /** @type {__VLS_StyleScopedClasses['opacity-25']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.path, __VLS_intrinsics.path)(__assign({ class: "opacity-75" }, { fill: "currentColor", d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" }));
    /** @type {__VLS_StyleScopedClasses['opacity-75']} */ ;
}
if (__VLS_ctx.isVerified) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.isLoading ? 'Verifying...' : 'Verify Code');
}
// @ts-ignore
[errorMessage, errorMessage, successMessage, successMessage, isLoading, isLoading, isLoading, isVerified, isVerified,];
var __VLS_3;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)(__assign(__assign({ onClick: (__VLS_ctx.resendCode) }, { type: "button", disabled: (__VLS_ctx.resendCooldown > 0) }), { class: "font-medium text-blue-600 hover:text-blue-500 disabled:text-gray-400 disabled:cursor-not-allowed" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['disabled:text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['disabled:cursor-not-allowed']} */ ;
(__VLS_ctx.resendCooldown > 0 ? "Resend in ".concat(__VLS_ctx.resendCooldown, "s") : 'Resend code');
// @ts-ignore
[resendCode, resendCooldown, resendCooldown, resendCooldown,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
