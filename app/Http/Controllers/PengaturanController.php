<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengaturan tampilan. Halaman dapat dibuka semua pengguna (mode terang/gelap),
 * sedangkan mengubah media latar login hanya untuk admin (lihat routes/web.php).
 */
class PengaturanController extends Controller
{
    public function edit(Request $request): View
    {
        $isAdmin = $request->user()->isAdmin();

        return view('pengaturan.edit', [
            'latarUrl' => $isAdmin ? Pengaturan::latarLoginUrl() : null,
            'latarIsVideo' => $isAdmin && Pengaturan::latarLoginIsVideo(),
            'latarMimeType' => $isAdmin ? Pengaturan::latarLoginMimeType() : null,
        ]);
    }

    public function updateLatarLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'latar' => [
                'required',
                'file',
                function ($attribute, $file, $fail) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $mime = strtolower((string) $file->getMimeType());
                    $imageTypes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'jfif' => 'image/jpeg',
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                    ];
                    $videoTypes = [
                        'mp4' => ['video/mp4', 'application/mp4'],
                        'webm' => ['video/webm'],
                    ];

                    if (array_key_exists($extension, $imageTypes)) {
                        if ($file->getSize() > 10 * 1024 * 1024) {
                            $fail('Ukuran gambar maksimal 10 MB.');
                            return;
                        }

                        if ($mime !== $imageTypes[$extension]) {
                            $fail('Isi file tidak cocok dengan format gambar yang dipilih.');
                        }

                        return;
                    }

                    if (array_key_exists($extension, $videoTypes)) {
                        if ($file->getSize() > 100 * 1024 * 1024) {
                            $fail('Ukuran video maksimal 100 MB.');
                            return;
                        }

                        if (! in_array($mime, $videoTypes[$extension], true)) {
                            $fail('Isi file tidak cocok dengan format video yang dipilih.');
                        }

                        return;
                    }

                    $fail('Format file tidak didukung. Gunakan JFIF, JPG, PNG, WEBP, MP4, atau WEBM.');
                },
            ],
        ], [
            'latar.required' => 'Pilih gambar atau video latar terlebih dahulu.',
            'latar.file' => 'File latar tidak valid atau gagal diunggah.',
        ], [
            'latar' => 'media latar login',
        ]);

        // Laravel memberi nama acak pada file; tipe aslinya tetap terdeteksi dari MIME.
        $baru = $request->file('latar')->store('pengaturan', 'public');
        $lama = Pengaturan::ambil(Pengaturan::LATAR_LOGIN);

        Pengaturan::simpan(Pengaturan::LATAR_LOGIN, $baru);

        if ($lama && $lama !== $baru) {
            Storage::disk('public')->delete($lama);
        }

        return back()->with('success', 'Media latar halaman login berhasil diperbarui.');
    }

    public function destroyLatarLogin(): RedirectResponse
    {
        $lama = Pengaturan::ambil(Pengaturan::LATAR_LOGIN);

        if ($lama) {
            Storage::disk('public')->delete($lama);
        }

        Pengaturan::simpan(Pengaturan::LATAR_LOGIN, null);

        return back()->with('success', 'Media latar dihapus. Halaman login menggunakan latar bawaan.');
    }
}
