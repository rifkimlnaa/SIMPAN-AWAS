<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\BackupGoogleDriveController;
use App\Http\Controllers\BackupStorageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('backup')->name('backup.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::get('/storage-usage', BackupStorageController::class)->middleware('throttle:30,1')->name('storage-usage');
    Route::get('/google/connect', [BackupGoogleDriveController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [BackupGoogleDriveController::class, 'callback'])->name('google.callback');
    Route::post('/google/disconnect', [BackupGoogleDriveController::class, 'disconnect'])->name('google.disconnect');
    Route::post('/{backup}/google-drive', [BackupGoogleDriveController::class, 'upload'])->whereNumber('backup')->middleware('throttle:3,1')->name('google.upload');
    Route::post('/', [BackupController::class, 'store'])->middleware('throttle:3,1')->name('store');
    Route::get('/{backup}/download', [BackupController::class, 'download'])->whereNumber('backup')->name('download');
    Route::post('/{backup}/restore', [BackupController::class, 'restore'])->whereNumber('backup')->middleware('throttle:5,1')->name('restore');
    Route::delete('/{backup}', [BackupController::class, 'destroy'])->whereNumber('backup')->middleware('throttle:5,1')->name('destroy');
});
