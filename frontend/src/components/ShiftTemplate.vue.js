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
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var props = defineProps();
var emit = defineEmits(['apply']);
var bgColorClass = (0, vue_1.computed)(function () {
    var colors = {
        'Morning': 'bg-blue-50 border-blue-200 hover:bg-blue-100',
        'Evening': 'bg-orange-50 border-orange-200 hover:bg-orange-100',
        'Night': 'bg-purple-50 border-purple-200 hover:bg-purple-100'
    };
    return colors[props.template.type] || 'bg-gray-50 border-gray-200 hover:bg-gray-100';
});
var dragStart = function (event) {
    var _a;
    (_a = event.dataTransfer) === null || _a === void 0 ? void 0 : _a.setData('text/plain', JSON.stringify(props.template));
};
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onDragstart: (__VLS_ctx.dragStart) }, { class: (['p-3 rounded-lg cursor-move border', __VLS_ctx.bgColorClass]) }), { draggable: "true" }));
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-move']} */ ;
/** @type {__VLS_StyleScopedClasses['border']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium text-sm" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
(__VLS_ctx.template.name);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs opacity-75" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['opacity-75']} */ ;
(__VLS_ctx.template.start);
(__VLS_ctx.template.end);
// @ts-ignore
[dragStart, bgColorClass, template, template, template,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    emits: {},
    __typeProps: {},
});
exports.default = {};
