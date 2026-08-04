<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('branches.{branchId}.rfid-scans', function ($user, int $branchId) {
    return $user->hasPermission('transactions.view') && $user->canAccessBranch($branchId);
});
