<?php

namespace App\Http\Controllers;

use App\Services\CpanelStorageService;
use App\Services\DriveUploader;
use App\Services\MediaBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class BackupController extends Controller
{
    public function index(MediaBackupService $media, DriveUploader $drive): View
    {
        $p = $media->pengaturan();

        return view('backup.index', [
            'p' => $p,
            'labelJadwal' => $media->labelJadwal($p),
            'berikutnya' => $media->jadwalBerikutnya($p),
            'storageUsageUrl' => route('backup.storage-usage'),
            'driveSiap' => $drive->isConfigured(),
            'driveTerhubung' => $drive->isConfigured() && $drive->isConnected(),
            'redirectUri' => (string) config('services.google_drive.redirect_uri'),
        ]);
    }

    public function storageUsage(Request $request, CpanelStorageService $service): JsonResponse
    {
        $usage = $service->usage($request->boolean('refresh'));

        return response()->json($usage, $usage['available'] ? 200 : 503)
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function jadwal(Request $request, MediaBackupService $media): RedirectResponse
    {
        $data = $request->validate([
            'frekuensi' => ['required', 'in:off,harian,mingguan,bulanan'],
            'jam' => ['required', 'date_format:H:i'],
            'hari' => ['nullable', 'integer', 'between:0,6'],
            'tanggal' => ['nullable', 'integer', 'between:1,28'],
            'tujuan' => ['required', 'in:unduh,drive,keduanya'],
        ]);

        $media->simpanPengaturan($data['frekuensi'], $data['jam'], (int) ($data['hari'] ?? 0), (int) ($data['tanggal'] ?? 1), $data['tujuan']);

        return back()->with('success', 'Jadwal backup disimpan.');
    }

    /** Status untuk pengecekan otomatis dari browser admin. */
    public function status(MediaBackupService $media): JsonResponse
    {
        return response()->json(['due' => $media->jatuhTempo()])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    /** Tombol manual: buat ZIP, kirim ke Drive dan/atau unduh sesuai pilihan. */
    public function unduh(MediaBackupService $media, DriveUploader $drive)
    {
        try {
            $zip = $media->buatZip();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $this->pesan($e));
        }

        $media->catatUnduhan();
        $pesanDrive = $this->keDrive($media, $drive, $zip);

        if (! $media->kirimUnduhan()) {
            return back()->with($pesanDrive['ok'] ? 'success' : 'error', $pesanDrive['teks']);
        }

        return $this->kirim($zip);
    }

    /** Unduh/kirim otomatis sesuai jadwal; hanya berjalan sekali per jadwal. */
    public function otomatis(MediaBackupService $media, DriveUploader $drive)
    {
        if (! $media->klaimJadwal()) {
            return new Response('', 204);
        }

        try {
            $zip = $media->buatZip();
        } catch (Throwable $e) {
            report($e);

            return new Response('', 500);
        }

        $this->keDrive($media, $drive, $zip);

        return $media->kirimUnduhan() ? $this->kirim($zip) : new Response('', 204);
    }

    /** @return array{ok:bool,teks:string} */
    private function keDrive(MediaBackupService $media, DriveUploader $drive, string $zip): array
    {
        if (! $media->kirimKeDrive()) {
            return ['ok' => true, 'teks' => 'Backup selesai.'];
        }

        try {
            $hasil = $drive->upload($zip, basename($zip));
            $teks = 'Backup terkirim ke Google Drive: '.$hasil['name'];
            $media->catatDrive(true, $teks);

            return ['ok' => true, 'teks' => $teks];
        } catch (Throwable $e) {
            report($e);
            $teks = 'Gagal kirim ke Google Drive: '.$this->pesan($e);
            $media->catatDrive(false, $teks);

            return ['ok' => false, 'teks' => $teks];
        }
    }

    public function driveHubungkan(Request $request, DriveUploader $drive): RedirectResponse
    {
        if (! $drive->isConfigured()) {
            return back()->with('error', 'Google Drive belum diatur di .env (GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, GOOGLE_DRIVE_REDIRECT_URI).');
        }
        $state = Str::random(64);
        $request->session()->put('backup_drive_state', $state);

        return redirect()->away($drive->authorizationUrl($state));
    }

    public function driveCallback(Request $request, DriveUploader $drive): RedirectResponse
    {
        $harap = (string) $request->session()->pull('backup_drive_state', '');
        if ($harap === '' || ! hash_equals($harap, (string) $request->query('state', ''))) {
            return redirect()->route('backup.index')->with('error', 'Verifikasi Google tidak cocok atau kedaluwarsa. Coba hubungkan lagi.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('backup.index')->with('error', 'Koneksi Google Drive dibatalkan.');
        }

        try {
            $drive->completeAuthorization((string) $request->query('code'));

            return redirect()->route('backup.index')->with('success', 'Google Drive berhasil dihubungkan.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('backup.index')->with('error', $this->pesan($e));
        }
    }

    public function drivePutus(DriveUploader $drive): RedirectResponse
    {
        $drive->disconnect();

        return back()->with('success', 'Koneksi Google Drive diputus. File yang sudah terkirim tidak dihapus.');
    }

    private function kirim(string $zip)
    {
        return response()->download($zip, basename($zip), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function pesan(Throwable $e): string
    {
        return mb_substr($e instanceof RuntimeException ? $e->getMessage() : 'Terjadi kesalahan internal. Periksa log aplikasi.', 0, 350);
    }
}
