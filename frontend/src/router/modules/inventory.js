"use strict";
// frontend/src/router/modules/inventory.ts
Object.defineProperty(exports, "__esModule", { value: true });
var inventoryRoutes = [
    {
        path: '/inventory',
        component: function () { return Promise.resolve().then(function () { return require('../../layouts/InventoryLayout.vue'); }); },
        name: 'inventory',
        redirect: '/inventory/dashboard',
        meta: {
            requiresAuth: true,
            module: 'inventory',
            permission: 'inventory.dashboard.view',
        },
        children: [
            // ==================== DASHBOARD ====================
            {
                path: 'dashboard',
                name: 'inventory.dashboard',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/InventoryDashboard.vue'); }); },
                meta: {
                    title: 'Inventory Dashboard',
                    permission: 'inventory.dashboard.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Dashboard' },
                    ],
                },
            },
            // ==================== BRANCH INVENTORY ====================
            {
                path: 'items',
                name: 'inventory.items',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Items/ItemsIndex.vue'); }); },
                meta: {
                    title: 'Branch Inventory',
                    permission: 'inventory.items.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Branch Inventory' },
                    ],
                },
            },
            // ==================== STOCK ADJUSTMENTS ====================
            {
                path: 'adjustments',
                name: 'inventory.adjustments',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Adjustments/AdjustmentIndex.vue'); }); },
                meta: {
                    title: 'Stock Adjustments',
                    permission: 'inventory.adjustments.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Adjustments' },
                    ],
                },
            },
            {
                path: 'adjustments/create',
                name: 'inventory.adjustments.create',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Adjustments/AdjustmentCreate.vue'); }); },
                meta: {
                    title: 'Create Stock Adjustment',
                    permission: 'inventory.adjustments.create',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Adjustments', to: '/inventory/adjustments' },
                        { label: 'Create' },
                    ],
                },
            },
            {
                path: 'adjustments/:id',
                name: 'inventory.adjustments.detail',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Adjustments/AdjustmentDetail.vue'); }); },
                meta: {
                    title: 'Adjustment Details',
                    permission: 'inventory.adjustments.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Adjustments', to: '/inventory/adjustments' },
                        { label: 'Details' },
                    ],
                },
            },
            // ==================== STOCK TRANSFERS ====================
            {
                path: 'transfers',
                name: 'inventory.transfers',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Transfers/TransferIndex.vue'); }); },
                meta: {
                    title: 'Stock Transfers',
                    permission: 'inventory.transfers.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Transfers' },
                    ],
                },
            },
            {
                path: 'transfers/create',
                name: 'inventory.transfers.create',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Transfers/TransferCreate.vue'); }); },
                meta: {
                    title: 'Create Stock Transfer',
                    permission: 'inventory.transfers.create',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Transfers', to: '/inventory/transfers' },
                        { label: 'Create' },
                    ],
                },
            },
            {
                path: 'transfers/:id',
                name: 'inventory.transfers.detail',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Transfers/TransferDetail.vue'); }); },
                meta: {
                    title: 'Transfer Details',
                    permission: 'inventory.transfers.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Transfers', to: '/inventory/transfers' },
                        { label: 'Details' },
                    ],
                },
            },
            // ==================== STOCK ALERTS ====================
            {
                path: 'alerts',
                name: 'inventory.alerts',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Alerts/AlertsIndex.vue'); }); },
                meta: {
                    title: 'Stock Alerts',
                    permission: 'inventory.alerts.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Stock Alerts' },
                    ],
                },
            },
            // ==================== INVENTORY TRANSACTIONS ====================
            {
                path: 'transactions',
                name: 'inventory.transactions',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Transactions/TransactionIndex.vue'); }); },
                meta: {
                    title: 'Inventory Transactions',
                    permission: 'inventory.transactions.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Transactions' },
                    ],
                },
            },
            // ==================== REPORTS ====================
            {
                path: 'reports',
                name: 'inventory.reports',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Reports/ReportsIndex.vue'); }); },
                meta: {
                    title: 'Inventory Reports',
                    permission: 'inventory.reports.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Reports' },
                    ],
                },
            },
            // ==================== NOTIFICATIONS ====================
            {
                path: 'notifications',
                name: 'inventory.notifications',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Notifications/NotificationIndex.vue'); }); },
                meta: {
                    title: 'Notifications',
                    permission: 'inventory.notifications.view',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Notifications' },
                    ],
                },
            },
            // ==================== CONFIGURATION ====================
            {
                path: 'configuration',
                name: 'inventory.configuration',
                component: function () { return Promise.resolve().then(function () { return require('../../views/system/inventory/Configuration/ConfigIndex.vue'); }); },
                meta: {
                    title: 'Inventory Configuration',
                    permission: 'inventory.configuration.manage',
                    breadcrumb: [
                        { label: 'Inventory', to: '/inventory' },
                        { label: 'Configuration' },
                    ],
                },
            },
        ],
    },
];
exports.default = inventoryRoutes;
