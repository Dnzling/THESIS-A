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
var auth_1 = require("../../../stores/auth");
var usetoast_1 = require("primevue/usetoast");
var router = (0, vue_router_1.useRouter)();
var authStore = (0, auth_1.useAuthStore)();
var toast = (0, usetoast_1.useToast)();
// --- State ---
var saving = (0, vue_1.ref)(false);
var showSuccessDialog = (0, vue_1.ref)(false);
var showErrorDialog = (0, vue_1.ref)(false);
var errorMessage = (0, vue_1.ref)('');
var errors = (0, vue_1.ref)({});
// --- Form ---
var form = (0, vue_1.ref)({
    name: '',
    code: '',
    shift_type: 'fixed',
    start_time: '09:00',
    end_time: '18:00',
    break_start: '',
    break_end: '',
    total_hours: '8',
    week_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    grace_period_minutes: 15,
    has_night_diff: false,
    night_diff_rate: 1.10,
    min_employees_required: 1,
    color: '#3b82f6',
    description: ''
});
// --- Options ---
var shiftTypeOptions = [
    { label: 'Fixed', value: 'fixed' },
    { label: 'Rotating', value: 'rotating' },
    { label: 'Flexible', value: 'flexible' }
];
var weekDays = [
    { label: 'M', full: 'Monday', value: 'monday' },
    { label: 'T', full: 'Tuesday', value: 'tuesday' },
    { label: 'W', full: 'Wednesday', value: 'wednesday' },
    { label: 'T', full: 'Thursday', value: 'thursday' },
    { label: 'F', full: 'Friday', value: 'friday' },
    { label: 'S', full: 'Saturday', value: 'saturday' },
    { label: 'S', full: 'Sunday', value: 'sunday' }
];
// --- Methods ---
var toggleDay = function (day) {
    var index = form.value.week_days.indexOf(day);
    if (index === -1) {
        form.value.week_days.push(day);
    }
    else {
        form.value.week_days.splice(index, 1);
    }
};
var recalcHours = function () {
    var _a, _b, _c, _d;
    if (!form.value.start_time || !form.value.end_time)
        return;
    var startParts = form.value.start_time.split(':').map(Number);
    var endParts = form.value.end_time.split(':').map(Number);
    var sh = (_a = startParts[0]) !== null && _a !== void 0 ? _a : 0;
    var sm = (_b = startParts[1]) !== null && _b !== void 0 ? _b : 0;
    var eh = (_c = endParts[0]) !== null && _c !== void 0 ? _c : 0;
    var em = (_d = endParts[1]) !== null && _d !== void 0 ? _d : 0;
    var totalMins = (eh * 60 + em) - (sh * 60 + sm);
    if (totalMins < 0)
        totalMins += 24 * 60;
    form.value.total_hours = String(parseFloat((totalMins / 60).toFixed(2)));
};
var createShift = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, err_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                errors.value = {};
                if (!form.value.name || !form.value.code || !form.value.start_time || !form.value.end_time) {
                    toast.add({ severity: 'warn', summary: 'Validation', detail: 'Please fill in all required fields', life: 3000 });
                    return [2 /*return*/];
                }
                saving.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                payload = {
                    name: form.value.name,
                    code: form.value.code,
                    shift_type: form.value.shift_type,
                    start_time: form.value.start_time,
                    end_time: form.value.end_time,
                    total_hours: Number(form.value.total_hours),
                    week_days: form.value.week_days.length > 0 ? form.value.week_days : null,
                    grace_period_minutes: form.value.grace_period_minutes,
                    has_night_diff: form.value.has_night_diff,
                    night_diff_rate: form.value.has_night_diff ? form.value.night_diff_rate : 1.10,
                    min_employees_required: form.value.min_employees_required,
                    color: form.value.color,
                    description: form.value.description || null
                };
                if (form.value.break_start)
                    payload.break_start = form.value.break_start;
                if (form.value.break_end)
                    payload.break_end = form.value.break_end;
                return [4 /*yield*/, axios_1.default.post('/api/shifts', payload, {
                        headers: { Authorization: "Bearer ".concat(authStore.token) }
                    })];
            case 2:
                response = _d.sent();
                if (response.data.success) {
                    showSuccessDialog.value = true;
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _d.sent();
                if (((_a = err_1.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors.value = err_1.response.data.errors || {};
                    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fix the highlighted fields', life: 4000 });
                }
                else {
                    errorMessage.value = ((_c = (_b = err_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to create shift. Please try again.';
                    showErrorDialog.value = true;
                }
                return [3 /*break*/, 5];
            case 4:
                saving.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var cancel = function () {
    router.push({ name: 'hr.shifts' });
};
var goBack = function () {
    router.push({ name: 'hr.shifts' });
};
var createAnother = function () {
    showSuccessDialog.value = false;
    form.value = {
        name: '',
        code: '',
        shift_type: 'fixed',
        start_time: '09:00',
        end_time: '18:00',
        break_start: '',
        break_end: '',
        total_hours: '8',
        week_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        grace_period_minutes: 15,
        has_night_diff: false,
        night_diff_rate: 1.10,
        min_employees_required: 1,
        color: '#3b82f6',
        description: ''
    };
    errors.value = {};
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 max-w-4xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-2xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.cancel) });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 space-y-6" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign({ modelValue: (__VLS_ctx.form.name), placeholder: "e.g., Morning Shift" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.name }) })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.name), placeholder: "e.g., Morning Shift" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.name }) })], __VLS_functionalComponentArgsRest(__VLS_8), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.name[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign(__assign({ modelValue: (__VLS_ctx.form.code), placeholder: "e.g., MORN-01" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.code }) })));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.code), placeholder: "e.g., MORN-01" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.code }) })], __VLS_functionalComponentArgsRest(__VLS_13), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.code) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.code[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_17;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign(__assign({ modelValue: (__VLS_ctx.form.shift_type), options: (__VLS_ctx.shiftTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select type" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.shift_type }) })));
var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.shift_type), options: (__VLS_ctx.shiftTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select type" }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.shift_type }) })], __VLS_functionalComponentArgsRest(__VLS_18), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.shift_type) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.shift_type[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign({ type: "color" }, { class: "h-10 w-16 rounded border border-gray-300 cursor-pointer" }));
(__VLS_ctx.form.color);
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['w-16']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
var __VLS_22;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_23 = __VLS_asFunctionalComponent1(__VLS_22, new __VLS_22(__assign({ modelValue: (__VLS_ctx.form.color), placeholder: "#3b82f6" }, { class: "flex-1" })));
var __VLS_24 = __VLS_23.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.color), placeholder: "#3b82f6" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_23), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_27;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_28 = __VLS_asFunctionalComponent1(__VLS_27, new __VLS_27(__assign(__assign({ modelValue: (__VLS_ctx.form.description), rows: "2" }, { class: "w-full" }), { placeholder: "Optional description for this shift..." })));
var __VLS_29 = __VLS_28.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.description), rows: "2" }, { class: "w-full" }), { placeholder: "Optional description for this shift..." })], __VLS_functionalComponentArgsRest(__VLS_28), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-6" }));
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.start_time), type: "time" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.start_time }) })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.start_time), type: "time" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.start_time }) })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ change: {} },
    { onChange: (__VLS_ctx.recalcHours) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_35;
var __VLS_36;
if (__VLS_ctx.errors.start_time) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.start_time[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.end_time), type: "time" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.end_time }) })));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.end_time), type: "time" }), { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.end_time }) })], __VLS_functionalComponentArgsRest(__VLS_40), false));
var __VLS_44;
var __VLS_45 = ({ change: {} },
    { onChange: (__VLS_ctx.recalcHours) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
var __VLS_42;
var __VLS_43;
if (__VLS_ctx.errors.end_time) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.end_time[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ modelValue: (__VLS_ctx.form.break_start), type: "time" }, { class: "w-full" })));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.break_start), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
var __VLS_51;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ modelValue: (__VLS_ctx.form.break_end), type: "time" }, { class: "w-full" })));
var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.break_end), type: "time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_56;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign(__assign({ modelValue: (__VLS_ctx.form.total_hours) }, { class: "w-full bg-gray-50" }), { placeholder: "Auto-calculated" })));
var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.total_hours) }, { class: "w-full bg-gray-50" }), { placeholder: "Auto-calculated" })], __VLS_functionalComponentArgsRest(__VLS_57), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
if (__VLS_ctx.errors.total_hours) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.total_hours[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_61;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61(__assign(__assign({ modelValue: (__VLS_ctx.form.grace_period_minutes), min: (0), max: (60) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.grace_period_minutes }) })));
var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.grace_period_minutes), min: (0), max: (60) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.grace_period_minutes }) })], __VLS_functionalComponentArgsRest(__VLS_62), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.grace_period_minutes) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.grace_period_minutes[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-6" }));
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var _loop_1 = function (day) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (day.value) }, { class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.toggleDay(day.value);
            // @ts-ignore
            [cancel, form, form, form, form, form, form, form, form, form, form, form, form, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, shiftTypeOptions, recalcHours, recalcHours, weekDays, toggleDay,];
        } }, { class: "border rounded-lg p-3 text-center cursor-pointer transition-colors" }), { class: (__VLS_ctx.form.week_days.includes(day.value) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50 border-gray-200') }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm font-medium" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (day.label);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-500 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (day.full);
    // @ts-ignore
    [form,];
};
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.weekDays)); _i < _a.length; _i++) {
    var day = _a[_i][0];
    _loop_1(day);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-6" }));
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_66;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign(__assign({ modelValue: (__VLS_ctx.form.min_employees_required), min: (1), max: (100) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.min_employees_required }) })));
var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.min_employees_required), min: (1), max: (100) }, { class: "w-full" }), { class: ({ 'p-invalid': __VLS_ctx.errors.min_employees_required }) })], __VLS_functionalComponentArgsRest(__VLS_67), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.min_employees_required) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.min_employees_required[0]);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-100 pt-6" }));
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_71;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
    modelValue: (__VLS_ctx.form.has_night_diff),
    inputId: "hasNightDiff",
    binary: true,
}));
var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.form.has_night_diff),
        inputId: "hasNightDiff",
        binary: true,
    }], __VLS_functionalComponentArgsRest(__VLS_72), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "hasNightDiff" }, { class: "text-sm font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
if (__VLS_ctx.form.has_night_diff) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.night_diff_rate), min: (1), max: (3), step: (0.01), minFractionDigits: (2), maxFractionDigits: (2) }, { class: "w-full" }), { placeholder: "e.g., 1.10" }), { class: ({ 'p-invalid': __VLS_ctx.errors.night_diff_rate }) })));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.night_diff_rate), min: (1), max: (3), step: (0.01), minFractionDigits: (2), maxFractionDigits: (2) }, { class: "w-full" }), { placeholder: "e.g., 1.10" }), { class: ({ 'p-invalid': __VLS_ctx.errors.night_diff_rate }) })], __VLS_functionalComponentArgsRest(__VLS_77), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-400 text-xs" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    if (__VLS_ctx.errors.night_diff_rate) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500 block" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        (__VLS_ctx.errors.night_diff_rate[0]);
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-2" }));
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50/50']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_81;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true })));
var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_82), false));
var __VLS_86;
var __VLS_87 = ({ click: {} },
    { onClick: (__VLS_ctx.cancel) });
var __VLS_84;
var __VLS_85;
var __VLS_88;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ 'onClick': {} }, { label: "Create Shift", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.saving) })));
var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Shift", icon: "pi pi-check", severity: "info", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_89), false));
var __VLS_93;
var __VLS_94 = ({ click: {} },
    { onClick: (__VLS_ctx.createShift) });
var __VLS_91;
var __VLS_92;
var __VLS_95;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign(__assign({ visible: (__VLS_ctx.showSuccessDialog), modal: true }, { style: ({ width: '400px' }) }), { closable: (false) })));
var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showSuccessDialog), modal: true }, { style: ({ width: '400px' }) }), { closable: (false) })], __VLS_functionalComponentArgsRest(__VLS_96), false));
var __VLS_100 = __VLS_98.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-4" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4" }));
/** @type {__VLS_StyleScopedClasses['w-16']} */ ;
/** @type {__VLS_StyleScopedClasses['h-16']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-green-500 text-2xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mb-4" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
(__VLS_ctx.form.name);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 justify-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
var __VLS_101;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101(__assign({ 'onClick': {} }, { label: "Back to Shifts", severity: "info" })));
var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back to Shifts", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_102), false));
var __VLS_106;
var __VLS_107 = ({ click: {} },
    { onClick: (__VLS_ctx.goBack) });
var __VLS_104;
var __VLS_105;
var __VLS_108;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108(__assign({ 'onClick': {} }, { label: "Create Another", severity: "secondary", outlined: true })));
var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Another", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_109), false));
var __VLS_113;
var __VLS_114 = ({ click: {} },
    { onClick: (__VLS_ctx.createAnother) });
var __VLS_111;
var __VLS_112;
// @ts-ignore
[cancel, form, form, form, form, form, errors, errors, errors, errors, errors, errors, saving, createShift, showSuccessDialog, goBack, createAnother,];
var __VLS_98;
var __VLS_115;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115(__assign({ visible: (__VLS_ctx.showErrorDialog), modal: true, header: "Error" }, { style: ({ width: '400px' }) })));
var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showErrorDialog), modal: true, header: "Error" }, { style: ({ width: '400px' }) })], __VLS_functionalComponentArgsRest(__VLS_116), false));
var __VLS_120 = __VLS_118.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3 p-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle text-red-500 text-3xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
(__VLS_ctx.errorMessage);
{
    var __VLS_121 = __VLS_118.slots.footer;
    var __VLS_122 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_123), false));
    var __VLS_127 = void 0;
    var __VLS_128 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showErrorDialog = false;
                // @ts-ignore
                [showErrorDialog, showErrorDialog, errorMessage,];
            } });
    var __VLS_125;
    var __VLS_126;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_118;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
