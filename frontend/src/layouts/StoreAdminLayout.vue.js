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
var auth_1 = require("../stores/auth");
var lodash_1 = require("lodash");
var UserDialog_vue_1 = require("../components/dialogs/UserDialog.vue");
var button_1 = require("primevue/button");
var badge_1 = require("primevue/badge");
var skeleton_1 = require("primevue/skeleton");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var authStore = (0, auth_1.useAuthStore)();
var userDialogRef = (0, vue_1.ref)(null);
var loadingNavigation = (0, vue_1.ref)(true);
var sidebarOpen = (0, vue_1.ref)(false);
// User data
var fullName = (0, vue_1.computed)(function () {
    var _a, _b, _c, _d;
    var first = (0, lodash_1.startCase)((0, lodash_1.toLower)(((_a = authStore.user) === null || _a === void 0 ? void 0 : _a.first_name) || ((_b = authStore.user) === null || _b === void 0 ? void 0 : _b.fname) || ''));
    var last = (0, lodash_1.startCase)((0, lodash_1.toLower)(((_c = authStore.user) === null || _c === void 0 ? void 0 : _c.last_name) || ((_d = authStore.user) === null || _d === void 0 ? void 0 : _d.lname) || ''));
    return "".concat(first, " ").concat(last).trim() || 'User';
});
var userInitials = (0, vue_1.computed)(function () {
    var _a, _b, _c, _d, _e, _f, _g, _h;
    var first = ((_b = (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.first_name) === null || _b === void 0 ? void 0 : _b[0]) || ((_d = (_c = authStore.user) === null || _c === void 0 ? void 0 : _c.fname) === null || _d === void 0 ? void 0 : _d[0]) || '';
    var last = ((_f = (_e = authStore.user) === null || _e === void 0 ? void 0 : _e.last_name) === null || _f === void 0 ? void 0 : _f[0]) || ((_h = (_g = authStore.user) === null || _g === void 0 ? void 0 : _g.lname) === null || _h === void 0 ? void 0 : _h[0]) || '';
    return (first + last).toUpperCase() || 'U';
});
var roleDisplay = (0, vue_1.computed)(function () { var _a; return (0, lodash_1.startCase)(((_a = authStore.user) === null || _a === void 0 ? void 0 : _a.role) || 'Store Admin'); });
// Notification count (you can implement this based on your needs)
var notificationCount = (0, vue_1.computed)(function () {
    // This could come from a notifications store or API
    return 3;
});
// Breadcrumbs
var breadcrumbs = (0, vue_1.computed)(function () {
    var crumbs = [];
    var title = route.meta.title;
    if (title && title !== 'Store Dashboard') {
        crumbs.push({
            name: title,
            path: null
        });
    }
    return crumbs;
});
// ✅ Get all store admin navigation items from API
var storeNavigation = (0, vue_1.computed)(function () {
    return authStore.navigation
        .filter(function (item) {
        return item.module === 'store_admin' &&
            item.is_active;
    })
        .sort(function (a, b) { return a.display_order - b.display_order; });
});
// Group navigation items by section
var groupedNavigation = (0, vue_1.computed)(function () {
    var sections = new Map();
    // Separate parent items and their children
    var parentItems = storeNavigation.value.filter(function (item) { return !item.parent_id; });
    var childItems = storeNavigation.value.filter(function (item) { return item.parent_id; });
    // Group children by parent
    var childrenByParent = childItems.reduce(function (acc, item) {
        if (item.parent_id) {
            if (!acc[item.parent_id]) {
                acc[item.parent_id] = [];
            }
            acc[item.parent_id].push(item);
        }
        return acc;
    }, {});
    // Attach children to parent items
    parentItems.forEach(function (parent) {
        if (childrenByParent[parent.id]) {
            parent.children = childrenByParent[parent.id].sort(function (a, b) { return a.display_order - b.display_order; });
        }
    });
    // Group by section
    parentItems.forEach(function (item) {
        var _a;
        var section = item.section || 'General';
        if (!sections.has(section)) {
            sections.set(section, []);
        }
        (_a = sections.get(section)) === null || _a === void 0 ? void 0 : _a.push(item);
    });
    // Convert to array and sort sections
    return Array.from(sections.entries())
        .map(function (_a) {
        var name = _a[0], items = _a[1];
        return ({
            name: name === 'General' ? null : name,
            items: items.sort(function (a, b) { return a.display_order - b.display_order; })
        });
    })
        .sort(function (a, b) {
        // Put null section (General) first
        if (a.name === null)
            return -1;
        if (b.name === null)
            return 1;
        return (a.name || '').localeCompare(b.name || '');
    });
});
// Load navigation on mount
var loadNavigation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var hasStore, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loadingNavigation.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 4, 5, 6]);
                if (!(authStore.navigation.length === 0)) return [3 /*break*/, 3];
                return [4 /*yield*/, authStore.fetchNavigation()];
            case 2:
                _c.sent();
                _c.label = 3;
            case 3:
                hasStore = !!((_b = (_a = authStore.user) === null || _a === void 0 ? void 0 : _a.store) === null || _b === void 0 ? void 0 : _b.id);
                if (!hasStore && route.path !== '/system/store/verification') {
                    router.push('/system/store/verification');
                }
                return [3 /*break*/, 6];
            case 4:
                error_1 = _c.sent();
                console.error('Failed to load navigation:', error_1);
                return [3 /*break*/, 6];
            case 5:
                loadingNavigation.value = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
// Toggle sidebar for mobile
var toggleSidebar = function () {
    sidebarOpen.value = !sidebarOpen.value;
};
// Close sidebar when route changes on mobile
(0, vue_1.watch)(function () { return route.path; }, function () {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
});
// Lifecycle
(0, vue_1.onMounted)(function () {
    loadNavigation();
});
// User dialog
var openUserDialog = function (event) {
    if (userDialogRef.value) {
        userDialogRef.value.toggle(event);
    }
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
/** @type {__VLS_StyleScopedClasses['router-link-active']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
/** @type {__VLS_StyleScopedClasses['has-children']} */ ;
/** @type {__VLS_StyleScopedClasses['open']} */ ;
/** @type {__VLS_StyleScopedClasses['nav-link']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex h-screen w-full max-w-[100vw] overflow-hidden bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-[100vw]']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.aside, __VLS_intrinsics.aside)(__assign({ class: "sidebar bg-blue-50 w-64 flex flex-col z-30 overflow-y-auto" }, { class: ({ 'open': __VLS_ctx.sidebarOpen }) }));
/** @type {__VLS_StyleScopedClasses['sidebar']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['w-64']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['z-30']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['open']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-5 py-4 border-b border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['px-5']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center w-10 h-10 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
/** @type {__VLS_StyleScopedClasses['w-10']} */ ;
/** @type {__VLS_StyleScopedClasses['h-10']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: "../../public/F.svg", alt: "Furnisync" }, { class: "w-20 h-20" }));
/** @type {__VLS_StyleScopedClasses['w-20']} */ ;
/** @type {__VLS_StyleScopedClasses['h-20']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "leading-tight" }, { style: {} }));
/** @type {__VLS_StyleScopedClasses['leading-tight']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-lg font-semibold text-gray-900" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "flex-1 overflow-y-auto py-4" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
if (__VLS_ctx.loadingNavigation) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_5 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ height: "40px" }, { class: "rounded-lg" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ height: "40px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    if (__VLS_ctx.storeNavigation.length > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 space-y-1 pb-4" }));
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-y-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['pb-4']} */ ;
        for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.groupedNavigation)); _i < _a.length; _i++) {
            var section = _a[_i][0];
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                key: (section.name),
            });
            if (section.name) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3 mt-4 first:mt-0" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                /** @type {__VLS_StyleScopedClasses['uppercase']} */ ;
                /** @type {__VLS_StyleScopedClasses['tracking-wider']} */ ;
                /** @type {__VLS_StyleScopedClasses['pl-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
                /** @type {__VLS_StyleScopedClasses['first:mt-0']} */ ;
                (section.name);
            }
            for (var _b = 0, _c = __VLS_vFor((section.items)); _b < _c.length; _b++) {
                var item = _c[_b][0];
                var __VLS_15 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
                routerLink;
                // @ts-ignore
                var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15(__assign({ key: (item.id), to: (item.route_path) }, { class: "nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors" })));
                var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([__assign({ key: (item.id), to: (item.route_path) }, { class: "nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors" })], __VLS_functionalComponentArgsRest(__VLS_16), false));
                /** @type {__VLS_StyleScopedClasses['nav-item']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['py-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:bg-blue-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
                var __VLS_20 = __VLS_18.slots.default;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ([item.icon || 'pi pi-circle', 'w-5']) }));
                /** @type {__VLS_StyleScopedClasses['w-5']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "flex-1" }));
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                (item.display_name);
                if (item.badge_count && item.badge_count > 0) {
                    var __VLS_21 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Badge} */
                    badge_1.default;
                    // @ts-ignore
                    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({
                        value: (item.badge_count),
                        severity: "danger",
                        size: "small",
                    }));
                    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{
                            value: (item.badge_count),
                            severity: "danger",
                            size: "small",
                        }], __VLS_functionalComponentArgsRest(__VLS_22), false));
                }
                // @ts-ignore
                [sidebarOpen, loadingNavigation, storeNavigation, groupedNavigation,];
                var __VLS_18;
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
        }
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 py-8 text-center" }));
        /** @type {__VLS_StyleScopedClasses['px-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-300 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-400 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-4 py-3 border-t border-gray-200" }));
/** @type {__VLS_StyleScopedClasses['px-4']} */ ;
/** @type {__VLS_StyleScopedClasses['py-3']} */ ;
/** @type {__VLS_StyleScopedClasses['border-t']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
var __VLS_26;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ to: "/system/index" }, { class: "flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors py-2 px-3 rounded-lg hover:bg-gray-50" })));
var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ to: "/system/index" }, { class: "flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors py-2 px-3 rounded-lg hover:bg-gray-50" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
/** @type {__VLS_StyleScopedClasses['px-3']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
var __VLS_31 = __VLS_29.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-arrow-left" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-arrow-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
// @ts-ignore
[];
var __VLS_29;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 flex flex-col h-screen overflow-hidden" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-hidden']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)(__assign({ class: "bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['py-4']} */ ;
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['sticky']} */ ;
/** @type {__VLS_StyleScopedClasses['top-0']} */ ;
/** @type {__VLS_StyleScopedClasses['z-20']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-sm']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-bars", text: true, rounded: true }), { class: "lg:hidden" })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-bars", text: true, rounded: true }), { class: "lg:hidden" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ click: {} },
    { onClick: (__VLS_ctx.toggleSidebar) });
/** @type {__VLS_StyleScopedClasses['lg:hidden']} */ ;
var __VLS_35;
var __VLS_36;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-xl font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
(__VLS_ctx.route.meta.title || 'Store Dashboard');
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.route.meta.subtitle || 'Manage your store operations');
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
var __VLS_39;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39({
    icon: "pi pi-bell",
    severity: "secondary",
    text: true,
    rounded: true,
    badge: (__VLS_ctx.notificationCount > 0 ? __VLS_ctx.notificationCount.toString() : undefined),
    badgeSeverity: "danger",
}));
var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([{
        icon: "pi pi-bell",
        severity: "secondary",
        text: true,
        rounded: true,
        badge: (__VLS_ctx.notificationCount > 0 ? __VLS_ctx.notificationCount.toString() : undefined),
        badgeSeverity: "danger",
    }], __VLS_functionalComponentArgsRest(__VLS_40), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: (__VLS_ctx.openUserDialog) }, { class: "border-l border-gray-200 pl-4 cursor-pointer select-none" }));
/** @type {__VLS_StyleScopedClasses['border-l']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['pl-4']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
/** @type {__VLS_StyleScopedClasses['select-none']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['px-2']} */ ;
/** @type {__VLS_StyleScopedClasses['py-1']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['transition']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-8']} */ ;
/** @type {__VLS_StyleScopedClasses['h-8']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-semibold text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
(__VLS_ctx.userInitials);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "font-semibold text-gray-800 text-sm" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
(__VLS_ctx.fullName);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.roleDisplay);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white border-b border-gray-200 px-6 py-2" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['border-b']} */ ;
/** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
/** @type {__VLS_StyleScopedClasses['px-6']} */ ;
/** @type {__VLS_StyleScopedClasses['py-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.nav, __VLS_intrinsics.nav)(__assign({ class: "flex items-center space-x-2 text-sm" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_44;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44(__assign({ to: "/system/index" }, { class: "text-gray-500 hover:text-blue-600" })));
var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([__assign({ to: "/system/index" }, { class: "text-gray-500 hover:text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_45), false));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
var __VLS_49 = __VLS_47.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-home" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-home']} */ ;
// @ts-ignore
[toggleSidebar, route, route, notificationCount, notificationCount, openUserDialog, userInitials, fullName, roleDisplay,];
var __VLS_47;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-angle-right text-gray-400 text-xs" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-angle-right']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
var __VLS_50;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50(__assign({ to: "/system/dashboard" }, { class: "text-gray-500 hover:text-blue-600" })));
var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([__assign({ to: "/system/dashboard" }, { class: "text-gray-500 hover:text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_51), false));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
var __VLS_55 = __VLS_53.slots.default;
// @ts-ignore
[];
var __VLS_53;
if (__VLS_ctx.breadcrumbs.length > 0) {
    for (var _d = 0, _e = __VLS_vFor((__VLS_ctx.breadcrumbs)); _d < _e.length; _d++) {
        var _f = _e[_d], crumb = _f[0], index = _f[1];
        (index);
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-angle-right text-gray-400 text-xs" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-angle-right']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        if (crumb.path) {
            var __VLS_56 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
            routerLink;
            // @ts-ignore
            var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign({ to: (crumb.path) }, { class: "text-gray-500 hover:text-blue-600" })));
            var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign({ to: (crumb.path) }, { class: "text-gray-500 hover:text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_57), false));
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:text-blue-600']} */ ;
            var __VLS_61 = __VLS_59.slots.default;
            (crumb.name);
            // @ts-ignore
            [breadcrumbs, breadcrumbs,];
            var __VLS_59;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-800 font-medium" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (crumb.name);
        }
        // @ts-ignore
        [];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.main, __VLS_intrinsics.main)(__assign({ class: "flex-1 overflow-y-auto p-6 bg-gray-50" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
/** @type {__VLS_StyleScopedClasses['overflow-y-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.routerView | typeof __VLS_components.RouterView} */
routerView;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62({}));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67 = UserDialog_vue_1.default;
// @ts-ignore
var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67({
    ref: "userDialogRef",
}));
var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([{
        ref: "userDialogRef",
    }], __VLS_functionalComponentArgsRest(__VLS_68), false));
var __VLS_72 = {};
var __VLS_70;
// @ts-ignore
var __VLS_73 = __VLS_72;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
