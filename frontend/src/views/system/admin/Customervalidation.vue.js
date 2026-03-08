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
var showBulkVerifyDialog = (0, vue_1.ref)(false);
var showBulkRejectDialog = (0, vue_1.ref)(false);
var showSettingsDialog = (0, vue_1.ref)(false);
var showPendingFilters = (0, vue_1.ref)(false);
var selectedCustomers = (0, vue_1.ref)([]);
var selectedReviewCustomer = (0, vue_1.ref)(null);
var customerToReject = (0, vue_1.ref)(null);
var reviewNotes = (0, vue_1.ref)('');
var rejectionReason = (0, vue_1.ref)(null);
var rejectionNotes = (0, vue_1.ref)('');
var bulkRejectionReason = (0, vue_1.ref)(null);
// Filters
var dateFilter = (0, vue_1.ref)(null);
var customerTypeFilter = (0, vue_1.ref)([]);
var verificationLevelFilter = (0, vue_1.ref)(null);
var documentStatusFilter = (0, vue_1.ref)([]);
var accountAgeFilter = (0, vue_1.ref)(null);
var verificationDateFilter = (0, vue_1.ref)(null);
var rejectionReasonFilter = (0, vue_1.ref)([]);
var statusFilter = (0, vue_1.ref)(null);
var riskFilter = (0, vue_1.ref)(null);
// Settings
var autoVerificationEnabled = (0, vue_1.ref)(false);
var verificationThreshold = (0, vue_1.ref)(null);
var requireIdVerification = (0, vue_1.ref)(true);
var requireAddressProof = (0, vue_1.ref)(true);
var maxReviewDays = (0, vue_1.ref)(7);
var highRiskThreshold = (0, vue_1.ref)(70);
var mediumRiskThreshold = (0, vue_1.ref)(40);
var emailNotifications = (0, vue_1.ref)(true);
// Customer Data
var pendingCustomers = (0, vue_1.ref)([
    {
        id: 1,
        customerId: 'CUST-2024-001',
        fullName: 'Juan Dela Cruz',
        email: 'juan@email.com',
        phone: '+639123456789',
        dateOfBirth: '1990-05-15',
        gender: 'Male',
        nationality: 'Filipino',
        address: '123 Main St, Manila',
        registrationDate: '2024-01-15',
        waitingTime: '2 days',
        verificationLevel: 'Basic',
        documentStatus: 'Complete',
        riskLevel: 'Low',
        riskScore: 25,
        customerType: 'Individual',
        documents: [
            { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Selfie Photo', type: 'photo', status: 'Pending', verificationStatus: 'pending' }
        ]
    },
    {
        id: 2,
        customerId: 'CUST-2024-002',
        fullName: 'Maria Santos',
        email: 'maria@email.com',
        phone: '+639234567890',
        dateOfBirth: '1985-08-22',
        gender: 'Female',
        nationality: 'Filipino',
        address: '456 Oak Ave, Quezon City',
        registrationDate: '2024-01-16',
        waitingTime: '1 day',
        verificationLevel: 'Enhanced',
        documentStatus: 'Incomplete',
        riskLevel: 'Medium',
        riskScore: 55,
        customerType: 'Business',
        documents: [
            { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Proof of Address', type: 'address', status: 'Missing', verificationStatus: 'missing' }
        ]
    },
    {
        id: 3,
        customerId: 'CUST-2024-003',
        fullName: 'Robert Lim',
        email: 'robert@email.com',
        phone: '+639345678901',
        dateOfBirth: '1978-12-10',
        gender: 'Male',
        nationality: 'Chinese',
        address: '789 Luxury Blvd, Makati',
        registrationDate: '2024-01-14',
        waitingTime: '3 days',
        verificationLevel: 'Full',
        documentStatus: 'Complete',
        riskLevel: 'High',
        riskScore: 85,
        customerType: 'VIP',
        documents: [
            { name: 'Government ID', type: 'id', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Income Proof', type: 'financial', status: 'Verified', verificationStatus: 'verified' }
        ]
    },
    {
        id: 4,
        customerId: 'CUST-2024-004',
        fullName: 'Sarah Chen',
        email: 'sarah@email.com',
        phone: '+639456789012',
        dateOfBirth: '1992-03-30',
        gender: 'Female',
        nationality: 'Chinese-Filipino',
        address: '101 Corporate St, Taguig',
        registrationDate: '2024-01-17',
        waitingTime: 'Just now',
        verificationLevel: 'Basic',
        documentStatus: 'Pending Review',
        riskLevel: 'Low',
        riskScore: 20,
        customerType: 'Individual',
        documents: [
            { name: 'Government ID', type: 'id', status: 'Pending', verificationStatus: 'pending' },
            { name: 'Proof of Address', type: 'address', status: 'Pending', verificationStatus: 'pending' }
        ]
    },
    {
        id: 5,
        customerId: 'CUST-2024-005',
        fullName: 'David Green',
        email: 'david@email.com',
        phone: '+639567890123',
        dateOfBirth: '1988-07-18',
        gender: 'Male',
        nationality: 'American',
        address: '202 Green St, Pasig',
        registrationDate: '2024-01-13',
        waitingTime: '4 days',
        verificationLevel: 'Enhanced',
        documentStatus: 'Complete',
        riskLevel: 'Medium',
        riskScore: 60,
        customerType: 'Business',
        documents: [
            { name: 'Passport', type: 'id', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Visa', type: 'id', status: 'Verified', verificationStatus: 'verified' },
            { name: 'Proof of Address', type: 'address', status: 'Verified', verificationStatus: 'verified' }
        ]
    }
]);
var verifiedCustomers = (0, vue_1.ref)([
    {
        id: 6,
        customerId: 'CUST-2023-101',
        fullName: 'James Wilson',
        email: 'james@email.com',
        phone: '+639678901234',
        customerType: 'VIP',
        address: '303 Heritage Rd, Cebu',
        registrationDate: '2023-12-10',
        verificationDate: '2023-12-15',
        verifiedBy: 'Admin 1',
        verificationLevel: 'Full',
        riskLevel: 'Low',
        riskScore: 15,
        status: 'Active',
        totalOrders: 45,
        totalSpent: 1250000
    },
    {
        id: 7,
        customerId: 'CUST-2023-102',
        fullName: 'Lisa Garcia',
        email: 'lisa@email.com',
        phone: '+639789012345',
        customerType: 'Individual',
        address: '404 Modern Ave, Davao',
        registrationDate: '2023-11-25',
        verificationDate: '2023-11-30',
        verifiedBy: 'Admin 2',
        verificationLevel: 'Enhanced',
        riskLevel: 'Medium',
        riskScore: 45,
        status: 'Active',
        totalOrders: 32,
        totalSpent: 980000
    },
    {
        id: 8,
        customerId: 'CUST-2023-103',
        fullName: 'Michael Tan',
        email: 'michael@email.com',
        phone: '+639890123456',
        customerType: 'Business',
        address: '505 Playground St, Iloilo',
        registrationDate: '2023-12-05',
        verificationDate: '2023-12-10',
        verifiedBy: 'Admin 1',
        verificationLevel: 'Full',
        riskLevel: 'Low',
        riskScore: 20,
        status: 'Active',
        totalOrders: 67,
        totalSpent: 750000
    },
    {
        id: 9,
        customerId: 'CUST-2024-006',
        fullName: 'Anna Lee',
        email: 'anna@email.com',
        phone: '+639901234567',
        customerType: 'Individual',
        address: '606 Garden St, Baguio',
        registrationDate: '2024-01-05',
        verificationDate: '2024-01-10',
        verifiedBy: 'Admin 3',
        verificationLevel: 'Basic',
        riskLevel: 'Medium',
        riskScore: 55,
        status: 'Active',
        totalOrders: 12,
        totalSpent: 560000
    },
    {
        id: 10,
        customerId: 'CUST-2024-007',
        fullName: 'Paul Rivera',
        email: 'paul@email.com',
        phone: '+639012345678',
        customerType: 'VIP',
        address: '707 Tech Blvd, Pasay',
        registrationDate: '2024-01-08',
        verificationDate: '2024-01-12',
        verifiedBy: 'Admin 2',
        verificationLevel: 'Full',
        riskLevel: 'Low',
        riskScore: 10,
        status: 'Active',
        totalOrders: 89,
        totalSpent: 420000
    }
]);
var rejectedCustomers = (0, vue_1.ref)([
    {
        id: 11,
        customerId: 'CUST-2023-201',
        fullName: 'John Doe',
        email: 'john@email.com',
        phone: '+639123987654',
        customerType: 'Individual',
        address: '808 Fast St, Mandaluyong',
        registrationDate: '2023-11-20',
        rejectionDate: '2023-11-25',
        rejectedBy: 'Admin 1',
        status: 'Rejected',
        rejectionReason: 'Fake Documents',
        riskLevel: 'High',
        riskScore: 90,
        notes: 'Submitted forged identification documents'
    },
    {
        id: 12,
        customerId: 'CUST-2023-202',
        fullName: 'Jane Smith',
        email: 'jane@email.com',
        phone: '+639234876543',
        customerType: 'Business',
        address: '909 Budget Rd, Paranaque',
        registrationDate: '2023-12-01',
        rejectionDate: '2023-12-05',
        rejectedBy: 'Admin 2',
        status: 'Rejected',
        rejectionReason: 'Suspicious Activity',
        riskLevel: 'High',
        riskScore: 85,
        notes: 'Multiple failed verification attempts'
    },
    {
        id: 13,
        customerId: 'CUST-2024-008',
        fullName: 'Carlos Reyes',
        email: 'carlos@email.com',
        phone: '+639345765432',
        customerType: 'Individual',
        address: '1010 Sleep St, Alabang',
        registrationDate: '2024-01-10',
        rejectionDate: '2024-01-14',
        rejectedBy: 'Admin 3',
        status: 'Rejected',
        rejectionReason: 'Incomplete Information',
        riskLevel: 'Medium',
        riskScore: 60,
        notes: 'Missing required personal information'
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
var customerTypeOptions = (0, vue_1.ref)([
    { name: 'Individual', value: 'individual' },
    { name: 'Business', value: 'business' },
    { name: 'VIP', value: 'vip' },
    { name: 'Corporate', value: 'corporate' },
    { name: 'Reseller', value: 'reseller' }
]);
var verificationLevelOptions = (0, vue_1.ref)([
    { name: 'Basic', value: 'basic' },
    { name: 'Enhanced', value: 'enhanced' },
    { name: 'Full', value: 'full' },
    { name: 'Premium', value: 'premium' }
]);
var customerDocStatusOptions = (0, vue_1.ref)([
    { name: 'Complete', value: 'complete' },
    { name: 'Incomplete', value: 'incomplete' },
    { name: 'Pending Review', value: 'pending' },
    { name: 'Missing Documents', value: 'missing' }
]);
var accountAgeOptions = (0, vue_1.ref)([
    { name: 'New (0-7 days)', value: 'new' },
    { name: 'Recent (8-30 days)', value: 'recent' },
    { name: 'Established (1-6 months)', value: 'established' },
    { name: 'Long-term (6+ months)', value: 'long-term' }
]);
var verificationDateOptions = (0, vue_1.ref)([
    { name: 'Today', value: 'today' },
    { name: 'This week', value: 'week' },
    { name: 'This month', value: 'month' },
    { name: 'Last month', value: 'last-month' },
    { name: 'All time', value: 'all' }
]);
var customerRejectionReasonOptions = (0, vue_1.ref)([
    { name: 'Fake Documents', value: 'fake-docs' },
    { name: 'Incomplete Information', value: 'incomplete-info' },
    { name: 'Suspicious Activity', value: 'suspicious' },
    { name: 'High Risk Profile', value: 'high-risk' },
    { name: 'Duplicate Account', value: 'duplicate' },
    { name: 'Policy Violation', value: 'policy' },
    { name: 'Other', value: 'other' }
]);
var customerStatusOptions = (0, vue_1.ref)([
    { name: 'Pending', value: 'pending' },
    { name: 'Verified', value: 'verified' },
    { name: 'Rejected', value: 'rejected' },
    { name: 'Suspended', value: 'suspended' },
    { name: 'Active', value: 'active' }
]);
var riskLevelOptions = (0, vue_1.ref)([
    { name: 'Low', value: 'low' },
    { name: 'Medium', value: 'medium' },
    { name: 'High', value: 'high' }
]);
var verificationStatusOptions = (0, vue_1.ref)([
    { name: 'Verified', value: 'verified' },
    { name: 'Pending', value: 'pending' },
    { name: 'Missing', value: 'missing' },
    { name: 'Invalid', value: 'invalid' }
]);
var thresholdOptions = (0, vue_1.ref)([
    { name: 'Low (0-30)', value: 'low' },
    { name: 'Medium (31-60)', value: 'medium' },
    { name: 'High (61-100)', value: 'high' }
]);
// Computed Properties
var filteredPendingCustomers = (0, vue_1.computed)(function () {
    var filtered = pendingCustomers.value;
    if (searchTerm.value && activeView.value === 'pending') {
        var term_1 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (customer) {
            return customer.fullName.toLowerCase().includes(term_1) ||
                customer.email.toLowerCase().includes(term_1) ||
                customer.customerId.toLowerCase().includes(term_1);
        });
    }
    if (verificationLevelFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.verificationLevel === verificationLevelFilter.value.name; });
    }
    if (documentStatusFilter.value.length > 0) {
        var statuses_1 = documentStatusFilter.value.map(function (s) { return s.value; });
        filtered = filtered.filter(function (customer) { return statuses_1.includes(customer.documentStatus.toLowerCase().replace(/ /g, '-')); });
    }
    if (riskFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.riskLevel === riskFilter.value.name; });
    }
    return filtered;
});
var filteredVerifiedCustomers = (0, vue_1.computed)(function () {
    var filtered = verifiedCustomers.value;
    if (searchTerm.value && activeView.value === 'verified') {
        var term_2 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (customer) {
            return customer.fullName.toLowerCase().includes(term_2) ||
                customer.email.toLowerCase().includes(term_2) ||
                customer.customerId.toLowerCase().includes(term_2);
        });
    }
    if (verificationDateFilter.value) {
        // Implement verification date filtering logic
    }
    if (riskFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.riskLevel === riskFilter.value.name; });
    }
    return filtered;
});
var filteredRejectedCustomers = (0, vue_1.computed)(function () {
    var filtered = rejectedCustomers.value;
    if (searchTerm.value && activeView.value === 'rejected') {
        var term_3 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (customer) {
            return customer.fullName.toLowerCase().includes(term_3) ||
                customer.email.toLowerCase().includes(term_3) ||
                customer.customerId.toLowerCase().includes(term_3);
        });
    }
    if (rejectionReasonFilter.value.length > 0) {
        var reasons_1 = rejectionReasonFilter.value.map(function (r) { return r.value; });
        filtered = filtered.filter(function (customer) { return reasons_1.includes(customer.rejectionReason.toLowerCase().replace(/ /g, '-')); });
    }
    if (riskFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.riskLevel === riskFilter.value.name; });
    }
    return filtered;
});
var filteredAllCustomers = (0, vue_1.computed)(function () {
    var allCustomers = __spreadArray(__spreadArray(__spreadArray([], pendingCustomers.value, true), verifiedCustomers.value, true), rejectedCustomers.value, true);
    var filtered = allCustomers;
    if (searchTerm.value && activeView.value === 'all') {
        var term_4 = searchTerm.value.toLowerCase();
        filtered = filtered.filter(function (customer) {
            return customer.fullName.toLowerCase().includes(term_4) ||
                customer.email.toLowerCase().includes(term_4) ||
                customer.customerId.toLowerCase().includes(term_4);
        });
    }
    if (statusFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.status === statusFilter.value.name; });
    }
    if (riskFilter.value) {
        filtered = filtered.filter(function (customer) { return customer.riskLevel === riskFilter.value.name; });
    }
    return filtered;
});
var verifiedTodayCount = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return verifiedCustomers.value.filter(function (customer) { return customer.verificationDate === today; }).length;
});
var rejectedTodayCount = (0, vue_1.computed)(function () {
    var today = new Date().toISOString().split('T')[0];
    return rejectedCustomers.value.filter(function (customer) { return customer.rejectionDate === today; }).length;
});
var totalCustomers = (0, vue_1.computed)(function () {
    return pendingCustomers.value.length + verifiedCustomers.value.length + rejectedCustomers.value.length;
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
var getCustomerStatusSeverity = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'warning';
        case 'verified':
        case 'active': return 'success';
        case 'rejected': return 'danger';
        case 'suspended': return 'secondary';
        default: return 'info';
    }
};
var getCustomerStatusIcon = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'pi-user-clock';
        case 'verified':
        case 'active': return 'pi-user-check';
        case 'rejected': return 'pi-user-times';
        case 'suspended': return 'pi-user-minus';
        default: return 'pi-user';
    }
};
var getCustomerStatusColor = function (status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'bg-yellow-100 text-yellow-600';
        case 'verified':
        case 'active': return 'bg-green-100 text-green-600';
        case 'rejected': return 'bg-red-100 text-red-600';
        case 'suspended': return 'bg-gray-100 text-gray-600';
        default: return 'bg-blue-100 text-blue-600';
    }
};
var getVerificationLevelSeverity = function (level) {
    switch (level.toLowerCase()) {
        case 'basic': return 'info';
        case 'enhanced': return 'warning';
        case 'full': return 'success';
        case 'premium': return 'help';
        default: return 'secondary';
    }
};
var getRiskLevelSeverity = function (level) {
    switch (level.toLowerCase()) {
        case 'low': return 'success';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        default: return 'info';
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
var getDocumentTypeIcon = function (type) {
    switch (type) {
        case 'id': return 'pi-id-card';
        case 'address': return 'pi-home';
        case 'photo': return 'pi-camera';
        case 'financial': return 'pi-credit-card';
        default: return 'pi-file';
    }
};
var getDocumentTypeColor = function (type) {
    switch (type) {
        case 'id': return 'text-blue-500';
        case 'address': return 'text-green-500';
        case 'photo': return 'text-purple-500';
        case 'financial': return 'text-orange-500';
        default: return 'text-gray-500';
    }
};
// Action Functions
var setActiveView = function (view) {
    activeView.value = view;
    selectedCustomers.value = [];
    searchTerm.value = '';
};
var togglePendingFilters = function () {
    showPendingFilters.value = !showPendingFilters.value;
};
var reviewCustomer = function (customer) {
    selectedReviewCustomer.value = customer;
    showReviewDialog.value = true;
};
var verifyCustomer = function (customer) {
    if (!customer)
        return;
    // Move from pending to verified
    var pendingIndex = pendingCustomers.value.findIndex(function (c) { return c.id === customer.id; });
    if (pendingIndex !== -1) {
        var verifiedCustomer = __assign({}, pendingCustomers.value[pendingIndex]);
        verifiedCustomer.verificationDate = new Date().toISOString().split('T')[0];
        verifiedCustomer.verifiedBy = 'Current Admin';
        verifiedCustomer.status = 'Active';
        verifiedCustomer.totalOrders = 0;
        verifiedCustomer.totalSpent = 0;
        pendingCustomers.value.splice(pendingIndex, 1);
        verifiedCustomers.value.unshift(verifiedCustomer);
    }
    showReviewDialog.value = false;
};
var rejectCustomer = function (customer) {
    customerToReject.value = customer;
    showRejectDialog.value = true;
};
var confirmReject = function () {
    var _a;
    if (!customerToReject.value)
        return;
    var pendingIndex = pendingCustomers.value.findIndex(function (c) { return c.id === customerToReject.value.id; });
    if (pendingIndex !== -1) {
        var rejectedCustomer = __assign({}, pendingCustomers.value[pendingIndex]);
        rejectedCustomer.rejectionDate = new Date().toISOString().split('T')[0];
        rejectedCustomer.rejectedBy = 'Current Admin';
        rejectedCustomer.status = 'Rejected';
        rejectedCustomer.rejectionReason = ((_a = rejectionReason.value) === null || _a === void 0 ? void 0 : _a.name) || 'Other';
        rejectedCustomer.notes = rejectionNotes.value;
        pendingCustomers.value.splice(pendingIndex, 1);
        rejectedCustomers.value.unshift(rejectedCustomer);
    }
    showRejectDialog.value = false;
    rejectionReason.value = null;
    rejectionNotes.value = '';
    customerToReject.value = null;
};
var viewCustomer = function (customer) {
    console.log('View customer:', customer);
    // Navigate to customer details page
};
var suspendCustomer = function (customer) {
    console.log('Suspend customer:', customer);
    // Implement suspension logic
};
var reverifyCustomer = function (customer) {
    console.log('Re-verify customer:', customer);
    // Move from verified to pending for re-verification
};
var viewRejectedCustomer = function (customer) {
    console.log('View rejected customer:', customer);
};
var rereviewCustomer = function (customer) {
    // Move from rejected to pending
    var rejectedIndex = rejectedCustomers.value.findIndex(function (c) { return c.id === customer.id; });
    if (rejectedIndex !== -1) {
        var pendingCustomer = __assign({}, rejectedCustomers.value[rejectedIndex]);
        delete pendingCustomer.rejectionDate;
        delete pendingCustomer.rejectedBy;
        delete pendingCustomer.rejectionReason;
        delete pendingCustomer.notes;
        pendingCustomer.status = 'Pending';
        pendingCustomer.documentStatus = 'Pending Review';
        rejectedCustomers.value.splice(rejectedIndex, 1);
        pendingCustomers.value.push(pendingCustomer);
    }
};
var viewDocument = function (doc) {
    console.log('View document:', doc);
    // Open document viewer
};
var requestMoreInfo = function () {
    console.log('Request more info for customer:', selectedReviewCustomer.value);
    // Implement request more info logic
};
var requestDocuments = function () {
    console.log('Request documents from selected customers:', selectedCustomers.value);
    // Implement document request logic
};
var bulkVerify = function () {
    selectedCustomers.value.forEach(function (customer) {
        verifyCustomer(customer);
    });
    selectedCustomers.value = [];
    showBulkVerifyDialog.value = false;
};
var bulkReject = function () {
    selectedCustomers.value.forEach(function (customer) {
        customerToReject.value = customer;
        confirmReject();
    });
    selectedCustomers.value = [];
    showBulkRejectDialog.value = false;
};
var exportReport = function () {
    console.log('Exporting customer validation report');
    // Implement export logic
};
var saveSettings = function () {
    console.log('Saving customer validation settings');
    showSettingsDialog.value = false;
};
(0, vue_1.onMounted)(function () {
    console.log('Customer Validation Management loaded');
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-clock mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-user-clock']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.pendingCustomers.length > 0) {
    var __VLS_15 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_16 = __VLS_asFunctionalComponent1(__VLS_15, new __VLS_15(__assign({ value: (__VLS_ctx.pendingCustomers.length), severity: "warning" }, { class: "ml-2" })));
    var __VLS_17 = __VLS_16.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.pendingCustomers.length), severity: "warning" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_16), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[pendingCustomers, pendingCustomers,];
var __VLS_10;
var __VLS_11;
var __VLS_20;
/** @ts-ignore @type {typeof __VLS_components.Button | typeof __VLS_components.Button} */
button_1.default;
// @ts-ignore
var __VLS_21 = __VLS_asFunctionalComponent1(__VLS_20, new __VLS_20(__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'verified' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'verified') })));
var __VLS_22 = __VLS_21.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { severity: (__VLS_ctx.activeView === 'verified' ? 'primary' : 'secondary'), outlined: (__VLS_ctx.activeView !== 'verified') })], __VLS_functionalComponentArgsRest(__VLS_21), false));
var __VLS_25;
var __VLS_26 = ({ click: {} },
    { onClick: function () {
            var _a = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                _a[_i] = arguments[_i];
            }
            var $event = _a[0];
            __VLS_ctx.setActiveView('verified');
            // @ts-ignore
            [activeView, activeView, setActiveView,];
        } });
var __VLS_27 = __VLS_23.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-check mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-user-check']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.verifiedCustomers.length > 0) {
    var __VLS_28 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_29 = __VLS_asFunctionalComponent1(__VLS_28, new __VLS_28(__assign({ value: (__VLS_ctx.verifiedCustomers.length), severity: "success" }, { class: "ml-2" })));
    var __VLS_30 = __VLS_29.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.verifiedCustomers.length), severity: "success" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_29), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[verifiedCustomers, verifiedCustomers,];
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-times mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-user-times']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
if (__VLS_ctx.rejectedCustomers.length > 0) {
    var __VLS_41 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Badge} */
    badge_1.default;
    // @ts-ignore
    var __VLS_42 = __VLS_asFunctionalComponent1(__VLS_41, new __VLS_41(__assign({ value: (__VLS_ctx.rejectedCustomers.length), severity: "danger" }, { class: "ml-2" })));
    var __VLS_43 = __VLS_42.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.rejectedCustomers.length), severity: "danger" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_42), false));
    /** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
}
// @ts-ignore
[rejectedCustomers, rejectedCustomers,];
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
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-users mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-users']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
var __VLS_54;
/** @ts-ignore @type {typeof __VLS_components.Badge} */
badge_1.default;
// @ts-ignore
var __VLS_55 = __VLS_asFunctionalComponent1(__VLS_54, new __VLS_54(__assign({ value: (__VLS_ctx.totalCustomers), severity: "info" }, { class: "ml-2" })));
var __VLS_56 = __VLS_55.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.totalCustomers), severity: "info" }, { class: "ml-2" })], __VLS_functionalComponentArgsRest(__VLS_55), false));
/** @type {__VLS_StyleScopedClasses['ml-2']} */ ;
// @ts-ignore
[totalCustomers,];
var __VLS_49;
var __VLS_50;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
/** @type {__VLS_StyleScopedClasses['flex']} */ ;
/** @type {__VLS_StyleScopedClasses['items-center']} */ ;
/** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "text-sm text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
var __VLS_59;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_60 = __VLS_asFunctionalComponent1(__VLS_59, new __VLS_59(__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "All levels" }, { class: "w-40" })));
var __VLS_61 = __VLS_60.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "All levels" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_60), false));
/** @type {__VLS_StyleScopedClasses['w-40']} */ ;
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
    (__VLS_ctx.filteredPendingCustomers.length);
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
    var __VLS_64 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_65 = __VLS_asFunctionalComponent1(__VLS_64, new __VLS_64(__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "success", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })));
    var __VLS_66 = __VLS_65.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", severity: "success", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_65), false));
    var __VLS_69 = void 0;
    var __VLS_70 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                if (!(__VLS_ctx.activeView === 'pending'))
                    return;
                __VLS_ctx.showBulkVerifyDialog = true;
                // @ts-ignore
                [activeView, riskFilter, riskLevelOptions, filteredPendingCustomers, selectedCustomers, showBulkVerifyDialog,];
            } });
    var __VLS_67;
    var __VLS_68;
    var __VLS_71 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_72 = __VLS_asFunctionalComponent1(__VLS_71, new __VLS_71(__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })));
    var __VLS_73 = __VLS_72.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", severity: "danger", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_72), false));
    var __VLS_76 = void 0;
    var __VLS_77 = ({ click: {} },
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
                [selectedCustomers, showBulkRejectDialog,];
            } });
    var __VLS_74;
    var __VLS_75;
    var __VLS_78 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_79 = __VLS_asFunctionalComponent1(__VLS_78, new __VLS_78(__assign({ 'onClick': {} }, { icon: "pi pi-id-card", severity: "help", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })));
    var __VLS_80 = __VLS_79.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-id-card", severity: "help", outlined: true, size: "small", disabled: (__VLS_ctx.selectedCustomers.length === 0) })], __VLS_functionalComponentArgsRest(__VLS_79), false));
    var __VLS_83 = void 0;
    var __VLS_84 = ({ click: {} },
        { onClick: (__VLS_ctx.requestDocuments) });
    var __VLS_81;
    var __VLS_82;
    var __VLS_85 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_86 = __VLS_asFunctionalComponent1(__VLS_85, new __VLS_85(__assign({ modelValue: (__VLS_ctx.verificationLevelFilter), options: (__VLS_ctx.verificationLevelOptions), optionLabel: "name", placeholder: "Verification Level" }, { class: "w-48" })));
    var __VLS_87 = __VLS_86.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.verificationLevelFilter), options: (__VLS_ctx.verificationLevelOptions), optionLabel: "name", placeholder: "Verification Level" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_86), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_90 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_91 = __VLS_asFunctionalComponent1(__VLS_90, new __VLS_90(__assign({ modelValue: (__VLS_ctx.documentStatusFilter), options: (__VLS_ctx.customerDocStatusOptions), optionLabel: "name", placeholder: "Document Status", display: "chip" }, { class: "w-48" })));
    var __VLS_92 = __VLS_91.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.documentStatusFilter), options: (__VLS_ctx.customerDocStatusOptions), optionLabel: "name", placeholder: "Document Status", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_91), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_95 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_96 = __VLS_asFunctionalComponent1(__VLS_95, new __VLS_95(__assign({ 'onClick': {} }, { icon: "pi pi-filter", severity: "secondary" })));
    var __VLS_97 = __VLS_96.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-filter", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_96), false));
    var __VLS_100 = void 0;
    var __VLS_101 = ({ click: {} },
        { onClick: (__VLS_ctx.togglePendingFilters) });
    var __VLS_98;
    var __VLS_99;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_102 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_103 = __VLS_asFunctionalComponent1(__VLS_102, new __VLS_102({}));
    var __VLS_104 = __VLS_103.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_103), false));
    var __VLS_107 = __VLS_105.slots.default;
    var __VLS_108 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_109 = __VLS_asFunctionalComponent1(__VLS_108, new __VLS_108({}));
    var __VLS_110 = __VLS_109.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_109), false));
    var __VLS_113 = __VLS_111.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [selectedCustomers, requestDocuments, verificationLevelFilter, verificationLevelOptions, documentStatusFilter, customerDocStatusOptions, togglePendingFilters,];
    var __VLS_111;
    var __VLS_114 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_115 = __VLS_asFunctionalComponent1(__VLS_114, new __VLS_114(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search customers..." }, { class: "w-full" })));
    var __VLS_116 = __VLS_115.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search customers..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_115), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_105;
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
        var __VLS_119 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
        multiselect_1.default;
        // @ts-ignore
        var __VLS_120 = __VLS_asFunctionalComponent1(__VLS_119, new __VLS_119(__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "All types", display: "chip" }, { class: "w-full" })));
        var __VLS_121 = __VLS_120.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "All types", display: "chip" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_120), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_124 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_125 = __VLS_asFunctionalComponent1(__VLS_124, new __VLS_124(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "All time" }, { class: "w-full" })));
        var __VLS_126 = __VLS_125.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "All time" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_125), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
        /** @type {__VLS_StyleScopedClasses['block']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
        /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
        var __VLS_129 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_130 = __VLS_asFunctionalComponent1(__VLS_129, new __VLS_129(__assign({ modelValue: (__VLS_ctx.accountAgeFilter), options: (__VLS_ctx.accountAgeOptions), optionLabel: "name", placeholder: "Any age" }, { class: "w-full" })));
        var __VLS_131 = __VLS_130.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.accountAgeFilter), options: (__VLS_ctx.accountAgeOptions), optionLabel: "name", placeholder: "Any age" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_130), false));
        /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    }
    var __VLS_134 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_135 = __VLS_asFunctionalComponent1(__VLS_134, new __VLS_134({
        value: (__VLS_ctx.filteredPendingCustomers),
        selection: (__VLS_ctx.selectedCustomers),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_136 = __VLS_135.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredPendingCustomers),
            selection: (__VLS_ctx.selectedCustomers),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_135), false));
    var __VLS_139 = __VLS_137.slots.default;
    var __VLS_140 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_141 = __VLS_asFunctionalComponent1(__VLS_140, new __VLS_140({
        selectionMode: "multiple",
        headerStyle: "width: 3rem",
    }));
    var __VLS_142 = __VLS_141.apply(void 0, __spreadArray([{
            selectionMode: "multiple",
            headerStyle: "width: 3rem",
        }], __VLS_functionalComponentArgsRest(__VLS_141), false));
    var __VLS_145 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_146 = __VLS_asFunctionalComponent1(__VLS_145, new __VLS_145(__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })));
    var __VLS_147 = __VLS_146.apply(void 0, __spreadArray([__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_146), false));
    var __VLS_150 = __VLS_148.slots.default;
    {
        var __VLS_151 = __VLS_148.slots.body;
        var slotProps = __VLS_vSlot(__VLS_151)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-blue-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user text-blue-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-user']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-blue-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.fullName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.customerId);
        // @ts-ignore
        [filteredPendingCustomers, selectedCustomers, showPendingFilters, customerTypeFilter, customerTypeOptions, dateFilter, dateFilterOptions, accountAgeFilter, accountAgeOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_148;
    var __VLS_152 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_153 = __VLS_asFunctionalComponent1(__VLS_152, new __VLS_152(__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })));
    var __VLS_154 = __VLS_153.apply(void 0, __spreadArray([__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_153), false));
    var __VLS_157 = __VLS_155.slots.default;
    {
        var __VLS_158 = __VLS_155.slots.body;
        var slotProps = __VLS_vSlot(__VLS_158)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.email);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.phone);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_155;
    var __VLS_159 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_160 = __VLS_asFunctionalComponent1(__VLS_159, new __VLS_159(__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })));
    var __VLS_161 = __VLS_160.apply(void 0, __spreadArray([__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_160), false));
    var __VLS_164 = __VLS_162.slots.default;
    {
        var __VLS_165 = __VLS_162.slots.body;
        var slotProps = __VLS_vSlot(__VLS_165)[0];
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
    var __VLS_162;
    var __VLS_166 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_167 = __VLS_asFunctionalComponent1(__VLS_166, new __VLS_166(__assign({ field: "verificationLevel", header: "Level", sortable: true }, { style: {} })));
    var __VLS_168 = __VLS_167.apply(void 0, __spreadArray([__assign({ field: "verificationLevel", header: "Level", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_167), false));
    var __VLS_171 = __VLS_169.slots.default;
    {
        var __VLS_172 = __VLS_169.slots.body;
        var slotProps = __VLS_vSlot(__VLS_172)[0];
        var __VLS_173 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_174 = __VLS_asFunctionalComponent1(__VLS_173, new __VLS_173({
            value: (slotProps.data.verificationLevel),
            severity: (__VLS_ctx.getVerificationLevelSeverity(slotProps.data.verificationLevel)),
            rounded: true,
        }));
        var __VLS_175 = __VLS_174.apply(void 0, __spreadArray([{
                value: (slotProps.data.verificationLevel),
                severity: (__VLS_ctx.getVerificationLevelSeverity(slotProps.data.verificationLevel)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_174), false));
        // @ts-ignore
        [getVerificationLevelSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_169;
    var __VLS_178 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_179 = __VLS_asFunctionalComponent1(__VLS_178, new __VLS_178(__assign({ field: "documentStatus", header: "Documents", sortable: true }, { style: {} })));
    var __VLS_180 = __VLS_179.apply(void 0, __spreadArray([__assign({ field: "documentStatus", header: "Documents", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_179), false));
    var __VLS_183 = __VLS_181.slots.default;
    {
        var __VLS_184 = __VLS_181.slots.body;
        var slotProps = __VLS_vSlot(__VLS_184)[0];
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
    var __VLS_181;
    var __VLS_185 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_186 = __VLS_asFunctionalComponent1(__VLS_185, new __VLS_185(__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })));
    var __VLS_187 = __VLS_186.apply(void 0, __spreadArray([__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_186), false));
    var __VLS_190 = __VLS_188.slots.default;
    {
        var __VLS_191 = __VLS_188.slots.body;
        var slotProps = __VLS_vSlot(__VLS_191)[0];
        var __VLS_192 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_193 = __VLS_asFunctionalComponent1(__VLS_192, new __VLS_192({
            value: (slotProps.data.riskLevel),
            severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
            rounded: true,
        }));
        var __VLS_194 = __VLS_193.apply(void 0, __spreadArray([{
                value: (slotProps.data.riskLevel),
                severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_193), false));
        // @ts-ignore
        [getRiskLevelSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_188;
    var __VLS_197 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_198 = __VLS_asFunctionalComponent1(__VLS_197, new __VLS_197(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_199 = __VLS_198.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_198), false));
    var __VLS_202 = __VLS_200.slots.default;
    {
        var __VLS_203 = __VLS_200.slots.body;
        var slotProps_1 = __VLS_vSlot(__VLS_203)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_204 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_205 = __VLS_asFunctionalComponent1(__VLS_204, new __VLS_204(__assign({ 'onClick': {} }, { label: "Review", size: "small", icon: "pi pi-eye" })));
        var __VLS_206 = __VLS_205.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Review", size: "small", icon: "pi pi-eye" })], __VLS_functionalComponentArgsRest(__VLS_205), false));
        var __VLS_209 = void 0;
        var __VLS_210 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.reviewCustomer(slotProps_1.data);
                    // @ts-ignore
                    [reviewCustomer,];
                } });
        var __VLS_207;
        var __VLS_208;
        var __VLS_211 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_212 = __VLS_asFunctionalComponent1(__VLS_211, new __VLS_211(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })));
        var __VLS_213 = __VLS_212.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_212), false));
        var __VLS_216 = void 0;
        var __VLS_217 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.verifyCustomer(slotProps_1.data);
                    // @ts-ignore
                    [verifyCustomer,];
                } });
        var __VLS_214;
        var __VLS_215;
        var __VLS_218 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_219 = __VLS_asFunctionalComponent1(__VLS_218, new __VLS_218(__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })));
        var __VLS_220 = __VLS_219.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-times", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_219), false));
        var __VLS_223 = void 0;
        var __VLS_224 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'pending'))
                        return;
                    __VLS_ctx.rejectCustomer(slotProps_1.data);
                    // @ts-ignore
                    [rejectCustomer,];
                } });
        var __VLS_221;
        var __VLS_222;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_200;
    // @ts-ignore
    [];
    var __VLS_137;
}
if (__VLS_ctx.activeView === 'verified') {
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
    (__VLS_ctx.filteredVerifiedCustomers.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_225 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_226 = __VLS_asFunctionalComponent1(__VLS_225, new __VLS_225({}));
    var __VLS_227 = __VLS_226.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_226), false));
    var __VLS_230 = __VLS_228.slots.default;
    var __VLS_231 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_232 = __VLS_asFunctionalComponent1(__VLS_231, new __VLS_231({}));
    var __VLS_233 = __VLS_232.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_232), false));
    var __VLS_236 = __VLS_234.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredVerifiedCustomers,];
    var __VLS_234;
    var __VLS_237 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_238 = __VLS_asFunctionalComponent1(__VLS_237, new __VLS_237(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search verified customers..." }, { class: "w-full" })));
    var __VLS_239 = __VLS_238.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search verified customers..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_238), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_228;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_242 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_243 = __VLS_asFunctionalComponent1(__VLS_242, new __VLS_242(__assign({ modelValue: (__VLS_ctx.verificationDateFilter), options: (__VLS_ctx.verificationDateOptions), optionLabel: "name", placeholder: "Verification Date" }, { class: "w-48" })));
    var __VLS_244 = __VLS_243.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.verificationDateFilter), options: (__VLS_ctx.verificationDateOptions), optionLabel: "name", placeholder: "Verification Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_243), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_247 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_248 = __VLS_asFunctionalComponent1(__VLS_247, new __VLS_247(__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })));
    var __VLS_249 = __VLS_248.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_248), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_252 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_253 = __VLS_asFunctionalComponent1(__VLS_252, new __VLS_252(__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "Risk Level" }, { class: "w-40" })));
    var __VLS_254 = __VLS_253.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "Risk Level" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_253), false));
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_257 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_258 = __VLS_asFunctionalComponent1(__VLS_257, new __VLS_257({
        value: (__VLS_ctx.filteredVerifiedCustomers),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_259 = __VLS_258.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredVerifiedCustomers),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_258), false));
    var __VLS_262 = __VLS_260.slots.default;
    var __VLS_263 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_264 = __VLS_asFunctionalComponent1(__VLS_263, new __VLS_263(__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })));
    var __VLS_265 = __VLS_264.apply(void 0, __spreadArray([__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_264), false));
    var __VLS_268 = __VLS_266.slots.default;
    {
        var __VLS_269 = __VLS_266.slots.body;
        var slotProps = __VLS_vSlot(__VLS_269)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-green-100 rounded-full flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-green-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-check text-green-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-user-check']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-green-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.fullName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.customerId);
        // @ts-ignore
        [riskFilter, riskLevelOptions, customerTypeFilter, customerTypeOptions, filteredVerifiedCustomers, verificationDateFilter, verificationDateOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_266;
    var __VLS_270 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_271 = __VLS_asFunctionalComponent1(__VLS_270, new __VLS_270(__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })));
    var __VLS_272 = __VLS_271.apply(void 0, __spreadArray([__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_271), false));
    var __VLS_275 = __VLS_273.slots.default;
    {
        var __VLS_276 = __VLS_273.slots.body;
        var slotProps = __VLS_vSlot(__VLS_276)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.email);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.phone);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_273;
    var __VLS_277 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_278 = __VLS_asFunctionalComponent1(__VLS_277, new __VLS_277(__assign({ field: "verificationDate", header: "Verified On", sortable: true }, { style: {} })));
    var __VLS_279 = __VLS_278.apply(void 0, __spreadArray([__assign({ field: "verificationDate", header: "Verified On", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_278), false));
    var __VLS_282 = __VLS_280.slots.default;
    {
        var __VLS_283 = __VLS_280.slots.body;
        var slotProps = __VLS_vSlot(__VLS_283)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.verificationDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.verifiedBy);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_280;
    var __VLS_284 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_285 = __VLS_asFunctionalComponent1(__VLS_284, new __VLS_284(__assign({ field: "verificationLevel", header: "Level", sortable: true }, { style: {} })));
    var __VLS_286 = __VLS_285.apply(void 0, __spreadArray([__assign({ field: "verificationLevel", header: "Level", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_285), false));
    var __VLS_289 = __VLS_287.slots.default;
    {
        var __VLS_290 = __VLS_287.slots.body;
        var slotProps = __VLS_vSlot(__VLS_290)[0];
        var __VLS_291 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_292 = __VLS_asFunctionalComponent1(__VLS_291, new __VLS_291({
            value: (slotProps.data.verificationLevel),
            severity: "success",
            rounded: true,
        }));
        var __VLS_293 = __VLS_292.apply(void 0, __spreadArray([{
                value: (slotProps.data.verificationLevel),
                severity: "success",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_292), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_287;
    var __VLS_296 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_297 = __VLS_asFunctionalComponent1(__VLS_296, new __VLS_296(__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })));
    var __VLS_298 = __VLS_297.apply(void 0, __spreadArray([__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_297), false));
    var __VLS_301 = __VLS_299.slots.default;
    {
        var __VLS_302 = __VLS_299.slots.body;
        var slotProps = __VLS_vSlot(__VLS_302)[0];
        var __VLS_303 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_304 = __VLS_asFunctionalComponent1(__VLS_303, new __VLS_303({
            value: (slotProps.data.riskLevel),
            severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
            rounded: true,
        }));
        var __VLS_305 = __VLS_304.apply(void 0, __spreadArray([{
                value: (slotProps.data.riskLevel),
                severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_304), false));
        // @ts-ignore
        [getRiskLevelSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_299;
    var __VLS_308 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_309 = __VLS_asFunctionalComponent1(__VLS_308, new __VLS_308(__assign({ field: "totalOrders", header: "Orders", sortable: true }, { style: {} })));
    var __VLS_310 = __VLS_309.apply(void 0, __spreadArray([__assign({ field: "totalOrders", header: "Orders", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_309), false));
    var __VLS_313 = __VLS_311.slots.default;
    {
        var __VLS_314 = __VLS_311.slots.body;
        var slotProps = __VLS_vSlot(__VLS_314)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (slotProps.data.totalOrders);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_311;
    var __VLS_315 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_316 = __VLS_asFunctionalComponent1(__VLS_315, new __VLS_315(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_317 = __VLS_316.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_316), false));
    var __VLS_320 = __VLS_318.slots.default;
    {
        var __VLS_321 = __VLS_318.slots.body;
        var slotProps_2 = __VLS_vSlot(__VLS_321)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_322 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_323 = __VLS_asFunctionalComponent1(__VLS_322, new __VLS_322(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_324 = __VLS_323.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_323), false));
        var __VLS_327 = void 0;
        var __VLS_328 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'verified'))
                        return;
                    __VLS_ctx.viewCustomer(slotProps_2.data);
                    // @ts-ignore
                    [viewCustomer,];
                } });
        var __VLS_325;
        var __VLS_326;
        var __VLS_329 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_330 = __VLS_asFunctionalComponent1(__VLS_329, new __VLS_329(__assign({ 'onClick': {} }, { icon: "pi pi-history", size: "small", severity: "warning" })));
        var __VLS_331 = __VLS_330.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-history", size: "small", severity: "warning" })], __VLS_functionalComponentArgsRest(__VLS_330), false));
        var __VLS_334 = void 0;
        var __VLS_335 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'verified'))
                        return;
                    __VLS_ctx.reverifyCustomer(slotProps_2.data);
                    // @ts-ignore
                    [reverifyCustomer,];
                } });
        var __VLS_332;
        var __VLS_333;
        var __VLS_336 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_337 = __VLS_asFunctionalComponent1(__VLS_336, new __VLS_336(__assign({ 'onClick': {} }, { icon: "pi pi-ban", size: "small", severity: "danger" })));
        var __VLS_338 = __VLS_337.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-ban", size: "small", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_337), false));
        var __VLS_341 = void 0;
        var __VLS_342 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'verified'))
                        return;
                    __VLS_ctx.suspendCustomer(slotProps_2.data);
                    // @ts-ignore
                    [suspendCustomer,];
                } });
        var __VLS_339;
        var __VLS_340;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_318;
    // @ts-ignore
    [];
    var __VLS_260;
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
    (__VLS_ctx.filteredRejectedCustomers.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_343 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_344 = __VLS_asFunctionalComponent1(__VLS_343, new __VLS_343({}));
    var __VLS_345 = __VLS_344.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_344), false));
    var __VLS_348 = __VLS_346.slots.default;
    var __VLS_349 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_350 = __VLS_asFunctionalComponent1(__VLS_349, new __VLS_349({}));
    var __VLS_351 = __VLS_350.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_350), false));
    var __VLS_354 = __VLS_352.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredRejectedCustomers,];
    var __VLS_352;
    var __VLS_355 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_356 = __VLS_asFunctionalComponent1(__VLS_355, new __VLS_355(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search rejected customers..." }, { class: "w-full" })));
    var __VLS_357 = __VLS_356.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search rejected customers..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_356), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_346;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_360 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_361 = __VLS_asFunctionalComponent1(__VLS_360, new __VLS_360(__assign({ modelValue: (__VLS_ctx.rejectionReasonFilter), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Rejection Reasons", display: "chip" }, { class: "w-48" })));
    var __VLS_362 = __VLS_361.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionReasonFilter), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Rejection Reasons", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_361), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_365 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_366 = __VLS_asFunctionalComponent1(__VLS_365, new __VLS_365(__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })));
    var __VLS_367 = __VLS_366.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_366), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_370 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_371 = __VLS_asFunctionalComponent1(__VLS_370, new __VLS_370(__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })));
    var __VLS_372 = __VLS_371.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.dateFilter), options: (__VLS_ctx.dateFilterOptions), optionLabel: "name", placeholder: "Registration Date" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_371), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_375 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_376 = __VLS_asFunctionalComponent1(__VLS_375, new __VLS_375({
        value: (__VLS_ctx.filteredRejectedCustomers),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_377 = __VLS_376.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredRejectedCustomers),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_376), false));
    var __VLS_380 = __VLS_378.slots.default;
    var __VLS_381 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_382 = __VLS_asFunctionalComponent1(__VLS_381, new __VLS_381(__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })));
    var __VLS_383 = __VLS_382.apply(void 0, __spreadArray([__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_382), false));
    var __VLS_386 = __VLS_384.slots.default;
    {
        var __VLS_387 = __VLS_384.slots.body;
        var slotProps = __VLS_vSlot(__VLS_387)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-10 h-10 bg-red-100 rounded-full flex items-center justify-center" }));
        /** @type {__VLS_StyleScopedClasses['w-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['h-10']} */ ;
        /** @type {__VLS_StyleScopedClasses['bg-red-100']} */ ;
        /** @type {__VLS_StyleScopedClasses['rounded-full']} */ ;
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['justify-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-user-times text-red-600" }));
        /** @type {__VLS_StyleScopedClasses['pi']} */ ;
        /** @type {__VLS_StyleScopedClasses['pi-user-times']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-red-600']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.fullName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.customerId);
        // @ts-ignore
        [customerTypeFilter, customerTypeOptions, dateFilter, dateFilterOptions, filteredRejectedCustomers, rejectionReasonFilter, customerRejectionReasonOptions,];
    }
    // @ts-ignore
    [];
    var __VLS_384;
    var __VLS_388 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_389 = __VLS_asFunctionalComponent1(__VLS_388, new __VLS_388(__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })));
    var __VLS_390 = __VLS_389.apply(void 0, __spreadArray([__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_389), false));
    var __VLS_393 = __VLS_391.slots.default;
    {
        var __VLS_394 = __VLS_391.slots.body;
        var slotProps = __VLS_vSlot(__VLS_394)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.email);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.phone);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_391;
    var __VLS_395 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_396 = __VLS_asFunctionalComponent1(__VLS_395, new __VLS_395(__assign({ field: "rejectionDate", header: "Rejected On", sortable: true }, { style: {} })));
    var __VLS_397 = __VLS_396.apply(void 0, __spreadArray([__assign({ field: "rejectionDate", header: "Rejected On", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_396), false));
    var __VLS_400 = __VLS_398.slots.default;
    {
        var __VLS_401 = __VLS_398.slots.body;
        var slotProps = __VLS_vSlot(__VLS_401)[0];
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
    var __VLS_398;
    var __VLS_402 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_403 = __VLS_asFunctionalComponent1(__VLS_402, new __VLS_402(__assign({ field: "rejectionReason", header: "Reason", sortable: true }, { style: {} })));
    var __VLS_404 = __VLS_403.apply(void 0, __spreadArray([__assign({ field: "rejectionReason", header: "Reason", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_403), false));
    var __VLS_407 = __VLS_405.slots.default;
    {
        var __VLS_408 = __VLS_405.slots.body;
        var slotProps = __VLS_vSlot(__VLS_408)[0];
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
    var __VLS_405;
    var __VLS_409 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_410 = __VLS_asFunctionalComponent1(__VLS_409, new __VLS_409(__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })));
    var __VLS_411 = __VLS_410.apply(void 0, __spreadArray([__assign({ field: "riskLevel", header: "Risk", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_410), false));
    var __VLS_414 = __VLS_412.slots.default;
    {
        var __VLS_415 = __VLS_412.slots.body;
        var slotProps = __VLS_vSlot(__VLS_415)[0];
        var __VLS_416 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_417 = __VLS_asFunctionalComponent1(__VLS_416, new __VLS_416({
            value: (slotProps.data.riskLevel),
            severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
            rounded: true,
        }));
        var __VLS_418 = __VLS_417.apply(void 0, __spreadArray([{
                value: (slotProps.data.riskLevel),
                severity: (__VLS_ctx.getRiskLevelSeverity(slotProps.data.riskLevel)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_417), false));
        // @ts-ignore
        [getRiskLevelSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_412;
    var __VLS_421 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_422 = __VLS_asFunctionalComponent1(__VLS_421, new __VLS_421(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_423 = __VLS_422.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_422), false));
    var __VLS_426 = __VLS_424.slots.default;
    {
        var __VLS_427 = __VLS_424.slots.body;
        var slotProps_3 = __VLS_vSlot(__VLS_427)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_428 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_429 = __VLS_asFunctionalComponent1(__VLS_428, new __VLS_428(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_430 = __VLS_429.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_429), false));
        var __VLS_433 = void 0;
        var __VLS_434 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'rejected'))
                        return;
                    __VLS_ctx.viewRejectedCustomer(slotProps_3.data);
                    // @ts-ignore
                    [viewRejectedCustomer,];
                } });
        var __VLS_431;
        var __VLS_432;
        var __VLS_435 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_436 = __VLS_asFunctionalComponent1(__VLS_435, new __VLS_435(__assign({ 'onClick': {} }, { label: "Re-review", size: "small", icon: "pi pi-redo" })));
        var __VLS_437 = __VLS_436.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Re-review", size: "small", icon: "pi pi-redo" })], __VLS_functionalComponentArgsRest(__VLS_436), false));
        var __VLS_440 = void 0;
        var __VLS_441 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'rejected'))
                        return;
                    __VLS_ctx.rereviewCustomer(slotProps_3.data);
                    // @ts-ignore
                    [rereviewCustomer,];
                } });
        var __VLS_438;
        var __VLS_439;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_424;
    // @ts-ignore
    [];
    var __VLS_378;
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
    (__VLS_ctx.filteredAllCustomers.length);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex flex-wrap items-center gap-4" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['flex-wrap']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "w-64" }));
    /** @type {__VLS_StyleScopedClasses['w-64']} */ ;
    var __VLS_442 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.IconField | typeof __VLS_components.IconField} */
    iconfield_1.default;
    // @ts-ignore
    var __VLS_443 = __VLS_asFunctionalComponent1(__VLS_442, new __VLS_442({}));
    var __VLS_444 = __VLS_443.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_443), false));
    var __VLS_447 = __VLS_445.slots.default;
    var __VLS_448 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputIcon | typeof __VLS_components.InputIcon} */
    inputicon_1.default;
    // @ts-ignore
    var __VLS_449 = __VLS_asFunctionalComponent1(__VLS_448, new __VLS_448({}));
    var __VLS_450 = __VLS_449.apply(void 0, __spreadArray([{}], __VLS_functionalComponentArgsRest(__VLS_449), false));
    var __VLS_453 = __VLS_451.slots.default;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i)(__assign({ class: "pi pi-search" }));
    /** @type {__VLS_StyleScopedClasses['pi']} */ ;
    /** @type {__VLS_StyleScopedClasses['pi-search']} */ ;
    // @ts-ignore
    [activeView, filteredAllCustomers,];
    var __VLS_451;
    var __VLS_454 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.InputText} */
    inputtext_1.default;
    // @ts-ignore
    var __VLS_455 = __VLS_asFunctionalComponent1(__VLS_454, new __VLS_454(__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search all customers..." }, { class: "w-full" })));
    var __VLS_456 = __VLS_455.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.searchTerm), placeholder: "Search all customers..." }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_455), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
    // @ts-ignore
    [searchTerm,];
    var __VLS_445;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center gap-2" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['gap-2']} */ ;
    var __VLS_459 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_460 = __VLS_asFunctionalComponent1(__VLS_459, new __VLS_459(__assign({ modelValue: (__VLS_ctx.statusFilter), options: (__VLS_ctx.customerStatusOptions), optionLabel: "name", placeholder: "Status" }, { class: "w-40" })));
    var __VLS_461 = __VLS_460.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.statusFilter), options: (__VLS_ctx.customerStatusOptions), optionLabel: "name", placeholder: "Status" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_460), false));
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_464 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.MultiSelect} */
    multiselect_1.default;
    // @ts-ignore
    var __VLS_465 = __VLS_asFunctionalComponent1(__VLS_464, new __VLS_464(__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })));
    var __VLS_466 = __VLS_465.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.customerTypeFilter), options: (__VLS_ctx.customerTypeOptions), optionLabel: "name", placeholder: "Customer Type", display: "chip" }, { class: "w-48" })], __VLS_functionalComponentArgsRest(__VLS_465), false));
    /** @type {__VLS_StyleScopedClasses['w-48']} */ ;
    var __VLS_469 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Select} */
    select_1.default;
    // @ts-ignore
    var __VLS_470 = __VLS_asFunctionalComponent1(__VLS_469, new __VLS_469(__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "Risk Level" }, { class: "w-40" })));
    var __VLS_471 = __VLS_470.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.riskFilter), options: (__VLS_ctx.riskLevelOptions), optionLabel: "name", placeholder: "Risk Level" }, { class: "w-40" })], __VLS_functionalComponentArgsRest(__VLS_470), false));
    /** @type {__VLS_StyleScopedClasses['w-40']} */ ;
    var __VLS_474 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.DataTable | typeof __VLS_components.DataTable} */
    datatable_1.default;
    // @ts-ignore
    var __VLS_475 = __VLS_asFunctionalComponent1(__VLS_474, new __VLS_474({
        value: (__VLS_ctx.filteredAllCustomers),
        dataKey: "id",
        sortMode: "multiple",
        tableStyle: "min-width: 50rem",
        paginator: true,
        rows: (10),
        rowsPerPageOptions: ([5, 10, 20, 50]),
    }));
    var __VLS_476 = __VLS_475.apply(void 0, __spreadArray([{
            value: (__VLS_ctx.filteredAllCustomers),
            dataKey: "id",
            sortMode: "multiple",
            tableStyle: "min-width: 50rem",
            paginator: true,
            rows: (10),
            rowsPerPageOptions: ([5, 10, 20, 50]),
        }], __VLS_functionalComponentArgsRest(__VLS_475), false));
    var __VLS_479 = __VLS_477.slots.default;
    var __VLS_480 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_481 = __VLS_asFunctionalComponent1(__VLS_480, new __VLS_480(__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })));
    var __VLS_482 = __VLS_481.apply(void 0, __spreadArray([__assign({ field: "fullName", header: "Customer", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_481), false));
    var __VLS_485 = __VLS_483.slots.default;
    {
        var __VLS_486 = __VLS_483.slots.body;
        var slotProps = __VLS_vSlot(__VLS_486)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center space-x-3" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-3']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: ("w-10 h-10 rounded-full flex items-center justify-center ".concat(__VLS_ctx.getCustomerStatusColor(slotProps.data.status))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("pi ".concat(__VLS_ctx.getCustomerStatusIcon(slotProps.data.status))) }));
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.fullName);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.customerId);
        // @ts-ignore
        [riskFilter, riskLevelOptions, customerTypeFilter, customerTypeOptions, filteredAllCustomers, statusFilter, customerStatusOptions, getCustomerStatusColor, getCustomerStatusIcon,];
    }
    // @ts-ignore
    [];
    var __VLS_483;
    var __VLS_487 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_488 = __VLS_asFunctionalComponent1(__VLS_487, new __VLS_487(__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })));
    var __VLS_489 = __VLS_488.apply(void 0, __spreadArray([__assign({ field: "email", header: "Contact", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_488), false));
    var __VLS_492 = __VLS_490.slots.default;
    {
        var __VLS_493 = __VLS_490.slots.body;
        var slotProps = __VLS_vSlot(__VLS_493)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (slotProps.data.email);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.phone);
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_490;
    var __VLS_494 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_495 = __VLS_asFunctionalComponent1(__VLS_494, new __VLS_494(__assign({ field: "status", header: "Status", sortable: true }, { style: {} })));
    var __VLS_496 = __VLS_495.apply(void 0, __spreadArray([__assign({ field: "status", header: "Status", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_495), false));
    var __VLS_499 = __VLS_497.slots.default;
    {
        var __VLS_500 = __VLS_497.slots.body;
        var slotProps = __VLS_vSlot(__VLS_500)[0];
        var __VLS_501 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_502 = __VLS_asFunctionalComponent1(__VLS_501, new __VLS_501({
            value: (slotProps.data.status),
            severity: (__VLS_ctx.getCustomerStatusSeverity(slotProps.data.status)),
            rounded: true,
        }));
        var __VLS_503 = __VLS_502.apply(void 0, __spreadArray([{
                value: (slotProps.data.status),
                severity: (__VLS_ctx.getCustomerStatusSeverity(slotProps.data.status)),
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_502), false));
        // @ts-ignore
        [getCustomerStatusSeverity,];
    }
    // @ts-ignore
    [];
    var __VLS_497;
    var __VLS_506 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_507 = __VLS_asFunctionalComponent1(__VLS_506, new __VLS_506(__assign({ field: "customerType", header: "Type", sortable: true }, { style: {} })));
    var __VLS_508 = __VLS_507.apply(void 0, __spreadArray([__assign({ field: "customerType", header: "Type", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_507), false));
    var __VLS_511 = __VLS_509.slots.default;
    {
        var __VLS_512 = __VLS_509.slots.body;
        var slotProps = __VLS_vSlot(__VLS_512)[0];
        var __VLS_513 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Tag} */
        tag_1.default;
        // @ts-ignore
        var __VLS_514 = __VLS_asFunctionalComponent1(__VLS_513, new __VLS_513({
            value: (slotProps.data.customerType),
            severity: "info",
            rounded: true,
        }));
        var __VLS_515 = __VLS_514.apply(void 0, __spreadArray([{
                value: (slotProps.data.customerType),
                severity: "info",
                rounded: true,
            }], __VLS_functionalComponentArgsRest(__VLS_514), false));
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_509;
    var __VLS_518 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_519 = __VLS_asFunctionalComponent1(__VLS_518, new __VLS_518(__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })));
    var __VLS_520 = __VLS_519.apply(void 0, __spreadArray([__assign({ field: "registrationDate", header: "Registered", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_519), false));
    var __VLS_523 = __VLS_521.slots.default;
    {
        var __VLS_524 = __VLS_521.slots.body;
        var slotProps = __VLS_vSlot(__VLS_524)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
        /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
        (__VLS_ctx.formatDate(slotProps.data.registrationDate));
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        (slotProps.data.accountAge);
        // @ts-ignore
        [formatDate,];
    }
    // @ts-ignore
    [];
    var __VLS_521;
    var __VLS_525 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_526 = __VLS_asFunctionalComponent1(__VLS_525, new __VLS_525(__assign({ field: "totalOrders", header: "Orders", sortable: true }, { style: {} })));
    var __VLS_527 = __VLS_526.apply(void 0, __spreadArray([__assign({ field: "totalOrders", header: "Orders", sortable: true }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_526), false));
    var __VLS_530 = __VLS_528.slots.default;
    {
        var __VLS_531 = __VLS_528.slots.body;
        var slotProps = __VLS_vSlot(__VLS_531)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "text-center" }));
        /** @type {__VLS_StyleScopedClasses['text-center']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-bold" }));
        /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
        (slotProps.data.totalOrders);
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
        /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
        /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_528;
    var __VLS_532 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Column | typeof __VLS_components.Column} */
    column_1.default;
    // @ts-ignore
    var __VLS_533 = __VLS_asFunctionalComponent1(__VLS_532, new __VLS_532(__assign({ header: "Actions" }, { style: {} })));
    var __VLS_534 = __VLS_533.apply(void 0, __spreadArray([__assign({ header: "Actions" }, { style: {} })], __VLS_functionalComponentArgsRest(__VLS_533), false));
    var __VLS_537 = __VLS_535.slots.default;
    {
        var __VLS_538 = __VLS_535.slots.body;
        var slotProps_4 = __VLS_vSlot(__VLS_538)[0];
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex space-x-2" }));
        /** @type {__VLS_StyleScopedClasses['flex']} */ ;
        /** @type {__VLS_StyleScopedClasses['space-x-2']} */ ;
        var __VLS_539 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_540 = __VLS_asFunctionalComponent1(__VLS_539, new __VLS_539(__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })));
        var __VLS_541 = __VLS_540.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-eye", size: "small", severity: "info" })], __VLS_functionalComponentArgsRest(__VLS_540), false));
        var __VLS_544 = void 0;
        var __VLS_545 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.activeView === 'all'))
                        return;
                    __VLS_ctx.viewCustomer(slotProps_4.data);
                    // @ts-ignore
                    [viewCustomer,];
                } });
        var __VLS_542;
        var __VLS_543;
        if (slotProps_4.data.status === 'Pending') {
            var __VLS_546 = void 0;
            /** @ts-ignore @type {typeof __VLS_components.Button} */
            button_1.default;
            // @ts-ignore
            var __VLS_547 = __VLS_asFunctionalComponent1(__VLS_546, new __VLS_546(__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })));
            var __VLS_548 = __VLS_547.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { icon: "pi pi-check", size: "small", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_547), false));
            var __VLS_551 = void 0;
            var __VLS_552 = ({ click: {} },
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
                        __VLS_ctx.verifyCustomer(slotProps_4.data);
                        // @ts-ignore
                        [verifyCustomer,];
                    } });
            var __VLS_549;
            var __VLS_550;
        }
        // @ts-ignore
        [];
    }
    // @ts-ignore
    [];
    var __VLS_535;
    // @ts-ignore
    [];
    var __VLS_477;
}
var __VLS_553;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_554 = __VLS_asFunctionalComponent1(__VLS_553, new __VLS_553(__assign({ visible: (__VLS_ctx.showReviewDialog), modal: true, header: (__VLS_ctx.selectedReviewCustomer ? "Review Customer: ".concat(__VLS_ctx.selectedReviewCustomer.fullName) : 'Review Customer') }, { style: ({ width: '800px' }) })));
var __VLS_555 = __VLS_554.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showReviewDialog), modal: true, header: (__VLS_ctx.selectedReviewCustomer ? "Review Customer: ".concat(__VLS_ctx.selectedReviewCustomer.fullName) : 'Review Customer') }, { style: ({ width: '800px' }) })], __VLS_functionalComponentArgsRest(__VLS_554), false));
var __VLS_558 = __VLS_556.slots.default;
if (__VLS_ctx.selectedReviewCustomer) {
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
    (__VLS_ctx.selectedReviewCustomer.fullName);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedReviewCustomer.dateOfBirth));
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewCustomer.gender);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewCustomer.nationality);
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
    (__VLS_ctx.selectedReviewCustomer.email);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewCustomer.phone);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewCustomer.address);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.formatDate(__VLS_ctx.selectedReviewCustomer.registrationDate));
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
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: ("pi ".concat(__VLS_ctx.getDocumentTypeIcon(doc.type), " ").concat(__VLS_ctx.getDocumentTypeColor(doc.type))) }));
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
        var __VLS_559 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Button} */
        button_1.default;
        // @ts-ignore
        var __VLS_560 = __VLS_asFunctionalComponent1(__VLS_559, new __VLS_559(__assign({ 'onClick': {} }, { label: "View", size: "small", icon: "pi pi-eye" })));
        var __VLS_561 = __VLS_560.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "View", size: "small", icon: "pi pi-eye" })], __VLS_functionalComponentArgsRest(__VLS_560), false));
        var __VLS_564 = void 0;
        var __VLS_565 = ({ click: {} },
            { onClick: function () {
                    var _a = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        _a[_i] = arguments[_i];
                    }
                    var $event = _a[0];
                    if (!(__VLS_ctx.selectedReviewCustomer))
                        return;
                    __VLS_ctx.viewDocument(doc);
                    // @ts-ignore
                    [formatDate, formatDate, showReviewDialog, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, getDocumentTypeIcon, getDocumentTypeColor, viewDocument,];
                } });
        var __VLS_566 = void 0;
        /** @ts-ignore @type {typeof __VLS_components.Select} */
        select_1.default;
        // @ts-ignore
        var __VLS_567 = __VLS_asFunctionalComponent1(__VLS_566, new __VLS_566(__assign({ modelValue: (doc.verificationStatus), options: (__VLS_ctx.verificationStatusOptions), optionLabel: "name", placeholder: "Verify" }, { class: "w-32" })));
        var __VLS_568 = __VLS_567.apply(void 0, __spreadArray([__assign({ modelValue: (doc.verificationStatus), options: (__VLS_ctx.verificationStatusOptions), optionLabel: "name", placeholder: "Verify" }, { class: "w-32" })], __VLS_functionalComponentArgsRest(__VLS_567), false));
        /** @type {__VLS_StyleScopedClasses['w-32']} */ ;
        // @ts-ignore
        [verificationStatusOptions,];
    };
    var __VLS_562, __VLS_563;
    for (var _i = 0, _a = __VLS_vFor((__VLS_ctx.selectedReviewCustomer.documents)); _i < _a.length; _i++) {
        var doc = _a[_i][0];
        _loop_1(doc);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.h4, __VLS_intrinsics.h4)(__assign({ class: "font-medium text-gray-800 mb-3" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-800']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-gray-50 p-4 rounded-lg" }));
    /** @type {__VLS_StyleScopedClasses['bg-gray-50']} */ ;
    /** @type {__VLS_StyleScopedClasses['p-4']} */ ;
    /** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "flex items-center justify-between" }));
    /** @type {__VLS_StyleScopedClasses['flex']} */ ;
    /** @type {__VLS_StyleScopedClasses['items-center']} */ ;
    /** @type {__VLS_StyleScopedClasses['justify-between']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "font-medium" }));
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    (__VLS_ctx.selectedReviewCustomer.riskLevel);
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-xs text-gray-500" }));
    /** @type {__VLS_StyleScopedClasses['text-xs']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-500']} */ ;
    (__VLS_ctx.selectedReviewCustomer.riskScore);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    var __VLS_571 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Tag} */
    tag_1.default;
    // @ts-ignore
    var __VLS_572 = __VLS_asFunctionalComponent1(__VLS_571, new __VLS_571(__assign({ value: (__VLS_ctx.selectedReviewCustomer.riskLevel), severity: (__VLS_ctx.getRiskLevelSeverity(__VLS_ctx.selectedReviewCustomer.riskLevel)) }, { class: "text-lg" })));
    var __VLS_573 = __VLS_572.apply(void 0, __spreadArray([__assign({ value: (__VLS_ctx.selectedReviewCustomer.riskLevel), severity: (__VLS_ctx.getRiskLevelSeverity(__VLS_ctx.selectedReviewCustomer.riskLevel)) }, { class: "text-lg" })], __VLS_functionalComponentArgsRest(__VLS_572), false));
    /** @type {__VLS_StyleScopedClasses['text-lg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
    /** @type {__VLS_StyleScopedClasses['block']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
    /** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
    /** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
    var __VLS_576 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Textarea} */
    textarea_1.default;
    // @ts-ignore
    var __VLS_577 = __VLS_asFunctionalComponent1(__VLS_576, new __VLS_576(__assign({ modelValue: (__VLS_ctx.reviewNotes), placeholder: "Enter review notes or comments...", rows: "3" }, { class: "w-full" })));
    var __VLS_578 = __VLS_577.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.reviewNotes), placeholder: "Enter review notes or comments...", rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_577), false));
    /** @type {__VLS_StyleScopedClasses['w-full']} */ ;
}
{
    var __VLS_581 = __VLS_556.slots.footer;
    var __VLS_582 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_583 = __VLS_asFunctionalComponent1(__VLS_582, new __VLS_582(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_584 = __VLS_583.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_583), false));
    var __VLS_587 = void 0;
    var __VLS_588 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showReviewDialog = false;
                // @ts-ignore
                [getRiskLevelSeverity, showReviewDialog, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, selectedReviewCustomer, reviewNotes,];
            } });
    var __VLS_585;
    var __VLS_586;
    var __VLS_589 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_590 = __VLS_asFunctionalComponent1(__VLS_589, new __VLS_589(__assign({ 'onClick': {} }, { label: "Request More Info", icon: "pi pi-question-circle" })));
    var __VLS_591 = __VLS_590.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Request More Info", icon: "pi pi-question-circle" })], __VLS_functionalComponentArgsRest(__VLS_590), false));
    var __VLS_594 = void 0;
    var __VLS_595 = ({ click: {} },
        { onClick: (__VLS_ctx.requestMoreInfo) });
    var __VLS_592;
    var __VLS_593;
    var __VLS_596 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_597 = __VLS_asFunctionalComponent1(__VLS_596, new __VLS_596(__assign({ 'onClick': {} }, { label: "Reject Customer", icon: "pi pi-times", severity: "danger" })));
    var __VLS_598 = __VLS_597.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject Customer", icon: "pi pi-times", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_597), false));
    var __VLS_601 = void 0;
    var __VLS_602 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.rejectCustomer(__VLS_ctx.selectedReviewCustomer);
                // @ts-ignore
                [rejectCustomer, selectedReviewCustomer, requestMoreInfo,];
            } });
    var __VLS_599;
    var __VLS_600;
    var __VLS_603 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_604 = __VLS_asFunctionalComponent1(__VLS_603, new __VLS_603(__assign({ 'onClick': {} }, { label: "Verify Customer", icon: "pi pi-check" })));
    var __VLS_605 = __VLS_604.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Verify Customer", icon: "pi pi-check" })], __VLS_functionalComponentArgsRest(__VLS_604), false));
    var __VLS_608 = void 0;
    var __VLS_609 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.verifyCustomer(__VLS_ctx.selectedReviewCustomer);
                // @ts-ignore
                [verifyCustomer, selectedReviewCustomer,];
            } });
    var __VLS_606;
    var __VLS_607;
    // @ts-ignore
    [];
}
// @ts-ignore
[];
var __VLS_556;
var __VLS_610;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_611 = __VLS_asFunctionalComponent1(__VLS_610, new __VLS_610(__assign({ visible: (__VLS_ctx.showRejectDialog), header: "Reject Customer Application" }, { style: ({ width: '600px' }) })));
var __VLS_612 = __VLS_611.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showRejectDialog), header: "Reject Customer Application" }, { style: ({ width: '600px' }) })], __VLS_functionalComponentArgsRest(__VLS_611), false));
var __VLS_615 = __VLS_613.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
if (__VLS_ctx.customerToReject) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600 mb-4" }));
    /** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
    /** @type {__VLS_StyleScopedClasses['mb-4']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)(__assign({ class: "font-bold" }));
    /** @type {__VLS_StyleScopedClasses['font-bold']} */ ;
    (__VLS_ctx.customerToReject.fullName);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_616;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_617 = __VLS_asFunctionalComponent1(__VLS_616, new __VLS_616(__assign({ modelValue: (__VLS_ctx.rejectionReason), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })));
var __VLS_618 = __VLS_617.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionReason), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_617), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_621;
/** @ts-ignore @type {typeof __VLS_components.Textarea} */
textarea_1.default;
// @ts-ignore
var __VLS_622 = __VLS_asFunctionalComponent1(__VLS_621, new __VLS_621(__assign({ modelValue: (__VLS_ctx.rejectionNotes), placeholder: "Provide additional details for rejection...", rows: "3" }, { class: "w-full" })));
var __VLS_623 = __VLS_622.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.rejectionNotes), placeholder: "Provide additional details for rejection...", rows: "3" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_622), false));
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
    var __VLS_626 = __VLS_613.slots.footer;
    var __VLS_627 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_628 = __VLS_asFunctionalComponent1(__VLS_627, new __VLS_627(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_629 = __VLS_628.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_628), false));
    var __VLS_632 = void 0;
    var __VLS_633 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showRejectDialog = false;
                // @ts-ignore
                [customerRejectionReasonOptions, showRejectDialog, showRejectDialog, customerToReject, customerToReject, rejectionReason, rejectionNotes,];
            } });
    var __VLS_630;
    var __VLS_631;
    var __VLS_634 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_635 = __VLS_asFunctionalComponent1(__VLS_634, new __VLS_634(__assign({ 'onClick': {} }, { label: "Confirm Reject", severity: "danger" })));
    var __VLS_636 = __VLS_635.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Confirm Reject", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_635), false));
    var __VLS_639 = void 0;
    var __VLS_640 = ({ click: {} },
        { onClick: (__VLS_ctx.confirmReject) });
    var __VLS_637;
    var __VLS_638;
    // @ts-ignore
    [confirmReject,];
}
// @ts-ignore
[];
var __VLS_613;
var __VLS_641;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_642 = __VLS_asFunctionalComponent1(__VLS_641, new __VLS_641(__assign({ visible: (__VLS_ctx.showBulkVerifyDialog), header: "Bulk Verify Customers" }, { style: ({ width: '500px' }) })));
var __VLS_643 = __VLS_642.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showBulkVerifyDialog), header: "Bulk Verify Customers" }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_642), false));
var __VLS_646 = __VLS_644.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
(__VLS_ctx.selectedCustomers.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "bg-green-50 p-4 rounded-lg" }));
/** @type {__VLS_StyleScopedClasses['bg-green-50']} */ ;
/** @type {__VLS_StyleScopedClasses['p-4']} */ ;
/** @type {__VLS_StyleScopedClasses['rounded-lg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-sm text-green-800" }));
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['text-green-800']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)(__assign({ class: "pi pi-info-circle mr-2" }));
/** @type {__VLS_StyleScopedClasses['pi']} */ ;
/** @type {__VLS_StyleScopedClasses['pi-info-circle']} */ ;
/** @type {__VLS_StyleScopedClasses['mr-2']} */ ;
{
    var __VLS_647 = __VLS_644.slots.footer;
    var __VLS_648 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_649 = __VLS_asFunctionalComponent1(__VLS_648, new __VLS_648(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_650 = __VLS_649.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_649), false));
    var __VLS_653 = void 0;
    var __VLS_654 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showBulkVerifyDialog = false;
                // @ts-ignore
                [selectedCustomers, showBulkVerifyDialog, showBulkVerifyDialog,];
            } });
    var __VLS_651;
    var __VLS_652;
    var __VLS_655 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_656 = __VLS_asFunctionalComponent1(__VLS_655, new __VLS_655(__assign({ 'onClick': {} }, { label: "Verify All", severity: "success" })));
    var __VLS_657 = __VLS_656.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Verify All", severity: "success" })], __VLS_functionalComponentArgsRest(__VLS_656), false));
    var __VLS_660 = void 0;
    var __VLS_661 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkVerify) });
    var __VLS_658;
    var __VLS_659;
    // @ts-ignore
    [bulkVerify,];
}
// @ts-ignore
[];
var __VLS_644;
var __VLS_662;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_663 = __VLS_asFunctionalComponent1(__VLS_662, new __VLS_662(__assign({ visible: (__VLS_ctx.showBulkRejectDialog), header: "Bulk Reject Customers" }, { style: ({ width: '500px' }) })));
var __VLS_664 = __VLS_663.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showBulkRejectDialog), header: "Bulk Reject Customers" }, { style: ({ width: '500px' }) })], __VLS_functionalComponentArgsRest(__VLS_663), false));
var __VLS_667 = __VLS_665.slots.default;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)(__assign({ class: "space-y-4" }));
/** @type {__VLS_StyleScopedClasses['space-y-4']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)(__assign({ class: "text-gray-600" }));
/** @type {__VLS_StyleScopedClasses['text-gray-600']} */ ;
(__VLS_ctx.selectedCustomers.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_668;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_669 = __VLS_asFunctionalComponent1(__VLS_668, new __VLS_668(__assign({ modelValue: (__VLS_ctx.bulkRejectionReason), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })));
var __VLS_670 = __VLS_669.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.bulkRejectionReason), options: (__VLS_ctx.customerRejectionReasonOptions), optionLabel: "name", placeholder: "Select reason" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_669), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_673 = __VLS_665.slots.footer;
    var __VLS_674 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_675 = __VLS_asFunctionalComponent1(__VLS_674, new __VLS_674(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_676 = __VLS_675.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_675), false));
    var __VLS_679 = void 0;
    var __VLS_680 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showBulkRejectDialog = false;
                // @ts-ignore
                [selectedCustomers, showBulkRejectDialog, showBulkRejectDialog, customerRejectionReasonOptions, bulkRejectionReason,];
            } });
    var __VLS_677;
    var __VLS_678;
    var __VLS_681 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_682 = __VLS_asFunctionalComponent1(__VLS_681, new __VLS_681(__assign({ 'onClick': {} }, { label: "Reject All", severity: "danger" })));
    var __VLS_683 = __VLS_682.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Reject All", severity: "danger" })], __VLS_functionalComponentArgsRest(__VLS_682), false));
    var __VLS_686 = void 0;
    var __VLS_687 = ({ click: {} },
        { onClick: (__VLS_ctx.bulkReject) });
    var __VLS_684;
    var __VLS_685;
    // @ts-ignore
    [bulkReject,];
}
// @ts-ignore
[];
var __VLS_665;
var __VLS_688;
/** @ts-ignore @type {typeof __VLS_components.Dialog | typeof __VLS_components.Dialog} */
dialog_1.default;
// @ts-ignore
var __VLS_689 = __VLS_asFunctionalComponent1(__VLS_688, new __VLS_688(__assign({ visible: (__VLS_ctx.showSettingsDialog), header: "Customer Validation Settings" }, { style: ({ width: '700px' }) })));
var __VLS_690 = __VLS_689.apply(void 0, __spreadArray([__assign({ visible: (__VLS_ctx.showSettingsDialog), header: "Customer Validation Settings" }, { style: ({ width: '700px' }) })], __VLS_functionalComponentArgsRest(__VLS_689), false));
var __VLS_693 = __VLS_691.slots.default;
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
var __VLS_694;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_695 = __VLS_asFunctionalComponent1(__VLS_694, new __VLS_694({
    modelValue: (__VLS_ctx.autoVerificationEnabled),
}));
var __VLS_696 = __VLS_695.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.autoVerificationEnabled),
    }], __VLS_functionalComponentArgsRest(__VLS_695), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_699;
/** @ts-ignore @type {typeof __VLS_components.Select} */
select_1.default;
// @ts-ignore
var __VLS_700 = __VLS_asFunctionalComponent1(__VLS_699, new __VLS_699(__assign({ modelValue: (__VLS_ctx.verificationThreshold), options: (__VLS_ctx.thresholdOptions), optionLabel: "name", placeholder: "Select threshold" }, { class: "w-full" })));
var __VLS_701 = __VLS_700.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.verificationThreshold), options: (__VLS_ctx.thresholdOptions), optionLabel: "name", placeholder: "Select threshold" }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_700), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
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
var __VLS_704;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_705 = __VLS_asFunctionalComponent1(__VLS_704, new __VLS_704(__assign({ modelValue: (__VLS_ctx.highRiskThreshold), min: (0), max: (100) }, { class: "w-full" })));
var __VLS_706 = __VLS_705.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.highRiskThreshold), min: (0), max: (100) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_705), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_709;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_710 = __VLS_asFunctionalComponent1(__VLS_709, new __VLS_709(__assign({ modelValue: (__VLS_ctx.mediumRiskThreshold), min: (0), max: (100) }, { class: "w-full" })));
var __VLS_711 = __VLS_710.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.mediumRiskThreshold), min: (0), max: (100) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_710), false));
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
var __VLS_714;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_715 = __VLS_asFunctionalComponent1(__VLS_714, new __VLS_714({
    modelValue: (__VLS_ctx.requireIdVerification),
}));
var __VLS_716 = __VLS_715.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.requireIdVerification),
    }], __VLS_functionalComponentArgsRest(__VLS_715), false));
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
var __VLS_719;
/** @ts-ignore @type {typeof __VLS_components.InputSwitch} */
inputswitch_1.default;
// @ts-ignore
var __VLS_720 = __VLS_asFunctionalComponent1(__VLS_719, new __VLS_719({
    modelValue: (__VLS_ctx.requireAddressProof),
}));
var __VLS_721 = __VLS_720.apply(void 0, __spreadArray([{
        modelValue: (__VLS_ctx.requireAddressProof),
    }], __VLS_functionalComponentArgsRest(__VLS_720), false));
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)(__assign({ class: "block text-sm font-medium text-gray-700 mb-1" }));
/** @type {__VLS_StyleScopedClasses['block']} */ ;
/** @type {__VLS_StyleScopedClasses['text-sm']} */ ;
/** @type {__VLS_StyleScopedClasses['font-medium']} */ ;
/** @type {__VLS_StyleScopedClasses['text-gray-700']} */ ;
/** @type {__VLS_StyleScopedClasses['mb-1']} */ ;
var __VLS_724;
/** @ts-ignore @type {typeof __VLS_components.InputNumber} */
inputnumber_1.default;
// @ts-ignore
var __VLS_725 = __VLS_asFunctionalComponent1(__VLS_724, new __VLS_724(__assign({ modelValue: (__VLS_ctx.maxReviewDays), min: (1), max: (30) }, { class: "w-full" })));
var __VLS_726 = __VLS_725.apply(void 0, __spreadArray([__assign({ modelValue: (__VLS_ctx.maxReviewDays), min: (1), max: (30) }, { class: "w-full" })], __VLS_functionalComponentArgsRest(__VLS_725), false));
/** @type {__VLS_StyleScopedClasses['w-full']} */ ;
{
    var __VLS_729 = __VLS_691.slots.footer;
    var __VLS_730 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_731 = __VLS_asFunctionalComponent1(__VLS_730, new __VLS_730(__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })));
    var __VLS_732 = __VLS_731.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Cancel", severity: "secondary" })], __VLS_functionalComponentArgsRest(__VLS_731), false));
    var __VLS_735 = void 0;
    var __VLS_736 = ({ click: {} },
        { onClick: function () {
                var _a = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    _a[_i] = arguments[_i];
                }
                var $event = _a[0];
                __VLS_ctx.showSettingsDialog = false;
                // @ts-ignore
                [showSettingsDialog, showSettingsDialog, autoVerificationEnabled, verificationThreshold, thresholdOptions, highRiskThreshold, mediumRiskThreshold, requireIdVerification, requireAddressProof, maxReviewDays,];
            } });
    var __VLS_733;
    var __VLS_734;
    var __VLS_737 = void 0;
    /** @ts-ignore @type {typeof __VLS_components.Button} */
    button_1.default;
    // @ts-ignore
    var __VLS_738 = __VLS_asFunctionalComponent1(__VLS_737, new __VLS_737(__assign({ 'onClick': {} }, { label: "Save Settings" })));
    var __VLS_739 = __VLS_738.apply(void 0, __spreadArray([__assign({ 'onClick': {} }, { label: "Save Settings" })], __VLS_functionalComponentArgsRest(__VLS_738), false));
    var __VLS_742 = void 0;
    var __VLS_743 = ({ click: {} },
        { onClick: (__VLS_ctx.saveSettings) });
    var __VLS_740;
    var __VLS_741;
    // @ts-ignore
    [saveSettings,];
}
// @ts-ignore
[];
var __VLS_691;
// @ts-ignore
[];
var __VLS_export = (await Promise.resolve().then(function () { return require('vue'); })).defineComponent({});
exports.default = {};
