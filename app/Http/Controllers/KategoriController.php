<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        return view('kategori.index', [
            'kategoris' => Kategori::withCount('arsips')->urut()->get(),
        ]);
    }

    public function create(): View
    {
        return view('kategori.form', ['kategori' => new Kategori]);
    }

    public function store(Request $request): RedirectResponse
    {
        Kategori::create($this->validated($request));

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori): View
    {
        return view('kategori.form', ['kategori' => $kategori]);
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($this->validated($request, $kategori));

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        // Dua kategori utama (Penanganan Pelanggaran & Penyelesaian Sengketa) tidak boleh dihapus.
        if ($kategori->isUtama()) {
            return back()->with('error', "Kategori utama \"{$kategori->nama}\" tidak bisa dihapus.");
        }

        $jumlah = $kategori->arsips()->count();

        if ($jumlah > 0) {
            return back()->with('error', "Kategori \"{$kategori->nama}\" tidak bisa dihapus karena masih memiliki {$jumlah} arsip.");
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Kategori $kategori = null): array
    {
        // Kategori tambahan hanya punya nama dan keterangan (tanpa pilihan jenis arsip).
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('kategoris', 'nama')->ignore($kategori?->id)],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $data['slug'] = Str::slug($data['nama']);

        $bentrok = $data['slug'] === '' || Kategori::where('slug', $data['slug'])
            ->when($kategori, fn ($q) => $q->where('id', '!=', $kategori->id))
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'nama' => 'Nama kategori tidak valid atau terlalu mirip dengan kategori lain.',
            ]);
        }

        // Kategori baru selalu berisian sederhana. Jenis kategori utama tidak pernah diubah dari sini.
        if (! $kategori) {
            $data['jenis'] = Kategori::JENIS_SEDERHANA;
        }

        return $data;
    }
}
