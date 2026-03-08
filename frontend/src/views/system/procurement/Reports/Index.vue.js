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
var rows = (0, vue_1.ref)([]);
var normalize = function (data) {
    if (Array.isArray(data))
        return data;
    if (data && typeof data === 'object') {
        return Object.entries(data).map(function (_a) {
            var label = _a[0], value = _a[1];
            return ({ label: label, value: String(value) });
        });
    }
    return [];
};
var loadSpend = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getSpendAnalysisReport()];
            case 2:
                response = _a.sent();
                rows.value = normalize(response.data);
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load spend report', error_1);
                rows.value = [];
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadSupplierPerformance = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getSupplierPerformanceReport()];
            case 2:
                response = _a.sent();
                rows.value = normalize(response.data);
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                console.error('Failed to load supplier performance report', error_2);
                rows.value = [];
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadCycleTime = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getProcurementCycleTimeReport()];
            case 2:
                response = _a.sent();
                rows.value = normalize(response.data);
                return [3 /*break*/, 5];
            case 3:
                error_3 = _a.sent();
                console.error('Failed to load cycle time report', error_3);
                rows.value = [];
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 bg-gray-50 min-h-screen space-y-6" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign(__assign({ 'onClick': {} }, { label: "Spend Analysis", icon: "pi pi-chart-line" }), { class: "w-full" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Spend Analysis", icon: "pi pi-chart-line" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: (__VLS_ctx.loadSpend) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_10;
    var __VLS_11;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign({ 'onClick': {} }, { label: "Supplier Performance", icon: "pi pi-users", severity: "secondary" }), { class: "w-full" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Supplier Performance", icon: "pi pi-users", severity: "secondary" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ click: {} },
        { onClick: (__VLS_ctx.loadSupplierPerformance) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_17;
    var __VLS_18;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ 'onClick': {} }, { label: "Cycle Time", icon: "pi pi-clock", severity: "info" }), { class: "w-full" })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Cycle Time", icon: "pi pi-clock", severity: "info" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = void 0;
    var __VLS_27 = ({ click: {} },
        { onClick: (__VLS_ctx.loadCycleTime) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_24;
    var __VLS_25;
    // @ts-ignore
    [loadSpend, loadSupplierPerformance, loadCycleTime,];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({}));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_29), false));
var __VLS_33 = __VLS_31.slots.default;
{
    var __VLS_34 = __VLS_31.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [];
}
{
    var __VLS_35 = __VLS_31.slots.content;
    if (__VLS_ctx.loading) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
        /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
        for (var _i = 0, _a = __VLS_vFor((4)); _i < _a.length; _i++) {
            var i = _a[_i][0];
            var __VLS_36 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
            Skeleton;
            // @ts-ignore
            var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36(__assign({ key: (i), height: "52px" }, { class: "rounded-lg" })));
            var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([__assign({ key: (i), height: "52px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_37), false));
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            // @ts-ignore
            [loading,];
        }
    }
    else {
        var __VLS_41 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_42 = __VLS_asFunctionalComponent1(__VLS_41, new __VLS_41(__assign(__assign({ value: (__VLS_ctx.rows) }, { class: "p-datatable-sm" }), { stripedRows: true })));
        var __VLS_43 = __VLS_42.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.rows) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_42), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_46 = __VLS_44.slots.default;
        {
            var __VLS_47 = __VLS_44.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file text-4xl text-gray-400" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-file']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-2" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            // @ts-ignore
            [rows,];
        }
        var __VLS_48 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
            field: "label",
            header: "Metric",
        }));
        var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
                field: "label",
                header: "Metric",
            }], __VLS_functionalComponentArgsRest(__VLS_49), false));
        var __VLS_53 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
            field: "value",
            header: "Value",
        }));
        var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
                field: "value",
                header: "Value",
            }], __VLS_functionalComponentArgsRest(__VLS_54), false));
        // @ts-ignore
        [];
        var __VLS_44;
    }
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_31;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
