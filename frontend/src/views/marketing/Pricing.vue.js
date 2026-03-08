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
var vue_router_1 = require("vue-router");
var TopNav_vue_1 = require("../../components/TopNav.vue");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputswitch_1 = require("primevue/inputswitch");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var accordion_1 = require("primevue/accordion");
var accordiontab_1 = require("primevue/accordiontab");
var router = (0, vue_router_1.useRouter)();
var isYearly = (0, vue_1.ref)(false);
// Pricing
var simpleMonthlyPrice = 1490;
var simpleYearlyPrice = 14304; // * 12 * 0.8 (20% discount)
var unlimitedMonthlyPrice = 3500;
var unlimitedYearlyPrice = 33600; // * 12 * 0.8
var simplePrice = (0, vue_1.computed)(function () { return isYearly.value ? simpleYearlyPrice : simpleMonthlyPrice; });
var unlimitedPrice = (0, vue_1.computed)(function () { return isYearly.value ? unlimitedYearlyPrice : unlimitedMonthlyPrice; });
var billingPeriod = (0, vue_1.computed)(function () { return isYearly.value ? 'year' : 'month'; });
var unlimitedYearlySavings = (0, vue_1.computed)(function () { return (unlimitedMonthlyPrice * 12) - unlimitedYearlyPrice; });
// Features
var simpleFeatures = [
    'Up to 500 furniture items',
    'Basic inventory tracking',
    '2 staff accounts',
    'Email support',
    'Basic reports',
    '1 store location'
];
var unlimitedFeatures = [
    'Unlimited furniture items',
    'Advanced inventory management',
    'Unlimited staff accounts',
    'Priority phone & email support',
    'Advanced analytics & reports',
    'Multiple store locations',
    'Custom API access',
    '3D model integration',
    'Bulk import/export',
    'Custom branding'
];
// Comparison table
var comparisonFeatures = (0, vue_1.ref)([
    { feature: 'Furniture Items', simple: 'Up to 500', unlimited: 'Unlimited' },
    { feature: 'Staff Accounts', simple: '2', unlimited: 'Unlimited' },
    { feature: 'Store Locations', simple: '1', unlimited: 'Multiple' },
    { feature: 'Inventory Tracking', simple: true, unlimited: true },
    { feature: 'Sales Analytics', simple: 'Basic', unlimited: 'Advanced' },
    { feature: '3D Model Integration', simple: false, unlimited: true },
    { feature: 'Custom API Access', simple: false, unlimited: true },
    { feature: 'Priority Support', simple: false, unlimited: true },
    { feature: 'Bulk Operations', simple: false, unlimited: true },
    { feature: 'Custom Branding', simple: false, unlimited: true }
]);
// Methods
var toggleBillingCycle = function () {
    console.log("Switched to \u20B1{isYearly.value ? 'yearly' : 'monthly'} billing");
};
var selectPlan = function (plan) {
    console.log("Selected ".concat(plan, " plan"));
    if (plan === 'unlimited') {
        // Redirect to signup with plan parameter
        router.push({
            path: '/register',
            query: { plan: 'unlimited' }
        });
    }
    else {
        router.push({
            path: '/register',
            query: { plan: 'simple' }
        });
    }
};
var startFreeTrial = function () {
    router.push('/register');
};
var scheduleDemo = function () {
    // Open calendar scheduling or redirect
    window.open('https://calendly.com/furnimanage/demo', '_blank');
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['p-card']} */ ;
/** @type {__VLS_StyleScopedClasses['p-card']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "min-h-screen bg-linear-to-b from-gray-50 to-white" }));
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-b']} */ ;
/** @type {__VLS_StyleScopedClasses['from-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['to-white']} */ ;
var __VLS_0 = TopNav_vue_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)(__assign({ class: "pt-20 pb-16 px-4 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['pt-20']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-16']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto text-center" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-6" }));
/** @type {__VLS_StyleScopedClasses['inline-flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-700']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-tag mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-tag']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-6" }));
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['md:text-5xl']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:text-6xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-transparent bg-clip-text bg-linear-to-r from-blue-600 to-purple-600" }));
/** @type {__VLS_StyleScopedClasses['text-transparent']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-clip-text']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['to-purple-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xl text-gray-600 max-w-3xl mx-auto mb-10" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-10']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center space-x-4 mb-12" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-12']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-700 font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
var __VLS_5;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.isYearly) })));
var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.isYearly) })], __VLS_functionalComponentArgsRest(__VLS_6), false));
var __VLS_10;
var __VLS_11 = ({ change: {} },
    { onChange: (__VLS_ctx.toggleBillingCycle) });
var __VLS_8;
var __VLS_9;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-700 font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded" }));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
/** @type {__VLS_StyleScopedClasses['px-2']} */ ;
/** @type {__VLS_StyleScopedClasses['py-1']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)(__assign({ class: "py-12 px-4 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['py-12']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-6xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['max-w-6xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid md:grid-cols-2 gap-8 lg:gap-12" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-8']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:gap-12']} */ ;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ class: "border-2 border-gray-200 hover:border-blue-300 transition-all duration-300" })));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ class: "border-2 border-gray-200 hover:border-blue-300 transition-all duration-300" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
/** @type {__VLS_StyleScopedClasses['border-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:border-blue-300']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-300']} */ ;
var __VLS_17 = __VLS_15.slots.default;
{
    var __VLS_18 = __VLS_15.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-8" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 mx-auto bg-blue-100 rounded-xl flex items-center justify-center mb-6" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    // @ts-ignore
    [isYearly, toggleBillingCycle,];
}
{
    var __VLS_19 = __VLS_15.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 pt-0" }));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-0']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center mb-8" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-5xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-5xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.simplePrice);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500 ml-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    (__VLS_ctx.billingPeriod);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (__VLS_ctx.isYearly ? 'yearly' : 'monthly');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mb-8" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.simpleFeatures)); _i < _a.length; _i++) {
        var feature = _a[_i][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (feature) }, { class: "flex items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-green-500 mr-3" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        (feature);
        // @ts-ignore
        [isYearly, simplePrice, billingPeriod, simpleFeatures,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times mr-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-times']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "line-through" }));
    /** @type {__VLS_StyleScopedClasses['line-through']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times mr-3" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-times']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "line-through" }));
    /** @type {__VLS_StyleScopedClasses['line-through']} */ ;
    var __VLS_20 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign(__assign({ 'onClick': {} }, { label: "Get Started Free" }), { class: "w-full bg-linear-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-none" })));
    var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Get Started Free" }), { class: "w-full bg-linear-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-none" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
    var __VLS_25 = void 0;
    var __VLS_26 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.selectPlan('simple');
                // @ts-ignore
                [selectPlan,];
            } });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:from-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:to-blue-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-none']} */ ;
    var __VLS_27 = __VLS_23.slots.default;
    {
        var __VLS_28 = __VLS_23.slots.icon;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-rocket mr-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-rocket']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_23;
    var __VLS_24;
    // @ts-ignore
    [];
}
{
    var __VLS_29 = __VLS_15.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-4 border-t border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-shield mr-1" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-shield']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_15;
var __VLS_30;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign({ class: "border-2 border-purple-300 shadow-xl relative overflow-hidden" })));
var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign({ class: "border-2 border-purple-300 shadow-xl relative overflow-hidden" })], __VLS_functionalComponentArgsRest(__VLS_31), false));
/** @type {__VLS_StyleScopedClasses['border-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border-purple-300']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['relative']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
var __VLS_35 = __VLS_33.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "absolute top-0 right-0" }));
/** @type {__VLS_StyleScopedClasses['absolute']} */ ;
/** @type {__VLS_StyleScopedClasses['top-0']} */ ;
/** @type {__VLS_StyleScopedClasses['right-0']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-linear-to-r from-purple-600 to-pink-600 text-white px-6 py-2 transform rotate-45 translate-x-8 translate-y-4" }));
/** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
/** @type {__VLS_StyleScopedClasses['from-purple-600']} */ ;
/** @type {__VLS_StyleScopedClasses['to-pink-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['transform']} */ ;
/** @type {__VLS_StyleScopedClasses['rotate-45']} */ ;
/** @type {__VLS_StyleScopedClasses['translate-x-8']} */ ;
/** @type {__VLS_StyleScopedClasses['translate-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
{
    var __VLS_36 = __VLS_33.slots.header;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-8" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-8']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 mx-auto bg-linear-to-r from-purple-100 to-pink-100 rounded-xl flex items-center justify-center mb-6" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-purple-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-pink-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-infinity text-purple-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-infinity']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    // @ts-ignore
    [];
}
{
    var __VLS_37 = __VLS_33.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 pt-0" }));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-0']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center mb-8" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-5xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-5xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.unlimitedPrice);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-500 ml-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
    (__VLS_ctx.billingPeriod);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (__VLS_ctx.isYearly ? 'yearly' : 'monthly');
    if (__VLS_ctx.isYearly) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-2" }));
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-green-600 font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle mr-1" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
        (__VLS_ctx.unlimitedYearlySavings);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mb-8" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.unlimitedFeatures)); _b < _c.length; _b++) {
        var feature = _c[_b][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (feature) }, { class: "flex items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-green-500 mr-3" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        (feature);
        // @ts-ignore
        [isYearly, isYearly, billingPeriod, unlimitedPrice, unlimitedYearlySavings, unlimitedFeatures,];
    }
    var __VLS_38 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38(__assign(__assign({ 'onClick': {} }, { label: "Start Unlimited Trial", severity: "secondary" }), { class: "w-full bg-linear-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 border-none text-white" })));
    var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Start Unlimited Trial", severity: "secondary" }), { class: "w-full bg-linear-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 border-none text-white" })], __VLS_functionalComponentArgsRest(__VLS_39), false));
    var __VLS_43 = void 0;
    var __VLS_44 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.selectPlan('unlimited');
                // @ts-ignore
                [selectPlan,];
            } });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-purple-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-pink-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:from-purple-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:to-pink-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-none']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
    var __VLS_45 = __VLS_41.slots.default;
    {
        var __VLS_46 = __VLS_41.slots.icon;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-star mr-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-star']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_41;
    var __VLS_42;
    // @ts-ignore
    [];
}
{
    var __VLS_47 = __VLS_33.slots.footer;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center p-4 border-t border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-crown mr-1" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-crown']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_33;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)(__assign({ class: "py-20 px-4 sm:px-6 lg:px-8 bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['py-20']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-5xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['max-w-5xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-3xl font-bold text-center text-gray-900 mb-12" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-12']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white rounded-xl shadow-lg overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
var __VLS_48;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
datatable_1.default;
// @ts-ignore
var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48(__assign({ value: (__VLS_ctx.comparisonFeatures) }, { class: "p-datatable-sm" })));
var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.comparisonFeatures) }, { class: "p-datatable-sm" })], __VLS_functionalComponentArgsRest(__VLS_49), false));
/** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
var __VLS_53 = __VLS_51.slots.default;
var __VLS_54;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54({
    field: "feature",
    header: "Feature",
    headerClass: "font-bold",
}));
var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([{
        field: "feature",
        header: "Feature",
        headerClass: "font-bold",
    }], __VLS_functionalComponentArgsRest(__VLS_55), false));
var __VLS_59 = __VLS_57.slots.default;
{
    var __VLS_60 = __VLS_57.slots.body;
    var data = __VLS_vSlot(__VLS_60)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (data.feature);
    // @ts-ignore
    [comparisonFeatures,];
}
// @ts-ignore
[];
var __VLS_57;
var __VLS_61;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61({
    header: "Simple",
    headerClass: "text-center",
}));
var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([{
        header: "Simple",
        headerClass: "text-center",
    }], __VLS_functionalComponentArgsRest(__VLS_62), false));
var __VLS_66 = __VLS_64.slots.default;
{
    var __VLS_67 = __VLS_64.slots.body;
    var data = __VLS_vSlot(__VLS_67)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    if (data.simple) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-green-500 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times text-gray-300 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-times']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_64;
var __VLS_68;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
    header: "Unlimited",
    headerClass: "text-center",
}));
var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
        header: "Unlimited",
        headerClass: "text-center",
    }], __VLS_functionalComponentArgsRest(__VLS_69), false));
var __VLS_73 = __VLS_71.slots.default;
{
    var __VLS_74 = __VLS_71.slots.body;
    var data = __VLS_vSlot(__VLS_74)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    if (data.unlimited) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check text-green-500 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times text-gray-300 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-times']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_71;
// @ts-ignore
[];
var __VLS_51;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)(__assign({ class: "py-20 px-4 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['py-20']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-4xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-3xl font-bold text-center text-gray-900 mb-12" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-12']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
var __VLS_75;
/** @ts-ignore @type {typeof __VLS_components.Accordion | typeof __VLS_components.Accordion} */
accordion_1.default;
// @ts-ignore
var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75({
    activeIndex: (0),
}));
var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([{
        activeIndex: (0),
    }], __VLS_functionalComponentArgsRest(__VLS_76), false));
var __VLS_80 = __VLS_78.slots.default;
var __VLS_81;
/** @ts-ignore @type {typeof __VLS_components.AccordionTab | typeof __VLS_components.AccordionTab} */
accordiontab_1.default;
// @ts-ignore
var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81({
    header: "Can I switch plans anytime?",
}));
var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([{
        header: "Can I switch plans anytime?",
    }], __VLS_functionalComponentArgsRest(__VLS_82), false));
var __VLS_86 = __VLS_84.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
// @ts-ignore
[];
var __VLS_84;
var __VLS_87;
/** @ts-ignore @type {typeof __VLS_components.AccordionTab | typeof __VLS_components.AccordionTab} */
accordiontab_1.default;
// @ts-ignore
var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
    header: "Is there a free trial?",
}));
var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
        header: "Is there a free trial?",
    }], __VLS_functionalComponentArgsRest(__VLS_88), false));
var __VLS_92 = __VLS_90.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
// @ts-ignore
[];
var __VLS_90;
var __VLS_93;
/** @ts-ignore @type {typeof __VLS_components.AccordionTab | typeof __VLS_components.AccordionTab} */
accordiontab_1.default;
// @ts-ignore
var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({
    header: "What payment methods do you accept?",
}));
var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{
        header: "What payment methods do you accept?",
    }], __VLS_functionalComponentArgsRest(__VLS_94), false));
var __VLS_98 = __VLS_96.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
// @ts-ignore
[];
var __VLS_96;
var __VLS_99;
/** @ts-ignore @type {typeof __VLS_components.AccordionTab | typeof __VLS_components.AccordionTab} */
accordiontab_1.default;
// @ts-ignore
var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99({
    header: "Can I cancel anytime?",
}));
var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([{
        header: "Can I cancel anytime?",
    }], __VLS_functionalComponentArgsRest(__VLS_100), false));
var __VLS_104 = __VLS_102.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
// @ts-ignore
[];
var __VLS_102;
// @ts-ignore
[];
var __VLS_78;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)(__assign({ class: "py-20 px-4 sm:px-6 lg:px-8 bg-linear-to-r from-blue-600 to-purple-600" }));
/** @type {__VLS_StyleScopedClasses['py-20']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['to-purple-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-4xl mx-auto text-center" }));
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-3xl font-bold text-white mb-6" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xl text-blue-100 mb-10" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-10']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row gap-4 justify-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
var __VLS_105;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105(__assign(__assign({ 'onClick': {} }, { label: "Start Free Trial", severity: "secondary" }), { class: "px-8 py-3 bg-white text-blue-600 font-bold hover:bg-gray-100" })));
var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Start Free Trial", severity: "secondary" }), { class: "px-8 py-3 bg-white text-blue-600 font-bold hover:bg-gray-100" })], __VLS_functionalComponentArgsRest(__VLS_106), false));
var __VLS_110;
var __VLS_111 = ({ click: {} },
    { onClick: (__VLS_ctx.startFreeTrial) });
/** @type {__VLS_StyleScopedClasses['px-8']} */ ;
/** @type {__VLS_StyleScopedClasses['py-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
var __VLS_112 = __VLS_108.slots.default;
{
    var __VLS_113 = __VLS_108.slots.icon;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-play mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-play']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    // @ts-ignore
    [startFreeTrial,];
}
// @ts-ignore
[];
var __VLS_108;
var __VLS_109;
var __VLS_114;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign(__assign({ 'onClick': {} }, { label: "Schedule a Demo", outlined: true }), { class: "px-8 py-3 border-2 border-white text-white hover:bg-white/10" })));
var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Schedule a Demo", outlined: true }), { class: "px-8 py-3 border-2 border-white text-white hover:bg-white/10" })], __VLS_functionalComponentArgsRest(__VLS_115), false));
var __VLS_119;
var __VLS_120 = ({ click: {} },
    { onClick: (__VLS_ctx.scheduleDemo) });
/** @type {__VLS_StyleScopedClasses['px-8']} */ ;
/** @type {__VLS_StyleScopedClasses['py-3']} */ ;
/** @type {__VLS_StyleScopedClasses['border-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border-white']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-white/10']} */ ;
var __VLS_121 = __VLS_117.slots.default;
{
    var __VLS_122 = __VLS_117.slots.icon;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    // @ts-ignore
    [scheduleDemo,];
}
// @ts-ignore
[];
var __VLS_117;
var __VLS_118;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-blue-200 text-sm mt-8" }));
/** @type {__VLS_StyleScopedClasses['text-blue-200']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-lock mr-1" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-lock']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.footer, __VLS_intrinsics.footer)(__assign({ class: "bg-gray-900 text-white py-12 px-4 sm:px-6 lg:px-8" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-900']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['py-12']} */ ;
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:px-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col md:flex-row justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6 md:mb-0" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
/** @type {__VLS_StyleScopedClasses['md:mb-0']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-2xl font-bold mb-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-400" }));
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-gray-400 hover:text-white" }));
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-white']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-file']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-gray-400 hover:text-white" }));
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-white']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-book text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-book']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "#" }, { class: "text-gray-400 hover:text-white" }));
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-white']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-phone text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-phone']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-800 mt-8 pt-8 text-center text-gray-400" }));
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-8']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
