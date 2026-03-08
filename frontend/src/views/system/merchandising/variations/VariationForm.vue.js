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
var checkbox_1 = require("primevue/checkbox");
var skeleton_1 = require("primevue/skeleton");
var route = (0, vue_router_1.useRoute)();
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var isEditMode = (0, vue_1.computed)(function () { return !!route.params.id; });
var submitting = (0, vue_1.ref)(false);
var loadingData = (0, vue_1.ref)(false);
var loadingProducts = (0, vue_1.ref)(false);
var products = (0, vue_1.ref)([]);
var selectedProduct = (0, vue_1.ref)(null);
var form = (0, vue_1.reactive)({
    product_id: null,
    variation_sku: '',
    variation_name: '',
    color: '',
    color_hex: '#3B82F6',
    size: '',
    material: '',
    finish: '',
    pattern: '',
    price_adjustment: 0,
    stock_quantity: 0,
    weight_kg: null,
    length_cm: null,
    width_cm: null,
    height_cm: null,
    is_active: true
});
var errors = (0, vue_1.ref)({});
var basePrice = (0, vue_1.computed)(function () {
    var _a;
    return ((_a = selectedProduct.value) === null || _a === void 0 ? void 0 : _a.base_price) || 0;
});
var finalPrice = (0, vue_1.computed)(function () {
    if (!basePrice.value)
        return null;
    return basePrice.value + (form.price_adjustment || 0);
});
// Watch product selection
(0, vue_1.watch)(function () { return form.product_id; }, function (newVal) {
    if (newVal) {
        selectedProduct.value = products.value.find(function (p) { return p.id === newVal; });
        generateSKU();
    }
});
var loadProducts = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingProducts.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getProducts({ per_page: 1000 })];
            case 2:
                response = _a.sent();
                products.value = response.data.data;
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                console.error('Failed to load products:', error_1);
                return [3 /*break*/, 5];
            case 4:
                loadingProducts.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var loadVariation = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, variation, error_2;
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
                return [4 /*yield*/, merchandising_service_1.default.getVariation(Number(route.params.id))];
            case 2:
                response = _c.sent();
                variation = response.data;
                Object.assign(form, {
                    product_id: variation.product_id,
                    variation_sku: variation.variation_sku,
                    variation_name: variation.variation_name,
                    color: variation.color || '',
                    color_hex: variation.color_hex || '#3B82F6',
                    size: variation.size || '',
                    material: variation.material || '',
                    finish: variation.finish || '',
                    pattern: variation.pattern || '',
                    price_adjustment: variation.price_adjustment || 0,
                    stock_quantity: variation.stock_quantity || 0,
                    weight_kg: variation.weight_kg,
                    length_cm: variation.length_cm,
                    width_cm: variation.width_cm,
                    height_cm: variation.height_cm,
                    is_active: variation.is_active
                });
                selectedProduct.value = variation.product;
                return [3 /*break*/, 5];
            case 3:
                error_2 = _c.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_2.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load variation',
                    life: 5000
                });
                router.push({ name: 'merchandising.variations' });
                return [3 /*break*/, 5];
            case 4:
                loadingData.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var onProductChange = function () {
    generateSKU();
};
var generateSKU = function () {
    var _a, _b, _c, _d;
    if (!selectedProduct.value)
        return;
    var baseSKU = selectedProduct.value.sku;
    var attributes = [
        (_a = form.color) === null || _a === void 0 ? void 0 : _a.substring(0, 3).toUpperCase(),
        (_b = form.size) === null || _b === void 0 ? void 0 : _b.substring(0, 2).toUpperCase(),
        (_c = form.material) === null || _c === void 0 ? void 0 : _c.substring(0, 3).toUpperCase(),
        (_d = form.finish) === null || _d === void 0 ? void 0 : _d.substring(0, 2).toUpperCase()
    ].filter(Boolean).join('-');
    form.variation_sku = attributes ? "".concat(baseSKU, "-").concat(attributes) : baseSKU;
};
var validateForm = function () {
    errors.value = {};
    if (!form.product_id) {
        errors.value.product_id = 'Please select a product';
    }
    if (!form.variation_name) {
        errors.value.variation_name = 'Variation name is required';
    }
    if (!form.variation_sku) {
        errors.value.variation_sku = 'Variation SKU is required';
    }
    if (form.stock_quantity === null || form.stock_quantity < 0) {
        errors.value.stock_quantity = 'Stock quantity must be 0 or greater';
    }
    return Object.keys(errors.value).length === 0;
};
var handleSubmit = function () { return __awaiter(void 0, void 0, void 0, function () {
    var submitData, error_3;
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
                submitData = __assign(__assign({}, form), { final_price: finalPrice.value });
                if (!isEditMode.value) return [3 /*break*/, 3];
                return [4 /*yield*/, merchandising_service_1.default.updateVariation(Number(route.params.id), submitData)];
            case 2:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Variation updated successfully',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 3: return [4 /*yield*/, merchandising_service_1.default.createVariation(submitData)];
            case 4:
                _d.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Variation created successfully',
                    life: 3000
                });
                _d.label = 5;
            case 5:
                router.push({ name: 'merchandising.variations' });
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
                    detail: ((_c = (_b = error_3.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save variation',
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
var formatPrice = function (price) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
(0, vue_1.onMounted)(function () {
    loadProducts();
    loadVariation();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-4xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-4xl']} */ ;
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
(__VLS_ctx.isEditMode ? 'Edit Variation' : 'Add New Variation');
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.isEditMode ? 'Update variation details' : 'Create a new product variation');
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
            __VLS_ctx.$router.push({ name: 'merchandising.variations' });
            // @ts-ignore
            [isEditMode, isEditMode, $router,];
        } });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.loadingData) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ height: "300px" }, { class: "rounded-lg" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ height: "300px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    var __VLS_12 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
    skeleton_1.default;
    // @ts-ignore
    var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ height: "200px" }, { class: "rounded-lg" })));
    var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ height: "200px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.handleSubmit) }));
    var __VLS_17 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_18 = __VLS_asFunctionalComponent1(__VLS_17, new __VLS_17(__assign({ class: "mb-6" })));
    var __VLS_19 = __VLS_18.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_18), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_22 = __VLS_20.slots.default;
    {
        var __VLS_23 = __VLS_20.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [loadingData, handleSubmit,];
    }
    {
        var __VLS_24 = __VLS_20.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "product_id" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_25 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign(__assign({ 'onChange': {} }, { id: "product_id", modelValue: (__VLS_ctx.form.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "Select a product" }), { class: ({ 'p-invalid': __VLS_ctx.errors.product_id }) }), { loading: (__VLS_ctx.loadingProducts), filter: true })));
        var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { id: "product_id", modelValue: (__VLS_ctx.form.product_id), options: (__VLS_ctx.products), optionLabel: "product_name", optionValue: "id", placeholder: "Select a product" }), { class: ({ 'p-invalid': __VLS_ctx.errors.product_id }) }), { loading: (__VLS_ctx.loadingProducts), filter: true })], __VLS_functionalComponentArgsRest(__VLS_26), false));
        var __VLS_30 = void 0;
        var __VLS_31 = ({ change: {} },
            { onChange: (__VLS_ctx.onProductChange) });
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        var __VLS_28;
        var __VLS_29;
        if (__VLS_ctx.errors.product_id) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.product_id);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "variation_sku" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_32 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign(__assign({ id: "variation_sku", modelValue: (__VLS_ctx.form.variation_sku), placeholder: "Will be auto-generated" }, { class: ({ 'p-invalid': __VLS_ctx.errors.variation_sku }) }), { readonly: true }), { class: "bg-gray-100" })));
        var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign(__assign({ id: "variation_sku", modelValue: (__VLS_ctx.form.variation_sku), placeholder: "Will be auto-generated" }, { class: ({ 'p-invalid': __VLS_ctx.errors.variation_sku }) }), { readonly: true }), { class: "bg-gray-100" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        if (__VLS_ctx.errors.variation_sku) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.variation_sku);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "variation_name" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_37 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_38 = __VLS_asFunctionalComponent1(__VLS_37, new __VLS_37(__assign({ id: "variation_name", modelValue: (__VLS_ctx.form.variation_name), placeholder: "e.g., Navy Blue - Large, Oak Wood Finish" }, { class: ({ 'p-invalid': __VLS_ctx.errors.variation_name }) })));
        var __VLS_39 = __VLS_38.apply(void 0, __spreadArray([__assign({ id: "variation_name", modelValue: (__VLS_ctx.form.variation_name), placeholder: "e.g., Navy Blue - Large, Oak Wood Finish" }, { class: ({ 'p-invalid': __VLS_ctx.errors.variation_name }) })], __VLS_functionalComponentArgsRest(__VLS_38), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        if (__VLS_ctx.errors.variation_name) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.variation_name);
        }
        // @ts-ignore
        [form, form, form, products, errors, errors, errors, errors, errors, errors, errors, errors, errors, loadingProducts, onProductChange,];
    }
    // @ts-ignore
    [];
    var __VLS_20;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ class: "mb-6" })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_47 = __VLS_45.slots.default;
    {
        var __VLS_48 = __VLS_45.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-palette text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-palette']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_49 = __VLS_45.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "color" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_50 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50(__assign({ 'onInput': {} }, { id: "color", modelValue: (__VLS_ctx.form.color), placeholder: "e.g., Navy Blue, Charcoal Gray" })));
        var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { id: "color", modelValue: (__VLS_ctx.form.color), placeholder: "e.g., Navy Blue, Charcoal Gray" })], __VLS_functionalComponentArgsRest(__VLS_51), false));
        var __VLS_55 = void 0;
        var __VLS_56 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        var __VLS_53;
        var __VLS_54;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "color_hex" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2 items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)(__assign({ type: "color" }, { class: "h-10 w-16 rounded border border-gray-300 cursor-pointer" }));
        (__VLS_ctx.form.color_hex);
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['w-16']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-300']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        var __VLS_57 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_58 = __VLS_asFunctionalComponent1(__VLS_57, new __VLS_57(__assign(__assign({ id: "color_hex", modelValue: (__VLS_ctx.form.color_hex), placeholder: "#000000" }, { class: "flex-1 font-mono" }), { maxlength: "7" })));
        var __VLS_59 = __VLS_58.apply(void 0, __spreadArray([__assign(__assign({ id: "color_hex", modelValue: (__VLS_ctx.form.color_hex), placeholder: "#000000" }, { class: "flex-1 font-mono" }), { maxlength: "7" })], __VLS_functionalComponentArgsRest(__VLS_58), false));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "size" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_62 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62(__assign({ 'onInput': {} }, { id: "size", modelValue: (__VLS_ctx.form.size), placeholder: "e.g., Small, Medium, Large, XL" })));
        var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { id: "size", modelValue: (__VLS_ctx.form.size), placeholder: "e.g., Small, Medium, Large, XL" })], __VLS_functionalComponentArgsRest(__VLS_63), false));
        var __VLS_67 = void 0;
        var __VLS_68 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        var __VLS_65;
        var __VLS_66;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "material" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_69 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ 'onInput': {} }, { id: "material", modelValue: (__VLS_ctx.form.material), placeholder: "e.g., Leather, Fabric, Wood" })));
        var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { id: "material", modelValue: (__VLS_ctx.form.material), placeholder: "e.g., Leather, Fabric, Wood" })], __VLS_functionalComponentArgsRest(__VLS_70), false));
        var __VLS_74 = void 0;
        var __VLS_75 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        var __VLS_72;
        var __VLS_73;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "finish" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_76 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76(__assign({ 'onInput': {} }, { id: "finish", modelValue: (__VLS_ctx.form.finish), placeholder: "e.g., Matte, Glossy, Satin" })));
        var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([__assign({ 'onInput': {} }, { id: "finish", modelValue: (__VLS_ctx.form.finish), placeholder: "e.g., Matte, Glossy, Satin" })], __VLS_functionalComponentArgsRest(__VLS_77), false));
        var __VLS_81 = void 0;
        var __VLS_82 = ({ input: {} },
            { onInput: (__VLS_ctx.generateSKU) });
        var __VLS_79;
        var __VLS_80;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "pattern" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_83 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputText} */
        inputtext_1.default;
        // @ts-ignore
        var __VLS_84 = __VLS_asFunctionalComponent1(__VLS_83, new __VLS_83({
            id: "pattern",
            modelValue: (__VLS_ctx.form.pattern),
            placeholder: "e.g., Solid, Striped, Checkered",
        }));
        var __VLS_85 = __VLS_84.apply(void 0, __spreadArray([{
                id: "pattern",
                modelValue: (__VLS_ctx.form.pattern),
                placeholder: "e.g., Solid, Striped, Checkered",
            }], __VLS_functionalComponentArgsRest(__VLS_84), false));
        // @ts-ignore
        [form, form, form, form, form, form, form, generateSKU, generateSKU, generateSKU, generateSKU,];
    }
    // @ts-ignore
    [];
    var __VLS_45;
    var __VLS_88 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ class: "mb-6" })));
    var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_89), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_93 = __VLS_91.slots.default;
    {
        var __VLS_94 = __VLS_91.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_95 = __VLS_91.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        if (__VLS_ctx.basePrice) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 border border-blue-200 rounded-lg p-4" }));
            /** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-blue-900" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-blue-900']} */ ;
            (__VLS_ctx.formatPrice(__VLS_ctx.basePrice));
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "price_adjustment" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_96 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96({
            id: "price_adjustment",
            modelValue: (__VLS_ctx.form.price_adjustment),
            mode: "currency",
            currency: "PHP",
            locale: "en-PH",
            minFractionDigits: (2),
            fluid: true,
        }));
        var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([{
                id: "price_adjustment",
                modelValue: (__VLS_ctx.form.price_adjustment),
                mode: "currency",
                currency: "PHP",
                locale: "en-PH",
                minFractionDigits: (2),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_97), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        if (__VLS_ctx.finalPrice !== null) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 border border-green-200 rounded-lg p-4" }));
            /** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['border']} */ ;
            /** @type {__VLS_StyleScopedClasses['border-green-200']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-green-900" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-green-900']} */ ;
            (__VLS_ctx.formatPrice(__VLS_ctx.finalPrice));
        }
        // @ts-ignore
        [form, basePrice, basePrice, formatPrice, formatPrice, finalPrice, finalPrice,];
    }
    // @ts-ignore
    [];
    var __VLS_91;
    var __VLS_101 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101(__assign({ class: "mb-6" })));
    var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([__assign({ class: "mb-6" })], __VLS_functionalComponentArgsRest(__VLS_102), false));
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_106 = __VLS_104.slots.default;
    {
        var __VLS_107 = __VLS_104.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-orange-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_108 = __VLS_104.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
        /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "stock_quantity" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        var __VLS_109 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ id: "stock_quantity", modelValue: (__VLS_ctx.form.stock_quantity), min: (0), showButtons: true }, { class: ({ 'p-invalid': __VLS_ctx.errors.stock_quantity }) })));
        var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ id: "stock_quantity", modelValue: (__VLS_ctx.form.stock_quantity), min: (0), showButtons: true }, { class: ({ 'p-invalid': __VLS_ctx.errors.stock_quantity }) })], __VLS_functionalComponentArgsRest(__VLS_110), false));
        /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
        if (__VLS_ctx.errors.stock_quantity) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
            /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
            (__VLS_ctx.errors.stock_quantity);
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "weight" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_114 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114({
            id: "weight",
            modelValue: (__VLS_ctx.form.weight_kg),
            minFractionDigits: (2),
            suffix: " kg",
            min: (0),
            fluid: true,
        }));
        var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([{
                id: "weight",
                modelValue: (__VLS_ctx.form.weight_kg),
                minFractionDigits: (2),
                suffix: " kg",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_115), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "length" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_119 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119({
            id: "length",
            modelValue: (__VLS_ctx.form.length_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([{
                id: "length",
                modelValue: (__VLS_ctx.form.length_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_120), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "width" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_124 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124({
            id: "width",
            modelValue: (__VLS_ctx.form.width_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([{
                id: "width",
                modelValue: (__VLS_ctx.form.width_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_125), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "height" }, { class: "text-sm font-semibold text-gray-700" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        var __VLS_129 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
        inputnumber_1.default;
        // @ts-ignore
        var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129({
            id: "height",
            modelValue: (__VLS_ctx.form.height_cm),
            minFractionDigits: (2),
            suffix: " cm",
            min: (0),
            fluid: true,
        }));
        var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([{
                id: "height",
                modelValue: (__VLS_ctx.form.height_cm),
                minFractionDigits: (2),
                suffix: " cm",
                min: (0),
                fluid: true,
            }], __VLS_functionalComponentArgsRest(__VLS_130), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 pt-3 border-t border-gray-200" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['pt-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
        var __VLS_134 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Checkbox} */
        checkbox_1.default;
        // @ts-ignore
        var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134({
            modelValue: (__VLS_ctx.form.is_active),
            inputId: "is_active",
            binary: (true),
        }));
        var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([{
                modelValue: (__VLS_ctx.form.is_active),
                inputId: "is_active",
                binary: (true),
            }], __VLS_functionalComponentArgsRest(__VLS_135), false));
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_active" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
        // @ts-ignore
        [form, form, form, form, form, form, errors, errors, errors,];
    }
    // @ts-ignore
    [];
    var __VLS_104;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-3 pt-6 border-t border-gray-200" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    var __VLS_139 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_140), false));
    var __VLS_144 = void 0;
    var __VLS_145 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loadingData))
                    return;
                __VLS_ctx.$router.push({ name: 'merchandising.variations' });
                // @ts-ignore
                [$router,];
            } });
    var __VLS_142;
    var __VLS_143;
    var __VLS_146 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Variation' : 'Create Variation'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })));
    var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.isEditMode ? 'Update Variation' : 'Create Variation'), icon: "pi pi-check", loading: (__VLS_ctx.submitting) })], __VLS_functionalComponentArgsRest(__VLS_147), false));
    var __VLS_151 = void 0;
    var __VLS_152 = ({ click: {} },
        { onClick: (__VLS_ctx.handleSubmit) });
    var __VLS_149;
    var __VLS_150;
}
// @ts-ignore
[isEditMode, handleSubmit, submitting,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
