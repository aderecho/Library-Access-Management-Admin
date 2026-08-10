<?php

namespace App\Services;

use App\Models\User;

class AdminDestination
{
    /**
     * @var array<string, string>
     */
    private const PAGES = [
        'dashboard.view' => 'admin.dashboard',
        'entry-monitor.view' => 'admin.entry-monitor',
        'transactions.view' => 'admin.transactions.index',
        'reports.view' => 'admin.reports.index',
        'advertisements.view' => 'admin.advertisements.index',
        'users.view' => 'admin.users.index',
        'roles.view' => 'admin.roles.index',
        'scanner-tokens.view' => 'admin.scanner-tokens.index',
        'branches.view' => 'admin.branches.index',
    ];

    public function urlFor(User $user): ?string
    {
        foreach (self::PAGES as $permission => $routeName) {
            if ($user->hasPermission($permission)) {
                return route($routeName);
            }
        }

        return null;
    }
}
