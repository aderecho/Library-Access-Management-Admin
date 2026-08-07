<?php

return [
    'groups' => [
        'Access monitoring' => [
            'dashboard.view' => 'View dashboard and analytics',
            'entry-monitor.view' => 'View the live entry monitor',
            'transactions.view' => 'View and search RFID transactions',
            'reports.view' => 'View usage reports',
            'reports.export' => 'Export reports as CSV',
        ],
        'Advertisement management' => [
            'advertisements.view' => 'View published advertisements',
            'advertisements.create' => 'Create and edit image or video advertisements',
        ],
        'User management' => [
            'users.view' => 'View user accounts',
            'users.create' => 'Create user accounts',
            'users.update' => 'Edit user accounts',
        ],
        'Role management' => [
            'roles.view' => 'View roles and permissions',
            'roles.create' => 'Create roles',
            'roles.update' => 'Edit roles and assign permissions',
        ],
        'Scanner management' => [
            'scanner-tokens.view' => 'View registered scanner applications',
            'scanner-tokens.create' => 'Generate scanner registration tokens',
            'scanner-tokens.update' => 'Activate, deactivate, and regenerate scanner tokens',
        ],
        'Branch management' => [
            'branches.view' => 'View library branches',
            'branches.create' => 'Create library branches',
            'branches.update' => 'Edit and deactivate library branches',
        ],
    ],
];
