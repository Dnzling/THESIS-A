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
var select_1 = require("primevue/select");
var multiselect_1 = require("primevue/multiselect");
var iconfield_1 = require("primevue/iconfield");
var inputicon_1 = require("primevue/inputicon");
var dialog_1 = require("primevue/dialog");
var textarea_1 = require("primevue/textarea");
var badge_1 = require("primevue/badge");
var inputnumber_1 = require("primevue/inputnumber");
var inputswitch_1 = require("primevue/inputswitch");
// State
var activeView = (0, vue_1.ref)('pending');
var loading = (0, vue_1.ref)(false);
var searchTerm = (0, vue_1.ref)('');
var showReviewDialog = (0, vue_1.ref)(false);
var showRejectDialog = (0, vue_1.ref)(false);
var showBulkApproveDialog = (0, vue_1.ref)(false);
var showBulkRejectDialog = (0, vue_1.ref)(false);
var showSettingsDialog = (0, vue_1.ref)(false);
var showPendingFilters = (0, vue_1.ref)(false);
var selectedStores = (0, vue_1.ref)([]);
var selectedReviewStore = (0, vue_1.ref)(null);
var storeToReject = (0, vue_1.ref)(null);
var reviewNotes = (0, vue_1.ref)('');
var rejectionReason = (0, vue_1.ref)(null);
var rejectionNotes = (0, vue_1.ref)('');
var bulkRejectionReason = (0, vue_1.ref)(null);
// Filters
var dateFilter = (0, vue_1.ref)(null);
var storeTypeFilter = (0, vue_1.ref)([]);
var waitingTimeFilter = (0, vue_1.ref)(null);
var documentStatusFilter = (0, vue_1.ref)([]);
var priorityFilter = (0, vue_1.ref)(null);
var approvalDateFilter = (0, vue_1.ref)(null);
var rejectionReasonFilter = (0, vue_1.ref)([]);
var statusFilter = (0, vue_1.ref)(null);
// Settings
var autoApprovalEnabled = (0, vue_1.ref)(false);
var autoApprovalDelay = (0, vue_1.ref)(null);
var emailNotifications = (0, vue_1.ref)(true);
var smsNotifications = (0, vue_1.ref)(false);
var minDocuments = (0, vue_1.ref)(3);
var maxReviewDays = (0, vue_1.ref)(7);
// Store Data
var pendingStores = (0, vue_1.ref)([
    {
        id: 1,
        storeId: 'STORE-2024-001',
        storeName: 'Modern Furniture Hub',
        ownerName: 'Juan Dela Cruz',
        ownerEmail: 'juan@email.com',
        ownerPhone: '+639123456789',
        storeType: 'Furniture Retail',
        address: '123 Main St, Manila',
        contactNumber: '+6328123456',
        registrationDate: '2024-01-15',
        waitingTime: '2 days',
        documentStatus: 'Complete',
        priority: 'High',
        documents: [
            { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Tax Certificate', status: 'Pending', verificationStatus: 'pending' }
        ]
    },
    {
        id: 2,
        storeId: 'STORE-2024-002',
        storeName: 'Wood Crafts Studio',
        ownerName: 'Maria Santos',
        ownerEmail: 'maria@email.com',
        ownerPhone: '+639234567890',
        storeType: 'Furniture Manufacturing',
        address: '456 Oak Ave, Quezon City',
        contactNumber: '+6328234567',
        registrationDate: '2024-01-16',
        waitingTime: '1 day',
        documentStatus: 'Incomplete',
        priority: 'Medium',
        documents: [
            { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Mayor\'s Permit', status: 'Missing', verificationStatus: 'missing' }
        ]
    },
    {
        id: 3,
        storeId: 'STORE-2024-003',
        storeName: 'Luxury Home Decor',
        ownerName: 'Robert Lim',
        ownerEmail: 'robert@email.com',
        ownerPhone: '+639345678901',
        storeType: 'Home Decor',
        address: '789 Luxury Blvd, Makati',
        contactNumber: '+6328345678',
        registrationDate: '2024-01-14',
        waitingTime: '3 days',
        documentStatus: 'Complete',
        priority: 'Low',
        documents: [
            { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Tax Certificate', status: 'Verified', verificationStatus: 'verified' }
        ]
    },
    {
        id: 4,
        storeId: 'STORE-2024-004',
        storeName: 'Office Solutions Inc',
        ownerName: 'Sarah Chen',
        ownerEmail: 'sarah@email.com',
        ownerPhone: '+639456789012',
        storeType: 'Office Furniture',
        address: '101 Corporate St, Taguig',
        contactNumber: '+6328456789',
        registrationDate: '2024-01-17',
        waitingTime: 'Just now',
        documentStatus: 'Pending Review',
        priority: 'High',
        documents: [
            { name: 'Business Permit', status: 'Pending', verificationStatus: 'pending' },
            { name: 'Mayor\'s Permit', status: 'Pending', verificationStatus: 'pending' }
        ]
    },
    {
        id: 5,
        storeId: 'STORE-2024-005',
        storeName: 'Eco Furniture Co',
        ownerName: 'David Green',
        ownerEmail: 'david@email.com',
        ownerPhone: '+639567890123',
        storeType: 'Sustainable Furniture',
        address: '202 Green St, Pasig',
        contactNumber: '+6328567890',
        registrationDate: '2024-01-13',
        waitingTime: '4 days',
        documentStatus: 'Complete',
        priority: 'Medium',
        documents: [
            { name: 'Business Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Mayor\'s Permit', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Tax Certificate', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Environmental Permit', status: 'Verified', verificationStatus: 'verified' }
        ]
    }
]);
var approvedStores = (0, vue_1.ref)([
    {
        id: 6,
        storeId: 'STORE-2023-101',
        storeName: 'Classic Furniture Gallery',
        ownerName: 'James Wilson',
        ownerEmail: 'james@email.com',
        storeType: 'Antique Furniture',
        address: '303 Heritage Rd, Cebu',
        registrationDate: '2023-12-10',
        approvalDate: '2023-12-15',
        approvedBy: 'Admin 1',
        status: 'Active',
        productsCount: 145,
        revenue: 1250000
    },
    {
        id: 7,
        storeId: 'STORE-2023-102',
        storeName: 'Modern Living Spaces',
        ownerName: 'Lisa Garcia',
        ownerEmail: 'lisa@email.com',
        storeType: 'Modern Furniture',
        address: '404 Modern Ave, Davao',
        registrationDate: '2023-11-25',
        approvalDate: '2023-11-30',
        approvedBy: 'Admin 2',
        status: 'Active',
        productsCount: 89,
        revenue: 980000
    },
    {
        id: 8,
        storeId: 'STORE-2023-103',
        storeName: 'Kids Furniture World',
        ownerName: 'Michael Tan',
        ownerEmail: 'michael@email.com',
        storeType: 'Kids Furniture',
        address: '505 Playground St, Iloilo',
        registrationDate: '2023-12-05',
        approvalDate: '2023-12-10',
        approvedBy: 'Admin 1',
        status: 'Active',
        productsCount: 67,
        revenue: 750000
    },
    {
        id: 9,
        storeId: 'STORE-2024-006',
        storeName: 'Outdoor Living Co',
        ownerName: 'Anna Lee',
        ownerEmail: 'anna@email.com',
        storeType: 'Outdoor Furniture',
        address: '606 Garden St, Baguio',
        registrationDate: '2024-01-05',
        approvalDate: '2024-01-10',
        approvedBy: 'Admin 3',
        status: 'Active',
        productsCount: 42,
        revenue: 560000
    },
    {
        id: 10,
        storeId: 'STORE-2024-007',
        storeName: 'Smart Furniture Tech',
        ownerName: 'Paul Rivera',
        ownerEmail: 'paul@email.com',
        storeType: 'Smart Furniture',
        address: '707 Tech Blvd, Pasay',
        registrationDate: '2024-01-08',
        approvalDate: '2024-01-12',
        approvedBy: 'Admin 2',
        status: 'Active',
        productsCount: 31,
        revenue: 420000
    }
]);
var rejectedStores = (0, vue_1.ref)([
    {
        id: 11,
        storeId: 'STORE-2023-201',
        storeName: 'Quick Furniture Mart',
        ownerName: 'John Doe',
        ownerEmail: 'john@email.com',
        storeType: 'Furniture Retail',
        address: '808 Fast St, Mandaluyong',
        registrationDate: '2023-11-20',
        rejectionDate: '2023-11-25',
        rejectedBy: 'Admin 1',
        status: 'Rejected',
        rejectionReason: 'Incomplete Documentation',
        notes: 'Missing required permits and identification'
    },
    {
        id: 12,
        storeId: 'STORE-2023-202',
        storeName: 'Budget Furniture Store',
        ownerName: 'Jane Smith',
        ownerEmail: 'jane@email.com',
        storeType: 'Budget Furniture',
        address: '909 Budget Rd, Paranaque',
        registrationDate: '2023-12-01',
        rejectionDate: '2023-12-05',
        rejectedBy: 'Admin 2',
        status: 'Rejected',
        rejectionReason: 'Business Location Issues',
        notes: 'Registered address does not match business location'
    },
    {
        id: 13,
        storeId: 'STORE-2024-008',
        storeName: 'Luxury Bedroom Sets',
        ownerName: 'Carlos Reyes',
        ownerEmail: 'carlos@email.com',
        storeType: 'Bedroom Furniture',
        address: '1010 Sleep St, Alabang',
        registrationDate: '2024-01-10',
        rejectionDate: '2024-01-14',
        rejectedBy: 'Admin 3',
        status: 'Rejected',
        rejectionReason: 'Duplicate Registration',
        notes: 'Multiple applications detected from same owner'
    }
]);
// Filter Options
var dateFilterOptions = (0, vue_1.ref)([
    { name: 'Today', value: 'today' },
    { name: 'Last 7 days', value: '7days' },
    { name: 'Last 30 days', value: '30days' },
    { name: 'Last 90 days', value: '90days' },
    { name: 'This year', value: 'year' },
    { name: 'All time', value: 'all' }
]);
var storeTypeOptions = (0, vue_1.ref)([
    { name: 'Furniture Retail', value: 'retail' },
    { name: 'Furniture Manufacturing', value: 'manufacturing' },
    { name: 'Home Decor', value: 'decor' },
    { name: 'Office Furniture', value: 'office' },
    { name: 'Sustainable Furniture', value: 'sustainable' },
    { name: 'Antique Furniture', value: 'antique' },
    { name: 'Kids Furniture', value: 'kids' },
    { name: 'Outdoor Furniture', value: 'outdoor' },
    { name: 'Smart Furniture', value: 'smart' }
]);
var waitingTimeOptions = (0, vue_1.ref)([
    { name: 'Just now', value: 'now' },
    { name: 'Within 1 day', value: '1day' },
    { name: '1-3 days', value: '1-3days' },
    { name: '3-7 days', value: '3-7days' },
    { name: 'Over 7 days', value: '7+days' }
]);
var documentStatusOptions = (0, vue_1.ref)([
    { name: 'Complete', value: 'complete' },
    { name: 'Incomplete', value: 'incomplete' },
    { name: 'Pending Review', value: 'pending' },
    { name: 'Missing Documents', value: 'missing' }
]);
var priorityOptions = (0, vue_1.ref)([
    { name: 'High', value: 'high' },
    { name: 'Medium', value: 'medium' },
    { name: 'Low', value: 'low' }
]);
var approvalDateOptions = (0, vue_1.ref)([
    { name: 'Today', value: 'today' },
    { name: 'This week', value: 'week' },
    { name: 'This month', value: 'month' },
    { name: 'Last month', value: 'last-month' },
    { name: 'All time', value: 'all' }
]);
var rejectionReasonOptions = (0, vue_1.ref)([
    { name: 'Incomplete Documentation', value: 'incomplete-docs' },
    { name: 'Business Location Issues', value: 'location' },
    { name: 'Duplicate Registration', value: 'duplicate' },
    { name: 'Invalid Business Type', value: 'invalid-type' },
    { name: 'Suspicious Activity', value: 'suspicious' },
    { name: 'Policy Violation', value: 'policy' },
    { name: 'Other', value: 'other' }
]);
var allStatusOptions = (0, vue_1.ref)([
    { name: 'Pending', value: 'pending' },
    { name: 'Approved', value: 'approved' },
    { name: 'Rejected', value: 'rejected' },
    { name: 'Suspended', value: 'suspended' },
    { name: 'Active', value: 'active' }
]);
var verificationStatusOptions = (0, vue_1.ref)([
    { name: 'Verified', value: 'verified' },
    { name: 'Pending', value: 'pending' },
    { name: 'Missing', value: 'missing' },
    { name: 'Invalid', value: 'invalid' }
]);
var delayOptions = (0, vue_1.ref)([
    { name: 'Immediately', value: '0' },
    { name: '1 hour', value: '1' },
    { name: '6 hours', value: '6' },
    { name: '24 hours', value: '24' },
    { name: '3 days', value: '72' }
]);
// Computed Properties
var filteredPendingStores = (0, vue_1.computed)(function () {
    var filtered = pendingStores.value;
    if (searchTerm.value && activeView.value === 'pending') {
        var term_1 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (store) {
            return store.storeName.toLowerCase().includes(term_1) ||
                store.ownerName.toLowerCase().includes(term_1) ||
                store.storeId.toLowerCase().includes(term_1);
        });
    }
    // Additional filters for pending view
    if (waitingTimeFilter.value) {
        // Implement waiting time filtering logic
    }
    if (documentStatusFilter.value.length > 0) {
        var statuses_1 = documentStatusFilter.value.map(function (s) { return s.value; });
        filtered = filtered.filter(function (store) { return statuses_1.includes(store.documentStatus.toLowerCase().replace(/ /g, '-')); });
    }
    if (priorityFilter.value) {
        filtered = filtered.filter(function (store) { return store.priority === priorityFilter.value.name; });
    }
    return filtered;
});
var filteredApprovedStores = (0, vue_1.computed)(function () {
    var filtered = approvedStores.value;
    if (searchTerm.value && activeView.value === 'approved') {
        var term_2 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (store) {
            return store.storeName.toLowerCase().includes(term_2) ||
                store.ownerName.toLowerCase().includes(term_2) ||
                store.storeId.toLowerCase().includes(term_2);
        });
    }
    // Additional filters for approved view
    if (approvalDateFilter.value) {
        // Implement approval date filtering logic
    }
    return filtered;
});
var filteredRejectedStores = (0, vue_1.computed)(function () {
    var filtered = rejectedStores.value;
    if (searchTerm.value && activeView.value === 'rejected') {
        var term_3 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (store) {
            return store.storeName.toLowerCase().includes(term_3) ||
                store.ownerName.toLowerCase().includes(term_3) ||
                store.storeId.toLowerCase().includes(term_3);
        });
    }
    // Additional filters for rejected view
    if (rejectionReasonFilter.value.length > 0) {
        var reasons_1 = rejectionReasonFilter.value.map(function (r) { return r.value; });
        filtered = filtered.filter(function (store) { return reasons_1.includes(store.rejectionReason.toLowerCase().replace(/ /g, '-')); });
    }
    return filtered;
});
var filteredAllStores = (0, vue_1.computed)(function () {
    var allStores = __spreadArray(__spreadArray(__spreadArray([], pendingStores.value, true), approvedStores.value, true), rejectedStores.value, true);
    var filtered = allStores;
    if (searchTerm.value && activeView.value === 'all') {
        var term_4 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (store) {
            return store.storeName.toLowerCase().includes(term_4) ||
                store.ownerName.toLowerCase().includes(term_4) ||
                store.storeId.toLowerCase().includes(term_4);
        });
    }
    // Status filter for all stores
    if (statusFilter.value) {
        filtered = filtered.filter(function (store) { return store.status === statusFilter.value.name; });
    }
    return filtered;
});
var approvedTodayCount = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return approvedStores.value.filter(function (store) { return store.approvalDate === today; }).length;
});
var rejectedTodayCount = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return rejectedStores.value.filter(function (store) { return store.rejectionDate === today; }).length;
});
var totalStores = (0, vue_1.computed)(function () {
    return pendingStores.value.length + approvedStores.value.length + rejectedStores.value.length;
});
// Helper Functions
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
var getStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'warning';
        case 'approved':
        case 'active': return 'success';
        case 'rejected': return 'danger';
        case 'suspended': return 'secondary';
        default: return 'info';
    }
};
var getStoreStatusIcon = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'pi-clock';
        case 'approved':
        case 'active': return 'pi-check-circle';
        case 'rejected': return 'pi-times-circle';
        case 'suspended': return 'pi-pause-circle';
        default: return 'pi-store';
    }
};
var getStoreStatusColor = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'bg-yellow-100 text-yellow-600';
        case 'approved':
        case 'active': return 'bg-green-100 text-green-600';
        case 'rejected': return 'bg-red-100 text-red-600';
        case 'suspended': return 'bg-gray-100 text-gray-600';
        default: return 'bg-blue-100 text-blue-600';
    }
};
var getDocumentIcon = function (status) {
    switch (status) {
        case 'Complete': return 'pi-check-circle';
        case 'Incomplete': return 'pi-exclamation-circle';
        case 'Pending Review': return 'pi-clock';
        case 'Missing Documents': return 'pi-times-circle';
        default: return 'pi-file';
    }
};
var getDocumentColor = function (status) {
    switch (status) {
        case 'Complete': return 'text-green-500';
        case 'Incomplete': return 'text-yellow-500';
        case 'Pending Review': return 'text-blue-500';
        case 'Missing Documents': return 'text-red-500';
        default: return 'text-gray-500';
    }
};
// Action Functions
var setActiveView = function (view) {
    activeView.value = view;
    selectedStores.value = [];
    searchTerm.value = '';
};
var togglePendingFilters = function () {
    showPendingFilters.value = !showPendingFilters.value;
};
var reviewStore = function (store) {
    selectedReviewStore.value = store;
    showReviewDialog.value = true;
};
var approveStore = function (store) {
    if (!store)
        return;
    // Move from pending to approved
    var pendingIndex = pendingStores.value.findIndex(function (s) { return s.id === store.id; });
    if (pendingIndex !== -1) {
        var approvedStore = __assign({}, pendingStores.value[pendingIndex]);
        approvedStore.approvalDate = new Date().toISOString().split('T')[0];
        approvedStore.approvedBy = 'Current Admin';
        approvedStore.status = 'Active';
        approvedStore.productsCount = 0;
        approvedStore.revenue = 0;
        pendingStores.value.splice(pendingIndex, 1);
        approvedStores.value.unshift(approvedStore);
    }
    showReviewDialog.value = false;
};
var rejectStore = function (store) {
    storeToReject.value = store;
    showRejectDialog.value = true;
};
var confirmReject = function () {
    var _a;
    if (!storeToReject.value)
        return;
    var pendingIndex = pendingStores.value.findIndex(function (s) { return s.id === storeToReject.value.id; });
    if (pendingIndex !== -1) {
        var rejectedStore = __assign({}, pendingStores.value[pendingIndex]);
        rejectedStore.rejectionDate = new Date().toISOString().split('T')[0];
        rejectedStore.rejectedBy = 'Current Admin';
        rejectedStore.status = 'Rejected';
        rejectedStore.rejectionReason = ((_a = rejectionReason.value) === null || _a === void 0 ? void 0 : _a.name) || 'Other';
        rejectedStore.notes = rejectionNotes.value;
        pendingStores.value.splice(pendingIndex, 1);
        rejectedStores.value.unshift(rejectedStore);
    }
    showRejectDialog.value = false;
    rejectionReason.value = null;
    rejectionNotes.value = '';
    storeToReject.value = null;
};
var viewStore = function (store) {
    console.log('View store:', store);
    // Navigate to store details page
};
var suspendStore = function (store) {
    console.log('Suspend store:', store);
    // Implement suspension logic
};
var viewRejectedStore = function (store) {
    console.log('View rejected store:', store);
};
var rereviewStore = function (store) {
    // Move from rejected to pending
    var rejectedIndex = rejectedStores.value.findIndex(function (s) { return s.id === store.id; });
    if (rejectedIndex !== -1) {
        var pendingStore = __assign({}, rejectedStores.value[rejectedIndex]);
        delete pendingStore.rejectionDate;
        delete pendingStore.rejectedBy;
        delete pendingStore.rejectionReason;
        delete pendingStore.notes;
        pendingStore.status = 'Pending';
        pendingStore.documentStatus = 'Pending Review';
        rejectedStores.value.splice(rejectedIndex, 1);
        pendingStores.value.push(pendingStore);
    }
};
var viewDocument = function (doc) {
    console.log('View document:', doc);
    // Open document viewer
};
var requestMoreInfo = function () {
    console.log('Request more info for store:', selectedReviewStore.value);
    // Implement request more info logic
};
var bulkApprove = function () {
    selectedStores.value.forEach(function (store) {
        approveStore(store);
    });
    selectedStores.value = [];
    showBulkApproveDialog.value = false;
};
var bulkReject = function () {
    selectedStores.value.forEach(function (store) {
        storeToReject.value = store;
        confirmReject();
    });
    selectedStores.value = [];
    showBulkRejectDialog.value = false;
};
var sendReminders = function () {
    console.log('Sending reminders to pending stores');
    // Implement reminder logic
};
var exportReport = function () {
    console.log('Exporting validation report');
    // Implement export logic
};
var saveSettings = function () {
    console.log('Saving validation settings');
    showSettingsDialog.value = false;
};
(0, vue_1.onMounted)(function () {
    console.log('Store Validation Management loaded');
});
var __VLS_ctx = __assign(__assign({}, {}), {});
var __VLS_components;
var __VLS_intrinsics;
var __VLS_directives;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
var __VLS_0;
/** @ts-ignore @type {typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0(__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary" })));
var __VLS_2 = __VLS_1.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Export Report", icon: "pi pi-download", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_1), false));
var __VLS_5;
var __VLS_6 = ({ click: {} },
    { onClick: (__VLS_ctx.exportReport) });
var __VLS_3;
var __VLS_4;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center justify-between gap-4" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800 mr-4" }));
/** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
/** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-4']} */ ;
var __VLS_7;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'pending' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'pending') })));
var __VLS_9 = __VLS_8.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'pending' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'pending') })], __VLS_functionalComponentArgsRest(__VLS_8), false));
var __VLS_12;
var __VLS_13 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setActiveView('pending');
            // @ts-ignore
            [exportReport, activeView, activeView, setActiveView,];
        } });
var __VLS_14 = __VLS_10.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-clock mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.pendingStores.length > 0) {
    var __VLS_15 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15(__assign({ value: (__VLS_ctx.pendingStores.length), severity: "warning" }, { class: "ml-2" })));
    var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.pendingStores.length), severity: "warning" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_16), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[pendingStores, pendingStores,];
var __VLS_10;
var __VLS_11;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'approved' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'approved') })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'approved' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'approved') })], __VLS_functionalComponentArgsRest(__VLS_21), false));
var __VLS_25;
var __VLS_26 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setActiveView('approved');
            // @ts-ignore
            [activeView, activeView, setActiveView,];
        } });
var __VLS_27 = __VLS_23.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.approvedStores.length > 0) {
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ value: (__VLS_ctx.approvedStores.length), severity: "success" }, { class: "ml-2" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.approvedStores.length), severity: "success" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[approvedStores, approvedStores,];
var __VLS_23;
var __VLS_24;
var __VLS_33;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_34 = __VLS_asFunctionalComponent1(__VLS_33, new __VLS_33(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'rejected' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'rejected') })));
var __VLS_35 = __VLS_34.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'rejected' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'rejected') })], __VLS_functionalComponentArgsRest(__VLS_34), false));
var __VLS_38;
var __VLS_39 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setActiveView('rejected');
            // @ts-ignore
            [activeView, activeView, setActiveView,];
        } });
var __VLS_40 = __VLS_36.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.rejectedStores.length > 0) {
    var __VLS_41 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_42 = __VLS_asFunctionalComponent1(__VLS_41, new __VLS_41(__assign({ value: (__VLS_ctx.rejectedStores.length), severity: "danger" }, { class: "ml-2" })));
    var __VLS_43 = __VLS_42.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.rejectedStores.length), severity: "danger" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_42), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[rejectedStores, rejectedStores,];
var __VLS_36;
var __VLS_37;
var __VLS_46;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_47 = __VLS_asFunctionalComponent1(__VLS_46, new __VLS_46(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'all' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'all') })));
var __VLS_48 = __VLS_47.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'all' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'all') })], __VLS_functionalComponentArgsRest(__VLS_47), false));
var __VLS_51;
var __VLS_52 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setActiveView('all');
            // @ts-ignore
            [activeView, activeView, setActiveView,];
        } });
var __VLS_53 = __VLS_49.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-list mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-list']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
var __VLS_54;
/** @ts-ignore @type {typeof __VLS_components.Badge} */
badge_1.default;
// @ts-ignore
var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54(__assign({ value: (__VLS_ctx.totalStores), severity: "info" }, { class: "ml-2" })));
var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.totalStores), severity: "info" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_55), false));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
// @ts-ignore
[totalStores,];
var __VLS_49;
var __VLS_50;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-white shadow rounded-xl p-6" }));
/** @type {__VLS_StyleScopedClasses['bg-white']} */ ;
/** @type {__VLS_StyleScopedClasses['shadow']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-xl']} */ ;
/** @type {__VLS_StyleScopedClasses['p-6']} */ ;
if (__VLS_ctx.activeView === 'pending') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center justify-between gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.filteredPendingStores.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_59 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "success", outlined: true, size: "small", disabled: (__VLS_ctx.selectedStores.length === 0) })));
    var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "success", outlined: true, size: "small", disabled: (__VLS_ctx.selectedStores.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_60), false));
    var __VLS_64 = void 0;
    var __VLS_65 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.activeView === 'pending'))
                    return;
                __VLS_ctx.showBulkApproveDialog = true;
                // @ts-ignore
                [activeView, filteredPendingStores, selectedStores, showBulkApproveDialog,];
            } });
    var __VLS_62;
    var __VLS_63;
    var __VLS_66 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_67 = __VLS_asFunctionalComponent1(__VLS_66, new __VLS_66(__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.selectedStores.length === 0) })));
    var __VLS_68 = __VLS_67.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.selectedStores.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_67), false));
    var __VLS_71 = void 0;
    var __VLS_72 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.activeView === 'pending'))
                    return;
                __VLS_ctx.showBulkRejectDialog = true;
                // @ts-ignore
                [selectedStores, showBulkRejectDialog,];
            } });
    var __VLS_69;
    var __VLS_70;
    var __VLS_73 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_74 = __VLS_asFunctionalComponent1(__VLS_73, new __VLS_73(__assign({ 'onClick': {} }, { icon: "pi pi-envelope", severity: "help", outlined: true, size: "small" })));
    var __VLS_75 = __VLS_74.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-envelope", severity: "help", outlined: true, size: "small" })], __VLS_functionalComponentArgsRest(__VLS_74), false));
    var __VLS_78 = void 0;
    var __VLS_79 = ({ click: {} },
        { onClick: (__VLS_ctx.sendReminders) });
    var __VLS_76;
    var __VLS_77;
    var __VLS_80 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_81 = __VLS_asFunctionalComponent1(__VLS_80, new __VLS_80(__assign({ modelValue: (__VLS_ctx.waitingTimeFilter), options: (__VLS_ctx.waitingTimeOptions), optionLabel: "name", placeholder: "Waiting Time" }, { class: "w-40" })));
    var __VLS_82 = __VLS_81.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.waitingTimeFilter), options: (__VLS_ctx.waitingTimeOptions), optionLabel: "name", placeholder: "Waiting Time" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_81), false));
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign({ modelValue: (__VLS_ctx.documentStatusFilter), options: (__VLS_ctx.documentStatusOptions), optionLabel: "name", placeholder: "Doc Status", display: "chip" }, { class: "w-48" })));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.documentStatusFilter), options: (__VLS_ctx.documentStatusOptions), optionLabel: "name", placeholder: "Doc Status", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_86), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign({ 'onClick': {} }, { icon: "pi pi-filter", severity: "secondary" })));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_91), false));
    var __VLS_95 = void 0;
    var __VLS_96 = ({ click: {} },
        { onClick: (__VLS_ctx.togglePendingFilters) });
    var __VLS_93;
    var __VLS_94;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_97 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_98 = __VLS_asFunctionalComponent1(__VLS_97, new __VLS_97({}));
    var __VLS_99 = __VLS_98.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_98), false));
    var __VLS_102 = __VLS_100.slots.default;
    var __VLS_103 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_104 = __VLS_asFunctionalComponent1(__VLS_103, new __VLS_103({}));
    var __VLS_105 = __VLS_104.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_104), false));
    var __VLS_108 = __VLS_106.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [sendReminders, waitingTimeFilter, waitingTimeOptions, documentStatusFilter, documentStatusOptions, togglePendingFilters,];
    var __VLS_106;
    var __VLS_109 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_110 = __VLS_asFunctionalComponent1(__VLS_109, new __VLS_109(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search" }, { class: "w-full" })));
    var __VLS_111 = __VLS_110.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_110), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_100;
    if (__VLS_ctx.showPendingFilters) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "mb-6 p-4 bg-gray-50 rounded-lg" }));
        /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "grid grid-cols-1 md:grid-cols-3 gap-4" }));
        /** @type {__VLS_StyleScopedClasses['grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['grid-cols-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['md:grid-cols-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_114 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
        multiselect_1.default;
        // @ts-ignore
        var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "All types", display: "chip" }, { class: "w-full" })));
        var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "All types", display: "chip" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_115), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_119 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "All time" }, { class: "w-full" })));
        var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "All time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_120), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-end gap-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-end']} */ ;
        /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
        var __VLS_124 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ 'onClick': {} }, { label: "Apply Filters", size: "small" })));
        var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Apply Filters", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_125), false));
        var __VLS_129 = void 0;
        var __VLS_130 = ({ click: {} },
            { onClick: (__VLS_ctx.applyFilters) });
        var __VLS_127;
        var __VLS_128;
        var __VLS_131 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_132 = __VLS_asFunctionalComponent1(__VLS_131, new __VLS_131(__assign({ 'onClick': {} }, { label: "Clear", severity: "secondary", size: "small" })));
        var __VLS_133 = __VLS_132.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Clear", severity: "secondary", size: "small" })], __VLS_functionalComponentArgsRest(__VLS_132), false));
        var __VLS_136 = void 0;
        var __VLS_137 = ({ click: {} },
            { onClick: (__VLS_ctx.clearFilters) });
        var __VLS_134;
        var __VLS_135;
    }
    var __VLS_138 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_139 = __VLS_asFunctionalComponent1(__VLS_138, new __VLS_138({
        value: (__VLS_ctx.filteredPendingStores),
        selection: (__VLS_ctx.selectedStores),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_140 = __VLS_139.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredPendingStores),
            selection: (__VLS_ctx.selectedStores),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_139), false));
    var __VLS_143 = __VLS_141.slots.default;
    var __VLS_144 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_145 = __VLS_asFunctionalComponent1(__VLS_144, new __VLS_144({
        selectionMode: "multiple",
        headerStyle: "width: 3rem",
    }));
    var __VLS_146 = __VLS_145.apply(void 0, __spreadArray([{
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
        }], __VLS_functionalComponentArgsRest(__VLS_145), false));
    var __VLS_149 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_150 = __VLS_asFunctionalComponent1(__VLS_149, new __VLS_149(__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })));
    var __VLS_151 = __VLS_150.apply(void 0, __spreadArray([__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_150), false));
    var __VLS_154 = __VLS_152.slots.default;
    {
        var __VLS_155 = __VLS_152.slots.body;
        var slotProps = __VLS_vSlot(__VLS_155)[0];
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
        (slotProps.data.storeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.storeId);
        // @ts-ignore
        [filteredPendingStores, selectedStores, showPendingFilters, storeTypeFilter, storeTypeOptions, dateFilter, dateFilterOptions, applyFilters, clearFilters,];
    }
    // @ts-ignore
    [];
    var __VLS_152;
    var __VLS_156 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_157 = __VLS_asFunctionalComponent1(__VLS_156, new __VLS_156(__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })));
    var __VLS_158 = __VLS_157.apply(void 0, __spreadArray([__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_157), false));
    var __VLS_161 = __VLS_159.slots.default;
    {
        var __VLS_162 = __VLS_159.slots.body;
        var slotProps = __VLS_vSlot(__VLS_162)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.ownerName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.ownerEmail);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_159;
    var __VLS_163 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_164 = __VLS_asFunctionalComponent1(__VLS_163, new __VLS_163(__assign({ field: "registrationDate", header: "Submitted", sortable: true }, { style: {} })));
    var __VLS_165 = __VLS_164.apply(void 0, __spreadArray([__assign({ field: "registrationDate", header: "Submitted", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_164), false));
    var __VLS_168 = __VLS_166.slots.default;
    {
        var __VLS_169 = __VLS_166.slots.body;
        var slotProps = __VLS_vSlot(__VLS_169)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.registrationDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.waitingTime);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_166;
    var __VLS_170 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_171 = __VLS_asFunctionalComponent1(__VLS_170, new __VLS_170(__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })));
    var __VLS_172 = __VLS_171.apply(void 0, __spreadArray([__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_171), false));
    var __VLS_175 = __VLS_173.slots.default;
    {
        var __VLS_176 = __VLS_173.slots.body;
        var slotProps = __VLS_vSlot(__VLS_176)[0];
        var __VLS_177 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_178 = __VLS_asFunctionalComponent1(__VLS_177, new __VLS_177({
            value: (slotProps.data.storeType),
            severity: "info",
            rounded: true,
        }));
        var __VLS_179 = __VLS_178.apply(void 0, __spreadArray([{
                value: (slotProps.data.storeType),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_178), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_173;
    var __VLS_182 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_183 = __VLS_asFunctionalComponent1(__VLS_182, new __VLS_182(__assign({ field: "documentStatus", header: "Documents", sortable: true }, { style: {} })));
    var __VLS_184 = __VLS_183.apply(void 0, __spreadArray([__assign({ field: "documentStatus", header: "Documents", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_183), false));
    var __VLS_187 = __VLS_185.slots.default;
    {
        var __VLS_188 = __VLS_185.slots.body;
        var slotProps = __VLS_vSlot(__VLS_188)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("pi ".concat(__VLS_ctx.getDocumentIcon(slotProps.data.documentStatus), " mr-2 ").concat(__VLS_ctx.getDocumentColor(slotProps.data.documentStatus))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (slotProps.data.documentStatus);
        // @ts-ignore
        [getDocumentIcon, getDocumentColor,];
    }
    // @ts-ignore
    [];
    var __VLS_185;
    var __VLS_189 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_190 = __VLS_asFunctionalComponent1(__VLS_189, new __VLS_189(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_191 = __VLS_190.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_190), false));
    var __VLS_194 = __VLS_192.slots.default;
    {
        var __VLS_195 = __VLS_192.slots.body;
        var slotProps_1 = __VLS_vSlot(__VLS_195)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_196 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_197 = __VLS_asFunctionalComponent1(__VLS_196, new __VLS_196(__assign({ 'onClick': {} }, { label: "Review", size: "small", icon: "pi pi-eye" })));
        var __VLS_198 = __VLS_197.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Review", size: "small", icon: "pi pi-eye" })], __VLS_functionalComponentArgsRest(__VLS_197), false));
        var __VLS_201 = void 0;
        var __VLS_202 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.reviewStore(slotProps_1.data);
                    // @ts-ignore
                    [reviewStore,];
                } });
        var __VLS_199;
        var __VLS_200;
        var __VLS_203 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_204 = __VLS_asFunctionalComponent1(__VLS_203, new __VLS_203(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })));
        var __VLS_205 = __VLS_204.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_204), false));
        var __VLS_208 = void 0;
        var __VLS_209 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.approveStore(slotProps_1.data);
                    // @ts-ignore
                    [approveStore,];
                } });
        var __VLS_206;
        var __VLS_207;
        var __VLS_210 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_211 = __VLS_asFunctionalComponent1(__VLS_210, new __VLS_210(__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })));
        var __VLS_212 = __VLS_211.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_211), false));
        var __VLS_215 = void 0;
        var __VLS_216 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.rejectStore(slotProps_1.data);
                    // @ts-ignore
                    [rejectStore,];
                } });
        var __VLS_213;
        var __VLS_214;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_192;
    // @ts-ignore
    [];
    var __VLS_141;
}
if (__VLS_ctx.activeView === 'approved') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center justify-between gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.filteredApprovedStores.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_217 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_218 = __VLS_asFunctionalComponent1(__VLS_217, new __VLS_217({}));
    var __VLS_219 = __VLS_218.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_218), false));
    var __VLS_222 = __VLS_220.slots.default;
    var __VLS_223 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_224 = __VLS_asFunctionalComponent1(__VLS_223, new __VLS_223({}));
    var __VLS_225 = __VLS_224.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_224), false));
    var __VLS_228 = __VLS_226.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredApprovedStores,];
    var __VLS_226;
    var __VLS_229 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_230 = __VLS_asFunctionalComponent1(__VLS_229, new __VLS_229(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search approved stores..." }, { class: "w-full" })));
    var __VLS_231 = __VLS_230.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search approved stores..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_230), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_220;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_234 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_235 = __VLS_asFunctionalComponent1(__VLS_234, new __VLS_234(__assign({ modelValue: (__VLS_ctx.approvalDateFilter), options: (__VLS_ctx.approvalDateOptions), optionLabel: "name", placeholder: "Approval Date" }, { class: "w-48" })));
    var __VLS_236 = __VLS_235.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.approvalDateFilter), options: (__VLS_ctx.approvalDateOptions), optionLabel: "name", placeholder: "Approval Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_235), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_239 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_240 = __VLS_asFunctionalComponent1(__VLS_239, new __VLS_239(__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })));
    var __VLS_241 = __VLS_240.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_240), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_244 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_245 = __VLS_asFunctionalComponent1(__VLS_244, new __VLS_244(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })));
    var __VLS_246 = __VLS_245.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_245), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_249 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_250 = __VLS_asFunctionalComponent1(__VLS_249, new __VLS_249({
        value: (__VLS_ctx.filteredApprovedStores),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_251 = __VLS_250.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredApprovedStores),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_250), false));
    var __VLS_254 = __VLS_252.slots.default;
    var __VLS_255 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_256 = __VLS_asFunctionalComponent1(__VLS_255, new __VLS_255(__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })));
    var __VLS_257 = __VLS_256.apply(void 0, __spreadArray([__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_256), false));
    var __VLS_260 = __VLS_258.slots.default;
    {
        var __VLS_261 = __VLS_258.slots.body;
        var slotProps = __VLS_vSlot(__VLS_261)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-check-circle text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-check-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.storeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.storeId);
        // @ts-ignore
        [storeTypeFilter, storeTypeOptions, dateFilter, dateFilterOptions, filteredApprovedStores, approvalDateFilter, approvalDateOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_258;
    var __VLS_262 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_263 = __VLS_asFunctionalComponent1(__VLS_262, new __VLS_262(__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })));
    var __VLS_264 = __VLS_263.apply(void 0, __spreadArray([__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_263), false));
    var __VLS_267 = __VLS_265.slots.default;
    {
        var __VLS_268 = __VLS_265.slots.body;
        var slotProps = __VLS_vSlot(__VLS_268)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.ownerName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.ownerEmail);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_265;
    var __VLS_269 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_270 = __VLS_asFunctionalComponent1(__VLS_269, new __VLS_269(__assign({ field: "approvalDate", header: "Approved On", sortable: true }, { style: {} })));
    var __VLS_271 = __VLS_270.apply(void 0, __spreadArray([__assign({ field: "approvalDate", header: "Approved On", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_270), false));
    var __VLS_274 = __VLS_272.slots.default;
    {
        var __VLS_275 = __VLS_272.slots.body;
        var slotProps = __VLS_vSlot(__VLS_275)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.approvalDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.approvedBy);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_272;
    var __VLS_276 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_277 = __VLS_asFunctionalComponent1(__VLS_276, new __VLS_276(__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })));
    var __VLS_278 = __VLS_277.apply(void 0, __spreadArray([__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_277), false));
    var __VLS_281 = __VLS_279.slots.default;
    {
        var __VLS_282 = __VLS_279.slots.body;
        var slotProps = __VLS_vSlot(__VLS_282)[0];
        var __VLS_283 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_284 = __VLS_asFunctionalComponent1(__VLS_283, new __VLS_283({
            value: (slotProps.data.storeType),
            severity: "info",
            rounded: true,
        }));
        var __VLS_285 = __VLS_284.apply(void 0, __spreadArray([{
                value: (slotProps.data.storeType),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_284), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_279;
    var __VLS_288 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_289 = __VLS_asFunctionalComponent1(__VLS_288, new __VLS_288(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_290 = __VLS_289.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_289), false));
    var __VLS_293 = __VLS_291.slots.default;
    {
        var __VLS_294 = __VLS_291.slots.body;
        var slotProps = __VLS_vSlot(__VLS_294)[0];
        var __VLS_295 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_296 = __VLS_asFunctionalComponent1(__VLS_295, new __VLS_295({
            value: (slotProps.data.status),
            severity: "success",
            rounded: true,
        }));
        var __VLS_297 = __VLS_296.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: "success",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_296), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_291;
    var __VLS_300 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_301 = __VLS_asFunctionalComponent1(__VLS_300, new __VLS_300(__assign({ field: "productsCount", header: "Products", sortable: true }, { style: {} })));
    var __VLS_302 = __VLS_301.apply(void 0, __spreadArray([__assign({ field: "productsCount", header: "Products", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_301), false));
    var __VLS_305 = __VLS_303.slots.default;
    {
        var __VLS_306 = __VLS_303.slots.body;
        var slotProps = __VLS_vSlot(__VLS_306)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (slotProps.data.productsCount);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_303;
    var __VLS_307 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_308 = __VLS_asFunctionalComponent1(__VLS_307, new __VLS_307(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_309 = __VLS_308.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_308), false));
    var __VLS_312 = __VLS_310.slots.default;
    {
        var __VLS_313 = __VLS_310.slots.body;
        var slotProps_2 = __VLS_vSlot(__VLS_313)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_314 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_315 = __VLS_asFunctionalComponent1(__VLS_314, new __VLS_314(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_316 = __VLS_315.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_315), false));
        var __VLS_319 = void 0;
        var __VLS_320 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'approved'))
                        return;
                    __VLS_ctx.viewStore(slotProps_2.data);
                    // @ts-ignore
                    [viewStore,];
                } });
        var __VLS_317;
        var __VLS_318;
        var __VLS_321 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_322 = __VLS_asFunctionalComponent1(__VLS_321, new __VLS_321(__assign({ 'onClick': {} }, { icon: "pi pi-ban", size: "small", severity: "danger" })));
        var __VLS_323 = __VLS_322.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-ban", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_322), false));
        var __VLS_326 = void 0;
        var __VLS_327 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'approved'))
                        return;
                    __VLS_ctx.suspendStore(slotProps_2.data);
                    // @ts-ignore
                    [suspendStore,];
                } });
        var __VLS_324;
        var __VLS_325;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_310;
    // @ts-ignore
    [];
    var __VLS_252;
}
if (__VLS_ctx.activeView === 'rejected') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center justify-between gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.filteredRejectedStores.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_328 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_329 = __VLS_asFunctionalComponent1(__VLS_328, new __VLS_328({}));
    var __VLS_330 = __VLS_329.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_329), false));
    var __VLS_333 = __VLS_331.slots.default;
    var __VLS_334 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_335 = __VLS_asFunctionalComponent1(__VLS_334, new __VLS_334({}));
    var __VLS_336 = __VLS_335.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_335), false));
    var __VLS_339 = __VLS_337.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredRejectedStores,];
    var __VLS_337;
    var __VLS_340 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_341 = __VLS_asFunctionalComponent1(__VLS_340, new __VLS_340(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search rejected stores..." }, { class: "w-full" })));
    var __VLS_342 = __VLS_341.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search rejected stores..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_341), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_331;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_345 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_346 = __VLS_asFunctionalComponent1(__VLS_345, new __VLS_345(__assign({ modelValue: (__VLS_ctx.rejectionReasonFilter), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Rejection Reasons", display: "chip" }, { class: "w-48" })));
    var __VLS_347 = __VLS_346.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionReasonFilter), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Rejection Reasons", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_346), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_350 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_351 = __VLS_asFunctionalComponent1(__VLS_350, new __VLS_350(__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })));
    var __VLS_352 = __VLS_351.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_351), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_355 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_356 = __VLS_asFunctionalComponent1(__VLS_355, new __VLS_355(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })));
    var __VLS_357 = __VLS_356.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_356), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_360 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_361 = __VLS_asFunctionalComponent1(__VLS_360, new __VLS_360({
        value: (__VLS_ctx.filteredRejectedStores),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_362 = __VLS_361.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredRejectedStores),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_361), false));
    var __VLS_365 = __VLS_363.slots.default;
    var __VLS_366 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_367 = __VLS_asFunctionalComponent1(__VLS_366, new __VLS_366(__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })));
    var __VLS_368 = __VLS_367.apply(void 0, __spreadArray([__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_367), false));
    var __VLS_371 = __VLS_369.slots.default;
    {
        var __VLS_372 = __VLS_369.slots.body;
        var slotProps = __VLS_vSlot(__VLS_372)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-times-circle text-red-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-times-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.storeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.storeId);
        // @ts-ignore
        [storeTypeFilter, storeTypeOptions, dateFilter, dateFilterOptions, filteredRejectedStores, rejectionReasonFilter, rejectionReasonOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_369;
    var __VLS_373 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_374 = __VLS_asFunctionalComponent1(__VLS_373, new __VLS_373(__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })));
    var __VLS_375 = __VLS_374.apply(void 0, __spreadArray([__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_374), false));
    var __VLS_378 = __VLS_376.slots.default;
    {
        var __VLS_379 = __VLS_376.slots.body;
        var slotProps = __VLS_vSlot(__VLS_379)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.ownerName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.ownerEmail);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_376;
    var __VLS_380 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_381 = __VLS_asFunctionalComponent1(__VLS_380, new __VLS_380(__assign({ field: "rejectionDate", header: "Rejected On", sortable: true }, { style: {} })));
    var __VLS_382 = __VLS_381.apply(void 0, __spreadArray([__assign({ field: "rejectionDate", header: "Rejected On", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_381), false));
    var __VLS_385 = __VLS_383.slots.default;
    {
        var __VLS_386 = __VLS_383.slots.body;
        var slotProps = __VLS_vSlot(__VLS_386)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.rejectionDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.rejectedBy);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_383;
    var __VLS_387 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_388 = __VLS_asFunctionalComponent1(__VLS_387, new __VLS_387(__assign({ field: "rejectionReason", header: "Reason", sortable: true }, { style: {} })));
    var __VLS_389 = __VLS_388.apply(void 0, __spreadArray([__assign({ field: "rejectionReason", header: "Reason", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_388), false));
    var __VLS_392 = __VLS_390.slots.default;
    {
        var __VLS_393 = __VLS_390.slots.body;
        var slotProps = __VLS_vSlot(__VLS_393)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-start" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-start']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle text-red-500 mt-1 mr-2" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        /** @type {__VLS_StyleScopedClasses['mt-1']} */ ;
        /** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm" }));
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        (slotProps.data.rejectionReason);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_390;
    var __VLS_394 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_395 = __VLS_asFunctionalComponent1(__VLS_394, new __VLS_394(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_396 = __VLS_395.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_395), false));
    var __VLS_399 = __VLS_397.slots.default;
    {
        var __VLS_400 = __VLS_397.slots.body;
        var slotProps = __VLS_vSlot(__VLS_400)[0];
        var __VLS_401 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_402 = __VLS_asFunctionalComponent1(__VLS_401, new __VLS_401({
            value: (slotProps.data.status),
            severity: "danger",
            rounded: true,
        }));
        var __VLS_403 = __VLS_402.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: "danger",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_402), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_397;
    var __VLS_406 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_407 = __VLS_asFunctionalComponent1(__VLS_406, new __VLS_406(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_408 = __VLS_407.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_407), false));
    var __VLS_411 = __VLS_409.slots.default;
    {
        var __VLS_412 = __VLS_409.slots.body;
        var slotProps_3 = __VLS_vSlot(__VLS_412)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_413 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_414 = __VLS_asFunctionalComponent1(__VLS_413, new __VLS_413(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_415 = __VLS_414.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_414), false));
        var __VLS_418 = void 0;
        var __VLS_419 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'rejected'))
                        return;
                    __VLS_ctx.viewRejectedStore(slotProps_3.data);
                    // @ts-ignore
                    [viewRejectedStore,];
                } });
        var __VLS_416;
        var __VLS_417;
        var __VLS_420 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_421 = __VLS_asFunctionalComponent1(__VLS_420, new __VLS_420(__assign({ 'onClick': {} }, { label: "Re-review", size: "small", icon: "pi pi-redo" })));
        var __VLS_422 = __VLS_421.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Re-review", size: "small", icon: "pi pi-redo" })], __VLS_functionalComponentArgsRest(__VLS_421), false));
        var __VLS_425 = void 0;
        var __VLS_426 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'rejected'))
                        return;
                    __VLS_ctx.rereviewStore(slotProps_3.data);
                    // @ts-ignore
                    [rereviewStore,];
                } });
        var __VLS_423;
        var __VLS_424;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_409;
    // @ts-ignore
    [];
    var __VLS_363;
}
if (__VLS_ctx.activeView === 'all') {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center justify-between gap-4 mb-6" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)(__assign({ class: "text-lg font-semibold text-gray-800" }));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-semibold']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.filteredAllStores.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_427 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_428 = __VLS_asFunctionalComponent1(__VLS_427, new __VLS_427({}));
    var __VLS_429 = __VLS_428.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_428), false));
    var __VLS_432 = __VLS_430.slots.default;
    var __VLS_433 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_434 = __VLS_asFunctionalComponent1(__VLS_433, new __VLS_433({}));
    var __VLS_435 = __VLS_434.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_434), false));
    var __VLS_438 = __VLS_436.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredAllStores,];
    var __VLS_436;
    var __VLS_439 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_440 = __VLS_asFunctionalComponent1(__VLS_439, new __VLS_439(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search all stores..." }, { class: "w-full" })));
    var __VLS_441 = __VLS_440.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search all stores..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_440), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_430;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_444 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_445 = __VLS_asFunctionalComponent1(__VLS_444, new __VLS_444(__assign({ modelValue: (__VLS_ctx.statusFilter), options: (__VLS_ctx.allStatusOptions), optionLabel: "name", placeholder: "Status" }, { class: "w-40" })));
    var __VLS_446 = __VLS_445.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.statusFilter), options: (__VLS_ctx.allStatusOptions), optionLabel: "name", placeholder: "Status" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_445), false));
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_449 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_450 = __VLS_asFunctionalComponent1(__VLS_449, new __VLS_449(__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })));
    var __VLS_451 = __VLS_450.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.storeTypeFilter), options: (__VLS_ctx.storeTypeOptions), optionLabel: "name", placeholder: "Store Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_450), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_454 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_455 = __VLS_asFunctionalComponent1(__VLS_454, new __VLS_454(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })));
    var __VLS_456 = __VLS_455.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_455), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_459 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_460 = __VLS_asFunctionalComponent1(__VLS_459, new __VLS_459({
        value: (__VLS_ctx.filteredAllStores),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_461 = __VLS_460.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredAllStores),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_460), false));
    var __VLS_464 = __VLS_462.slots.default;
    var __VLS_465 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_466 = __VLS_asFunctionalComponent1(__VLS_465, new __VLS_465(__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })));
    var __VLS_467 = __VLS_466.apply(void 0, __spreadArray([__assign({ field: "storeName", header: "Store Name", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_466), false));
    var __VLS_470 = __VLS_468.slots.default;
    {
        var __VLS_471 = __VLS_468.slots.body;
        var slotProps = __VLS_vSlot(__VLS_471)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-10 h-10 rounded-lg flex items-center justify-center ".concat(__VLS_ctx.getStoreStatusColor(slotProps.data.status))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("pi ".concat(__VLS_ctx.getStoreStatusIcon(slotProps.data.status))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.storeName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.storeId);
        // @ts-ignore
        [storeTypeFilter, storeTypeOptions, dateFilter, dateFilterOptions, filteredAllStores, statusFilter, allStatusOptions, getStoreStatusColor, getStoreStatusIcon,];
    }
    // @ts-ignore
    [];
    var __VLS_468;
    var __VLS_472 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_473 = __VLS_asFunctionalComponent1(__VLS_472, new __VLS_472(__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })));
    var __VLS_474 = __VLS_473.apply(void 0, __spreadArray([__assign({ field: "owner", header: "Owner", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_473), false));
    var __VLS_477 = __VLS_475.slots.default;
    {
        var __VLS_478 = __VLS_475.slots.body;
        var slotProps = __VLS_vSlot(__VLS_478)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.ownerName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.ownerEmail);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_475;
    var __VLS_479 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_480 = __VLS_asFunctionalComponent1(__VLS_479, new __VLS_479(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_481 = __VLS_480.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_480), false));
    var __VLS_484 = __VLS_482.slots.default;
    {
        var __VLS_485 = __VLS_482.slots.body;
        var slotProps = __VLS_vSlot(__VLS_485)[0];
        var __VLS_486 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_487 = __VLS_asFunctionalComponent1(__VLS_486, new __VLS_486({
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
            rounded: true,
        }));
        var __VLS_488 = __VLS_487.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: (__VLS_ctx.getStatusSeverity(slotProps.data.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_487), false));
        // @ts-ignore
        [getStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_482;
    var __VLS_491 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_492 = __VLS_asFunctionalComponent1(__VLS_491, new __VLS_491(__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })));
    var __VLS_493 = __VLS_492.apply(void 0, __spreadArray([__assign({ field: "storeType", header: "Type", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_492), false));
    var __VLS_496 = __VLS_494.slots.default;
    {
        var __VLS_497 = __VLS_494.slots.body;
        var slotProps = __VLS_vSlot(__VLS_497)[0];
        var __VLS_498 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_499 = __VLS_asFunctionalComponent1(__VLS_498, new __VLS_498({
            value: (slotProps.data.storeType),
            severity: "info",
            rounded: true,
        }));
        var __VLS_500 = __VLS_499.apply(void 0, __spreadArray([{
                value: (slotProps.data.storeType),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_499), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_494;
    var __VLS_503 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_504 = __VLS_asFunctionalComponent1(__VLS_503, new __VLS_503(__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })));
    var __VLS_505 = __VLS_504.apply(void 0, __spreadArray([__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_504), false));
    var __VLS_508 = __VLS_506.slots.default;
    {
        var __VLS_509 = __VLS_506.slots.body;
        var slotProps = __VLS_vSlot(__VLS_509)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.registrationDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.age);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_506;
    var __VLS_510 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_511 = __VLS_asFunctionalComponent1(__VLS_510, new __VLS_510(__assign({ field: "productsCount", header: "Products", sortable: true }, { style: {} })));
    var __VLS_512 = __VLS_511.apply(void 0, __spreadArray([__assign({ field: "productsCount", header: "Products", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_511), false));
    var __VLS_515 = __VLS_513.slots.default;
    {
        var __VLS_516 = __VLS_513.slots.body;
        var slotProps = __VLS_vSlot(__VLS_516)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (slotProps.data.productsCount);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_513;
    var __VLS_517 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_518 = __VLS_asFunctionalComponent1(__VLS_517, new __VLS_517(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_519 = __VLS_518.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_518), false));
    var __VLS_522 = __VLS_520.slots.default;
    {
        var __VLS_523 = __VLS_520.slots.body;
        var slotProps_4 = __VLS_vSlot(__VLS_523)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_524 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_525 = __VLS_asFunctionalComponent1(__VLS_524, new __VLS_524(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_526 = __VLS_525.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_525), false));
        var __VLS_529 = void 0;
        var __VLS_530 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'all'))
                        return;
                    __VLS_ctx.viewStore(slotProps_4.data);
                    // @ts-ignore
                    [viewStore,];
                } });
        var __VLS_527;
        var __VLS_528;
        if (slotProps_4.data.status === 'Pending') {
            var __VLS_531 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_532 = __VLS_asFunctionalComponent1(__VLS_531, new __VLS_531(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })));
            var __VLS_533 = __VLS_532.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_532), false));
            var __VLS_536 = void 0;
            var __VLS_537 = ({ click: {} },
                { onClick: function () {
                        var _a = [];
                        for (var _i = 0; _i < arguments.length; _i++) {
                            _a[_i] = arguments[_i];
                        }
                        var $event = _a[0];
                        if (!(__VLS_ctx.activeView === 'all'))
                            return;
                        if (!(slotProps_4.data.status === 'Pending'))
                            return;
                        __VLS_ctx.approveStore(slotProps_4.data);
                        // @ts-ignore
                        [approveStore,];
                    } });
            var __VLS_534;
            var __VLS_535;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_520;
    // @ts-ignore
    [];
    var __VLS_462;
}
var __VLS_538;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_539 = __VLS_asFunctionalComponent1(__VLS_538, new __VLS_538(__assign({ visible: (__VLS_ctx.showReviewDialog), modal: true, header: (__VLS_ctx.selectedReviewStore ? "Review Store: ".concat(__VLS_ctx.selectedReviewStore.storeName) : 'Review Store') }, { style: ({ width: '800px' }) })));
var __VLS_540 = __VLS_539.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showReviewDialog), modal: true, header: (__VLS_ctx.selectedReviewStore ? "Review Store: ".concat(__VLS_ctx.selectedReviewStore.storeName) : 'Review Store') }, { style: ({ width: '800px' }) })], __VLS_functionalComponentArgsRest(__VLS_539), false));
var __VLS_543 = __VLS_541.slots.default;
if (__VLS_ctx.selectedReviewStore) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
    /** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
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
    (__VLS_ctx.selectedReviewStore.storeName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewStore.storeType);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewStore.address);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewStore.contactNumber);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
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
    (__VLS_ctx.selectedReviewStore.ownerName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewStore.ownerEmail);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewStore.ownerPhone);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedReviewStore.registrationDate));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
    /** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
    var _loop_1 = function (doc) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ key: (doc.name) }, { class: "flex items-center justify-between p-3 bg-gray-50 rounded" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
        /** @type {__VLS_StyleScopedClasses['p-3']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-file-pdf text-red-500" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-file-pdf']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-500']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (doc.name);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (doc.status);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_544 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_545 = __VLS_asFunctionalComponent1(__VLS_544, new __VLS_544(__assign({ 'onClick': {} }, { label: "View", size: "small", icon: "pi pi-eye" })));
        var __VLS_546 = __VLS_545.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View", size: "small", icon: "pi pi-eye" })], __VLS_functionalComponentArgsRest(__VLS_545), false));
        var __VLS_549 = void 0;
        var __VLS_550 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.selectedReviewStore))
                        return;
                    __VLS_ctx.viewDocument(doc);
                    // @ts-ignore
                    [formatDate, showReviewDialog, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, selectedReviewStore, viewDocument,];
                } });
        var __VLS_551 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_552 = __VLS_asFunctionalComponent1(__VLS_551, new __VLS_551(__assign({ modelValue: (doc.verificationStatus), options: (__VLS_ctx.verificationStatusOptions), optionLabel: "name", placeholder: "Verify" }, { class: "w-32" })));
        var __VLS_553 = __VLS_552.apply(void 0, __spreadArray([__assign({ modelValue: (doc.verificationStatus), options: (__VLS_ctx.verificationStatusOptions), optionLabel: "name", placeholder: "Verify" }, { class: "w-32" })], __VLS_functionalComponentArgsRest(__VLS_552), false));
        /** @type {__VLS_StyleScopedClasses['w-32']} */ ;
        // @ts-ignore
        [verificationStatusOptions,];
    };
    var __VLS_547, __VLS_548;
    for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.selectedReviewStore.documents)); _i < _a.length; _i++) {
        var doc = _a[_i][0];
        _loop_1(doc);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_556 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    textarea_1.default;
    // @ts-ignore
    var __VLS_557 = __VLS_asFunctionalComponent1(__VLS_556, new __VLS_556(__assign({ modelValue: (__VLS_ctx.reviewNotes), placeholder: "Enter review notes or comments...", rows: "3" }, { class: "w-full" })));
    var __VLS_558 = __VLS_557.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.reviewNotes), placeholder: "Enter review notes or comments...", rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_557), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
{
    var __VLS_561 = __VLS_541.slots.footer;
    var __VLS_562 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_563 = __VLS_asFunctionalComponent1(__VLS_562, new __VLS_562(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_564 = __VLS_563.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_563), false));
    var __VLS_567 = void 0;
    var __VLS_568 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showReviewDialog = false;
                // @ts-ignore
                [showReviewDialog, reviewNotes,];
            } });
    var __VLS_565;
    var __VLS_566;
    var __VLS_569 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_570 = __VLS_asFunctionalComponent1(__VLS_569, new __VLS_569(__assign({ 'onClick': {} }, { label: "Request More Info", icon: "pi pi-question-circle" })));
    var __VLS_571 = __VLS_570.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Request More Info", icon: "pi pi-question-circle" })], __VLS_functionalComponentArgsRest(__VLS_570), false));
    var __VLS_574 = void 0;
    var __VLS_575 = ({ click: {} },
        { onClick: (__VLS_ctx.requestMoreInfo) });
    var __VLS_572;
    var __VLS_573;
    var __VLS_576 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_577 = __VLS_asFunctionalComponent1(__VLS_576, new __VLS_576(__assign({ 'onClick': {} }, { label: "Reject Store", icon: "pi pi-times", severity: "danger" })));
    var __VLS_578 = __VLS_577.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject Store", icon: "pi pi-times", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_577), false));
    var __VLS_581 = void 0;
    var __VLS_582 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.rejectStore(__VLS_ctx.selectedReviewStore);
                // @ts-ignore
                [rejectStore, selectedReviewStore, requestMoreInfo,];
            } });
    var __VLS_579;
    var __VLS_580;
    var __VLS_583 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_584 = __VLS_asFunctionalComponent1(__VLS_583, new __VLS_583(__assign({ 'onClick': {} }, { label: "Approve Store", icon: "pi pi-check" })));
    var __VLS_585 = __VLS_584.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Approve Store", icon: "pi pi-check" })], __VLS_functionalComponentArgsRest(__VLS_584), false));
    var __VLS_588 = void 0;
    var __VLS_589 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.approveStore(__VLS_ctx.selectedReviewStore);
                // @ts-ignore
                [approveStore, selectedReviewStore,];
            } });
    var __VLS_586;
    var __VLS_587;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_541;
var __VLS_590;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_591 = __VLS_asFunctionalComponent1(__VLS_590, new __VLS_590(__assign({ visible: (__VLS_ctx.showRejectDialog), header: "Reject Store Application" }, { style: ({ width: '600px' }) })));
var __VLS_592 = __VLS_591.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showRejectDialog), header: "Reject Store Application" }, { style: ({ width: '600px' }) })], __VLS_functionalComponentArgsRest(__VLS_591), false));
var __VLS_595 = __VLS_593.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
if (__VLS_ctx.storeToReject) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    (__VLS_ctx.storeToReject.storeName);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_596;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_597 = __VLS_asFunctionalComponent1(__VLS_596, new __VLS_596(__assign({ modelValue: (__VLS_ctx.rejectionReason), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })));
var __VLS_598 = __VLS_597.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionReason), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_597), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_601;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_602 = __VLS_asFunctionalComponent1(__VLS_601, new __VLS_601(__assign({ modelValue: (__VLS_ctx.rejectionNotes), placeholder: "Provide additional details for rejection...", rows: "3" }, { class: "w-full" })));
var __VLS_603 = __VLS_602.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionNotes), placeholder: "Provide additional details for rejection...", rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_602), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center p-3 bg-yellow-50 rounded" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['p-3']} */ ;
/** @type {__VLS_StyleScopedClasses['bg-yellow-50']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-exclamation-triangle text-yellow-500 mr-3" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-exclamation-triangle']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-500']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-yellow-800" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-yellow-800']} */ ;
{
    var __VLS_606 = __VLS_593.slots.footer;
    var __VLS_607 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_608 = __VLS_asFunctionalComponent1(__VLS_607, new __VLS_607(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_609 = __VLS_608.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_608), false));
    var __VLS_612 = void 0;
    var __VLS_613 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showRejectDialog = false;
                // @ts-ignore
                [rejectionReasonOptions, showRejectDialog, showRejectDialog, storeToReject, storeToReject, rejectionReason, rejectionNotes,];
            } });
    var __VLS_610;
    var __VLS_611;
    var __VLS_614 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_615 = __VLS_asFunctionalComponent1(__VLS_614, new __VLS_614(__assign({ 'onClick': {} }, { label: "Confirm Reject", severity: "danger" })));
    var __VLS_616 = __VLS_615.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Confirm Reject", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_615), false));
    var __VLS_619 = void 0;
    var __VLS_620 = ({ click: {} },
        { onClick: (__VLS_ctx.confirmReject) });
    var __VLS_617;
    var __VLS_618;
    // @ts-ignore
    [confirmReject,];
}
// @ts-ignore
[];
var __VLS_593;
var __VLS_621;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_622 = __VLS_asFunctionalComponent1(__VLS_621, new __VLS_621(__assign({ visible: (__VLS_ctx.showBulkApproveDialog), header: "Bulk Approve Stores" }, { style: ({ width: '500px' }) })));
var __VLS_623 = __VLS_622.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showBulkApproveDialog), header: "Bulk Approve Stores" }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_622), false));
var __VLS_626 = __VLS_624.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
(__VLS_ctx.selectedStores.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-blue-50 p-4 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['bg-blue-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-blue-800" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-blue-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
{
    var __VLS_627 = __VLS_624.slots.footer;
    var __VLS_628 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_629 = __VLS_asFunctionalComponent1(__VLS_628, new __VLS_628(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_630 = __VLS_629.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_629), false));
    var __VLS_633 = void 0;
    var __VLS_634 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showBulkApproveDialog = false;
                // @ts-ignore
                [selectedStores, showBulkApproveDialog, showBulkApproveDialog,];
            } });
    var __VLS_631;
    var __VLS_632;
    var __VLS_635 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_636 = __VLS_asFunctionalComponent1(__VLS_635, new __VLS_635(__assign({ 'onClick': {} }, { label: "Approve All", severity: "success" })));
    var __VLS_637 = __VLS_636.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Approve All", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_636), false));
    var __VLS_640 = void 0;
    var __VLS_641 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkApprove) });
    var __VLS_638;
    var __VLS_639;
    // @ts-ignore
    [bulkApprove,];
}
// @ts-ignore
[];
var __VLS_624;
var __VLS_642;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_643 = __VLS_asFunctionalComponent1(__VLS_642, new __VLS_642(__assign({ visible: (__VLS_ctx.showBulkRejectDialog), header: "Bulk Reject Stores" }, { style: ({ width: '500px' }) })));
var __VLS_644 = __VLS_643.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showBulkRejectDialog), header: "Bulk Reject Stores" }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_643), false));
var __VLS_647 = __VLS_645.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
(__VLS_ctx.selectedStores.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_648;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_649 = __VLS_asFunctionalComponent1(__VLS_648, new __VLS_648(__assign({ modelValue: (__VLS_ctx.bulkRejectionReason), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })));
var __VLS_650 = __VLS_649.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkRejectionReason), options: (__VLS_ctx.rejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_649), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_653 = __VLS_645.slots.footer;
    var __VLS_654 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_655 = __VLS_asFunctionalComponent1(__VLS_654, new __VLS_654(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_656 = __VLS_655.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_655), false));
    var __VLS_659 = void 0;
    var __VLS_660 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showBulkRejectDialog = false;
                // @ts-ignore
                [selectedStores, showBulkRejectDialog, showBulkRejectDialog, rejectionReasonOptions, bulkRejectionReason,];
            } });
    var __VLS_657;
    var __VLS_658;
    var __VLS_661 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_662 = __VLS_asFunctionalComponent1(__VLS_661, new __VLS_661(__assign({ 'onClick': {} }, { label: "Reject All", severity: "danger" })));
    var __VLS_663 = __VLS_662.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject All", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_662), false));
    var __VLS_666 = void 0;
    var __VLS_667 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkReject) });
    var __VLS_664;
    var __VLS_665;
    // @ts-ignore
    [bulkReject,];
}
// @ts-ignore
[];
var __VLS_645;
var __VLS_668;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_669 = __VLS_asFunctionalComponent1(__VLS_668, new __VLS_668(__assign({ visible: (__VLS_ctx.showSettingsDialog), header: "Validation Settings" }, { style: ({ width: '700px' }) })));
var __VLS_670 = __VLS_669.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showSettingsDialog), header: "Validation Settings" }, { style: ({ width: '700px' }) })], __VLS_functionalComponentArgsRest(__VLS_669), false));
var __VLS_673 = __VLS_671.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-6" }));
/** @type {__VLS_StyleScopedClasses['space-y-6']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
var __VLS_674;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_675 = __VLS_asFunctionalComponent1(__VLS_674, new __VLS_674({
    modelValue: (__VLS_ctx.autoApprovalEnabled),
}));
var __VLS_676 = __VLS_675.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.autoApprovalEnabled),
    }], __VLS_functionalComponentArgsRest(__VLS_675), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_679;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_680 = __VLS_asFunctionalComponent1(__VLS_679, new __VLS_679(__assign({ modelValue: (__VLS_ctx.autoApprovalDelay), options: (__VLS_ctx.delayOptions), optionLabel: "name", placeholder: "Select delay" }, { class: "w-full" })));
var __VLS_681 = __VLS_680.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.autoApprovalDelay), options: (__VLS_ctx.delayOptions), optionLabel: "name", placeholder: "Select delay" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_680), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
var __VLS_684;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_685 = __VLS_asFunctionalComponent1(__VLS_684, new __VLS_684({
    modelValue: (__VLS_ctx.emailNotifications),
}));
var __VLS_686 = __VLS_685.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.emailNotifications),
    }], __VLS_functionalComponentArgsRest(__VLS_685), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
var __VLS_689;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_690 = __VLS_asFunctionalComponent1(__VLS_689, new __VLS_689({
    modelValue: (__VLS_ctx.smsNotifications),
}));
var __VLS_691 = __VLS_690.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.smsNotifications),
    }], __VLS_functionalComponentArgsRest(__VLS_690), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-3" }));
/** @type {__VLS_StyleScopedClasses['space-y-3']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_694;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_695 = __VLS_asFunctionalComponent1(__VLS_694, new __VLS_694(__assign({ modelValue: (__VLS_ctx.minDocuments), min: (1), max: (10) }, { class: "w-full" })));
var __VLS_696 = __VLS_695.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.minDocuments), min: (1), max: (10) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_695), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_699;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_700 = __VLS_asFunctionalComponent1(__VLS_699, new __VLS_699(__assign({ modelValue: (__VLS_ctx.maxReviewDays), min: (1), max: (30) }, { class: "w-full" })));
var __VLS_701 = __VLS_700.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.maxReviewDays), min: (1), max: (30) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_700), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_704 = __VLS_671.slots.footer;
    var __VLS_705 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_706 = __VLS_asFunctionalComponent1(__VLS_705, new __VLS_705(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_707 = __VLS_706.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_706), false));
    var __VLS_710 = void 0;
    var __VLS_711 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showSettingsDialog = false;
                // @ts-ignore
                [showSettingsDialog, showSettingsDialog, autoApprovalEnabled, autoApprovalDelay, delayOptions, emailNotifications, smsNotifications, minDocuments, maxReviewDays,];
            } });
    var __VLS_708;
    var __VLS_709;
    var __VLS_712 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_713 = __VLS_asFunctionalComponent1(__VLS_712, new __VLS_712(__assign({ 'onClick': {} }, { label: "Save Settings" })));
    var __VLS_714 = __VLS_713.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Settings" })], __VLS_functionalComponentArgsRest(__VLS_713), false));
    var __VLS_717 = void 0;
    var __VLS_718 = ({ click: {} },
        { onClick: (__VLS_ctx.saveSettings) });
    var __VLS_715;
    var __VLS_716;
    // @ts-ignore
    [saveSettings,];
}
// @ts-ignore
[];
var __VLS_671;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
