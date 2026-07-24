<?php

use App\Http\Controllers\Api\RfidScanController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\ScannerRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['scanner.token', 'throttle:120,1'])->group(function () {
    Route::get('/scanner/validate', [ScannerRegistrationController::class, 'show'])->name('api.scanner.validate');
    Route::post('/rfid/scan', [RfidScanController::class, 'store'])->name('api.rfid.scan');
    Route::get('/advertisements', [AdvertisementController::class, 'index'])->name('api.advertisements.index');
});
