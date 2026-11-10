@extends('layouts.app')

@php($edit = $kategori->exists)
@section('title', $edit ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<form method="POST" action="{{ $edit ? route('kategori.update', $kategori) : route('kategori.store') }}" data-once
      class="card mx-auto max-w-xl space-y-5 p-6">
    @csrf
    @if ($edit)
        @method('PUT')
    @endif

    <div>
        <label for="nama" class="label">Nama kategori <span class="text-red-600">*</span></label>
        <input id="nama" name="nama" type="text" maxlength="100" required autofocus
               value="{{ old('nama', $kategori->nama) }}" class="input @error('nama') border-red-400 @enderror">
        @error('nama')<p class="err">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="keterangan" class="label">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="3" maxlength="500"
                  class="input @error('keterangan') border-red-400 @enderror">{{ old('keterangan', $kategori->keterangan) }}</textarea>
        @error('keterangan')<p class="err">{{ $message }}</p>@enderror
    </div>

    @if ($edit)
        <div>
            <span class="label">Jenis isian arsip</span>
            <p class="text-sm text-slate-700">{{ $kategori->jenisLabel() }}</p>
            <p class="hint">Jenis isian tidak dapat diubah setelah kategori dibuat.</p>
        </div>
    @else
        <fieldset>
            <legend class="label">Jenis isian arsip <span class="text-red-600">*</span></legend>
            @php($jenisDipilih = old('jenis', \App\Models\Kategori::JENIS_SEDERHANA))
            <div class="space-y-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="jenis" value="sederhana" class="mt-1 h-4 w-4 text-brand-700 focus:ring-brand-500" @checked($jenisDipilih === 'sederhana')>
                    <span>
                        <span class="block text-sm font-medium text-slate-900">Sederhana</span>
                        <span class="block text-xs text-slate-500">Isian: judul, keterangan, tanggal, dan berkas yang dilampirkan.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="radio" name="jenis" value="formulir" class="mt-1 h-4 w-4 text-brand-700 focus:ring-brand-500" @checked($jenisDipilih === 'formulir')>
                    <span>
                        <span class="block text-sm font-medium text-slate-900">Formulir lengkap</span>
                        <span class="block text-xs text-slate-500">Seluruh kolom Formulir Laporan seperti PSAP dan PSPP, termasuk cetak formulir PDF.</span>
                    </span>
                </label>
            </div>
            <p class="hint">Jenis isian tidak dapat diubah setelah kategori dibuat.</p>
            @error('jenis')<p class="err">{{ $message }}</p>@enderror
        </fieldset>
    @endif

    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
        <a class="btn btn-light" href="{{ route('kategori.index') }}">Batal</a>
        <button type="submit" class="btn btn-primary"><i data-lucide="check" class="h-4 w-4"></i> Simpan</button>
    </div>
</form>
@endsection
