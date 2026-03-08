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
var axios_1 = require("../../../axios");
var loading = (0, vue_1.ref)(false);
var notifications = (0, vue_1.ref)([]);
var unreadCount = (0, vue_1.ref)(0);
var totalRecords = (0, vue_1.ref)(0);
var currentPage = (0, vue_1.ref)(1);
var pageSize = 10;
var filters = (0, vue_1.reactive)({
    status: 'all',
    type: 'all',
    sort: 'newest'
});
var statusFilters = [
    { label: 'All Notifications', value: 'all' },
    { label: 'Unread Only', value: 'unread' },
    { label: 'Read Only', value: 'read' }
];
var typeFilters = [
    { label: 'All Types', value: 'all' },
    { label: 'Alerts', value: 'alert' },
    { label: 'Transfers', value: 'transfer' },
    { label: 'Approvals', value: 'approval' },
    { label: 'System', value: 'system' }
];
var sortOptions = [
    { label: 'Newest First', value: 'newest' },
    { label: 'Oldest First', value: 'oldest' },
    { label: 'Unread First', value: 'unread_first' }
];
var getNotificationBgClass = function (type) {
    var classes = {
        alert: 'bg-red-100 text-red-600',
        warning: 'bg-yellow-100 text-yellow-600',
        transfer: 'bg-blue-100 text-blue-600',
        approval: 'bg-purple-100 text-purple-600',
        system: 'bg-green-100 text-green-600',
    };
    return classes[type] || 'bg-gray-100 text-gray-600';
};
var getNotificationIcon = function (type) {
    var icons = {
        alert: 'pi-exclamation-circle',
        warning: 'pi-alert',
        transfer: 'pi-arrow-right-arrow-left',
        approval: 'pi-check-circle',
        system: 'pi-info-circle',
    };
    return icons[type] || 'pi-bell';
};
var formatTime = function (datetime) {
    var date = new Date(datetime);
    var now = new Date();
    var diff = now.getTime() - date.getTime();
    if (diff < 60000)
        return 'just now';
    if (diff < 3600000)
        return "".concat(Math.floor(diff / 60000), "m ago");
    if (diff < 86400000)
        return "".concat(Math.floor(diff / 3600000), "h ago");
    if (diff < 604800000)
        return "".concat(Math.floor(diff / 86400000), "d ago");
    return date.toLocaleDateString();
};
var fetchNotifications = function () {
    var args_1 = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        args_1[_i] = arguments[_i];
    }
    return __awaiter(void 0, __spreadArray([], args_1, true), void 0, function (page) {
        var response, error_1;
        var _a, _b;
        if (page === void 0) { page = 1; }
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    loading.value = true;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 3, 4, 5]);
                    return [4 /*yield*/, axios_1.default.get('/api/inventory/notifications', {
                            params: {
                                page: page,
                                limit: pageSize,
                                status: filters.status !== 'all' ? filters.status : undefined,
                                type: filters.type !== 'all' ? filters.type : undefined,
                                sort: filters.sort
                            }
                        })];
                case 2:
                    response = _c.sent();
                    notifications.value = response.data.data;
                    totalRecords.value = ((_a = response.data.meta) === null || _a === void 0 ? void 0 : _a.total) || 0;
                    unreadCount.value = ((_b = response.data.meta) === null || _b === void 0 ? void 0 : _b.unread_count) || 0;
                    currentPage.value = page;
                    return [3 /*break*/, 5];
                case 3:
                    error_1 = _c.sent();
                    console.error('Failed to fetch notifications:', error_1);
                    return [3 /*break*/, 5];
                case 4:
                    loading.value = false;
                    return [7 /*endfinally*/];
                case 5: return [2 /*return*/];
            }
        });
    });
};
var markAsRead = function (notification) { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.put("/api/inventory/notifications/".concat(notification.id, "/read"))];
            case 1:
                _a.sent();
                notification.read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to mark as read:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var markAllAsRead = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.put('/api/inventory/notifications/mark-all-read')];
            case 1:
                _a.sent();
                notifications.value = notifications.value.map(function (n) { return (__assign(__assign({}, n), { read_at: new Date().toISOString() })); });
                unreadCount.value = 0;
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Failed to mark all as read:', error_3);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var deleteNotification = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, axios_1.default.delete("/api/inventory/notifications/".concat(id))];
            case 1:
                _a.sent();
                notifications.value = notifications.value.filter(function (n) { return n.id !== id; });
                totalRecords.value = Math.max(0, totalRecords.value - 1);
                return [3 /*break*/, 3];
            case 2:
                error_4 = _a.sent();
                console.error('Failed to delete notification:', error_4);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var deleteAll = function () { return __awaiter(void 0, void 0, void 0, function () {
    var confirmed, ids, error_5;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                confirmed = confirm('Are you sure you want to delete all notifications?');
                if (!confirmed)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                ids = notifications.value.map(function (n) { return n.id; });
                return [4 /*yield*/, axios_1.default.post('/api/inventory/notifications/batch-delete', { ids: ids })];
            case 2:
                _a.sent();
                notifications.value = [];
                totalRecords.value = 0;
                unreadCount.value = 0;
                return [3 /*break*/, 4];
            case 3:
                error_5 = _a.sent();
                console.error('Failed to delete all:', error_5);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var handleNotificationClick = function (notification) {
    if (!notification.read_at) {
        markAsRead(notification);
    }
};
var navigateTo = function (notification) {
    if (notification.action_url) {
        window.location.href = notification.action_url;
    }
};
var previousPage = function () {
    if (currentPage.value > 1) {
        fetchNotifications(currentPage.value - 1);
    }
};
var nextPage = function () {
    var maxPage = Math.ceil(totalRecords.value / pageSize);
    if (currentPage.value < maxPage) {
        fetchNotifications(currentPage.value + 1);
    }
};
(0, vue_1.onMounted)(function () {
    fetchNotifications();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
if (__VLS_ctx.unreadCount > 0) {
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Mark All as Read", icon: "pi pi-check-circle", severity: "secondary" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Mark All as Read", icon: "pi pi-check-circle", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ click: {} },
        { onClick: (__VLS_ctx.markAllAsRead) });
    var __VLS_3;
    var __VLS_4;
}
if (__VLS_ctx.notifications.length > 0) {
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Delete All", icon: "pi pi-trash", severity: "danger", text: true })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete All", icon: "pi pi-trash", severity: "danger", text: true })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteAll) });
    var __VLS_10;
    var __VLS_11;
}
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
{
    var __VLS_20 = __VLS_17.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusFilters), optionLabel: "label", optionValue: "value", placeholder: "All notifications" }), { class: "w-full" })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusFilters), optionLabel: "label", optionValue: "value", placeholder: "All notifications" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = void 0;
    var __VLS_27 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchNotifications) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_24;
    var __VLS_25;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeFilters), optionLabel: "label", optionValue: "value", placeholder: "All types" }), { class: "w-full" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.type), options: (__VLS_ctx.typeFilters), optionLabel: "label", optionValue: "value", placeholder: "All types" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchNotifications) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_31;
    var __VLS_32;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
    Dropdown;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.sort), options: (__VLS_ctx.sortOptions), optionLabel: "label", optionValue: "value" }), { class: "w-full" })));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.sort), options: (__VLS_ctx.sortOptions), optionLabel: "label", optionValue: "value" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = void 0;
    var __VLS_41 = ({ change: {} },
        { onChange: (__VLS_ctx.fetchNotifications) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_38;
    var __VLS_39;
    // @ts-ignore
    [unreadCount, markAllAsRead, notifications, deleteAll, filters, filters, filters, statusFilters, fetchNotifications, fetchNotifications, fetchNotifications, typeFilters, sortOptions,];
}
// @ts-ignore
[];
var __VLS_17;
if (__VLS_ctx.unreadCount > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-bell text-2xl text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-bell']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.unreadCount);
    (__VLS_ctx.unreadCount !== 1 ? 's' : '');
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign(__assign({ 'onClick': {} }, { label: "Mark All Read" }), { class: "text-sm" })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Mark All Read" }), { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    var __VLS_47 = void 0;
    var __VLS_48 = ({ click: {} },
        { onClick: (__VLS_ctx.markAllAsRead) });
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    var __VLS_45;
    var __VLS_46;
}
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _a = __VLS_vFor((5)); _i < _a.length; _i++) {
        var i = _a[_i][0];
        var __VLS_49 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })));
        var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [unreadCount, unreadCount, unreadCount, markAllAsRead, loading,];
    }
}
else if (__VLS_ctx.notifications.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-16" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-16']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-6xl text-gray-300 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    var _loop_1 = function (notification) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                if (!!(__VLS_ctx.notifications.length === 0))
                    return;
                __VLS_ctx.handleNotificationClick(notification);
                // @ts-ignore
                [notifications, notifications, handleNotificationClick,];
            } }, { key: (notification.id) }), { class: ([
                'p-4 rounded-lg border-2 transition-colors cursor-pointer hover:shadow-md',
                notification.read_at
                    ? 'border-gray-200 bg-white'
                    : 'border-blue-300 bg-blue-50'
            ]) }));
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:shadow-md']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start gap-4" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: (['flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg', __VLS_ctx.getNotificationBgClass(notification.type)]) }));
        /** @type {__VLS_StyleScopedClasses['flex-shrink-0']} */ ;
        /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['pi', __VLS_ctx.getNotificationIcon(notification.type)]) }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 min-w-0" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: (['font-semibold text-gray-900', !notification.read_at && 'font-bold']) }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (notification.title);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1 text-sm line-clamp-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['line-clamp-2']} */ ;
        (notification.message);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-shrink-0" }));
        /** @type {__VLS_StyleScopedClasses['flex-shrink-0']} */ ;
        if (!notification.read_at) {
            var __VLS_54 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            Badge;
            // @ts-ignore
            var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54({
                value: "NEW",
                severity: "danger",
            }));
            var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([{
                    value: "NEW",
                    severity: "danger",
                }], __VLS_functionalComponentArgsRest(__VLS_55), false));
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between mt-3 gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.formatTime(notification.created_at));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        if (!notification.read_at) {
            var __VLS_59 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", text: true, rounded: true, size: "small" })));
            var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check-circle", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_60), false));
            var __VLS_64 = void 0;
            var __VLS_65 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.notifications.length === 0))
                            return;
                        if (!(!notification.read_at))
                            return;
                        __VLS_ctx.markAsRead(notification);
                        // @ts-ignore
                        [getNotificationBgClass, getNotificationIcon, formatTime, markAsRead,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Mark as read') }), null, null);
        }
        if (notification.action_url) {
            var __VLS_66 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-right", text: true, rounded: true, size: "small" })));
            var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-right", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_67), false));
            var __VLS_71 = void 0;
            var __VLS_72 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.notifications.length === 0))
                            return;
                        if (!(notification.action_url))
                            return;
                        __VLS_ctx.navigateTo(notification);
                        // @ts-ignore
                        [vTooltip, navigateTo,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View') }), null, null);
        }
        var __VLS_73 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, size: "small", severity: "danger" })));
        var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_74), false));
        var __VLS_78 = void 0;
        var __VLS_79 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.notifications.length === 0))
                        return;
                    __VLS_ctx.deleteNotification(notification.id);
                    // @ts-ignore
                    [vTooltip, deleteNotification,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Delete') }), null, null);
        // @ts-ignore
        [vTooltip,];
    };
    var __VLS_62, __VLS_63, __VLS_69, __VLS_70, __VLS_76, __VLS_77;
    for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.notifications)); _b < _c.length; _b++) {
        var notification = _c[_b][0];
        _loop_1(notification);
    }
    if (__VLS_ctx.totalRecords > __VLS_ctx.pageSize) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-center gap-2 mt-6" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
        var __VLS_80 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", disabled: (__VLS_ctx.currentPage === 1), text: true, rounded: true })));
        var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-left", disabled: (__VLS_ctx.currentPage === 1), text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_81), false));
        var __VLS_85 = void 0;
        var __VLS_86 = ({ click: {} },
            { onClick: (__VLS_ctx.previousPage) });
        var __VLS_83;
        var __VLS_84;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (__VLS_ctx.currentPage);
        (Math.ceil(__VLS_ctx.totalRecords / __VLS_ctx.pageSize));
        var __VLS_87 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87(__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", disabled: (__VLS_ctx.currentPage >= Math.ceil(__VLS_ctx.totalRecords / __VLS_ctx.pageSize)), text: true, rounded: true })));
        var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chevron-right", disabled: (__VLS_ctx.currentPage >= Math.ceil(__VLS_ctx.totalRecords / __VLS_ctx.pageSize)), text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_88), false));
        var __VLS_92 = void 0;
        var __VLS_93 = ({ click: {} },
            { onClick: (__VLS_ctx.nextPage) });
        var __VLS_90;
        var __VLS_91;
    }
}
// @ts-ignore
[totalRecords, totalRecords, totalRecords, pageSize, pageSize, pageSize, currentPage, currentPage, currentPage, previousPage, nextPage,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
