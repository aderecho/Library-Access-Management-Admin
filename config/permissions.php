<?php

return [
    'groups' => [
        'Access monitoring' => [
            'dashboard.view' => 'View dashboard and analytics',
            'transactions.view' => 'View and search RFID transactions',
            'reports.view' => 'View usage reports',
            'reports.export' => 'Export reports as CSV',
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
    ],
];
