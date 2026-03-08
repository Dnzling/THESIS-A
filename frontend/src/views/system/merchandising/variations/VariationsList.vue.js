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
var _a, _b, _c, _d;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../../stores/auth");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var select_1 = require("primevue/select");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var vue_router_1 = require("vue-router");
var toast = (0, usetoast_1.useToast)();
var router = (0, vue_router_1.useRouter)();
var authStore = (0, auth_1.useAuthStore)();
// State
var variations = (0, vue_1.ref)([]);
var products = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var updatingStock = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var bulkUpdateDialogVisible = (0, vue_1.ref)(false);
var viewDialogVisible = (0, vue_1.ref)(false);
var currentVariation = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var selectedVariations = (0, vue_1.ref)([]);
var productVariations = (0, vue_1.ref)([]);
var filters = (0, vue_1.reactive)({
    product_id: null,
    stock_status: null,
    is_active: null
});
var bulkUpdateData = (0, vue_1.reactive)({
    product_id: null,
    stocks: {}
});
var stockStatusOptions = ['In Stock', 'Low Stock', 'Out of Stock'];
var statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];
// Methods
var loadVariations = function () { return __awaiter(void 0, void 0, void 0, function () {
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
                variations.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load variations',
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
var loadProducts = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getProducts({ per_page: 1000 })];
            case 1:
                response = _a.sent();
                products.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to load products:', error_2);
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var loadProductVariations = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!bulkUpdateData.product_id)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, , 4]);
                return [4 /*yield*/, merchandising_service_1.default.getVariationsByProduct(bulkUpdateData.product_id)];
            case 2:
                response = _a.sent();
                productVariations.value = response.data.variations || [];
                // Initialize stock values
                bulkUpdateData.stocks = {};
                productVariations.value.forEach(function (v) {
                    bulkUpdateData.stocks[v.id] = v.stock_quantity;
                });
                return [3 /*break*/, 4];
            case 3:
                error_3 = _a.sent();
                console.error('Failed to load product variations:', error_3);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var onSearch = function () {
    loadVariations();
};
var onRowSelect = function () {
    // Handle row selection
};
var onRowUnselect = function () {
    // Handle row deselection
};
var viewVariation = function (variation) {
    currentVariation.value = variation;
    viewDialogVisible.value = true;
};
var openBulkUpdateDialog = function () {
    bulkUpdateData.product_id = null;
    bulkUpdateData.stocks = {};
    productVariations.value = [];
    bulkUpdateDialogVisible.value = true;
};
var submitBulkStockUpdate = function () { return __awaiter(void 0, void 0, void 0, function () {
    var updates, error_4;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!bulkUpdateData.product_id)
                    return [2 /*return*/];
                updatingStock.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                updates = Object.entries(bulkUpdateData.stocks).map(function (_a) {
                    var id = _a[0], stock = _a[1];
                    return ({
                        variation_id: parseInt(id),
                        stock_quantity: stock
                    });
                });
                return [4 /*yield*/, merchandising_service_1.default.bulkUpdateStock({ updates: updates })];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Stock quantities updated successfully',
                    life: 3000
                });
                bulkUpdateDialogVisible.value = false;
                loadVariations();
                return [3 /*break*/, 5];
            case 3:
                error_4 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_4.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to update stock',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                updatingStock.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var bulkUpdateStatus = function (isActive) { return __awaiter(void 0, void 0, void 0, function () {
    var ids, _i, ids_1, id, error_5;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (selectedVariations.value.length === 0)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 6, , 7]);
                ids = selectedVariations.value.map(function (v) { return v.id; });
                _i = 0, ids_1 = ids;
                _a.label = 2;
            case 2:
                if (!(_i < ids_1.length)) return [3 /*break*/, 5];
                id = ids_1[_i];
                return [4 /*yield*/, merchandising_service_1.default.updateVariation(id, { is_active: isActive })];
            case 3:
                _a.sent();
                _a.label = 4;
            case 4:
                _i++;
                return [3 /*break*/, 2];
            case 5:
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "".concat(ids.length, " variations ").concat(isActive ? 'activated' : 'deactivated'),
                    life: 3000
                });
                selectedVariations.value = [];
                loadVariations();
                return [3 /*break*/, 7];
            case 6:
                error_5 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to update variations',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 7: return [2 /*return*/];
        }
    });
}); };
var confirmDelete = function (variation) {
    currentVariation.value = variation;
    deleteDialogVisible.value = true;
};
var deleteVariation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_6;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteVariation(currentVariation.value.id)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Variation deleted successfully',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadVariations();
                return [3 /*break*/, 5];
            case 3:
                error_6 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_6.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete variation',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                deleting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var getStockSeverity = function (stock) {
    if (stock === 0)
        return 'danger';
    if (stock <= 10)
        return 'warning';
    return 'success';
};
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
var formatDate = function (date) {
    if (!date)
        return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
(0, vue_1.onMounted)(function () {
    loadVariations();
    loadProducts();
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
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Bulk Stock Update", icon: "pi pi-upload", severity: "secondary", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Stock Update", icon: "pi pi-upload", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.openBulkUpdateDialog) });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.authStore.hasPermission('merchandising.variations.create')) {
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Add Variation", icon: "pi pi-plus" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Variation", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.authStore.hasPermission('merchandising.variations.create')))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.variations.create' });
                // @ts-ignore
                [openBulkUpdateDialog, authStore, router,];
            } });
    var __VLS_10;
    var __VLS_11;
}
var __VLS_14;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
var __VLS_19 = __VLS_17.slots.default;
{
    var __VLS_20 = __VLS_17.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = __VLS_24.slots.default;
    var __VLS_27 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_28 = __VLS_asFunctionalComponent1(__VLS_27, new __VLS_27(__assign({ class: "pi pi-search" })));
    var __VLS_29 = __VLS_28.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_28), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search variations..." }), { class: "w-full" })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search variations..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_35;
    var __VLS_36;
    // @ts-ignore
    [searchQuery, onSearch,];
    var __VLS_24;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "All Products", showClear: true, filter: true })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "All Products", showClear: true, filter: true })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ change: {} },
        { onChange: (__VLS_ctx.loadVariations) });
    var __VLS_42;
    var __VLS_43;
    var __VLS_46 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatusOptions), placeholder: "All Stock Status", showClear: true })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatusOptions), placeholder: "All Stock Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    var __VLS_51 = void 0;
    var __VLS_52 = ({ change: {} },
        { onChange: (__VLS_ctx.loadVariations) });
    var __VLS_49;
    var __VLS_50;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    var __VLS_58 = void 0;
    var __VLS_59 = ({ change: {} },
        { onChange: (__VLS_ctx.loadVariations) });
    var __VLS_56;
    var __VLS_57;
    // @ts-ignore
    [filters, filters, filters, products, loadVariations, loadVariations, loadVariations, stockStatusOptions, statusOptions,];
}
// @ts-ignore
[];
var __VLS_17;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _e = __VLS_vFor((5)); _i < _e.length; _i++) {
        var i = _e[_i][0];
        var __VLS_60 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ key: (i), height: "100px" }, { class: "rounded-lg" })));
        var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ key: (i), height: "100px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.variations.length > 0) {
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({}));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = __VLS_68.slots.default;
    {
        var __VLS_71 = __VLS_68.slots.content;
        var __VLS_72 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        datatable_1.default;
        // @ts-ignore
        var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign(__assign(__assign(__assign({ 'onRowSelect': {} }, { 'onRowUnselect': {} }), { value: (__VLS_ctx.variations), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }), { class: "p-datatable-sm" }), { selection: (__VLS_ctx.selectedVariations) })));
        var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ 'onRowSelect': {} }, { 'onRowUnselect': {} }), { value: (__VLS_ctx.variations), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }), { class: "p-datatable-sm" }), { selection: (__VLS_ctx.selectedVariations) })], __VLS_functionalComponentArgsRest(__VLS_73), false));
        var __VLS_77 = void 0;
        var __VLS_78 = ({ rowSelect: {} },
            { onRowSelect: (__VLS_ctx.onRowSelect) });
        var __VLS_79 = ({ rowUnselect: {} },
            { onRowUnselect: (__VLS_ctx.onRowUnselect) });
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_80 = __VLS_75.slots.default;
        {
            var __VLS_81 = __VLS_75.slots.header;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-semibold text-gray-700" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
            (__VLS_ctx.variations.length);
            if (__VLS_ctx.selectedVariations.length > 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-blue-600" }));
                /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
                (__VLS_ctx.selectedVariations.length);
            }
            if (__VLS_ctx.selectedVariations.length > 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                var __VLS_82 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82(__assign({ 'onClick': {} }, { label: "Bulk Activate", icon: "pi pi-check", severity: "success", size: "small" })));
                var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Activate", icon: "pi pi-check", severity: "success", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_83), false));
                var __VLS_87 = void 0;
                var __VLS_88 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loading))
                                return;
                            if (!(__VLS_ctx.variations.length > 0))
                                return;
                            if (!(__VLS_ctx.selectedVariations.length > 0))
                                return;
                            __VLS_ctx.bulkUpdateStatus(true);
                            // @ts-ignore
                            [variations, variations, variations, selectedVariations, selectedVariations, selectedVariations, selectedVariations, onRowSelect, onRowUnselect, bulkUpdateStatus,];
                        } });
                var __VLS_85;
                var __VLS_86;
                var __VLS_89 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Button} */
                button_1.default;
                // @ts-ignore
                var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89(__assign({ 'onClick': {} }, { label: "Bulk Deactivate", icon: "pi pi-times", severity: "danger", size: "small", outlined: true })));
                var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Deactivate", icon: "pi pi-times", severity: "danger", size: "small", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_90), false));
                var __VLS_94 = void 0;
                var __VLS_95 = ({ click: {} },
                    { onClick: function () {
                            var _a = [];
                            for (var _i = 0; _i < arguments.length; _i++) {
                                _a[_i] = arguments[_i];
                            }
                            var $event = _a[0];
                            if (!!(__VLS_ctx.loading))
                                return;
                            if (!(__VLS_ctx.variations.length > 0))
                                return;
                            if (!(__VLS_ctx.selectedVariations.length > 0))
                                return;
                            __VLS_ctx.bulkUpdateStatus(false);
                            // @ts-ignore
                            [bulkUpdateStatus,];
                        } });
                var __VLS_92;
                var __VLS_93;
            }
            // @ts-ignore
            [];
        }
        {
            var __VLS_96 = __VLS_75.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-th-large text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-th-large']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            // @ts-ignore
            [];
        }
        var __VLS_97 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
            exportable: (false),
        }));
        var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{
                selectionMode: "multiple",
                headerStyle: "width: 3rem",
                exportable: (false),
            }], __VLS_functionalComponentArgsRest(__VLS_98), false));
        var __VLS_102 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({
            field: "variation_sku",
            header: "SKU",
            sortable: true,
            frozen: true,
        }));
        var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{
                field: "variation_sku",
                header: "SKU",
                sortable: true,
                frozen: true,
            }], __VLS_functionalComponentArgsRest(__VLS_103), false));
        var __VLS_107 = __VLS_105.slots.default;
        {
            var __VLS_108 = __VLS_105.slots.body;
            var data = __VLS_vSlot(__VLS_108)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (data.variation_sku);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_105;
        var __VLS_109 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109({
            field: "product.product_name",
            header: "Product",
            sortable: true,
        }));
        var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([{
                field: "product.product_name",
                header: "Product",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_110), false));
        var __VLS_114 = __VLS_112.slots.default;
        {
            var __VLS_115 = __VLS_112.slots.body;
            var data = __VLS_vSlot(__VLS_115)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            ((_a = data.product) === null || _a === void 0 ? void 0 : _a.product_name);
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 font-mono" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
            ((_b = data.product) === null || _b === void 0 ? void 0 : _b.sku);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_112;
        var __VLS_116 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_117 = __VLS_asFunctionalComponent1(__VLS_116, new __VLS_116({
            field: "variation_name",
            header: "Variation",
            sortable: true,
        }));
        var __VLS_118 = __VLS_117.apply(void 0, __spreadArray([{
                field: "variation_name",
                header: "Variation",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_117), false));
        var __VLS_121 = __VLS_119.slots.default;
        {
            var __VLS_122 = __VLS_119.slots.body;
            var data = __VLS_vSlot(__VLS_122)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            if (data.color_hex) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ style: ({ backgroundColor: data.color_hex }) }, { class: "w-6 h-6 rounded border-2 border-gray-300" }));
                /** @type {__VLS_StyleScopedClasses['w-6']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-6']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
            /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
            (data.variation_name);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_119;
        var __VLS_123 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123({
            header: "Attributes",
        }));
        var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([{
                header: "Attributes",
            }], __VLS_functionalComponentArgsRest(__VLS_124), false));
        var __VLS_128 = __VLS_126.slots.default;
        {
            var __VLS_129 = __VLS_126.slots.body;
            var data = __VLS_vSlot(__VLS_129)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            if (data.color) {
                var __VLS_130 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_131 = __VLS_asFunctionalComponent1(__VLS_130, new __VLS_130({
                    value: (data.color),
                    severity: "info",
                    size: "small",
                }));
                var __VLS_132 = __VLS_131.apply(void 0, __spreadArray([{
                        value: (data.color),
                        severity: "info",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_131), false));
            }
            if (data.size) {
                var __VLS_135 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_136 = __VLS_asFunctionalComponent1(__VLS_135, new __VLS_135({
                    value: (data.size),
                    severity: "secondary",
                    size: "small",
                }));
                var __VLS_137 = __VLS_136.apply(void 0, __spreadArray([{
                        value: (data.size),
                        severity: "secondary",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_136), false));
            }
            if (data.material) {
                var __VLS_140 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140({
                    value: (data.material),
                    severity: "success",
                    size: "small",
                }));
                var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([{
                        value: (data.material),
                        severity: "success",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_141), false));
            }
            if (data.finish) {
                var __VLS_145 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145({
                    value: (data.finish),
                    severity: "warning",
                    size: "small",
                }));
                var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([{
                        value: (data.finish),
                        severity: "warning",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_146), false));
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_126;
        var __VLS_150 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150({
            field: "final_price",
            header: "Price",
            sortable: true,
        }));
        var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([{
                field: "final_price",
                header: "Price",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_151), false));
        var __VLS_155 = __VLS_153.slots.default;
        {
            var __VLS_156 = __VLS_153.slots.body;
            var data = __VLS_vSlot(__VLS_156)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (__VLS_ctx.formatPrice(data.final_price || 0));
            if (data.price_adjustment !== 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                (data.price_adjustment > 0 ? '+' : '');
                (__VLS_ctx.formatPrice(data.price_adjustment));
            }
            // @ts-ignore
            [formatPrice, formatPrice,];
        }
        // @ts-ignore
        [];
        var __VLS_153;
        var __VLS_157 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157({
            field: "stock_quantity",
            header: "Stock",
            sortable: true,
        }));
        var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([{
                field: "stock_quantity",
                header: "Stock",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_158), false));
        var __VLS_162 = __VLS_160.slots.default;
        {
            var __VLS_163 = __VLS_160.slots.body;
            var data = __VLS_vSlot(__VLS_163)[0].data;
            var __VLS_164 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            badge_1.default;
            // @ts-ignore
            var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164({
                value: (data.stock_quantity),
                severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
            }));
            var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([{
                    value: (data.stock_quantity),
                    severity: (__VLS_ctx.getStockSeverity(data.stock_quantity)),
                }], __VLS_functionalComponentArgsRest(__VLS_165), false));
            // @ts-ignore
            [getStockSeverity,];
        }
        // @ts-ignore
        [];
        var __VLS_160;
        var __VLS_169 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_170 = __VLS_asFunctionalComponent1(__VLS_169, new __VLS_169({
            field: "weight_kg",
            header: "Weight",
        }));
        var __VLS_171 = __VLS_170.apply(void 0, __spreadArray([{
                field: "weight_kg",
                header: "Weight",
            }], __VLS_functionalComponentArgsRest(__VLS_170), false));
        var __VLS_174 = __VLS_172.slots.default;
        {
            var __VLS_175 = __VLS_172.slots.body;
            var data = __VLS_vSlot(__VLS_175)[0].data;
            if (data.weight_kg) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                (data.weight_kg);
            }
            else {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-400 italic" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                /** @type {__VLS_StyleScopedClasses['italic']} */ ;
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_172;
        var __VLS_176 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_177 = __VLS_asFunctionalComponent1(__VLS_176, new __VLS_176({
            field: "is_active",
            header: "Status",
        }));
        var __VLS_178 = __VLS_177.apply(void 0, __spreadArray([{
                field: "is_active",
                header: "Status",
            }], __VLS_functionalComponentArgsRest(__VLS_177), false));
        var __VLS_181 = __VLS_179.slots.default;
        {
            var __VLS_182 = __VLS_179.slots.body;
            var data = __VLS_vSlot(__VLS_182)[0].data;
            var __VLS_183 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_184 = __VLS_asFunctionalComponent1(__VLS_183, new __VLS_183({
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'secondary'),
            }));
            var __VLS_185 = __VLS_184.apply(void 0, __spreadArray([{
                    value: (data.is_active ? 'Active' : 'Inactive'),
                    severity: (data.is_active ? 'success' : 'secondary'),
                }], __VLS_functionalComponentArgsRest(__VLS_184), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_179;
        var __VLS_188 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        column_1.default;
        // @ts-ignore
        var __VLS_189 = __VLS_asFunctionalComponent1(__VLS_188, new __VLS_188({
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }));
        var __VLS_190 = __VLS_189.apply(void 0, __spreadArray([{
                header: "Actions",
                frozen: (true),
                alignFrozen: "right",
            }], __VLS_functionalComponentArgsRest(__VLS_189), false));
        var __VLS_193 = __VLS_191.slots.default;
        {
            var __VLS_194 = __VLS_191.slots.body;
            var data_1 = __VLS_vSlot(__VLS_194)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_195 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })));
            var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_196), false));
            var __VLS_200 = void 0;
            var __VLS_201 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.variations.length > 0))
                            return;
                        __VLS_ctx.viewVariation(data_1);
                        // @ts-ignore
                        [viewVariation,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View') }), null, null);
            var __VLS_198;
            var __VLS_199;
            var __VLS_202 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_203 = __VLS_asFunctionalComponent1(__VLS_202, new __VLS_202(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })));
            var __VLS_204 = __VLS_203.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_203), false));
            var __VLS_207 = void 0;
            var __VLS_208 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.variations.length > 0))
                            return;
                        __VLS_ctx.router.push({ name: 'merchandising.variations.edit', params: { id: data_1.id } });
                        // @ts-ignore
                        [router, vTooltip,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_205;
            var __VLS_206;
            var __VLS_209 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_210 = __VLS_asFunctionalComponent1(__VLS_209, new __VLS_209(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
            var __VLS_211 = __VLS_210.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_210), false));
            var __VLS_214 = void 0;
            var __VLS_215 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.variations.length > 0))
                            return;
                        __VLS_ctx.confirmDelete(data_1);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            var __VLS_212;
            var __VLS_213;
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_191;
        // @ts-ignore
        [];
        var __VLS_75;
        var __VLS_76;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_68;
}
else {
    var __VLS_216 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_217 = __VLS_asFunctionalComponent1(__VLS_216, new __VLS_216({}));
    var __VLS_218 = __VLS_217.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_217), false));
    var __VLS_221 = __VLS_219.slots.default;
    {
        var __VLS_222 = __VLS_219.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-th-large text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-th-large']} */ ;
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
        var __VLS_223 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_224 = __VLS_asFunctionalComponent1(__VLS_223, new __VLS_223(__assign(__assign({ 'onClick': {} }, { label: "Create Your First Variation", icon: "pi pi-plus" }), { class: "mt-4" })));
        var __VLS_225 = __VLS_224.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create Your First Variation", icon: "pi pi-plus" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_224), false));
        var __VLS_228 = void 0;
        var __VLS_229 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    if (!!(__VLS_ctx.variations.length > 0))
                        return;
                    __VLS_ctx.router.push({ name: 'merchandising.variations.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        var __VLS_226;
        var __VLS_227;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_219;
}
var __VLS_230;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_231 = __VLS_asFunctionalComponent1(__VLS_230, new __VLS_230(__assign({ visible: (__VLS_ctx.bulkUpdateDialogVisible), header: "Bulk Stock Update", modal: (true) }, { class: "w-full max-w-md" })));
var __VLS_232 = __VLS_231.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.bulkUpdateDialogVisible), header: "Bulk Stock Update", modal: (true) }, { class: "w-full max-w-md" })], __VLS_functionalComponentArgsRest(__VLS_231), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-md']} */ ;
var __VLS_235 = __VLS_233.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "bulk_product" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_236;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_237 = __VLS_asFunctionalComponent1(__VLS_236, new __VLS_236(__assign({ 'onChange': {} }, { id: "bulk_product", modelValue: (__VLS_ctx.bulkUpdateData.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "Choose a product", filter: true })));
var __VLS_238 = __VLS_237.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { id: "bulk_product", modelValue: (__VLS_ctx.bulkUpdateData.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "Choose a product", filter: true })], __VLS_functionalComponentArgsRest(__VLS_237), false));
var __VLS_241;
var __VLS_242 = ({ change: {} },
    { onChange: (__VLS_ctx.loadProductVariations) });
var __VLS_239;
var __VLS_240;
if (__VLS_ctx.bulkUpdateData.product_id) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    for (var _f = 0, _g = __VLS_vFor((__VLS_ctx.productVariations)); _f < _g.length; _f++) {
        var variation = _g[_f][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (variation.id) }, { class: "flex items-center gap-3 p-3 bg-gray-50 rounded" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-medium" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (variation.variation_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (variation.stock_quantity);
        var __VLS_243 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_244 = __VLS_asFunctionalComponent1(__VLS_243, new __VLS_243(__assign({ modelValue: (__VLS_ctx.bulkUpdateData.stocks[variation.id]), min: (0), showButtons: true, buttonLayout: "horizontal" }, { class: "w-32" })));
        var __VLS_245 = __VLS_244.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkUpdateData.stocks[variation.id]), min: (0), showButtons: true, buttonLayout: "horizontal" }, { class: "w-32" })], __VLS_functionalComponentArgsRest(__VLS_244), false));
        /** @type {__VLS_StyleScopedClasses['w-32']} */ ;
        // @ts-ignore
        [products, bulkUpdateDialogVisible, bulkUpdateData, bulkUpdateData, bulkUpdateData, loadProductVariations, productVariations,];
    }
}
{
    var __VLS_248 = __VLS_233.slots.footer;
    var __VLS_249 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_250 = __VLS_asFunctionalComponent1(__VLS_249, new __VLS_249(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_251 = __VLS_250.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_250), false));
    var __VLS_254 = void 0;
    var __VLS_255 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.bulkUpdateDialogVisible = false;
                // @ts-ignore
                [bulkUpdateDialogVisible,];
            } });
    var __VLS_252;
    var __VLS_253;
    var __VLS_256 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_257 = __VLS_asFunctionalComponent1(__VLS_256, new __VLS_256(__assign({ 'onClick': {} }, { label: "Update Stock", icon: "pi pi-check", loading: (__VLS_ctx.updatingStock) })));
    var __VLS_258 = __VLS_257.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Update Stock", icon: "pi pi-check", loading: (__VLS_ctx.updatingStock) })], __VLS_functionalComponentArgsRest(__VLS_257), false));
    var __VLS_261 = void 0;
    var __VLS_262 = ({ click: {} },
        { onClick: (__VLS_ctx.submitBulkStockUpdate) });
    var __VLS_259;
    var __VLS_260;
    // @ts-ignore
    [updatingStock, submitBulkStockUpdate,];
}
// @ts-ignore
[];
var __VLS_233;
var __VLS_263;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_264 = __VLS_asFunctionalComponent1(__VLS_263, new __VLS_263(__assign({ visible: (__VLS_ctx.viewDialogVisible), header: "Variation Details", modal: (true) }, { class: "w-full max-w-2xl" })));
var __VLS_265 = __VLS_264.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.viewDialogVisible), header: "Variation Details", modal: (true) }, { class: "w-full max-w-2xl" })], __VLS_functionalComponentArgsRest(__VLS_264), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-2xl']} */ ;
var __VLS_268 = __VLS_266.slots.default;
if (__VLS_ctx.currentVariation) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold font-mono" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
    (__VLS_ctx.currentVariation.variation_sku);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    ((_c = __VLS_ctx.currentVariation.product) === null || _c === void 0 ? void 0 : _c.product_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.currentVariation.variation_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_269 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_270 = __VLS_asFunctionalComponent1(__VLS_269, new __VLS_269({
        value: (__VLS_ctx.currentVariation.is_active ? 'Active' : 'Inactive'),
        severity: (__VLS_ctx.currentVariation.is_active ? 'success' : 'secondary'),
    }));
    var __VLS_271 = __VLS_270.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.currentVariation.is_active ? 'Active' : 'Inactive'),
            severity: (__VLS_ctx.currentVariation.is_active ? 'success' : 'secondary'),
        }], __VLS_functionalComponentArgsRest(__VLS_270), false));
    if (__VLS_ctx.currentVariation.color || __VLS_ctx.currentVariation.size || __VLS_ctx.currentVariation.material) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        if (__VLS_ctx.currentVariation.color) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 px-3 py-2 bg-blue-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            if (__VLS_ctx.currentVariation.color_hex) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ style: ({ backgroundColor: __VLS_ctx.currentVariation.color_hex }) }, { class: "w-5 h-5 rounded border border-gray-300" }));
                /** @type {__VLS_StyleScopedClasses['w-5']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-5']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                /** @type {__VLS_StyleScopedClasses['border']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (__VLS_ctx.currentVariation.color);
        }
        if (__VLS_ctx.currentVariation.size) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-3 py-2 bg-purple-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-purple-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (__VLS_ctx.currentVariation.size);
        }
        if (__VLS_ctx.currentVariation.material) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-3 py-2 bg-green-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (__VLS_ctx.currentVariation.material);
        }
        if (__VLS_ctx.currentVariation.finish) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "px-3 py-2 bg-orange-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['px-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-orange-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (__VLS_ctx.currentVariation.finish);
        }
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (__VLS_ctx.formatPrice(((_d = __VLS_ctx.currentVariation.product) === null || _d === void 0 ? void 0 : _d.base_price) || 0));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-semibold" }, { class: (__VLS_ctx.currentVariation.price_adjustment >= 0 ? 'text-green-600' : 'text-red-600') }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.currentVariation.price_adjustment >= 0 ? '+' : '');
    (__VLS_ctx.formatPrice(__VLS_ctx.currentVariation.price_adjustment || 0));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    (__VLS_ctx.formatPrice(__VLS_ctx.currentVariation.final_price || 0));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_274 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_275 = __VLS_asFunctionalComponent1(__VLS_274, new __VLS_274({
        value: (__VLS_ctx.currentVariation.stock_quantity),
        severity: (__VLS_ctx.getStockSeverity(__VLS_ctx.currentVariation.stock_quantity)),
    }));
    var __VLS_276 = __VLS_275.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.currentVariation.stock_quantity),
            severity: (__VLS_ctx.getStockSeverity(__VLS_ctx.currentVariation.stock_quantity)),
        }], __VLS_functionalComponentArgsRest(__VLS_275), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.currentVariation.weight_kg || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.currentVariation.length_cm || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    (__VLS_ctx.currentVariation.width_cm || 'N/A');
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4 text-sm" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    (__VLS_ctx.formatDate(__VLS_ctx.currentVariation.created_at));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    (__VLS_ctx.formatDate(__VLS_ctx.currentVariation.updated_at));
}
{
    var __VLS_279 = __VLS_266.slots.footer;
    var __VLS_280 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_281 = __VLS_asFunctionalComponent1(__VLS_280, new __VLS_280(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil" })));
    var __VLS_282 = __VLS_281.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil" })], __VLS_functionalComponentArgsRest(__VLS_281), false));
    var __VLS_285 = void 0;
    var __VLS_286 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.$router.push({ name: 'merchandising.variations.edit', params: { id: __VLS_ctx.currentVariation.id } });
                // @ts-ignore
                [formatPrice, formatPrice, formatPrice, getStockSeverity, viewDialogVisible, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, currentVariation, formatDate, formatDate, $router,];
            } });
    var __VLS_283;
    var __VLS_284;
    var __VLS_287 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_288 = __VLS_asFunctionalComponent1(__VLS_287, new __VLS_287(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })));
    var __VLS_289 = __VLS_288.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_288), false));
    var __VLS_292 = void 0;
    var __VLS_293 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.viewDialogVisible = false;
                // @ts-ignore
                [viewDialogVisible,];
            } });
    var __VLS_290;
    var __VLS_291;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_266;
var __VLS_294;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_295 = __VLS_asFunctionalComponent1(__VLS_294, new __VLS_294(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_296 = __VLS_295.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_295), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_299 = __VLS_297.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-red-600" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold" }));
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_300 = __VLS_297.slots.footer;
    var __VLS_301 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_302 = __VLS_asFunctionalComponent1(__VLS_301, new __VLS_301(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_303 = __VLS_302.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_302), false));
    var __VLS_306 = void 0;
    var __VLS_307 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteDialogVisible = false;
                // @ts-ignore
                [deleteDialogVisible, deleteDialogVisible,];
            } });
    var __VLS_304;
    var __VLS_305;
    var __VLS_308 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_309 = __VLS_asFunctionalComponent1(__VLS_308, new __VLS_308(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_310 = __VLS_309.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_309), false));
    var __VLS_313 = void 0;
    var __VLS_314 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteVariation) });
    var __VLS_311;
    var __VLS_312;
    // @ts-ignore
    [deleting, deleteVariation,];
}
// @ts-ignore
[];
var __VLS_297;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
