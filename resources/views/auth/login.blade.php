@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden flex-col justify-between overflow-hidden bg-brand-900 bg-cover bg-center p-12 text-brand-100 lg:flex"
         @if ($latarUrl) style="background-image: url('{{ $latarUrl }}')" @endif>
        @if ($latarUrl)
            {{-- Lapisan gelap agar teks tetap terbaca di atas foto --}}
            <div class="absolute inset-0 bg-gradient-to-t from-brand-900/90 via-brand-900/60 to-brand-900/70" aria-hidden="true"></div>
        @endif

        <div class="relative flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white p-1.5"><img src="{{ asset('img/ikon.png') }}" alt="Logo Bawaslu" class="h-full w-full object-contain"></span>
            <span class="text-sm font-semibold text-white">Bawaslu Kabupaten Banyumas</span>
        </div>
        <div class="relative max-w-md">
            <h2 class="text-4xl font-bold leading-tight text-white">{{ config('arsip.nama') }}</h2>
            <p class="mt-2 text-lg font-medium leading-snug text-white/90">{{ config('arsip.nama_lengkap') }}</p>
            <p class="mt-5 text-sm leading-relaxed text-brand-100">Arsip permohonan penyelesaian sengketa proses pemilu (PSAP dan PSPP) Divisi HP3S, tersimpan rapi dan mudah dicari.</p>
        </div>
        <p class="relative text-xs text-brand-100">&copy; {{ date('Y') }} Bawaslu Kabupaten Banyumas</p>
    </div>

    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <img src="{{ asset('img/logo.png') }}" alt="Logo Bawaslu Kabupaten Banyumas" class="mb-8 h-14 w-auto dark:rounded-lg dark:bg-white dark:p-2">
            <p class="text-sm font-semibold text-brand-700">{{ config('arsip.nama') }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Masuk ke akun Anda</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan username dan password yang diberikan admin.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-5" data-once>
                @csrf
                <div>
                    <label for="username" class="label">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}"
                           class="input @error('username') border-red-400 @enderror"
                           autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required>
                    @error('username') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    @include('partials.password', ['id' => 'password', 'name' => 'password', 'auto' => 'current-password', 'required' => true])
                    @error('password') <p class="err">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full py-2.5">Masuk</button>
            </form>
        </div>
    </div>
</div>
@endsection
