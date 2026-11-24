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

    @if ($edit && $kategori->isUtama())
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            Ini <strong>kategori utama</strong>. Nama dan keterangan boleh diubah, tetapi kategori tidak bisa dihapus dan
            jenis arsipnya (dropdown) tetap.
        </div>
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

    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
        <a class="btn btn-light" href="{{ route('kategori.index') }}">Batal</a>
        <button type="submit" class="btn btn-primary"><i data-lucide="check" class="h-4 w-4"></i> Simpan</button>
    </div>
</form>
@endsection
