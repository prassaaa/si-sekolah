<?php

use App\Http\Controllers\Api\RfidScanController;
use Illuminate\Support\Facades\Route;

Route::middleware('rfid.device')->group(function () {
    Route::post('/rfid/scan', [RfidScanController::class, 'store'])->name('api.rfid.scan');
});
