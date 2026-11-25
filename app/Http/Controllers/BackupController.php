<?php

namespace App\Http\Controllers;

use App\Services\CpanelStorageService;
use App\Services\MediaBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class BackupController extends Controller
{
    public function index(MediaBackupService $media): View
    {
        $p = $media->pengaturan();

        return view('backup.index', [
            'p' => $p,
            'labelJadwal' => $media->labelJadwal($p),
            'berikutnya' => $media->jadwalBerikutnya($p),
            'storageUsageUrl' => route('backup.storage-usage'),
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
        ]);

        $media->simpanPengaturan($data['frekuensi'], $data['jam'], (int) ($data['hari'] ?? 0), (int) ($data['tanggal'] ?? 1));

        return back()->with('success', 'Jadwal backup disimpan.');
    }

    /** Status untuk pengecekan otomatis dari browser admin. */
    public function status(MediaBackupService $media): JsonResponse
    {
        return response()->json(['due' => $media->jatuhTempo()])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    /** Unduh manual (tombol). */
    public function unduh(MediaBackupService $media)
    {
        try {
            $zip = $media->buatZip();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $this->pesan($e));
        }

        $media->catatUnduhan();

        return $this->kirim($zip);
    }

    /** Unduh otomatis sesuai jadwal; hanya berjalan sekali per jadwal. */
    public function otomatis(MediaBackupService $media)
    {
        if (! $media->klaimJadwal()) {
            return new Response('', 204);
        }

        try {
            return $this->kirim($media->buatZip());
        } catch (Throwable $e) {
            report($e);

            return new Response('', 500);
        }
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
