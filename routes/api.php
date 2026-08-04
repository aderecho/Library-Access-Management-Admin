<?php

use App\Http\Controllers\Api\RfidScanController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\ScannerRegistrationController;
use App\Http\Controllers\Api\ScannerSettingsSsoController;
use Illuminate\Support\Facades\Route;

Route::prefix('/scanner/settings/sso')->middleware('throttle:20,1')->group(function () {
    Route::post('/', [ScannerSettingsSsoController::class, 'start'])->name('api.scanner.settings.sso.start');
    Route::get('/{requestId}', [ScannerSettingsSsoController::class, 'status'])
        ->whereAlphaNumeric('requestId')
        ->name('api.scanner.settings.sso.status');
});

Route::middleware(['scanner.token', 'throttle:120,1'])->group(function () {
    Route::get('/scanner/validate', [ScannerRegistrationController::class, 'show'])->name('api.scanner.validate');
    Route::post('/rfid/scan', [RfidScanController::class, 'store'])->name('api.rfid.scan');
    Route::get('/advertisements', [AdvertisementController::class, 'index'])->name('api.advertisements.index');
});
