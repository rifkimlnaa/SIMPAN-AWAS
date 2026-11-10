<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengaturan tampilan. Halaman dapat dibuka semua pengguna (mode terang/gelap),
 * sedangkan mengubah foto latar login hanya untuk admin (lihat routes/web.php).
 */
class PengaturanController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pengaturan.edit', [
            'latarUrl' => $request->user()->isAdmin() ? Pengaturan::latarLoginUrl() : null,
        ]);
    }

    public function updateLatarLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'latar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], ['latar' => 'foto latar login']);

        // Nama berkas diacak oleh Laravel; foto lama dihapus agar tidak menumpuk.
        $baru = $request->file('latar')->store('pengaturan', 'public');
        $lama = Pengaturan::ambil(Pengaturan::LATAR_LOGIN);

        Pengaturan::simpan(Pengaturan::LATAR_LOGIN, $baru);

        if ($lama) {
            Storage::disk('public')->delete($lama);
        }

        return back()->with('success', 'Foto latar halaman login berhasil diperbarui.');
    }

    public function destroyLatarLogin(): RedirectResponse
    {
        $lama = Pengaturan::ambil(Pengaturan::LATAR_LOGIN);

        if ($lama) {
            Storage::disk('public')->delete($lama);
        }

        Pengaturan::simpan(Pengaturan::LATAR_LOGIN, null);

        return back()->with('success', 'Foto latar dihapus. Halaman login kembali berlatar biru polos.');
    }
}
