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
var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var inventory_service_1 = require("../../../../services/inventory.service");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var loading = (0, vue_1.ref)(false);
var processing = (0, vue_1.ref)(false);
var detail = (0, vue_1.ref)(null);
var transferId = (0, vue_1.computed)(function () { return Number(route.params.id); });
var canAction = (0, vue_1.computed)(function () { var _a; return ['approved', 'shipped'].includes((_a = detail.value) === null || _a === void 0 ? void 0 : _a.status); });
var loadDetail = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getTransfer(transferId.value)];
            case 2:
                response = _a.sent();
                detail.value = response.data || null;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load transfer detail', error_1);
                detail.value = null;
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var shipTransfer = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                processing.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 4, 5, 6]);
                return [4 /*yield*/, inventory_service_1.default.shipTransfer(transferId.value)];
            case 2:
                _a.sent();
                return [4 /*yield*/, loadDetail()];
            case 3:
                _a.sent();
                return [3 /*break*/, 6];
            case 4:
                error_2 = _a.sent();
                console.error('Failed to ship transfer', error_2);
                return [3 /*break*/, 6];
            case 5:
                processing.value = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
var receiveTransfer = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                processing.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 4, 5, 6]);
                return [4 /*yield*/, inventory_service_1.default.receiveTransfer(transferId.value)];
            case 2:
                _a.sent();
                return [4 /*yield*/, loadDetail()];
            case 3:
                _a.sent();
                return [3 /*break*/, 6];
            case 4:
                error_3 = _a.sent();
                console.error('Failed to receive transfer', error_3);
                return [3 /*break*/, 6];
            case 5:
                processing.value = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
var cancelTransfer = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                processing.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 4, 5, 6]);
                return [4 /*yield*/, inventory_service_1.default.cancelTransfer(transferId.value)];
            case 2:
                _a.sent();
                return [4 /*yield*/, loadDetail()];
            case 3:
                _a.sent();
                return [3 /*break*/, 6];
            case 4:
                error_4 = _a.sent();
                console.error('Failed to cancel transfer', error_4);
                return [3 /*break*/, 6];
            case 5:
                processing.value = false;
                return [7 /*endfinally*/];
            case 6: return [2 /*return*/];
        }
    });
}); };
var statusSeverity = function (status) {
    if (status === 'received')
        return 'success';
    if (status === 'shipped')
        return 'info';
    if (status === 'cancelled')
        return 'danger';
    if (status === 'approved')
        return 'help';
    return 'secondary';
};
(0, vue_1.onMounted)(function () {
    loadDetail();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'inventory.transfers' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Tag} */
Tag;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
    value: (((_a = __VLS_ctx.detail) === null || _a === void 0 ? void 0 : _a.status) || 'draft'),
    severity: (__VLS_ctx.statusSeverity(((_b = __VLS_ctx.detail) === null || _b === void 0 ? void 0 : _b.status) || 'draft')),
}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{
        value: (((_c = __VLS_ctx.detail) === null || _c === void 0 ? void 0 : _c.status) || 'draft'),
        severity: (__VLS_ctx.statusSeverity(((_d = __VLS_ctx.detail) === null || _d === void 0 ? void 0 : _d.status) || 'draft')),
    }], __VLS_functionalComponentArgsRest(__VLS_8), false));
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    Skeleton;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ height: "180px" }, { class: "rounded-lg" })));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ height: "180px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_17 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    Skeleton;
    // @ts-ignore
    var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ height: "250px" }, { class: "rounded-lg" })));
    var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ height: "250px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    var __VLS_22 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_23 = __VLS_asFunctionalComponent1(__VLS_22, new __VLS_22({}));
    var __VLS_24 = __VLS_23.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_23), false));
    var __VLS_27 = __VLS_25.slots.default;
    {
        var __VLS_28 = __VLS_25.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_e = __VLS_ctx.detail) === null || _e === void 0 ? void 0 : _e.transfer_no) || '-');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_f = __VLS_ctx.detail) === null || _f === void 0 ? void 0 : _f.from_name) || '-');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_g = __VLS_ctx.detail) === null || _g === void 0 ? void 0 : _g.to_name) || '-');
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (((_h = __VLS_ctx.detail) === null || _h === void 0 ? void 0 : _h.transfer_date) || '-');
        // @ts-ignore
        [detail, detail, detail, detail, detail, detail, statusSeverity, loading,];
    }
    // @ts-ignore
    [];
    var __VLS_25;
    var __VLS_29 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29({}));
    var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_30), false));
    var __VLS_34 = __VLS_32.slots.default;
    {
        var __VLS_35 = __VLS_32.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-list text-emerald-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-list']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-emerald-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_36 = __VLS_32.slots.content;
        var __VLS_37 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign(__assign({ value: (((_j = __VLS_ctx.detail) === null || _j === void 0 ? void 0 : _j.items) || []) }, { class: "p-datatable-sm" }), { stripedRows: true })));
        var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign(__assign({ value: (((_k = __VLS_ctx.detail) === null || _k === void 0 ? void 0 : _k.items) || []) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_38), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_42 = __VLS_40.slots.default;
        var __VLS_43 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43({
            field: "item_name",
            header: "Item",
        }));
        var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([{
                field: "item_name",
                header: "Item",
            }], __VLS_functionalComponentArgsRest(__VLS_44), false));
        var __VLS_48 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
            field: "quantity",
            header: "Quantity",
        }));
        var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
                field: "quantity",
                header: "Quantity",
            }], __VLS_functionalComponentArgsRest(__VLS_49), false));
        var __VLS_53 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
            field: "remarks",
            header: "Remarks",
        }));
        var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
                field: "remarks",
                header: "Remarks",
            }], __VLS_functionalComponentArgsRest(__VLS_54), false));
        // @ts-ignore
        [detail,];
        var __VLS_40;
        if (__VLS_ctx.canAction) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "pt-4 flex gap-2 justify-end" }));
            /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
            var __VLS_58 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "danger", outlined: true, loading: (__VLS_ctx.processing) })));
            var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", severity: "danger", outlined: true, loading: (__VLS_ctx.processing) })], __VLS_functionalComponentArgsRest(__VLS_59), false));
            var __VLS_63 = void 0;
            var __VLS_64 = ({ click: {} },
                { onClick: (__VLS_ctx.cancelTransfer) });
            var __VLS_61;
            var __VLS_62;
            var __VLS_65 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ 'onClick': {} }, { label: "Ship", icon: "pi pi-send", severity: "info", loading: (__VLS_ctx.processing) })));
            var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Ship", icon: "pi pi-send", severity: "info", loading: (__VLS_ctx.processing) })], __VLS_functionalComponentArgsRest(__VLS_66), false));
            var __VLS_70 = void 0;
            var __VLS_71 = ({ click: {} },
                { onClick: (__VLS_ctx.shipTransfer) });
            var __VLS_68;
            var __VLS_69;
            var __VLS_72 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign({ 'onClick': {} }, { label: "Receive", icon: "pi pi-check", severity: "success", loading: (__VLS_ctx.processing) })));
            var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Receive", icon: "pi pi-check", severity: "success", loading: (__VLS_ctx.processing) })], __VLS_functionalComponentArgsRest(__VLS_73), false));
            var __VLS_77 = void 0;
            var __VLS_78 = ({ click: {} },
                { onClick: (__VLS_ctx.receiveTransfer) });
            var __VLS_75;
            var __VLS_76;
        }
        // @ts-ignore
        [canAction, processing, processing, processing, cancelTransfer, shipTransfer, receiveTransfer,];
    }
    // @ts-ignore
    [];
    var __VLS_32;
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
