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
var _a;
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var usetoast_1 = require("primevue/usetoast");
var auth_1 = require("../../../../stores/auth");
var merchandising_service_1 = require("../../../../services/merchandising.service");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var categories = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var dialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var editMode = (0, vue_1.ref)(false);
var isSubcategory = (0, vue_1.ref)(false);
var currentCategory = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var viewMode = (0, vue_1.ref)('list'); // 'tree' or 'list'
var filters = (0, vue_1.reactive)({
    is_active: null,
    parent_only: null
});
var formData = (0, vue_1.reactive)({
    category_code: '',
    category_name: '',
    description: '',
    parent_category_id: null,
    icon_path: '',
    is_active: true,
    display_order: 0
});
var errors = (0, vue_1.ref)({});
var statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];
var parentFilterOptions = [
    { label: 'Root Categories Only', value: 'root' },
    { label: 'Subcategories Only', value: 'sub' }
];
// Computed
var categoryTree = (0, vue_1.computed)(function () {
    return buildTree(categories.value.filter(function (c) { return !c.parent_category_id; }));
});
var parentCategoryOptions = (0, vue_1.computed)(function () {
    if (editMode.value && currentCategory.value) {
        return categories.value.filter(function (c) {
            return c.id !== currentCategory.value.id &&
                c.parent_category_id !== currentCategory.value.id;
        });
    }
    return categories.value.filter(function (c) { return !c.parent_category_id; });
});
// Methods
var buildTree = function (items, parentId) {
    if (parentId === void 0) { parentId = null; }
    return items.map(function (item) {
        var children = categories.value.filter(function (c) { return c.parent_category_id === item.id; });
        return {
            key: item.id,
            label: item.category_name,
            data: item,
            icon: item.icon_path,
            children: children.length > 0 ? buildTree(children, item.id) : undefined
        };
    });
};
var loadCategories = function () { return __awaiter(void 0, void 0, void 0, function () {
    var params, response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loading.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                params = {};
                if (filters.is_active !== null)
                    params.is_active = filters.is_active;
                if (filters.parent_only === 'root')
                    params.parent_only = true;
                if (filters.parent_only === 'sub')
                    params.has_parent = true;
                if (searchQuery.value)
                    params.search = searchQuery.value;
                return [4 /*yield*/, merchandising_service_1.default.getCategories(params)];
            case 2:
                response = _a.sent();
                categories.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load categories',
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
var onSearch = function () {
    loadCategories();
};
var openCreateDialog = function () {
    resetForm();
    editMode.value = false;
    isSubcategory.value = false;
    dialogVisible.value = true;
};
var addSubcategory = function (parent) {
    resetForm();
    formData.parent_category_id = parent.id;
    isSubcategory.value = true;
    editMode.value = false;
    dialogVisible.value = true;
};
var editCategory = function (category) {
    currentCategory.value = category;
    Object.assign(formData, {
        category_code: category.category_code,
        category_name: category.category_name,
        description: category.description || '',
        parent_category_id: category.parent_category_id,
        icon_path: category.icon_path || '',
        is_active: category.is_active,
        display_order: category.display_order || 0
    });
    editMode.value = true;
    isSubcategory.value = false;
    dialogVisible.value = true;
};
var saveCategory = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!validate())
                    return [2 /*return*/];
                saving.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 6, 7, 8]);
                if (!editMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateCategory(currentCategory.value.id, formData)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createCategory(formData)];
            case 4:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category created successfully',
                    life: 3000
                });
                _c.label = 5;
            case 5:
                dialogVisible.value = false;
                loadCategories();
                return [3 /*break*/, 8];
            case 6:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to save category',
                    life: 3000
                });
                return [3 /*break*/, 8];
            case 7:
                saving.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var confirmDelete = function (category) {
    currentCategory.value = category;
    deleteDialogVisible.value = true;
};
var deleteCategory = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteCategory(currentCategory.value.id)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category deleted successfully',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadCategories();
                return [3 /*break*/, 5];
            case 3:
                error_3 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_3.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete category',
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
var validate = function () {
    errors.value = {};
    if (!formData.category_code) {
        errors.value.category_code = 'Category code is required';
    }
    if (!formData.category_name) {
        errors.value.category_name = 'Category name is required';
    }
    return Object.keys(errors.value).length === 0;
};
var resetForm = function () {
    formData.category_code = '';
    formData.category_name = '';
    formData.description = '';
    formData.parent_category_id = null;
    formData.icon_path = '';
    formData.is_active = true;
    formData.display_order = 0;
    errors.value = {};
};
var openIconBrowser = function () {
    window.open('https://primevue.org/icons', '_blank');
};
var truncate = function (text, length) {
    return text && text.length > length ? text.substring(0, length) + '...' : text;
};
(0, vue_1.onMounted)(function () {
    loadCategories();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
/** @type {__VLS_StyleScopedClasses['p-tree-node-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Tree View", icon: "pi pi-sitemap", severity: (__VLS_ctx.viewMode === 'tree' ? 'primary' : 'secondary'), outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Tree View", icon: "pi pi-sitemap", severity: (__VLS_ctx.viewMode === 'tree' ? 'primary' : 'secondary'), outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.viewMode = 'tree';
            // @ts-ignore
            [viewMode, viewMode,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "List View", icon: "pi pi-list", severity: (__VLS_ctx.viewMode === 'list' ? 'primary' : 'secondary'), outlined: true })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "List View", icon: "pi pi-list", severity: (__VLS_ctx.viewMode === 'list' ? 'primary' : 'secondary'), outlined: true })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.viewMode = 'list';
            // @ts-ignore
            [viewMode, viewMode,];
        } });
var __VLS_10;
var __VLS_11;
if (__VLS_ctx.authStore.hasPermission('merchandising.categories.create')) {
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign({ 'onClick': {} }, { label: "Add Category", icon: "pi pi-plus" }), { class: "ml-auto" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Add Category", icon: "pi pi-plus" }), { class: "ml-auto" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreateDialog) });
    /** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
    var __VLS_17;
    var __VLS_18;
}
var __VLS_21;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21({}));
var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_22), false));
var __VLS_26 = __VLS_24.slots.default;
{
    var __VLS_27 = __VLS_24.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({}));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = __VLS_31.slots.default;
    var __VLS_34 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34(__assign({ class: "pi pi-search" })));
    var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_35), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search categories..." }), { class: "w-full" })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search categories..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_42;
    var __VLS_43;
    // @ts-ignore
    [authStore, openCreateDialog, searchQuery, onSearch,];
    var __VLS_31;
    var __VLS_46 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })));
    var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_47), false));
    var __VLS_51 = void 0;
    var __VLS_52 = ({ change: {} },
        { onChange: (__VLS_ctx.loadCategories) });
    var __VLS_49;
    var __VLS_50;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.parent_only), options: (__VLS_ctx.parentFilterOptions), optionLabel: "label", optionValue: "value", placeholder: "All Categories", showClear: true })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.parent_only), options: (__VLS_ctx.parentFilterOptions), optionLabel: "label", optionValue: "value", placeholder: "All Categories", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    var __VLS_58 = void 0;
    var __VLS_59 = ({ change: {} },
        { onChange: (__VLS_ctx.loadCategories) });
    var __VLS_56;
    var __VLS_57;
    // @ts-ignore
    [filters, filters, statusOptions, loadCategories, loadCategories, parentFilterOptions,];
}
// @ts-ignore
[];
var __VLS_24;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _b = __VLS_vFor((5)); _i < _b.length; _i++) {
        var i = _b[_i][0];
        var __VLS_60 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })));
        var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading) {
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({}));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = __VLS_68.slots.default;
    {
        var __VLS_71 = __VLS_68.slots.content;
        var __VLS_72 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tree | typeof __VLS_components.Tree} */
        Tree;
        // @ts-ignore
        var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign(__assign({ value: (__VLS_ctx.categoryTree) }, { class: "w-full" }), { pt: ({
                root: { class: 'border-none' }
            }) })));
        var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.categoryTree) }, { class: "w-full" }), { pt: ({
                    root: { class: 'border-none' }
                }) })], __VLS_functionalComponentArgsRest(__VLS_73), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_77 = __VLS_75.slots.default;
        {
            var __VLS_78 = __VLS_75.slots.default;
            var node_1 = __VLS_vSlot(__VLS_78)[0].node;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between w-full p-2 hover:bg-gray-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
            if (node_1.data.icon_path) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (node_1.data.icon_path) }, { class: "text-xl text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (node_1.label);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-mono text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            (node_1.data.category_code);
            var __VLS_79 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            Badge;
            // @ts-ignore
            var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79({
                value: ("".concat(node_1.data.products_count || 0, " products")),
                severity: "info",
                size: "small",
            }));
            var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([{
                    value: ("".concat(node_1.data.products_count || 0, " products")),
                    severity: "info",
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_80), false));
            if (node_1.data.is_active) {
                var __VLS_84 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                Tag;
                // @ts-ignore
                var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84({
                    value: "Active",
                    severity: "success",
                    size: "small",
                }));
                var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([{
                        value: "Active",
                        severity: "success",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_85), false));
            }
            else {
                var __VLS_89 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                Tag;
                // @ts-ignore
                var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89({
                    value: "Inactive",
                    severity: "secondary",
                    size: "small",
                }));
                var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([{
                        value: "Inactive",
                        severity: "secondary",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_90), false));
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_94 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94(__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "info", text: true, rounded: true, size: "small" })));
            var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "info", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_95), false));
            var __VLS_99 = void 0;
            var __VLS_100 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.addSubcategory(node_1.data);
                        // @ts-ignore
                        [viewMode, loading, categoryTree, addSubcategory,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Add Subcategory') }), null, null);
            var __VLS_97;
            var __VLS_98;
            var __VLS_101 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })));
            var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_102), false));
            var __VLS_106 = void 0;
            var __VLS_107 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.editCategory(node_1.data);
                        // @ts-ignore
                        [vTooltip, editCategory,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_104;
            var __VLS_105;
            var __VLS_108 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
            var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_109), false));
            var __VLS_113 = void 0;
            var __VLS_114 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.confirmDelete(node_1.data);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            var __VLS_111;
            var __VLS_112;
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_75;
        if (__VLS_ctx.categoryTree.length === 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-folder-open text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-folder-open']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            var __VLS_115 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_116 = __VLS_asFunctionalComponent1(__VLS_115, new __VLS_115(__assign(__assign({ 'onClick': {} }, { label: "Create Your First Category", icon: "pi pi-plus" }), { class: "mt-4" })));
            var __VLS_117 = __VLS_116.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create Your First Category", icon: "pi pi-plus" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_116), false));
            var __VLS_120 = void 0;
            var __VLS_121 = ({ click: {} },
                { onClick: (__VLS_ctx.openCreateDialog) });
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            var __VLS_118;
            var __VLS_119;
        }
        // @ts-ignore
        [openCreateDialog, categoryTree,];
    }
    // @ts-ignore
    [];
    var __VLS_68;
}
else if (__VLS_ctx.viewMode === 'list' && !__VLS_ctx.loading) {
    var __VLS_122 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_123 = __VLS_asFunctionalComponent1(__VLS_122, new __VLS_122({}));
    var __VLS_124 = __VLS_123.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_123), false));
    var __VLS_127 = __VLS_125.slots.default;
    {
        var __VLS_128 = __VLS_125.slots.content;
        var __VLS_129 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129(__assign({ value: (__VLS_ctx.categories), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" })));
        var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.categories), paginator: (true), rows: (15), rowsPerPageOptions: ([15, 25, 50]), dataKey: "id", stripedRows: true }, { class: "p-datatable-sm" })], __VLS_functionalComponentArgsRest(__VLS_130), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_134 = __VLS_132.slots.default;
        {
            var __VLS_135 = __VLS_132.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-folder-open text-6xl text-gray-300" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-folder-open']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-4" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
            // @ts-ignore
            [viewMode, loading, categories,];
        }
        var __VLS_136 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136({
            field: "category_code",
            header: "Code",
            sortable: true,
        }));
        var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([{
                field: "category_code",
                header: "Code",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_137), false));
        var __VLS_141 = __VLS_139.slots.default;
        {
            var __VLS_142 = __VLS_139.slots.body;
            var data = __VLS_vSlot(__VLS_142)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-sm font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (data.category_code);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_139;
        var __VLS_143 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143({
            field: "category_name",
            header: "Category Name",
            sortable: true,
        }));
        var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([{
                field: "category_name",
                header: "Category Name",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_144), false));
        var __VLS_148 = __VLS_146.slots.default;
        {
            var __VLS_149 = __VLS_146.slots.body;
            var data = __VLS_vSlot(__VLS_149)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            if (data.icon_path) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (data.icon_path) }, { class: "text-lg text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            }
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (data.category_name);
            if (data.description) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                (__VLS_ctx.truncate(data.description, 50));
            }
            // @ts-ignore
            [truncate,];
        }
        // @ts-ignore
        [];
        var __VLS_146;
        var __VLS_150 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150({
            field: "parent.category_name",
            header: "Parent Category",
        }));
        var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([{
                field: "parent.category_name",
                header: "Parent Category",
            }], __VLS_functionalComponentArgsRest(__VLS_151), false));
        var __VLS_155 = __VLS_153.slots.default;
        {
            var __VLS_156 = __VLS_153.slots.body;
            var data = __VLS_vSlot(__VLS_156)[0].data;
            if (data.parent) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-700" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                (data.parent.category_name);
            }
            else {
                var __VLS_157 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                Tag;
                // @ts-ignore
                var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157({
                    value: "Root Category",
                    severity: "info",
                    size: "small",
                }));
                var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([{
                        value: "Root Category",
                        severity: "info",
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_158), false));
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_153;
        var __VLS_162 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_163 = __VLS_asFunctionalComponent1(__VLS_162, new __VLS_162({
            field: "products_count",
            header: "Products",
            sortable: true,
        }));
        var __VLS_164 = __VLS_163.apply(void 0, __spreadArray([{
                field: "products_count",
                header: "Products",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_163), false));
        var __VLS_167 = __VLS_165.slots.default;
        {
            var __VLS_168 = __VLS_165.slots.body;
            var data = __VLS_vSlot(__VLS_168)[0].data;
            var __VLS_169 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            Badge;
            // @ts-ignore
            var __VLS_170 = __VLS_asFunctionalComponent1(__VLS_169, new __VLS_169({
                value: (data.products_count || 0),
                severity: "info",
            }));
            var __VLS_171 = __VLS_170.apply(void 0, __spreadArray([{
                    value: (data.products_count || 0),
                    severity: "info",
                }], __VLS_functionalComponentArgsRest(__VLS_170), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_165;
        var __VLS_174 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_175 = __VLS_asFunctionalComponent1(__VLS_174, new __VLS_174({
            field: "display_order",
            header: "Order",
            sortable: true,
        }));
        var __VLS_176 = __VLS_175.apply(void 0, __spreadArray([{
                field: "display_order",
                header: "Order",
                sortable: true,
            }], __VLS_functionalComponentArgsRest(__VLS_175), false));
        var __VLS_179 = __VLS_177.slots.default;
        {
            var __VLS_180 = __VLS_177.slots.body;
            var data = __VLS_vSlot(__VLS_180)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            (data.display_order);
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_177;
        var __VLS_181 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_182 = __VLS_asFunctionalComponent1(__VLS_181, new __VLS_181({
            field: "is_active",
            header: "Status",
        }));
        var __VLS_183 = __VLS_182.apply(void 0, __spreadArray([{
                field: "is_active",
                header: "Status",
            }], __VLS_functionalComponentArgsRest(__VLS_182), false));
        var __VLS_186 = __VLS_184.slots.default;
        {
            var __VLS_187 = __VLS_184.slots.body;
            var data = __VLS_vSlot(__VLS_187)[0].data;
            var __VLS_188 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            Tag;
            // @ts-ignore
            var __VLS_189 = __VLS_asFunctionalComponent1(__VLS_188, new __VLS_188({
                value: (data.is_active ? 'Active' : 'Inactive'),
                severity: (data.is_active ? 'success' : 'secondary'),
            }));
            var __VLS_190 = __VLS_189.apply(void 0, __spreadArray([{
                    value: (data.is_active ? 'Active' : 'Inactive'),
                    severity: (data.is_active ? 'success' : 'secondary'),
                }], __VLS_functionalComponentArgsRest(__VLS_189), false));
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_184;
        var __VLS_193 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
        Column;
        // @ts-ignore
        var __VLS_194 = __VLS_asFunctionalComponent1(__VLS_193, new __VLS_193({
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }));
        var __VLS_195 = __VLS_194.apply(void 0, __spreadArray([{
                header: "Actions",
                frozen: (true),
                alignFrozen: "right",
            }], __VLS_functionalComponentArgsRest(__VLS_194), false));
        var __VLS_198 = __VLS_196.slots.default;
        {
            var __VLS_199 = __VLS_196.slots.body;
            var data_1 = __VLS_vSlot(__VLS_199)[0].data;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_200 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_201 = __VLS_asFunctionalComponent1(__VLS_200, new __VLS_200(__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "info", text: true, rounded: true })));
            var __VLS_202 = __VLS_201.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-plus", severity: "info", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_201), false));
            var __VLS_205 = void 0;
            var __VLS_206 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'list' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.addSubcategory(data_1);
                        // @ts-ignore
                        [addSubcategory,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Add Subcategory') }), null, null);
            var __VLS_203;
            var __VLS_204;
            var __VLS_207 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_208 = __VLS_asFunctionalComponent1(__VLS_207, new __VLS_207(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })));
            var __VLS_209 = __VLS_208.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_208), false));
            var __VLS_212 = void 0;
            var __VLS_213 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'list' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.editCategory(data_1);
                        // @ts-ignore
                        [vTooltip, editCategory,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_210;
            var __VLS_211;
            var __VLS_214 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            Button;
            // @ts-ignore
            var __VLS_215 = __VLS_asFunctionalComponent1(__VLS_214, new __VLS_214(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, disabled: (data_1.products_count > 0) })));
            var __VLS_216 = __VLS_215.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, disabled: (data_1.products_count > 0) })], __VLS_functionalComponentArgsRest(__VLS_215), false));
            var __VLS_219 = void 0;
            var __VLS_220 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.viewMode === 'tree' && !__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.viewMode === 'list' && !__VLS_ctx.loading))
                            return;
                        __VLS_ctx.confirmDelete(data_1);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            var __VLS_217;
            var __VLS_218;
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        var __VLS_196;
        // @ts-ignore
        [];
        var __VLS_132;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_125;
}
var __VLS_221;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_222 = __VLS_asFunctionalComponent1(__VLS_221, new __VLS_221(__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Category' : (__VLS_ctx.isSubcategory ? 'Add Subcategory' : 'Add Category')), modal: (true) }, { class: "w-full max-w-2xl" })));
var __VLS_223 = __VLS_222.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Category' : (__VLS_ctx.isSubcategory ? 'Add Subcategory' : 'Add Category')), modal: (true) }, { class: "w-full max-w-2xl" })], __VLS_functionalComponentArgsRest(__VLS_222), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-2xl']} */ ;
var __VLS_226 = __VLS_224.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "category_code" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_227;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_228 = __VLS_asFunctionalComponent1(__VLS_227, new __VLS_227(__assign({ id: "category_code", modelValue: (__VLS_ctx.formData.category_code), placeholder: "e.g., SOFA, CHAIR, TABLE" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_code }) })));
var __VLS_229 = __VLS_228.apply(void 0, __spreadArray([__assign({ id: "category_code", modelValue: (__VLS_ctx.formData.category_code), placeholder: "e.g., SOFA, CHAIR, TABLE" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_code }) })], __VLS_functionalComponentArgsRest(__VLS_228), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.category_code) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.category_code);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "category_name" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_232;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_233 = __VLS_asFunctionalComponent1(__VLS_232, new __VLS_232(__assign({ id: "category_name", modelValue: (__VLS_ctx.formData.category_name), placeholder: "e.g., Sofas & Couches" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_name }) })));
var __VLS_234 = __VLS_233.apply(void 0, __spreadArray([__assign({ id: "category_name", modelValue: (__VLS_ctx.formData.category_name), placeholder: "e.g., Sofas & Couches" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_name }) })], __VLS_functionalComponentArgsRest(__VLS_233), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.category_name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.category_name);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "parent_category_id" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_237;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_238 = __VLS_asFunctionalComponent1(__VLS_237, new __VLS_237({
    id: "parent_category_id",
    modelValue: (__VLS_ctx.formData.parent_category_id),
    options: (__VLS_ctx.parentCategoryOptions),
    optionLabel: "category_name",
    optionValue: "id",
    placeholder: "None (Root Category)",
    showClear: true,
}));
var __VLS_239 = __VLS_238.apply(void 0, __spreadArray([{
        id: "parent_category_id",
        modelValue: (__VLS_ctx.formData.parent_category_id),
        options: (__VLS_ctx.parentCategoryOptions),
        optionLabel: "category_name",
        optionValue: "id",
        placeholder: "None (Root Category)",
        showClear: true,
    }], __VLS_functionalComponentArgsRest(__VLS_238), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "description" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_242;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_243 = __VLS_asFunctionalComponent1(__VLS_242, new __VLS_242({
    id: "description",
    modelValue: (__VLS_ctx.formData.description),
    rows: "3",
    placeholder: "Category description...",
}));
var __VLS_244 = __VLS_243.apply(void 0, __spreadArray([{
        id: "description",
        modelValue: (__VLS_ctx.formData.description),
        rows: "3",
        placeholder: "Category description...",
    }], __VLS_functionalComponentArgsRest(__VLS_243), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "icon_path" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_247;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247(__assign({ id: "icon_path", modelValue: (__VLS_ctx.formData.icon_path), placeholder: "e.g., pi pi-box, pi pi-home" }, { class: "flex-1" })));
var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([__assign({ id: "icon_path", modelValue: (__VLS_ctx.formData.icon_path), placeholder: "e.g., pi pi-box, pi pi-home" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_248), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
var __VLS_252;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_253 = __VLS_asFunctionalComponent1(__VLS_252, new __VLS_252(__assign({ 'onClick': {} }, { label: "Browse Icons", icon: "pi pi-external-link", outlined: true })));
var __VLS_254 = __VLS_253.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Browse Icons", icon: "pi pi-external-link", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_253), false));
var __VLS_257;
var __VLS_258 = ({ click: {} },
    { onClick: (__VLS_ctx.openIconBrowser) });
var __VLS_255;
var __VLS_256;
if (__VLS_ctx.formData.icon_path) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.formData.icon_path) }, { class: "text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "https://primevue.org/icons", target: "_blank" }, { class: "text-blue-600" }));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "display_order" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_259;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
InputNumber;
// @ts-ignore
var __VLS_260 = __VLS_asFunctionalComponent1(__VLS_259, new __VLS_259({
    id: "display_order",
    modelValue: (__VLS_ctx.formData.display_order),
    min: (0),
    showButtons: true,
}));
var __VLS_261 = __VLS_260.apply(void 0, __spreadArray([{
        id: "display_order",
        modelValue: (__VLS_ctx.formData.display_order),
        min: (0),
        showButtons: true,
    }], __VLS_functionalComponentArgsRest(__VLS_260), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_264;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
Checkbox;
// @ts-ignore
var __VLS_265 = __VLS_asFunctionalComponent1(__VLS_264, new __VLS_264({
    modelValue: (__VLS_ctx.formData.is_active),
    inputId: "is_active",
    binary: (true),
}));
var __VLS_266 = __VLS_265.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.formData.is_active),
        inputId: "is_active",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_265), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_active" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
{
    var __VLS_269 = __VLS_224.slots.footer;
    var __VLS_270 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_271 = __VLS_asFunctionalComponent1(__VLS_270, new __VLS_270(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_272 = __VLS_271.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_271), false));
    var __VLS_275 = void 0;
    var __VLS_276 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.dialogVisible = false;
                // @ts-ignore
                [dialogVisible, dialogVisible, editMode, isSubcategory, formData, formData, formData, formData, formData, formData, formData, formData, formData, errors, errors, errors, errors, errors, errors, parentCategoryOptions, openIconBrowser,];
            } });
    var __VLS_273;
    var __VLS_274;
    var __VLS_277 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_278 = __VLS_asFunctionalComponent1(__VLS_277, new __VLS_277(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })));
    var __VLS_279 = __VLS_278.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_278), false));
    var __VLS_282 = void 0;
    var __VLS_283 = ({ click: {} },
        { onClick: (__VLS_ctx.saveCategory) });
    var __VLS_280;
    var __VLS_281;
    // @ts-ignore
    [editMode, saving, saveCategory,];
}
// @ts-ignore
[];
var __VLS_224;
var __VLS_284;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_285 = __VLS_asFunctionalComponent1(__VLS_284, new __VLS_284(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_286 = __VLS_285.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_285), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_289 = __VLS_287.slots.default;
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
if (((_a = __VLS_ctx.currentCategory) === null || _a === void 0 ? void 0 : _a.products_count) > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-red-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.currentCategory.products_count);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_290 = __VLS_287.slots.footer;
    var __VLS_291 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_292 = __VLS_asFunctionalComponent1(__VLS_291, new __VLS_291(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_293 = __VLS_292.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_292), false));
    var __VLS_296 = void 0;
    var __VLS_297 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteDialogVisible = false;
                // @ts-ignore
                [deleteDialogVisible, deleteDialogVisible, currentCategory, currentCategory,];
            } });
    var __VLS_294;
    var __VLS_295;
    var __VLS_298 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_299 = __VLS_asFunctionalComponent1(__VLS_298, new __VLS_298(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_300 = __VLS_299.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_299), false));
    var __VLS_303 = void 0;
    var __VLS_304 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteCategory) });
    var __VLS_301;
    var __VLS_302;
    // @ts-ignore
    [deleting, deleteCategory,];
}
// @ts-ignore
[];
var __VLS_287;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
