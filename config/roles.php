<?php

return [
    'abilities' => [
        'default' => [
            'view-profile',
            'update-profile',
            'change-password',
        ],
        
        1 => [ // Super Admin
            'manage-users',
            'manage-roles',
            'manage-stores',
            'view-all-reports',
            'system-settings',
        ],
        
        2 => [ // Store Admin
            'manage-store-users',
            'view-store-reports',
            'manage-inventory',
            'manage-orders',
            'manage-employees',
        ],
        
        3 => [ // HR Manager
            'manage-employees',
            'view-attendance',
            'process-payroll',
            'manage-leaves',
        ],
        
        4 => [ // Sales Staff
            'create-sales',
            'view-products',
            'process-payments',
            'view-sales-history',
        ],
        
        5 => [ // Inventory Clerk
            'manage-stock',
            'update-inventory',
            'view-low-stock',
            'process-receivings',
        ],
    ],
];