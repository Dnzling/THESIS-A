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
var procurement_service_1 = require("../../../../services/procurement.service");
var router = (0, vue_router_1.useRouter)();
var loading = (0, vue_1.ref)(false);
var suppliers = (0, vue_1.ref)([]);
var filters = (0, vue_1.reactive)({
    search: '',
    status: null
});
var statuses = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Blacklisted', value: 'blacklisted' }
];
var loadSuppliers = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_1;
    var _a;
    return __generator(this, function (_b) {
        switch (_b.label) {
            case 0:
                loading.value = true;
                _b.label = 1;
            case 1:
                _b.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, procurement_service_1.default.getSuppliers(filters)];
            case 2:
                response = _b.sent();
                suppliers.value = ((_a = response.data) === null || _a === void 0 ? void 0 : _a.data) || [];
                return [3 /*break*/, 5];
            case 3:
                error_1 = _b.sent();
                console.error('Failed to load suppliers', error_1);
                suppliers.value = [];
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var statusSeverity = function (status) {
    if (status === 'active')
        return 'success';
    if (status === 'blacklisted')
        return 'danger';
    return 'secondary';
};
var onFilterChange = function () {
    loadSuppliers();
};
var resetFilters = function () {
    filters.search = '';
    filters.status = null;
    loadSuppliers();
};
(0, vue_1.onMounted)(function () {
    loadSuppliers();
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
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Add Supplier", icon: "pi pi-plus", severity: "success" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Supplier", icon: "pi pi-plus", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.router.push({ name: 'procurement.suppliers.create' });
            // @ts-ignore
            [router,];
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
    var __VLS_26 = __VLS_asFunctionalComponent1(__VLS_25, new __VLS_25(__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search supplier" }), { class: "w-full" })));
    var __VLS_27 = __VLS_26.apply(void 0, __spreadArray([__assign(__assign({ 'onInput': {} }, { modelValue: (__VLS_ctx.filters.search), placeholder: "Search supplier" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_26), false));
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
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statuses), optionLabel: "label", optionValue: "value", placeholder: "Status", showClear: true }), { class: "w-full" })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign(__assign({ 'onChange': {} }, { modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statuses), optionLabel: "label", optionValue: "value", placeholder: "Status", showClear: true }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ change: {} },
        { onChange: (__VLS_ctx.onFilterChange) });
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_35;
    var __VLS_36;
    var __VLS_39 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_40 = __VLS_asFunctionalComponent1(__VLS_39, new __VLS_39(__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-filter-slash", severity: "secondary", outlined: true })));
    var __VLS_41 = __VLS_40.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reset", icon: "pi pi-filter-slash", severity: "secondary", outlined: true })], __VLS_functionalComponentArgsRest(__VLS_40), false));
    var __VLS_44 = void 0;
    var __VLS_45 = ({ click: {} },
        { onClick: (__VLS_ctx.resetFilters) });
    var __VLS_42;
    var __VLS_43;
    // @ts-ignore
    [filters, onFilterChange, statuses, resetFilters,];
}
// @ts-ignore
[];
var __VLS_10;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46({}));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51 = __VLS_49.slots.default;
{
    var __VLS_52 = __VLS_49.slots.content;
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53(__assign(__assign({ value: (__VLS_ctx.suppliers), loading: (__VLS_ctx.loading) }, { class: "p-datatable-sm" }), { stripedRows: true })));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.suppliers), loading: (__VLS_ctx.loading) }, { class: "p-datatable-sm" }), { stripedRows: true })], __VLS_functionalComponentArgsRest(__VLS_54), false));
    /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
    var __VLS_58 = __VLS_56.slots.default;
    {
        var __VLS_59 = __VLS_56.slots.empty;
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
        [suppliers, loading,];
    }
    var __VLS_60 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_61 = __VLS_asFunctionalComponent1(__VLS_60, new __VLS_60({
        field: "supplier_name",
        header: "Supplier",
    }));
    var __VLS_62 = __VLS_61.apply(void 0, __spreadArray([{
            field: "supplier_name",
            header: "Supplier",
        }], __VLS_functionalComponentArgsRest(__VLS_61), false));
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65({
        field: "contact_person",
        header: "Contact",
    }));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([{
            field: "contact_person",
            header: "Contact",
        }], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70({
        field: "phone",
        header: "Phone",
    }));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([{
            field: "phone",
            header: "Phone",
        }], __VLS_functionalComponentArgsRest(__VLS_71), false));
    var __VLS_75 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_76 = __VLS_asFunctionalComponent1(__VLS_75, new __VLS_75({
        field: "email",
        header: "Email",
    }));
    var __VLS_77 = __VLS_76.apply(void 0, __spreadArray([{
            field: "email",
            header: "Email",
        }], __VLS_functionalComponentArgsRest(__VLS_76), false));
    var __VLS_80 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80({
        field: "status",
        header: "Status",
    }));
    var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([{
            field: "status",
            header: "Status",
        }], __VLS_functionalComponentArgsRest(__VLS_81), false));
    var __VLS_85 = __VLS_83.slots.default;
    {
        var __VLS_86 = __VLS_83.slots.body;
        var data = __VLS_vSlot(__VLS_86)[0].data;
        var __VLS_87 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        Tag;
        // @ts-ignore
        var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87({
            value: (data.status || 'active'),
            severity: (__VLS_ctx.statusSeverity(data.status || 'active')),
        }));
        var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([{
                value: (data.status || 'active'),
                severity: (__VLS_ctx.statusSeverity(data.status || 'active')),
            }], __VLS_functionalComponentArgsRest(__VLS_88), false));
        // @ts-ignore
        [statusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_83;
    var __VLS_92 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_93 = __VLS_asFunctionalComponent1(__VLS_92, new __VLS_92({
        header: "Actions",
        frozen: (true),
        alignFrozen: "right",
    }));
    var __VLS_94 = __VLS_93.apply(void 0, __spreadArray([{
            header: "Actions",
            frozen: (true),
            alignFrozen: "right",
        }], __VLS_functionalComponentArgsRest(__VLS_93), false));
    var __VLS_97 = __VLS_95.slots.default;
    {
        var __VLS_98 = __VLS_95.slots.body;
        var data_1 = __VLS_vSlot(__VLS_98)[0].data;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_99 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })));
        var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", severity: "info", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_100), false));
        var __VLS_104 = void 0;
        var __VLS_105 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.router.push({ name: 'procurement.suppliers.detail', params: { id: data_1.id } });
                    // @ts-ignore
                    [router,];
                } });
        var __VLS_102;
        var __VLS_103;
        var __VLS_106 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })));
        var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", severity: "warning", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_107), false));
        var __VLS_111 = void 0;
        var __VLS_112 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.router.push({ name: 'procurement.suppliers.edit', params: { id: data_1.id } });
                    // @ts-ignore
                    [router,];
                } });
        var __VLS_109;
        var __VLS_110;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_95;
    // @ts-ignore
    [];
    var __VLS_56;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_49;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
