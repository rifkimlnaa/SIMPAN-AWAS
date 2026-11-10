@extends('layouts.app')

@php($edit = $petugas->exists)
@section('title', $edit ? 'Edit Petugas' : 'Tambah Petugas')

@section('content')
<form method="POST" action="{{ $edit ? route('petugas.update', $petugas) : route('petugas.store') }}" data-once autocomplete="off"
      class="card mx-auto max-w-xl space-y-5 p-6">
    @csrf
    @if ($edit)
        @method('PUT')
    @endif

    <div>
        <label for="name" class="label">Nama lengkap <span class="text-red-600">*</span></label>
        <input id="name" name="name" type="text" maxlength="100" required autofocus
               value="{{ old('name', $petugas->name) }}" class="input @error('name') border-red-400 @enderror">
        @error('name')<p class="err">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="username" class="label">Username <span class="text-red-600">*</span></label>
        <input id="username" name="username" type="text" maxlength="50" required autocapitalize="none" spellcheck="false"
               value="{{ old('username', $petugas->username) }}" class="input @error('username') border-red-400 @enderror">
        <p class="hint">Huruf, angka, strip (-), dan garis bawah (_). Minimal 4 karakter.</p>
        @error('username')<p class="err">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="password" class="label">Password @unless ($edit)<span class="text-red-600">*</span>@endunless</label>
            @include('partials.password', ['id' => 'password', 'name' => 'password', 'required' => ! $edit])
            @error('password')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Ulangi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
        </div>
    </div>
    <p class="hint !mt-0">{{ $edit ? 'Kosongkan jika tidak ingin mengganti. ' : '' }}Minimal 8 karakter, kombinasi huruf dan angka.</p>

    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
        <a class="btn btn-light" href="{{ route('petugas.index') }}">Batal</a>
        <button type="submit" class="btn btn-primary"><i data-lucide="check" class="h-4 w-4"></i> Simpan</button>
    </div>
</form>
@endsection
