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
var procurement_service_1 = require("../../../../services/procurement.service");
var loading = (0, vue_1.ref)(false);
var payments = (0, vue_1.ref)([]);
var loadPayments = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                loading.value = true;
                _b.label = 1;
            case 1:
                _b.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getSupplierPayments()];
            case 2:
                response = _b.sent();
                payments.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || [];
                return [3 /*break*/, 5];
            case 3:
                error_1 = _b.sent();
                console.error('Failed to load supplier payments', error_1);
                payments.value = [];
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var approve = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 3, , 4]);
                return [4 /*yield*/, procurement_service_1.default.approveSupplierPayment(id)];
            case 1:
                _a.sent();
                return [4 /*yield*/, loadPayments()];
            case 2:
                _a.sent();
                return [3 /*break*/, 4];
            case 3:
                error_2 = _a.sent();
                console.error('Failed to approve payment', error_2);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var reject = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 3, , 4]);
                return [4 /*yield*/, procurement_service_1.default.rejectSupplierPayment(id)];
            case 1:
                _a.sent();
                return [4 /*yield*/, loadPayments()];
            case 2:
                _a.sent();
                return [3 /*break*/, 4];
            case 3:
                error_3 = _a.sent();
                console.error('Failed to reject payment', error_3);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var processPayment = function (id) { return __awaiter(void 0, void 0, void 0, function () {
    var error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 3, , 4]);
                return [4 /*yield*/, procurement_service_1.default.processSupplierPayment(id)];
            case 1:
                _a.sent();
                return [4 /*yield*/, loadPayments()];
            case 2:
                _a.sent();
                return [3 /*break*/, 4];
            case 3:
                error_4 = _a.sent();
                console.error('Failed to process payment', error_4);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var statusSeverity = function (status) {
    if (['approved', 'processing', 'completed'].includes(status))
        return 'success';
    if (status === 'pending_approval')
        return 'info';
    if (['failed', 'cancelled'].includes(status))
        return 'danger';
    return 'secondary';
};
(0, vue_1.onMounted)(function () {
    loadPayments();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 bg-gray-50 min-h-screen" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5 = __VLS_3.slots.default;
{
    var __VLS_6 = __VLS_3.slots.content;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign({ value: (__VLS_ctx.payments), loading: (__VLS_ctx.loading) }, { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.payments), loading: (__VLS_ctx.loading) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_12 = __VLS_10.slots.default;
    var __VLS_13 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13({
        field: "payment_number",
        header: "Payment No.",
    }));
    var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([{
            field: "payment_number",
            header: "Payment No.",
        }], __VLS_functionalComponentArgsRest(__VLS_14), false));
    var __VLS_18 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18({
        field: "payment_date",
        header: "Payment Date",
    }));
    var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([{
            field: "payment_date",
            header: "Payment Date",
        }], __VLS_functionalComponentArgsRest(__VLS_19), false));
    var __VLS_23 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23({
        field: "payment_amount",
        header: "Amount",
    }));
    var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([{
            field: "payment_amount",
            header: "Amount",
        }], __VLS_functionalComponentArgsRest(__VLS_24), false));
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({
        field: "payment_method",
        header: "Method",
    }));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{
            field: "payment_method",
            header: "Method",
        }], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33({
        field: "status",
        header: "Status",
    }));
    var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([{
            field: "status",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_34), false));
    var __VLS_38 = __VLS_36.slots.default;
    {
        var __VLS_39 = __VLS_36.slots.body;
        var data = __VLS_vSlot(__VLS_39)[0].data;
        var __VLS_40 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40({
            value: (data.status),
            severity: (__VLS_ctx.statusSeverity(data.status)),
        }));
        var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([{
                value: (data.status),
                severity: (__VLS_ctx.statusSeverity(data.status)),
            }], __VLS_functionalComponentArgsRest(__VLS_41), false));
        // @ts-ignore
        [payments, loading, statusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_36;
    var __VLS_45 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45({
        header: "Actions",
    }));
    var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([{
            header: "Actions",
        }], __VLS_functionalComponentArgsRest(__VLS_46), false));
    var __VLS_50 = __VLS_48.slots.default;
    {
        var __VLS_51 = __VLS_48.slots.body;
        var data_1 = __VLS_vSlot(__VLS_51)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_52 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign({ 'onClick': {} }, { icon: "pi pi-check", text: true, rounded: true, severity: "success" })));
        var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", text: true, rounded: true, severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_53), false));
        var __VLS_57 = void 0;
        var __VLS_58 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.approve(data_1.id);
                    // @ts-ignore
                    [approve,];
                } });
        var __VLS_55;
        var __VLS_56;
        var __VLS_59 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ 'onClick': {} }, { icon: "pi pi-cog", text: true, rounded: true, severity: "info" })));
        var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-cog", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_60), false));
        var __VLS_64 = void 0;
        var __VLS_65 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.processPayment(data_1.id);
                    // @ts-ignore
                    [processPayment,];
                } });
        var __VLS_62;
        var __VLS_63;
        var __VLS_66 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, severity: "danger" })));
        var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_67), false));
        var __VLS_71 = void 0;
        var __VLS_72 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.reject(data_1.id);
                    // @ts-ignore
                    [reject,];
                } });
        var __VLS_69;
        var __VLS_70;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_48;
    // @ts-ignore
    [];
    var __VLS_10;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_3;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
