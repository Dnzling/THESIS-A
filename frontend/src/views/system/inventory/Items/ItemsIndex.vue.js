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
var _a, _b, _c;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var axios_1 = require("axios");
var loading = (0, vue_1.ref)(false);
var items = (0, vue_1.ref)([]);
var totalRecords = (0, vue_1.ref)(0);
var toast = (0, usetoast_1.useToast)();
var filters = (0, vue_1.reactive)({
    search: '',
    stock_status: null,
    page: 1,
    per_page: 15
});
var stockStatuses = [
    { label: 'In Stock', value: 'in_stock' },
    { label: 'Low Stock', value: 'low_stock' },
    { label: 'Out of Stock', value: 'out_of_stock' }
];
var loadItems = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                loading.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                params = {
                    page: filters.page,
                    per_page: filters.per_page
                };
                if (filters.search)
                    params.search = filters.search;
                if (filters.stock_status)
                    params.stock_status = filters.stock_status;
                return [4 /*yield*/, axios_1.default.get('/api/inventory/items', { params: params })];
            case 2:
                response = _d.sent();
                if ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) {
                    items.value = response.data.data;
                    totalRecords.value = response.data.total || items.value.length;
                }
                else {
                    items.value = [];
                    totalRecords.value = 0;
                }
                return [3 /*break*/, 5];
            case 3:
                error_1 = _d.sent();
                console.error('Failed to load inventory', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_1.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to load inventory',
                    life: 3000
                });
                items.value = [];
                totalRecords.value = 0;
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var onPage = function (event) {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
    loadItems();
};
var resetFilters = function () {
    filters.search = '';
    filters.stock_status = null;
    filters.page = 1;
    filters.per_page = 15;
    loadItems();
};
var getStockLabel = function (item) {
    var qty = item.quantity_on_hand || 0;
    var reorder = item.reorder_point || 0;
    if (qty <= 0)
        return 'Out of Stock';
    if (qty <= reorder)
        return 'Low Stock';
    return 'In Stock';
};
var getStockSeverity = function (item) {
    var qty = item.quantity_on_hand || 0;
    var reorder = item.reorder_point || 0;
    if (qty <= 0)
        return 'danger';
    if (qty <= reorder)
        return 'warning';
    return 'success';
};
var viewDetails = function (item) {
    console.log('View item details:', item);
    // Can navigate to detail view if needed
};
var editItem = function (item) {
    console.log('Edit item:', item);
    router.push({ name: 'inventory.items.edit', params: { id: item.id } });
};
(0, vue_1.onMounted)(function () {
    loadItems();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 min-h-screen p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
/** @type {__VLS_StyleScopedClasses['min-h-screen']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
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
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ class: "mb-6" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
var __VLS_5 = __VLS_3.slots.default;
{
    var __VLS_6 = __VLS_3.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = __VLS_10.slots.default;
    var __VLS_13 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13(__assign({ class: "pi pi-search" })));
    var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_14), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_18 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search item name or SKU" }, { class: "w-full" })));
    var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search item name or SKU" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_19), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [filters,];
    var __VLS_10;
    var __VLS_23 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23(__assign(__assign({ modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatuses), optionLabel: "label", optionValue: "value", placeholder: "Stock Status" }, { class: "w-full" }), { showClear: true })));
    var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.filters.stock_status), options: (__VLS_ctx.stockStatuses), optionLabel: "label", optionValue: "value", placeholder: "Stock Status" }, { class: "w-full" }), { showClear: true })], __VLS_functionalComponentArgsRest(__VLS_24), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ 'onClick': {} }, { icon: "pi pi-search", label: "Search" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-search", label: "Search" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ click: {} },
        { onClick: (__VLS_ctx.loadItems) });
    var __VLS_31;
    var __VLS_32;
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset", severity: "secondary" })));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter-slash", label: "Reset", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = void 0;
    var __VLS_41 = ({ click: {} },
        { onClick: (__VLS_ctx.resetFilters) });
    var __VLS_38;
    var __VLS_39;
    // @ts-ignore
    [filters, stockStatuses, loadItems, resetFilters,];
}
// @ts-ignore
[];
var __VLS_3;
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42({}));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_43), false));
var __VLS_47 = __VLS_45.slots.default;
{
    var __VLS_48 = __VLS_45.slots.content;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.items), loading: (__VLS_ctx.loading), paginator: true, rows: (__VLS_ctx.filters.per_page), totalRecords: (__VLS_ctx.totalRecords), lazy: (true), dataKey: "id", rowsPerPageOptions: ([15, 25, 50]), currentPageReportTemplate: "Showing {first} to {last} of {totalRecords}", paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown" }), { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onPage': {} }, { value: (__VLS_ctx.items), loading: (__VLS_ctx.loading), paginator: true, rows: (__VLS_ctx.filters.per_page), totalRecords: (__VLS_ctx.totalRecords), lazy: (true), dataKey: "id", rowsPerPageOptions: ([15, 25, 50]), currentPageReportTemplate: "Showing {first} to {last} of {totalRecords}", paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown" }), { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    var __VLS_54 = void 0;
    var __VLS_55 = ({ page: {} },
        { onPage: (__VLS_ctx.onPage) });
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_56 = __VLS_52.slots.default;
    {
        var __VLS_57 = __VLS_52.slots.empty;
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
        [filters, items, loading, totalRecords, onPage,];
    }
    var __VLS_58 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ field: "product.sku", header: "SKU" }, { style: {} })));
    var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ field: "product.sku", header: "SKU" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_59), false));
    var __VLS_63 = __VLS_61.slots.default;
    {
        var __VLS_64 = __VLS_61.slots.body;
        var data = __VLS_vSlot(__VLS_64)[0].data;
        (((_a = data.product) === null || _a === void 0 ? void 0 : _a.sku) || 'N/A');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_61;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ field: "product.product_name", header: "Item Name" }, { style: {} })));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ field: "product.product_name", header: "Item Name" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = __VLS_68.slots.default;
    {
        var __VLS_71 = __VLS_68.slots.body;
        var data = __VLS_vSlot(__VLS_71)[0].data;
        (((_b = data.product) === null || _b === void 0 ? void 0 : _b.product_name) || 'N/A');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_68;
    var __VLS_72 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign({ field: "branch.name", header: "Branch" }, { style: {} })));
    var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign({ field: "branch.name", header: "Branch" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_73), false));
    var __VLS_77 = __VLS_75.slots.default;
    {
        var __VLS_78 = __VLS_75.slots.body;
        var data = __VLS_vSlot(__VLS_78)[0].data;
        (((_c = data.branch) === null || _c === void 0 ? void 0 : _c.name) || 'N/A');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_75;
    var __VLS_79 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ field: "quantity_on_hand", header: "On Hand" }, { style: {} })));
    var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ field: "quantity_on_hand", header: "On Hand" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_80), false));
    var __VLS_84 = __VLS_82.slots.default;
    {
        var __VLS_85 = __VLS_82.slots.body;
        var data = __VLS_vSlot(__VLS_85)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (data.quantity_on_hand);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_82;
    var __VLS_86 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_87 = __VLS_asFunctionalComponent1(__VLS_86, new __VLS_86(__assign({ field: "reorder_point", header: "Reorder Level" }, { style: {} })));
    var __VLS_88 = __VLS_87.apply(void 0, __spreadArray([__assign({ field: "reorder_point", header: "Reorder Level" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_87), false));
    var __VLS_91 = __VLS_89.slots.default;
    {
        var __VLS_92 = __VLS_89.slots.body;
        var data = __VLS_vSlot(__VLS_92)[0].data;
        (data.reorder_point);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_89;
    var __VLS_93 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93(__assign({ field: "status", header: "Status" }, { style: {} })));
    var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_94), false));
    var __VLS_98 = __VLS_96.slots.default;
    {
        var __VLS_99 = __VLS_96.slots.body;
        var data = __VLS_vSlot(__VLS_99)[0].data;
        var __VLS_100 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_101 = __VLS_asFunctionalComponent1(__VLS_100, new __VLS_100({
            value: (__VLS_ctx.getStockLabel(data)),
            severity: (__VLS_ctx.getStockSeverity(data)),
        }));
        var __VLS_102 = __VLS_101.apply(void 0, __spreadArray([{
                value: (__VLS_ctx.getStockLabel(data)),
                severity: (__VLS_ctx.getStockSeverity(data)),
            }], __VLS_functionalComponentArgsRest(__VLS_101), false));
        // @ts-ignore
        [getStockLabel, getStockSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_96;
    var __VLS_105 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_106), false));
    var __VLS_110 = __VLS_108.slots.default;
    {
        var __VLS_111 = __VLS_108.slots.body;
        var data_1 = __VLS_vSlot(__VLS_111)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_112 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_113 = __VLS_asFunctionalComponent1(__VLS_112, new __VLS_112(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, severity: "info" })));
        var __VLS_114 = __VLS_113.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_113), false));
        var __VLS_117 = void 0;
        var __VLS_118 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.viewDetails(data_1);
                    // @ts-ignore
                    [viewDetails,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('View details') }), null, null);
        var __VLS_115;
        var __VLS_116;
        var __VLS_119 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, severity: "warning" })));
        var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_120), false));
        var __VLS_124 = void 0;
        var __VLS_125 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editItem(data_1);
                    // @ts-ignore
                    [vTooltip, editItem,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Adjust stock') }), null, null);
        var __VLS_122;
        var __VLS_123;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_108;
    // @ts-ignore
    [];
    var __VLS_52;
    var __VLS_53;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_45;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
