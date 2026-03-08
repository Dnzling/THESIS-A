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
var usetoast_1 = require("primevue/usetoast");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var select_1 = require("primevue/select");
var fileupload_1 = require("primevue/fileupload");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var tag_1 = require("primevue/tag");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var selectedMethod = (0, vue_1.ref)('csv');
var categories = (0, vue_1.ref)([]);
var csvData = (0, vue_1.ref)([]);
var applying = (0, vue_1.ref)(false);
var categoryForm = (0, vue_1.reactive)({
    category_id: null,
    adjustment_type: 'percentage',
    adjustment_value: 0,
    reason: ''
});
var percentageForm = (0, vue_1.reactive)({
    apply_to: 'All Products',
    percentage: 0,
    round_to: null,
    reason: ''
});
var adjustmentTypes = [
    { label: 'Percentage (%)', value: 'percentage' },
    { label: 'Fixed Amount (₱)', value: 'fixed' }
];
var applyToOptions = [
    'All Products',
    'Products with Stock',
    'Low Stock Items',
    'High Margin Products'
];
var roundingOptions = [
    { label: 'Nearest ₱10', value: 10 },
    { label: 'Nearest ₱100', value: 100 },
    { label: 'Nearest ₱1,000', value: 1000 }
];
var affectedProductsCount = (0, vue_1.computed)(function () {
    // Mock calculation
    return 45;
});
var canApply = (0, vue_1.computed)(function () {
    if (selectedMethod.value === 'csv')
        return csvData.value.length > 0;
    if (selectedMethod.value === 'category')
        return !!categoryForm.category_id;
    if (selectedMethod.value === 'percentage')
        return percentageForm.percentage !== 0;
    return false;
});
var loadCategories = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getCategories()];
            case 1:
                response = _a.sent();
                categories.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_1 = _a.sent();
                console.error('Failed to load categories:', error_1);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var handleCSVUpload = function (event) {
    var file = event.files[0];
    var reader = new FileReader();
    reader.onload = function (e) {
        var _a;
        var text = (_a = e.target) === null || _a === void 0 ? void 0 : _a.result;
        parseCSV(text);
    };
    reader.readAsText(file);
};
var parseCSV = function (text) {
    var lines = text.split('\n');
    var data = [];
    for (var i = 1; i < lines.length; i++) {
        var line = lines[i].split(',');
        if (line.length >= 2) {
            var currentPrice = Math.random() * 20000 + 5000; // Mock
            var newPrice = parseFloat(line[1]);
            data.push({
                sku: line[0],
                current_price: currentPrice,
                new_price: newPrice,
                change: ((newPrice - currentPrice) / currentPrice) * 100
            });
        }
    }
    csvData.value = data;
};
var downloadTemplate = function () {
    var csv = 'SKU,new_price,discounted_price,price_change_reason\nSOFA-001,25000,22000,Summer Sale\nCHAIR-002,8500,7500,Clearance';
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'bulk_pricing_template.csv';
    a.click();
};
var applyBulkUpdate = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                applying.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, new Promise(function (resolve) { return setTimeout(resolve, 2000); })];
            case 2:
                _a.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Bulk price update completed successfully',
                    life: 3000
                });
                setTimeout(function () {
                    router.push({ name: 'merchandising.pricing' });
                }, 1000);
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to apply bulk update',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                applying.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
(0, vue_1.onMounted)(function () {
    loadCategories();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-6xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-6xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Back to Pricing", icon: "pi pi-arrow-left", text: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back to Pricing", icon: "pi pi-arrow-left", text: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$router.push({ name: 'merchandising.pricing' });
            // @ts-ignore
            [$router,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cog text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-cog']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    // @ts-ignore
    [];
}
{
    var __VLS_14 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.selectedMethod = 'csv';
            // @ts-ignore
            [selectedMethod,];
        } }, { class: ([
            'p-6 border-2 rounded-lg cursor-pointer transition-all',
            __VLS_ctx.selectedMethod === 'csv'
                ? 'border-blue-600 bg-blue-50'
                : 'border-gray-200 hover:border-blue-300'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['pi pi-file-excel text-4xl mb-3', __VLS_ctx.selectedMethod === 'csv' ? 'text-blue-600' : 'text-gray-600']) }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-file-excel']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: (['font-semibold', __VLS_ctx.selectedMethod === 'csv' ? 'text-blue-900' : 'text-gray-900']) }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.selectedMethod = 'category';
            // @ts-ignore
            [selectedMethod, selectedMethod, selectedMethod, selectedMethod,];
        } }, { class: ([
            'p-6 border-2 rounded-lg cursor-pointer transition-all',
            __VLS_ctx.selectedMethod === 'category'
                ? 'border-blue-600 bg-blue-50'
                : 'border-gray-200 hover:border-blue-300'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['pi pi-sitemap text-4xl mb-3', __VLS_ctx.selectedMethod === 'category' ? 'text-blue-600' : 'text-gray-600']) }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-sitemap']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: (['font-semibold', __VLS_ctx.selectedMethod === 'category' ? 'text-blue-900' : 'text-gray-900']) }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.selectedMethod = 'percentage';
            // @ts-ignore
            [selectedMethod, selectedMethod, selectedMethod, selectedMethod,];
        } }, { class: ([
            'p-6 border-2 rounded-lg cursor-pointer transition-all',
            __VLS_ctx.selectedMethod === 'percentage'
                ? 'border-blue-600 bg-blue-50'
                : 'border-gray-200 hover:border-blue-300'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['pi pi-percentage text-4xl mb-3', __VLS_ctx.selectedMethod === 'percentage' ? 'text-blue-600' : 'text-gray-600']) }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-percentage']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: (['font-semibold', __VLS_ctx.selectedMethod === 'percentage' ? 'text-blue-900' : 'text-gray-900']) }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    // @ts-ignore
    [selectedMethod, selectedMethod, selectedMethod,];
}
// @ts-ignore
[];
var __VLS_10;
if (__VLS_ctx.selectedMethod === 'csv') {
    var __VLS_15 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15({}));
    var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_16), false));
    var __VLS_20 = __VLS_18.slots.default;
    {
        var __VLS_21 = __VLS_18.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-upload text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-upload']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [selectedMethod,];
    }
    {
        var __VLS_22 = __VLS_18.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-4" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-blue-900 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-900']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2 text-sm text-blue-800" }));
        /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "block bg-white p-2 rounded text-xs mt-2" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.br)({});
        var __VLS_23 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.FileUpload} */
        fileupload_1.default;
        // @ts-ignore
        var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23(__assign(__assign({ 'onSelect': {} }, { mode: "basic", accept: ".csv", maxFileSize: (1000000), chooseLabel: "Choose CSV File" }), { class: "w-full" })));
        var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([__assign(__assign({ 'onSelect': {} }, { mode: "basic", accept: ".csv", maxFileSize: (1000000), chooseLabel: "Choose CSV File" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_24), false));
        var __VLS_28 = void 0;
        var __VLS_29 = ({ select: {} },
            { onSelect: (__VLS_ctx.handleCSVUpload) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_26;
        var __VLS_27;
        var __VLS_30 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_31 = __VLS_asFunctionalComponent1(__VLS_30, new __VLS_30(__assign({ 'onClick': {} }, { label: "Download CSV Template", icon: "pi pi-download", severity: "secondary", outlined: true })));
        var __VLS_32 = __VLS_31.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Download CSV Template", icon: "pi pi-download", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_31), false));
        var __VLS_35 = void 0;
        var __VLS_36 = ({ click: {} },
            { onClick: (__VLS_ctx.downloadTemplate) });
        var __VLS_33;
        var __VLS_34;
        if (__VLS_ctx.csvData.length > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-6" }));
            /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-900 mb-4" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
            (__VLS_ctx.csvData.length);
            var __VLS_37 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
            datatable_1.default;
            // @ts-ignore
            var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign(__assign({ value: (__VLS_ctx.csvData) }, { class: "p-datatable-sm" }), { stripedRows: true })));
            var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.csvData) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_38), false));
            /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
            var __VLS_42 = __VLS_40.slots.default;
            var __VLS_43 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43({
                field: "sku",
                header: "SKU",
            }));
            var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([{
                    field: "sku",
                    header: "SKU",
                }], __VLS_functionalComponentArgsRest(__VLS_44), false));
            var __VLS_48 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
                field: "current_price",
                header: "Current Price",
            }));
            var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
                    field: "current_price",
                    header: "Current Price",
                }], __VLS_functionalComponentArgsRest(__VLS_49), false));
            var __VLS_53 = __VLS_51.slots.default;
            {
                var __VLS_54 = __VLS_51.slots.body;
                var data = __VLS_vSlot(__VLS_54)[0].data;
                (__VLS_ctx.formatPrice(data.current_price));
                // @ts-ignore
                [handleCSVUpload, downloadTemplate, csvData, csvData, csvData, formatPrice,];
            }
            // @ts-ignore
            [];
            var __VLS_51;
            var __VLS_55 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55({
                field: "new_price",
                header: "New Price",
            }));
            var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([{
                    field: "new_price",
                    header: "New Price",
                }], __VLS_functionalComponentArgsRest(__VLS_56), false));
            var __VLS_60 = __VLS_58.slots.default;
            {
                var __VLS_61 = __VLS_58.slots.body;
                var data = __VLS_vSlot(__VLS_61)[0].data;
                (__VLS_ctx.formatPrice(data.new_price));
                // @ts-ignore
                [formatPrice,];
            }
            // @ts-ignore
            [];
            var __VLS_58;
            var __VLS_62 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            column_1.default;
            // @ts-ignore
            var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62({
                header: "Change",
            }));
            var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([{
                    header: "Change",
                }], __VLS_functionalComponentArgsRest(__VLS_63), false));
            var __VLS_67 = __VLS_65.slots.default;
            {
                var __VLS_68 = __VLS_65.slots.body;
                var data = __VLS_vSlot(__VLS_68)[0].data;
                var __VLS_69 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69({
                    value: ("".concat(data.change > 0 ? '+' : '').concat(data.change.toFixed(1), "%")),
                    severity: (data.change > 0 ? 'success' : 'danger'),
                }));
                var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([{
                        value: ("".concat(data.change > 0 ? '+' : '').concat(data.change.toFixed(1), "%")),
                        severity: (data.change > 0 ? 'success' : 'danger'),
                    }], __VLS_functionalComponentArgsRest(__VLS_70), false));
                // @ts-ignore
                [];
            }
            // @ts-ignore
            [];
            var __VLS_65;
            // @ts-ignore
            [];
            var __VLS_40;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_18;
}
if (__VLS_ctx.selectedMethod === 'category') {
    var __VLS_74 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74({}));
    var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_75), false));
    var __VLS_79 = __VLS_77.slots.default;
    {
        var __VLS_80 = __VLS_77.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-sitemap text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-sitemap']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [selectedMethod,];
    }
    {
        var __VLS_81 = __VLS_77.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_82 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82({
            modelValue: (__VLS_ctx.categoryForm.category_id),
            options: (__VLS_ctx.categories),
            optionLabel: "category_name",
            optionValue: "id",
            placeholder: "Select category",
            filter: true,
        }));
        var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.categoryForm.category_id),
                options: (__VLS_ctx.categories),
                optionLabel: "category_name",
                optionValue: "id",
                placeholder: "Select category",
                filter: true,
            }], __VLS_functionalComponentArgsRest(__VLS_83), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_87 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
            modelValue: (__VLS_ctx.categoryForm.adjustment_type),
            options: (__VLS_ctx.adjustmentTypes),
            optionLabel: "label",
            optionValue: "value",
            placeholder: "Select type",
        }));
        var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.categoryForm.adjustment_type),
                options: (__VLS_ctx.adjustmentTypes),
                optionLabel: "label",
                optionValue: "value",
                placeholder: "Select type",
            }], __VLS_functionalComponentArgsRest(__VLS_88), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_92 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92({
            modelValue: (__VLS_ctx.categoryForm.adjustment_value),
            suffix: (__VLS_ctx.categoryForm.adjustment_type === 'percentage' ? '%' : ''),
            min: (__VLS_ctx.categoryForm.adjustment_type === 'percentage' ? -100 : undefined),
        }));
        var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.categoryForm.adjustment_value),
                suffix: (__VLS_ctx.categoryForm.adjustment_type === 'percentage' ? '%' : ''),
                min: (__VLS_ctx.categoryForm.adjustment_type === 'percentage' ? -100 : undefined),
            }], __VLS_functionalComponentArgsRest(__VLS_93), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_97 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({
            modelValue: (__VLS_ctx.categoryForm.reason),
            placeholder: "e.g., Seasonal discount",
        }));
        var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.categoryForm.reason),
                placeholder: "e.g., Seasonal discount",
            }], __VLS_functionalComponentArgsRest(__VLS_98), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-50 border border-orange-200 rounded-lg p-4" }));
        /** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-orange-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-orange-900" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-900']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle mr-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
        (__VLS_ctx.affectedProductsCount);
        // @ts-ignore
        [categoryForm, categoryForm, categoryForm, categoryForm, categoryForm, categoryForm, categories, adjustmentTypes, affectedProductsCount,];
    }
    // @ts-ignore
    [];
    var __VLS_77;
}
if (__VLS_ctx.selectedMethod === 'percentage') {
    var __VLS_102 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({}));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_103), false));
    var __VLS_107 = __VLS_105.slots.default;
    {
        var __VLS_108 = __VLS_105.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-percentage text-orange-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-percentage']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [selectedMethod,];
    }
    {
        var __VLS_109 = __VLS_105.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_110 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110({
            modelValue: (__VLS_ctx.percentageForm.apply_to),
            options: (__VLS_ctx.applyToOptions),
            placeholder: "Select scope",
        }));
        var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.percentageForm.apply_to),
                options: (__VLS_ctx.applyToOptions),
                placeholder: "Select scope",
            }], __VLS_functionalComponentArgsRest(__VLS_111), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_115 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115({
            modelValue: (__VLS_ctx.percentageForm.percentage),
            suffix: "%",
            min: (-100),
            max: (100),
            showButtons: true,
        }));
        var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.percentageForm.percentage),
                suffix: "%",
                min: (-100),
                max: (100),
                showButtons: true,
            }], __VLS_functionalComponentArgsRest(__VLS_116), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_120 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120({
            modelValue: (__VLS_ctx.percentageForm.round_to),
            options: (__VLS_ctx.roundingOptions),
            optionLabel: "label",
            optionValue: "value",
            placeholder: "No rounding",
        }));
        var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.percentageForm.round_to),
                options: (__VLS_ctx.roundingOptions),
                optionLabel: "label",
                optionValue: "value",
                placeholder: "No rounding",
            }], __VLS_functionalComponentArgsRest(__VLS_121), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_125 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_126 = __VLS_asFunctionalComponent1(__VLS_125, new __VLS_125({
            modelValue: (__VLS_ctx.percentageForm.reason),
            placeholder: "e.g., Annual price adjustment",
        }));
        var __VLS_127 = __VLS_126.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.percentageForm.reason),
                placeholder: "e.g., Annual price adjustment",
            }], __VLS_functionalComponentArgsRest(__VLS_126), false));
        if (__VLS_ctx.percentageForm.percentage) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 border border-green-200 rounded-lg p-4" }));
            /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-green-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-green-900 mb-2" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-green-900']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-3 gap-4 text-sm text-green-800" }));
            /** @type {__VLS_StyleScopedClasses['grid']} */ ;
            /** @type {__VLS_StyleScopedClasses['grid-cols-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-green-800']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (__VLS_ctx.formatPrice(10000 * (1 + __VLS_ctx.percentageForm.percentage / 100)));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (__VLS_ctx.percentageForm.percentage > 0 ? '+' : '');
            (__VLS_ctx.formatPrice(10000 * __VLS_ctx.percentageForm.percentage / 100));
        }
        // @ts-ignore
        [formatPrice, formatPrice, percentageForm, percentageForm, percentageForm, percentageForm, percentageForm, percentageForm, percentageForm, percentageForm, applyToOptions, roundingOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_105;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_130;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_131 = __VLS_asFunctionalComponent1(__VLS_130, new __VLS_130(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
var __VLS_132 = __VLS_131.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_131), false));
var __VLS_135;
var __VLS_136 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$router.push({ name: 'merchandising.pricing' });
            // @ts-ignore
            [$router,];
        } });
var __VLS_133;
var __VLS_134;
var __VLS_137;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_138 = __VLS_asFunctionalComponent1(__VLS_137, new __VLS_137(__assign({ 'onClick': {} }, { label: "Apply Changes", icon: "pi pi-check", loading: (__VLS_ctx.applying), disabled: (!__VLS_ctx.canApply) })));
var __VLS_139 = __VLS_138.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Apply Changes", icon: "pi pi-check", loading: (__VLS_ctx.applying), disabled: (!__VLS_ctx.canApply) })], __VLS_functionalComponentArgsRest(__VLS_138), false));
var __VLS_142;
var __VLS_143 = ({ click: {} },
    { onClick: (__VLS_ctx.applyBulkUpdate) });
var __VLS_140;
var __VLS_141;
// @ts-ignore
[applying, canApply, applyBulkUpdate,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
