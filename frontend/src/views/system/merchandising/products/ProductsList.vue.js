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
var usetoast_1 = require("primevue/usetoast");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var auth_1 = require("../../../../stores/auth");
var vue_router_1 = require("vue-router");
var authStore = (0, auth_1.useAuthStore)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
// State
var products = (0, vue_1.ref)([]);
var categories = (0, vue_1.ref)([]);
var tags = (0, vue_1.ref)([]);
var attributes = (0, vue_1.ref)([]);
var selectedProducts = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var totalRecords = (0, vue_1.ref)(0);
var dialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var currentProduct = (0, vue_1.ref)(null);
var filters = (0, vue_1.reactive)({
    search: '',
    category_id: null,
    is_active: null,
    page: 1,
    per_page: 15,
    sort_by: 'created_at',
    sort_order: 'desc'
});
var activeStatuses = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];
// Methods
var loadProducts = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getProducts(filters)];
            case 2:
                response = _a.sent();
                products.value = response.data.data;
                totalRecords.value = response.data.total;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load products', life: 3000 });
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
                return [4 /*yield*/, merchandising_service_1.default.getCategories({ active_only: true })];
            case 1:
                response = _a.sent();
                categories.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_2 = _a.sent();
                console.error('Failed to load categories');
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var loadTags = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getTags({ active_only: true })];
            case 1:
                response = _a.sent();
                tags.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_3 = _a.sent();
                console.error('Failed to load tags');
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var loadAttributes = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_4;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                return [4 /*yield*/, merchandising_service_1.default.getAttributes({ filterable_only: true })];
            case 1:
                response = _a.sent();
                attributes.value = response.data.data;
                return [3 /*break*/, 3];
            case 2:
                error_4 = _a.sent();
                console.error('Failed to load attributes');
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var onPage = function (event) {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
    loadProducts();
};
var onSort = function (event) {
    filters.sort_by = event.sortField;
    filters.sort_order = event.sortOrder === 1 ? 'asc' : 'desc';
    loadProducts();
};
var onFilterChange = function () {
    filters.page = 1;
    loadProducts();
};
var resetFilters = function () {
    filters.search = '';
    filters.category_id = null;
    filters.is_active = null;
    loadProducts();
};
// Add these methods to your script setup
var viewProduct = function (productId) {
    router.push({
        name: 'merchandising.products.view',
        params: { id: productId }
    });
};
var editProduct = function (productId) {
    router.push({
        name: 'merchandising.products.edit',
        params: { id: productId }
    });
};
var confirmDelete = function (product) {
    selectedProducts.value = product;
    deleteDialogVisible.value = true;
};
var deleteProduct = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_5;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteProduct(currentProduct.value.id)];
            case 2:
                _c.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Product deleted', life: 3000 });
                deleteDialogVisible.value = false;
                loadProducts();
                return [3 /*break*/, 5];
            case 3:
                error_5 = _c.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: ((_b = (_a = error_5.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete product', life: 3000 });
                return [3 /*break*/, 5];
            case 4:
                deleting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var bulkActivate = function () { return __awaiter(void 0, void 0, void 0, function () {
    var ids, error_6;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                ids = selectedProducts.value.map(function (p) { return p.id; });
                return [4 /*yield*/, merchandising_service_1.default.bulkStatusUpdate(ids, true)];
            case 1:
                _a.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Products activated', life: 3000 });
                selectedProducts.value = [];
                loadProducts();
                return [3 /*break*/, 3];
            case 2:
                error_6 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to activate products', life: 3000 });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var bulkDeactivate = function () { return __awaiter(void 0, void 0, void 0, function () {
    var ids, error_7;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                _a.trys.push([0, 2, , 3]);
                ids = selectedProducts.value.map(function (p) { return p.id; });
                return [4 /*yield*/, merchandising_service_1.default.bulkStatusUpdate(ids, false)];
            case 1:
                _a.sent();
                toast.add({ severity: 'success', summary: 'Success', detail: 'Products deactivated', life: 3000 });
                selectedProducts.value = [];
                loadProducts();
                return [3 /*break*/, 3];
            case 2:
                error_7 = _a.sent();
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to deactivate products', life: 3000 });
                return [3 /*break*/, 3];
            case 3: return [2 /*return*/];
        }
    });
}); };
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(price);
};
(0, vue_1.onMounted)(function () {
    loadProducts();
    loadCategories();
    loadTags();
    loadAttributes();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-6 bg-gray-50 min-h-screen" }));
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6 flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add Product", severity: "success" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add Product", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$router.push({ name: 'merchandising.products.create' });
            // @ts-ignore
            [$router,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ class: "mb-6" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = __VLS_17.slots.default;
    var __VLS_20 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ class: "pi pi-search" })));
    var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_25 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search products" }), { class: "w-full" })));
    var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search products" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
    var __VLS_30 = void 0;
    var __VLS_31 = ({ input: {} },
        { onInput: (__VLS_ctx.onFilterChange) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_28;
    var __VLS_29;
    // @ts-ignore
    [filters, onFilterChange,];
    var __VLS_17;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "All Categories" }), { class: "w-full" }), { showClear: true })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.category_id), options: (__VLS_ctx.categories), optionLabel: "category_name", optionValue: "id", placeholder: "All Categories" }), { class: "w-full" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ change: {} },
        { onChange: (__VLS_ctx.onFilterChange) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_35;
    var __VLS_36;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.activeStatuses), optionLabel: "label", optionValue: "value", placeholder: "Status" }), { class: "w-full" }), { showClear: true })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.activeStatuses), optionLabel: "label", optionValue: "value", placeholder: "Status" }), { class: "w-full" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ change: {} },
        { onChange: (__VLS_ctx.onFilterChange) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_42;
    var __VLS_43;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4 flex gap-2" }));
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    if (__VLS_ctx.selectedProducts.length > 0) {
        var __VLS_46 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onClick': {} }, { icon: "pi pi-check", label: "Activate", severity: "success", size: "small" })));
        var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", label: "Activate", severity: "success", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
        var __VLS_51 = void 0;
        var __VLS_52 = ({ click: {} },
            { onClick: (__VLS_ctx.bulkActivate) });
        var __VLS_49;
        var __VLS_50;
    }
    if (__VLS_ctx.selectedProducts.length > 0) {
        var __VLS_53 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ 'onClick': {} }, { icon: "pi pi-times", label: "Deactivate", severity: "warning", size: "small" })));
        var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", label: "Deactivate", severity: "warning", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_54), false));
        var __VLS_58 = void 0;
        var __VLS_59 = ({ click: {} },
            { onClick: (__VLS_ctx.bulkDeactivate) });
        var __VLS_56;
        var __VLS_57;
    }
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset", severity: "secondary", size: "small", outlined: true })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset", severity: "secondary", size: "small", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    var __VLS_66 = ({ click: {} },
        { onClick: (__VLS_ctx.resetFilters) });
    var __VLS_63;
    var __VLS_64;
    // @ts-ignore
    [filters, filters, onFilterChange, onFilterChange, categories, activeStatuses, selectedProducts, selectedProducts, bulkActivate, bulkDeactivate, resetFilters,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_67;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67({}));
var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_68), false));
var __VLS_72 = __VLS_70.slots.default;
{
    var __VLS_73 = __VLS_70.slots.content;
    var __VLS_74 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74(__assign(__assign(__assign(__assign({ 'onPage': {} }, { 'onSort': {} }), { selection: (__VLS_ctx.selectedProducts), value: (__VLS_ctx.products), loading: (__VLS_ctx.loading), paginator: true, rows: (15), totalRecords: (__VLS_ctx.totalRecords), lazy: (true), dataKey: "id", rowsPerPageOptions: ([15, 25, 50]), currentPageReportTemplate: "Showing {first} to {last} of {totalRecords}", paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown" }), { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ 'onPage': {} }, { 'onSort': {} }), { selection: (__VLS_ctx.selectedProducts), value: (__VLS_ctx.products), loading: (__VLS_ctx.loading), paginator: true, rows: (15), totalRecords: (__VLS_ctx.totalRecords), lazy: (true), dataKey: "id", rowsPerPageOptions: ([15, 25, 50]), currentPageReportTemplate: "Showing {first} to {last} of {totalRecords}", paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown" }), { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_75), false));
    var __VLS_79 = void 0;
    var __VLS_80 = ({ page: {} },
        { onPage: (__VLS_ctx.onPage) });
    var __VLS_81 = ({ sort: {} },
        { onSort: (__VLS_ctx.onSort) });
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_82 = __VLS_77.slots.default;
    {
        var __VLS_83 = __VLS_77.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-400" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-2" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        // @ts-ignore
        [selectedProducts, products, loading, totalRecords, onPage, onSort,];
    }
    var __VLS_84 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84({
        selectionMode: "multiple",
        headerStyle: "width: 3rem",
    }));
    var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([{
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
        }], __VLS_functionalComponentArgsRest(__VLS_85), false));
    var __VLS_89 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89({
        field: "sku",
        header: "SKU",
        sortable: true,
    }));
    var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([{
            field: "sku",
            header: "SKU",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_90), false));
    var __VLS_94 = __VLS_92.slots.default;
    {
        var __VLS_95 = __VLS_92.slots.body;
        var data = __VLS_vSlot(__VLS_95)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm" }));
        /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (data.sku);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_92;
    var __VLS_96 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96({
        field: "product_name",
        header: "Product Name",
        sortable: true,
    }));
    var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([{
            field: "product_name",
            header: "Product Name",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_97), false));
    var __VLS_101 = __VLS_99.slots.default;
    {
        var __VLS_102 = __VLS_99.slots.body;
        var data = __VLS_vSlot(__VLS_102)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
        if (data.thumbnail) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.img)(__assign({ src: (data.thumbnail), alt: "Product" }, { class: "w-12 h-12 rounded object-cover" }));
            /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            /** @type {__VLS_StyleScopedClasses['object-cover']} */ ;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 rounded bg-gray-200 flex items-center justify-center" }));
            /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
            /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-image text-gray-400" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-image']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (data.product_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (data.brand);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_99;
    var __VLS_103 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103({
        field: "category.category_name",
        header: "Category",
    }));
    var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([{
            field: "category.category_name",
            header: "Category",
        }], __VLS_functionalComponentArgsRest(__VLS_104), false));
    var __VLS_108 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108({
        field: "base_price",
        header: "Price",
        sortable: true,
    }));
    var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([{
            field: "base_price",
            header: "Price",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_109), false));
    var __VLS_113 = __VLS_111.slots.default;
    {
        var __VLS_114 = __VLS_111.slots.body;
        var data = __VLS_vSlot(__VLS_114)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatPrice(data.base_price));
        if (data.discounted_price) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-red-600 line-through" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['line-through']} */ ;
            (__VLS_ctx.formatPrice(data.discounted_price));
        }
        // @ts-ignore
        [formatPrice, formatPrice,];
    }
    // @ts-ignore
    [];
    var __VLS_111;
    var __VLS_115 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115({
        field: "is_active",
        header: "Status",
    }));
    var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([{
            field: "is_active",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_116), false));
    var __VLS_120 = __VLS_118.slots.default;
    {
        var __VLS_121 = __VLS_118.slots.body;
        var data = __VLS_vSlot(__VLS_121)[0].data;
        var __VLS_122 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122({
            value: (data.is_active ? 'Active' : 'Inactive'),
            severity: (data.is_active ? 'success' : 'secondary'),
        }));
        var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([{
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'secondary'),
            }], __VLS_functionalComponentArgsRest(__VLS_123), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_118;
    var __VLS_127 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127({
        header: "Variations",
    }));
    var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([{
            header: "Variations",
        }], __VLS_functionalComponentArgsRest(__VLS_128), false));
    var __VLS_132 = __VLS_130.slots.default;
    {
        var __VLS_133 = __VLS_130.slots.body;
        var data = __VLS_vSlot(__VLS_133)[0].data;
        var __VLS_134 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Badge} */
        Badge;
        // @ts-ignore
        var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134({
            value: (data.variations_count || 0),
            severity: "info",
        }));
        var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([{
                value: (data.variations_count || 0),
                severity: "info",
            }], __VLS_functionalComponentArgsRest(__VLS_135), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_130;
    var __VLS_139 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139({
        header: "Actions",
        frozen: (true),
        alignFrozen: "right",
    }));
    var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([{
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }], __VLS_functionalComponentArgsRest(__VLS_140), false));
    var __VLS_144 = __VLS_142.slots.default;
    {
        var __VLS_145 = __VLS_142.slots.body;
        var data_1 = __VLS_vSlot(__VLS_145)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_146 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })));
        var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_147), false));
        var __VLS_151 = void 0;
        var __VLS_152 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewProduct(data_1.id);
                    // @ts-ignore
                    [viewProduct,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('View Details') }), null, null);
        var __VLS_149;
        var __VLS_150;
        if (__VLS_ctx.authStore.hasPermission('merchandising.products.update')) {
            var __VLS_153 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })));
            var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_154), false));
            var __VLS_158 = void 0;
            var __VLS_159 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(__VLS_ctx.authStore.hasPermission('merchandising.products.update')))
                            return;
                        __VLS_ctx.editProduct(data_1.id);
                        // @ts-ignore
                        [vTooltip, authStore, editProduct,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_156;
            var __VLS_157;
        }
        if (__VLS_ctx.authStore.hasPermission('merchandising.products.delete')) {
            var __VLS_160 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })));
            var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_161), false));
            var __VLS_165 = void 0;
            var __VLS_166 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(__VLS_ctx.authStore.hasPermission('merchandising.products.delete')))
                            return;
                        __VLS_ctx.confirmDelete(data_1);
                        // @ts-ignore
                        [vTooltip, authStore, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            var __VLS_163;
            var __VLS_164;
        }
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_142;
    // @ts-ignore
    [];
    var __VLS_77;
    var __VLS_78;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_70;
var __VLS_167;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_168), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_172 = __VLS_170.slots.default;
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
    var __VLS_173 = __VLS_170.slots.footer;
    var __VLS_174 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_175 = __VLS_asFunctionalComponent1(__VLS_174, new __VLS_174(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_176 = __VLS_175.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_175), false));
    var __VLS_179 = void 0;
    var __VLS_180 = ({ click: {} },
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
    var __VLS_177;
    var __VLS_178;
    var __VLS_181 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_182 = __VLS_asFunctionalComponent1(__VLS_181, new __VLS_181(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_183 = __VLS_182.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_182), false));
    var __VLS_186 = void 0;
    var __VLS_187 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteProduct) });
    var __VLS_184;
    var __VLS_185;
    // @ts-ignore
    [deleting, deleteProduct,];
}
// @ts-ignore
[];
var __VLS_170;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
