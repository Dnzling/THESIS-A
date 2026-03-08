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
var inventory_service_1 = require("../../../../services/inventory.service");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
// State
var savingDraft = (0, vue_1.ref)(false);
var submitting = (0, vue_1.ref)(false);
var loadingBranches = (0, vue_1.ref)(false);
var loadingProducts = (0, vue_1.ref)(false);
var showCancelDialog = (0, vue_1.ref)(false);
// Form data - Updated to match backend expectations
var form = (0, vue_1.reactive)({
    branch_id: null,
    type: '',
    adjustment_date: new Date(),
    reason: '',
    remarks: '',
    items: []
});
// New item form
var newItem = (0, vue_1.reactive)({
    inventory_item_id: null,
    adjustment_type: 'add',
    quantity: 1,
    system_quantity: 0,
    remarks: ''
});
// Validation errors - Updated with type
var errors = (0, vue_1.ref)({
    adjustment_date: '',
    branch_id: '',
    type: '',
    reason: ''
});
// Data from API
var branches = (0, vue_1.ref)([]);
var inventoryItems = (0, vue_1.ref)([]);
// Options
var adjustmentTypeOptions = [
    { label: 'Physical Count', value: 'physical_count' },
    { label: 'Cycle Count', value: 'cycle_count' },
    { label: 'Spot Check', value: 'spot_check' },
    { label: 'Damage', value: 'damage' },
    { label: 'Loss', value: 'loss' },
    { label: 'Found', value: 'found' },
    { label: 'Correction', value: 'correction' },
    { label: 'Write Off', value: 'writeoff' }
];
var reasonOptions = [
    { label: 'Physical Count Correction', value: 'physical_count' },
    { label: 'Damaged Goods', value: 'damaged' },
    { label: 'Expired Items', value: 'expired' },
    { label: 'Theft/Loss', value: 'theft' },
    { label: 'Wrong Delivery', value: 'wrong_delivery' },
    { label: 'Quality Control', value: 'quality_control' },
    { label: 'Sample/Demo Usage', value: 'sample' },
    { label: 'Other', value: 'other' }
];
var adjustmentTypes = [
    { label: 'Add Stock', value: 'add' },
    { label: 'Deduct Stock', value: 'deduct' }
];
// Computed
// Transform inventory items into flat structure for Select component
var productOptions = (0, vue_1.computed)(function () {
    if (!inventoryItems.value || inventoryItems.value.length === 0) {
        return [];
    }
    var addedIds = form.items.map(function (item) { return item.inventory_item_id; });
    return inventoryItems.value
        .filter(function (item) { return !addedIds.includes(item.id); })
        .map(function (item) {
        var _a, _b, _c;
        return ({
            id: item.id,
            productId: item.product_id,
            variationId: item.variation_id,
            productName: ((_a = item.product) === null || _a === void 0 ? void 0 : _a.product_name) || 'Unknown Product',
            sku: ((_b = item.product) === null || _b === void 0 ? void 0 : _b.sku) || 'N/A',
            stock: item.quantity_on_hand || 0,
            displayName: "".concat(((_c = item.product) === null || _c === void 0 ? void 0 : _c.product_name) || 'Unknown', " (Stock: ").concat(item.quantity_on_hand || 0, ")"),
            // Keep original data for reference
            original: item
        });
    });
});
var selectedProduct = (0, vue_1.computed)(function () {
    if (!newItem.inventory_item_id)
        return null;
    var product = productOptions.value.find(function (item) { return item.id === newItem.inventory_item_id; });
    // Update system quantity when product changes
    if (product && product.stock !== newItem.system_quantity) {
        newItem.system_quantity = product.stock;
    }
    return product;
});
var canAddItem = (0, vue_1.computed)(function () {
    return newItem.inventory_item_id && newItem.quantity > 0;
});
var isFormValid = (0, vue_1.computed)(function () {
    return (form.branch_id &&
        form.type &&
        form.adjustment_date &&
        form.reason &&
        form.items.length > 0);
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
var loadInventoryItems = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!form.branch_id) {
                    inventoryItems.value = [];
                    return [2 /*return*/];
                }
                loadingProducts.value = true;
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, inventory_service_1.default.getBranchInventory(form.branch_id, { per_page: 100 })
                    // Handle nested data structure properly
                ];
            case 2:
                response = _d.sent();
                // Handle nested data structure properly
                if (((_a = response.data) === null || _a === void 0 ? void 0 : _a.success) === true && ((_b = response.data) === null || _b === void 0 ? void 0 : _b.data)) {
                    inventoryItems.value = response.data.data;
                }
                else if ((_c = response.data) === null || _c === void 0 ? void 0 : _c.data) {
                    inventoryItems.value = response.data.data;
                }
                else if (Array.isArray(response.data)) {
                    inventoryItems.value = response.data;
                }
                else {
                    inventoryItems.value = [];
                }
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
                error_2 = _d.sent();
                console.error('Failed to load inventory items:', error_2);
                inventoryItems.value = [];
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
var onBranchChange = function () {
    // Reset products and items when branch changes
    inventoryItems.value = [];
    form.items = [];
    newItem.inventory_item_id = null;
    newItem.system_quantity = 0;
    if (form.branch_id) {
        loadInventoryItems();
    }
};
var getProductName = function (inventoryItemId) {
    var _a;
    if (!inventoryItems.value || inventoryItems.value.length === 0)
        return "Item #".concat(inventoryItemId);
    var item = inventoryItems.value.find(function (i) { return i.id === inventoryItemId; });
    return ((_a = item === null || item === void 0 ? void 0 : item.product) === null || _a === void 0 ? void 0 : _a.product_name) || "Item #".concat(inventoryItemId);
};
var getProductSku = function (inventoryItemId) {
    var _a;
    if (!inventoryItems.value || inventoryItems.value.length === 0)
        return '';
    var item = inventoryItems.value.find(function (i) { return i.id === inventoryItemId; });
    return ((_a = item === null || item === void 0 ? void 0 : item.product) === null || _a === void 0 ? void 0 : _a.sku) || '';
};
var getCurrentStock = function (inventoryItemId) {
    if (!inventoryItems.value || inventoryItems.value.length === 0)
        return 0;
    var item = inventoryItems.value.find(function (i) { return i.id === inventoryItemId; });
    return (item === null || item === void 0 ? void 0 : item.quantity_on_hand) || 0;
};
var calculateNewQuantity = function (item) {
    var currentStock = getCurrentStock(item.inventory_item_id);
    if (item.adjustment_type === 'add') {
        return currentStock + item.quantity;
    }
    else {
        return currentStock - item.quantity;
    }
};
var addItem = function () {
    if (!canAddItem.value)
        return;
    form.items.push({
        inventory_item_id: newItem.inventory_item_id,
        adjustment_type: newItem.adjustment_type,
        quantity: newItem.quantity,
        remarks: newItem.remarks || undefined
    });
    toast.add({
        severity: 'success',
        summary: 'Item Added',
        detail: "".concat(getProductName(newItem.inventory_item_id), " has been added to the adjustment"),
        life: 2000
    });
    // Reset new item form
    newItem.inventory_item_id = null;
    newItem.adjustment_type = 'add';
    newItem.quantity = 1;
    newItem.system_quantity = 0;
    newItem.remarks = '';
};
var removeItem = function (index) {
    form.items.splice(index, 1);
    toast.add({
        severity: 'info',
        summary: 'Item Removed',
        detail: "Item has been removed from the adjustment",
        life: 2000
    });
};
var validateForm = function () {
    var isValid = true;
    errors.value = { adjustment_date: '', branch_id: '', type: '', reason: '' };
    if (!form.adjustment_date) {
        errors.value.adjustment_date = 'Adjustment date is required';
        isValid = false;
    }
    if (!form.branch_id) {
        errors.value.branch_id = 'Branch is required';
        isValid = false;
    }
    if (!form.type) {
        errors.value.type = 'Adjustment type is required';
        isValid = false;
    }
    if (!form.reason) {
        errors.value.reason = 'Reason is required';
        isValid = false;
    }
    if (form.items.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'No Items',
            detail: 'Please add at least one item to adjust',
            life: 3000
        });
        isValid = false;
    }
    return isValid;
};
var toDateString = function (value) {
    if (!value)
        return '';
    return value.toISOString().split('T')[0];
};
var saveAdjustment = function (submit) { return __awaiter(void 0, void 0, void 0, function () {
    var adjustmentData, response, adjustmentId, error_3, validationErrors_1;
    var _a, _b, _c, _d, _e, _f, _g;
    return __generator(this, function (_h) {
        switch (_h.label) {
            case 0:
                if (!validateForm())
                    return [2 /*return*/];
                if (submit) {
                    submitting.value = true;
                }
                else {
                    savingDraft.value = true;
                }
                _h.label = 1;
            case 1:
                _h.trys.push([1, 3, 4, 5]);
                adjustmentData = {
                    branch_id: form.branch_id,
                    type: form.type,
                    reason: form.reason,
                    adjustment_date: toDateString(form.adjustment_date),
                    remarks: form.remarks || undefined,
                    items: form.items.map(function (item) {
                        // Find the original inventory item to get product_id and variation_id
                        var inventoryItem = inventoryItems.value.find(function (i) { return i.id === item.inventory_item_id; });
                        if (!inventoryItem) {
                            throw new Error("Inventory item ".concat(item.inventory_item_id, " not found"));
                        }
                        return {
                            product_id: inventoryItem.product_id,
                            variation_id: inventoryItem.variation_id,
                            system_quantity: getCurrentStock(item.inventory_item_id),
                            actual_quantity: item.quantity,
                            notes: item.remarks || undefined
                        };
                    })
                };
                return [4 /*yield*/, inventory_service_1.default.createAdjustment(adjustmentData)];
            case 2:
                response = _h.sent();
                adjustmentId = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.id) || ((_c = (_b = response.data) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.id);
                if (!adjustmentId) {
                    throw new Error('No adjustment ID returned');
                }
                toast.add({
                    severity: 'success',
                    summary: !submit ? 'Draft Saved' : 'Adjustment Created',
                    detail: "Adjustment #".concat(adjustmentId, " has been created successfully"),
                    life: 3000
                });
                router.push({ name: 'inventory.adjustments' });
                return [3 /*break*/, 5];
            case 3:
                error_3 = _h.sent();
                console.error('Failed to save adjustment:', error_3);
                // Show detailed validation errors
                if ((_e = (_d = error_3.response) === null || _d === void 0 ? void 0 : _d.data) === null || _e === void 0 ? void 0 : _e.errors) {
                    validationErrors_1 = error_3.response.data.errors;
                    Object.keys(validationErrors_1).forEach(function (key) {
                        toast.add({
                            severity: 'error',
                            summary: 'Validation Error',
                            detail: "".concat(key, ": ").concat(validationErrors_1[key].join(', ')),
                            life: 5000
                        });
                    });
                }
                else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: ((_g = (_f = error_3.response) === null || _f === void 0 ? void 0 : _f.data) === null || _g === void 0 ? void 0 : _g.message) || 'Failed to save adjustment',
                        life: 5000
                    });
                }
                return [3 /*break*/, 5];
            case 4:
                savingDraft.value = false;
                submitting.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var saveDraft = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0: return [4 /*yield*/, saveAdjustment(false)];
            case 1:
                _a.sent();
                return [2 /*return*/];
        }
    });
}); };
var submitAdjustment = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0: return [4 /*yield*/, saveAdjustment(true)];
            case 1:
                _a.sent();
                return [2 /*return*/];
        }
    });
}); };
var cancel = function () {
    if (form.items.length > 0 || form.reason || form.remarks || form.type) {
        showCancelDialog.value = true;
    }
    else {
        router.push({ name: 'inventory.adjustments' });
    }
};
var confirmCancel = function () {
    showCancelDialog.value = false;
    router.push({ name: 'inventory.adjustments' });
};
// Watch for product selection to show warnings
(0, vue_1.watch)(function () { return newItem.inventory_item_id; }, function (newVal) {
    if (newVal && selectedProduct.value) {
        var product = selectedProduct.value;
        if (product.stock === 0 && newItem.adjustment_type === 'deduct') {
            toast.add({
                severity: 'warn',
                summary: 'Warning',
                detail: 'This item is currently out of stock. Deducting may result in negative inventory.',
                life: 4000
            });
        }
    }
});
// Lifecycle
(0, vue_1.onMounted)(function () {
    loadBranches();
});
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
            __VLS_ctx.router.push({ name: 'inventory.adjustments' });
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)(__assign({ onSubmit: (__VLS_ctx.submitAdjustment) }, { class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
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
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.branch_id), options: (__VLS_ctx.branches), optionLabel: "name", optionValue: "id", placeholder: "Select branch", loading: (__VLS_ctx.loadingBranches) }), { class: ({ 'p-invalid': __VLS_ctx.errors.branch_id }) }), { fluid: true })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.form.branch_id), options: (__VLS_ctx.branches), optionLabel: "name", optionValue: "id", placeholder: "Select branch", loading: (__VLS_ctx.loadingBranches) }), { class: ({ 'p-invalid': __VLS_ctx.errors.branch_id }) }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ change: {} },
        { onChange: (__VLS_ctx.onBranchChange) });
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    var __VLS_17;
    var __VLS_18;
    if (__VLS_ctx.errors.branch_id) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.branch_id);
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
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign(__assign({ modelValue: (__VLS_ctx.form.type), options: (__VLS_ctx.adjustmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select adjustment type" }, { class: ({ 'p-invalid': __VLS_ctx.errors.type }) }), { fluid: true })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.type), options: (__VLS_ctx.adjustmentTypeOptions), optionLabel: "label", optionValue: "value", placeholder: "Select adjustment type" }, { class: ({ 'p-invalid': __VLS_ctx.errors.type }) }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    if (__VLS_ctx.errors.type) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.type);
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
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
    DatePicker;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign(__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.adjustment_date), dateFormat: "yy-mm-dd" }, { class: "w-full" }), { maxDate: (new Date()) }), { class: ({ 'p-invalid': __VLS_ctx.errors.adjustment_date }) }), { fluid: true })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign(__assign(__assign(__assign({ modelValue: (__VLS_ctx.form.adjustment_date), dateFormat: "yy-mm-dd" }, { class: "w-full" }), { maxDate: (new Date()) }), { class: ({ 'p-invalid': __VLS_ctx.errors.adjustment_date }) }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    if (__VLS_ctx.errors.adjustment_date) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.adjustment_date);
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
    var __VLS_31 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign(__assign({ modelValue: (__VLS_ctx.form.reason), options: (__VLS_ctx.reasonOptions), optionLabel: "label", optionValue: "value", placeholder: "Select reason" }, { class: ({ 'p-invalid': __VLS_ctx.errors.reason }) }), { fluid: true })));
    var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.form.reason), options: (__VLS_ctx.reasonOptions), optionLabel: "label", optionValue: "value", placeholder: "Select reason" }, { class: ({ 'p-invalid': __VLS_ctx.errors.reason }) }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_32), false));
    /** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
    if (__VLS_ctx.errors.reason) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        (__VLS_ctx.errors.reason);
    }
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
    [submitAdjustment, form, form, form, form, branches, loadingBranches, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, errors, onBranchChange, adjustmentTypeOptions, reasonOptions,];
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-4']} */ ;
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
        options: (__VLS_ctx.productOptions),
        optionLabel: "displayName",
        optionValue: "id",
        placeholder: "Select product...",
        loading: (__VLS_ctx.loadingProducts),
        filter: true,
        showClear: true,
        fluid: true,
    }));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.newItem.inventory_item_id),
            options: (__VLS_ctx.productOptions),
            optionLabel: "displayName",
            optionValue: "id",
            placeholder: "Select product...",
            loading: (__VLS_ctx.loadingProducts),
            filter: true,
            showClear: true,
            fluid: true,
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
        (option.productName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (option.sku);
        (option.stock);
        // @ts-ignore
        [newItem, productOptions, loadingProducts,];
    }
    {
        var __VLS_49 = __VLS_45.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-2 text-center text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['p-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_45;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_50 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50({
        modelValue: (__VLS_ctx.newItem.adjustment_type),
        options: (__VLS_ctx.adjustmentTypes),
        optionLabel: "label",
        optionValue: "value",
        placeholder: "Type",
        fluid: true,
    }));
    var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.newItem.adjustment_type),
            options: (__VLS_ctx.adjustmentTypes),
            optionLabel: "label",
            optionValue: "value",
            placeholder: "Type",
            fluid: true,
        }], __VLS_functionalComponentArgsRest(__VLS_51), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    var __VLS_55 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_56 = __VLS_asFunctionalComponent1(__VLS_55, new __VLS_55(__assign(__assign({ modelValue: (__VLS_ctx.newItem.system_quantity), min: (0), disabled: true }, { class: "w-full bg-gray-100" }), { fluid: true })));
    var __VLS_57 = __VLS_56.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.newItem.system_quantity), min: (0), disabled: true }, { class: "w-full bg-gray-100" }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_56), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    InputNumber;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign(__assign({ modelValue: (__VLS_ctx.newItem.quantity), min: (0), showButtons: true, buttonLayout: "horizontal" }, { class: "w-full" }), { fluid: true })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.newItem.quantity), min: (0), showButtons: true, buttonLayout: "horizontal" }, { class: "w-full" }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_61), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2 justify-end md:col-span-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:col-span-2']} */ ;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add", disabled: (!__VLS_ctx.canAddItem) }), { class: "mt-6 w-full" }), { fluid: true })));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-plus", label: "Add", disabled: (!__VLS_ctx.canAddItem) }), { class: "mt-6 w-full" }), { fluid: true })], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = void 0;
    var __VLS_71 = ({ click: {} },
        { onClick: (__VLS_ctx.addItem) });
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_68;
    var __VLS_69;
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
        (__VLS_ctx.selectedProduct.productName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500 ml-2" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
        (__VLS_ctx.selectedProduct.sku);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        (__VLS_ctx.selectedProduct.stock);
        if (__VLS_ctx.newItem.adjustment_type === 'deduct' && __VLS_ctx.selectedProduct.stock < __VLS_ctx.newItem.quantity) {
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
    var __VLS_72 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72(__assign(__assign({ value: (__VLS_ctx.form.items) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })));
    var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.form.items) }, { class: "p-datatable-sm" }), { stripedRows: true, showGridlines: true })], __VLS_functionalComponentArgsRest(__VLS_73), false));
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_77 = __VLS_75.slots.default;
    {
        var __VLS_78 = __VLS_75.slots.empty;
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
        [form, newItem, newItem, newItem, newItem, newItem, adjustmentTypes, canAddItem, addItem, selectedProduct, selectedProduct, selectedProduct, selectedProduct, selectedProduct,];
    }
    var __VLS_79 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_80 = __VLS_asFunctionalComponent1(__VLS_79, new __VLS_79(__assign({ header: "Product" }, { style: {} })));
    var __VLS_81 = __VLS_80.apply(void 0, __spreadArray([__assign({ header: "Product" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_80), false));
    var __VLS_84 = __VLS_82.slots.default;
    {
        var __VLS_85 = __VLS_82.slots.body;
        var data = __VLS_vSlot(__VLS_85)[0].data;
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
    var __VLS_82;
    var __VLS_86 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_87 = __VLS_asFunctionalComponent1(__VLS_86, new __VLS_86(__assign({ field: "adjustment_type", header: "Type" }, { style: {} })));
    var __VLS_88 = __VLS_87.apply(void 0, __spreadArray([__assign({ field: "adjustment_type", header: "Type" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_87), false));
    var __VLS_91 = __VLS_89.slots.default;
    {
        var __VLS_92 = __VLS_89.slots.body;
        var data = __VLS_vSlot(__VLS_92)[0].data;
        var __VLS_93 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({
            severity: (data.adjustment_type === 'add' ? 'success' : 'danger'),
            value: (data.adjustment_type === 'add' ? 'ADD' : 'DEDUCT'),
        }));
        var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{
                severity: (data.adjustment_type === 'add' ? 'success' : 'danger'),
                value: (data.adjustment_type === 'add' ? 'ADD' : 'DEDUCT'),
            }], __VLS_functionalComponentArgsRest(__VLS_94), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_89;
    var __VLS_98 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98(__assign({ header: "System Qty" }, { style: {} })));
    var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([__assign({ header: "System Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_99), false));
    var __VLS_103 = __VLS_101.slots.default;
    {
        var __VLS_104 = __VLS_101.slots.body;
        var data = __VLS_vSlot(__VLS_104)[0].data;
        (__VLS_ctx.getCurrentStock(data.inventory_item_id));
        // @ts-ignore
        [getCurrentStock,];
    }
    // @ts-ignore
    [];
    var __VLS_101;
    var __VLS_105 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105(__assign({ field: "quantity", header: "Actual Qty" }, { style: {} })));
    var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([__assign({ field: "quantity", header: "Actual Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_106), false));
    var __VLS_110 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ header: "New Qty" }, { style: {} })));
    var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ header: "New Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_111), false));
    var __VLS_115 = __VLS_113.slots.default;
    {
        var __VLS_116 = __VLS_113.slots.body;
        var data = __VLS_vSlot(__VLS_116)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ({
                'text-green-600 font-medium': data.adjustment_type === 'add',
                'text-red-600 font-medium': data.adjustment_type === 'deduct'
            }) }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.calculateNewQuantity(data));
        // @ts-ignore
        [calculateNewQuantity,];
    }
    // @ts-ignore
    [];
    var __VLS_113;
    var __VLS_117 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ header: "Variance" }, { style: {} })));
    var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ header: "Variance" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_118), false));
    var __VLS_122 = __VLS_120.slots.default;
    {
        var __VLS_123 = __VLS_120.slots.body;
        var data = __VLS_vSlot(__VLS_123)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ({
                'text-green-600': data.quantity > __VLS_ctx.getCurrentStock(data.inventory_item_id),
                'text-red-600': data.quantity < __VLS_ctx.getCurrentStock(data.inventory_item_id),
                'text-gray-600': data.quantity === __VLS_ctx.getCurrentStock(data.inventory_item_id)
            }) }));
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (data.quantity - __VLS_ctx.getCurrentStock(data.inventory_item_id));
        // @ts-ignore
        [getCurrentStock, getCurrentStock, getCurrentStock, getCurrentStock,];
    }
    // @ts-ignore
    [];
    var __VLS_120;
    var __VLS_124 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ field: "remarks", header: "Notes" }, { style: {} })));
    var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ field: "remarks", header: "Notes" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_125), false));
    var __VLS_129 = __VLS_127.slots.default;
    {
        var __VLS_130 = __VLS_127.slots.body;
        var data = __VLS_vSlot(__VLS_130)[0].data;
        (data.remarks || '-');
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_127;
    var __VLS_131 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_132), false));
    var __VLS_136 = __VLS_134.slots.default;
    {
        var __VLS_137 = __VLS_134.slots.body;
        var index_1 = __VLS_vSlot(__VLS_137)[0].index;
        var __VLS_138 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })));
        var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, rounded: true, severity: "danger", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_139), false));
        var __VLS_143 = void 0;
        var __VLS_144 = ({ click: {} },
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
        var __VLS_141;
        var __VLS_142;
        // @ts-ignore
        [vTooltip,];
    }
    // @ts-ignore
    [];
    var __VLS_134;
    // @ts-ignore
    [];
    var __VLS_75;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "text-sm font-semibold text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    var __VLS_145 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    Textarea;
    // @ts-ignore
    var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145({
        modelValue: (__VLS_ctx.form.remarks),
        rows: "2",
        placeholder: "Any additional notes about this adjustment...",
        fluid: true,
    }));
    var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.form.remarks),
            rows: "2",
            placeholder: "Any additional notes about this adjustment...",
            fluid: true,
        }], __VLS_functionalComponentArgsRest(__VLS_146), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "pt-4 flex gap-2 justify-end border-t border-gray-200" }));
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
    var __VLS_150 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_151 = __VLS_asFunctionalComponent1(__VLS_150, new __VLS_150(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, type: "button", fluid: true })));
    var __VLS_152 = __VLS_151.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true, type: "button", fluid: true })], __VLS_functionalComponentArgsRest(__VLS_151), false));
    var __VLS_155 = void 0;
    var __VLS_156 = ({ click: {} },
        { onClick: (__VLS_ctx.cancel) });
    var __VLS_153;
    var __VLS_154;
    var __VLS_157 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157(__assign({ 'onClick': {} }, { label: "Save as Draft", severity: "secondary", type: "button", loading: (__VLS_ctx.savingDraft), disabled: (__VLS_ctx.form.items.length === 0), fluid: true })));
    var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save as Draft", severity: "secondary", type: "button", loading: (__VLS_ctx.savingDraft), disabled: (__VLS_ctx.form.items.length === 0), fluid: true })], __VLS_functionalComponentArgsRest(__VLS_158), false));
    var __VLS_162 = void 0;
    var __VLS_163 = ({ click: {} },
        { onClick: (__VLS_ctx.saveDraft) });
    var __VLS_160;
    var __VLS_161;
    var __VLS_164 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164({
        label: "Submit Adjustment",
        icon: "pi pi-check",
        loading: (__VLS_ctx.submitting),
        type: "submit",
        disabled: (!__VLS_ctx.isFormValid),
        fluid: true,
    }));
    var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([{
            label: "Submit Adjustment",
            icon: "pi pi-check",
            loading: (__VLS_ctx.submitting),
            type: "submit",
            disabled: (!__VLS_ctx.isFormValid),
            fluid: true,
        }], __VLS_functionalComponentArgsRest(__VLS_165), false));
    // @ts-ignore
    [form, form, cancel, savingDraft, saveDraft, submitting, isFormValid,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_169;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_170 = __VLS_asFunctionalComponent1(__VLS_169, new __VLS_169(__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Discard Changes", modal: (true) }, { class: "w-full sm:w-96" })));
var __VLS_171 = __VLS_170.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showCancelDialog), header: "Discard Changes", modal: (true) }, { class: "w-full sm:w-96" })], __VLS_functionalComponentArgsRest(__VLS_170), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['sm:w-96']} */ ;
var __VLS_174 = __VLS_172.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-end gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-end']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_175;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_176 = __VLS_asFunctionalComponent1(__VLS_175, new __VLS_175(__assign({ 'onClick': {} }, { label: "No, Stay", severity: "secondary", outlined: true })));
var __VLS_177 = __VLS_176.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "No, Stay", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_176), false));
var __VLS_180;
var __VLS_181 = ({ click: {} },
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
var __VLS_178;
var __VLS_179;
var __VLS_182;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_183 = __VLS_asFunctionalComponent1(__VLS_182, new __VLS_182(__assign({ 'onClick': {} }, { label: "Yes, Discard", severity: "danger" })));
var __VLS_184 = __VLS_183.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Yes, Discard", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_183), false));
var __VLS_187;
var __VLS_188 = ({ click: {} },
    { onClick: (__VLS_ctx.confirmCancel) });
var __VLS_185;
var __VLS_186;
// @ts-ignore
[confirmCancel,];
var __VLS_172;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
