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
var dialog_1 = require("primevue/dialog");
var button_1 = require("primevue/button");
var props = defineProps({
    // v-model for visibility
    modelValue: {
        type: Boolean,
        required: true
    },
    // 'success' or 'error'
    type: {
        type: String,
        default: 'success'
    },
    // Main message
    message: {
        type: String,
        default: ''
    },
    // Error details (optional)
    errors: {
        type: [String, Array],
        default: null
    },
    // Custom title (optional)
    title: {
        type: String,
        default: ''
    },
    // Custom button text (optional)
    buttonText: {
        type: String,
        default: 'OK'
    },
    // Auto close after seconds (0 = no auto close)
    autoClose: {
        type: Number,
        default: 0
    }
});
var emit = defineEmits(['update:modelValue', 'confirm', 'close']);
// Auto close functionality
(0, vue_1.watch)(function () { return props.modelValue; }, function (newVal) {
    if (newVal && props.autoClose > 0) {
        setTimeout(function () {
            onConfirm();
        }, props.autoClose * 1000);
    }
});
// Methods
var onConfirm = function () {
    emit('confirm');
    emit('update:modelValue', false);
    emit('close');
};
var onHide = function () {
    emit('close');
};
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign(__assign({ 'onHide': {} }, { visible: (__VLS_ctx.modelValue), header: (__VLS_ctx.title) }), { style: ({ width: '350px' }) }), { modal: (true), closable: (true), dismissableMask: (true) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onHide': {} }, { visible: (__VLS_ctx.modelValue), header: (__VLS_ctx.title) }), { style: ({ width: '350px' }) }), { modal: (true), closable: (true), dismissableMask: (true) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ hide: {} },
    { onHide: (__VLS_ctx.onHide) });
var __VLS_7 = {};
var __VLS_8 = __VLS_3.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col items-center text-center py-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
if (__VLS_ctx.type === 'success') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-3" }));
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-4xl text-green-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
}
if (__VLS_ctx.type === 'error') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-3" }));
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto" }));
    /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-16']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle text-4xl text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-700 mb-4" }));
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
(__VLS_ctx.message);
if (__VLS_ctx.type === 'error' && __VLS_ctx.errors) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-full mb-4" }));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    if (typeof __VLS_ctx.errors === 'string') {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-red-600 bg-red-50 p-2 rounded" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        (__VLS_ctx.errors);
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.ul, __VLS_intrinsics.ul)(__assign({ class: "text-sm text-red-600 bg-red-50 p-2 rounded text-left" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-red-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-left']} */ ;
        for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.errors)); _i < _a.length; _i++) {
            var _b = _a[_i], error = _b[0], index = _b[1];
            __VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({
                key: (index),
            });
            (error);
            // @ts-ignore
            [modelValue, title, onHide, type, type, type, message, errors, errors, errors, errors,];
        }
    }
}
{
    var __VLS_9 = __VLS_3.slots.footer;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign(__assign(__assign({ 'onClick': {} }, { label: (__VLS_ctx.buttonText), severity: (__VLS_ctx.type === 'success' ? 'success' : 'secondary') }), { class: "w-full" }), { autofocus: true })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { label: (__VLS_ctx.buttonText), severity: (__VLS_ctx.type === 'success' ? 'success' : 'secondary') }), { class: "w-full" }), { autofocus: true })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    var __VLS_15 = void 0;
    var __VLS_16 = ({ click: {} },
        { onClick: (__VLS_ctx.onConfirm) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_13;
    var __VLS_14;
    // @ts-ignore
    [type, buttonText, onConfirm,];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_4;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    emits: {},
    props: {
        // v-model for visibility
        modelValue: {
            type: Boolean,
            required: true
        },
        // 'success' or 'error'
        type: {
            type: String,
            default: 'success'
        },
        // Main message
        message: {
            type: String,
            default: ''
        },
        // Error details (optional)
        errors: {
            type: [String, Array],
            default: null
        },
        // Custom title (optional)
        title: {
            type: String,
            default: ''
        },
        // Custom button text (optional)
        buttonText: {
            type: String,
            default: 'OK'
        },
        // Auto close after seconds (0 = no auto close)
        autoClose: {
            type: Number,
            default: 0
        }
    },
});
exports.default = {};
