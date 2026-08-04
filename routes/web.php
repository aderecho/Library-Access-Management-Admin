<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScannerTokenController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ScannerSettingsSsoController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('login.google');
    Route::get(config('services.google.callback_path'), [AuthController::class, 'handleGoogleCallback'])->name('login.google.callback');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/scanner/settings/authorize/{requestId}', [ScannerSettingsSsoController::class, 'authorizeRequest'])
    ->whereAlphaNumeric('requestId')
    ->middleware('auth')
    ->name('scanner.settings.authorize');

Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
        Route::get('/entry-monitor', [TransactionController::class, 'monitor'])->middleware('permission:transactions.view')->name('entry-monitor');
        Route::get('/advertisements', [AdvertisementController::class, 'index'])->middleware('permission:advertisements.view')->name('advertisements.index');
        Route::post('/advertisements', [AdvertisementController::class, 'store'])->middleware('permission:advertisements.create')->name('advertisements.store');
        Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions.view')->name('transactions.index');
        Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->middleware('permission:reports.export')->name('reports.export-excel');

        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');

        Route::patch('/roles/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:roles.update')->name('roles.permissions.update');
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');

        Route::get('/scanner-tokens', [ScannerTokenController::class, 'index'])->middleware('permission:scanner-tokens.view')->name('scanner-tokens.index');
        Route::post('/scanner-tokens', [ScannerTokenController::class, 'store'])->middleware('permission:scanner-tokens.create')->name('scanner-tokens.store');
        Route::put('/scanner-tokens/{scannerToken}', [ScannerTokenController::class, 'update'])->middleware('permission:scanner-tokens.update')->name('scanner-tokens.update');
        Route::post('/scanner-tokens/{scannerToken}/regenerate', [ScannerTokenController::class, 'regenerate'])->middleware('permission:scanner-tokens.update')->name('scanner-tokens.regenerate');
        Route::get('/branches', [BranchController::class, 'index'])->middleware('permission:branches.view')->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->middleware('permission:branches.create')->name('branches.store');
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->middleware('permission:branches.update')->name('branches.update');
    });
