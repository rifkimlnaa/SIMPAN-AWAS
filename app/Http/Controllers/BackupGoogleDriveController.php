<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\GoogleDriveBackupService;
use App\Services\BackupPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class BackupGoogleDriveController extends Controller
{
    public function connect(Request $request, GoogleDriveBackupService $drive): RedirectResponse
    {
        if (! $drive->isConfigured()) {
            return back()->with('error', 'OAuth Google Drive belum dikonfigurasi. Isi GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, dan GOOGLE_DRIVE_REDIRECT_URI di .env.');
        }
        $state = Str::random(64);
        $request->session()->put('backup_google_oauth_state', $state);
        $request->session()->put('backup_google_oauth_user', $request->user()->id);
        return redirect()->away($drive->authorizationUrl($state));
    }

    public function callback(Request $request, GoogleDriveBackupService $drive): RedirectResponse
    {
        $expected = (string) $request->session()->pull('backup_google_oauth_state', '');
        $expectedUser = (int) $request->session()->pull('backup_google_oauth_user', 0);
        $actual = (string) $request->query('state', '');
        if ($expected === '' || $expectedUser !== (int) $request->user()->id || ! hash_equals($expected, $actual)) {
            return redirect()->route('backup.index')->with('error', 'Verifikasi OAuth Google tidak cocok atau sudah kedaluwarsa. Silakan hubungkan ulang.');
        }
        if ($request->filled('error')) {
            return redirect()->route('backup.index')->with('error', 'Koneksi Google Drive dibatalkan: '.$request->query('error'));
        }
        $code = (string) $request->query('code', '');
        if ($code === '') return redirect()->route('backup.index')->with('error', 'Google tidak mengirim authorization code.');

        try {
            $drive->completeAuthorization($code);
            return redirect()->route('backup.index')->with('success', 'Google Drive berhasil dihubungkan. Backup sekarang dapat dikirim ke folder SIMPAN-AWAS Backup.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('backup.index')->with('error', $exception->getMessage());
        }
    }

    public function disconnect(GoogleDriveBackupService $drive): RedirectResponse
    {
        $drive->disconnect();
        return redirect()->route('backup.index')->with('success', 'Koneksi Google Drive diputus. File backup yang sudah terkirim di Google Drive tidak dihapus.');
    }

    public function upload(Backup $backup, GoogleDriveBackupService $drive, BackupPackageService $packages): RedirectResponse
    {
        try {
            $result = $drive->uploadBackup($backup, $packages);
            return redirect()->route('backup.index')->with('success', 'Backup berhasil dikirim ke Google Drive: '.$result['name']);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('backup.index')->with('error', $exception->getMessage());
        }
    }
}
