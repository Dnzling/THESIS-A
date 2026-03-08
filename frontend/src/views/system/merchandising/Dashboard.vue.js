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
var merchandising_service_1 = require("../../../services/merchandising.service");
var card_1 = require("primevue/card");
var button_1 = require("primevue/button");
var skeleton_1 = require("primevue/skeleton");
var tag_1 = require("primevue/tag");
var badge_1 = require("primevue/badge");
var chart_1 = require("primevue/chart");
var timeline_1 = require("primevue/timeline");
var vue_router_1 = require("vue-router");
var router = (0, vue_router_1.useRouter)();
var toast = (0, usetoast_1.useToast)();
var loading = (0, vue_1.ref)(true);
var loadingActivity = (0, vue_1.ref)(false);
// ✅ Initialize with default values to prevent undefined errors
var stats = (0, vue_1.ref)({
    total_products: 0,
    active_products: 0,
    inactive_products: 0,
    total_categories: 0,
    total_subcategories: 0,
    in_stock_products: 0,
    low_stock_products: 0,
    out_of_stock_products: 0,
    total_3d_models: 0,
    total_images: 0,
    total_variations: 0,
    active_variations: 0,
    total_3d_size: 0,
    total_image_size: 0,
    total_inventory_value: 0,
    average_price: 0,
    featured_count: 0,
    new_arrival_count: 0,
    bestseller_count: 0,
    products_by_category: [],
    stock_status_distribution: [],
    price_range_distribution: []
});
var recentProducts = (0, vue_1.ref)([]);
var lowStockProducts = (0, vue_1.ref)([]);
var activityLog = (0, vue_1.ref)([]);
// Chart Data
var categoryChartData = (0, vue_1.computed)(function () {
    if (!stats.value.products_by_category || stats.value.products_by_category.length === 0) {
        return { labels: [], datasets: [] };
    }
    var labels = stats.value.products_by_category.map(function (item) { return item.category_name || 'Uncategorized'; });
    var data = stats.value.products_by_category.map(function (item) { return item.count; });
    return {
        labels: labels,
        datasets: [
            {
                data: data,
                backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#6b7280'],
                hoverBackgroundColor: ['#4f46e5', '#7c3aed', '#db2777', '#d97706', '#059669', '#2563eb', '#dc2626', '#4b5563']
            }
        ]
    };
});
var stockChartData = (0, vue_1.computed)(function () {
    if (!stats.value.stock_status_distribution || stats.value.stock_status_distribution.length === 0) {
        return { labels: [], datasets: [] };
    }
    var distribution = stats.value.stock_status_distribution;
    return {
        labels: distribution.map(function (item) { return item.stock_status; }),
        datasets: [
            {
                label: 'Products',
                data: distribution.map(function (item) { return item.count; }),
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
                borderColor: ['#059669', '#d97706', '#dc2626', '#4b5563'],
                borderWidth: 2,
                borderRadius: 8
            }
        ]
    };
});
var priceRangeChartData = (0, vue_1.computed)(function () {
    if (!stats.value.price_range_distribution || stats.value.price_range_distribution.length === 0) {
        return { labels: [], datasets: [] };
    }
    var distribution = stats.value.price_range_distribution;
    return {
        labels: distribution.map(function (item) { return item.range; }),
        datasets: [
            {
                label: 'Products',
                data: distribution.map(function (item) { return item.count; }),
                backgroundColor: '#f59e0b',
                borderColor: '#d97706',
                borderWidth: 2,
                borderRadius: 8
            }
        ]
    };
});
var featuresChartData = (0, vue_1.computed)(function () {
    return {
        labels: ['Featured', 'New Arrivals', 'Bestsellers', 'With 3D', 'With Images'],
        datasets: [
            {
                data: [
                    stats.value.featured_count || 0,
                    stats.value.new_arrival_count || 0,
                    stats.value.bestseller_count || 0,
                    stats.value.total_3d_models || 0,
                    stats.value.total_images || 0
                ],
                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#8b5cf6', '#3b82f6'],
                borderColor: '#fff',
                borderWidth: 2
            }
        ]
    };
});
var pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                usePointStyle: true,
                padding: 15
            }
        }
    }
};
var barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1
            }
        }
    }
};
var polarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
};
// Methods
var loadDashboardStats = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, productsResponse, lowStockResponse, error_1;
    var _a, _b;
    return __generator(this, function (_c) {
        switch (_c.label) {
            case 0:
                loading.value = true;
                _c.label = 1;
            case 1:
                _c.trys.push([1, 5, 6, 7]);
                return [4 /*yield*/, merchandising_service_1.default.getDashboardStats()];
            case 2:
                response = _c.sent();
                stats.value = __assign(__assign({}, stats.value), response.data);
                return [4 /*yield*/, merchandising_service_1.default.getProducts({
                        per_page: 5,
                        sort_by: 'created_at',
                        sort_order: 'desc'
                    })];
            case 3:
                productsResponse = _c.sent();
                recentProducts.value = productsResponse.data.data || [];
                return [4 /*yield*/, merchandising_service_1.default.getProducts({
                        stock_status: 'Low Stock',
                        per_page: 6
                    })];
            case 4:
                lowStockResponse = _c.sent();
                lowStockProducts.value = lowStockResponse.data.data || [];
                return [3 /*break*/, 7];
            case 5:
                error_1 = _c.sent();
                console.error('Failed to load dashboard stats:', error_1);
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: ((_b = (_a = error_1.response) === null || _a === void 0 ? void 0 : _a.data) === null || _b === void 0 ? void 0 : _b.message) || 'Failed to load dashboard statistics',
                    life: 3000
                });
                return [3 /*break*/, 7];
            case 6:
                loading.value = false;
                return [7 /*endfinally*/];
            case 7: return [2 /*return*/];
        }
    });
}); };
var loadActivityLog = function () { return __awaiter(void 0, void 0, void 0, function () {
    var response, error_2;
    return __generator(this, function (_a) {
        switch (_a.label) {
            case 0:
                loadingActivity.value = true;
                _a.label = 1;
            case 1:
                _a.trys.push([1, 3, 4, 5]);
                return [4 /*yield*/, merchandising_service_1.default.getActivityLog({ per_page: 10 })];
            case 2:
                response = _a.sent();
                activityLog.value = response.data.data || [];
                return [3 /*break*/, 5];
            case 3:
                error_2 = _a.sent();
                console.error('Failed to load activity log:', error_2);
                // Don't show error toast for activity log, just keep it empty
                activityLog.value = [];
                return [3 /*break*/, 5];
            case 4:
                loadingActivity.value = false;
                return [7 /*endfinally*/];
            case 5: return [2 /*return*/];
        }
    });
}); };
var getStockSeverity = function (status) {
    var severities = {
        'In Stock': 'success',
        'Low Stock': 'warning',
        'Out of Stock': 'danger',
        'Pre-order': 'info'
    };
    return severities[status] || 'secondary';
};
var getActivityColor = function (action) {
    var colors = {
        'created': 'bg-green-500',
        'updated': 'bg-blue-500',
        'deleted': 'bg-red-500',
        'uploaded': 'bg-purple-500',
        'price_changed': 'bg-yellow-500'
    };
    return colors[action] || 'bg-gray-500';
};
var getActivityIcon = function (action) {
    var icons = {
        'created': 'pi pi-plus',
        'updated': 'pi pi-pencil',
        'deleted': 'pi pi-trash',
        'uploaded': 'pi pi-upload',
        'price_changed': 'pi pi-dollar'
    };
    return icons[action] || 'pi pi-info-circle';
};
var getActivitySeverity = function (action) {
    var severities = {
        'created': 'success',
        'updated': 'info',
        'deleted': 'danger',
        'uploaded': 'secondary',
        'price_changed': 'warning'
    };
    return severities[action] || 'secondary';
};
var formatPrice = function (price) {
    if (!price || isNaN(price))
        return '0.00';
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
};
var formatFileSize = function (bytes) {
    if (!bytes || bytes === 0 || isNaN(bytes))
        return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
var formatRelativeTime = function (dateString) {
    if (!dateString)
        return 'N/A';
    var date = new Date(dateString);
    var now = new Date();
    var diff = now.getTime() - date.getTime();
    var minutes = Math.floor(diff / 60000);
    var hours = Math.floor(diff / 3600000);
    var days = Math.floor(diff / 86400000);
    if (minutes < 1)
        return 'Just now';
    if (minutes < 60)
        return "".concat(minutes, "m ago");
    if (hours < 24)
        return "".concat(hours, "h ago");
    if (days < 7)
        return "".concat(days, "d ago");
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
(0, vue_1.onMounted)(function () {
    loadDashboardStats();
    loadActivityLog();
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
if (__VLS_ctx.loading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    for (var _i = 0, _a = __VLS_vFor((8)); _i < _a.length; _i++) {
        var i = _a[_i][0];
        var __VLS_0 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })));
        var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ key: (i), height: "120px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [loading,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    for (var _b = 0, _c = __VLS_vFor((4)); _b < _c.length; _b++) {
        var i = _c[_b][0];
        var __VLS_5 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
        skeleton_1.default;
        // @ts-ignore
        var __VLS_6 = __VLS_asFunctionalComponent1(__VLS_5, new __VLS_5(__assign({ key: (i), height: "320px" }, { class: "rounded-lg" })));
        var __VLS_7 = __VLS_6.apply(void 0, __spreadArray([__assign({ key: (i), height: "320px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_6), false));
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        // @ts-ignore
        [];
    }
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_10 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_11 = __VLS_asFunctionalComponent1(__VLS_10, new __VLS_10(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_12 = __VLS_11.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_11), false));
    var __VLS_15 = void 0;
    var __VLS_16 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.products' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_17 = __VLS_13.slots.default;
    {
        var __VLS_18 = __VLS_13.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.total_products);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-green-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
        (__VLS_ctx.stats.active_products);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-3xl text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        // @ts-ignore
        [stats, stats,];
    }
    // @ts-ignore
    [];
    var __VLS_13;
    var __VLS_14;
    var __VLS_19 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_20 = __VLS_asFunctionalComponent1(__VLS_19, new __VLS_19(__assign({ class: "hover:shadow-lg transition-shadow" })));
    var __VLS_21 = __VLS_20.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_20), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_24 = __VLS_22.slots.default;
    {
        var __VLS_25 = __VLS_22.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.total_categories);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-tag" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-tag']} */ ;
        (__VLS_ctx.stats.total_subcategories);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-purple-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-tags text-3xl text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-tags']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        // @ts-ignore
        [stats, stats,];
    }
    // @ts-ignore
    [];
    var __VLS_22;
    var __VLS_26 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_27 = __VLS_asFunctionalComponent1(__VLS_26, new __VLS_26(__assign({ class: "hover:shadow-lg transition-shadow" })));
    var __VLS_28 = __VLS_27.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_27), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_31 = __VLS_29.slots.default;
    {
        var __VLS_32 = __VLS_29.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.in_stock_products);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-yellow-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
        (__VLS_ctx.stats.low_stock_products);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-3xl text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        // @ts-ignore
        [stats, stats,];
    }
    // @ts-ignore
    [];
    var __VLS_29;
    var __VLS_33 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ class: "hover:shadow-lg transition-shadow" })));
    var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_34), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_38 = __VLS_36.slots.default;
    {
        var __VLS_39 = __VLS_36.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatPrice(__VLS_ctx.stats.total_inventory_value));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-yellow-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-yellow-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar text-3xl text-yellow-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        // @ts-ignore
        [stats, formatPrice,];
    }
    // @ts-ignore
    [];
    var __VLS_36;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_40 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
    var __VLS_45 = void 0;
    var __VLS_46 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.3d-gallery' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_47 = __VLS_43.slots.default;
    {
        var __VLS_48 = __VLS_43.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.total_3d_models);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-indigo-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
        (__VLS_ctx.formatFileSize(__VLS_ctx.stats.total_3d_size));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-indigo-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-indigo-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-cube text-3xl text-indigo-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-cube']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-indigo-600']} */ ;
        // @ts-ignore
        [stats, stats, formatFileSize,];
    }
    // @ts-ignore
    [];
    var __VLS_43;
    var __VLS_44;
    var __VLS_49 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_50 = __VLS_asFunctionalComponent1(__VLS_49, new __VLS_49(__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })));
    var __VLS_51 = __VLS_50.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { class: "hover:shadow-lg transition-shadow cursor-pointer" })], __VLS_functionalComponentArgsRest(__VLS_50), false));
    var __VLS_54 = void 0;
    var __VLS_55 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(__VLS_ctx.loading))
                    return;
                __VLS_ctx.router.push({ name: 'merchandising.assets' });
                // @ts-ignore
                [router,];
            } });
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
    var __VLS_56 = __VLS_52.slots.default;
    {
        var __VLS_57 = __VLS_52.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.total_images);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-blue-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-image" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-image']} */ ;
        (__VLS_ctx.formatFileSize(__VLS_ctx.stats.total_image_size));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-image text-3xl text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-image']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        // @ts-ignore
        [stats, stats, formatFileSize,];
    }
    // @ts-ignore
    [];
    var __VLS_52;
    var __VLS_53;
    var __VLS_58 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ class: "hover:shadow-lg transition-shadow" })));
    var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_59), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_63 = __VLS_61.slots.default;
    {
        var __VLS_64 = __VLS_61.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-3xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.stats.total_variations);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-purple-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-th-large" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-th-large']} */ ;
        (__VLS_ctx.stats.active_variations);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-purple-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-th-large text-3xl text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-th-large']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        // @ts-ignore
        [stats, stats,];
    }
    // @ts-ignore
    [];
    var __VLS_61;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ class: "hover:shadow-lg transition-shadow" })));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ class: "hover:shadow-lg transition-shadow" })], __VLS_functionalComponentArgsRest(__VLS_66), false));
    /** @type {__VLS_StyleScopedClasses['hover:shadow-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    var __VLS_70 = __VLS_68.slots.default;
    {
        var __VLS_71 = __VLS_68.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-600 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-2xl font-bold text-gray-900" }));
        /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
        (__VLS_ctx.formatPrice(__VLS_ctx.stats.average_price));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-line" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-chart-line']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-orange-100 p-4 rounded-full" }));
        /** @type {__VLS_StyleScopedClasses['bg-orange-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-line text-3xl text-orange-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-chart-line']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-orange-600']} */ ;
        // @ts-ignore
        [stats, formatPrice,];
    }
    // @ts-ignore
    [];
    var __VLS_68;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_72 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_73 = __VLS_asFunctionalComponent1(__VLS_72, new __VLS_72({}));
    var __VLS_74 = __VLS_73.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_73), false));
    var __VLS_77 = __VLS_75.slots.default;
    {
        var __VLS_78 = __VLS_75.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-pie text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-chart-pie']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_79 = __VLS_75.slots.content;
        if (__VLS_ctx.categoryChartData.labels.length > 0) {
            var __VLS_80 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            chart_1.default;
            // @ts-ignore
            var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ type: "doughnut", data: (__VLS_ctx.categoryChartData), options: (__VLS_ctx.pieChartOptions) }, { class: "h-80" })));
            var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ type: "doughnut", data: (__VLS_ctx.categoryChartData), options: (__VLS_ctx.pieChartOptions) }, { class: "h-80" })], __VLS_functionalComponentArgsRest(__VLS_81), false));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-80 flex items-center justify-center text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-pie text-6xl text-gray-300 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-chart-pie']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        // @ts-ignore
        [categoryChartData, categoryChartData, pieChartOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_75;
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85({}));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_86), false));
    var __VLS_90 = __VLS_88.slots.default;
    {
        var __VLS_91 = __VLS_88.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-bar text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-chart-bar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_92 = __VLS_88.slots.content;
        if (__VLS_ctx.stockChartData.labels.length > 0) {
            var __VLS_93 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            chart_1.default;
            // @ts-ignore
            var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93(__assign({ type: "bar", data: (__VLS_ctx.stockChartData), options: (__VLS_ctx.barChartOptions) }, { class: "h-80" })));
            var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([__assign({ type: "bar", data: (__VLS_ctx.stockChartData), options: (__VLS_ctx.barChartOptions) }, { class: "h-80" })], __VLS_functionalComponentArgsRest(__VLS_94), false));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-80 flex items-center justify-center text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-bar text-6xl text-gray-300 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-chart-bar']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        // @ts-ignore
        [stockChartData, stockChartData, barChartOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_88;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    var __VLS_98 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_99 = __VLS_asFunctionalComponent1(__VLS_98, new __VLS_98({}));
    var __VLS_100 = __VLS_99.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_99), false));
    var __VLS_103 = __VLS_101.slots.default;
    {
        var __VLS_104 = __VLS_101.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar text-yellow-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_105 = __VLS_101.slots.content;
        if (__VLS_ctx.priceRangeChartData.labels.length > 0) {
            var __VLS_106 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            chart_1.default;
            // @ts-ignore
            var __VLS_107 = __VLS_asFunctionalComponent1(__VLS_106, new __VLS_106(__assign({ type: "bar", data: (__VLS_ctx.priceRangeChartData), options: (__VLS_ctx.barChartOptions) }, { class: "h-80" })));
            var __VLS_108 = __VLS_107.apply(void 0, __spreadArray([__assign({ type: "bar", data: (__VLS_ctx.priceRangeChartData), options: (__VLS_ctx.barChartOptions) }, { class: "h-80" })], __VLS_functionalComponentArgsRest(__VLS_107), false));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-80 flex items-center justify-center text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-dollar text-6xl text-gray-300 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-dollar']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        // @ts-ignore
        [barChartOptions, priceRangeChartData, priceRangeChartData,];
    }
    // @ts-ignore
    [];
    var __VLS_101;
    var __VLS_111 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111({}));
    var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_112), false));
    var __VLS_116 = __VLS_114.slots.default;
    {
        var __VLS_117 = __VLS_114.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-star text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-star']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_118 = __VLS_114.slots.content;
        if (__VLS_ctx.featuresChartData.labels.length > 0) {
            var __VLS_119 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Chart} */
            chart_1.default;
            // @ts-ignore
            var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119(__assign({ type: "polarArea", data: (__VLS_ctx.featuresChartData), options: (__VLS_ctx.polarChartOptions) }, { class: "h-80" })));
            var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([__assign({ type: "polarArea", data: (__VLS_ctx.featuresChartData), options: (__VLS_ctx.polarChartOptions) }, { class: "h-80" })], __VLS_functionalComponentArgsRest(__VLS_120), false));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-80 flex items-center justify-center text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['h-80']} */ ;
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-star text-6xl text-gray-300 mb-3 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-star']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-6xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-300']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        // @ts-ignore
        [featuresChartData, featuresChartData, polarChartOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_114;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-3 gap-6" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    var __VLS_124 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124({}));
    var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_125), false));
    var __VLS_129 = __VLS_127.slots.default;
    {
        var __VLS_130 = __VLS_127.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        var __VLS_131 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })));
        var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
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
                    __VLS_ctx.router.push({ name: 'merchandising.products' });
                    // @ts-ignore
                    [router,];
                } });
        var __VLS_134;
        var __VLS_135;
        // @ts-ignore
        [];
    }
    {
        var __VLS_138 = __VLS_127.slots.content;
        if (__VLS_ctx.recentProducts.length === 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl mb-2 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        else {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
            /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
            var _loop_1 = function (product) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!!(__VLS_ctx.recentProducts.length === 0))
                            return;
                        __VLS_ctx.router.push({ name: 'merchandising.products.view', params: { id: product.id } });
                        // @ts-ignore
                        [router, recentProducts, recentProducts,];
                    } }, { key: (product.id) }), { class: "flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
                /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-linear-to-br from-blue-100 to-blue-200 rounded flex items-center justify-center" }));
                /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-linear-to-br']} */ ;
                /** @type {__VLS_StyleScopedClasses['from-blue-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['to-blue-200']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1 min-w-0" }));
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                /** @type {__VLS_StyleScopedClasses['min-w-0']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-sm text-gray-900 truncate" }));
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
                /** @type {__VLS_StyleScopedClasses['truncate']} */ ;
                (product.product_name);
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                (product.sku);
                var __VLS_139 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_140 = __VLS_asFunctionalComponent1(__VLS_139, new __VLS_139({
                    value: (product.stock_status),
                    severity: (__VLS_ctx.getStockSeverity(product.stock_status)),
                    size: "small",
                }));
                var __VLS_141 = __VLS_140.apply(void 0, __spreadArray([{
                        value: (product.stock_status),
                        severity: (__VLS_ctx.getStockSeverity(product.stock_status)),
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_140), false));
                // @ts-ignore
                [getStockSeverity,];
            };
            for (var _d = 0, _e = __VLS_vFor((__VLS_ctx.recentProducts)); _d < _e.length; _d++) {
                var product = _e[_d][0];
                _loop_1(product);
            }
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_127;
    var __VLS_144 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144(__assign({ class: "lg:col-span-2" })));
    var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([__assign({ class: "lg:col-span-2" })], __VLS_functionalComponentArgsRest(__VLS_145), false));
    /** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
    var __VLS_149 = __VLS_147.slots.default;
    {
        var __VLS_150 = __VLS_147.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-history text-purple-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-history']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        var __VLS_151 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151(__assign({ 'onClick': {} }, { icon: "pi pi-refresh", text: true, rounded: true, size: "small" })));
        var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-refresh", text: true, rounded: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_152), false));
        var __VLS_156 = void 0;
        var __VLS_157 = ({ click: {} },
            { onClick: (__VLS_ctx.loadActivityLog) });
        var __VLS_154;
        var __VLS_155;
        // @ts-ignore
        [loadActivityLog,];
    }
    {
        var __VLS_158 = __VLS_147.slots.content;
        if (__VLS_ctx.loadingActivity) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
            /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
            for (var _f = 0, _g = __VLS_vFor((5)); _f < _g.length; _f++) {
                var i = _g[_f][0];
                var __VLS_159 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Skeleton} */
                skeleton_1.default;
                // @ts-ignore
                var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ key: (i), height: "60px" }, { class: "rounded-lg" })));
                var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ key: (i), height: "60px" }, { class: "rounded-lg" })], __VLS_functionalComponentArgsRest(__VLS_160), false));
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                // @ts-ignore
                [loadingActivity,];
            }
        }
        else if (__VLS_ctx.activityLog.length === 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
            /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl mb-2 block" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
            /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['block']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
        }
        else {
            var __VLS_164 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Timeline | typeof __VLS_components.Timeline} */
            timeline_1.default;
            // @ts-ignore
            var __VLS_165 = __VLS_asFunctionalComponent1(__VLS_164, new __VLS_164(__assign({ value: (__VLS_ctx.activityLog) }, { class: "customized-timeline" })));
            var __VLS_166 = __VLS_165.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.activityLog) }, { class: "customized-timeline" })], __VLS_functionalComponentArgsRest(__VLS_165), false));
            /** @type {__VLS_StyleScopedClasses['customized-timeline']} */ ;
            var __VLS_169 = __VLS_167.slots.default;
            {
                var __VLS_170 = __VLS_167.slots.marker;
                var item = __VLS_vSlot(__VLS_170)[0].item;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: (['flex items-center justify-center w-8 h-8 rounded-full', __VLS_ctx.getActivityColor(item.action)]) }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['w-8']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-8']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: (['text-white text-sm', __VLS_ctx.getActivityIcon(item.action)]) }));
                /** @type {__VLS_StyleScopedClasses['text-white']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                // @ts-ignore
                [activityLog, activityLog, getActivityColor, getActivityIcon,];
            }
            {
                var __VLS_171 = __VLS_167.slots.content;
                var item = __VLS_vSlot(__VLS_171)[0].item;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors" }));
                /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start justify-between gap-3" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-sm text-gray-900" }));
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
                (item.description);
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-600 mt-1" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
                (item.details);
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2 mt-2" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
                /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user text-xs" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-user']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                (item.user);
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-400" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-xs text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                (__VLS_ctx.formatRelativeTime(item.created_at));
                var __VLS_172 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_173 = __VLS_asFunctionalComponent1(__VLS_172, new __VLS_172({
                    value: (item.action),
                    severity: (__VLS_ctx.getActivitySeverity(item.action)),
                    size: "small",
                }));
                var __VLS_174 = __VLS_173.apply(void 0, __spreadArray([{
                        value: (item.action),
                        severity: (__VLS_ctx.getActivitySeverity(item.action)),
                        size: "small",
                    }], __VLS_functionalComponentArgsRest(__VLS_173), false));
                // @ts-ignore
                [formatRelativeTime, getActivitySeverity,];
            }
            // @ts-ignore
            [];
            var __VLS_167;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_147;
    if (__VLS_ctx.lowStockProducts.length > 0) {
        var __VLS_177 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
        card_1.default;
        // @ts-ignore
        var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177(__assign({ class: "mt-6 border-l-4 border-yellow-500" })));
        var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([__assign({ class: "mt-6 border-l-4 border-yellow-500" })], __VLS_functionalComponentArgsRest(__VLS_178), false));
        /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-l-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['border-yellow-500']} */ ;
        var __VLS_182 = __VLS_180.slots.default;
        {
            var __VLS_183 = __VLS_180.slots.title;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
            /** @type {__VLS_StyleScopedClasses['flex']} */ ;
            /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-yellow-600" }));
            /** @type {__VLS_StyleScopedClasses['pi']} */ ;
            /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
            /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            var __VLS_184 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Badge} */
            badge_1.default;
            // @ts-ignore
            var __VLS_185 = __VLS_asFunctionalComponent1(__VLS_184, new __VLS_184({
                value: (__VLS_ctx.lowStockProducts.length),
                severity: "warning",
                size: "large",
            }));
            var __VLS_186 = __VLS_185.apply(void 0, __spreadArray([{
                    value: (__VLS_ctx.lowStockProducts.length),
                    severity: "warning",
                    size: "large",
                }], __VLS_functionalComponentArgsRest(__VLS_185), false));
            // @ts-ignore
            [lowStockProducts, lowStockProducts,];
        }
        {
            var __VLS_189 = __VLS_180.slots.content;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" }));
            /** @type {__VLS_StyleScopedClasses['grid']} */ ;
            /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
            /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
            /** @type {__VLS_StyleScopedClasses['lg:grid-cols-3']} */ ;
            /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
            var _loop_2 = function (product) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign(__assign({ onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!!(__VLS_ctx.loading))
                            return;
                        if (!(__VLS_ctx.lowStockProducts.length > 0))
                            return;
                        __VLS_ctx.router.push({ name: 'merchandising.products.edit', params: { id: product.id } });
                        // @ts-ignore
                        [router, lowStockProducts,];
                    } }, { key: (product.id) }), { class: "flex items-center gap-3 p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors cursor-pointer border border-yellow-200" }));
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
                /** @type {__VLS_StyleScopedClasses['hover:bg-yellow-100']} */ ;
                /** @type {__VLS_StyleScopedClasses['transition-colors']} */ ;
                /** @type {__VLS_StyleScopedClasses['cursor-pointer']} */ ;
                /** @type {__VLS_StyleScopedClasses['border']} */ ;
                /** @type {__VLS_StyleScopedClasses['border-yellow-200']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-yellow-200 rounded flex items-center justify-center" }));
                /** @type {__VLS_StyleScopedClasses['w-12']} */ ;
                /** @type {__VLS_StyleScopedClasses['h-12']} */ ;
                /** @type {__VLS_StyleScopedClasses['bg-yellow-200']} */ ;
                /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
                /** @type {__VLS_StyleScopedClasses['flex']} */ ;
                /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
                /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-yellow-600" }));
                /** @type {__VLS_StyleScopedClasses['pi']} */ ;
                /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
                /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-semibold text-sm text-gray-900" }));
                /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-900']} */ ;
                (product.product_name);
                __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
                /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
                /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
                (product.sku);
                var __VLS_190 = void 0;
                /** @ts-ignore @type {typeof __VLS_components.Tag} */
                tag_1.default;
                // @ts-ignore
                var __VLS_191 = __VLS_asFunctionalComponent1(__VLS_190, new __VLS_190({
                    value: "Low Stock",
                    severity: "warning",
                }));
                var __VLS_192 = __VLS_191.apply(void 0, __spreadArray([{
                        value: "Low Stock",
                        severity: "warning",
                    }], __VLS_functionalComponentArgsRest(__VLS_191), false));
                // @ts-ignore
                [];
            };
            for (var _h = 0, _j = __VLS_vFor((__VLS_ctx.lowStockProducts)); _h < _j.length; _h++) {
                var product = _j[_h][0];
                _loop_2(product);
            }
            // @ts-ignore
            [];
        }
        // @ts-ignore
        [];
        var __VLS_180;
    }
    var __VLS_195 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Card | typeof __VLS_components.Card} */
    card_1.default;
    // @ts-ignore
    var __VLS_196 = __VLS_asFunctionalComponent1(__VLS_195, new __VLS_195(__assign({ class: "mt-6" })));
    var __VLS_197 = __VLS_196.apply(void 0, __spreadArray([__assign({ class: "mt-6" })], __VLS_functionalComponentArgsRest(__VLS_196), false));
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    var __VLS_200 = __VLS_198.slots.default;
    {
        var __VLS_201 = __VLS_198.slots.title;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-bolt text-yellow-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-bolt']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        // @ts-ignore
        [];
    }
    {
        var __VLS_202 = __VLS_198.slots.content;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 md:grid-cols-4 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        var __VLS_203 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_204 = __VLS_asFunctionalComponent1(__VLS_203, new __VLS_203(__assign(__assign({ 'onClick': {} }, { label: "Add Product", icon: "pi pi-plus" }), { class: "w-full" })));
        var __VLS_205 = __VLS_204.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Add Product", icon: "pi pi-plus" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_204), false));
        var __VLS_208 = void 0;
        var __VLS_209 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'merchandising.products.create' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_206;
        var __VLS_207;
        var __VLS_210 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_211 = __VLS_asFunctionalComponent1(__VLS_210, new __VLS_210(__assign(__assign({ 'onClick': {} }, { label: "View Products", icon: "pi pi-list", severity: "secondary" }), { class: "w-full" })));
        var __VLS_212 = __VLS_211.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "View Products", icon: "pi pi-list", severity: "secondary" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_211), false));
        var __VLS_215 = void 0;
        var __VLS_216 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'merchandising.products' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_213;
        var __VLS_214;
        var __VLS_217 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_218 = __VLS_asFunctionalComponent1(__VLS_217, new __VLS_217(__assign(__assign({ 'onClick': {} }, { label: "3D Gallery", icon: "pi pi-cube", severity: "info" }), { class: "w-full" })));
        var __VLS_219 = __VLS_218.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "3D Gallery", icon: "pi pi-cube", severity: "info" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_218), false));
        var __VLS_222 = void 0;
        var __VLS_223 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'merchandising.3d-gallery' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_220;
        var __VLS_221;
        var __VLS_224 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_225 = __VLS_asFunctionalComponent1(__VLS_224, new __VLS_224(__assign(__assign({ 'onClick': {} }, { label: "Upload Assets", icon: "pi pi-cloud-upload", severity: "success" }), { class: "w-full" })));
        var __VLS_226 = __VLS_225.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { label: "Upload Assets", icon: "pi pi-cloud-upload", severity: "success" }), { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_225), false));
        var __VLS_229 = void 0;
        var __VLS_230 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.loading))
                        return;
                    __VLS_ctx.router.push({ name: 'merchandising.assets.upload' });
                    // @ts-ignore
                    [router,];
                } });
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        var __VLS_227;
        var __VLS_228;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_198;
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
