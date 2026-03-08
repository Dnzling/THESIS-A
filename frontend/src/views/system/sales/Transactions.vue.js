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
var datatable_1 = require("primevue/datatable");
var column_1 = require("primevue/column");
var inputtext_1 = require("primevue/inputtext");
var button_1 = require("primevue/button");
var tag_1 = require("primevue/tag");
var datepicker_1 = require("primevue/datepicker");
var select_1 = require("primevue/select");
var multiselect_1 = require("primevue/multiselect");
var inputnumber_1 = require("primevue/inputnumber");
var dialog_1 = require("primevue/dialog");
var avatar_1 = require("primevue/avatar");
var textarea_1 = require("primevue/textarea");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
// Debug mode
var debug = (0, vue_1.ref)(true);
// State
var loading = (0, vue_1.ref)(false);
var showBatchActions = (0, vue_1.ref)(false);
var showTransactionDialog = (0, vue_1.ref)(false);
var showNewTransactionDialog = (0, vue_1.ref)(false);
var showDeleteDialog = (0, vue_1.ref)(false);
var searchTerm = (0, vue_1.ref)('');
var selectedStatus = (0, vue_1.ref)([]);
var selectedPaymentMethod = (0, vue_1.ref)(null);
var selectedTransactions = (0, vue_1.ref)([]);
var selectedTransaction = (0, vue_1.ref)(null);
var transactionToDelete = (0, vue_1.ref)(null);
// Date Range - Fixed: ensure dates are Date objects
var dateRange = (0, vue_1.ref)({
    start: new Date(new Date().setDate(new Date().getDate() - 7)),
    end: new Date()
});
// New Transaction
var newTransaction = (0, vue_1.ref)({
    customerId: null,
    paymentMethod: null,
    items: [
        { id: 1, product: 'Modern Sofa', quantity: 1, price: 25000 }
    ],
    shipping: 500,
    taxRate: 12,
    notes: ''
});
// Transactions Data - CORRECT VERSION
var transactions = (0, vue_1.ref)([
    {
        id: 1,
        orderId: 'ORD-2026-0012',
        customer: 'John Smith',
        email: 'john@email.com',
        date: '2026-01-15',
        time: '10:30 AM',
        amount: 25500,
        subtotal: 25000,
        shipping: 500,
        tax: 0,
        itemCount: 3,
        paymentMethod: 'Credit Card',
        status: 'Completed',
        notes: 'Customer requested express shipping',
        items: [
            { id: 1, name: 'Modern Sofa', quantity: 1, price: 25000 }
        ]
    },
    {
        id: 2,
        orderId: 'ORD-2026-0013',
        customer: 'Sarah Johnson',
        email: 'sarah@email.com',
        date: '2026-01-15',
        time: '11:45 AM',
        amount: 18700,
        subtotal: 18500,
        shipping: 200,
        tax: 0,
        itemCount: 2,
        paymentMethod: 'PayPal',
        status: 'Processing',
        notes: '',
        items: [
            { id: 1, name: 'Office Chair', quantity: 2, price: 9250 }
        ]
    },
    {
        id: 3,
        orderId: 'ORD-2026-0014',
        customer: 'Mike Wilson',
        email: 'mike@email.com',
        date: '2026-01-14',
        time: '02:15 PM',
        amount: 32500,
        subtotal: 32500,
        shipping: 0,
        tax: 0,
        itemCount: 1,
        paymentMethod: 'Bank Transfer',
        status: 'Completed',
        notes: 'Paid in full',
        items: [
            { id: 1, name: 'Dining Table Set', quantity: 1, price: 32500 }
        ]
    },
    {
        id: 4,
        orderId: 'ORD-2026-0015',
        customer: 'Emma Davis',
        email: 'emma@email.com',
        date: '2026-01-14',
        time: '03:30 PM',
        amount: 12500,
        subtotal: 12500,
        shipping: 0,
        tax: 0,
        itemCount: 4,
        paymentMethod: 'Credit Card',
        status: 'Pending',
        notes: 'Awaiting payment confirmation',
        items: [
            { id: 1, name: 'Desk Lamp', quantity: 2, price: 2500 },
            { id: 2, name: 'Bookshelf', quantity: 1, price: 7500 }
        ]
    },
    {
        id: 5,
        orderId: 'ORD-2026-0016',
        customer: 'Robert Brown',
        email: 'robert@email.com',
        date: '2026-01-13',
        time: '09:15 AM',
        amount: 42500,
        subtotal: 42500,
        shipping: 0,
        tax: 0,
        itemCount: 2,
        paymentMethod: 'Cash',
        status: 'Completed',
        notes: 'In-store purchase',
        items: [
            { id: 1, name: 'King Size Bed', quantity: 1, price: 30000 },
            { id: 2, name: 'Mattress', quantity: 1, price: 12500 }
        ]
    },
    {
        id: 6,
        orderId: 'ORD-2026-0017',
        customer: 'Lisa Anderson',
        email: 'lisa@email.com',
        date: '2026-01-13',
        time: '04:45 PM',
        amount: 18500,
        subtotal: 18500,
        shipping: 0,
        tax: 0,
        itemCount: 1,
        paymentMethod: 'Credit Card',
        status: 'Cancelled',
        notes: 'Customer cancelled order',
        items: [
            { id: 1, name: 'Coffee Table', quantity: 1, price: 18500 }
        ]
    },
    {
        id: 7,
        orderId: 'ORD-2026-0018',
        customer: 'David Miller',
        email: 'david@email.com',
        date: '2026-01-12',
        time: '01:20 PM',
        amount: 29500,
        subtotal: 29500,
        shipping: 0,
        tax: 0,
        itemCount: 3,
        paymentMethod: 'PayPal',
        status: 'Refunded',
        notes: 'Refund processed',
        items: [
            { id: 1, name: 'TV Stand', quantity: 1, price: 15000 },
            { id: 2, name: 'Bar Stool', quantity: 2, price: 7250 }
        ]
    },
    {
        id: 8,
        orderId: 'ORD-2026-0019',
        customer: 'Jennifer Lee',
        email: 'jennifer@email.com',
        date: '2026-01-12',
        time: '10:00 AM',
        amount: 15500,
        subtotal: 15500,
        shipping: 0,
        tax: 0,
        itemCount: 2,
        paymentMethod: 'Bank Transfer',
        status: 'On Hold',
        notes: 'Awaiting stock',
        items: [
            { id: 1, name: 'Nightstand', quantity: 2, price: 7750 }
        ]
    }
]);
// Filter Options
var statusOptions = (0, vue_1.ref)([
    { name: 'Completed', value: 'completed' },
    { name: 'Processing', value: 'processing' },
    { name: 'Pending', value: 'pending' },
    { name: 'On Hold', value: 'on_hold' },
    { name: 'Cancelled', value: 'cancelled' },
    { name: 'Refunded', value: 'refunded' }
]);
var paymentMethodOptions = (0, vue_1.ref)([
    { name: 'Credit Card', value: 'credit_card' },
    { name: 'PayPal', value: 'paypal' },
    { name: 'Bank Transfer', value: 'bank_transfer' },
    { name: 'Cash', value: 'cash' },
    { name: 'GCash', value: 'gcash' },
    { name: 'Maya', value: 'maya' }
]);
var customers = (0, vue_1.ref)([
    { id: 1, name: 'John Smith', email: 'john@email.com' },
    { id: 2, name: 'Sarah Johnson', email: 'sarah@email.com' },
    { id: 3, name: 'Mike Wilson', email: 'mike@email.com' },
    { id: 4, name: 'Emma Davis', email: 'emma@email.com' },
    { id: 5, name: 'Robert Brown', email: 'robert@email.com' }
]);
// Computed Properties - FIXED VERSION
var filteredTransactions = (0, vue_1.computed)(function () {
    console.log('Computing filtered transactions...');
    console.log('Search term:', searchTerm.value);
    console.log('Original transactions:', transactions.value.length);
    var filtered = __spreadArray([], transactions.value, true); // Create a copy to avoid mutating original
    // Search filter
    if (searchTerm.value) {
        var term_1 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (t) {
            var _a, _b, _c;
            return ((_a = t.orderId) === null || _a === void 0 ? void 0 : _a.toLowerCase().includes(term_1)) ||
                ((_b = t.customer) === null || _b === void 0 ? void 0 : _b.toLowerCase().includes(term_1)) ||
                ((_c = t.email) === null || _c === void 0 ? void 0 : _c.toLowerCase().includes(term_1));
        });
    }
    // Status filter - FIXED: Check if selectedStatus has values
    if (selectedStatus.value && selectedStatus.value.length > 0) {
        var statuses_1 = selectedStatus.value.map(function (s) { return s.name; });
        filtered = filtered.filter(function (t) { return statuses_1.includes(t.status); });
    }
    // Payment method filter - FIXED: Check if selectedPaymentMethod exists
    if (selectedPaymentMethod.value) {
        filtered = filtered.filter(function (t) { return t.paymentMethod === selectedPaymentMethod.value.name; });
    }
    // Date range filter - FIXED: Convert dates properly
    if (dateRange.value.start && dateRange.value.end) {
        var startDate_1 = new Date(dateRange.value.start);
        var endDate_1 = new Date(dateRange.value.end);
        filtered = filtered.filter(function (t) {
            try {
                var transactionDate = new Date(t.date);
                return transactionDate >= startDate_1 && transactionDate <= endDate_1;
            }
            catch (e) {
                return true; // If date parsing fails, include the transaction
            }
        });
    }
    console.log('Filtered transactions:', filtered.length);
    return filtered;
});
var todaysRevenue = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return transactions.value
        .filter(function (t) { return t.date === today && t.status === 'Completed'; })
        .reduce(function (sum, t) { return sum + t.amount; }, 0);
});
var todayTransactions = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return transactions.value.filter(function (t) { return t.date === today; }).length;
});
var pendingCount = (0, vue_1.computed)(function () {
    return transactions.value.filter(function (t) {
        return ['Pending', 'On Hold', 'Processing'].includes(t.status);
    }).length;
});
var monthlyRevenue = (0, vue_1.computed)(function () {
    var currentMonth = new Date().getMonth();
    var currentYear = new Date().getFullYear();
    return transactions.value
        .filter(function (t) {
        try {
            var date = new Date(t.date);
            return date.getMonth() === currentMonth &&
                date.getFullYear() === currentYear &&
                t.status === 'Completed';
        }
        catch (e) {
            return false;
        }
    })
        .reduce(function (sum, t) { return sum + t.amount; }, 0);
});
var avgOrderValue = (0, vue_1.computed)(function () {
    var completedOrders = transactions.value.filter(function (t) { return t.status === 'Completed'; });
    if (completedOrders.length === 0)
        return 0;
    var total = completedOrders.reduce(function (sum, t) { return sum + t.amount; }, 0);
    return Math.round(total / completedOrders.length);
});
// Helper Functions
var formatCurrency = function (amount) {
    return amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
var formatDate = function (dateString) {
    try {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    catch (e) {
        return dateString;
    }
};
var getInitials = function (name) {
    if (!name)
        return '?';
    return name.split(' ').map(function (n) { return n[0]; }).join('').toUpperCase();
};
var getStatusSeverity = function (status) {
    switch (status) {
        case 'Completed': return 'success';
        case 'Processing': return 'info';
        case 'Pending': return 'warning';
        case 'On Hold': return 'secondary';
        case 'Cancelled': return 'danger';
        case 'Refunded': return 'help';
        default: return 'secondary';
    }
};
var getStatusIcon = function (status) {
    switch (status) {
        case 'Completed': return 'pi pi-check-circle';
        case 'Processing': return 'pi pi-spinner';
        case 'Pending': return 'pi pi-clock';
        case 'On Hold': return 'pi pi-pause-circle';
        case 'Cancelled': return 'pi pi-times-circle';
        case 'Refunded': return 'pi pi-undo';
        default: return 'pi pi-question-circle';
    }
};
var getPaymentMethodIcon = function (method) {
    switch (method) {
        case 'Credit Card': return 'pi-credit-card';
        case 'PayPal': return 'pi-paypal';
        case 'Bank Transfer': return 'pi-building';
        case 'Cash': return 'pi-money-bill';
        case 'GCash': return 'pi-mobile';
        case 'Maya': return 'pi-wallet';
        default: return 'pi-wallet';
    }
};
var getPaymentMethodClass = function (method) {
    switch (method) {
        case 'Credit Card': return 'bg-blue-100 text-blue-800';
        case 'PayPal': return 'bg-indigo-100 text-indigo-800';
        case 'Bank Transfer': return 'bg-green-100 text-green-800';
        case 'Cash': return 'bg-yellow-100 text-yellow-800';
        case 'GCash': return 'bg-purple-100 text-purple-800';
        case 'Maya': return 'bg-pink-100 text-pink-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
// Action Functions
var applyFilters = function () {
    console.log('Filters applied:', {
        searchTerm: searchTerm.value,
        selectedStatus: selectedStatus.value,
        selectedPaymentMethod: selectedPaymentMethod.value,
        dateRange: dateRange.value
    });
};
var clearFilters = function () {
    searchTerm.value = '';
    selectedStatus.value = [];
    selectedPaymentMethod.value = null;
    dateRange.value = {
        start: new Date(new Date().setDate(new Date().getDate() - 7)),
        end: new Date()
    };
};
var viewTransaction = function (transaction) {
    console.log('View transaction:', transaction);
    selectedTransaction.value = transaction;
    showTransactionDialog.value = true;
};
var editTransaction = function (transaction) {
    console.log('Edit transaction:', transaction);
    selectedTransaction.value = transaction;
    // Navigate to edit page or show edit dialog
};
var printInvoice = function (transaction) {
    console.log('Print invoice:', transaction);
    // Implement print functionality
};
var confirmDelete = function (transaction) {
    console.log('Confirm delete:', transaction);
    transactionToDelete.value = transaction;
    showDeleteDialog.value = true;
};
var deleteTransaction = function () {
    if (transactionToDelete.value) {
        var index = transactions.value.findIndex(function (t) { return t.id === transactionToDelete.value.id; });
        if (index !== -1) {
            transactions.value.splice(index, 1);
            console.log('Transaction deleted');
        }
    }
    showDeleteDialog.value = false;
    transactionToDelete.value = null;
};
var addItem = function () {
    newTransaction.value.items.push({
        id: newTransaction.value.items.length + 1,
        product: '',
        quantity: 1,
        price: 0
    });
};
var removeItem = function (index) {
    newTransaction.value.items.splice(index, 1);
};
var createTransaction = function () {
    var _a;
    // Generate new transaction
    var newId = transactions.value.length > 0
        ? Math.max.apply(Math, transactions.value.map(function (t) { return t.id; })) + 1
        : 1;
    var today = new Date().toISOString().split('T')[0];
    var time = new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    var subtotal = newTransaction.value.items.reduce(function (sum, item) { return sum + (item.quantity * item.price); }, 0);
    var tax = subtotal * (newTransaction.value.taxRate / 100);
    var amount = subtotal + newTransaction.value.shipping + tax;
    var customer = customers.value.find(function (c) { return c.id === newTransaction.value.customerId; });
    var transaction = {
        id: newId,
        orderId: "ORD-".concat(new Date().getFullYear(), "-").concat(String(newId).padStart(4, '0')),
        customer: (customer === null || customer === void 0 ? void 0 : customer.name) || 'New Customer',
        email: (customer === null || customer === void 0 ? void 0 : customer.email) || '',
        date: today,
        time: time,
        amount: amount,
        subtotal: subtotal,
        shipping: newTransaction.value.shipping,
        tax: tax,
        itemCount: newTransaction.value.items.length,
        paymentMethod: ((_a = newTransaction.value.paymentMethod) === null || _a === void 0 ? void 0 : _a.name) || 'Cash',
        status: 'Pending',
        notes: newTransaction.value.notes,
        items: __spreadArray([], newTransaction.value.items, true)
    };
    transactions.value.unshift(transaction);
    showNewTransactionDialog.value = false;
    resetNewTransaction();
    console.log('New transaction created:', transaction);
};
var resetNewTransaction = function () {
    newTransaction.value = {
        customerId: null,
        paymentMethod: null,
        items: [
            { id: 1, product: 'Modern Sofa', quantity: 1, price: 25000 }
        ],
        shipping: 500,
        taxRate: 12,
        notes: ''
    };
};
var exportSelected = function () {
    console.log('Export selected:', selectedTransactions.value);
    // Implement export functionality
};
var markAsProcessed = function () {
    selectedTransactions.value.forEach(function (transaction) {
        var index = transactions.value.findIndex(function (t) { return t.id === transaction.id; });
        if (index !== -1 && transactions.value[index].status !== 'Completed') {
            transactions.value[index].status = 'Completed';
        }
    });
    selectedTransactions.value = [];
    showBatchActions.value = false;
};
var deleteSelected = function () {
    selectedTransactions.value.forEach(function (transaction) {
        var index = transactions.value.findIndex(function (t) { return t.id === transaction.id; });
        if (index !== -1) {
            transactions.value.splice(index, 1);
        }
    });
    selectedTransactions.value = [];
    showBatchActions.value = false;
};
var clearSelection = function () {
    selectedTransactions.value = [];
    showBatchActions.value = false;
};
// Add this to debug
console.log('Transactions component initialized');
console.log('Transactions data:', transactions.value);
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
if (!__VLS_ctx.transactions || __VLS_ctx.transactions.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-yellow-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)(__assign({ class: "text-2xl font-bold text-gray-800" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "New Transaction", icon: "pi pi-plus" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "New Transaction", icon: "pi pi-plus" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showNewTransactionDialog = true;
            // @ts-ignore
            [transactions, transactions, showNewTransactionDialog,];
        } });
var __VLS_3;
var __VLS_4;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { label: "Batch Actions", icon: "pi pi-cog", severity: "secondary" })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Batch Actions", icon: "pi pi-cog", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.showBatchActions = !__VLS_ctx.showBatchActions;
            // @ts-ignore
            [showBatchActions, showBatchActions,];
        } });
var __VLS_10;
var __VLS_11;
if (__VLS_ctx.showBatchActions) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
    /** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
    /** @type {__VLS_StyleScopedClasses['shadow']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm font-medium text-gray-700" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    (__VLS_ctx.selectedTransactions.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
    var __VLS_14 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_15 = __VLS_asFunctionalComponent1(__VLS_14, new __VLS_14(__assign({ 'onClick': {} }, { label: "Export Selected", icon: "pi pi-download", size: "small", severity: "secondary" })));
    var __VLS_16 = __VLS_15.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export Selected", icon: "pi pi-download", size: "small", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_15), false));
    var __VLS_19 = void 0;
    var __VLS_20 = ({ click: {} },
        { onClick: (__VLS_ctx.exportSelected) });
    var __VLS_17;
    var __VLS_18;
    var __VLS_21 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_22 = __VLS_asFunctionalComponent1(__VLS_21, new __VLS_21(__assign({ 'onClick': {} }, { label: "Mark as Processed", icon: "pi pi-check-circle", size: "small" })));
    var __VLS_23 = __VLS_22.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Mark as Processed", icon: "pi pi-check-circle", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_22), false));
    var __VLS_26 = void 0;
    var __VLS_27 = ({ click: {} },
        { onClick: (__VLS_ctx.markAsProcessed) });
    var __VLS_24;
    var __VLS_25;
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ 'onClick': {} }, { label: "Delete Selected", icon: "pi pi-trash", size: "small", severity: "danger" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Delete Selected", icon: "pi pi-trash", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    var __VLS_33 = void 0;
    var __VLS_34 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteSelected) });
    var __VLS_31;
    var __VLS_32;
    var __VLS_35 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_36 = __VLS_asFunctionalComponent1(__VLS_35, new __VLS_35(__assign({ 'onClick': {} }, { label: "Clear Selection", text: true, size: "small" })));
    var __VLS_37 = __VLS_36.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear Selection", text: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_36), false));
    var __VLS_40 = void 0;
    var __VLS_41 = ({ click: {} },
        { onClick: (__VLS_ctx.clearSelection) });
    var __VLS_38;
    var __VLS_39;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-4 gap-6" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-4']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.h6, __VLS_intrinsics.h6)(__assign({ class: "text-sm font-semibold text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-money-bill text-green-500 text-lg" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-money-bill']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.formatCurrency(__VLS_ctx.todaysRevenue));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-green-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-500']} */ ;
(__VLS_ctx.todayTransactions);
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock text-yellow-500 text-lg" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.pendingCount);
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-yellow-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-chart-line text-blue-500 text-lg" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-chart-line']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.formatCurrency(__VLS_ctx.monthlyRevenue));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-blue-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-500']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-shopping-cart text-purple-500 text-lg" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-shopping-cart']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-2xl font-bold text-gray-800 mt-2" }));
/** @type {__VLS_StyleScopedClasses['text-2xl']} */ ;
/** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
(__VLS_ctx.formatCurrency(__VLS_ctx.avgOrderValue));
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-purple-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-purple-500']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 flex-1" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
/** @type {__VLS_StyleScopedClasses['md:grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['lg:grid-cols-4']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_42;
/** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
multiselect_1.default;
// @ts-ignore
var __VLS_43 = __VLS_asFunctionalComponent1(__VLS_42, new __VLS_42(__assign({ modelValue: (__VLS_ctx.selectedStatus), options: (__VLS_ctx.statusOptions), optionLabel: "name", placeholder: "All Status", display: "chip" }, { class: "w-full" })));
var __VLS_44 = __VLS_43.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.selectedStatus), options: (__VLS_ctx.statusOptions), optionLabel: "name", placeholder: "All Status", display: "chip" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_43), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_47;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_48 = __VLS_asFunctionalComponent1(__VLS_47, new __VLS_47(__assign({ modelValue: (__VLS_ctx.selectedPaymentMethod), options: (__VLS_ctx.paymentMethodOptions), optionLabel: "name", placeholder: "All Methods" }, { class: "w-full" })));
var __VLS_49 = __VLS_48.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.selectedPaymentMethod), options: (__VLS_ctx.paymentMethodOptions), optionLabel: "name", placeholder: "All Methods" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_48), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_52;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
datepicker_1.default;
// @ts-ignore
var __VLS_53 = __VLS_asFunctionalComponent1(__VLS_52, new __VLS_52(__assign({ modelValue: (__VLS_ctx.dateRange.start), placeholder: "From", showIcon: true, dateFormat: "yy-mm-dd" }, { class: "flex-1" })));
var __VLS_54 = __VLS_53.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateRange.start), placeholder: "From", showIcon: true, dateFormat: "yy-mm-dd" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_53), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
var __VLS_57;
/** @ts-ignore @type {typeof __VLS_components.DatePicker} */
datepicker_1.default;
// @ts-ignore
var __VLS_58 = __VLS_asFunctionalComponent1(__VLS_57, new __VLS_57(__assign({ modelValue: (__VLS_ctx.dateRange.end), placeholder: "To", showIcon: true, dateFormat: "yy-mm-dd" }, { class: "flex-1" })));
var __VLS_59 = __VLS_58.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateRange.end), placeholder: "To", showIcon: true, dateFormat: "yy-mm-dd" }, { class: "flex-1" })], __VLS_functionalComponentArgsRest(__VLS_58), false));
/** @type {__VLS_StyleScopedClasses['flex-1']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mt-4 flex justify-between items-center" }));
/** @type {__VLS_StyleScopedClasses['mt-4']} */ ;
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_62;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_63 = __VLS_asFunctionalComponent1(__VLS_62, new __VLS_62(__assign({ 'onClick': {} }, { label: "Apply Filters", size: "small" })));
var __VLS_64 = __VLS_63.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Apply Filters", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_63), false));
var __VLS_67;
var __VLS_68 = ({ click: {} },
    { onClick: (__VLS_ctx.applyFilters) });
var __VLS_65;
var __VLS_66;
var __VLS_69;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_70 = __VLS_asFunctionalComponent1(__VLS_69, new __VLS_69(__assign({ 'onClick': {} }, { label: "Clear Filters", severity: "secondary", size: "small" })));
var __VLS_71 = __VLS_70.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear Filters", severity: "secondary", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_70), false));
var __VLS_74;
var __VLS_75 = ({ click: {} },
    { onClick: (__VLS_ctx.clearFilters) });
var __VLS_72;
var __VLS_73;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
(__VLS_ctx.filteredTransactions.length);
(__VLS_ctx.transactions.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6" }));
/** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_76;
/** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
iconfield_1.default;
// @ts-ignore
var __VLS_77 = __VLS_asFunctionalComponent1(__VLS_76, new __VLS_76({}));
var __VLS_78 = __VLS_77.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_77), false));
var __VLS_81 = __VLS_79.slots.default;
var __VLS_82;
/** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
inputicon_1.default;
// @ts-ignore
var __VLS_83 = __VLS_asFunctionalComponent1(__VLS_82, new __VLS_82({}));
var __VLS_84 = __VLS_83.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_83), false));
var __VLS_87 = __VLS_85.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
// @ts-ignore
[transactions, showBatchActions, selectedTransactions, exportSelected, markAsProcessed, deleteSelected, clearSelection, formatCurrency, formatCurrency, formatCurrency, todaysRevenue, todayTransactions, pendingCount, monthlyRevenue, avgOrderValue, selectedStatus, statusOptions, selectedPaymentMethod, paymentMethodOptions, dateRange, dateRange, applyFilters, clearFilters, filteredTransactions,];
var __VLS_85;
var __VLS_88;
/** @ts-ignore @type {typeof __VLS_components.InputText} */
inputtext_1.default;
// @ts-ignore
var __VLS_89 = __VLS_asFunctionalComponent1(__VLS_88, new __VLS_88(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search" }, { class: "w-1/4 " })));
var __VLS_90 = __VLS_89.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search" }, { class: "w-1/4 " })], __VLS_functionalComponentArgsRest(__VLS_89), false));
/** @type {__VLS_StyleScopedClasses['w-1/4']} */ ;
/** @type {__VLS_StyleScopedClasses['']} */ ;
// @ts-ignore
[searchTerm,];
var __VLS_79;
if (__VLS_ctx.filteredTransactions.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center py-8 text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['py-8']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-inbox text-4xl mb-4" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-inbox']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-4xl']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm mt-2" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['mt-2']} */ ;
}
else {
    var __VLS_93 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_94 = __VLS_asFunctionalComponent1(__VLS_93, new __VLS_93({
        value: (__VLS_ctx.filteredTransactions),
        selection: (__VLS_ctx.selectedTransactions),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
        paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
        currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} transactions",
        loading: (__VLS_ctx.loading),
    }));
    var __VLS_95 = __VLS_94.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredTransactions),
            selection: (__VLS_ctx.selectedTransactions),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
            paginatorTemplate: "FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",
            currentPageReportTemplate: "Showing {first} to {last} of {totalRecords} transactions",
            loading: (__VLS_ctx.loading),
        }], __VLS_functionalComponentArgsRest(__VLS_94), false));
    var __VLS_98 = __VLS_96.slots.default;
    var __VLS_99 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_100 = __VLS_asFunctionalComponent1(__VLS_99, new __VLS_99(__assign({ selectionMode: "multiple" }, { style: {} })));
    var __VLS_101 = __VLS_100.apply(void 0, __spreadArray([__assign({ selectionMode: "multiple" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_100), false));
    var __VLS_104 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_105 = __VLS_asFunctionalComponent1(__VLS_104, new __VLS_104(__assign({ field: "orderId", header: "Order ID", sortable: true }, { style: {} })));
    var __VLS_106 = __VLS_105.apply(void 0, __spreadArray([__assign({ field: "orderId", header: "Order ID", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_105), false));
    var __VLS_109 = __VLS_107.slots.default;
    {
        var __VLS_110 = __VLS_107.slots.body;
        var slotProps_1 = __VLS_vSlot(__VLS_110)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        var __VLS_111 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_112 = __VLS_asFunctionalComponent1(__VLS_111, new __VLS_111(__assign(__assign({ 'onClick': {} }, { icon: "pi pi-external-link", text: true, rounded: true, size: "small" }), { class: "mr-2" })));
        var __VLS_113 = __VLS_112.apply(void 0, __spreadArray([__assign(__assign({ 'onClick': {} }, { icon: "pi pi-external-link", text: true, rounded: true, size: "small" }), { class: "mr-2" })], __VLS_functionalComponentArgsRest(__VLS_112), false));
        var __VLS_116 = void 0;
        var __VLS_117 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.filteredTransactions.length === 0))
                        return;
                    __VLS_ctx.viewTransaction(slotProps_1.data);
                    // @ts-ignore
                    [selectedTransactions, filteredTransactions, filteredTransactions, loading, viewTransaction,];
                } });
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        var __VLS_114;
        var __VLS_115;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-mono text-blue-600 font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-mono']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps_1.data.orderId);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_107;
    var __VLS_118 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_119 = __VLS_asFunctionalComponent1(__VLS_118, new __VLS_118(__assign({ field: "customer", header: "Customer", sortable: true }, { style: {} })));
    var __VLS_120 = __VLS_119.apply(void 0, __spreadArray([__assign({ field: "customer", header: "Customer", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_119), false));
    var __VLS_123 = __VLS_121.slots.default;
    {
        var __VLS_124 = __VLS_121.slots.body;
        var slotProps = __VLS_vSlot(__VLS_124)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        var __VLS_125 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Avatar} */
        avatar_1.default;
        // @ts-ignore
        var __VLS_126 = __VLS_asFunctionalComponent1(__VLS_125, new __VLS_125(__assign({ label: (__VLS_ctx.getInitials(slotProps.data.customer)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800" })));
        var __VLS_127 = __VLS_126.apply(void 0, __spreadArray([__assign({ label: (__VLS_ctx.getInitials(slotProps.data.customer)), size: "small", shape: "circle" }, { class: "bg-blue-100 text-blue-800" })], __VLS_functionalComponentArgsRest(__VLS_126), false));
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.customer);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.email);
        // @ts-ignore
        [getInitials,];
    }
    // @ts-ignore
    [];
    var __VLS_121;
    var __VLS_130 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_131 = __VLS_asFunctionalComponent1(__VLS_130, new __VLS_130(__assign({ field: "date", header: "Date & Time", sortable: true }, { style: {} })));
    var __VLS_132 = __VLS_131.apply(void 0, __spreadArray([__assign({ field: "date", header: "Date & Time", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_131), false));
    var __VLS_135 = __VLS_133.slots.default;
    {
        var __VLS_136 = __VLS_133.slots.body;
        var slotProps = __VLS_vSlot(__VLS_136)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.date));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.time);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_133;
    var __VLS_137 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_138 = __VLS_asFunctionalComponent1(__VLS_137, new __VLS_137(__assign({ field: "amount", header: "Amount", sortable: true }, { style: {} })));
    var __VLS_139 = __VLS_138.apply(void 0, __spreadArray([__assign({ field: "amount", header: "Amount", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_138), false));
    var __VLS_142 = __VLS_140.slots.default;
    {
        var __VLS_143 = __VLS_140.slots.body;
        var slotProps = __VLS_vSlot(__VLS_143)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-right" }));
        /** @type {__VLS_StyleScopedClasses['text-right']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.formatCurrency(slotProps.data.amount));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.itemCount);
        // @ts-ignore
        [formatCurrency,];
    }
    // @ts-ignore
    [];
    var __VLS_140;
    var __VLS_144 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144(__assign({ field: "paymentMethod", header: "Payment", sortable: true }, { style: {} })));
    var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([__assign({ field: "paymentMethod", header: "Payment", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_145), false));
    var __VLS_149 = __VLS_147.slots.default;
    {
        var __VLS_150 = __VLS_147.slots.body;
        var slotProps = __VLS_vSlot(__VLS_150)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("p-1 rounded ".concat(__VLS_ctx.getPaymentMethodClass(slotProps.data.paymentMethod))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("pi ".concat(__VLS_ctx.getPaymentMethodIcon(slotProps.data.paymentMethod), " text-sm")) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (slotProps.data.paymentMethod);
        // @ts-ignore
        [getPaymentMethodClass, getPaymentMethodIcon,];
    }
    // @ts-ignore
    [];
    var __VLS_147;
    var __VLS_151 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_152 = __VLS_asFunctionalComponent1(__VLS_151, new __VLS_151(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_153 = __VLS_152.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_152), false));
    var __VLS_156 = __VLS_154.slots.default;
    {
        var __VLS_157 = __VLS_154.slots.body;
        var slotProps = __VLS_vSlot(__VLS_157)[0];
        var __VLS_158 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_159 = __VLS_asFunctionalComponent1(__VLS_158, new __VLS_158({
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
            icon: (__VLS_ctx.getStatusIcon(slotProps.data.status)),
            rounded: true,
        }));
        var __VLS_160 = __VLS_159.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
                icon: (__VLS_ctx.getStatusIcon(slotProps.data.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_159), false));
        // @ts-ignore
        [getStatusSeverity, getStatusIcon,];
    }
    // @ts-ignore
    [];
    var __VLS_154;
    var __VLS_163 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_164), false));
    var __VLS_168 = __VLS_166.slots.default;
    {
        var __VLS_169 = __VLS_166.slots.body;
        var slotProps_2 = __VLS_vSlot(__VLS_169)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-1" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-1']} */ ;
        var __VLS_170 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "info" })));
        var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", text: true, rounded: true, severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_171), false));
        var __VLS_175 = void 0;
        var __VLS_176 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.filteredTransactions.length === 0))
                        return;
                    __VLS_ctx.viewTransaction(slotProps_2.data);
                    // @ts-ignore
                    [viewTransaction,];
                } });
        var __VLS_173;
        var __VLS_174;
        var __VLS_177 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177(__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })));
        var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-pencil", size: "small", text: true, rounded: true, severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_178), false));
        var __VLS_182 = void 0;
        var __VLS_183 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.filteredTransactions.length === 0))
                        return;
                    __VLS_ctx.editTransaction(slotProps_2.data);
                    // @ts-ignore
                    [editTransaction,];
                } });
        var __VLS_180;
        var __VLS_181;
        var __VLS_184 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_185 = __VLS_asFunctionalComponent1(__VLS_184, new __VLS_184(__assign({ 'onClick': {} }, { icon: "pi pi-print", size: "small", text: true, rounded: true, severity: "help" })));
        var __VLS_186 = __VLS_185.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-print", size: "small", text: true, rounded: true, severity: "help" })], __VLS_functionalComponentArgsRest(__VLS_185), false));
        var __VLS_189 = void 0;
        var __VLS_190 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.filteredTransactions.length === 0))
                        return;
                    __VLS_ctx.printInvoice(slotProps_2.data);
                    // @ts-ignore
                    [printInvoice,];
                } });
        var __VLS_187;
        var __VLS_188;
        var __VLS_191 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_192 = __VLS_asFunctionalComponent1(__VLS_191, new __VLS_191(__assign({ 'onClick': {} }, { icon: "pi pi-trash", size: "small", text: true, rounded: true, severity: "danger" })));
        var __VLS_193 = __VLS_192.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", size: "small", text: true, rounded: true, severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_192), false));
        var __VLS_196 = void 0;
        var __VLS_197 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!!(__VLS_ctx.filteredTransactions.length === 0))
                        return;
                    __VLS_ctx.confirmDelete(slotProps_2.data);
                    // @ts-ignore
                    [confirmDelete,];
                } });
        var __VLS_194;
        var __VLS_195;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_166;
    // @ts-ignore
    [];
    var __VLS_96;
}
var __VLS_198;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_199 = __VLS_asFunctionalComponent1(__VLS_198, new __VLS_198(__assign({ visible: (__VLS_ctx.showTransactionDialog), header: (__VLS_ctx.selectedTransaction ? "Transaction #".concat(__VLS_ctx.selectedTransaction.orderId) : 'Transaction Details') }, { style: ({ width: '700px' }) })));
var __VLS_200 = __VLS_199.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showTransactionDialog), header: (__VLS_ctx.selectedTransaction ? "Transaction #".concat(__VLS_ctx.selectedTransaction.orderId) : 'Transaction Details') }, { style: ({ width: '700px' }) })], __VLS_functionalComponentArgsRest(__VLS_199), false));
var __VLS_203 = __VLS_201.slots.default;
if (__VLS_ctx.selectedTransaction) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
    /** @type {__VLS_StyleScopedClasses['grid']} */ ;
    /** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedTransaction.customer);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedTransaction.date);
    (__VLS_ctx.selectedTransaction.time);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedTransaction.paymentMethod);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    var __VLS_204 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_205 = __VLS_asFunctionalComponent1(__VLS_204, new __VLS_204({
        value: (__VLS_ctx.selectedTransaction.status),
        severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedTransaction.status)),
    }));
    var __VLS_206 = __VLS_205.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.selectedTransaction.status),
            severity: (__VLS_ctx.getStatusSeverity(__VLS_ctx.selectedTransaction.status)),
        }], __VLS_functionalComponentArgsRest(__VLS_205), false));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-2" }));
    /** @type {__VLS_StyleScopedClasses['space-y-2']} */ ;
    for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.selectedTransaction.items)); _i < _a.length; _i++) {
        var item = _a[_i][0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (item.id) }, { class: "flex justify-between items-center p-3 bg-gray-50 rounded" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (item.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (item.quantity);
        (__VLS_ctx.formatCurrency(item.price));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (__VLS_ctx.formatCurrency(item.quantity * item.price));
        // @ts-ignore
        [formatCurrency, formatCurrency, getStatusSeverity, showTransactionDialog, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "border-t pt-4" }));
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between mb-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatCurrency(__VLS_ctx.selectedTransaction.subtotal));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between mb-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatCurrency(__VLS_ctx.selectedTransaction.shipping));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between mb-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-gray-600" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatCurrency(__VLS_ctx.selectedTransaction.tax));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex justify-between font-bold text-lg pt-2 border-t" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['pt-2']} */ ;
    /** @type {__VLS_StyleScopedClasses['border-t']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    (__VLS_ctx.formatCurrency(__VLS_ctx.selectedTransaction.amount));
    if (__VLS_ctx.selectedTransaction.notes) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-2" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
        /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
        (__VLS_ctx.selectedTransaction.notes);
    }
}
{
    var __VLS_209 = __VLS_201.slots.footer;
    var __VLS_210 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_211 = __VLS_asFunctionalComponent1(__VLS_210, new __VLS_210(__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })));
    var __VLS_212 = __VLS_211.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Close", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_211), false));
    var __VLS_215 = void 0;
    var __VLS_216 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showTransactionDialog = false;
                // @ts-ignore
                [formatCurrency, formatCurrency, formatCurrency, formatCurrency, showTransactionDialog, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction, selectedTransaction,];
            } });
    var __VLS_213;
    var __VLS_214;
    var __VLS_217 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_218 = __VLS_asFunctionalComponent1(__VLS_217, new __VLS_217(__assign({ 'onClick': {} }, { label: "Print Invoice", icon: "pi pi-print" })));
    var __VLS_219 = __VLS_218.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Print Invoice", icon: "pi pi-print" })], __VLS_functionalComponentArgsRest(__VLS_218), false));
    var __VLS_222 = void 0;
    var __VLS_223 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.printInvoice(__VLS_ctx.selectedTransaction);
                // @ts-ignore
                [printInvoice, selectedTransaction,];
            } });
    var __VLS_220;
    var __VLS_221;
    var __VLS_224 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_225 = __VLS_asFunctionalComponent1(__VLS_224, new __VLS_224(__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil" })));
    var __VLS_226 = __VLS_225.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Edit", icon: "pi pi-pencil" })], __VLS_functionalComponentArgsRest(__VLS_225), false));
    var __VLS_229 = void 0;
    var __VLS_230 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.editTransaction(__VLS_ctx.selectedTransaction);
                // @ts-ignore
                [editTransaction, selectedTransaction,];
            } });
    var __VLS_227;
    var __VLS_228;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_201;
var __VLS_231;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_232 = __VLS_asFunctionalComponent1(__VLS_231, new __VLS_231(__assign({ visible: (__VLS_ctx.showNewTransactionDialog), header: "New Transaction" }, { style: ({ width: '800px' }) })));
var __VLS_233 = __VLS_232.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showNewTransactionDialog), header: "New Transaction" }, { style: ({ width: '800px' }) })], __VLS_functionalComponentArgsRest(__VLS_232), false));
var __VLS_236 = __VLS_234.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_237;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_238 = __VLS_asFunctionalComponent1(__VLS_237, new __VLS_237(__assign({ modelValue: (__VLS_ctx.newTransaction.customerId), options: (__VLS_ctx.customers), optionLabel: "name", placeholder: "Select Customer" }, { class: "w-full" })));
var __VLS_239 = __VLS_238.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newTransaction.customerId), options: (__VLS_ctx.customers), optionLabel: "name", placeholder: "Select Customer" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_238), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_242;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_243 = __VLS_asFunctionalComponent1(__VLS_242, new __VLS_242(__assign({ modelValue: (__VLS_ctx.newTransaction.paymentMethod), options: (__VLS_ctx.paymentMethodOptions), optionLabel: "name", placeholder: "Select Method" }, { class: "w-full" })));
var __VLS_244 = __VLS_243.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newTransaction.paymentMethod), options: (__VLS_ctx.paymentMethodOptions), optionLabel: "name", placeholder: "Select Method" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_243), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_247;
/** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
datatable_1.default;
// @ts-ignore
var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247(__assign({ value: (__VLS_ctx.newTransaction.items) }, { class: "mb-4" })));
var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.newTransaction.items) }, { class: "mb-4" })], __VLS_functionalComponentArgsRest(__VLS_248), false));
/** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
var __VLS_252 = __VLS_250.slots.default;
var __VLS_253;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_254 = __VLS_asFunctionalComponent1(__VLS_253, new __VLS_253({
    field: "product",
    header: "Product",
}));
var __VLS_255 = __VLS_254.apply(void 0, __spreadArray([{
        field: "product",
        header: "Product",
    }], __VLS_functionalComponentArgsRest(__VLS_254), false));
var __VLS_258;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_259 = __VLS_asFunctionalComponent1(__VLS_258, new __VLS_258(__assign({ field: "quantity", header: "Qty" }, { style: {} })));
var __VLS_260 = __VLS_259.apply(void 0, __spreadArray([__assign({ field: "quantity", header: "Qty" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_259), false));
var __VLS_263 = __VLS_261.slots.default;
{
    var __VLS_264 = __VLS_261.slots.body;
    var slotProps = __VLS_vSlot(__VLS_264)[0];
    var __VLS_265 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    inputnumber_1.default;
    // @ts-ignore
    var __VLS_266 = __VLS_asFunctionalComponent1(__VLS_265, new __VLS_265({
        modelValue: (slotProps.data.quantity),
        min: (1),
        showButtons: true,
    }));
    var __VLS_267 = __VLS_266.apply(void 0, __spreadArray([{
            modelValue: (slotProps.data.quantity),
            min: (1),
            showButtons: true,
        }], __VLS_functionalComponentArgsRest(__VLS_266), false));
    // @ts-ignore
    [showNewTransactionDialog, paymentMethodOptions, newTransaction, newTransaction, newTransaction, customers,];
}
// @ts-ignore
[];
var __VLS_261;
var __VLS_270;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_271 = __VLS_asFunctionalComponent1(__VLS_270, new __VLS_270(__assign({ field: "price", header: "Price" }, { style: {} })));
var __VLS_272 = __VLS_271.apply(void 0, __spreadArray([__assign({ field: "price", header: "Price" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_271), false));
var __VLS_275 = __VLS_273.slots.default;
{
    var __VLS_276 = __VLS_273.slots.body;
    var slotProps = __VLS_vSlot(__VLS_276)[0];
    var __VLS_277 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputNumber} */
    inputnumber_1.default;
    // @ts-ignore
    var __VLS_278 = __VLS_asFunctionalComponent1(__VLS_277, new __VLS_277({
        modelValue: (slotProps.data.price),
        mode: "currency",
        currency: "PHP",
        locale: "en-PH",
    }));
    var __VLS_279 = __VLS_278.apply(void 0, __spreadArray([{
            modelValue: (slotProps.data.price),
            mode: "currency",
            currency: "PHP",
            locale: "en-PH",
        }], __VLS_functionalComponentArgsRest(__VLS_278), false));
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_273;
var __VLS_282;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_283 = __VLS_asFunctionalComponent1(__VLS_282, new __VLS_282(__assign({ header: "Total" }, { style: {} })));
var __VLS_284 = __VLS_283.apply(void 0, __spreadArray([__assign({ header: "Total" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_283), false));
var __VLS_287 = __VLS_285.slots.default;
{
    var __VLS_288 = __VLS_285.slots.body;
    var slotProps = __VLS_vSlot(__VLS_288)[0];
    (__VLS_ctx.formatCurrency(slotProps.data.quantity * slotProps.data.price));
    // @ts-ignore
    [formatCurrency,];
}
// @ts-ignore
[];
var __VLS_285;
var __VLS_289;
/** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
column_1.default;
// @ts-ignore
var __VLS_290 = __VLS_asFunctionalComponent1(__VLS_289, new __VLS_289(__assign({ header: "Action" }, { style: {} })));
var __VLS_291 = __VLS_290.apply(void 0, __spreadArray([__assign({ header: "Action" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_290), false));
var __VLS_294 = __VLS_292.slots.default;
{
    var __VLS_295 = __VLS_292.slots.body;
    var slotProps_3 = __VLS_vSlot(__VLS_295)[0];
    var __VLS_296 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_297 = __VLS_asFunctionalComponent1(__VLS_296, new __VLS_296(__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })));
    var __VLS_298 = __VLS_297.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-trash", severity: "danger", text: true, rounded: true })], __VLS_functionalComponentArgsRest(__VLS_297), false));
    var __VLS_301 = void 0;
    var __VLS_302 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.removeItem(slotProps_3.index);
                // @ts-ignore
                [removeItem,];
            } });
    var __VLS_299;
    var __VLS_300;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_292;
// @ts-ignore
[];
var __VLS_250;
var __VLS_303;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_304 = __VLS_asFunctionalComponent1(__VLS_303, new __VLS_303(__assign({ 'onClick': {} }, { label: "Add Item", icon: "pi pi-plus", size: "small" })));
var __VLS_305 = __VLS_304.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Add Item", icon: "pi pi-plus", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_304), false));
var __VLS_308;
var __VLS_309 = ({ click: {} },
    { onClick: (__VLS_ctx.addItem) });
var __VLS_306;
var __VLS_307;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-2 gap-4" }));
/** @type {__VLS_StyleScopedClasses['grid']} */ ;
/** @type {__VLS_StyleScopedClasses['grid-cols-2']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_310;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_311 = __VLS_asFunctionalComponent1(__VLS_310, new __VLS_310(__assign({ modelValue: (__VLS_ctx.newTransaction.shipping), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" })));
var __VLS_312 = __VLS_311.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newTransaction.shipping), mode: "currency", currency: "PHP", locale: "en-PH" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_311), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_315;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_316 = __VLS_asFunctionalComponent1(__VLS_315, new __VLS_315(__assign({ modelValue: (__VLS_ctx.newTransaction.taxRate), suffix: "%", min: (0), max: (100) }, { class: "w-full" })));
var __VLS_317 = __VLS_316.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newTransaction.taxRate), suffix: "%", min: (0), max: (100) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_316), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_320;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_321 = __VLS_asFunctionalComponent1(__VLS_320, new __VLS_320(__assign({ modelValue: (__VLS_ctx.newTransaction.notes), rows: "3" }, { class: "w-full" })));
var __VLS_322 = __VLS_321.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.newTransaction.notes), rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_321), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_325 = __VLS_234.slots.footer;
    var __VLS_326 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_327 = __VLS_asFunctionalComponent1(__VLS_326, new __VLS_326(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_328 = __VLS_327.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_327), false));
    var __VLS_331 = void 0;
    var __VLS_332 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showNewTransactionDialog = false;
                // @ts-ignore
                [showNewTransactionDialog, newTransaction, newTransaction, newTransaction, addItem,];
            } });
    var __VLS_329;
    var __VLS_330;
    var __VLS_333 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_334 = __VLS_asFunctionalComponent1(__VLS_333, new __VLS_333(__assign({ 'onClick': {} }, { label: "Create Transaction" })));
    var __VLS_335 = __VLS_334.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Create Transaction" })], __VLS_functionalComponentArgsRest(__VLS_334), false));
    var __VLS_338 = void 0;
    var __VLS_339 = ({ click: {} },
        { onClick: (__VLS_ctx.createTransaction) });
    var __VLS_336;
    var __VLS_337;
    // @ts-ignore
    [createTransaction,];
}
// @ts-ignore
[];
var __VLS_234;
var __VLS_340;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_341 = __VLS_asFunctionalComponent1(__VLS_340, new __VLS_340(__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: (true) }, { style: ({ width: '400px' }) })));
var __VLS_342 = __VLS_341.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showDeleteDialog), header: "Confirm Delete", modal: (true) }, { style: ({ width: '400px' }) })], __VLS_functionalComponentArgsRest(__VLS_341), false));
var __VLS_345 = __VLS_343.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "confirmation-content" }));
/** @type {__VLS_StyleScopedClasses['confirmation-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle mr-3" }, { style: {} }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
{
    var __VLS_346 = __VLS_343.slots.footer;
    var __VLS_347 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_348 = __VLS_asFunctionalComponent1(__VLS_347, new __VLS_347(__assign({ 'onClick': {} }, { label: "No", severity: "secondary" })));
    var __VLS_349 = __VLS_348.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "No", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_348), false));
    var __VLS_352 = void 0;
    var __VLS_353 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showDeleteDialog = false;
                // @ts-ignore
                [showDeleteDialog, showDeleteDialog,];
            } });
    var __VLS_350;
    var __VLS_351;
    var __VLS_354 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_355 = __VLS_asFunctionalComponent1(__VLS_354, new __VLS_354(__assign({ 'onClick': {} }, { label: "Yes", severity: "danger" })));
    var __VLS_356 = __VLS_355.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Yes", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_355), false));
    var __VLS_359 = void 0;
    var __VLS_360 = ({ click: {} },
        { onClick: (__VLS_ctx.deleteTransaction) });
    var __VLS_357;
    var __VLS_358;
    // @ts-ignore
    [deleteTransaction,];
}
// @ts-ignore
[];
var __VLS_343;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
