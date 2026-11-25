<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('backup')->name('backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::get('/storage-usage', [BackupController::class, 'storageUsage'])->middleware('throttle:30,1')->name('storage-usage');
    Route::post('/unduh-zip', [BackupController::class, 'unduhZip'])->middleware('throttle:3,1')->name('unduh-zip');
    Route::post('/', [BackupController::class, 'store'])->middleware('throttle:3,1')->name('store');
    Route::get('/{backup}/download', [BackupController::class, 'download'])->whereNumber('backup')->name('download');
    Route::post('/{backup}/restore', [BackupController::class, 'restore'])->whereNumber('backup')->middleware('throttle:5,1')->name('restore');
    Route::delete('/{backup}', [BackupController::class, 'destroy'])->whereNumber('backup')->middleware('throttle:5,1')->name('destroy');
});
