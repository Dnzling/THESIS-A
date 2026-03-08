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
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var chart_js_1 = require("chart.js");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var inputtext_1 = require("primevue/inputtext");
var tag_1 = require("primevue/tag");
var auth_1 = require("../../../stores/auth");
// Register Chart.js components
chart_js_1.Chart.register.apply(chart_js_1.Chart, chart_js_1.registerables);
var authStore = (0, auth_1.useAuthStore)();
var hasStore = (0, vue_1.ref)(!!((_a = authStore.user.store) === null || _a === void 0 ? void 0 : _a.id));
var hasProduct = (0, vue_1.ref)(false);
var router = (0, vue_router_1.useRouter)();
// Chart references
var revenueChartCanvas = (0, vue_1.ref)(null);
var revenueChart = null;
// Chart view state
var chartView = (0, vue_1.ref)('monthly');
var totalRevenue = (0, vue_1.ref)(0);
// Dashboard stats
var totalProducts = (0, vue_1.ref)(1248);
var monthlyRevenue = (0, vue_1.ref)(325840.25);
var activeOrders = (0, vue_1.ref)(48);
var lowStockItems = (0, vue_1.ref)(16);
// Static revenue data
var revenueData = {
    daily: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        data: [45200, 52000, 48300, 61000, 72500, 89500, 67200]
    },
    monthly: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        data: [285000, 312000, 295000, 325840, 342000, 367500, 389200, 375000, 402000, 418500, 435000, 462000]
    },
    yearly: {
        labels: ['2020', '2021', '2022', '2023', '2024'],
        data: [2850000, 3125000, 3678000, 4250000, 4620000]
    }
};
var topProducts = (0, vue_1.ref)([
    { id: 1, name: 'Modern Sofa Set', category: 'Living Room', revenue: 125000, sold: 25 },
    { id: 2, name: 'Wooden Dining Table', category: 'Dining Room', revenue: 98750, sold: 19 },
    { id: 3, name: 'Office Chair', category: 'Office', revenue: 76500, sold: 51 },
    { id: 4, name: 'King Size Bed', category: 'Bedroom', revenue: 64200, sold: 12 },
    { id: 5, name: 'Bookshelf', category: 'Study', revenue: 53800, sold: 22 }
]);
var recentActivities = (0, vue_1.ref)([
    {
        id: 1,
        description: 'New order #ORD-2024-0012 placed',
        time: '10 minutes ago',
        icon: 'pi pi-shopping-cart',
        iconColor: 'text-blue-600',
        bgColor: 'bg-blue-100',
        status: 'Pending',
        statusColor: 'bg-yellow-100 text-yellow-800'
    },
    {
        id: 2,
        description: 'Product "Modern Sofa" stock updated',
        time: '30 minutes ago',
        icon: 'pi pi-box',
        iconColor: 'text-green-600',
        bgColor: 'bg-green-100',
        status: 'Updated',
        statusColor: 'bg-green-100 text-green-800'
    },
    {
        id: 3,
        description: 'Monthly sales report generated',
        time: '2 hours ago',
        icon: 'pi pi-file-pdf',
        iconColor: 'text-purple-600',
        bgColor: 'bg-purple-100',
        status: 'Completed',
        statusColor: 'bg-blue-100 text-blue-800'
    },
    {
        id: 4,
        description: 'Supplier payment processed',
        time: '5 hours ago',
        icon: 'pi pi-dollar',
        iconColor: 'text-green-600',
        bgColor: 'bg-green-100',
        status: 'Paid',
        statusColor: 'bg-green-100 text-green-800'
    }
]);
var goToStoreRegistration = function () {
    router.push('/system/store/verification');
};
var goToProductRegistration = function () {
    router.push('/system/productRegistration');
};
// Initialize and render chart
var initChart = function () {
    if (!revenueChartCanvas.value)
        return;
    // Destroy existing chart if it exists
    if (revenueChart) {
        revenueChart.destroy();
    }
    var ctx = revenueChartCanvas.value.getContext('2d');
    if (!ctx)
        return;
    var currentData = revenueData[chartView.value];
    // Calculate total revenue for current view
    totalRevenue.value = currentData.data.reduce(function (sum, value) { return sum + value; }, 0);
    revenueChart = new chart_js_1.Chart(ctx, {
        type: 'line',
        data: {
            labels: currentData.labels,
            datasets: [{
                    label: 'Revenue (₱)',
                    data: currentData.data,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (context) {
                            return "\u20B1".concat(context.parsed.y.toLocaleString());
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function (value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
};
// Set chart view and update
var setChartView = function (view) {
    chartView.value = view;
};
// Watch for chart view changes
(0, vue_1.watch)(chartView, function () {
    initChart();
});
(0, vue_1.onMounted)(function () {
    // const storeExists = localStorage.getItem('hasStore');
    // const productExists = localStorage.getItem('hasProduct')
    // hasStore.value = storeExists === 'true'
    // hasProduct.value = productExists === 'true'
    // Initialize chart after component is mounted
    setTimeout(function () {
        initChart();
    }, 100);
});
(0, vue_1.onUnmounted)(function () {
    // Clean up chart instance
    if (revenueChart) {
        revenueChart.destroy();
    }
});
// Add these inside your script setup
var searchTerm = (0, vue_1.ref)('');
// Static inventory data with productId field
var inventoryItems = (0, vue_1.ref)([
    { productId: 'FUR-001', productName: 'Modern Sofa Set', category: 'Living Room', currentStock: 45, maxStock: 100, status: 'In Stock' },
    { productId: 'FUR-002', productName: 'Wooden Dining Table', category: 'Dining Room', currentStock: 12, maxStock: 50, status: 'In Stock' },
    { productId: 'FUR-003', productName: 'Office Chair', category: 'Office', currentStock: 5, maxStock: 30, status: 'Low Stock' },
    { productId: 'FUR-004', productName: 'King Size Bed', category: 'Bedroom', currentStock: 8, maxStock: 25, status: 'In Stock' },
    { productId: 'FUR-005', productName: 'Bookshelf', category: 'Study', currentStock: 22, maxStock: 40, status: 'In Stock' },
    { productId: 'FUR-006', productName: 'Coffee Table', category: 'Living Room', currentStock: 3, maxStock: 20, status: 'Low Stock' },
    { productId: 'FUR-007', productName: 'Dining Chair Set', category: 'Dining Room', currentStock: 0, maxStock: 60, status: 'Out of Stock' },
    { productId: 'FUR-008', productName: 'Desk Lamp', category: 'Office', currentStock: 18, maxStock: 35, status: 'In Stock' },
    { productId: 'FUR-009', productName: 'Wardrobe', category: 'Bedroom', currentStock: 7, maxStock: 15, status: 'Low Stock' },
    { productId: 'FUR-010', productName: 'TV Stand', category: 'Living Room', currentStock: 25, maxStock: 45, status: 'In Stock' },
    { productId: 'FUR-011', productName: 'Bar Stool', category: 'Dining Room', currentStock: 30, maxStock: 50, status: 'In Stock' },
    { productId: 'FUR-012', productName: 'Filing Cabinet', category: 'Office', currentStock: 2, maxStock: 10, status: 'Low Stock' },
    { productId: 'FUR-013', productName: 'Nightstand', category: 'Bedroom', currentStock: 15, maxStock: 25, status: 'In Stock' },
    { productId: 'FUR-014', productName: 'Recliner Chair', category: 'Living Room', currentStock: 0, maxStock: 12, status: 'Out of Stock' },
    { productId: 'FUR-015', productName: 'Buffet Table', category: 'Dining Room', currentStock: 9, maxStock: 18, status: 'In Stock' },
]);
// Filtered items computed property
var filteredInventoryItems = (0, vue_1.computed)(function () {
    if (!searchTerm.value)
        return inventoryItems.value;
    var term = searchTerm.value.toLowerCase();
    return inventoryItems.value.filter(function (item) {
        return item.productId.toLowerCase().includes(term) ||
            item.productName.toLowerCase().includes(term) ||
            item.category.toLowerCase().includes(term) ||
            item.status.toLowerCase().includes(term);
    });
});
// Helper functions
var getStatusSeverity = function (status) {
    switch (status) {
        case 'In Stock': return 'success';
        case 'Low Stock': return 'warning';
        case 'Out of Stock': return 'danger';
        default: return 'info';
    }
};
var getStatusDotColor = function (status) {
    switch (status) {
        case 'In Stock': return 'bg-green-500';
        case 'Low Stock': return 'bg-yellow-500';
        case 'Out of Stock': return 'bg-red-500';
        default: return 'bg-gray-400';
    }
};
var getStatusCount = function (status) {
    return inventoryItems.value.filter(function (item) { return item.status === status; }).length;
};
var viewInventoryItem = function (item) {
    // Implement view logic
    console.log('View item:', item);
};
var editInventoryItem = function (item) {
    // Implement edit logic
    console.log('Edit item:', item);
};
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
if (!__VLS_ctx.hasStore) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: " bg-gray-50 flex items-center justify-center p-6" }));
    /** @type {__VLS_StyleScopedClasses['']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center max-w-md" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['max-w-md']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-8" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    var __VLS_0 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign(__assign({ 'onClick': {} }, { severity: "info" }), { class: "inline-flex items-center gap-3" })));
    var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { severity: "info" }), { class: "inline-flex items-center gap-3" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
    var __VLS_5 = void 0;
    var __VLS_6 = ({ click: {} },
        { onClick: (__VLS_ctx.goToStoreRegistration) });
    /** @type {__VLS_StyleScopedClasses['inline-flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_7 = __VLS_3.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-plus-circle" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-plus-circle']} */ ;
    // @ts-ignore
    [hasStore, goToStoreRegistration,];
    var __VLS_3;
    var __VLS_4;
}
else if (!__VLS_ctx.hasProduct) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: " bg-gray-50 flex items-center justify-center p-6" }));
    /** @type {__VLS_StyleScopedClasses['']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center max-w-md" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['max-w-md']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold text-gray-800 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-8" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-8']} */ ;
    var __VLS_8 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_9 = __VLS_asFunctionalComponent1(__VLS_8, new __VLS_8(__assign(__assign({ 'onClick': {} }, { severity: "success" }), { class: "inline-flex items-center gap-3" })));
    var __VLS_10 = __VLS_9.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { severity: "success" }), { class: "inline-flex items-center gap-3" })], __VLS_functionalComponentArgsRest(__VLS_9), false));
    var __VLS_13 = void 0;
    var __VLS_14 = ({ click: {} },
        { onClick: (__VLS_ctx.goToProductRegistration) });
    /** @type {__VLS_StyleScopedClasses['inline-flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-3']} */ ;
    var __VLS_15 = __VLS_11.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-plus-circle" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-plus-circle']} */ ;
    // @ts-ignore
    [hasProduct, goToProductRegistration,];
    var __VLS_11;
    var __VLS_12;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-5" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-500 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-gray-800 my-2" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['my-2']} */ ;
    (__VLS_ctx.totalProducts);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-wallet text-green-500 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-wallet']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-gray-800 my-2" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['my-2']} */ ;
    (__VLS_ctx.monthlyRevenue.toLocaleString());
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-shopping-cart text-orange-500 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-shopping-cart']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-orange-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-gray-800 my-2" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['my-2']} */ ;
    (__VLS_ctx.activeOrders);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-red-500 text-lg" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-3xl font-bold text-gray-800 my-2" }));
    /** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['my-2']} */ ;
    (__VLS_ctx.lowStockItems);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6 mb-5" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
    /** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-5']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
    var __VLS_16 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_17 = __VLS_asFunctionalComponent1(__VLS_16, new __VLS_16(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'daily' ? 'primary' : 'secondary'), size: "small" })));
    var __VLS_18 = __VLS_17.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'daily' ? 'primary' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_17), false));
    var __VLS_21 = void 0;
    var __VLS_22 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(!__VLS_ctx.hasStore))
                    return;
                if (!!(!__VLS_ctx.hasProduct))
                    return;
                __VLS_ctx.setChartView('daily');
                // @ts-ignore
                [totalProducts, monthlyRevenue, activeOrders, lowStockItems, chartView, setChartView,];
            } });
    var __VLS_23 = __VLS_19.slots.default;
    // @ts-ignore
    [];
    var __VLS_19;
    var __VLS_20;
    var __VLS_24 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'monthly' ? 'primary' : 'secondary'), size: "small" })));
    var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'monthly' ? 'primary' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
    var __VLS_29 = void 0;
    var __VLS_30 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(!__VLS_ctx.hasStore))
                    return;
                if (!!(!__VLS_ctx.hasProduct))
                    return;
                __VLS_ctx.setChartView('monthly');
                // @ts-ignore
                [chartView, setChartView,];
            } });
    var __VLS_31 = __VLS_27.slots.default;
    // @ts-ignore
    [];
    var __VLS_27;
    var __VLS_28;
    var __VLS_32 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
    Button;
    // @ts-ignore
    var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'yearly' ? 'primary' : 'secondary'), size: "small" })));
    var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.chartView === 'yearly' ? 'primary' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
    var __VLS_37 = void 0;
    var __VLS_38 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!!(!__VLS_ctx.hasStore))
                    return;
                if (!!(!__VLS_ctx.hasProduct))
                    return;
                __VLS_ctx.setChartView('yearly');
                // @ts-ignore
                [chartView, setChartView,];
            } });
    var __VLS_39 = __VLS_35.slots.default;
    // @ts-ignore
    [];
    var __VLS_35;
    var __VLS_36;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-64" }));
    /** @type {__VLS_StyleScopedClasses['h-64']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.canvas, __VLS_intrinsics.canvas)({
        ref: "revenueChartCanvas",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4 text-center text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    (__VLS_ctx.totalRevenue.toLocaleString());
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _i = 0, _b = __VLS_vFor((__VLS_ctx.topProducts)); _i < _b.length; _i++) {
        var product = _b[_i][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (product.id) }, { class: "flex items-center justify-between p-3 hover:bg-gray-50 rounded" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-100 rounded flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (product.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (product.category);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
        /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (product.revenue.toLocaleString());
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (product.sold);
        // @ts-ignore
        [totalRevenue, topProducts,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    var __VLS_40 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search inventory..." }, { class: "w-64" })));
    var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search inventory..." }, { class: "w-64" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_45 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45({
        value: (__VLS_ctx.filteredInventoryItems),
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
        paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
        currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} products",
    }));
    var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredInventoryItems),
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
            paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
            currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} products",
        }], __VLS_functionalComponentArgsRest(__VLS_46), false));
    var __VLS_50 = __VLS_48.slots.default;
    var __VLS_51 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51(__assign({ field: "productId", header: "Product ID", sortable: true }, { style: {} })));
    var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([__assign({ field: "productId", header: "Product ID", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_52), false));
    var __VLS_56 = __VLS_54.slots.default;
    {
        var __VLS_57 = __VLS_54.slots.body;
        var slotProps = __VLS_vSlot(__VLS_57)[0];
        (slotProps.data.productId);
        // @ts-ignore
        [searchTerm, filteredInventoryItems,];
    }
    // @ts-ignore
    [];
    var __VLS_54;
    var __VLS_58 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_59 = __VLS_asFunctionalComponent1(__VLS_58, new __VLS_58(__assign({ field: "productName", header: "Product Name", sortable: true }, { style: {} })));
    var __VLS_60 = __VLS_59.apply(void 0, __spreadArray([__assign({ field: "productName", header: "Product Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_59), false));
    var __VLS_63 = __VLS_61.slots.default;
    {
        var __VLS_64 = __VLS_61.slots.body;
        var slotProps = __VLS_vSlot(__VLS_64)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-8 h-8 bg-blue-100 rounded flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-8']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-box text-blue-600 text-sm" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-box']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (slotProps.data.productName);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_61;
    var __VLS_65 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_66 = __VLS_asFunctionalComponent1(__VLS_65, new __VLS_65(__assign({ field: "category", header: "Category", sortable: true }, { style: {} })));
    var __VLS_67 = __VLS_66.apply(void 0, __spreadArray([__assign({ field: "category", header: "Category", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_66), false));
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70(__assign({ field: "currentStock", header: "Quantity", sortable: true }, { style: {} })));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([__assign({ field: "currentStock", header: "Quantity", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_71), false));
    var __VLS_75 = __VLS_73.slots.default;
    {
        var __VLS_76 = __VLS_73.slots.body;
        var slotProps = __VLS_vSlot(__VLS_76)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium mr-2" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        (slotProps.data.currentStock);
        var __VLS_77 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77({
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
            size: "small",
        }));
        var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
                size: "small",
            }], __VLS_functionalComponentArgsRest(__VLS_78), false));
        // @ts-ignore
        [getStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_73;
    var __VLS_82 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_83), false));
    var __VLS_87 = __VLS_85.slots.default;
    {
        var __VLS_88 = __VLS_85.slots.body;
        var slotProps = __VLS_vSlot(__VLS_88)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-3 h-3 rounded-full mx-auto mb-1 ".concat(__VLS_ctx.getStatusDotColor(slotProps.data.status))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (slotProps.data.status);
        // @ts-ignore
        [getStatusDotColor,];
    }
    // @ts-ignore
    [];
    var __VLS_85;
    var __VLS_89 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_90 = __VLS_asFunctionalComponent1(__VLS_89, new __VLS_89(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_91 = __VLS_90.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_90), false));
    var __VLS_94 = __VLS_92.slots.default;
    {
        var __VLS_95 = __VLS_92.slots.body;
        var slotProps_1 = __VLS_vSlot(__VLS_95)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-1']} */ ;
        var __VLS_96 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "secondary" })));
        var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_97), false));
        var __VLS_101 = void 0;
        var __VLS_102 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(!__VLS_ctx.hasStore))
                        return;
                    if (!!(!__VLS_ctx.hasProduct))
                        return;
                    __VLS_ctx.viewInventoryItem(slotProps_1.data);
                    // @ts-ignore
                    [viewInventoryItem,];
                } });
        var __VLS_99;
        var __VLS_100;
        var __VLS_103 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        Button;
        // @ts-ignore
        var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })));
        var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_104), false));
        var __VLS_108 = void 0;
        var __VLS_109 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(!__VLS_ctx.hasStore))
                        return;
                    if (!!(!__VLS_ctx.hasProduct))
                        return;
                    __VLS_ctx.editInventoryItem(slotProps_1.data);
                    // @ts-ignore
                    [editInventoryItem,];
                } });
        var __VLS_106;
        var __VLS_107;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_92;
    // @ts-ignore
    [];
    var __VLS_48;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-6 text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['mt-6']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-3 h-3 bg-green-500 rounded-full mr-2" }));
    /** @type {__VLS_StyleScopedClasses['w-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.getStatusCount('In Stock'));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-3 h-3 bg-yellow-500 rounded-full mr-2" }));
    /** @type {__VLS_StyleScopedClasses['w-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-yellow-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.getStatusCount('Low Stock'));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-3 h-3 bg-red-500 rounded-full mr-2" }));
    /** @type {__VLS_StyleScopedClasses['w-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-red-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.getStatusCount('Out of Stock'));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
    /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.inventoryItems.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    for (var _c = 0, _d = __VLS_vFor((__VLS_ctx.recentActivities)); _c < _d.length; _c++) {
        var activity = _d[_c][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (activity.id) }, { class: "flex items-center space-x-3 p-3 hover:bg-gray-50 rounded" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['hover:bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-8 h-8 rounded-full flex items-center justify-center ".concat(activity.bgColor)) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("".concat(activity.icon, " ").concat(activity.iconColor)) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex-1" }));
        /** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (activity.description);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (activity.time);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: ("px-2 py-1 rounded text-xs font-medium ".concat(activity.statusColor)) }));
        (activity.status);
        // @ts-ignore
        [getStatusCount, getStatusCount, getStatusCount, inventoryItems, recentActivities,];
    }
}
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
