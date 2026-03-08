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
Object.defineProperty(exports, "__esModule", { value: true });
var vue_1 = require("vue");
var vue_router_1 = require("vue-router");
var chart_js_1 = require("chart.js");
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var button_1 = require("primevue/button");
var tag_1 = require("primevue/tag");
var select_1 = require("primevue/select");
// Register Chart.js
chart_js_1.Chart.register.apply(chart_js_1.Chart, chart_js_1.registerables);
var router = (0, vue_router_1.useRouter)();
// Chart refs
var revenueChartRef = (0, vue_1.ref)(null);
var growthChartRef = (0, vue_1.ref)(null);
var revenueChart = null;
var growthChart = null;
// State
var revenueChartView = (0, vue_1.ref)('monthly');
var growthPeriod = (0, vue_1.ref)({ name: 'Last 6 months', value: '6months' });
var currentDate = (0, vue_1.ref)('');
var currentTime = (0, vue_1.ref)('');
// Stats Data
var stats = (0, vue_1.ref)({
    activeStores: 156,
    newStoresThisWeek: 12,
    activeSubscriptions: 142,
    subscriptionGrowth: 8.5,
    pendingValidations: 23,
    monthlyRevenue: 452500,
    revenueGrowth: 15.2
});
var totalPlatformRevenue = (0, vue_1.computed)(function () { return 3250000; });
// Growth Period Options
var growthPeriodOptions = (0, vue_1.ref)([
    { name: 'Last 3 months', value: '3months' },
    { name: 'Last 6 months', value: '6months' },
    { name: 'Last 12 months', value: '12months' },
    { name: 'Year to Date', value: 'ytd' }
]);
// Recent Activities
var recentActivities = (0, vue_1.ref)([
    {
        id: 1,
        time: '10:30 AM',
        action: 'Store Approved',
        description: 'Modern Furniture Hub approved',
        status: 'Completed',
        icon: 'pi pi-check-circle',
        iconColor: 'text-green-600',
        iconBg: 'bg-green-100'
    },
    {
        id: 2,
        time: '9:45 AM',
        action: 'Payment Received',
        description: 'Premium subscription payment from Classic Furniture',
        status: 'Success',
        icon: 'pi pi-credit-card',
        iconColor: 'text-blue-600',
        iconBg: 'bg-blue-100'
    },
    {
        id: 3,
        time: '9:15 AM',
        action: 'Store Registration',
        description: 'New store registration submitted',
        status: 'Pending',
        icon: 'pi pi-clock',
        iconColor: 'text-yellow-600',
        iconBg: 'bg-yellow-100'
    },
    {
        id: 4,
        time: 'Yesterday',
        action: 'Subscription Renewal',
        description: 'Office Solutions Inc renewed premium plan',
        status: 'Completed',
        icon: 'pi pi-sync',
        iconColor: 'text-green-600',
        iconBg: 'bg-green-100'
    },
    {
        id: 5,
        time: 'Yesterday',
        action: 'Account Suspended',
        description: 'Wood Crafts Studio suspended for violation',
        status: 'Warning',
        icon: 'pi pi-exclamation-triangle',
        iconColor: 'text-red-600',
        iconBg: 'bg-red-100'
    }
]);
// Subscription Plans
var subscriptionPlans = (0, vue_1.ref)([
    {
        id: 1,
        name: 'Basic',
        price: 2999,
        period: 'month',
        status: 'Active',
        subscribers: 78,
        features: {
            stores: 1,
            products: 100,
            users: 2
        }
    },
    {
        id: 2,
        name: 'Premium',
        price: 7999,
        period: 'month',
        status: 'Popular',
        subscribers: 52,
        features: {
            stores: 3,
            products: 500,
            users: 5
        }
    },
    {
        id: 3,
        name: 'Enterprise',
        price: 19999,
        period: 'month',
        status: 'Active',
        subscribers: 12,
        features: {
            stores: 10,
            products: 'Unlimited',
            users: 20
        }
    }
]);
// Pending Stores
var pendingStores = (0, vue_1.ref)([
    {
        id: 1,
        name: 'Modern Furniture Hub',
        owner: 'Juan Dela Cruz',
        waitingTime: '2 days',
        registrationDate: '2024-01-15'
    },
    {
        id: 2,
        name: 'Wood Crafts Studio',
        owner: 'Maria Santos',
        waitingTime: '1 day',
        registrationDate: '2024-01-16'
    },
    {
        id: 3,
        name: 'Luxury Home Decor',
        owner: 'Robert Lim',
        waitingTime: '3 days',
        registrationDate: '2024-01-14'
    },
    {
        id: 4,
        name: 'Office Solutions Inc',
        owner: 'Sarah Chen',
        waitingTime: 'Just now',
        registrationDate: '2024-01-17'
    },
    {
        id: 5,
        name: 'Eco Furniture Co',
        owner: 'David Green',
        waitingTime: '4 days',
        registrationDate: '2024-01-13'
    }
]);
// Recent Payments
var recentPayments = (0, vue_1.ref)([
    {
        id: 1,
        store: 'Classic Furniture Gallery',
        amount: 7999,
        date: 'Today',
        status: 'Paid'
    },
    {
        id: 2,
        store: 'Modern Living Spaces',
        amount: 7999,
        date: 'Today',
        status: 'Paid'
    },
    {
        id: 3,
        store: 'Kids Furniture World',
        amount: 2999,
        date: 'Yesterday',
        status: 'Paid'
    },
    {
        id: 4,
        store: 'Outdoor Living Co',
        amount: 19999,
        date: 'Jan 15, 2024',
        status: 'Paid'
    },
    {
        id: 5,
        store: 'Smart Furniture Tech',
        amount: 7999,
        date: 'Jan 14, 2024',
        status: 'Pending'
    }
]);
// Helper Functions
var formatCurrency = function (amount) {
    return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
var getStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'success':
        case 'paid': return 'success';
        case 'pending': return 'warning';
        case 'warning': return 'danger';
        default: return 'info';
    }
};
var getPlanStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'active': return 'success';
        case 'popular': return 'info';
        case 'inactive': return 'secondary';
        default: return 'info';
    }
};
var getPaymentStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'paid': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        default: return 'info';
    }
};
// Chart Functions
var initRevenueChart = function () {
    if (!revenueChartRef.value)
        return;
    if (revenueChart) {
        revenueChart.destroy();
    }
    var ctx = revenueChartRef.value.getContext('2d');
    if (!ctx)
        return;
    var data = revenueChartView.value === 'monthly'
        ? monthlyRevenueData
        : yearlyRevenueData;
    revenueChart = new chart_js_1.Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Platform Revenue',
                    data: data.platformRevenue,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Subscription Revenue',
                    data: data.subscriptionRevenue,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '₱' + (Number(value) / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        }
    });
};
var initGrowthChart = function () {
    if (!growthChartRef.value)
        return;
    if (growthChart) {
        growthChart.destroy();
    }
    var ctx = growthChartRef.value.getContext('2d');
    if (!ctx)
        return;
    growthChart = new chart_js_1.Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'New Stores',
                    data: [12, 15, 18, 22, 25, 28],
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderColor: 'rgb(79, 70, 229)',
                    borderWidth: 1
                },
                {
                    label: 'Active Stores',
                    data: [120, 125, 130, 140, 148, 156],
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Stores'
                    }
                }
            }
        }
    });
};
// Chart Data
var monthlyRevenueData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    platformRevenue: [250000, 280000, 310000, 295000, 325000, 350000, 375000, 400000, 420000, 435000, 450000, 462000],
    subscriptionRevenue: [150000, 165000, 180000, 175000, 190000, 205000, 220000, 235000, 245000, 255000, 265000, 275000]
};
var yearlyRevenueData = {
    labels: ['2020', '2021', '2022', '2023', '2024'],
    platformRevenue: [1250000, 1850000, 2450000, 3100000, 3850000],
    subscriptionRevenue: [750000, 1200000, 1800000, 2400000, 3000000]
};
// Action Functions
var setRevenueChartView = function (view) {
    revenueChartView.value = view;
    initRevenueChart();
};
var updateDateTime = function () {
    var now = new Date();
    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};
// Navigation Functions
var goToPendingApprovals = function () {
    router.push('/admin/store-validation');
};
var goToSubscriptions = function () {
    router.push('/admin/subscriptions');
};
var goToRevenueReports = function () {
    router.push('/admin/analytics');
};
var goToCustomerValidation = function () {
    router.push('/admin/customer-validation');
};
var goToSettings = function () {
    router.push('/admin/settings');
};
var goToActivityLog = function () {
    router.push('/admin/activity-log');
};
var managePlan = function (plan) {
    console.log('Manage plan:', plan);
    router.push("/admin/subscriptions/plans/".concat(plan.id));
};
var approveStore = function (store) {
    console.log('Approve store:', store);
    // Implement approval logic
};
var rejectStore = function (store) {
    console.log('Reject store:', store);
    // Implement rejection logic
};
// Lifecycle
(0, vue_1.onMounted)(function () {
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update time every minute
    setTimeout(function () {
        initRevenueChart();
        initGrowthChart();
    }, 100);
});
(0, vue_1.onUnmounted)(function () {
    if (revenueChart) {
        revenueChart.destroy();
    }
    if (growthChart) {
        growthChart.destroy();
    }
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-linear-to-r from-blue-600 to-purple-600 text-white shadow-xl rounded-2xl p-8" }));
/** @type {__VLS_StyleScopedClasses['bg-linear-to-r']} */ ;
/** @type {__VLS_StyleScopedClasses['from-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['to-purple-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-8']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-3xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-3xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-blue-100 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4 mt-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-calendar mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-calendar']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
(__VLS_ctx.currentDate);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
(__VLS_ctx.currentTime);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
/** @type {__VLS_StyleScopedClasses['text-right']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-4xl font-bold" }));
/** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
(__VLS_ctx.formatCurrency(__VLS_ctx.totalPlatformRevenue));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-blue-200" }));
/** @type {__VLS_StyleScopedClasses['text-blue-200']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.stats.activeStores);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-green-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.stats.newStoresThisWeek);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-12']} */ ;
/** @type {__VLS_StyleScopedClasses['h-12']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-store text-blue-600 text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-store']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ to: "/admin/stores" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ to: "/admin/stores" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_5 = __VLS_3.slots.default;
// @ts-ignore
[currentDate, currentTime, formatCurrency, totalPlatformRevenue, stats, stats,];
var __VLS_3;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.stats.activeSubscriptions);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-green-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.stats.subscriptionGrowth);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-green-100 rounded-full flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-12']} */ ;
/** @type {__VLS_StyleScopedClasses['h-12']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-credit-card text-green-600 text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-credit-card']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
var __VLS_6;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_7 = __VLS_asFunctionalComponent1(__VLS_6, new __VLS_6(__assign({ to: "/admin/subscriptions/active" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_8 = __VLS_7.apply(void 0, __spreadArray([__assign({ to: "/admin/subscriptions/active" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_7), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_11 = __VLS_9.slots.default;
// @ts-ignore
[stats, stats,];
var __VLS_9;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.stats.pendingValidations);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-yellow-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-12']} */ ;
/** @type {__VLS_StyleScopedClasses['h-12']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-yellow-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-yellow-600 text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
var __VLS_12;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_13 = __VLS_asFunctionalComponent1(__VLS_12, new __VLS_12(__assign({ to: "/admin/store-validation" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_14 = __VLS_13.apply(void 0, __spreadArray([__assign({ to: "/admin/store-validation" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_13), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_17 = __VLS_15.slots.default;
// @ts-ignore
[stats,];
var __VLS_15;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.formatCurrency(__VLS_ctx.stats.monthlyRevenue));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-green-500 mt-1" }));
/** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
(__VLS_ctx.stats.revenueGrowth);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center" }));
/** @type {__VLS_StyleScopedClasses['w-12']} */ ;
/** @type {__VLS_StyleScopedClasses['h-12']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-purple-100']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-line text-purple-600 text-xl" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-chart-line']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-xl']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
var __VLS_18;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_19 = __VLS_asFunctionalComponent1(__VLS_18, new __VLS_18(__assign({ to: "/admin/analytics" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_20 = __VLS_19.apply(void 0, __spreadArray([__assign({ to: "/admin/analytics" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_19), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_23 = __VLS_21.slots.default;
// @ts-ignore
[formatCurrency, stats, stats,];
var __VLS_21;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_24;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_25 = __VLS_asFunctionalComponent1(__VLS_24, new __VLS_24(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.revenueChartView === 'monthly' ? 'primary' : 'secondary'), size: "small" })));
var __VLS_26 = __VLS_25.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.revenueChartView === 'monthly' ? 'primary' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_25), false));
var __VLS_29;
var __VLS_30 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setRevenueChartView('monthly');
            // @ts-ignore
            [revenueChartView, setRevenueChartView,];
        } });
var __VLS_31 = __VLS_27.slots.default;
// @ts-ignore
[];
var __VLS_27;
var __VLS_28;
var __VLS_32;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_33 = __VLS_asFunctionalComponent1(__VLS_32, new __VLS_32(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.revenueChartView === 'yearly' ? 'primary' : 'secondary'), size: "small" })));
var __VLS_34 = __VLS_33.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.revenueChartView === 'yearly' ? 'primary' : 'secondary'), size: "small" })], __VLS_functionalComponentArgsRest(__VLS_33), false));
var __VLS_37;
var __VLS_38 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setRevenueChartView('yearly');
            // @ts-ignore
            [revenueChartView, setRevenueChartView,];
        } });
var __VLS_39 = __VLS_35.slots.default;
// @ts-ignore
[];
var __VLS_35;
var __VLS_36;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-72" }));
/** @type {__VLS_StyleScopedClasses['h-72']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.canvas, __VLS_intrinsics.canvas)({
    ref: "revenueChartRef",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
var __VLS_40;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_41 = __VLS_asFunctionalComponent1(__VLS_40, new __VLS_40(__assign({ modelValue: (__VLS_ctx.growthPeriod), options: (__VLS_ctx.growthPeriodOptions), optionLabel: "name", placeholder: "Last 6 months" }, { class: "w-50" })));
var __VLS_42 = __VLS_41.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.growthPeriod), options: (__VLS_ctx.growthPeriodOptions), optionLabel: "name", placeholder: "Last 6 months" }, { class: "w-50" })], __VLS_functionalComponentArgsRest(__VLS_41), false));
/** @type {__VLS_StyleScopedClasses['w-50']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "h-72" }));
/** @type {__VLS_StyleScopedClasses['h-72']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.canvas, __VLS_intrinsics.canvas)({
    ref: "growthChartRef",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
var __VLS_45;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_46 = __VLS_asFunctionalComponent1(__VLS_45, new __VLS_45(__assign({ to: "/admin/subscriptions" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_47 = __VLS_46.apply(void 0, __spreadArray([__assign({ to: "/admin/subscriptions" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_46), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_50 = __VLS_48.slots.default;
// @ts-ignore
[growthPeriod, growthPeriodOptions,];
var __VLS_48;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
var _loop_1 = function (plan) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (plan.id) }, { class: "border rounded-lg p-4 hover:shadow-md transition-shadow" }));
    /** @type {__VLS_StyleScopedClasses['border']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:shadow-md']} */ ;
    /** @type {__VLS_StyleScopedClasses['transition-shadow']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-start mb-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-bold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    (plan.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
    (__VLS_ctx.formatCurrency(plan.price));
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (plan.period);
    var __VLS_51 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_52 = __VLS_asFunctionalComponent1(__VLS_51, new __VLS_51({
        value: (plan.status),
        severity: (__VLS_ctx.getPlanStatusSeverity(plan.status)),
    }));
    var __VLS_53 = __VLS_52.apply(void 0, __spreadArray([{
            value: (plan.status),
            severity: (__VLS_ctx.getPlanStatusSeverity(plan.status)),
        }], __VLS_functionalComponentArgsRest(__VLS_52), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-500 mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (plan.features.stores);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-500 mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (plan.features.products);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center text-sm text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-500 mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (plan.features.users);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (plan.subscribers);
    var __VLS_56 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_57 = __VLS_asFunctionalComponent1(__VLS_56, new __VLS_56(__assign({ 'onClick': {} }, { label: "Manage", size: "small", severity: "secondary" })));
    var __VLS_58 = __VLS_57.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Manage", size: "small", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_57), false));
    var __VLS_61 = void 0;
    var __VLS_62 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.managePlan(plan);
                // @ts-ignore
                [formatCurrency, subscriptionPlans, getPlanStatusSeverity, managePlan,];
            } });
    // @ts-ignore
    [];
};
var __VLS_59, __VLS_60;
for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.subscriptionPlans)); _i < _a.length; _i++) {
    var plan = _a[_i][0];
    _loop_1(plan);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.pendingStores.length);
var __VLS_63;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_64 = __VLS_asFunctionalComponent1(__VLS_63, new __VLS_63(__assign({ 'onClick': {} }, { label: "Review All", size: "small" })));
var __VLS_65 = __VLS_64.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Review All", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_64), false));
var __VLS_68;
var __VLS_69 = ({ click: {} },
    { onClick: (__VLS_ctx.goToPendingApprovals) });
var __VLS_66;
var __VLS_67;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
var _loop_2 = function (store) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (store.id) }, { class: "flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['hover:bg-gray-100']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center" }));
    /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
    /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-store text-blue-600" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-store']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (store.name);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (store.owner);
    (store.waitingTime);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
    var __VLS_70 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_71 = __VLS_asFunctionalComponent1(__VLS_70, new __VLS_70(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })));
    var __VLS_72 = __VLS_71.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_71), false));
    var __VLS_75 = void 0;
    var __VLS_76 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.approveStore(store);
                // @ts-ignore
                [pendingStores, pendingStores, goToPendingApprovals, approveStore,];
            } });
    var __VLS_77 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_78 = __VLS_asFunctionalComponent1(__VLS_77, new __VLS_77(__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })));
    var __VLS_79 = __VLS_78.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_78), false));
    var __VLS_82 = void 0;
    var __VLS_83 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.rejectStore(store);
                // @ts-ignore
                [rejectStore,];
            } });
    // @ts-ignore
    [];
};
var __VLS_73, __VLS_74, __VLS_80, __VLS_81;
for (var _b = 0, _c = __VLS_vFor((__VLS_ctx.pendingStores.slice(0, 3))); _b < _c.length; _b++) {
    var store = _c[_b][0];
    _loop_2(store);
}
if (__VLS_ctx.pendingStores.length > 3) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4 text-center" }));
    /** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.pendingStores.length - 3);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
var __VLS_84;
/** @ts-ignore @type {typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink | typeof __VLS_components.routerLink | typeof __VLS_components.RouterLink} */
routerLink;
// @ts-ignore
var __VLS_85 = __VLS_asFunctionalComponent1(__VLS_84, new __VLS_84(__assign({ to: "/admin/billing" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })));
var __VLS_86 = __VLS_85.apply(void 0, __spreadArray([__assign({ to: "/admin/billing" }, { class: "text-blue-600 text-sm font-medium hover:text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_85), false));
/** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['hover:text-blue-800']} */ ;
var __VLS_89 = __VLS_87.slots.default;
// @ts-ignore
[pendingStores, pendingStores,];
var __VLS_87;
var __VLS_90;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
datatable_1.default;
// @ts-ignore
var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90({
    value: (__VLS_ctx.recentPayments),
    tableStyle: "min-width: 50rem",
}));
var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.recentPayments),
        tableStyle: "min-width: 50rem",
    }], __VLS_functionalComponentArgsRest(__VLS_91), false));
var __VLS_95 = __VLS_93.slots.default;
var __VLS_96;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_97 = __VLS_asFunctionalComponent1(__VLS_96, new __VLS_96(__assign({ field: "store", header: "Store" }, { style: {} })));
var __VLS_98 = __VLS_97.apply(void 0, __spreadArray([__assign({ field: "store", header: "Store" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_97), false));
var __VLS_101 = __VLS_99.slots.default;
{
    var __VLS_102 = __VLS_99.slots.body;
    var slotProps = __VLS_vSlot(__VLS_102)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-store text-gray-400 mr-2" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-store']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-400']} */ ;
    /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (slotProps.data.store);
    // @ts-ignore
    [recentPayments,];
}
// @ts-ignore
[];
var __VLS_99;
var __VLS_103;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103(__assign({ field: "amount", header: "Amount" }, { style: {} })));
var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([__assign({ field: "amount", header: "Amount" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_104), false));
var __VLS_108 = __VLS_106.slots.default;
{
    var __VLS_109 = __VLS_106.slots.body;
    var slotProps = __VLS_vSlot(__VLS_109)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    (__VLS_ctx.formatCurrency(slotProps.data.amount));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_106;
var __VLS_110;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_111 = __VLS_asFunctionalComponent1(__VLS_110, new __VLS_110(__assign({ field: "date", header: "Date" }, { style: {} })));
var __VLS_112 = __VLS_111.apply(void 0, __spreadArray([__assign({ field: "date", header: "Date" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_111), false));
var __VLS_115 = __VLS_113.slots.default;
{
    var __VLS_116 = __VLS_113.slots.body;
    var slotProps = __VLS_vSlot(__VLS_116)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (slotProps.data.date);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_113;
var __VLS_117;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_118 = __VLS_asFunctionalComponent1(__VLS_117, new __VLS_117(__assign({ field: "status", header: "Status" }, { style: {} })));
var __VLS_119 = __VLS_118.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_118), false));
var __VLS_122 = __VLS_120.slots.default;
{
    var __VLS_123 = __VLS_120.slots.body;
    var slotProps = __VLS_vSlot(__VLS_123)[0];
    var __VLS_124 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124({
        value: (slotProps.data.status),
        severity: (__VLS_ctx.getPaymentStatusSeverity(slotProps.data.status)),
        rounded: true,
    }));
    var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([{
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getPaymentStatusSeverity(slotProps.data.status)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_125), false));
    // @ts-ignore
    [getPaymentStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_120;
// @ts-ignore
[];
var __VLS_93;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "lg:col-span-2" }));
/** @type {__VLS_StyleScopedClasses['lg:col-span-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center mb-6" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
var __VLS_129;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129(__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })));
var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View All", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_130), false));
var __VLS_134;
var __VLS_135 = ({ click: {} },
    { onClick: (__VLS_ctx.goToActivityLog) });
var __VLS_132;
var __VLS_133;
var __VLS_136;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
datatable_1.default;
// @ts-ignore
var __VLS_137 = __VLS_asFunctionalComponent1(__VLS_136, new __VLS_136({
    value: (__VLS_ctx.recentActivities),
    tableStyle: "min-width: 50rem",
}));
var __VLS_138 = __VLS_137.apply(void 0, __spreadArray([{
        value: (__VLS_ctx.recentActivities),
        tableStyle: "min-width: 50rem",
    }], __VLS_functionalComponentArgsRest(__VLS_137), false));
var __VLS_141 = __VLS_139.slots.default;
var __VLS_142;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_143 = __VLS_asFunctionalComponent1(__VLS_142, new __VLS_142(__assign({ field: "time", header: "Time" }, { style: {} })));
var __VLS_144 = __VLS_143.apply(void 0, __spreadArray([__assign({ field: "time", header: "Time" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_143), false));
var __VLS_147 = __VLS_145.slots.default;
{
    var __VLS_148 = __VLS_145.slots.body;
    var slotProps = __VLS_vSlot(__VLS_148)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (slotProps.data.time);
    // @ts-ignore
    [goToActivityLog, recentActivities,];
}
// @ts-ignore
[];
var __VLS_145;
var __VLS_149;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_150 = __VLS_asFunctionalComponent1(__VLS_149, new __VLS_149(__assign({ field: "action", header: "Action" }, { style: {} })));
var __VLS_151 = __VLS_150.apply(void 0, __spreadArray([__assign({ field: "action", header: "Action" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_150), false));
var __VLS_154 = __VLS_152.slots.default;
{
    var __VLS_155 = __VLS_152.slots.body;
    var slotProps = __VLS_vSlot(__VLS_155)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-8 h-8 rounded-full flex items-center justify-center mr-3 ".concat(slotProps.data.iconBg)) }));
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("".concat(slotProps.data.icon, " ").concat(slotProps.data.iconColor)) }));
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (slotProps.data.action);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_152;
var __VLS_156;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_157 = __VLS_asFunctionalComponent1(__VLS_156, new __VLS_156(__assign({ field: "description", header: "Description" }, { style: {} })));
var __VLS_158 = __VLS_157.apply(void 0, __spreadArray([__assign({ field: "description", header: "Description" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_157), false));
var __VLS_161 = __VLS_159.slots.default;
{
    var __VLS_162 = __VLS_159.slots.body;
    var slotProps = __VLS_vSlot(__VLS_162)[0];
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    (slotProps.data.description);
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_159;
var __VLS_163;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163(__assign({ field: "status", header: "Status" }, { style: {} })));
var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_164), false));
var __VLS_168 = __VLS_166.slots.default;
{
    var __VLS_169 = __VLS_166.slots.body;
    var slotProps = __VLS_vSlot(__VLS_169)[0];
    var __VLS_170 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170({
        value: (slotProps.data.status),
        severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
        rounded: true,
    }));
    var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([{
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
            rounded: true,
        }], __VLS_functionalComponentArgsRest(__VLS_171), false));
    // @ts-ignore
    [getStatusSeverity,];
}
// @ts-ignore
[];
var __VLS_166;
// @ts-ignore
[];
var __VLS_139;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
