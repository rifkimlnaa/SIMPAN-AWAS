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
            'kategoris' => Kategori::withCount('arsips')->orderBy('nama')->get(),
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
        $jumlah = $kategori->arsips()->count();

        if ($jumlah > 0) {
            return back()->with('error', "Kategori \"{$kategori->nama}\" tidak bisa dihapus karena masih memiliki {$jumlah} arsip.");
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Kategori $kategori = null): array
    {
        $rules = [
            'nama' => ['required', 'string', 'max:100', Rule::unique('kategoris', 'nama')->ignore($kategori?->id)],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];

        // Jenis isian ditentukan saat kategori dibuat dan tidak bisa diubah lagi
        // (struktur datanya berbeda antara formulir lengkap dan sederhana).
        if (! $kategori) {
            $rules['jenis'] = ['required', Rule::in([Kategori::JENIS_FORMULIR, Kategori::JENIS_SEDERHANA])];
        }

        $data = $request->validate($rules, [], ['jenis' => 'jenis isian arsip']);

        $data['slug'] = Str::slug($data['nama']);

        $bentrok = $data['slug'] === '' || Kategori::where('slug', $data['slug'])
            ->when($kategori, fn ($q) => $q->where('id', '!=', $kategori->id))
            ->exists();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'nama' => 'Nama kategori tidak valid atau terlalu mirip dengan kategori lain.',
            ]);
        }

        return $data;
    }
}
