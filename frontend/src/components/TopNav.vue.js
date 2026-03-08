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
var route = (0, vue_router_1.useRoute)();
var isMobileMenuOpen = (0, vue_1.ref)(false);
var toggleMobileMenu = function () {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
var closeMobileMenu = function () {
    isMobileMenuOpen.value = false;
};
// Close mobile menu on route change
var vue_2 = require("vue");
(0, vue_2.watch)(function () { return route.path; }, closeMobileMenu);
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "p-6 border-b-2 border-gray-200 bg-white shadow-sm" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b-2']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onClick': {} }, { to: "/" }), { class: "flex items-center space-x-3 group" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { to: "/" }), { class: "flex items-center space-x-3 group" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.closeMobileMenu) });
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
/** @type {__VLS_StyleScopedClasses['group']} */ ;
var __VLS_7 = __VLS_3.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-35" }));
/** @type {__VLS_StyleScopedClasses['w-35']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.img)({
    src: "../../public/Logo Landscape.png",
    alt: "",
});
// @ts-ignore
[closeMobileMenu,];
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "hidden md:flex items-center space-x-1" }));
/** @type {__VLS_StyleScopedClasses['hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-1']} */ ;
var __VLS_8;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_9 = __VLS_asFunctionalComponent1(__VLS_8, new __VLS_8(__assign(__assign({ to: "/" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
        'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/',
        'hover:shadow': __VLS_ctx.route.path !== '/'
    }) })));
var __VLS_10 = __VLS_9.apply(void 0, __spreadArray([__assign(__assign({ to: "/" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
            'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/',
            'hover:shadow': __VLS_ctx.route.path !== '/'
        }) })], __VLS_functionalComponentArgsRest(__VLS_9), false));
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow']} */ ;
var __VLS_13 = __VLS_11.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-home mr-2" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-home']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[route, route,];
var __VLS_11;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign({ to: "/about" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
        'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/about',
        'hover:shadow': __VLS_ctx.route.path !== '/about'
    }) })));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign({ to: "/about" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
            'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/about',
            'hover:shadow': __VLS_ctx.route.path !== '/about'
        }) })], __VLS_functionalComponentArgsRest(__VLS_15), false));
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow']} */ ;
var __VLS_19 = __VLS_17.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-info-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-info-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[route, route,];
var __VLS_17;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign(__assign({ to: "/pricing" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
        'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/pricing',
        'hover:shadow': __VLS_ctx.route.path !== '/pricing'
    }) })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign(__assign({ to: "/pricing" }, { class: "px-4 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" }), { class: ({
            'text-blue-600 bg-blue-50 shadow-sm': __VLS_ctx.route.path === '/pricing',
            'hover:shadow': __VLS_ctx.route.path !== '/pricing'
        }) })], __VLS_functionalComponentArgsRest(__VLS_21), false));
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow']} */ ;
var __VLS_25 = __VLS_23.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-tags mr-2" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-tags']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[route, route,];
var __VLS_23;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "hidden md:flex items-center space-x-4" }));
/** @type {__VLS_StyleScopedClasses['hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
var __VLS_26;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ to: "/login" }, { class: "px-6 py-2.5 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5" })));
var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ to: "/login" }, { class: "px-6 py-2.5 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2.5']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['to-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:from-blue-700']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:to-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-md']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transform']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:-translate-y-0.5']} */ ;
var __VLS_31 = __VLS_29.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-sign-in-alt mr-2" }));
/** @type {__VLS_StyleScopedClasses['fas']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-sign-in-alt']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
// @ts-ignore
[];
var __VLS_29;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)(__assign(__assign({ onClick: (__VLS_ctx.toggleMobileMenu) }, { class: "md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200" }), { class: ({ 'bg-gray-100': __VLS_ctx.isMobileMenuOpen }) }));
/** @type {__VLS_StyleScopedClasses['md:hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col space-y-1.5" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-1.5']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-6 h-0.5 bg-gray-700 transition-transform duration-300" }, { class: ({ 'rotate-45 translate-y-2': __VLS_ctx.isMobileMenuOpen }) }));
/** @type {__VLS_StyleScopedClasses['w-6']} */ ;
/** @type {__VLS_StyleScopedClasses['h-0.5']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-transform']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-300']} */ ;
/** @type {__VLS_StyleScopedClasses['rotate-45']} */ ;
/** @type {__VLS_StyleScopedClasses['translate-y-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-6 h-0.5 bg-gray-700 transition-opacity duration-300" }, { class: ({ 'opacity-0': __VLS_ctx.isMobileMenuOpen }) }));
/** @type {__VLS_StyleScopedClasses['w-6']} */ ;
/** @type {__VLS_StyleScopedClasses['h-0.5']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-opacity']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-300']} */ ;
/** @type {__VLS_StyleScopedClasses['opacity-0']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "w-6 h-0.5 bg-gray-700 transition-transform duration-300" }, { class: ({ '-rotate-45 -translate-y-2': __VLS_ctx.isMobileMenuOpen }) }));
/** @type {__VLS_StyleScopedClasses['w-6']} */ ;
/** @type {__VLS_StyleScopedClasses['h-0.5']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-transform']} */ ;
/** @type {__VLS_StyleScopedClasses['duration-300']} */ ;
/** @type {__VLS_StyleScopedClasses['-rotate-45']} */ ;
/** @type {__VLS_StyleScopedClasses['-translate-y-2']} */ ;
if (__VLS_ctx.isMobileMenuOpen) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "md:hidden pt-4 animate-fadeIn" }));
    /** @type {__VLS_StyleScopedClasses['md:hidden']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['animate-fadeIn']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-1" }));
    /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
    routerLink;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign(__assign({ 'onClick': {} }, { to: "/" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/' }) })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { to: "/" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/' }) })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ click: {} },
        { onClick: (__VLS_ctx.closeMobileMenu) });
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    /** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    var __VLS_39 = __VLS_35.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-home w-6 mr-3" }));
    /** @type {__VLS_StyleScopedClasses['fas']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-home']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    // @ts-ignore
    [closeMobileMenu, route, toggleMobileMenu, isMobileMenuOpen, isMobileMenuOpen, isMobileMenuOpen, isMobileMenuOpen, isMobileMenuOpen,];
    var __VLS_35;
    var __VLS_36;
    var __VLS_40 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
    routerLink;
    // @ts-ignore
    var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign(__assign(__assign({ 'onClick': {} }, { to: "/about" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/about' }) })));
    var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { to: "/about" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/about' }) })], __VLS_functionalComponentArgsRest(__VLS_41), false));
    var __VLS_45 = void 0;
    var __VLS_46 = ({ click: {} },
        { onClick: (__VLS_ctx.closeMobileMenu) });
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    /** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    var __VLS_47 = __VLS_43.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-info-circle w-6 mr-3" }));
    /** @type {__VLS_StyleScopedClasses['fas']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-info-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    // @ts-ignore
    [closeMobileMenu, route,];
    var __VLS_43;
    var __VLS_44;
    var __VLS_48 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
    routerLink;
    // @ts-ignore
    var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48(__assign(__assign(__assign({ 'onClick': {} }, { to: "/pricing" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/pricing' }) })));
    var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { to: "/pricing" }), { class: "flex items-center px-4 py-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" }), { class: ({ 'text-blue-600 bg-blue-50': __VLS_ctx.route.path === '/pricing' }) })], __VLS_functionalComponentArgsRest(__VLS_49), false));
    var __VLS_53 = void 0;
    var __VLS_54 = ({ click: {} },
        { onClick: (__VLS_ctx.closeMobileMenu) });
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    /** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    var __VLS_55 = __VLS_51.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-tags w-6 mr-3" }));
    /** @type {__VLS_StyleScopedClasses['fas']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-tags']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
    // @ts-ignore
    [closeMobileMenu, route,];
    var __VLS_51;
    var __VLS_52;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "pt-4 mt-4 border-t border-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-100']} */ ;
    var __VLS_56 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
    routerLink;
    // @ts-ignore
    var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign(__assign({ 'onClick': {} }, { to: "/login" }), { class: "flex items-center justify-center px-6 py-3 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md" })));
    var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { to: "/login" }), { class: "flex items-center justify-center px-6 py-3 bg-linear-to-r from-blue-600 to-blue-500 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md" })], __VLS_functionalComponentArgsRest(__VLS_57), false));
    var __VLS_61 = void 0;
    var __VLS_62 = ({ click: {} },
        { onClick: (__VLS_ctx.closeMobileMenu) });
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['px-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:from-blue-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:to-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    /** @type {__VLS_StyleScopedClasses['duration-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow-md']} */ ;
    var __VLS_63 = __VLS_59.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "fas fa-sign-in-alt mr-2" }));
    /** @type {__VLS_StyleScopedClasses['fas']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-sign-in-alt']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    // @ts-ignore
    [closeMobileMenu,];
    var __VLS_59;
    var __VLS_60;
}
var __VLS_64;
/** @ts-ignore @type {typeof __VLS_components.routerView | typeof __VLS_components.RouterView | typeof __VLS_components.routerView | typeof __VLS_components.RouterView} */
routerView;
// @ts-ignore
var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64({}));
var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_65), false));
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
