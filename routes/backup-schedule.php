<?php

use App\Services\BackupService;
use App\Services\BackupPackageService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
})->purpose('Membuat backup terenkripsi SIMPAN-AWAS');

Artisan::command('backup:cleanup', function (BackupService $service): int {
    $deleted = $service->cleanup();
    $this->info("Pembersihan selesai. {$deleted} backup lama dihapus.");
    return 0;
})->purpose('Menghapus backup otomatis sesuai kebijakan retensi');

Schedule::command('backup:create --type=daily')->dailyAt('01:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('backup:create --type=weekly')->weeklyOn(0, '02:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('backup:create --type=monthly')->monthlyOn(1, '03:00')->timezone('Asia/Jakarta')->withoutOverlapping();
Schedule::command('backup:cleanup')->dailyAt('04:00')->timezone('Asia/Jakarta')->withoutOverlapping();


Artisan::command('backup:import-zip {file} {--disk=}', function (BackupPackageService $packages, BackupService $service): int {
    $file = (string) $this->argument('file');
    $disk = $this->option('disk') ?: config('backup.disk', 'local');
    try {
        $backup = $packages->importZip($file, $service, (string) $disk);
        $this->info('Backup berhasil didaftarkan: '.$backup->filename);
        $this->comment('Selanjutnya pulihkan melalui menu Backup & Pemulihan atau backup:restore-file.');
        return 0;
    } catch (\Throwable $exception) {
        $this->error('Impor ZIP gagal: '.$exception->getMessage());
        return 1;
    }
})->purpose('Impor paket ZIP SIMPAN-AWAS dan daftarkan snapshot terenkripsi');

Artisan::command('backup:restore-file {path} {--disk=}', function (BackupService $service): int {
    $disk = $this->option('disk') ?: config('backup.disk', 'local');
    $path = (string) $this->argument('path');

    $this->warn('PERINGATAN: database dan folder storage aplikasi akan diganti sesuai snapshot.');
    if (! $this->confirm('Daftarkan dan pulihkan '.$path.' dari disk '.$disk.'?', false)) {
        $this->comment('Pemulihan dibatalkan.');
        return 1;
    }

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
})->purpose('Pulihkan backup terenkripsi dari file pada disk yang dikonfigurasi');
