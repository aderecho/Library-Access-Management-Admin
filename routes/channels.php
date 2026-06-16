<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.rfid-scans', function ($user) {
    return $user->hasPermission('dashboard.view')
        || $user->hasPermission('transactions.view')
        || $user->hasPermission('reports.view');
});
