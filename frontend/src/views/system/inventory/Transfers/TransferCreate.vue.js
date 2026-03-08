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
var vue_router_1 = require("vue-router");
var usetoast_1 = require("primevue/usetoast");
var inventory_service_1 = require("../../../../services/inventory.service");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var saving = (0, vue_1.ref)(false);
var loadingBranches = (0, vue_1.ref)(false);
var loadingProducts = (0, vue_1.ref)(false);
var showCancelDialog = (0, vue_1.ref)(false);
// Form state
var form = (0, vue_1.reactive)({
    from_branch_id: null,
    to_branch_id: null,
    transfer_date: new Date(),
    expected_receive_date: null,
    remarks: '',
    items: []
});
// New item form
var newItem = (0, vue_1.reactive)({
    inventory_item_id: null,
    quantity: 1,
    notes: ''
});
// Validation errors
var errors = (0, vue_1.ref)({
    from_branch_id: '',
    to_branch_id: '',
    expected_receive_date: ''
});
// API data
var branches = (0, vue_1.ref)([]);
var inventoryItems = (0, vue_1.ref)([]);
// Computed
var toBranchOptions = (0, vue_1.computed)(function () {
    return branches.value.filter(function (b) { return b.id !== form.from_branch_id; });
});
var availableProducts = (0, vue_1.computed)(function () {
    if (!form.from_branch_id)
        return [];
    // Filter products from source branch and not already added
    var addedIds = form.items.map(function (item) { return item.inventory_item_id; });
    return inventoryItems.value.filter(function (p) { return !addedIds.includes(p.id); });
});
var selectedProduct = (0, vue_1.computed)(function () {
    if (!newItem.inventory_item_id)
        return null;
    return inventoryItems.value.find(function (p) { return p.id === newItem.inventory_item_id; });
});
var canAddItem = (0, vue_1.computed)(function () {
    return newItem.inventory_item_id && newItem.quantity > 0;
});
var isFormValid = (0, vue_1.computed)(function () {
    return (form.from_branch_id &&
        form.to_branch_id &&
        form.items.length > 0);
});
var totalQuantity = (0, vue_1.computed)(function () {
    return form.items.reduce(function (sum, item) { return sum + item.quantity; }, 0);
});
// Methods
var loadBranches = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                loadingBranches.value = true;
                _b.label = 1;
            case 1:
                _b.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getBranches()];
            case 2:
                response = _b.sent();
                branches.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || response.data || [];
                return [3 /*break*/, 5];
            case 3:
                error_1 = _b.sent();
                console.error('Failed to load branches:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load branches',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                loadingBranches.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var onFromBranchChange = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                form.to_branch_id = null;
                form.items = []; // Clear items when source branch changes
                inventoryItems.value = [];
                newItem.inventory_item_id = null;
                if (!form.from_branch_id) return [3 /*break*/, 2];
                return [4 /*yield*/, loadInventoryForBranch(form.from_branch_id)];
            case 1:
                _a.sent();
                _a.label = 2;
            case 2: return [2 /*return*/];
        }
    });
}); };
var loadInventoryForBranch = function (branchId) { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                if (!branchId)
                    return [2 /*return*/];
                loadingProducts.value = true;
                _b.label = 1;
            case 1:
                _b.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getBranchInventory(branchId, { per_page: 100 })];
            case 2:
                response = _b.sent();
                inventoryItems.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || response.data || [];
                if (inventoryItems.value.length === 0) {
                    toast.add({
                        severity: 'info',
                        summary: 'No Items',
                        detail: 'No inventory items found for this branch',
                        life: 3000
                    });
                }
                return [3 /*break*/, 5];
            case 3:
                error_2 = _b.sent();
                console.error('Failed to load inventory:', error_2);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to load products',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                loadingProducts.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var getProductName = function (inventoryItemId) {
    var _a;
    var item = inventoryItems.value.find(function (p) { return p.id === inventoryItemId; });
    return ((_a = item === null || item === void 0 ? void 0 : item.product) === null || _a === void 0 ? void 0 : _a.product_name) || "Item #".concat(inventoryItemId);
};
var getProductSku = function (inventoryItemId) {
    var _a;
    var item = inventoryItems.value.find(function (p) { return p.id === inventoryItemId; });
    return ((_a = item === null || item === void 0 ? void 0 : item.product) === null || _a === void 0 ? void 0 : _a.sku) || '-';
};
var addItem = function () {
    if (!canAddItem.value)
        return;
    form.items.push({
        inventory_item_id: newItem.inventory_item_id,
        quantity: newItem.quantity,
        notes: newItem.notes || undefined
    });
    toast.add({
        severity: 'success',
        summary: 'Item Added',
        detail: "".concat(getProductName(newItem.inventory_item_id), " added to transfer"),
        life: 2000
    });
    // Reset form
    newItem.inventory_item_id = null;
    newItem.quantity = 1;
    newItem.notes = '';
};
var removeItem = function (index) {
    form.items.splice(index, 1);
    toast.add({
        severity: 'info',
        summary: 'Item Removed',
        detail: 'Item removed from transfer',
        life: 2000
    });
};
var validateForm = function () {
    errors.value = { from_branch_id: '', to_branch_id: '', expected_receive_date: '' };
    var isValid = true;
    if (!form.from_branch_id) {
        errors.value.from_branch_id = 'From branch is required';
        isValid = false;
    }
    if (!form.to_branch_id) {
        errors.value.to_branch_id = 'To branch is required';
        isValid = false;
    }
    if (form.from_branch_id === form.to_branch_id) {
        errors.value.to_branch_id = 'From and To branches must be different';
        isValid = false;
    }
    return isValid;
};
var submitTransfer = function () { return __awaiter(void 0, void 0, void 0, function () {
    var payload, response, transferId, error_3, message;
    var _a, _b, _c, _d, _e, _f;
    return __generator(this, function (_g) {
        switch (_g.label) {
            case 0:
                if (!validateForm()) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Validation Error',
                        detail: 'Please fix validation errors',
                        life: 3000
                    });
                    return [2 /*return*/];
                }
                saving.value = true;
                _g.label = 1;
            case 1:
                _g.trys.push([1, 3, 4, 5]);
                payload = {
                    from_branch_id: form.from_branch_id,
                    to_branch_id: form.to_branch_id,
                    transfer_date: form.transfer_date.toISOString().split('T')[0],
                    expected_receive_date: (_a = form.expected_receive_date) === null || _a === void 0 ? void 0 : _a.toISOString().split('T')[0],
                    remarks: form.remarks || undefined,
                    items: form.items.map(function (item) { return ({
                        inventory_item_id: item.inventory_item_id,
                        quantity: item.quantity,
                        notes: item.notes
                    }); })
                };
                return [4 /*yield*/, inventory_service_1.default.createTransfer(payload)];
            case 2:
                response = _g.sent();
                transferId = ((_b = response.data) === null || _b === void 0 ? void 0 : _b.id) || ((_d = (_c = response.data) === null || _c === void 0 ? void 0 : _c.data) === null || _d === void 0 ? void 0 : _d.id);
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "Transfer #".concat(transferId, " created successfully"),
                    life: 3000
                });
                router.push({ name: 'inventory.transfers' });
                return [3 /*break*/, 5];
            case 3:
                error_3 = _g.sent();
                console.error('Failed to create transfer', error_3);
                message = ((_f = (_e = error_3.response) === null || _e === void 0 ? void 0 : _e.data) === null || _f === void 0 ? void 0 : _f.message) || 'Failed to create transfer';
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: message,
                    life: 5000
                });
                return [3 /*break*/, 5];
            case 4:
                saving.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var cancel = function () {
    if (form.items.length > 0 || form.remarks || form.expected_receive_date) {
        showCancelDialog.value = true;
    }
    else {
        router.push({ name: 'inventory.transfers' });
    }
};
var confirmCancel = function () {
    showCancelDialog.value = false;
    router.push({ name: 'inventory.transfers' });
};
// Watch for quantity validation
(0, vue_1.watch)(function () { return newItem.quantity; }, function (newVal) {
    if (selectedProduct.value && newVal > selectedProduct.value.quantity_on_hand) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Quantity exceeds available stock',
            life: 3000
        });
    }
});
// Load initial data
(0, vue_1.onMounted)(function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0: return [4 /*yield*/, loadBranches()];
            case 1:
                _a.sent();
                return [2 /*return*/];
        }
    });
}); });
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "max-w-7xl mx-auto space-y-6 pb-6" }));
/** @type {__VLS_StyleScopedClasses['max-w-7xl']} */ ;
/** @type {__VLS_StyleScopedClasses['mx-auto']} */ ;
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
/** @type {__VLS_StyleScopedClasses['pb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-arrow-left", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'inventory.transfers' });
            // @ts-ignore
            [router,];
        } });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({}));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12 = __VLS_10.slots.default;
{
    var __VLS_13 = __VLS_10.slots.content;
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.submitTransfer) }, { class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
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
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.from_branch_id), options: (__VLS_ctx.branches), optionLabel: "name", optionValue: "id", placeholder: "Select source branch", loading: (__VLS_ctx.loadingBranches) }), { class: ({ 'p-invalid': __VLS_ctx.errors.from_branch_id }) })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.from_branch_id), options: (__VLS_ctx.branches), optionLabel: "name", optionValue: "id", placeholder: "Select source branch", loading: (__VLS_ctx.loadingBranches) }), { class: ({ 'p-invalid': __VLS_ctx.errors.from_branch_id }) })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ change: {} },
        { onChange: (__VLS_ctx.onFromBranchChange) });
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    var __VLS_17;
    var __VLS_18;
    if (__VLS_ctx.errors.from_branch_id) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.from_branch_id);
    }
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
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ modelValue: (__VLS_ctx.form.to_branch_id), options: (__VLS_ctx.toBranchOptions), optionLabel: "name", optionValue: "id", placeholder: "Select destination branch", disabled: (!__VLS_ctx.form.from_branch_id) }, { class: ({ 'p-invalid': __VLS_ctx.errors.to_branch_id }) })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.form.to_branch_id), options: (__VLS_ctx.toBranchOptions), optionLabel: "name", optionValue: "id", placeholder: "Select destination branch", disabled: (!__VLS_ctx.form.from_branch_id) }, { class: ({ 'p-invalid': __VLS_ctx.errors.to_branch_id }) })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    if (__VLS_ctx.errors.to_branch_id) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.to_branch_id);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
    DatePicker;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.expected_receive_date), dateFormat: "yy-mm-dd" }, { class: "w-full" }), { minDate: (new Date()) }), { class: ({ 'p-invalid': __VLS_ctx.errors.expected_receive_date }) })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.expected_receive_date), dateFormat: "yy-mm-dd" }, { class: "w-full" }), { minDate: (new Date()) }), { class: ({ 'p-invalid': __VLS_ctx.errors.expected_receive_date }) })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    if (__VLS_ctx.errors.expected_receive_date) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.expected_receive_date);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_31 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    Textarea;
    // @ts-ignore
    var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31({
        modelValue: (__VLS_ctx.form.remarks),
        rows: "2",
        placeholder: "Add any additional notes about this transfer...",
    }));
    var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.form.remarks),
            rows: "2",
            placeholder: "Add any additional notes about this transfer...",
        }], __VLS_functionalComponentArgsRest(__VLS_32), false));
    var __VLS_36 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Divider | typeof __VLS_components.Divider} */
    Divider;
    // @ts-ignore
    var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36({}));
    var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_37), false));
    var __VLS_41 = __VLS_39.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-semibold text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    // @ts-ignore
    [submitTransfer, form, form, form, form, form, branches, loadingBranches, errors, errors, errors, errors, errors, errors, errors, errors, errors, onFromBranchChange, toBranchOptions,];
    var __VLS_39;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-12 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-12']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-5" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select | typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42({
        modelValue: (__VLS_ctx.newItem.inventory_item_id),
        options: (__VLS_ctx.availableProducts),
        optionLabel: "product.product_name",
        optionValue: "id",
        placeholder: "Select product...",
        loading: (__VLS_ctx.loadingProducts),
        filter: true,
        showClear: true,
    }));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.newItem.inventory_item_id),
            options: (__VLS_ctx.availableProducts),
            optionLabel: "product.product_name",
            optionValue: "id",
            placeholder: "Select product...",
            loading: (__VLS_ctx.loadingProducts),
            filter: true,
            showClear: true,
        }], __VLS_functionalComponentArgsRest(__VLS_43), false));
    var __VLS_47 = __VLS_45.slots.default;
    {
        var __VLS_48 = __VLS_45.slots.option;
        var option = __VLS_vSlot(__VLS_48)[0].option;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (((_a = option.product) === null || _a === void 0 ? void 0 : _a.product_name) || 'Unknown');
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (((_b = option.product) === null || _b === void 0 ? void 0 : _b.sku) || 'N/A');
        (option.quantity_on_hand || 0);
        // @ts-ignore
        [newItem, availableProducts, loadingProducts,];
    }
    // @ts-ignore
    [];
    var __VLS_45;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ modelValue: (__VLS_ctx.newItem.quantity), min: (1), max: (((_c = __VLS_ctx.selectedProduct) === null || _c === void 0 ? void 0 : _c.quantity_on_hand) || 999999), showButtons: true, buttonLayout: "horizontal" }, { class: "w-full" })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newItem.quantity), min: (1), max: (((_d = __VLS_ctx.selectedProduct) === null || _d === void 0 ? void 0 : _d.quantity_on_hand) || 999999), showButtons: true, buttonLayout: "horizontal" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    var __VLS_54 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54({
        modelValue: (__VLS_ctx.newItem.notes),
        placeholder: "Optional notes",
    }));
    var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.newItem.notes),
            placeholder: "Optional notes",
        }], __VLS_functionalComponentArgsRest(__VLS_55), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 justify-end md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    var __VLS_59 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add", disabled: (!__VLS_ctx.canAddItem) }), { class: "mt-6 w-full" })));
    var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add", disabled: (!__VLS_ctx.canAddItem) }), { class: "mt-6 w-full" })], __VLS_functionalComponentArgsRest(__VLS_60), false));
    var __VLS_64 = void 0;
    var __VLS_65 = ({ click: {} },
        { onClick: (__VLS_ctx.addItem) });
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_62;
    var __VLS_63;
    if (__VLS_ctx.selectedProduct) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm bg-white p-3 rounded border border-blue-200" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['border']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-blue-200']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        ((_e = __VLS_ctx.selectedProduct.product) === null || _e === void 0 ? void 0 : _e.product_name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500 ml-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
        ((_f = __VLS_ctx.selectedProduct.product) === null || _f === void 0 ? void 0 : _f.sku);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ({
                'text-green-600 font-medium': __VLS_ctx.selectedProduct.quantity_on_hand > 0,
                'text-red-600 font-medium': __VLS_ctx.selectedProduct.quantity_on_hand === 0
            }) }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.selectedProduct.quantity_on_hand || 0);
        if (__VLS_ctx.newItem.quantity > (((_g = __VLS_ctx.selectedProduct) === null || _g === void 0 ? void 0 : _g.quantity_on_hand) || 0)) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-2 text-amber-600 text-xs" }));
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-amber-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle mr-1" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
            /** @type {__VLS_StyleScopedClasses['mr-1']} */ ;
        }
    }
    var __VLS_66 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign(__assign({ value: (__VLS_ctx.form.items) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })));
    var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.form.items) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })], __VLS_functionalComponentArgsRest(__VLS_67), false));
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_71 = __VLS_69.slots.default;
    {
        var __VLS_72 = __VLS_69.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl mb-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        // @ts-ignore
        [form, newItem, newItem, newItem, selectedProduct, selectedProduct, selectedProduct, selectedProduct, selectedProduct, selectedProduct, selectedProduct, selectedProduct, canAddItem, addItem,];
    }
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73(__assign({ header: "Product" }, { style: {} })));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([__assign({ header: "Product" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = __VLS_76.slots.default;
    {
        var __VLS_79 = __VLS_76.slots.body;
        var data = __VLS_vSlot(__VLS_79)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.getProductName(data.inventory_item_id));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (__VLS_ctx.getProductSku(data.inventory_item_id));
        // @ts-ignore
        [getProductName, getProductSku,];
    }
    // @ts-ignore
    [];
    var __VLS_76;
    var __VLS_80 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ field: "quantity", header: "Quantity" }, { style: {} })));
    var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ field: "quantity", header: "Quantity" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_81), false));
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign({ field: "notes", header: "Notes" }, { style: {} })));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign({ field: "notes", header: "Notes" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_86), false));
    var __VLS_90 = __VLS_88.slots.default;
    {
        var __VLS_91 = __VLS_88.slots.body;
        var data = __VLS_vSlot(__VLS_91)[0].data;
        (data.notes || '-');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_88;
    var __VLS_92 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_93), false));
    var __VLS_97 = __VLS_95.slots.default;
    {
        var __VLS_98 = __VLS_95.slots.body;
        var index_1 = __VLS_vSlot(__VLS_98)[0].index;
        var __VLS_99 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })));
        var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_100), false));
        var __VLS_104 = void 0;
        var __VLS_105 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.removeItem(index_1);
                    // @ts-ignore
                    [removeItem,];
                } });
        __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { value: ('Remove item') }), null, null);
        var __VLS_102;
        var __VLS_103;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_95;
    // @ts-ignore
    [];
    var __VLS_69;
    if (__VLS_ctx.form.items.length > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (__VLS_ctx.form.items.length);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mt-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-semibold" }));
        /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (__VLS_ctx.totalQuantity);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "pt-4 flex gap-2 justify-end border-t border-gray-200" }));
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    var __VLS_106 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, type: "button" })));
    var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, type: "button" })], __VLS_functionalComponentArgsRest(__VLS_107), false));
    var __VLS_111 = void 0;
    var __VLS_112 = ({ click: {} },
        { onClick: (__VLS_ctx.cancel) });
    var __VLS_109;
    var __VLS_110;
    var __VLS_113 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_114 = __VLS_asFunctionalComponent1(__VLS_113, new __VLS_113({
        label: "Submit Transfer",
        icon: "pi pi-check",
        loading: (__VLS_ctx.saving),
        type: "submit",
        disabled: (!__VLS_ctx.isFormValid),
    }));
    var __VLS_115 = __VLS_114.apply(void 0, __spreadArray([{
            label: "Submit Transfer",
            icon: "pi pi-check",
            loading: (__VLS_ctx.saving),
            type: "submit",
            disabled: (!__VLS_ctx.isFormValid),
        }], __VLS_functionalComponentArgsRest(__VLS_114), false));
    // @ts-ignore
    [form, form, totalQuantity, cancel, saving, isFormValid,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_118;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118(__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Discard Changes", modal: (true) }, { class: "w-full sm:w-96" })));
var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Discard Changes", modal: (true) }, { class: "w-full sm:w-96" })], __VLS_functionalComponentArgsRest(__VLS_119), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-96']} */ ;
var __VLS_123 = __VLS_121.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_124;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ 'onClick': {} }, { label: "No, Stay", severity: "secondary", outlined: true })));
var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "No, Stay", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_125), false));
var __VLS_129;
var __VLS_130 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showCancelDialog = false;
            // @ts-ignore
            [showCancelDialog, showCancelDialog,];
        } });
var __VLS_127;
var __VLS_128;
var __VLS_131;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ 'onClick': {} }, { label: "Yes, Discard", severity: "danger" })));
var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Yes, Discard", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
var __VLS_136;
var __VLS_137 = ({ click: {} },
    { onClick: (__VLS_ctx.confirmCancel) });
var __VLS_134;
var __VLS_135;
// @ts-ignore
[confirmCancel,];
var __VLS_121;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
