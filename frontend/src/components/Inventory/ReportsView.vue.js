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
var auto_1 = require("chart.js/auto");
var axios_1 = require("../../axios");
var selectedReport = (0, vue_1.ref)(null);
var loading = (0, vue_1.ref)(false);
var reportData = (0, vue_1.ref)([]);
var reportSummary = (0, vue_1.ref)([]);
var chartData = (0, vue_1.ref)(null);
var pieChartData = (0, vue_1.ref)(null);
var reportColumns = (0, vue_1.ref)([]);
var reportTypes = [
    {
        id: 'branch_summary',
        name: 'Branch Summary',
        description: 'Key performance indicators by branch',
        icon: 'pi pi-chart-pie'
    },
    {
        id: 'store_summary',
        name: 'Store Overview',
        description: 'Overall inventory metrics',
        icon: 'pi pi-chart-line'
    },
    {
        id: 'movements',
        name: 'Stock Movement',
        description: 'Inbound and outbound trends',
        icon: 'pi pi-arrow-right-arrow-left'
    },
    {
        id: 'category',
        name: 'By Category',
        description: 'Inventory breakdown by category',
        icon: 'pi pi-sitemap'
    },
    {
        id: 'slow_movers',
        name: 'Slow Movers',
        description: 'Underperforming products',
        icon: 'pi pi-arrow-down'
    },
    {
        id: 'fast_movers',
        name: 'Fast Movers',
        description: 'Best selling products',
        icon: 'pi pi-arrow-up'
    },
    {
        id: 'transfers',
        name: 'Transfer Metrics',
        description: 'Inter-store transfer analysis',
        icon: 'pi pi-sitemap'
    },
    {
        id: 'aging',
        name: 'Stock Aging',
        description: 'Product age analysis',
        icon: 'pi pi-clock'
    }
];
var filters = (0, vue_1.reactive)({
    startDate: new Date(new Date().setDate(new Date().getDate() - 30)),
    endDate: new Date(),
    groupBy: 'daily'
});
var groupByOptions = [
    { label: 'Daily', value: 'daily' },
    { label: 'Weekly', value: 'weekly' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Category', value: 'category' },
    { label: 'Branch', value: 'branch' }
];
var chartOptions = {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
};
var getReportTitle = function () {
    var report = reportTypes.find(function (r) { return r.id === selectedReport.value; });
    return (report === null || report === void 0 ? void 0 : report.name) || 'Report';
};
var formatValue = function (value, type) {
    if (type === 'currency')
        return formatCurrency(value);
    if (type === 'percent')
        return "".concat(value, "%");
    if (!isNaN(value))
        return Math.round(value).toLocaleString();
    return String(value);
};
var formatCurrency = function (value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value);
};
var getStatusSeverity = function (status) {
    var severities = {
        'in_stock': 'success',
        'low_stock': 'warning',
        'out_of_stock': 'danger',
        'slow_moving': 'warning',
        'fast_moving': 'success'
    };
    return severities[status] || 'info';
};
var loadSelectedReport = function () { return __awaiter(void 0, void 0, void 0, function () {
    var endpoint, response, data, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                if (!selectedReport.value)
                    return [2 /*return*/];
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 3, 4, 5]);
                endpoint = "/api/inventory/reports/".concat(selectedReport.value);
                return [4 /*yield*/, axios_1.default.get(endpoint, {
                        params: {
                            from_date: (_a = filters.startDate) === null || _a === void 0 ? void 0 : _a.toISOString(),
                            to_date: (_b = filters.endDate) === null || _b === void 0 ? void 0 : _b.toISOString(),
                            group_by: filters.groupBy
                        }
                    })];
            case 2:
                response = _c.sent();
                data = response.data.data;
                // Set summary cards
                reportSummary.value = data.summary || [];
                // Set table data
                reportData.value = data.items || data.details || [];
                // Set columns based on report type
                setReportColumns();
                // Generate charts
                generateCharts(data);
                return [3 /*break*/, 5];
            case 3:
                error_1 = _c.sent();
                console.error('Failed to load report:', error_1);
                return [3 /*break*/, 5];
            case 4:
                loading.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var setReportColumns = function () {
    var columnMap = {
        branch_summary: [
            { field: 'branch_name', header: 'Branch', width: '200px' },
            { field: 'total_items', header: 'Total Items', type: 'number' },
            { field: 'total_value', header: 'Inventory Value', type: 'currency' },
            { field: 'low_stock_count', header: 'Low Stock', type: 'number' },
            { field: 'status', header: 'Status', type: 'status' }
        ],
        store_summary: [
            { field: 'metric', header: 'Metric', width: '250px' },
            { field: 'value', header: 'Value', type: 'number' },
            { field: 'change', header: 'Change', type: 'percent' },
            { field: 'target', header: 'Target', type: 'number' }
        ],
        movements: [
            { field: 'date', header: 'Date', width: '120px' },
            { field: 'inbound', header: 'Inbound', type: 'number' },
            { field: 'outbound', header: 'Outbound', type: 'number' },
            { field: 'net', header: 'Net', type: 'number' }
        ],
        category: [
            { field: 'category_name', header: 'Category', width: '200px' },
            { field: 'product_count', header: 'Products', type: 'number' },
            { field: 'inventory_value', header: 'Value', type: 'currency' },
            { field: 'percentage', header: '% of Total', type: 'percent' }
        ],
        slow_movers: [
            { field: 'sku', header: 'SKU', width: '100px' },
            { field: 'product_name', header: 'Product', width: '200px' },
            { field: 'quantity_on_hand', header: 'Current Stock', type: 'number' },
            { field: 'units_sold', header: '90-Day Sales', type: 'number' },
            { field: 'days_in_stock', header: 'Days In Stock', type: 'number' }
        ],
        fast_movers: [
            { field: 'sku', header: 'SKU', width: '100px' },
            { field: 'product_name', header: 'Product', width: '200px' },
            { field: 'quantity_on_hand', header: 'Current Stock', type: 'number' },
            { field: 'units_sold', header: '90-Day Sales', type: 'number' },
            { field: 'sales_velocity', header: 'Velocity', type: 'number' }
        ],
        transfers: [
            { field: 'transfer_no', header: 'Transfer #', width: '120px' },
            { field: 'from_branch', header: 'From', width: '150px' },
            { field: 'to_branch', header: 'To', width: '150px' },
            { field: 'item_count', header: 'Items', type: 'number' },
            { field: 'status', header: 'Status', type: 'status' }
        ],
        aging: [
            { field: 'sku', header: 'SKU', width: '100px' },
            { field: 'product_name', header: 'Product', width: '200px' },
            { field: 'received_date', header: 'Received', width: '120px' },
            { field: 'days_in_inventory', header: 'Days In Inventory', type: 'number' },
            { field: 'inventory_value', header: 'Value', type: 'currency' }
        ]
    };
    reportColumns.value = columnMap[selectedReport.value] || [];
};
var generateCharts = function (data) {
    // Generate line/bar chart
    if (data.chart_data) {
        chartData.value = {
            labels: data.chart_data.labels || [],
            datasets: data.chart_data.datasets || []
        };
    }
    // Generate pie/doughnut chart
    if (data.distribution_data) {
        pieChartData.value = {
            labels: data.distribution_data.labels || [],
            datasets: [
                {
                    data: data.distribution_data.values || [],
                    backgroundColor: [
                        '#3b82f6',
                        '#ef4444',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ec4899',
                        '#06b6d4',
                        '#84cc16'
                    ]
                }
            ]
        };
    }
};
var exportReport = function () { return __awaiter(void 0, void 0, void 0, function () {
    var endpoint, response, url, link, error_2;
    var _a, _b, _c;
    return __generator(this, function (_d) {
        switch (_d.label) {
            case 0:
                if (!selectedReport.value)
                    return [2 /*return*/];
                _d.label = 1;
            case 1:
                _d.trys.push([1, 3, , 4]);
                endpoint = "/api/inventory/reports/".concat(selectedReport.value, "/export");
                return [4 /*yield*/, axios_1.default.get(endpoint, {
                        params: {
                            from_date: (_a = filters.startDate) === null || _a === void 0 ? void 0 : _a.toISOString(),
                            to_date: (_b = filters.endDate) === null || _b === void 0 ? void 0 : _b.toISOString(),
                            export_format: 'csv'
                        },
                        responseType: 'blob'
                    })
                    // Create download link
                ];
            case 2:
                response = _d.sent();
                url = window.URL.createObjectURL(response.data);
                link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', "".concat(selectedReport.value, "_").concat(new Date().toISOString().split('T')[0], ".csv"));
                document.body.appendChild(link);
                link.click();
                (_c = link.parentElement) === null || _c === void 0 ? void 0 : _c.removeChild(link);
                return [3 /*break*/, 4];
            case 3:
                error_2 = _d.sent();
                console.error('Failed to export report:', error_2);
                return [3 /*break*/, 4];
            case 4: return [2 /*return*/];
        }
    });
}); };
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-col md:flex-row md:items-center md:justify-between gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-col']} */ ;
/** @type {__VLS_StyleScopedClasses['md:flex-row']} */ ;
/** @type {__VLS_StyleScopedClasses['md:items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['md:justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { icon: "pi pi-download", label: "Export", severity: "secondary", disabled: (__VLS_ctx.selectedReport === null) })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-download", label: "Export", severity: "secondary", disabled: (__VLS_ctx.selectedReport === null) })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.exportReport) });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
Button;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { icon: "pi pi-refresh", label: "Refresh" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-refresh", label: "Refresh" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: (__VLS_ctx.loadSelectedReport) });
var __VLS_10;
var __VLS_11;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var _loop_1 = function (report) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.selectedReport = report.id;
            // @ts-ignore
            [selectedReport, selectedReport, exportReport, loadSelectedReport, reportTypes,];
        } }, { key: (report.id) }), { class: ([
            'p-4 rounded-lg border-2 cursor-pointer transition-all',
            __VLS_ctx.selectedReport === report.id
                ? 'border-blue-600 bg-blue-50'
                : 'border-gray-200 bg-white hover:border-gray-300'
        ]) }));
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-all']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: (['text-3xl', report.icon]) }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "font-semibold text-gray-900" }));
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
    (report.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    (report.description);
    // @ts-ignore
    [selectedReport,];
};
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.reportTypes)); _i < _a.length; _i++) {
    var report = _a[_i][0];
    _loop_1(report);
}
if (__VLS_ctx.selectedReport) {
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14({}));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = __VLS_17.slots.default;
    {
        var __VLS_20 = __VLS_17.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        var __VLS_21 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Calendar} */
        Calendar;
        // @ts-ignore
        var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ modelValue: (__VLS_ctx.filters.startDate), dateFormat: "dd/mm/yy" }, { class: "w-full" })));
        var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.startDate), dateFormat: "dd/mm/yy" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        var __VLS_26 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Calendar} */
        Calendar;
        // @ts-ignore
        var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ modelValue: (__VLS_ctx.filters.endDate), dateFormat: "dd/mm/yy" }, { class: "w-full" })));
        var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.endDate), dateFormat: "dd/mm/yy" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        var __VLS_31 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Dropdown} */
        Dropdown;
        // @ts-ignore
        var __VLS_32 = __VLS_asFunctionalComponent1(__VLS_31, new __VLS_31(__assign({ modelValue: (__VLS_ctx.filters.groupBy), options: (__VLS_ctx.groupByOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })));
        var __VLS_33 = __VLS_32.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.filters.groupBy), options: (__VLS_ctx.groupByOptions), optionLabel: "label", optionValue: "value" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_32), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-end" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-end']} */ ;
        var __VLS_36 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_37 = __VLS_asFunctionalComponent1(__VLS_36, new __VLS_36(__assign(__assign({ 'onClick': {} }, { label: "Generate Report", icon: "pi pi-chart-bar" }), { class: "w-full" })));
        var __VLS_38 = __VLS_37.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Generate Report", icon: "pi pi-chart-bar" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_37), false));
        var __VLS_41 = void 0;
        var __VLS_42 = ({ click: {} },
            { onClick: (__VLS_ctx.loadSelectedReport) });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_39;
        var __VLS_40;
        // @ts-ignore
        [selectedReport, loadSelectedReport, filters, filters, filters, groupByOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_17;
}
if (__VLS_ctx.selectedReport && !__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    if (__VLS_ctx.reportSummary) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.reportSummary)); _b < _c.length; _b++) {
            var _d = _c[_b], item = _d[0], index = _d[1];
            var __VLS_43 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
            Card;
            // @ts-ignore
            var __VLS_44 = __VLS_asFunctionalComponent1(__VLS_43, new __VLS_43(__assign({ key: (index) }, { class: "hover:shadow-lg transition-shadow" })));
            var __VLS_45 = __VLS_44.apply(void 0, __spreadArray([__assign({ key: (index) }, { class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_44), false));
            /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
            /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
            var __VLS_48 = __VLS_46.slots.default;
            {
                var __VLS_49 = __VLS_46.slots.content;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
                (item.label);
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-gray-900" }));
                /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
                /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
                (__VLS_ctx.formatValue(item.value, item.type));
                if (item.change) {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: (['text-xs mt-1', item.change > 0 ? 'text-green-600' : 'text-red-600']) }));
                    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                    /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['pi', item.change > 0 ? 'pi-arrow-up' : 'pi-arrow-down']) }));
                    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                    (Math.abs(item.change));
                }
                // @ts-ignore
                [selectedReport, loading, reportSummary, reportSummary, formatValue,];
            }
            // @ts-ignore
            [];
            var __VLS_46;
            // @ts-ignore
            [];
        }
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    if (__VLS_ctx.chartData) {
        var __VLS_50 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        Card;
        // @ts-ignore
        var __VLS_51 = __VLS_asFunctionalComponent1(__VLS_50, new __VLS_50({}));
        var __VLS_52 = __VLS_51.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_51), false));
        var __VLS_55 = __VLS_53.slots.default;
        {
            var __VLS_56 = __VLS_53.slots.title;
            (__VLS_ctx.getReportTitle());
            // @ts-ignore
            [chartData, getReportTitle,];
        }
        {
            var __VLS_57 = __VLS_53.slots.content;
            var __VLS_58 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            auto_1.Chart;
            // @ts-ignore
            var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58({
                type: "line",
                data: (__VLS_ctx.chartData),
                options: (__VLS_ctx.chartOptions),
            }));
            var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([{
                    type: "line",
                    data: (__VLS_ctx.chartData),
                    options: (__VLS_ctx.chartOptions),
                }], __VLS_functionalComponentArgsRest(__VLS_59), false));
            // @ts-ignore
            [chartData, chartOptions,];
        }
        // @ts-ignore
        [];
        var __VLS_53;
    }
    if (__VLS_ctx.pieChartData) {
        var __VLS_63 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        Card;
        // @ts-ignore
        var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63({}));
        var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_64), false));
        var __VLS_68 = __VLS_66.slots.default;
        {
            var __VLS_69 = __VLS_66.slots.title;
            (__VLS_ctx.getReportTitle());
            // @ts-ignore
            [getReportTitle, pieChartData,];
        }
        {
            var __VLS_70 = __VLS_66.slots.content;
            var __VLS_71 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            auto_1.Chart;
            // @ts-ignore
            var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71({
                type: "doughnut",
                data: (__VLS_ctx.pieChartData),
                options: (__VLS_ctx.chartOptions),
            }));
            var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([{
                    type: "doughnut",
                    data: (__VLS_ctx.pieChartData),
                    options: (__VLS_ctx.chartOptions),
                }], __VLS_functionalComponentArgsRest(__VLS_72), false));
            // @ts-ignore
            [chartOptions, pieChartData,];
        }
        // @ts-ignore
        [];
        var __VLS_66;
    }
    var __VLS_76 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    Card;
    // @ts-ignore
    var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({}));
    var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_77), false));
    var __VLS_81 = __VLS_79.slots.default;
    {
        var __VLS_82 = __VLS_79.slots.title;
        (__VLS_ctx.getReportTitle());
        // @ts-ignore
        [getReportTitle,];
    }
    {
        var __VLS_83 = __VLS_79.slots.content;
        var __VLS_84 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
        DataTable;
        // @ts-ignore
        var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign(__assign({ value: (__VLS_ctx.reportData) }, { class: "p-datatable-sm" }), { stripedRows: true, responsiveLayout: "scroll", paginator: (true), rows: (10) })));
        var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign(__assign({ value: (__VLS_ctx.reportData) }, { class: "p-datatable-sm" }), { stripedRows: true, responsiveLayout: "scroll", paginator: (true), rows: (10) })], __VLS_functionalComponentArgsRest(__VLS_85), false));
        /** @type {__VLS_StyleScopedClasses['p-datatable-sm']} */ ;
        var __VLS_89 = __VLS_87.slots.default;
        {
            var __VLS_90 = __VLS_87.slots.empty;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl text-gray-400 mb-2" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
            /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
            // @ts-ignore
            [reportData,];
        }
        for (var _e = 0, _f = __VLS_vFor((__VLS_ctx.reportColumns)); _e < _f.length; _e++) {
            var column = _f[_e][0];
            var __VLS_91 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
            Column;
            // @ts-ignore
            var __VLS_92 = __VLS_asFunctionalComponent1(__VLS_91, new __VLS_91(__assign({ key: (column.field), field: (column.field), header: (column.header), sortable: (column.sortable !== false) }, { style: ({ width: column.width || '150px' }) })));
            var __VLS_93 = __VLS_92.apply(void 0, __spreadArray([__assign({ key: (column.field), field: (column.field), header: (column.header), sortable: (column.sortable !== false) }, { style: ({ width: column.width || '150px' }) })], __VLS_functionalComponentArgsRest(__VLS_92), false));
            var __VLS_96 = __VLS_94.slots.default;
            {
                var __VLS_97 = __VLS_94.slots.body;
                var data = __VLS_vSlot(__VLS_97)[0].data;
                if (column.type === 'currency') {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                    (__VLS_ctx.formatCurrency(data[column.field]));
                }
                else if (column.type === 'percent') {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                    (data[column.field]);
                }
                else if (column.type === 'status') {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                    var __VLS_98 = void 0;
                    /** @ts-ignore @type {typeof __VLS_components.Tag} */
                    Tag;
                    // @ts-ignore
                    var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({
                        value: (data[column.field]),
                        severity: (__VLS_ctx.getStatusSeverity(data[column.field])),
                    }));
                    var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{
                            value: (data[column.field]),
                            severity: (__VLS_ctx.getStatusSeverity(data[column.field])),
                        }], __VLS_functionalComponentArgsRest(__VLS_99), false));
                }
                else {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                    (data[column.field]);
                }
                // @ts-ignore
                [reportColumns, formatCurrency, getStatusSeverity,];
            }
            // @ts-ignore
            [];
            var __VLS_94;
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_87;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_79;
}
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    for (var _g = 0, _h = __VLS_vFor((3)); _g < _h.length; _g++) {
        var i = _h[_g][0];
        var __VLS_103 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        Skeleton;
        // @ts-ignore
        var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ height: "100px", key: (i) }, { class: "rounded-lg" })));
        var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ height: "100px", key: (i) }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_104), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
}
if (!__VLS_ctx.selectedReport) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-16" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-16']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-bar text-6xl text-gray-300 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-chart-bar']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
}
// @ts-ignore
[selectedReport,];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
