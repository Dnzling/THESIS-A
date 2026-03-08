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
var auth_1 = require("../../../../stores/auth");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var inputtext_1 = require("primevue/inputtext");
var inputnumber_1 = require("primevue/inputnumber");
var textarea_1 = require("primevue/textarea");
var select_1 = require("primevue/select");
var checkbox_1 = require("primevue/checkbox");
var datepicker_1 = require("primevue/datepicker");
var dialog_1 = require("primevue/dialog");
var skeleton_1 = require("primevue/skeleton");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var toast = (0, usetoast_1.useToast)();
var authStore = (0, auth_1.useAuthStore)();
// State
var pricingRules = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var saving = (0, vue_1.ref)(false);
var deleting = (0, vue_1.ref)(false);
var dialogVisible = (0, vue_1.ref)(false);
var deleteDialogVisible = (0, vue_1.ref)(false);
var editMode = (0, vue_1.ref)(false);
var currentRule = (0, vue_1.ref)(null);
var searchQuery = (0, vue_1.ref)('');
var stats = (0, vue_1.reactive)({
    totalRules: 0,
    activeRules: 0,
    scheduledRules: 0,
    affectedProducts: 0
});
var filters = (0, vue_1.reactive)({
    rule_type: null,
    status: null,
    sort_by: 'priority_desc'
});
var formData = (0, vue_1.reactive)({
    name: '',
    description: '',
    rule_type: 'discount',
    discount_type: 'percentage',
    discount_value: 0,
    start_date: null,
    end_date: null,
    applies_to: null,
    priority: 0,
    is_active: true,
    conditions: []
});
var errors = (0, vue_1.ref)({});
var ruleTypes = [
    { label: 'Percentage Discount', value: 'discount' },
    { label: 'Flash Sale', value: 'flash_sale' },
    { label: 'Bulk Pricing', value: 'bulk' },
    { label: 'BOGO (Buy One Get One)', value: 'bogo' },
    { label: 'Bundle Discount', value: 'bundle' }
];
var discountTypes = [
    { label: 'Percentage (%)', value: 'percentage' },
    { label: 'Fixed Amount (₱)', value: 'fixed' }
];
var statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Scheduled', value: 'scheduled' },
    { label: 'Expired', value: 'expired' },
    { label: 'Inactive', value: 'inactive' }
];
var sortOptions = [
    { label: 'Priority: High to Low', value: 'priority_desc' },
    { label: 'Priority: Low to High', value: 'priority_asc' },
    { label: 'Newest First', value: 'created_desc' },
    { label: 'Oldest First', value: 'created_asc' }
];
var appliesOptions = [
    'All Products',
    'Specific Category',
    'Specific Products',
    'Minimum Purchase Amount'
];
var activePromotions = (0, vue_1.computed)(function () {
    return pricingRules.value.filter(function (rule) { return rule.is_active && isRuleActive(rule); }).length;
});
// Methods
var loadPricingRules = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        loading.value = true;
        try {
            // Mock data - replace with actual API call
            pricingRules.value = [
                {
                    id: 1,
                    name: 'Summer Sale 2024',
                    description: '20% off on all sofas and chairs',
                    rule_type: 'discount',
                    discount_type: 'percentage',
                    discount_value: 20,
                    start_date: '2024-06-01T00:00:00',
                    end_date: '2024-08-31T23:59:59',
                    applies_to: 'Sofas & Chairs',
                    priority: 10,
                    is_active: true,
                    conditions: ['Category: Sofas', 'Category: Chairs']
                },
                {
                    id: 2,
                    name: 'Bulk Purchase Discount',
                    description: 'Buy 3 or more, get 15% off',
                    rule_type: 'bulk',
                    discount_type: 'percentage',
                    discount_value: 15,
                    start_date: null,
                    end_date: null,
                    applies_to: 'All Products',
                    priority: 5,
                    is_active: true,
                    conditions: ['Minimum Quantity: 3']
                }
            ];
            calculateStats();
        }
        catch (error) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to load pricing rules',
                life: 3000
            });
        }
        finally {
            loading.value = false;
        }
        return [2 /*return*/];
    });
}); };
var calculateStats = function () {
    stats.totalRules = pricingRules.value.length;
    stats.activeRules = pricingRules.value.filter(function (rule) { return rule.is_active; }).length;
    stats.scheduledRules = pricingRules.value.filter(function (rule) { return isScheduled(rule); }).length;
    stats.affectedProducts = 125; // Mock data
};
var onSearch = function () {
    loadPricingRules();
};
var openCreateDialog = function () {
    resetForm();
    editMode.value = false;
    dialogVisible.value = true;
};
var editRule = function (rule) {
    currentRule.value = rule;
    Object.assign(formData, {
        name: rule.name,
        description: rule.description || '',
        rule_type: rule.rule_type,
        discount_type: rule.discount_type,
        discount_value: rule.discount_value,
        start_date: rule.start_date ? new Date(rule.start_date) : null,
        end_date: rule.end_date ? new Date(rule.end_date) : null,
        applies_to: rule.applies_to,
        priority: rule.priority || 0,
        is_active: rule.is_active,
        conditions: rule.conditions || []
    });
    editMode.value = true;
    dialogVisible.value = true;
};
var saveRule = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_1;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!validate())
                    return [2 /*return*/];
                saving.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                // Mock save - replace with actual API call
                return [4 /*yield*/, new Promise(function (resolve) { return setTimeout(resolve, 1000); })];
            case 2:
                // Mock save - replace with actual API call
                _a.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: editMode.value ? 'Pricing rule updated' : 'Pricing rule created',
                    life: 3000
                });
                dialogVisible.value = false;
                loadPricingRules();
                return [3 /*break*/, 5];
            case 3:
                error_1 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to save pricing rule',
                    life: 3000
                });
                return [3 /*break*/, 5];
            case 4:
                saving.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var duplicateRule = function (rule) {
    editRule(__assign(__assign({}, rule), { name: "".concat(rule.name, " (Copy)") }));
    editMode.value = false;
};
var toggleRuleStatus = function (rule) { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        try {
            rule.is_active = !rule.is_active;
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: rule.is_active ? 'Rule activated' : 'Rule deactivated',
                life: 2000
            });
        }
        catch (error) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update rule status',
                life: 3000
            });
        }
        return [2 /*return*/];
    });
}); };
var confirmDelete = function (rule) {
    currentRule.value = rule;
    deleteDialogVisible.value = true;
};
var deleteRule = function () { return __awaiter(void 0, void 0, void 0, function () {
    var error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                deleting.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, new Promise(function (resolve) { return setTimeout(resolve, 1000); })];
            case 2:
                _a.sent();
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Pricing rule deleted',
                    life: 3000
                });
                deleteDialogVisible.value = false;
                loadPricingRules();
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete rule',
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
    if (!formData.name)
        errors.value.name = 'Rule name is required';
    if (!formData.rule_type)
        errors.value.rule_type = 'Rule type is required';
    if (!formData.discount_value)
        errors.value.discount_value = 'Discount value is required';
    return Object.keys(errors.value).length === 0;
};
var resetForm = function () {
    formData.name = '';
    formData.description = '';
    formData.rule_type = 'discount';
    formData.discount_type = 'percentage';
    formData.discount_value = 0;
    formData.start_date = null;
    formData.end_date = null;
    formData.applies_to = null;
    formData.priority = 0;
    formData.is_active = true;
    formData.conditions = [];
    errors.value = {};
};
var getRuleTypeLabel = function (type) {
    var rule = ruleTypes.find(function (r) { return r.value === type; });
    return (rule === null || rule === void 0 ? void 0 : rule.label) || type;
};
var getRuleTypeSeverity = function (type) {
    var severities = {
        'discount': 'info',
        'flash_sale': 'danger',
        'bulk': 'success',
        'bogo': 'warning',
        'bundle': 'secondary'
    };
    return severities[type] || 'info';
};
var getStatusLabel = function (rule) {
    if (!rule.is_active)
        return 'Inactive';
    if (isScheduled(rule))
        return 'Scheduled';
    if (isExpired(rule))
        return 'Expired';
    return 'Active';
};
var getStatusSeverity = function (rule) {
    if (!rule.is_active)
        return 'secondary';
    if (isScheduled(rule))
        return 'warning';
    if (isExpired(rule))
        return 'danger';
    return 'success';
};
var isRuleActive = function (rule) {
    var now = new Date();
    var start = rule.start_date ? new Date(rule.start_date) : null;
    var end = rule.end_date ? new Date(rule.end_date) : null;
    if (start && now < start)
        return false;
    if (end && now > end)
        return false;
    return rule.is_active;
};
var isScheduled = function (rule) {
    if (!rule.start_date)
        return false;
    return new Date(rule.start_date) > new Date();
};
var isExpired = function (rule) {
    if (!rule.end_date)
        return false;
    return new Date(rule.end_date) < new Date();
};
var formatCondition = function (condition) {
    return condition;
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
    loadPricingRules();
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
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Bulk Update", icon: "pi pi-upload", severity: "secondary", outlined: true })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Bulk Update", icon: "pi pi-upload", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.$router.push({ name: 'merchandising.pricing.bulk' });
            // @ts-ignore
            [$router,];
        } });
var __VLS_3;
var __VLS_4;
if (__VLS_ctx.authStore.hasPermission('merchandising.pricing.edit')) {
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Add Pricing Rule", icon: "pi pi-plus" })));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Pricing Rule", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = void 0;
    var __VLS_13 = ({ click: {} },
        { onClick: (__VLS_ctx.openCreateDialog) });
    var __VLS_10;
    var __VLS_11;
}
if (__VLS_ctx.activePromotions > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4" }));
    /** @type {__VLS_StyleScopedClasses['bg-gradient-to-r']} */ ;
    /** @type {__VLS_StyleScopedClasses['from-green-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['to-emerald-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-green-200']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-600 rounded-full p-3" }));
    /** @type {__VLS_StyleScopedClasses['bg-green-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-white text-xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
    /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-green-900" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-900']} */ ;
    (__VLS_ctx.activePromotions);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-green-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-700']} */ ;
}
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
    (__VLS_ctx.stats.totalRules);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-percentage text-blue-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-percentage']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    // @ts-ignore
    [authStore, openCreateDialog, activePromotions, activePromotions, stats,];
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
    (__VLS_ctx.stats.activeRules);
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
    (__VLS_ctx.stats.scheduledRules);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-orange-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-orange-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-purple-600 mt-1" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
    (__VLS_ctx.stats.affectedProducts);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-purple-100 p-3 rounded-full" }));
    /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-purple-600 text-2xl" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
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
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search rules..." }), { class: "w-full" })));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.searchQuery), placeholder: "Search rules..." }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_61), false));
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
    var __VLS_68 = __VLS_asFunctionalComponent1(__VLS_67, new __VLS_67(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.rule_type), options: (__VLS_ctx.ruleTypes), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true })));
    var __VLS_69 = __VLS_68.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.rule_type), options: (__VLS_ctx.ruleTypes), optionLabel: "label", optionValue: "value", placeholder: "All Types", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_68), false));
    var __VLS_72 = void 0;
    var __VLS_73 = ({ change: {} },
        { onChange: (__VLS_ctx.loadPricingRules) });
    var __VLS_70;
    var __VLS_71;
    var __VLS_74 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_75 = __VLS_asFunctionalComponent1(__VLS_74, new __VLS_74(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })));
    var __VLS_76 = __VLS_75.apply(void 0, __spreadArray([__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), optionLabel: "label", optionValue: "value", placeholder: "All Status", showClear: true })], __VLS_functionalComponentArgsRest(__VLS_75), false));
    var __VLS_79 = void 0;
    var __VLS_80 = ({ change: {} },
        { onChange: (__VLS_ctx.loadPricingRules) });
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
        { onChange: (__VLS_ctx.loadPricingRules) });
    var __VLS_84;
    var __VLS_85;
    // @ts-ignore
    [filters, filters, filters, ruleTypes, loadPricingRules, loadPricingRules, loadPricingRules, statusOptions, sortOptions,];
}
// @ts-ignore
[];
var __VLS_45;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _a = __VLS_vFor((5)); _i < _a.length; _i++) {
        var i = _a[_i][0];
        var __VLS_88 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })));
        var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_89), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
else if (__VLS_ctx.pricingRules.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
    var _loop_1 = function (rule) {
        var __VLS_93 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93(__assign({ key: (rule.id) }, { class: "hover:shadow-lg transition-shadow" })));
        var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([__assign({ key: (rule.id) }, { class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_94), false));
        /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
        var __VLS_98 = __VLS_96.slots.default;
        {
            var __VLS_99 = __VLS_96.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
            /** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
            /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-bold text-gray-900" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
            (rule.name);
            var __VLS_100 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_101 = __VLS_asFunctionalComponent1(__VLS_100, new __VLS_100({
                value: (__VLS_ctx.getRuleTypeLabel(rule.rule_type)),
                severity: (__VLS_ctx.getRuleTypeSeverity(rule.rule_type)),
            }));
            var __VLS_102 = __VLS_101.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.getRuleTypeLabel(rule.rule_type)),
                    severity: (__VLS_ctx.getRuleTypeSeverity(rule.rule_type)),
                }], __VLS_functionalComponentArgsRest(__VLS_101), false));
            var __VLS_105 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Tag} */
            tag_1.default;
            // @ts-ignore
            var __VLS_106 = __VLS_asFunctionalComponent1(__VLS_105, new __VLS_105({
                value: (__VLS_ctx.getStatusLabel(rule)),
                severity: (__VLS_ctx.getStatusSeverity(rule)),
            }));
            var __VLS_107 = __VLS_106.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.getStatusLabel(rule)),
                    severity: (__VLS_ctx.getStatusSeverity(rule)),
                }], __VLS_functionalComponentArgsRest(__VLS_106), false));
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mt-2" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
            (rule.description);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
            var __VLS_110 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })));
            var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_111), false));
            var __VLS_115 = void 0;
            var __VLS_116 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.pricingRules.length > 0))
                            return;
                        __VLS_ctx.editRule(rule);
                        // @ts-ignore
                        [pricingRules, pricingRules, getRuleTypeLabel, getRuleTypeSeverity, getStatusLabel, getStatusSeverity, editRule,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Edit') }), null, null);
            var __VLS_117 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ 'onClick': {} }, { icon: "pi pi-copy", severity: "secondary", text: true, rounded: true })));
            var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-copy", severity: "secondary", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_118), false));
            var __VLS_122 = void 0;
            var __VLS_123 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.pricingRules.length > 0))
                            return;
                        __VLS_ctx.duplicateRule(rule);
                        // @ts-ignore
                        [vTooltip, duplicateRule,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Duplicate') }), null, null);
            var __VLS_124 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ 'onClick': {} }, { icon: (rule.is_active ? 'pi pi-pause' : 'pi pi-play'), severity: (rule.is_active ? 'warning' : 'success'), text: true, rounded: true })));
            var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: (rule.is_active ? 'pi pi-pause' : 'pi pi-play'), severity: (rule.is_active ? 'warning' : 'success'), text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_125), false));
            var __VLS_129 = void 0;
            var __VLS_130 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.pricingRules.length > 0))
                            return;
                        __VLS_ctx.toggleRuleStatus(rule);
                        // @ts-ignore
                        [vTooltip, toggleRuleStatus,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: (rule.is_active ? 'Deactivate' : 'Activate') }), null, null);
            var __VLS_131 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })));
            var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_132), false));
            var __VLS_136 = void 0;
            var __VLS_137 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.pricingRules.length > 0))
                            return;
                        __VLS_ctx.confirmDelete(rule);
                        // @ts-ignore
                        [vTooltip, confirmDelete,];
                    } });
            __VLS_asFunctionalDirective(__VLS_directives.vTooltip, {})(null, __assign(__assign({}, __VLS_directiveBindingRestFields), { modifiers: { top: true, }, value: ('Delete') }), null, null);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg" }));
            /** @type {__VLS_StyleScopedClasses['grid']} */ ;
            /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
            /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
            /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-lg font-bold text-green-600" }));
            /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
            (rule.discount_type === 'percentage' ? "".concat(rule.discount_value, "%") : "\u20B1".concat(__VLS_ctx.formatPrice(rule.discount_value)));
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (rule.applies_to || 'All Products');
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold" }));
            /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
            /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
            (rule.start_date ? __VLS_ctx.formatDate(rule.start_date) : 'No start');
            (rule.end_date ? __VLS_ctx.formatDate(rule.end_date) : 'No end');
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mb-1" }));
            /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
            var __VLS_138 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            badge_1.default;
            // @ts-ignore
            var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138({
                value: (rule.priority || 0),
                severity: "info",
            }));
            var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([{
                    value: (rule.priority || 0),
                    severity: "info",
                }], __VLS_functionalComponentArgsRest(__VLS_139), false));
            if (rule.conditions && rule.conditions.length > 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t border-gray-200 pt-4" }));
                /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-gray-200']} */ ;
                /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm font-semibold text-gray-700 mb-2" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
                /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                for (var _d = 0, _e = __VLS_vFor((rule.conditions)); _d < _e.length; _d++) {
                    var _f = _e[_d], condition = _f[0], index = _f[1];
                    var __VLS_143 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    tag_1.default;
                    // @ts-ignore
                    var __VLS_144 = __VLS_asFunctionalComponent1(__VLS_143, new __VLS_143({
                        key: (index),
                        value: (__VLS_ctx.formatCondition(condition)),
                        severity: "secondary",
                    }));
                    var __VLS_145 = __VLS_144.apply(void 0, __spreadArray([{
                            key: (index),
                            value: (__VLS_ctx.formatCondition(condition)),
                            severity: "secondary",
                        }], __VLS_functionalComponentArgsRest(__VLS_144), false));
                    // @ts-ignore
                    [vTooltip, formatPrice, formatDate, formatDate, formatCondition,];
                }
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        // @ts-ignore
        [];
    };
    var __VLS_113, __VLS_114, __VLS_120, __VLS_121, __VLS_127, __VLS_128, __VLS_134, __VLS_135, __VLS_96;
    for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.pricingRules)); _b < _c.length; _b++) {
        var rule = _c[_b][0];
        _loop_1(rule);
    }
}
else {
    var __VLS_148 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_149 = __VLS_asFunctionalComponent1(__VLS_148, new __VLS_148({}));
    var __VLS_150 = __VLS_149.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_149), false));
    var __VLS_153 = __VLS_151.slots.default;
    {
        var __VLS_154 = __VLS_151.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-percentage text-6xl text-gray-300" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-percentage']} */ ;
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
        var __VLS_155 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_156 = __VLS_asFunctionalComponent1(__VLS_155, new __VLS_155(__assign(__assign({ 'onClick': {} }, { label: "Create Your First Rule", icon: "pi pi-plus" }), { class: "mt-4" })));
        var __VLS_157 = __VLS_156.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Create Your First Rule", icon: "pi pi-plus" }), { class: "mt-4" })], __VLS_functionalComponentArgsRest(__VLS_156), false));
        var __VLS_160 = void 0;
        var __VLS_161 = ({ click: {} },
            { onClick: (__VLS_ctx.openCreateDialog) });
        /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
        var __VLS_158;
        var __VLS_159;
        // @ts-ignore
        [openCreateDialog,];
    }
    // @ts-ignore
    [];
    var __VLS_151;
}
var __VLS_162;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_163 = __VLS_asFunctionalComponent1(__VLS_162, new __VLS_162(__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Pricing Rule' : 'Create Pricing Rule'), modal: (true) }, { class: "w-full max-w-3xl" })));
var __VLS_164 = __VLS_163.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.dialogVisible), header: (__VLS_ctx.editMode ? 'Edit Pricing Rule' : 'Create Pricing Rule'), modal: (true) }, { class: "w-full max-w-3xl" })], __VLS_functionalComponentArgsRest(__VLS_163), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
/** @type {__VLS_StyleScopedClasses['max-w-3xl']} */ ;
var __VLS_167 = __VLS_165.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "rule_name" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_168;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_169 = __VLS_asFunctionalComponent1(__VLS_168, new __VLS_168(__assign({ id: "rule_name", modelValue: (__VLS_ctx.formData.name), placeholder: "e.g., Summer Sale 2024" }, { class: ({ 'p-invalid': __VLS_ctx.errors.name }) })));
var __VLS_170 = __VLS_169.apply(void 0, __spreadArray([__assign({ id: "rule_name", modelValue: (__VLS_ctx.formData.name), placeholder: "e.g., Summer Sale 2024" }, { class: ({ 'p-invalid': __VLS_ctx.errors.name }) })], __VLS_functionalComponentArgsRest(__VLS_169), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
if (__VLS_ctx.errors.name) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-red-500" }));
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    (__VLS_ctx.errors.name);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "description" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_173;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_174 = __VLS_asFunctionalComponent1(__VLS_173, new __VLS_173({
    id: "description",
    modelValue: (__VLS_ctx.formData.description),
    rows: "2",
    placeholder: "Optional description...",
}));
var __VLS_175 = __VLS_174.apply(void 0, __spreadArray([{
        id: "description",
        modelValue: (__VLS_ctx.formData.description),
        rows: "2",
        placeholder: "Optional description...",
    }], __VLS_functionalComponentArgsRest(__VLS_174), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "rule_type" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_178;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_179 = __VLS_asFunctionalComponent1(__VLS_178, new __VLS_178(__assign({ id: "rule_type", modelValue: (__VLS_ctx.formData.rule_type), options: (__VLS_ctx.ruleTypes), optionLabel: "label", optionValue: "value", placeholder: "Select type" }, { class: ({ 'p-invalid': __VLS_ctx.errors.rule_type }) })));
var __VLS_180 = __VLS_179.apply(void 0, __spreadArray([__assign({ id: "rule_type", modelValue: (__VLS_ctx.formData.rule_type), options: (__VLS_ctx.ruleTypes), optionLabel: "label", optionValue: "value", placeholder: "Select type" }, { class: ({ 'p-invalid': __VLS_ctx.errors.rule_type }) })], __VLS_functionalComponentArgsRest(__VLS_179), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "discount_type" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_183;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_184 = __VLS_asFunctionalComponent1(__VLS_183, new __VLS_183({
    id: "discount_type",
    modelValue: (__VLS_ctx.formData.discount_type),
    options: (__VLS_ctx.discountTypes),
    optionLabel: "label",
    optionValue: "value",
    placeholder: "Select type",
}));
var __VLS_185 = __VLS_184.apply(void 0, __spreadArray([{
        id: "discount_type",
        modelValue: (__VLS_ctx.formData.discount_type),
        options: (__VLS_ctx.discountTypes),
        optionLabel: "label",
        optionValue: "value",
        placeholder: "Select type",
    }], __VLS_functionalComponentArgsRest(__VLS_184), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "discount_value" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_188;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_189 = __VLS_asFunctionalComponent1(__VLS_188, new __VLS_188(__assign({ id: "discount_value", modelValue: (__VLS_ctx.formData.discount_value), suffix: (__VLS_ctx.formData.discount_type === 'percentage' ? '%' : ''), min: (0), max: (__VLS_ctx.formData.discount_type === 'percentage' ? 100 : undefined) }, { class: ({ 'p-invalid': __VLS_ctx.errors.discount_value }) })));
var __VLS_190 = __VLS_189.apply(void 0, __spreadArray([__assign({ id: "discount_value", modelValue: (__VLS_ctx.formData.discount_value), suffix: (__VLS_ctx.formData.discount_type === 'percentage' ? '%' : ''), min: (0), max: (__VLS_ctx.formData.discount_type === 'percentage' ? 100 : undefined) }, { class: ({ 'p-invalid': __VLS_ctx.errors.discount_value }) })], __VLS_functionalComponentArgsRest(__VLS_189), false));
/** @type {__VLS_StyleScopedClasses['p-invalid']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "start_date" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_193;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
datepicker_1.default;
// @ts-ignore
var __VLS_194 = __VLS_asFunctionalComponent1(__VLS_193, new __VLS_193({
    id: "start_date",
    modelValue: (__VLS_ctx.formData.start_date),
    showTime: true,
    hourFormat: "24",
    placeholder: "Select start date",
}));
var __VLS_195 = __VLS_194.apply(void 0, __spreadArray([{
        id: "start_date",
        modelValue: (__VLS_ctx.formData.start_date),
        showTime: true,
        hourFormat: "24",
        placeholder: "Select start date",
    }], __VLS_functionalComponentArgsRest(__VLS_194), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "end_date" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_198;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
datepicker_1.default;
// @ts-ignore
var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198({
    id: "end_date",
    modelValue: (__VLS_ctx.formData.end_date),
    showTime: true,
    hourFormat: "24",
    placeholder: "Select end date",
}));
var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([{
        id: "end_date",
        modelValue: (__VLS_ctx.formData.end_date),
        showTime: true,
        hourFormat: "24",
        placeholder: "Select end date",
    }], __VLS_functionalComponentArgsRest(__VLS_199), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "applies_to" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_203;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_204 = __VLS_asFunctionalComponent1(__VLS_203, new __VLS_203({
    id: "applies_to",
    modelValue: (__VLS_ctx.formData.applies_to),
    options: (__VLS_ctx.appliesOptions),
    placeholder: "All Products",
    showClear: true,
}));
var __VLS_205 = __VLS_204.apply(void 0, __spreadArray([{
        id: "applies_to",
        modelValue: (__VLS_ctx.formData.applies_to),
        options: (__VLS_ctx.appliesOptions),
        placeholder: "All Products",
        showClear: true,
    }], __VLS_functionalComponentArgsRest(__VLS_204), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "priority" }, { class: "text-sm font-semibold text-gray-700" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
var __VLS_208;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_209 = __VLS_asFunctionalComponent1(__VLS_208, new __VLS_208({
    id: "priority",
    modelValue: (__VLS_ctx.formData.priority),
    min: (0),
    showButtons: true,
}));
var __VLS_210 = __VLS_209.apply(void 0, __spreadArray([{
        id: "priority",
        modelValue: (__VLS_ctx.formData.priority),
        min: (0),
        showButtons: true,
    }], __VLS_functionalComponentArgsRest(__VLS_209), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 pt-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
/** @type {__VLS_StyleScopedClasses['pt-6']} */ ;
var __VLS_213;
/** @ts-ignore @type {typeof __VLS_components.Checkbox} */
checkbox_1.default;
// @ts-ignore
var __VLS_214 = __VLS_asFunctionalComponent1(__VLS_213, new __VLS_213({
    modelValue: (__VLS_ctx.formData.is_active),
    inputId: "is_active",
    binary: (true),
}));
var __VLS_215 = __VLS_214.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.formData.is_active),
        inputId: "is_active",
        binary: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_214), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ for: "is_active" }, { class: "text-sm font-semibold text-gray-700 cursor-pointer" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
{
    var __VLS_218 = __VLS_165.slots.footer;
    var __VLS_219 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_220 = __VLS_asFunctionalComponent1(__VLS_219, new __VLS_219(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })));
    var __VLS_221 = __VLS_220.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_220), false));
    var __VLS_224 = void 0;
    var __VLS_225 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.dialogVisible = false;
                // @ts-ignore
                [ruleTypes, dialogVisible, dialogVisible, editMode, formData, formData, formData, formData, formData, formData, formData, formData, formData, formData, formData, formData, errors, errors, errors, errors, errors, discountTypes, appliesOptions,];
            } });
    var __VLS_222;
    var __VLS_223;
    var __VLS_226 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_227 = __VLS_asFunctionalComponent1(__VLS_226, new __VLS_226(__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })));
    var __VLS_228 = __VLS_227.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: (__VLS_ctx.editMode ? 'Update' : 'Create'), icon: "pi pi-check", loading: (__VLS_ctx.saving) })], __VLS_functionalComponentArgsRest(__VLS_227), false));
    var __VLS_231 = void 0;
    var __VLS_232 = ({ click: {} },
        { onClick: (__VLS_ctx.saveRule) });
    var __VLS_229;
    var __VLS_230;
    // @ts-ignore
    [editMode, saving, saveRule,];
}
// @ts-ignore
[];
var __VLS_165;
var __VLS_233;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_234 = __VLS_asFunctionalComponent1(__VLS_233, new __VLS_233(__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })));
var __VLS_235 = __VLS_234.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.deleteDialogVisible), header: "Confirm Delete", modal: (true) }, { class: "w-96" })], __VLS_functionalComponentArgsRest(__VLS_234), false));
/** @type {__VLS_StyleScopedClasses['w-96']} */ ;
var __VLS_238 = __VLS_236.slots.default;
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
    var __VLS_239 = __VLS_236.slots.footer;
    var __VLS_240 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_241 = __VLS_asFunctionalComponent1(__VLS_240, new __VLS_240(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })));
    var __VLS_242 = __VLS_241.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", text: true })], __VLS_functionalComponentArgsRest(__VLS_241), false));
    var __VLS_245 = void 0;
    var __VLS_246 = ({ click: {} },
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
    var __VLS_243;
    var __VLS_244;
    var __VLS_247 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })));
    var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", loading: (__VLS_ctx.deleting) })], __VLS_functionalComponentArgsRest(__VLS_248), false));
    var __VLS_252 = void 0;
    var __VLS_253 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteRule) });
    var __VLS_250;
    var __VLS_251;
    // @ts-ignore
    [deleting, deleteRule,];
}
// @ts-ignore
[];
var __VLS_236;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
