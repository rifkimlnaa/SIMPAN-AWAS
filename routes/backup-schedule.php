<?php

use App\Services\BackupService;
use App\Services\BackupPackageService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

$runBackup = static function (string $type): void {
    try {
        $backup = app(BackupService::class)->create($type);
        Log::info('SIMPAN-AWAS scheduled backup completed.', ['type' => $type, 'backup_id' => $backup->id, 'filename' => $backup->filename]);

        $drive = app(GoogleDriveBackupService::class);
        if (config('backup.google_drive.auto_upload', true) && $drive->isConnected()) {
            try {
                $drive->uploadBackup($backup, app(BackupPackageService::class));
            } catch (\Throwable $exception) {
                Log::error('SIMPAN-AWAS scheduled Google Drive upload failed.', ['backup_id' => $backup->id, 'message' => $exception->getMessage()]);
            }
        }
    } catch (\Throwable $exception) {
        report($exception);
        Log::error('SIMPAN-AWAS scheduled backup failed.', ['type' => $type, 'message' => $exception->getMessage()]);
    }
};

Artisan::command('backup:create {--type=daily}', function (BackupService $service, GoogleDriveBackupService $drive, BackupPackageService $packages): int {
    $type = (string) $this->option('type');
    if (! in_array($type, ['daily', 'weekly', 'monthly', 'manual', 'pre-restore'], true)) {
        $this->error('Jenis backup tidak dikenal.');
        return 1;
    }
    try {
        $backup = $service->create($type);
        $this->info('Backup berhasil: '.$backup->filename.' ('.$backup->sizeForHumans().')');
        if (config('backup.google_drive.auto_upload', true) && $drive->isConnected()) {
            try {
                $uploaded = $drive->uploadBackup($backup, $packages);
                $this->info('Salinan Google Drive berhasil: '.$uploaded['name']);
            } catch (\Throwable $driveException) {
                report($driveException);
                $this->warn('Backup lokal aman, tetapi upload Google Drive gagal: '.$driveException->getMessage());
            }
        }
        return 0;
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Backup gagal: '.$exception->getMessage());
        return 1;
    }
})->purpose('Membuat backup terenkripsi SIMPAN-AWAS tanpa proses shell');

Artisan::command('backup:cleanup', function (BackupService $service): int {
    $deleted = $service->cleanup();
    $this->info("Pembersihan selesai. {$deleted} backup lama dihapus.");
    return 0;
})->purpose('Menghapus backup otomatis sesuai kebijakan retensi');

Artisan::command('backup:import-zip {file} {--disk=}', function (BackupPackageService $packages, BackupService $service): int {
    try {
        $backup = $packages->importZip((string) $this->argument('file'), $service, (string) ($this->option('disk') ?: config('backup.disk', 'local')));
        $this->info('Backup berhasil didaftarkan: '.$backup->filename);
        return 0;
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Impor ZIP gagal: '.$exception->getMessage());
        return 1;
    }
})->purpose('Impor paket ZIP SIMPAN-AWAS');

Artisan::command('backup:restore-file {path} {--disk=}', function (BackupService $service): int {
    $disk = $this->option('disk') ?: config('backup.disk', 'local');
    $path = (string) $this->argument('path');
    $this->warn('PERINGATAN: database dan folder storage aplikasi akan diganti sesuai snapshot.');
    if (! $this->confirm('Daftarkan dan pulihkan '.$path.' dari disk '.$disk.'?', false)) return 1;
    try {
        $backup = $service->registerBackupFile($path, (string) $disk);
    } catch (\Throwable $exception) {
        $this->error('File backup tidak dapat didaftarkan: '.$exception->getMessage());
        return 1;
    }
    $maintenanceEnabled = false;
    try {
        Artisan::call('down', ['--retry' => 60]);
        $maintenanceEnabled = true;
        $safety = $service->restore($backup, null);
        $this->info('Pemulihan selesai. Backup pengaman: '.$safety->filename);
        return 0;
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Pemulihan gagal: '.$exception->getMessage());
        return 1;
    } finally {
        if ($maintenanceEnabled) {
            try { Artisan::call('up'); } catch (\Throwable $exception) { report($exception); }
        }
    }
})->purpose('Pulihkan backup terenkripsi dari disk yang dikonfigurasi');

// Callback dijalankan langsung di proses schedule:run; tidak memakai Schedule::command/Symfony Process.
Schedule::call(fn () => $runBackup('daily'))->dailyAt('01:00')->name('backup-harian')->timezone('Asia/Jakarta')->withoutOverlapping(180);
Schedule::call(fn () => $runBackup('weekly'))->weeklyOn(0, '02:00')->name('backup-mingguan')->timezone('Asia/Jakarta')->withoutOverlapping(360);
Schedule::call(fn () => $runBackup('monthly'))->monthlyOn(1, '03:00')->name('backup-bulanan')->timezone('Asia/Jakarta')->withoutOverlapping(720);
Schedule::call(function (): void {
    try { app(BackupService::class)->cleanup(); }
    catch (\Throwable $exception) { report($exception); Log::error('SIMPAN-AWAS scheduled backup cleanup failed.', ['message' => $exception->getMessage()]); }
})->name('backup-bersihkan')->dailyAt('04:00')->timezone('Asia/Jakarta')->withoutOverlapping(60);
