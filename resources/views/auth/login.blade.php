@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<main class="tb-login">
    <section class="tb-login-main" aria-labelledby="login-heading">
        <div class="tb-login-box">
            <div class="tb-login-brand">
                <img src="{{ asset('img/ikon.png') }}" alt="Logo Bawaslu">
                <span>{{ config('arsip.nama') }}</span>
            </div>

            <h1 id="login-heading" class="tb-login-title">Masuk ke akun Anda</h1>

            <div class="card">
                <div class="card-body">
                    <form data-turbo="false" method="POST" action="{{ route('login.attempt') }}" data-once>
                        @csrf
                        <div class="mb-4">
                            <label for="username" class="label">Username</label>
                            <input id="username" name="username" type="text" value="{{ old('username') }}"
                                   class="input @error('username') border-red-400 @enderror"
                                   autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required>
                            @error('username') <p class="err">{{ $message }}</p> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="password" class="label">Password</label>
                            @include('partials.password', ['id' => 'password', 'name' => 'password', 'auto' => 'current-password', 'required' => true])
                            @error('password') <p class="err">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Masuk</button>
                    </form>
                </div>
            </div>

            <p class="tb-login-note">Akses hanya untuk pengguna terdaftar. Hubungi admin bila belum memiliki akun.</p>
        </div>
    </section>

    <aside class="tb-login-cover {{ $latarUrl ? '' : 'plain' }}" @if ($latarUrl && ! ($latarIsVideo ?? false)) style="background-image: url('{{ $latarUrl }}')" @endif aria-hidden="{{ $latarUrl ? 'true' : 'false' }}">
        @if ($latarUrl && ($latarIsVideo ?? false))
            <video autoplay muted loop playsinline preload="metadata" tabindex="-1">
                <source src="{{ $latarUrl }}" type="{{ $latarMimeType ?? 'video/mp4' }}">
            </video>
        @endif
        <div class="tb-login-cover-text">
            <h2>Kelola data penanganan pelanggaran dan penyelesaian sengketa proses Pemilu dalam satu sistem.</h2>
            <p>Dokumen tersimpan lebih tertata, terpusat, dan mudah ditelusuri oleh Bawaslu Kabupaten Banyumas.</p>
        </div>
    </aside>
</main>

@if ($latarUrl && ($latarIsVideo ?? false))
    <script>
        (function () {
            var video = document.querySelector('.tb-login-cover video');
            if (!video) return;
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                video.pause();
                video.removeAttribute('autoplay');
            }
            video.muted = true;
            video.defaultMuted = true;
            video.playsInline = true;
            var p = video.play();
            if (p && typeof p.catch === 'function') p.catch(function () { /* latar tetap berwarna polos */ });
        })();
    </script>
@endif
@endsection
