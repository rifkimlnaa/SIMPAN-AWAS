<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('backup')->name('backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::get('/storage-usage', [BackupController::class, 'storageUsage'])->middleware('throttle:30,1')->name('storage-usage');
    Route::put('/jadwal', [BackupController::class, 'jadwal'])->name('jadwal');
    Route::get('/status', [BackupController::class, 'status'])->name('status');
    Route::post('/unduh', [BackupController::class, 'unduh'])->name('unduh');
    Route::get('/google/hubungkan', [BackupController::class, 'driveHubungkan'])->name('google.hubungkan');
    Route::get('/google/callback', [BackupController::class, 'driveCallback'])->name('google.callback');
    Route::post('/google/putus', [BackupController::class, 'drivePutus'])->name('google.putus');
    Route::post('/otomatis', [BackupController::class, 'otomatis'])->name('otomatis');
});
