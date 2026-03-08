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
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var checkbox_1 = require("primevue/checkbox");
var skeleton_1 = require("primevue/skeleton");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var isEditMode = (0, vue_1.computed)(function () { return !!route.params.id; });
var submitting = (0, vue_1.ref)(false);
var loadingData = (0, vue_1.ref)(false);
var loadingCategories = (0, vue_1.ref)(false);
var categories = (0, vue_1.ref)([]);
var form = (0, vue_1.reactive)({
    category_code: '',
    category_name: '',
    description: '',
    parent_category_id: null,
    icon_path: '',
    is_active: true,
    display_order: 0
});
var errors = (0, vue_1.ref)({});
var loadCategories = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingCategories.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getCategories()];
            case 2:
                response = _a.sent();
                categories.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load categories:', error_1);
                return [3 /*break*/, 5];
            case 4:
                loadingCategories.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadCategory = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, category, error_2;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!isEditMode.value)
                    return [2 /*return*/];
                loadingData.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getCategory(Number(route.params.id))];
            case 2:
                response = _c.sent();
                category = response.data;
                Object.assign(form, {
                    category_code: category.category_code,
                    category_name: category.category_name,
                    description: category.description || '',
                    parent_category_id: category.parent_category_id,
                    icon_path: category.icon_path || '',
                    is_active: category.is_active,
                    display_order: category.display_order || 0
                });
                return [3 /*break*/, 5];
            case 3:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load category',
                    life: 5000
                });
                router.push({ name: 'merchandising.categories' });
                return [3 /*break*/, 5];
            case 4:
                loadingData.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var validateForm = function () {
    errors.value = {};
    if (!form.category_code) {
        errors.value.category_code = 'Category code is required';
    }
    if (!form.category_name) {
        errors.value.category_name = 'Category name is required';
    }
    return Object.keys(errors.value).length === 0;
};
var handleSubmit = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_3;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!validateForm()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation Error',
                        detail: 'Please fill in all required fields',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                submitting.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 6, 7, 8]);
                if (!isEditMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateCategory(Number(route.params.id), form)];
            case 2:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createCategory(form)];
            case 4:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Category created successfully',
                    life: 3000
                });
                _d.label = 5;
            case 5:
                router.push({ name: 'merchandising.categories' });
                return [3 /*break*/, 8];
            case 6:
                error_3 = _d.sent();
                console.error('Form submission error:', error_3);
                if (((_a = error_3.response) === null || _a === void 0 ? void 0 : _a.status) === 422) {
                    errors.value = error_3.response.data.errors || {};
                }
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_c = (_b = error_3.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save category',
                    life: 5000
                });
                return [3 /*break*/, 8];
            case 7:
                submitting.value = false;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var openIconBrowser = function () {
    window.open('https://primevue.org/icons', '_blank');
};
(0, vue_1.onMounted)(function () {
    loadCategories();
    loadCategory();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-3xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-3xl']} */ ;
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
(__VLS_ctx.isEditMode ? 'Edit Category' : 'Add New Category');
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.isEditMode ? 'Update category information' : 'Create a new product category');
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Back", icon: "pi pi-arrow-left", text: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'merchandising.categories' });
            // @ts-ignore
            [isEditMode, isEditMode, router,];
        } });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.loadingData) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ height: "400px" }, { class: "rounded-lg" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ height: "400px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleSubmit) }));
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12({}));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_13), false));
    var __VLS_17 = __VLS_15.slots.default;
    {
        var __VLS_18 = __VLS_15.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
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
        var __VLS_19 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ id: "category_code", modelValue: (__VLS_ctx.form.category_code), placeholder: "e.g., SOFA, CHAIR, TABLE" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_code }) })));
        var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ id: "category_code", modelValue: (__VLS_ctx.form.category_code), placeholder: "e.g., SOFA, CHAIR, TABLE" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_code }) })], __VLS_functionalComponentArgsRest(__VLS_20), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        if (__VLS_ctx.errors.category_code) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.category_code);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
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
        var __VLS_24 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign({ id: "category_name", modelValue: (__VLS_ctx.form.category_name), placeholder: "e.g., Sofas & Couches, Dining Tables" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_name }) })));
        var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign({ id: "category_name", modelValue: (__VLS_ctx.form.category_name), placeholder: "e.g., Sofas & Couches, Dining Tables" }, { class: ({ 'p-invalid': __VLS_ctx.errors.category_name }) })], __VLS_functionalComponentArgsRest(__VLS_25), false));
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
        var __VLS_29 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29({
            id: "parent_category_id",
            modelValue: (__VLS_ctx.form.parent_category_id),
            options: (__VLS_ctx.categories),
            optionLabel: "category_name",
            optionValue: "id",
            placeholder: "None (Root Category)",
            showClear: true,
            loading: (__VLS_ctx.loadingCategories),
        }));
        var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([{
                id: "parent_category_id",
                modelValue: (__VLS_ctx.form.parent_category_id),
                options: (__VLS_ctx.categories),
                optionLabel: "category_name",
                optionValue: "id",
                placeholder: "None (Root Category)",
                showClear: true,
                loading: (__VLS_ctx.loadingCategories),
            }], __VLS_functionalComponentArgsRest(__VLS_30), false));
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
        var __VLS_34 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Textarea} */
        textarea_1.default;
        // @ts-ignore
        var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34({
            id: "description",
            modelValue: (__VLS_ctx.form.description),
            rows: "4",
            placeholder: "Category description...",
        }));
        var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([{
                id: "description",
                modelValue: (__VLS_ctx.form.description),
                rows: "4",
                placeholder: "Category description...",
            }], __VLS_functionalComponentArgsRest(__VLS_35), false));
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
        var __VLS_39 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ id: "icon_path", modelValue: (__VLS_ctx.form.icon_path), placeholder: "e.g., pi pi-box, pi pi-home" }, { class: "flex-1" })));
        var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ id: "icon_path", modelValue: (__VLS_ctx.form.icon_path), placeholder: "e.g., pi pi-box, pi pi-home" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_40), false));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        var __VLS_44 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44(__assign({ 'onClick': {} }, { label: "Browse Icons", icon: "pi pi-external-link", outlined: true })));
        var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Browse Icons", icon: "pi pi-external-link", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_45), false));
        var __VLS_49 = void 0;
        var __VLS_50 = ({ click: {} },
            { onClick: (__VLS_ctx.openIconBrowser) });
        var __VLS_47;
        var __VLS_48;
        if (__VLS_ctx.form.icon_path) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-2 p-3 bg-gray-50 rounded" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (__VLS_ctx.form.icon_path) }, { class: "text-3xl text-gray-700" }));
            /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)(__assign({ href: "https://primevue.org/icons", target: "_blank" }, { class: "text-blue-600 hover:underline" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:underline']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "display_order" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_51 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51({
            id: "display_order",
            modelValue: (__VLS_ctx.form.display_order),
            min: (0),
            showButtons: true,
        }));
        var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([{
                id: "display_order",
                modelValue: (__VLS_ctx.form.display_order),
                min: (0),
                showButtons: true,
            }], __VLS_functionalComponentArgsRest(__VLS_52), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 pt-3 border-t border-gray-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        var __VLS_56 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56({
            modelValue: (__VLS_ctx.form.is_active),
            inputId: "is_active",
            binary: (true),
        }));
        var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_active),
                inputId: "is_active",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_57), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_active" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        // @ts-ignore
        [loadingData, handleSubmit, form, form, form, form, form, form, form, form, form, errors, errors, errors, errors, errors, errors, categories, loadingCategories, openIconBrowser,];
    }
    // @ts-ignore
    [];
    var __VLS_15;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-3 mt-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    var __VLS_61 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_62 = __VLS_asFunctionalComponent1(__VLS_61, new __VLS_61(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_63 = __VLS_62.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_62), false));
    var __VLS_66 = void 0;
    var __VLS_67 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.categories' });
                // @ts-ignore
                [router,];
            } });
    var __VLS_64;
    var __VLS_65;
    var __VLS_68 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Category' : 'Create Category'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })));
    var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Category' : 'Create Category'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })], __VLS_functionalComponentArgsRest(__VLS_69), false));
    var __VLS_73 = void 0;
    var __VLS_74 = ({ click: {} },
        { onClick: (__VLS_ctx.handleSubmit) });
    var __VLS_71;
    var __VLS_72;
}
// @ts-ignore
[isEditMode, handleSubmit, submitting,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
