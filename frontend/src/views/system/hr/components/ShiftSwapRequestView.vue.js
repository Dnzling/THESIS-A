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
var props = defineProps();
var emit = defineEmits();
// State
var filters = (0, vue_1.ref)({
    search: '',
    status: null,
    type: null
});
var showCreateDialog = (0, vue_1.ref)(false);
var showRejectDialogFlag = (0, vue_1.ref)(false);
var selectedRequestId = (0, vue_1.ref)(null);
var rejectReason = (0, vue_1.ref)('');
var submitting = (0, vue_1.ref)(false);
var rejecting = (0, vue_1.ref)(false);
var swapForm = (0, vue_1.ref)({
    receiver_id: null,
    requestor_schedule_id: null,
    receiver_schedule_id: null,
    swap_type: 'full_swap',
    reason: ''
});
// Options
var statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Accepted', value: 'accepted' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Cancelled', value: 'cancelled' }
];
var swapTypeOptions = [
    { label: 'Full Swap', value: 'full_swap' },
    { label: 'Give Away', value: 'give_away' },
    { label: 'Pick Up', value: 'pick_up' }
];
// Mock data - replace with actual API calls
var myUpcomingShifts = (0, vue_1.ref)([
    { label: 'Morning Shift - Mar 15, 2026 (9AM-6PM)', value: 1 },
    { label: 'Evening Shift - Mar 16, 2026 (2PM-10PM)', value: 2 }
]);
var receiverShifts = (0, vue_1.computed)(function () {
    if (!swapForm.value.receiver_id)
        return [];
    return [
        { label: 'Evening Shift - Mar 15, 2026 (2PM-10PM)', value: 3 },
        { label: 'Morning Shift - Mar 16, 2026 (9AM-6PM)', value: 4 }
    ];
});
// Computed
var filteredSwapRequests = (0, vue_1.computed)(function () {
    var filtered = props.swapRequests;
    if (filters.value.search) {
        var search_1 = filters.value.search.toLowerCase();
        filtered = filtered.filter(function (sr) {
            var _a, _b;
            return ((_a = sr.requestor_name) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(search_1)) ||
                ((_b = sr.receiver_name) === null || _b === void 0 ? void 0 : _b.toLowerCase().includes(search_1));
        });
    }
    if (filters.value.status) {
        filtered = filtered.filter(function (sr) { return sr.status === filters.value.status; });
    }
    if (filters.value.type) {
        filtered = filtered.filter(function (sr) { return sr.swap_type === filters.value.type; });
    }
    return filtered;
});
// Methods
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getSwapTypeSeverity = function (type) {
    var map = {
        'full_swap': 'info',
        'give_away': 'warning',
        'pick_up': 'success'
    };
    return map[type] || 'secondary';
};
var canAccept = function (request) {
    // Add logic based on user role and request status
    return request.status === 'pending';
};
var canReject = function (request) {
    return request.status === 'pending';
};
var canCancel = function (request) {
    // Only requestor can cancel pending requests
    return request.status === 'pending'; // Add user check
};
var acceptRequest = function (request) {
    emit('accept', request.id);
};
var showRejectDialog = function (request) {
    selectedRequestId.value = request.id;
    showRejectDialogFlag.value = true;
};
var rejectRequest = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!selectedRequestId.value)
                    return [2 /*return*/];
                rejecting.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, emit('reject', selectedRequestId.value, rejectReason.value)];
            case 2:
                _a.sent();
                showRejectDialogFlag.value = false;
                rejectReason.value = '';
                selectedRequestId.value = null;
                return [3 /*break*/, 4];
            case 3:
                rejecting.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); };
var cancelRequest = function (request) {
    emit('cancel', request.id);
};
var viewDetails = function (request) {
    console.log('View details:', request);
};
var submitSwapRequest = function () { return __awaiter(void 0, void 0, void 0, function () {
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                submitting.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, , 3, 4]);
                return [4 /*yield*/, emit('create', swapForm.value)];
            case 2:
                _a.sent();
                showCreateDialog.value = false;
                swapForm.value = {
                    receiver_id: null,
                    requestor_schedule_id: null,
                    receiver_schedule_id: null,
                    swap_type: 'full_swap',
                    reason: ''
                };
                return [3 /*break*/, 4];
            case 3:
                submitting.value = false;
                return [7 /*endfinally*/];
            case 4: return [2 /*return*/];
        }
    });
}); };
var __VLS_ctx = __assign(__assign(__assign(__assign(__assign({}, {}), {}), {}), {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-medium" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
if (__VLS_ctx.pendingCount) {
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    Badge;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({
        value: (__VLS_ctx.pendingCount),
        severity: "warning",
    }));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.pendingCount),
            severity: "warning",
        }], __VLS_functionalComponentArgsRest(__VLS_1), false));
}
var __VLS_5;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ 'onClick': {} }, { label: "New Swap Request", icon: "pi pi-plus", severity: "info" })));
var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "New Swap Request", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
var __VLS_10;
var __VLS_11 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showCreateDialog = true;
            // @ts-ignore
            [pendingCount, pendingCount, showCreateDialog,];
        } });
var __VLS_8;
var __VLS_9;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
IconField;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12({}));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_13), false));
var __VLS_17 = __VLS_15.slots.default;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.InputIcon} */
InputIcon;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign({ class: "pi pi-search" })));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign({ class: "pi pi-search" })], __VLS_functionalComponentArgsRest(__VLS_19), false));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
var __VLS_23;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
InputText;
// @ts-ignore
var __VLS_24 = __VLS_asFunctionalComponent1(__VLS_23, new __VLS_23({
    modelValue: (__VLS_ctx.filters.search),
    placeholder: "Search employee...",
    size: "small",
}));
var __VLS_25 = __VLS_24.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.search),
        placeholder: "Search employee...",
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_24), false));
// @ts-ignore
[filters,];
var __VLS_15;
var __VLS_28;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28({
    modelValue: (__VLS_ctx.filters.status),
    options: (__VLS_ctx.statusOptions),
    placeholder: "All Status",
    showClear: true,
    size: "small",
}));
var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.status),
        options: (__VLS_ctx.statusOptions),
        placeholder: "All Status",
        showClear: true,
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_29), false));
var __VLS_33;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33({
    modelValue: (__VLS_ctx.filters.type),
    options: (__VLS_ctx.swapTypeOptions),
    placeholder: "Swap Type",
    showClear: true,
    size: "small",
}));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.filters.type),
        options: (__VLS_ctx.swapTypeOptions),
        placeholder: "Swap Type",
        showClear: true,
        size: "small",
    }], __VLS_functionalComponentArgsRest(__VLS_34), false));
var __VLS_38;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
DataTable;
// @ts-ignore
var __VLS_39 = __VLS_asFunctionalComponent1(__VLS_38, new __VLS_38(__assign({ value: (__VLS_ctx.filteredSwapRequests), loading: (__VLS_ctx.loading), paginator: (true), rows: (10) }, { class: "text-sm" })));
var __VLS_40 = __VLS_39.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.filteredSwapRequests), loading: (__VLS_ctx.loading), paginator: (true), rows: (10) }, { class: "text-sm" })], __VLS_functionalComponentArgsRest(__VLS_39), false));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
var __VLS_43 = __VLS_41.slots.default;
var __VLS_44;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_45 = __VLS_asFunctionalComponent1(__VLS_44, new __VLS_44({
    field: "requestor_name",
    header: "Requestor",
    sortable: (true),
}));
var __VLS_46 = __VLS_45.apply(void 0, __spreadArray([{
        field: "requestor_name",
        header: "Requestor",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_45), false));
var __VLS_49 = __VLS_47.slots.default;
{
    var __VLS_50 = __VLS_47.slots.body;
    var data = __VLS_vSlot(__VLS_50)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_51 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ label: (__VLS_ctx.getInitials(data.requestor_name)), size: "small" }, { class: "bg-blue-100 text-blue-600" })));
    var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.requestor_name)), size: "small" }, { class: "bg-blue-100 text-blue-600" })], __VLS_functionalComponentArgsRest(__VLS_52), false));
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.requestor_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (data.requestor_shift);
    // @ts-ignore
    [filters, filters, statusOptions, swapTypeOptions, filteredSwapRequests, loading, getInitials,];
}
// @ts-ignore
[];
var __VLS_47;
var __VLS_56;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56({
    header: "Swap Type",
}));
var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([{
        header: "Swap Type",
    }], __VLS_functionalComponentArgsRest(__VLS_57), false));
var __VLS_61 = __VLS_59.slots.default;
{
    var __VLS_62 = __VLS_59.slots.body;
    var data = __VLS_vSlot(__VLS_62)[0].data;
    var __VLS_63 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({
        value: (data.swap_type),
        severity: (__VLS_ctx.getSwapTypeSeverity(data.swap_type)),
        rounded: true,
    }));
    var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{
            value: (data.swap_type),
            severity: (__VLS_ctx.getSwapTypeSeverity(data.swap_type)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_64), false));
    // @ts-ignore
    [getSwapTypeSeverity,];
}
// @ts-ignore
[];
var __VLS_59;
var __VLS_68;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
    field: "receiver_name",
    header: "Receiver",
    sortable: (true),
}));
var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
        field: "receiver_name",
        header: "Receiver",
        sortable: (true),
    }], __VLS_functionalComponentArgsRest(__VLS_69), false));
var __VLS_73 = __VLS_71.slots.default;
{
    var __VLS_74 = __VLS_71.slots.body;
    var data = __VLS_vSlot(__VLS_74)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_75 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Avatar} */
    Avatar;
    // @ts-ignore
    var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75(__assign({ label: (__VLS_ctx.getInitials(data.receiver_name)), size: "small" }, { class: "bg-purple-100 text-purple-600" })));
    var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(data.receiver_name)), size: "small" }, { class: "bg-purple-100 text-purple-600" })], __VLS_functionalComponentArgsRest(__VLS_76), false));
    /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    (data.receiver_name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-xs text-gray-400" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    (data.receiver_shift);
    // @ts-ignore
    [getInitials,];
}
// @ts-ignore
[];
var __VLS_71;
var __VLS_80;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80({
    field: "swap_date",
    header: "Date",
}));
var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([{
        field: "swap_date",
        header: "Date",
    }], __VLS_functionalComponentArgsRest(__VLS_81), false));
var __VLS_85 = __VLS_83.slots.default;
{
    var __VLS_86 = __VLS_83.slots.body;
    var data = __VLS_vSlot(__VLS_86)[0].data;
    (data.swap_date);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_83;
var __VLS_87;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
    field: "reason",
    header: "Reason",
}));
var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
        field: "reason",
        header: "Reason",
    }], __VLS_functionalComponentArgsRest(__VLS_88), false));
var __VLS_92 = __VLS_90.slots.default;
{
    var __VLS_93 = __VLS_90.slots.body;
    var data = __VLS_vSlot(__VLS_93)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (data.reason || '—');
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_90;
var __VLS_94;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_95 = __VLS_asFunctionalComponent1(__VLS_94, new __VLS_94({
    field: "status_badge",
    header: "Status",
}));
var __VLS_96 = __VLS_95.apply(void 0, __spreadArray([{
        field: "status_badge",
        header: "Status",
    }], __VLS_functionalComponentArgsRest(__VLS_95), false));
var __VLS_99 = __VLS_97.slots.default;
{
    var __VLS_100 = __VLS_97.slots.body;
    var data = __VLS_vSlot(__VLS_100)[0].data;
    var __VLS_101 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    Tag;
    // @ts-ignore
    var __VLS_102 = __VLS_asFunctionalComponent1(__VLS_101, new __VLS_101({
        value: (data.status_badge.label),
        severity: (data.status_badge.color),
        rounded: true,
    }));
    var __VLS_103 = __VLS_102.apply(void 0, __spreadArray([{
            value: (data.status_badge.label),
            severity: (data.status_badge.color),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_102), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_97;
var __VLS_106;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
Column;
// @ts-ignore
var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ header: "Actions" }, { style: {} })));
var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_107), false));
var __VLS_111 = __VLS_109.slots.default;
{
    var __VLS_112 = __VLS_109.slots.body;
    var data_1 = __VLS_vSlot(__VLS_112)[0].data;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-1" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-1']} */ ;
    if (__VLS_ctx.canAccept(data_1)) {
        var __VLS_113 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_114 = __VLS_asFunctionalComponent1(__VLS_113, new __VLS_113(__assign({ 'onClick': {} }, { icon: "pi pi-check", text: true, rounded: true, severity: "success", size: "small", tooltip: "Accept" })));
        var __VLS_115 = __VLS_114.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", text: true, rounded: true, severity: "success", size: "small", tooltip: "Accept" })], __VLS_functionalComponentArgsRest(__VLS_114), false));
        var __VLS_118 = void 0;
        var __VLS_119 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canAccept(data_1)))
                        return;
                    __VLS_ctx.acceptRequest(data_1);
                    // @ts-ignore
                    [canAccept, acceptRequest,];
                } });
        var __VLS_116;
        var __VLS_117;
    }
    if (__VLS_ctx.canReject(data_1)) {
        var __VLS_120 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_121 = __VLS_asFunctionalComponent1(__VLS_120, new __VLS_120(__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, severity: "danger", size: "small", tooltip: "Reject" })));
        var __VLS_122 = __VLS_121.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", text: true, rounded: true, severity: "danger", size: "small", tooltip: "Reject" })], __VLS_functionalComponentArgsRest(__VLS_121), false));
        var __VLS_125 = void 0;
        var __VLS_126 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canReject(data_1)))
                        return;
                    __VLS_ctx.showRejectDialog(data_1);
                    // @ts-ignore
                    [canReject, showRejectDialog,];
                } });
        var __VLS_123;
        var __VLS_124;
    }
    if (__VLS_ctx.canCancel(data_1)) {
        var __VLS_127 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_128 = __VLS_asFunctionalComponent1(__VLS_127, new __VLS_127(__assign({ 'onClick': {} }, { icon: "pi pi-undo", text: true, rounded: true, severity: "warning", size: "small", tooltip: "Cancel" })));
        var __VLS_129 = __VLS_128.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-undo", text: true, rounded: true, severity: "warning", size: "small", tooltip: "Cancel" })], __VLS_functionalComponentArgsRest(__VLS_128), false));
        var __VLS_132 = void 0;
        var __VLS_133 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.canCancel(data_1)))
                        return;
                    __VLS_ctx.cancelRequest(data_1);
                    // @ts-ignore
                    [canCancel, cancelRequest,];
                } });
        var __VLS_130;
        var __VLS_131;
    }
    var __VLS_134 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134(__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small", tooltip: "View" })));
    var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", text: true, rounded: true, severity: "info", size: "small", tooltip: "View" })], __VLS_functionalComponentArgsRest(__VLS_135), false));
    var __VLS_139 = void 0;
    var __VLS_140 = ({ click: {} },
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
    var __VLS_137;
    var __VLS_138;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_109;
// @ts-ignore
[];
var __VLS_41;
var __VLS_141;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_142 = __VLS_asFunctionalComponent1(__VLS_141, new __VLS_141(__assign({ visible: (__VLS_ctx.showCreateDialog), header: "New Shift Swap Request", modal: true }, { style: ({ width: '600px' }) })));
var __VLS_143 = __VLS_142.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showCreateDialog), header: "New Shift Swap Request", modal: true }, { style: ({ width: '600px' }) })], __VLS_functionalComponentArgsRest(__VLS_142), false));
var __VLS_146 = __VLS_144.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4 p-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_147;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_148 = __VLS_asFunctionalComponent1(__VLS_147, new __VLS_147(__assign(__assign({ modelValue: (__VLS_ctx.swapForm.receiver_id), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employee to swap with" }, { class: "w-full" }), { filter: true })));
var __VLS_149 = __VLS_148.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.swapForm.receiver_id), options: (__VLS_ctx.employees), optionLabel: "label", optionValue: "value", placeholder: "Select employee to swap with" }, { class: "w-full" }), { filter: true })], __VLS_functionalComponentArgsRest(__VLS_148), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
var __VLS_152;
/** @ts-ignore @type {typeof __VLS_components.SelectButton} */
SelectButton;
// @ts-ignore
var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152({
    modelValue: (__VLS_ctx.swapForm.swap_type),
    options: (__VLS_ctx.swapTypeOptions),
    optionLabel: "label",
    optionValue: "value",
}));
var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.swapForm.swap_type),
        options: (__VLS_ctx.swapTypeOptions),
        optionLabel: "label",
        optionValue: "value",
    }], __VLS_functionalComponentArgsRest(__VLS_153), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-3" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_157;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_158 = __VLS_asFunctionalComponent1(__VLS_157, new __VLS_157(__assign({ modelValue: (__VLS_ctx.swapForm.requestor_schedule_id), options: (__VLS_ctx.myUpcomingShifts), optionLabel: "label", optionValue: "value", placeholder: "Select your shift" }, { class: "w-full" })));
var __VLS_159 = __VLS_158.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.requestor_schedule_id), options: (__VLS_ctx.myUpcomingShifts), optionLabel: "label", optionValue: "value", placeholder: "Select your shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_158), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-red-500" }));
/** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
var __VLS_162;
/** @ts-ignore @type {typeof __VLS_components.Select} */
Select;
// @ts-ignore
var __VLS_163 = __VLS_asFunctionalComponent1(__VLS_162, new __VLS_162(__assign({ modelValue: (__VLS_ctx.swapForm.receiver_schedule_id), options: (__VLS_ctx.receiverShifts), optionLabel: "label", optionValue: "value", placeholder: "Select their shift" }, { class: "w-full" })));
var __VLS_164 = __VLS_163.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.swapForm.receiver_schedule_id), options: (__VLS_ctx.receiverShifts), optionLabel: "label", optionValue: "value", placeholder: "Select their shift" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_163), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_167;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign(__assign({ modelValue: (__VLS_ctx.swapForm.reason), rows: "3" }, { class: "w-full" }), { placeholder: "Why do you want to swap?" })));
var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.swapForm.reason), rows: "3" }, { class: "w-full" }), { placeholder: "Why do you want to swap?" })], __VLS_functionalComponentArgsRest(__VLS_168), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_172 = __VLS_144.slots.footer;
    var __VLS_173 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_174 = __VLS_asFunctionalComponent1(__VLS_173, new __VLS_173(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })));
    var __VLS_175 = __VLS_174.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_174), false));
    var __VLS_178 = void 0;
    var __VLS_179 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showCreateDialog = false;
                // @ts-ignore
                [showCreateDialog, showCreateDialog, swapTypeOptions, swapForm, swapForm, swapForm, swapForm, swapForm, employees, myUpcomingShifts, receiverShifts,];
            } });
    var __VLS_176;
    var __VLS_177;
    var __VLS_180 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_181 = __VLS_asFunctionalComponent1(__VLS_180, new __VLS_180(__assign({ 'onClick': {} }, { label: "Submit Request", icon: "pi pi-send", severity: "info", loading: (__VLS_ctx.submitting) })));
    var __VLS_182 = __VLS_181.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Submit Request", icon: "pi pi-send", severity: "info", loading: (__VLS_ctx.submitting) })], __VLS_functionalComponentArgsRest(__VLS_181), false));
    var __VLS_185 = void 0;
    var __VLS_186 = ({ click: {} },
        { onClick: (__VLS_ctx.submitSwapRequest) });
    var __VLS_183;
    var __VLS_184;
    // @ts-ignore
    [submitting, submitSwapRequest,];
}
// @ts-ignore
[];
var __VLS_144;
var __VLS_187;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_188 = __VLS_asFunctionalComponent1(__VLS_187, new __VLS_187(__assign({ visible: (__VLS_ctx.showRejectDialogFlag), header: "Reject Swap Request", modal: true }, { style: ({ width: '400px' }) })));
var __VLS_189 = __VLS_188.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showRejectDialogFlag), header: "Reject Swap Request", modal: true }, { style: ({ width: '400px' }) })], __VLS_functionalComponentArgsRest(__VLS_188), false));
var __VLS_192 = __VLS_190.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3 p-2" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
/** @type {__VLS_StyleScopedClasses['p-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "field" }));
/** @type {__VLS_StyleScopedClasses['field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_193;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
Textarea;
// @ts-ignore
var __VLS_194 = __VLS_asFunctionalComponent1(__VLS_193, new __VLS_193(__assign(__assign({ modelValue: (__VLS_ctx.rejectReason), rows: "2" }, { class: "w-full" }), { placeholder: "Optional rejection reason" })));
var __VLS_195 = __VLS_194.apply(void 0, __spreadArray([__assign(__assign({ modelValue: (__VLS_ctx.rejectReason), rows: "2" }, { class: "w-full" }), { placeholder: "Optional rejection reason" })], __VLS_functionalComponentArgsRest(__VLS_194), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_198 = __VLS_190.slots.footer;
    var __VLS_199 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_200 = __VLS_asFunctionalComponent1(__VLS_199, new __VLS_199(__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })));
    var __VLS_201 = __VLS_200.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", icon: "pi pi-times", text: true })], __VLS_functionalComponentArgsRest(__VLS_200), false));
    var __VLS_204 = void 0;
    var __VLS_205 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showRejectDialogFlag = false;
                // @ts-ignore
                [showRejectDialogFlag, showRejectDialogFlag, rejectReason,];
            } });
    var __VLS_202;
    var __VLS_203;
    var __VLS_206 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_207 = __VLS_asFunctionalComponent1(__VLS_206, new __VLS_206(__assign({ 'onClick': {} }, { label: "Reject Request", icon: "pi pi-times", severity: "danger", loading: (__VLS_ctx.rejecting) })));
    var __VLS_208 = __VLS_207.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject Request", icon: "pi pi-times", severity: "danger", loading: (__VLS_ctx.rejecting) })], __VLS_functionalComponentArgsRest(__VLS_207), false));
    var __VLS_211 = void 0;
    var __VLS_212 = ({ click: {} },
        { onClick: (__VLS_ctx.rejectRequest) });
    var __VLS_209;
    var __VLS_210;
    // @ts-ignore
    [rejecting, rejectRequest,];
}
// @ts-ignore
[];
var __VLS_190;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({
    __typeEmits: {},
    __typeProps: {},
});
exports.default = {};
