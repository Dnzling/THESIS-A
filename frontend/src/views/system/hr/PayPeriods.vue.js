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
var usetoast_1 = require("primevue/usetoast");
var vue_1 = require("vue");
var axios_1 = require("axios");
var auth_1 = require("../../../stores/auth");
// Data
var toast = (0, usetoast_1.useToast)();
var payPeriods = (0, vue_1.ref)([]);
var loading = (0, vue_1.ref)(false);
var error = (0, vue_1.ref)(null);
var showDialog = (0, vue_1.ref)(false);
var dialogTitle = (0, vue_1.ref)('Create Pay Period');
var editingId = (0, vue_1.ref)(null);
var showDeleteModal = (0, vue_1.ref)(false);
var periodToDelete = (0, vue_1.ref)(null);
var authStore = (0, auth_1.useAuthStore)();
var filters = (0, vue_1.ref)({
    search: '',
    status: null,
    dateRange: null
});
var periodForm = (0, vue_1.ref)({
    name: '',
    startDate: null,
    endDate: null,
    halfType: null,
    payDate: null
});
var statusOptions = (0, vue_1.ref)(['draft', 'for-review', 'approved', 'released']);
// Computed
var filteredPayPeriods = (0, vue_1.computed)(function () {
    return payPeriods.value.filter(function (item) {
        var matchesSearch = !filters.value.search ||
            item.period.toLowerCase().includes(filters.value.search.toLowerCase());
        var matchesStatus = !filters.value.status || item.status === filters.value.status;
        // Date range filter
        var matchesDateRange = true;
        if (filters.value.dateRange && filters.value.dateRange[0] && filters.value.dateRange[1]) {
            var filterStart = new Date(filters.value.dateRange[0]);
            var filterEnd = new Date(filters.value.dateRange[1]);
            // Reset time to start/end of day for accurate comparison
            filterStart.setHours(0, 0, 0, 0);
            filterEnd.setHours(23, 59, 59, 999);
            // Convert string dates to Date objects
            var itemStart = new Date(item.cutoffStart);
            var itemEnd = new Date(item.cutoffEnd);
            // Check if period overlaps with selected range
            // This will include periods that have any overlap with the selected date range
            matchesDateRange = itemStart <= filterEnd && itemEnd >= filterStart;
            // Alternative: Strict inclusion (period must be completely within range)
            // matchesDateRange = itemStart >= filterStart && itemEnd <= filterEnd
        }
        return matchesSearch && matchesStatus && matchesDateRange;
    });
});
// Then your fetch becomes simpler:
var fetchPayPeriods = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                error.value = null;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, axios_1.default.get('/api/payroll/periods', {
                        headers: {
                            'Authorization': "Bearer ".concat(authStore.token)
                        }
                    })];
            case 2:
                response = _c.sent();
                if (response.data.success) {
                    payPeriods.value = response.data.data;
                }
                else {
                    error.value = response.data.message || 'Failed to fetch pay periods';
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: error.value,
                        life: 3000
                    });
                }
                return [3 /*break*/, 5];
            case 3:
                err_1 = _c.sent();
                error.value = 'Failed to fetch pay periods';
                console.error('Fetch error:', err_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = err_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to connect to server',
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
// Watch for changes in startDate to auto-update endDate and payDate
(0, vue_1.watch)(function () { return periodForm.value.startDate; }, function (newStartDate) {
    if (newStartDate) {
        var start = new Date(newStartDate);
        // Auto-calculate end date (+14 days)
        var newEndDate = new Date(start);
        newEndDate.setDate(start.getDate() + 14);
        periodForm.value.endDate = newEndDate;
        // Auto-calculate pay date (+15 days)
        var newPayDate = new Date(start);
        newPayDate.setDate(start.getDate() + 14);
        periodForm.value.payDate = newPayDate;
        // Auto-update half type based on start date
        var day = start.getDate();
        periodForm.value.halfType = day <= 15 ? '1st Half' : '2nd Half';
        // Auto-update name
        var monthName = start.toLocaleString('default', { month: 'long' });
        var year = start.getFullYear();
        periodForm.value.name = "".concat(monthName, " ").concat(year, " (").concat(periodForm.value.halfType, ")");
    }
});
var createPeriod = function () {
    // Get current date for default values
    var today = new Date();
    var currentYear = today.getFullYear();
    var currentMonth = today.getMonth();
    var day = today.getDate();
    // Determine which half of the month we're in
    var defaultHalfType = day <= 15 ? '1st Half' : '2nd Half';
    // Set start date based on half type
    var startDate;
    if (defaultHalfType === '1st Half') {
        startDate = new Date(currentYear, currentMonth, 1);
    }
    else {
        startDate = new Date(currentYear, currentMonth, 16);
    }
    // Calculate end date and pay date
    var endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + 14);
    var payDate = new Date(startDate);
    payDate.setDate(startDate.getDate() + 14);
    // Format month name
    var monthName = startDate.toLocaleString('default', { month: 'long' });
    // Generate the period name
    var periodName = "".concat(monthName, " ").concat(currentYear, " (").concat(defaultHalfType, ")");
    periodForm.value = {
        name: periodName,
        startDate: startDate,
        endDate: endDate,
        halfType: defaultHalfType,
        payDate: payDate
    };
    dialogTitle.value = 'Create Pay Period';
    showDialog.value = true;
};
var savePeriod = function () { return __awaiter(void 0, void 0, void 0, function () {
    var formatDate, response, err_2, errorMessage, errors;
    var _a, _b, _c, _d, _e;
    return __generator(this, function (_f) {
        switch (_f.label) {
            case 0:
                _f.trys.push([0, 4, , 5]);
                console.log(periodForm.value.name);
                formatDate = function (date) {
                    if (!date)
                        return null;
                    var d = new Date(date);
                    var year = d.getFullYear();
                    var month = String(d.getMonth() + 1).padStart(2, '0');
                    var day = String(d.getDate()).padStart(2, '0');
                    return "".concat(year, "-").concat(month, "-").concat(day);
                };
                return [4 /*yield*/, axios_1.default.post('/api/payroll/periods', {
                        name: periodForm.value.name,
                        start_date: formatDate(periodForm.value.startDate),
                        end_date: formatDate(periodForm.value.endDate),
                        cutoff_date: formatDate(periodForm.value.payDate),
                        notes: '' // Add notes if needed
                    }, {
                        headers: {
                            'Authorization': "Bearer 14|Lcuhac078HH2u8ryNGLIgirhwYUVyyDvnu7SgxqD069d74a9"
                        }
                    })];
            case 1:
                response = _f.sent();
                if (!response.data.success) return [3 /*break*/, 3];
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: "Pay period created successfully",
                    life: 3000
                });
                showDialog.value = false;
                resetForm();
                return [4 /*yield*/, fetchPayPeriods()];
            case 2:
                _f.sent();
                _f.label = 3;
            case 3: return [3 /*break*/, 5];
            case 4:
                err_2 = _f.sent();
                console.error('Save error:', ((_a = err_2.response) === null || _a === void 0 ? void 0 : _a.data) || err_2);
                errorMessage = ((_c = (_b = err_2.response) === null || _b === void 0 ? void 0 : _b.data) === null || _c === void 0 ? void 0 : _c.message) || 'Failed to save pay period';
                errors = (_e = (_d = err_2.response) === null || _d === void 0 ? void 0 : _d.data) === null || _e === void 0 ? void 0 : _e.errors;
                if (errors) {
                    // Handle validation errors
                    Object.values(errors).forEach(function (error) {
                        toast.add({
                            severity: 'error',
                            summary: 'Validation Error',
                            detail: error[0],
                            life: 3000
                        });
                    });
                }
                else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: errorMessage,
                        life: 3000
                    });
                }
                return [3 /*break*/, 5];
            case 5: return [2 /*return*/];
        }
    });
}); };
var editPeriod = function (period) {
    editingId.value = period.id;
    periodForm.value = {
        name: period.period,
        startDate: new Date(period.cutoffStart),
        endDate: new Date(period.cutoffEnd),
        halfType: null,
        payDate: new Date(period.payDate)
    };
    dialogTitle.value = 'Edit Pay Period';
    showDialog.value = true;
};
var confirmDeletePeriod = function (period) {
    periodToDelete.value = period;
    showDeleteModal.value = true;
};
var deletePeriod = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, err_3;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                if (!periodToDelete.value)
                    return [2 /*return*/];
                _a.label = 1;
            case 1:
                _a.trys.push([1, 6, 7, 8]);
                return [4 /*yield*/, axios_1.default.delete("/api/payroll/periods/".concat(periodToDelete.value.id))];
            case 2:
                response = _a.sent();
                if (!response.data.success) return [3 /*break*/, 4];
                toast.add({
                    severity: 'success',
                    summary: 'Deleted',
                    detail: "Period \"".concat(periodToDelete.value.period, "\" has been deleted"),
                    life: 3000
                });
                // Refresh the list
                return [4 /*yield*/, fetchPayPeriods()];
            case 3:
                // Refresh the list
                _a.sent();
                return [3 /*break*/, 5];
            case 4:
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: response.data.message || 'Failed to delete',
                    life: 3000
                });
                _a.label = 5;
            case 5: return [3 /*break*/, 8];
            case 6:
                err_3 = _a.sent();
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to delete period',
                    life: 3000
                });
                return [3 /*break*/, 8];
            case 7:
                showDeleteModal.value = false;
                periodToDelete.value = null;
                return [7 /*endfinally*/];
            case 8: return [2 /*return*/];
        }
    });
}); };
var resetForm = function () {
    periodForm.value = {
        name: '',
        startDate: null,
        endDate: null,
        halfType: null,
        payDate: null
    };
    editingId.value = null;
    dialogTitle.value = 'Create Pay Period';
};
// Lifecycle
(0, vue_1.onMounted)(function () {
    fetchPayPeriods();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
Card;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5 = __VLS_3.slots.default;
{
    var __VLS_6 = __VLS_3.slots.title;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-3 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    var __VLS_7 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    IconField;
    // @ts-ignore
    var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
        iconPosition: "left",
    }));
    var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([{
            iconPosition: "left",
        }], __VLS_functionalComponentArgsRest(__VLS_8), false));
    var __VLS_12 = __VLS_10.slots.default;
    var __VLS_13 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    InputIcon;
    // @ts-ignore
    var __VLS_14 = __VLS_asFunctionalComponent1(__VLS_13, new __VLS_13({}));
    var __VLS_15 = __VLS_14.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_14), false));
    var __VLS_18 = __VLS_16.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_16;
    var __VLS_19 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    InputText;
    // @ts-ignore
    var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search period" }, { class: "w-full" })));
    var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.search), placeholder: "Search period" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_20), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [filters,];
    var __VLS_10;
    var __VLS_24 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    Select;
    // @ts-ignore
    var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }, { class: "w-48" })));
    var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.status), options: (__VLS_ctx.statusOptions), placeholder: "All Status", showClear: true }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_29 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DatePicker} */
    DatePicker;
    // @ts-ignore
    var __VLS_30 = __VLS_asFunctionalComponent1(__VLS_29, new __VLS_29({
        modelValue: (__VLS_ctx.filters.dateRange),
        showIcon: true,
        showClear: true,
        selectionMode: "range",
        placeholder: "Date Range",
        fluid: true,
    }));
    var __VLS_31 = __VLS_30.apply(void 0, __spreadArray([{
            modelValue: (__VLS_ctx.filters.dateRange),
            showIcon: true,
            showClear: true,
            selectionMode: "range",
            placeholder: "Date Range",
            fluid: true,
        }], __VLS_functionalComponentArgsRest(__VLS_30), false));
    var __VLS_34 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_35 = __VLS_asFunctionalComponent1(__VLS_34, new __VLS_34(__assign(__assign({ 'onClick': {} }, { label: "New Period", icon: "pi pi-plus", severity: "info" }), { class: "ml-auto" })));
    var __VLS_36 = __VLS_35.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "New Period", icon: "pi pi-plus", severity: "info" }), { class: "ml-auto" })], __VLS_functionalComponentArgsRest(__VLS_35), false));
    var __VLS_39 = void 0;
    var __VLS_40 = ({ click: {} },
        { onClick: (__VLS_ctx.createPeriod) });
    /** @type {__VLS_StyleScopedClasses['ml-auto']} */ ;
    var __VLS_37;
    var __VLS_38;
    // @ts-ignore
    [filters, filters, statusOptions, createPeriod,];
}
{
    var __VLS_41 = __VLS_3.slots.content;
    var __VLS_42 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    DataTable;
    // @ts-ignore
    var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign(__assign({ value: (__VLS_ctx.filteredPayPeriods) }, { class: "w-full" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} periods", rowHover: true, showGridlines: true, removableSort: true, responsiveLayout: "scroll", sortField: "name", sortOrder: (1), tableStyle: "min-width: 50rem" })));
    var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.filteredPayPeriods) }, { class: "w-full" }), { loading: (__VLS_ctx.loading), paginator: true, rows: (10), rowsPerPageOptions: ([5, 10, 20, 50]), paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown", currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} periods", rowHover: true, showGridlines: true, removableSort: true, responsiveLayout: "scroll", sortField: "name", sortOrder: (1), tableStyle: "min-width: 50rem" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    var __VLS_47 = __VLS_45.slots.default;
    var __VLS_48 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_49 = __VLS_asFunctionalComponent1(__VLS_48, new __VLS_48({
        field: "period",
        header: "Period",
        sortable: true,
    }));
    var __VLS_50 = __VLS_49.apply(void 0, __spreadArray([{
            field: "period",
            header: "Period",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_49), false));
    var __VLS_53 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_54 = __VLS_asFunctionalComponent1(__VLS_53, new __VLS_53({
        field: "cutoffStart",
        header: "Start Date",
        sortable: true,
    }));
    var __VLS_55 = __VLS_54.apply(void 0, __spreadArray([{
            field: "cutoffStart",
            header: "Start Date",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_54), false));
    var __VLS_58 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
        field: "cutoffEnd",
        header: "End Date",
        sortable: true,
    }));
    var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
            field: "cutoffEnd",
            header: "End Date",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_59), false));
    var __VLS_63 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({
        field: "payDate",
        header: "Pay Date",
        sortable: true,
    }));
    var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{
            field: "payDate",
            header: "Pay Date",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_64), false));
    var __VLS_68 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_69 = __VLS_asFunctionalComponent1(__VLS_68, new __VLS_68({
        field: "status",
        header: "Status",
        sortable: true,
    }));
    var __VLS_70 = __VLS_69.apply(void 0, __spreadArray([{
            field: "status",
            header: "Status",
            sortable: true,
        }], __VLS_functionalComponentArgsRest(__VLS_69), false));
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    Column;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73({
        header: "Actions",
    }));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([{
            header: "Actions",
        }], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = __VLS_76.slots.default;
    {
        var __VLS_79 = __VLS_76.slots.body;
        var slotProps_1 = __VLS_vSlot(__VLS_79)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_80 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true })));
        var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", text: true })], __VLS_functionalComponentArgsRest(__VLS_81), false));
        var __VLS_85 = void 0;
        var __VLS_86 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.editPeriod(slotProps_1.data);
                    // @ts-ignore
                    [filteredPayPeriods, loading, editPeriod,];
                } });
        var __VLS_83;
        var __VLS_84;
        var __VLS_87 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_88 = __VLS_asFunctionalComponent1(__VLS_87, new __VLS_87(__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, severity: "danger" })));
        var __VLS_89 = __VLS_88.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", text: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_88), false));
        var __VLS_92 = void 0;
        var __VLS_93 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.confirmDeletePeriod(slotProps_1.data);
                    // @ts-ignore
                    [confirmDeletePeriod,];
                } });
        var __VLS_90;
        var __VLS_91;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_76;
    {
        var __VLS_94 = __VLS_45.slots.empty;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-12" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['py-12']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-500 text-lg" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-400 text-sm mb-4" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
        var __VLS_95 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign({ 'onClick': {} }, { label: "Add Your First Period", icon: "pi pi-plus", severity: "info" })));
        var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Your First Period", icon: "pi pi-plus", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_96), false));
        var __VLS_100 = void 0;
        var __VLS_101 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    __VLS_ctx.showDialog = true;
                    // @ts-ignore
                    [showDialog,];
                } });
        var __VLS_98;
        var __VLS_99;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_45;
    // @ts-ignore
    [];
}
var __VLS_102;
/** @ts-ignore @type {typeof __VLS_components.ScrollTop} */
ScrollTop;
// @ts-ignore
var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({}));
var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_103), false));
// @ts-ignore
[];
var __VLS_3;
var __VLS_107;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_108 = __VLS_asFunctionalComponent1(__VLS_107, new __VLS_107(__assign({ visible: (__VLS_ctx.showDialog), header: (__VLS_ctx.dialogTitle), modal: true }, { style: ({ width: '500px' }) })));
var __VLS_109 = __VLS_108.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDialog), header: (__VLS_ctx.dialogTitle), modal: true }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_108), false));
var __VLS_112 = __VLS_110.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_113;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_114 = __VLS_asFunctionalComponent1(__VLS_113, new __VLS_113({
    modelValue: (__VLS_ctx.periodForm.startDate),
    showIcon: true,
    showClear: true,
    fluid: true,
    iconDisplay: "input",
}));
var __VLS_115 = __VLS_114.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.periodForm.startDate),
        showIcon: true,
        showClear: true,
        fluid: true,
        iconDisplay: "input",
    }], __VLS_functionalComponentArgsRest(__VLS_114), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_118;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118({
    modelValue: (__VLS_ctx.periodForm.endDate),
    showIcon: true,
    showClear: true,
    fluid: true,
    iconDisplay: "input",
}));
var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.periodForm.endDate),
        showIcon: true,
        showClear: true,
        fluid: true,
        iconDisplay: "input",
    }], __VLS_functionalComponentArgsRest(__VLS_119), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({
    for: "",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_123;
/** @ts-ignore @type {typeof __VLS_components.RadioButton} */
RadioButton;
// @ts-ignore
var __VLS_124 = __VLS_asFunctionalComponent1(__VLS_123, new __VLS_123({
    modelValue: (__VLS_ctx.periodForm.halfType),
    value: "1st Half",
}));
var __VLS_125 = __VLS_124.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.periodForm.halfType),
        value: "1st Half",
    }], __VLS_functionalComponentArgsRest(__VLS_124), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_128;
/** @ts-ignore @type {typeof __VLS_components.RadioButton} */
RadioButton;
// @ts-ignore
var __VLS_129 = __VLS_asFunctionalComponent1(__VLS_128, new __VLS_128({
    modelValue: (__VLS_ctx.periodForm.halfType),
    value: "2nd Half",
}));
var __VLS_130 = __VLS_129.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.periodForm.halfType),
        value: "2nd Half",
    }], __VLS_functionalComponentArgsRest(__VLS_129), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_133;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
DatePicker;
// @ts-ignore
var __VLS_134 = __VLS_asFunctionalComponent1(__VLS_133, new __VLS_133({
    modelValue: (__VLS_ctx.periodForm.payDate),
    showIcon: true,
    fluid: true,
    showClear: true,
    iconDisplay: "input",
}));
var __VLS_135 = __VLS_134.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.periodForm.payDate),
        showIcon: true,
        fluid: true,
        showClear: true,
        iconDisplay: "input",
    }], __VLS_functionalComponentArgsRest(__VLS_134), false));
{
    var __VLS_138 = __VLS_110.slots.footer;
    var __VLS_139 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_140), false));
    var __VLS_144 = void 0;
    var __VLS_145 = ({ click: {} },
        { onClick: (__VLS_ctx.resetForm) });
    var __VLS_142;
    var __VLS_143;
    var __VLS_146 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_147 = __VLS_asFunctionalComponent1(__VLS_146, new __VLS_146(__assign({ 'onClick': {} }, { label: "Save", severity: "info" })));
    var __VLS_148 = __VLS_147.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_147), false));
    var __VLS_151 = void 0;
    var __VLS_152 = ({ click: {} },
        { onClick: (__VLS_ctx.savePeriod) });
    var __VLS_149;
    var __VLS_150;
    // @ts-ignore
    [showDialog, dialogTitle, periodForm, periodForm, periodForm, periodForm, periodForm, resetForm, savePeriod,];
}
// @ts-ignore
[];
var __VLS_110;
var __VLS_153;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
Dialog;
// @ts-ignore
var __VLS_154 = __VLS_asFunctionalComponent1(__VLS_153, new __VLS_153(__assign(__assign({ visible: (__VLS_ctx.showDeleteModal), header: "Confirm Delete" }, { style: ({ width: '400px' }) }), { modal: true })));
var __VLS_155 = __VLS_154.apply(void 0, __spreadArray([__assign(__assign({ visible: (__VLS_ctx.showDeleteModal), header: "Confirm Delete" }, { style: ({ width: '400px' }) }), { modal: true })], __VLS_functionalComponentArgsRest(__VLS_154), false));
var __VLS_158 = __VLS_156.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
/** @type {__VLS_StyleScopedClasses['text-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-4xl text-yellow-500 mb-3" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
((_a = __VLS_ctx.periodToDelete) === null || _a === void 0 ? void 0 : _a.period);
__VLS_asFunctionalElement1(__VLS_intrinsics.small, __VLS_intrinsics.small)(__assign({ class: "text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
{
    var __VLS_159 = __VLS_156.slots.footer;
    var __VLS_160 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_161 = __VLS_asFunctionalComponent1(__VLS_160, new __VLS_160(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", rounded: true })));
    var __VLS_162 = __VLS_161.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary", rounded: true })], __VLS_functionalComponentArgsRest(__VLS_161), false));
    var __VLS_165 = void 0;
    var __VLS_166 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDeleteModal = false;
                // @ts-ignore
                [showDeleteModal, showDeleteModal, periodToDelete,];
            } });
    var __VLS_163;
    var __VLS_164;
    var __VLS_167 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_168 = __VLS_asFunctionalComponent1(__VLS_167, new __VLS_167(__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", rounded: true })));
    var __VLS_169 = __VLS_168.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete", severity: "danger", rounded: true })], __VLS_functionalComponentArgsRest(__VLS_168), false));
    var __VLS_172 = void 0;
    var __VLS_173 = ({ click: {} },
        { onClick: (__VLS_ctx.deletePeriod) });
    var __VLS_170;
    var __VLS_171;
    // @ts-ignore
    [deletePeriod,];
}
// @ts-ignore
[];
var __VLS_156;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
