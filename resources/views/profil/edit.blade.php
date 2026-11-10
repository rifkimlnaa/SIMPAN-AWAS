@extends('layouts.app')
@section('title', 'Profil & Keamanan')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <p class="text-sm text-slate-500">Kelola data akun, foto profil, dan password Anda.</p>

    <section class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Foto profil</h2>
        <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start">
            @include('partials.avatar', ['user' => $user, 'size' => 'lg'])

            <form method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data" data-once class="min-w-0 flex-1">
                @csrf
                <label for="foto" class="label">Unggah foto baru</label>
                <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp" required
                       class="file-input @error('foto') border-red-400 @enderror">
                <p class="hint">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                @error('foto')<p class="err">{{ $message }}</p>@enderror
                <button type="submit" class="btn btn-primary btn-sm mt-3"><i data-lucide="upload" class="h-4 w-4"></i> Unggah foto</button>
            </form>

            @if ($user->foto)
                <form method="POST" action="{{ route('profil.foto.destroy') }}" class="js-confirm-delete" data-message="Foto profil Anda akan dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger-ghost btn-sm"><i data-lucide="trash-2" class="h-4 w-4"></i> Hapus foto</button>
                </form>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ route('profil.update') }}" data-once class="card space-y-5 p-6">
        @csrf
        @method('PUT')
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Data profil</h2>
            <span class="badge badge-gray">{{ $user->roleLabel() }}</span>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Nama lengkap <span class="text-red-600">*</span></label>
                <input id="name" name="name" type="text" maxlength="100" required
                       value="{{ old('name', $user->name) }}" class="input @error('name') border-red-400 @enderror">
                @error('name')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="label">Username <span class="text-red-600">*</span></label>
                <input id="username" name="username" type="text" maxlength="50" required autocapitalize="none" spellcheck="false"
                       value="{{ old('username', $user->username) }}" class="input @error('username') border-red-400 @enderror">
                @error('username')<p class="err">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="btn btn-primary"><i data-lucide="check" class="h-4 w-4"></i> Simpan profil</button>
        </div>
    </form>

    <form method="POST" action="{{ route('profil.password') }}" data-once class="card space-y-5 p-6">
        @csrf
        @method('PUT')
        <h2 class="text-base font-semibold text-slate-900">Ganti password</h2>

        <div>
            <label for="current_password" class="label">Password saat ini <span class="text-red-600">*</span></label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                   class="input @error('current_password') border-red-400 @enderror">
            @error('current_password')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="new_password" class="label">Password baru <span class="text-red-600">*</span></label>
                @include('partials.password', ['id' => 'new_password', 'name' => 'password', 'required' => true])
                @error('password')<p class="err">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi password baru <span class="text-red-600">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="input">
            </div>
        </div>
        <p class="hint !mt-0">Minimal 8 karakter, kombinasi huruf dan angka.</p>

        <div class="flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="btn btn-primary"><i data-lucide="shield-check" class="h-4 w-4"></i> Ganti password</button>
        </div>
    </form>
</div>
@endsection
