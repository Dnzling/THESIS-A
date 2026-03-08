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
var axios_1 = require("axios");
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var vue_router_1 = require("vue-router");
var RegisterForm_vue_1 = require("../../components/auth/RegisterForm.vue");
var toast = (0, usetoast_1.useToast)();
var router = (0, vue_router_1.useRouter)();
var isSubmitting = (0, vue_1.ref)(false);
// Handle form submission ===== for API =====
var handleRegister = function (formData) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1, errors, firstError;
    var _a, _b, _c, _d;
    return __generator(this, function (_e) {
        switch (_e.label) {
            case 0:
                isSubmitting.value = true;
                _e.label = 1;
            case 1:
                _e.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.post('/api/auth/register', {
                        fname: formData.fname,
                        lname: formData.lname,
                        email: formData.email,
                        password: formData.password,
                        role_id: 2,
                        birthday: formData.birthday,
                        device_name: 'web-browswer'
                    })
                    // Success
                ];
            case 2:
                response = _e.sent();
                // Success
                localStorage.setItem('register_token', response.data.user.access_token);
                router.push('/verify-otp');
                return [3 /*break*/, 5];
            case 3:
                error_1 = _e.sent();
                console.error('Registration error:', error_1);
                // Handle validation errors
                if (((_a = error_1.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors = (_b = error_1.response.data) === null || _b === void 0 ? void 0 : _b.errors;
                    // Show first error in toast
                    if (errors && Object.keys(errors).length > 0) {
                        firstError = Object.values(errors)[0][0];
                        toast.add({
                            severity: 'error',
                            summary: 'Validation Error',
                            detail: firstError,
                            life: 5000
                        });
                    }
                }
                else {
                    // General error
                    toast.add({
                        severity: 'error',
                        summary: 'Registration Failed',
                        detail: ((_d = (_c = error_1.response) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.message) || 'Something went wrong. Please try again.',
                        life: 5000
                    });
                }
                throw error_1;
            case 4:
                isSubmitting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var handleFormError = function (errorMessage) {
    toast.add({
        severity: 'warn',
        summary: 'Form Error',
        detail: errorMessage,
        life: 3000
    });
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
var __VLS_0 = RegisterForm_vue_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onSubmit': {} }, { 'onError': {} }), { isSubmitting: (__VLS_ctx.isSubmitting) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onSubmit': {} }, { 'onError': {} }), { isSubmitting: (__VLS_ctx.isSubmitting) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ submit: {} },
    { onSubmit: (__VLS_ctx.handleRegister) });
var __VLS_7 = ({ error: {} },
    { onError: (__VLS_ctx.handleFormError) });
var __VLS_8 = {};
var __VLS_3;
var __VLS_4;
// @ts-ignore
[isSubmitting, handleRegister, handleFormError,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
