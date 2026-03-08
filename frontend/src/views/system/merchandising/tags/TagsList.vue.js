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
var TagForm_vue_1 = require("./TagForm.vue");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var select_1 = require("primevue/select");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var tag_1 = require("primevue/tag");
var paginator_1 = require("primevue/paginator");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var tags = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var formDialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var editMode = (0, vue_1.ref)(false);
var currentTag = (0, vue_1.ref)(null);
var currentTagId = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var totalRecords = (0, vue_1.ref)(0);
var tagFormRef = (0, vue_1.ref)(null);
var filters = (0, vue_1.reactive)({
    tag_type: null,
    is_active: null,
    page: 1,
    per_page: 15
});
var tagTypes = ['Style', 'Room', 'Promotion', 'Feature'];
var statusOptions = [
    { label: 'Active', value: true },
    { label: 'Inactive', value: false }
];
// Methods
var loadTags = function () { return __awaiter(void 0, void 0, void 0, function () {
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
                return [4 /*yield*/, merchandising_service_1.default.getTags(params)];
            case 2:
                response = _a.sent();
                tags.value = response.data.data;
                totalRecords.value = response.data.total || tags.value.length;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load tags',
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
    filters.page = 1;
    loadTags();
};
var onPage = function (event) {
    filters.page = event.page + 1;
    loadTags();
};
var openCreateDialog = function () {
    currentTagId.value = null;
    editMode.value = false;
    formDialogVisible.value = true;
};
var openEditDialog = function (tag) {
    currentTagId.value = tag.id;
    editMode.value = true;
    formDialogVisible.value = true;
};
var submitForm = function () { return __awaiter(void 0, void 0, void 0, function () {
    var success;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!tagFormRef.value)
                    return [2 /*return*/];
                saving.value = true;
                return [4 /*yield*/, tagFormRef.value.save()];
            case 1:
                success = _a.sent();
                saving.value = false;
                if (success) {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: editMode.value ? 'Tag updated successfully' : 'Tag created successfully',
                        life: 3000
                    });
                    formDialogVisible.value = false;
                    loadTags();
                }
                return [2 /*return*/];
        }
    });
}); };
var handleSave = function () {
    // This is called from the child component if needed
    formDialogVisible.value = false;
    loadTags();
};
var confirmDelete = function (tag) {
    currentTag.value = tag;
    deleteDialogVisible.value = true;
};
var deleteTag = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                deleting.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.deleteTag(currentTag.value.id)];
            case 2:
                _c.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Tag deleted successfully',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadTags();
                return [3 /*break*/, 5];
            case 3:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to delete tag',
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
var getTagTypeSeverity = function (type) {
    var severities = {
        'Style': 'info',
        'Room': 'success',
        'Promotion': 'danger',
        'Feature': 'warning'
    };
    return severities[type] || 'secondary';
};
(0, vue_1.onMounted)(function () {
    loadTags();
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
if (__VLS_ctx.authStore.hasPermission('merchandising.tags.create')) {
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Add Tag", icon: "pi pi-plus" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Tag", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreateDialog) });
    var __VLS_3;
    var __VLS_4;
}
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
card_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = __VLS_17.slots.default;
    var __VLS_20 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ class: "pi pi-search" })));
    var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_21), false));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_25 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search tags..." }), { class: "w-full" })));
    var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search tags..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
    var __VLS_30 = void 0;
    var __VLS_31 = ({ input: {} },
        { onInput: (__VLS_ctx.onSearch) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_28;
    var __VLS_29;
    // @ts-ignore
    [authStore, openCreateDialog, searchQuery, onSearch,];
    var __VLS_17;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.tag_type), options: (__VLS_ctx.tagTypes), placeholder: "All Types", showClear: true })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.tag_type), options: (__VLS_ctx.tagTypes), placeholder: "All Types", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ change: {} },
        { onChange: (__VLS_ctx.loadTags) });
    var __VLS_35;
    var __VLS_36;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.is_active), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ change: {} },
        { onChange: (__VLS_ctx.loadTags) });
    var __VLS_42;
    var __VLS_43;
    // @ts-ignore
    [filters, filters, tagTypes, loadTags, loadTags, statusOptions,];
}
// @ts-ignore
[];
var __VLS_10;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _b = __VLS_vFor((5)); _i < _b.length; _i++) {
        var i = _b[_i][0];
        var __VLS_46 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })));
        var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ key: (i), height: "80px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_47), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.tags.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['xl:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    var _loop_1 = function (tag) {
        var __VLS_51 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ key: (tag.id) }, { class: "hover:shadow-lg transition-shadow" })));
        var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ key: (tag.id) }, { class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
        /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
        var __VLS_56 = __VLS_54.slots.default;
        {
            var __VLS_57 = __VLS_54.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
            /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-bold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (tag.tag_name);
            var __VLS_58 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ value: (tag.tag_type), severity: (__VLS_ctx.getTagTypeSeverity(tag.tag_type)), size: "small" }, { class: "mt-2" })));
            var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ value: (tag.tag_type), severity: (__VLS_ctx.getTagTypeSeverity(tag.tag_type)), size: "small" }, { class: "mt-2" })], __VLS_functionalComponentArgsRest(__VLS_59), false));
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_63 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })));
            var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_64), false));
            var __VLS_68 = void 0;
            var __VLS_69 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.tags.length > 0))
                            return;
                        __VLS_ctx.openEditDialog(tag);
                        // @ts-ignore
                        [tags, tags, getTagTypeSeverity, openEditDialog,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_70 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })));
            var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_71), false));
            var __VLS_75 = void 0;
            var __VLS_76 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.tags.length > 0))
                            return;
                        __VLS_ctx.confirmDelete(tag);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between pt-3 border-t border-gray-200" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            /** @type {__VLS_StyleScopedClasses['pt-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            (tag.products_count || 0);
            var __VLS_77 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77({
                value: (tag.is_active ? 'Active' : 'Inactive'),
                severity: (tag.is_active ? 'success' : 'secondary'),
                size: "small",
            }));
            var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([{
                    value: (tag.is_active ? 'Active' : 'Inactive'),
                    severity: (tag.is_active ? 'success' : 'secondary'),
                    size: "small",
                }], __VLS_functionalComponentArgsRest(__VLS_78), false));
            if (tag.color_hex) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ style: ({ backgroundColor: tag.color_hex }) }, { class: "w-8 h-8 rounded border-2 border-gray-300" }));
                /** @type {__VLS_StyleScopedClasses['w-8']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-8']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs font-mono text-gray-600" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                (tag.color_hex);
            }
            // @ts-ignore
            [vTooltip,];
        }
        // @ts-ignore
        [];
        // @ts-ignore
        [];
    };
    var __VLS_66, __VLS_67, __VLS_73, __VLS_74, __VLS_54;
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.tags)); _c < _d.length; _c++) {
        var tag = _d[_c][0];
        _loop_1(tag);
    }
}
else {
    var __VLS_82 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82({}));
    var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_83), false));
    var __VLS_87 = __VLS_85.slots.default;
    {
        var __VLS_88 = __VLS_85.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-tags text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-tags']} */ ;
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
        var __VLS_89 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89(__assign(__assign({ 'onClick': {} }, { label: "Create Your First Tag", icon: "pi pi-plus" }), { class: "mt-4" })));
        var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create Your First Tag", icon: "pi pi-plus" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_90), false));
        var __VLS_94 = void 0;
        var __VLS_95 = ({ click: {} },
            { onClick: (__VLS_ctx.openCreateDialog) });
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        var __VLS_92;
        var __VLS_93;
        // @ts-ignore
        [openCreateDialog,];
    }
    // @ts-ignore
    [];
    var __VLS_85;
}
if (__VLS_ctx.totalRecords > 15) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    var __VLS_96 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Paginator} */
    paginator_1.default;
    // @ts-ignore
    var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96(__assign({ 'onPage': {} }, { rows: (15), totalRecords: (__VLS_ctx.totalRecords) })));
    var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([__assign({ 'onPage': {} }, { rows: (15), totalRecords: (__VLS_ctx.totalRecords) })], __VLS_functionalComponentArgsRest(__VLS_97), false));
    var __VLS_101 = void 0;
    var __VLS_102 = ({ page: {} },
        { onPage: (__VLS_ctx.onPage) });
    var __VLS_99;
    var __VLS_100;
}
var __VLS_103;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ visible: (__VLS_ctx.formDialogVisible), header: (__VLS_ctx.editMode ? 'Edit Tag' : 'Create Tag'), modal: (true) }, { class: "w-full max-w-lg" })));
var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.formDialogVisible), header: (__VLS_ctx.editMode ? 'Edit Tag' : 'Create Tag'), modal: (true) }, { class: "w-full max-w-lg" })], __VLS_functionalComponentArgsRest(__VLS_104), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-lg']} */ ;
var __VLS_108 = __VLS_106.slots.default;
var __VLS_109 = TagForm_vue_1.default;
// @ts-ignore
var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign(__assign({ 'onSave': {} }, { 'onCancel': {} }), { ref: "tagFormRef", tagId: (__VLS_ctx.currentTagId) })));
var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign(__assign({ 'onSave': {} }, { 'onCancel': {} }), { ref: "tagFormRef", tagId: (__VLS_ctx.currentTagId) })], __VLS_functionalComponentArgsRest(__VLS_110), false));
var __VLS_114;
var __VLS_115 = ({ save: {} },
    { onSave: (__VLS_ctx.handleSave) });
var __VLS_116 = ({ cancel: {} },
    { onCancel: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.formDialogVisible = false;
            // @ts-ignore
            [totalRecords, totalRecords, onPage, formDialogVisible, formDialogVisible, editMode, currentTagId, handleSave,];
        } });
var __VLS_117 = {};
var __VLS_112;
var __VLS_113;
{
    var __VLS_119 = __VLS_106.slots.footer;
    var __VLS_120 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_121), false));
    var __VLS_125 = void 0;
    var __VLS_126 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.formDialogVisible = false;
                // @ts-ignore
                [formDialogVisible,];
            } });
    var __VLS_123;
    var __VLS_124;
    var __VLS_127 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })));
    var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_128), false));
    var __VLS_132 = void 0;
    var __VLS_133 = ({ click: {} },
        { onClick: (__VLS_ctx.submitForm) });
    var __VLS_130;
    var __VLS_131;
    // @ts-ignore
    [editMode, saving, submitForm,];
}
// @ts-ignore
[];
var __VLS_106;
var __VLS_134;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_135), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_139 = __VLS_137.slots.default;
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
if (((_a = __VLS_ctx.currentTag) === null || _a === void 0 ? void 0 : _a.products_count) > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-orange-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.currentTag.products_count);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
{
    var __VLS_140 = __VLS_137.slots.footer;
    var __VLS_141 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_142 = __VLS_asFunctionalComponent1(__VLS_141, new __VLS_141(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_143 = __VLS_142.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_142), false));
    var __VLS_146 = void 0;
    var __VLS_147 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.deleteDialogVisible = false;
                // @ts-ignore
                [deleteDialogVisible, deleteDialogVisible, currentTag, currentTag,];
            } });
    var __VLS_144;
    var __VLS_145;
    var __VLS_148 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_149), false));
    var __VLS_153 = void 0;
    var __VLS_154 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteTag) });
    var __VLS_151;
    var __VLS_152;
    // @ts-ignore
    [deleting, deleteTag,];
}
// @ts-ignore
[];
var __VLS_137;
// @ts-ignore
var __VLS_118 = __VLS_117;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
