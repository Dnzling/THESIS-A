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
var _a, _b, _c, _d, _e, _f, _g;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../../stores/auth");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var progressbar_1 = require("primevue/progressbar");
var fileupload_1 = require("primevue/fileupload");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var inventory = (0, vue_1.ref)([]);
var categories = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var exporting = (0, vue_1.ref)(false);
var updating = (0, vue_1.ref)(false);
var bulkUpdating = (0, vue_1.ref)(false);
var stockUpdateDialogVisible = (0, vue_1.ref)(false);
var bulkUpdateDialogVisible = (0, vue_1.ref)(false);
var currentItem = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var stats = (0, vue_1.reactive)({
    totalProducts: 0,
    inStock: 0,
    lowStock: 0,
    outOfStock: 0
});
var filters = (0, vue_1.reactive)({
    category_id: null,
    stock_status: null,
    sort_by: 'stock_asc'
});
var stockUpdateData = (0, vue_1.reactive)({
    new_quantity: 0,
    reason: ''
});
var stockStatusOptions = ['All', 'In Stock', 'Low Stock', 'Out of Stock'];
var sortOptions = [
    { label: 'Stock: Low to High', value: 'stock_asc' },
    { label: 'Stock: High to Low', value: 'stock_desc' },
    { label: 'Product Name A-Z', value: 'name_asc' },
    { label: 'Product Name Z-A', value: 'name_desc' }
];
// Methods
var loadInventory = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                params = __assign({}, filters);
                if (searchQuery.value)
                    params.search = searchQuery.value;
                return [4 /*yield*/, merchandising_service_1.default.getVariations(params)];
            case 2:
                response = _a.sent();
                inventory.value = response.data.data;
                // Calculate stats
                calculateStats();
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load inventory',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadCategories = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
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
                error_2 = _a.sent();
                console.error('Failed to load categories:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var calculateStats = function () {
    stats.totalProducts = inventory.value.length;
    stats.inStock = inventory.value.filter(function (item) { return item.stock_quantity > 10; }).length;
    stats.lowStock = inventory.value.filter(function (item) { return item.stock_quantity > 0 && item.stock_quantity <= 10; }).length;
    stats.outOfStock = inventory.value.filter(function (item) { return item.stock_quantity === 0; }).length;
};
var onSearch = function () {
    loadInventory();
};
var getStockStatus = function (quantity) {
    if (quantity === 0)
        return 'Out of Stock';
    if (quantity <= 10)
        return 'Low Stock';
    return 'In Stock';
};
var getStockSeverity = function (quantity) {
    if (quantity === 0)
        return 'danger';
    if (quantity <= 10)
        return 'warning';
    return 'success';
};
var getStockColor = function (quantity) {
    if (quantity === 0)
        return '#ef4444';
    if (quantity <= 10)
        return '#f97316';
    return '#10b981';
};
var getStockPercentage = function (quantity) {
    var max = 100;
    return Math.min((quantity / max) * 100, 100);
};
var openStockUpdateDialog = function (item) {
    currentItem.value = item;
    stockUpdateData.new_quantity = item.stock_quantity;
    stockUpdateData.reason = '';
    stockUpdateDialogVisible.value = true;
};
var updateStock = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!currentItem.value)
                    return [2 /*return*/];
                updating.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.updateVariation(currentItem.value.id, {
                        stock_quantity: stockUpdateData.new_quantity
                    })];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Stock quantity updated successfully',
                    life: 3000
                });
                stockUpdateDialogVisible.value = false;
                loadInventory();
                return [3 /*break*/, 5];
            case 3:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to update stock',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                updating.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var openBulkUpdateDialog = function () {
    bulkUpdateDialogVisible.value = true;
};
var handleCSVUpload = function (event) {
    console.log('CSV uploaded:', event.files[0]);
    // TODO: Parse CSV and prepare for bulk update
};
var processBulkUpdate = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        bulkUpdating.value = true;
        try {
            // TODO: Implement bulk update logic
            toast.add({
                severity: 'info',
                summary: 'Coming Soon',
                detail: 'Bulk update feature will be available soon',
                life: 3000
            });
            bulkUpdateDialogVisible.value = false;
        }
        finally {
            bulkUpdating.value = false;
        }
        return [2 /*return*/];
    });
}); };
var viewStockHistory = function (item) {
    toast.add({
        severity: 'info',
        summary: 'Coming Soon',
        detail: 'Stock history feature will be available soon',
        life: 3000
    });
};
var exportInventory = function () {
    exporting.value = true;
    setTimeout(function () {
        toast.add({
            severity: 'success',
            summary: 'Export Started',
            detail: 'Inventory report will be downloaded shortly',
            life: 3000
        });
        exporting.value = false;
    }, 1000);
};
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
(0, vue_1.onMounted)(function () {
    loadCategories();
    loadInventory();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary", outlined: true, loading: (__VLS_ctx.exporting) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary", outlined: true, loading: (__VLS_ctx.exporting) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.exportInventory) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Bulk Stock Update", icon: "pi pi-upload" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Stock Update", icon: "pi pi-upload" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: (__VLS_ctx.openBulkUpdateDialog) });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
{
    var __VLS_20 = __VLS_17.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.totalProducts);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    // @ts-ignore
    [exporting, exportInventory, openBulkUpdateDialog, stats,];
}
// @ts-ignore
[];
var __VLS_17;
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26 = __VLS_24.slots.default;
{
    var __VLS_27 = __VLS_24.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-green-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.inStock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_24;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({}));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_29), false));
var __VLS_33 = __VLS_31.slots.default;
{
    var __VLS_34 = __VLS_31.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-orange-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.lowStock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-orange-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-orange-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_31;
var __VLS_35;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35({}));
var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_36), false));
var __VLS_40 = __VLS_38.slots.default;
{
    var __VLS_41 = __VLS_38.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-red-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.outOfStock);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-red-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle text-red-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    // @ts-ignore
    [stats,];
}
// @ts-ignore
[];
var __VLS_38;
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42({}));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_43), false));
var __VLS_47 = __VLS_45.slots.default;
{
    var __VLS_48 = __VLS_45.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49({}));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_50), false));
    var __VLS_54 = __VLS_52.slots.default;
    var __VLS_55 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign({ class: "pi pi-search" })));
    var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_56), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search products..." }), { class: "w-full" })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search products..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_63;
    var __VLS_64;
    // @ts-ignore
    [searchQuery, onSearch,];
    var __VLS_52;
    var __VLS_67 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "All Categories", showClear: true, filter: true })));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "All Categories", showClear: true, filter: true })], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = void 0;
    var __VLS_73 = ({ change: {} },
        { onChange: (__VLS_ctx.loadInventory) });
    var __VLS_70;
    var __VLS_71;
    var __VLS_74 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatusOptions), placeholder: "All Stock Status", showClear: true })));
    var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatusOptions), placeholder: "All Stock Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_75), false));
    var __VLS_79 = void 0;
    var __VLS_80 = ({ change: {} },
        { onChange: (__VLS_ctx.loadInventory) });
    var __VLS_77;
    var __VLS_78;
    var __VLS_81 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_82 = __VLS_asFunctionalComponent1(__VLS_81, new __VLS_81(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.sort_by), options: (__VLS_ctx.sortOptions), optionLabel: "label", optionValue: "value", placeholder: "Sort by" })));
    var __VLS_83 = __VLS_82.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.sort_by), options: (__VLS_ctx.sortOptions), optionLabel: "label", optionValue: "value", placeholder: "Sort by" })], __VLS_functionalComponentArgsRest(__VLS_82), false));
    var __VLS_86 = void 0;
    var __VLS_87 = ({ change: {} },
        { onChange: (__VLS_ctx.loadInventory) });
    var __VLS_84;
    var __VLS_85;
    // @ts-ignore
    [filters, filters, filters, categories, loadInventory, loadInventory, loadInventory, stockStatusOptions, sortOptions,];
}
// @ts-ignore
[];
var __VLS_45;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _h = __VLS_vFor((10)); _i < _h.length; _i++) {
        var i = _h[_i][0];
        var __VLS_88 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })));
        var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_89), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.inventory.length > 0) {
    var __VLS_93 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({}));
    var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_94), false));
    var __VLS_98 = __VLS_96.slots.default;
    {
        var __VLS_99 = __VLS_96.slots.content;
        var __VLS_100 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        datatable_1.default;
        // @ts-ignore
        var __VLS_101 = __VLS_asFunctionalComponent1(__VLS_100, new __VLS_100(__assign(__assign({ value: (__VLS_ctx.inventory), paginator: (true), rows: (20), rowsPerPageOptions: ([20, 50, 100]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" }), { responsiveLayout: "scroll" })));
        var __VLS_102 = __VLS_101.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.inventory), paginator: (true), rows: (20), rowsPerPageOptions: ([20, 50, 100]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" }), { responsiveLayout: "scroll" })], __VLS_functionalComponentArgsRest(__VLS_101), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_105 = __VLS_103.slots.default;
        {
            var __VLS_106 = __VLS_103.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            // @ts-ignore
            [inventory, inventory,];
        }
        var __VLS_107 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107({
            field: "product.sku",
            header: "SKU",
            frozen: true,
        }));
        var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([{
                field: "product.sku",
                header: "SKU",
                frozen: true,
            }], __VLS_functionalComponentArgsRest(__VLS_108), false));
        var __VLS_112 = __VLS_110.slots.default;
        {
            var __VLS_113 = __VLS_110.slots.body;
            var data = __VLS_vSlot(__VLS_113)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (data.variation_sku || ((_a = data.product) === null || _a === void 0 ? void 0 : _a.sku));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_110;
        var __VLS_114 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114({
            field: "product.product_name",
            header: "Product",
            sortable: true,
        }));
        var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([{
                field: "product.product_name",
                header: "Product",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_115), false));
        var __VLS_119 = __VLS_117.slots.default;
        {
            var __VLS_120 = __VLS_117.slots.body;
            var data = __VLS_vSlot(__VLS_120)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            ((_b = data.product) === null || _b === void 0 ? void 0 : _b.product_name);
            if (data.variation_name) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                (data.variation_name);
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_117;
        var __VLS_121 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_122 = __VLS_asFunctionalComponent1(__VLS_121, new __VLS_121({
            field: "category",
            header: "Category",
        }));
        var __VLS_123 = __VLS_122.apply(void 0, __spreadArray([{
                field: "category",
                header: "Category",
            }], __VLS_functionalComponentArgsRest(__VLS_122), false));
        var __VLS_126 = __VLS_124.slots.default;
        {
            var __VLS_127 = __VLS_124.slots.body;
            var data = __VLS_vSlot(__VLS_127)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-700" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            (((_d = (_c = data.product) === null || _c === void 0 ? void 0 : _c.category) === null || _d === void 0 ? void 0 : _d.category_name) || 'N/A');
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_124;
        var __VLS_128 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128({
            field: "stock_quantity",
            header: "Stock Quantity",
            sortable: true,
        }));
        var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([{
                field: "stock_quantity",
                header: "Stock Quantity",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_129), false));
        var __VLS_133 = __VLS_131.slots.default;
        {
            var __VLS_134 = __VLS_131.slots.body;
            var data = __VLS_vSlot(__VLS_134)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            var __VLS_135 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            badge_1.default;
            // @ts-ignore
            var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135({
                value: (data.stock_quantity),
                severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
            }));
            var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([{
                    value: (data.stock_quantity),
                    severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
                }], __VLS_functionalComponentArgsRest(__VLS_136), false));
            var __VLS_140 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.ProgressBar} */
            progressbar_1.default;
            // @ts-ignore
            var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140(__assign(__assign({ value: (__VLS_ctx.getStockPercentage(data.stock_quantity)), showValue: (false) }, { style: ({ height: '6px', width: '80px' }) }), { pt: ({
                    value: { style: { background: __VLS_ctx.getStockColor(data.stock_quantity) } }
                }) })));
            var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.getStockPercentage(data.stock_quantity)), showValue: (false) }, { style: ({ height: '6px', width: '80px' }) }), { pt: ({
                        value: { style: { background: __VLS_ctx.getStockColor(data.stock_quantity) } }
                    }) })], __VLS_functionalComponentArgsRest(__VLS_141), false));
            // @ts-ignore
            [getStockSeverity, getStockPercentage, getStockColor,];
        }
        // @ts-ignore
        [];
        var __VLS_131;
        var __VLS_145 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145({
            field: "reorder_level",
            header: "Reorder Level",
        }));
        var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([{
                field: "reorder_level",
                header: "Reorder Level",
            }], __VLS_functionalComponentArgsRest(__VLS_146), false));
        var __VLS_150 = __VLS_148.slots.default;
        {
            var __VLS_151 = __VLS_148.slots.body;
            var data = __VLS_vSlot(__VLS_151)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            (data.reorder_level || 10);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_148;
        var __VLS_152 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152({
            field: "price",
            header: "Price",
        }));
        var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([{
                field: "price",
                header: "Price",
            }], __VLS_functionalComponentArgsRest(__VLS_153), false));
        var __VLS_157 = __VLS_155.slots.default;
        {
            var __VLS_158 = __VLS_155.slots.body;
            var data = __VLS_vSlot(__VLS_158)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (__VLS_ctx.formatPrice(data.final_price || ((_e = data.product) === null || _e === void 0 ? void 0 : _e.base_price) || 0));
            // @ts-ignore
            [formatPrice,];
        }
        // @ts-ignore
        [];
        var __VLS_155;
        var __VLS_159 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159({
            field: "status",
            header: "Status",
        }));
        var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([{
                field: "status",
                header: "Status",
            }], __VLS_functionalComponentArgsRest(__VLS_160), false));
        var __VLS_164 = __VLS_162.slots.default;
        {
            var __VLS_165 = __VLS_162.slots.body;
            var data = __VLS_vSlot(__VLS_165)[0].data;
            var __VLS_166 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_167 = __VLS_asFunctionalComponent1(__VLS_166, new __VLS_166({
                value: (__VLS_ctx.getStockStatus(data.stock_quantity)),
                severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
            }));
            var __VLS_168 = __VLS_167.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.getStockStatus(data.stock_quantity)),
                    severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
                }], __VLS_functionalComponentArgsRest(__VLS_167), false));
            // @ts-ignore
            [getStockSeverity, getStockStatus,];
        }
        // @ts-ignore
        [];
        var __VLS_162;
        var __VLS_171 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_172 = __VLS_asFunctionalComponent1(__VLS_171, new __VLS_171({
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }));
        var __VLS_173 = __VLS_172.apply(void 0, __spreadArray([{
                header: "Actions",
                frozen: (true),
                alignFrozen: "right",
            }], __VLS_functionalComponentArgsRest(__VLS_172), false));
        var __VLS_176 = __VLS_174.slots.default;
        {
            var __VLS_177 = __VLS_174.slots.body;
            var data_1 = __VLS_vSlot(__VLS_177)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_178 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_179 = __VLS_asFunctionalComponent1(__VLS_178, new __VLS_178(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "info", text: true, rounded: true, size: "small" })));
            var __VLS_180 = __VLS_179.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_179), false));
            var __VLS_183 = void 0;
            var __VLS_184 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.inventory.length > 0))
                            return;
                        __VLS_ctx.openStockUpdateDialog(data_1);
                        // @ts-ignore
                        [openStockUpdateDialog,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Update Stock') }), null, null);
            var __VLS_181;
            var __VLS_182;
            var __VLS_185 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign({ 'onClick': {} }, { icon: "pi pi-chart-line", severity: "secondary", text: true, rounded: true, size: "small" })));
            var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-chart-line", severity: "secondary", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_186), false));
            var __VLS_190 = void 0;
            var __VLS_191 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.inventory.length > 0))
                            return;
                        __VLS_ctx.viewStockHistory(data_1);
                        // @ts-ignore
                        [vTooltip, viewStockHistory,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View History') }), null, null);
            var __VLS_188;
            var __VLS_189;
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_174;
        // @ts-ignore
        [];
        var __VLS_103;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_96;
}
else {
    var __VLS_192 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_193 = __VLS_asFunctionalComponent1(__VLS_192, new __VLS_192({}));
    var __VLS_194 = __VLS_193.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_193), false));
    var __VLS_197 = __VLS_195.slots.default;
    {
        var __VLS_198 = __VLS_195.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-sm mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_195;
}
var __VLS_199;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_200 = __VLS_asFunctionalComponent1(__VLS_199, new __VLS_199(__assign({ visible: (__VLS_ctx.stockUpdateDialogVisible), header: "Update Stock Quantity", modal: (true) }, { class: "w-full max-w-md" })));
var __VLS_201 = __VLS_200.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.stockUpdateDialogVisible), header: "Update Stock Quantity", modal: (true) }, { class: "w-full max-w-md" })], __VLS_functionalComponentArgsRest(__VLS_200), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-md']} */ ;
var __VLS_204 = __VLS_202.slots.default;
if (__VLS_ctx.currentItem) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    ((_f = __VLS_ctx.currentItem.product) === null || _f === void 0 ? void 0 : _f.product_name);
    if (__VLS_ctx.currentItem.variation_name) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        (__VLS_ctx.currentItem.variation_name);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (__VLS_ctx.currentItem.variation_sku || ((_g = __VLS_ctx.currentItem.product) === null || _g === void 0 ? void 0 : _g.sku));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.currentItem.stock_quantity);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "new_stock" }, { class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_205 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    inputnumber_1.default;
    // @ts-ignore
    var __VLS_206 = __VLS_asFunctionalComponent1(__VLS_205, new __VLS_205({
        id: "new_stock",
        modelValue: (__VLS_ctx.stockUpdateData.new_quantity),
        min: (0),
        showButtons: true,
        buttonLayout: "horizontal",
    }));
    var __VLS_207 = __VLS_206.apply(void 0, __spreadArray([{
            id: "new_stock",
            modelValue: (__VLS_ctx.stockUpdateData.new_quantity),
            min: (0),
            showButtons: true,
            buttonLayout: "horizontal",
        }], __VLS_functionalComponentArgsRest(__VLS_206), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "reason" }, { class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_210 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    textarea_1.default;
    // @ts-ignore
    var __VLS_211 = __VLS_asFunctionalComponent1(__VLS_210, new __VLS_210({
        id: "reason",
        modelValue: (__VLS_ctx.stockUpdateData.reason),
        rows: "3",
        placeholder: "e.g., Stock received, Damaged items, etc.",
    }));
    var __VLS_212 = __VLS_211.apply(void 0, __spreadArray([{
            id: "reason",
            modelValue: (__VLS_ctx.stockUpdateData.reason),
            rows: "3",
            placeholder: "e.g., Stock received, Damaged items, etc.",
        }], __VLS_functionalComponentArgsRest(__VLS_211), false));
}
{
    var __VLS_215 = __VLS_202.slots.footer;
    var __VLS_216 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_217 = __VLS_asFunctionalComponent1(__VLS_216, new __VLS_216(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_218 = __VLS_217.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_217), false));
    var __VLS_221 = void 0;
    var __VLS_222 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.stockUpdateDialogVisible = false;
                // @ts-ignore
                [stockUpdateDialogVisible, stockUpdateDialogVisible, currentItem, currentItem, currentItem, currentItem, currentItem, currentItem, currentItem, stockUpdateData, stockUpdateData,];
            } });
    var __VLS_219;
    var __VLS_220;
    var __VLS_223 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_224 = __VLS_asFunctionalComponent1(__VLS_223, new __VLS_223(__assign({ 'onClick': {} }, { label: "Update Stock", icon: "pi pi-check", loading: (__VLS_ctx.updating) })));
    var __VLS_225 = __VLS_224.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Update Stock", icon: "pi pi-check", loading: (__VLS_ctx.updating) })], __VLS_functionalComponentArgsRest(__VLS_224), false));
    var __VLS_228 = void 0;
    var __VLS_229 = ({ click: {} },
        { onClick: (__VLS_ctx.updateStock) });
    var __VLS_226;
    var __VLS_227;
    // @ts-ignore
    [updating, updateStock,];
}
// @ts-ignore
[];
var __VLS_202;
var __VLS_230;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_231 = __VLS_asFunctionalComponent1(__VLS_230, new __VLS_230(__assign({ visible: (__VLS_ctx.bulkUpdateDialogVisible), header: "Bulk Stock Update", modal: (true) }, { class: "w-full max-w-2xl" })));
var __VLS_232 = __VLS_231.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.bulkUpdateDialogVisible), header: "Bulk Stock Update", modal: (true) }, { class: "w-full max-w-2xl" })], __VLS_functionalComponentArgsRest(__VLS_231), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-2xl']} */ ;
var __VLS_235 = __VLS_233.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
var __VLS_236;
/** @ts-ignore @type {typeof __VLS_components.FileUpload} */
fileupload_1.default;
// @ts-ignore
var __VLS_237 = __VLS_asFunctionalComponent1(__VLS_236, new __VLS_236(__assign(__assign({ 'onSelect': {} }, { mode: "basic", accept: ".csv", maxFileSize: (1000000), chooseLabel: "Choose CSV File" }), { class: "w-full" })));
var __VLS_238 = __VLS_237.apply(void 0, __spreadArray([__assign(__assign({ 'onSelect': {} }, { mode: "basic", accept: ".csv", maxFileSize: (1000000), chooseLabel: "Choose CSV File" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_237), false));
var __VLS_241;
var __VLS_242 = ({ select: {} },
    { onSelect: (__VLS_ctx.handleCSVUpload) });
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
var __VLS_239;
var __VLS_240;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-xs text-blue-800 block" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-xs text-blue-800 block mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.code, __VLS_intrinsics.code)(__assign({ class: "text-xs text-blue-800 block" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
/** @type {__VLS_StyleScopedClasses['block']} */ ;
{
    var __VLS_243 = __VLS_233.slots.footer;
    var __VLS_244 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_245 = __VLS_asFunctionalComponent1(__VLS_244, new __VLS_244(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_246 = __VLS_245.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_245), false));
    var __VLS_249 = void 0;
    var __VLS_250 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.bulkUpdateDialogVisible = false;
                // @ts-ignore
                [bulkUpdateDialogVisible, bulkUpdateDialogVisible, handleCSVUpload,];
            } });
    var __VLS_247;
    var __VLS_248;
    var __VLS_251 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_252 = __VLS_asFunctionalComponent1(__VLS_251, new __VLS_251(__assign({ 'onClick': {} }, { label: "Upload & Update", icon: "pi pi-upload", loading: (__VLS_ctx.bulkUpdating) })));
    var __VLS_253 = __VLS_252.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Upload & Update", icon: "pi pi-upload", loading: (__VLS_ctx.bulkUpdating) })], __VLS_functionalComponentArgsRest(__VLS_252), false));
    var __VLS_256 = void 0;
    var __VLS_257 = ({ click: {} },
        { onClick: (__VLS_ctx.processBulkUpdate) });
    var __VLS_254;
    var __VLS_255;
    // @ts-ignore
    [bulkUpdating, processBulkUpdate,];
}
// @ts-ignore
[];
var __VLS_233;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
