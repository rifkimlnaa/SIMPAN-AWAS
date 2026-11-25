<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\CpanelStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::query()->with(['creator', 'restorer'])->latest()->paginate(15);
        $disk = (string) config('backup.disk', 'local');
        $keyReady = strlen((string) config('backup.encryption_key', '')) >= 32;

        return view('backup.index', [
            'backups' => $backups,
            'diskName' => $disk,
            'keyReady' => $keyReady,
            'totalComplete' => Backup::query()->where('status', 'complete')->count(),
            'totalFailed' => Backup::query()->where('status', 'failed')->count(),
            'lastBackup' => Backup::query()->where('status', 'complete')->latest('completed_at')->first(),
            'storageUsageUrl' => route('backup.storage-usage'),
        ]);
    }

    /**
     * Mengambil statistik kuota disk akun cPanel melalui UAPI StatsBar.
     * Endpoint ini hanya dapat diakses Admin dan tidak pernah mengirim token ke browser.
     */
    public function storageUsage(Request $request, CpanelStorageService $service): JsonResponse
    {
        $usage = $service->usage($request->boolean('refresh'));

        return response()->json($usage, $usage['available'] ? 200 : 503)
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function store(Request $request, BackupService $service): RedirectResponse
    {
        try {
            $backup = $service->create('manual', $request->user()->id);

            return back()->with('success', 'Backup berhasil dibuat: '.$backup->filename);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $this->safeMessage($exception));
        }
    }

    /** Satu klik: langsung unduh ZIP berisi database + semua berkas arsip. */
    public function unduhZip(BackupService $service)
    {
        try {
            $zip = $service->createDownloadZip();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $this->safeMessage($exception));
        }

        return response()->download($zip, basename($zip), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    public function download(Backup $backup)
    {
        abort_unless($backup->isComplete() && $backup->path, 404);
        abort_unless(Storage::disk($backup->disk)->exists($backup->path), 404);

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function restore(Request $request, Backup $backup, BackupService $service): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'in:PULIHKAN DATA'],
        ], [
            'confirmation.in' => 'Ketik PULIHKAN DATA persis untuk mengonfirmasi pemulihan.',
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Password administrator tidak sesuai.'])->withInput();
        }

        $maintenanceEnabled = false;
        try {
            // Stop new web requests while the database and storage are being replaced.
            Artisan::call('down', ['--retry' => 60]);
            $maintenanceEnabled = true;

            $safety = $service->restore($backup, $request->user()->id);

            return redirect()->route('backup.index')->with(
                'success',
                'Pemulihan selesai. Backup pengaman sebelum pemulihan: '.$safety->filename.'. Silakan masuk kembali jika sesi berakhir.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', $this->safeMessage($exception));
        } finally {
            if ($maintenanceEnabled) {
                try {
                    Artisan::call('up');
                } catch (Throwable $maintenanceException) {
                    Log::critical('Gagal mengaktifkan kembali aplikasi setelah proses restore.', [
                        'exception' => $maintenanceException,
                    ]);
                }
            }
        }
    }

    public function destroy(Request $request, Backup $backup, BackupService $service): RedirectResponse
    {
        abort_if($backup->status === 'running', 409, 'Backup yang sedang berjalan tidak dapat dihapus.');

        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check((string) $request->input('current_password'), $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Password administrator tidak sesuai.']);
        }

        try {
            $service->delete($backup);

            return back()->with('success', 'Backup berhasil dihapus.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup tidak dapat dihapus. Periksa log aplikasi.');
        }
    }

    private function safeMessage(Throwable $exception): string
    {
        $message = $exception instanceof RuntimeException ? $exception->getMessage() : 'Terjadi kesalahan internal. Periksa log aplikasi.';
        return mb_substr($message, 0, 350);
    }
}
