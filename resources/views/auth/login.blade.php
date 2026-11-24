@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
<style>
    /* Landing page split-screen: informasi aplikasi di kiri, akses login di kanan. */
    .login-screen {
        position: relative;
        isolation: isolate;
        display: grid;
        min-height: 100vh;
        min-height: 100svh;
        place-items: center;
        overflow: hidden;
        padding: clamp(1.25rem, 4vw, 3.75rem);
        background-color: #10283f;
        background-image:
            radial-gradient(circle at 12% 16%, rgba(255, 112, 75, .34), transparent 32%),
            radial-gradient(circle at 88% 82%, rgba(77, 148, 190, .30), transparent 34%),
            linear-gradient(125deg, #10283f 0%, #173d57 52%, #733f42 100%);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .login-background-video {
        position: absolute;
        z-index: -2;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        pointer-events: none;
    }

    .login-screen::before,
    .login-screen::after {
        position: absolute;
        z-index: -1;
        pointer-events: none;
        content: "";
    }

    .login-screen::before {
        inset: 0;
        background:
            linear-gradient(90deg, rgba(5, 17, 31, .76) 0%, rgba(7, 23, 39, .64) 47%, rgba(8, 22, 37, .48) 100%),
            linear-gradient(135deg, rgba(8, 28, 47, .16), rgba(120, 48, 36, .16));
    }

    .login-screen::after {
        width: min(70vw, 52rem);
        aspect-ratio: 1;
        top: -42%;
        right: -20%;
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 50%;
        box-shadow: 0 0 0 3.5rem rgba(255, 255, 255, .022), 0 0 0 7rem rgba(255, 255, 255, .018);
    }

    .login-shell {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(19rem, 22.5rem);
        align-items: center;
        gap: clamp(2.5rem, 6vw, 6.5rem);
        width: min(100%, 76rem);
        margin: auto;
    }

    /* Kolom pengenalan SIMPAN-AWAS */
    .login-branding {
        max-width: 43rem;
        color: #fff;
        animation: login-copy-enter .65s cubic-bezier(.2, .75, .25, 1) both;
    }

    .login-brand-lockup {
        display: flex;
        align-items: center;
        gap: .9rem;
        margin-bottom: clamp(2rem, 5vw, 4.15rem);
    }

    .login-brand-icon {
        display: grid;
        flex: 0 0 auto;
        width: 3.35rem;
        height: 3.35rem;
        place-items: center;
        border: 1px solid rgba(255, 255, 255, .34);
        border-radius: 1.05rem;
        background: rgba(255, 255, 255, .13);
        -webkit-backdrop-filter: blur(12px);
        backdrop-filter: blur(12px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28), 0 .65rem 1.6rem rgba(3, 12, 24, .14);
    }

    .login-brand-icon img {
        width: 2.45rem;
        height: 2.45rem;
        object-fit: contain;
    }

    .login-brand-name {
        color: #fff;
        font-size: 1.03rem;
        font-weight: 850;
        letter-spacing: .055em;
        line-height: 1.2;
    }

    .login-brand-office {
        margin-top: .28rem;
        color: rgba(235, 243, 250, .72);
        font-size: .78rem;
        letter-spacing: .025em;
    }

    .login-hero-title {
        max-width: 43rem;
        margin-top: 0;
        color: #fff;
        font-size: clamp(2.55rem, 5.1vw, 4.65rem);
        font-weight: 850;
        letter-spacing: -.055em;
        line-height: 1.04;
        text-wrap: balance;
        text-shadow: 0 .35rem 2.2rem rgba(0, 0, 0, .16);
    }

    .login-hero-title span {
        color: #fff;
    }

    .login-hero-copy {
        max-width: 36rem;
        margin-top: 1.35rem;
        color: rgba(232, 240, 248, .82);
        font-size: clamp(.92rem, 1.15vw, 1.04rem);
        line-height: 1.8;
    }

    .login-feature-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        max-width: 43rem;
        margin-top: 2.1rem;
    }

    .login-feature {
        min-width: 0;
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 1rem;
        padding: .9rem .85rem;
        background: linear-gradient(145deg, rgba(255, 255, 255, .12), rgba(255, 255, 255, .055));
        -webkit-backdrop-filter: blur(12px) saturate(135%);
        backdrop-filter: blur(12px) saturate(135%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .10);
        transition: transform .2s ease, border-color .2s ease, background .2s ease;
    }

    .login-feature:hover {
        transform: translateY(-3px);
        border-color: rgba(255, 255, 255, .30);
        background: linear-gradient(145deg, rgba(255, 255, 255, .17), rgba(255, 255, 255, .075));
    }

    .login-feature-icon {
        display: grid;
        width: 2rem;
        height: 2rem;
        place-items: center;
        margin-bottom: .65rem;
        border: 1px solid rgba(255, 255, 255, .19);
        border-radius: .65rem;
        background: rgba(255, 112, 75, .18);
        color: #ffb19c;
    }

    .login-feature-icon svg { width: 1.05rem; height: 1.05rem; }

    .login-feature-title,
    .login-feature-note { display: block; }

    .login-feature-title {
        color: #fff;
        font-size: .76rem;
        font-weight: 750;
        line-height: 1.35;
    }

    .login-feature-note {
        margin-top: .28rem;
        color: rgba(226, 236, 246, .66);
        font-size: .66rem;
        line-height: 1.45;
    }

    .login-brand-footer {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem 1rem;
        margin-top: 2.1rem;
        color: rgba(224, 234, 244, .56);
        font-size: .69rem;
        letter-spacing: .025em;
    }

    /* Kartu login compact dengan smoked frosted glass. */
    .login-glass {
        position: relative;
        width: min(100%, 22.5rem);
        justify-self: end;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .43);
        border-radius: 1.45rem;
        padding: 1.55rem;
        background: linear-gradient(140deg, rgba(190, 207, 219, .30), rgba(118, 143, 161, .24));
        -webkit-backdrop-filter: blur(19px) saturate(150%);
        backdrop-filter: blur(19px) saturate(150%);
        box-shadow: 0 1.35rem 3.8rem rgba(2, 10, 20, .24), inset 0 1px 0 rgba(255, 255, 255, .48), inset 0 -1px 0 rgba(255, 255, 255, .16), inset 1px 0 0 rgba(255, 255, 255, .18);
        animation: login-card-enter .65s cubic-bezier(.2, .75, .25, 1) both;
    }

    .login-glass::before {
        position: absolute;
        top: 0;
        left: 8%;
        right: 8%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .95), transparent);
        content: "";
        pointer-events: none;
    }

    .login-glass::after {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background:
            radial-gradient(ellipse at 8% 0%, rgba(255, 255, 255, .13), transparent 42%),
            linear-gradient(145deg, rgba(255, 255, 255, .055), transparent 45%, rgba(255, 255, 255, .025));
        content: "";
        pointer-events: none;
    }

    .login-glass > * {
        position: relative;
        z-index: 1;
    }

    .login-title {
        margin-top: .38rem;
        color: #fff;
        font-size: 1.48rem;
        font-weight: 800;
        letter-spacing: -.035em;
        line-height: 1.2;
    }

    .login-description {
        margin-top: .48rem;
        color: rgba(232, 240, 248, .74);
        font-size: .8rem;
        line-height: 1.6;
    }

    .login-form { margin-top: 1.35rem; }
    .login-form.space-y-5 > :not([hidden]) ~ :not([hidden]) { margin-top: .85rem; }
    .login-form .label { color: rgba(245, 248, 252, .90) !important; font-weight: 700; }

    /* Field username dan password memakai permukaan kaca yang seragam. */
    .login-form .input,
    .login-form input.input {
        min-height: 2.65rem;
        border: 1px solid rgba(255, 255, 255, .27) !important;
        border-radius: .78rem !important;
        background: rgba(228, 237, 244, .16) !important;
        color: rgba(248, 251, 255, .96) !important;
        caret-color: #ffab91;
        -webkit-text-fill-color: rgba(248, 251, 255, .96) !important;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, .08);
        transition: border-color .22s ease, background-color .22s ease, box-shadow .22s ease;
    }

    .login-form .input::placeholder,
    .login-form input.input::placeholder { color: rgba(240, 245, 250, .58) !important; }

    /* Chrome/Edge mengecat autofill dengan biru-putih bawaan. Timpa dengan slate
       yang senada dengan field Password agar username tidak kembali putih. */
    .login-form input[name="username"]:autofill,
    .login-form input[name="username"]:-webkit-autofill,
    .login-form input[name="username"]:-webkit-autofill:hover,
    .login-form input[name="username"]:-webkit-autofill:focus,
    .login-form input[name="username"]:-webkit-autofill:active {
        -webkit-text-fill-color: rgba(248, 251, 255, .98) !important;
        color: rgba(248, 251, 255, .98) !important;
        caret-color: #ffab91 !important;
        background-color: rgb(96, 115, 144) !important;
        background-image: none !important;
        -webkit-box-shadow: inset 0 0 0 1000px rgb(96, 115, 144) !important;
        box-shadow: inset 0 0 0 1000px rgb(96, 115, 144) !important;
        border-color: rgba(255, 255, 255, .27) !important;
        transition: background-color 99999s ease-out 0s;
    }

    .login-form .input:focus,
    .login-form input.input:focus {
        border-color: rgba(255, 151, 119, .88) !important;
        background: rgba(238, 244, 248, .24) !important;
        box-shadow: 0 0 0 3px rgba(255, 112, 75, .19) !important;
    }

        /* Isian otomatis Chrome: samakan username & password dengan gaya kaca (teks tetap putih) */
        .login-form input.input:-webkit-autofill,
    .login-form input.input:-webkit-autofill:hover,
    .login-form input.input:-webkit-autofill:focus,
    .login-form input.input:-webkit-autofill:active {
        -webkit-text-fill-color: #fff !important;
        caret-color: #ffb19c;
        -webkit-box-shadow: 0 0 0 100rem #566170 inset !important;
        box-shadow: 0 0 0 100rem #566170 inset !important;
        border: 1px solid rgba(255, 255, 255, .27) !important;
        transition: background-color 600000s 0s, color 600000s 0s;
    }
    .login-form input.input:-webkit-autofill:focus {
        border-color: rgba(255, 151, 119, .88) !important;
        -webkit-box-shadow: 0 0 0 100rem #5d6978 inset, 0 0 0 3px rgba(255, 112, 75, .19) !important;
    }

    .login-form .btn-primary {
        min-height: 2.7rem;
        border: 1px solid #f15a35 !important;
        border-radius: .8rem !important;
        background: linear-gradient(135deg, #ff7957, #ed4b28) !important;
        box-shadow: 0 .55rem 1.2rem rgba(237, 75, 40, .25);
        color: #fff !important;
        font-weight: 750;
        letter-spacing: .01em;
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .login-form .btn-primary:hover {
        filter: brightness(1.04);
        box-shadow: 0 .75rem 1.5rem rgba(237, 75, 40, .34);
        transform: translateY(-1px);
    }

    .login-form .btn-primary:active { transform: translateY(0) scale(.99); }
    .login-password button { color: rgba(241, 246, 251, .68) !important; }
    .login-password button:hover { color: #ffab91 !important; }

    .login-footer {
        margin-top: 1.2rem;
        border-top: 1px solid rgba(255, 255, 255, .20);
        padding-top: .8rem;
        color: rgba(235, 242, 249, .58);
        font-size: .68rem;
        text-align: center;
    }

    @keyframes login-card-enter {
        from { opacity: 0; transform: translateY(14px) scale(.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes login-copy-enter {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 900px) {
        .login-screen { padding: 2rem 1.25rem; }
        .login-shell {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            gap: 2rem;
            width: min(100%, 34rem);
        }
        .login-branding { width: 100%; text-align: center; }
        .login-brand-lockup { justify-content: center; margin-bottom: 1.5rem; }
        .login-hero-title { margin-left: auto; margin-right: auto; font-size: clamp(2.2rem, 7vw, 3.35rem); }
        .login-hero-copy { margin-left: auto; margin-right: auto; }
        .login-feature-grid { margin: 1.5rem auto 0; }
        .login-brand-footer { justify-content: center; margin-top: 1.25rem; }
        .login-glass { justify-self: center; }
    }

    @media (max-width: 560px) {
        .login-screen { padding: 1.35rem 1rem; }
        .login-shell { gap: 1.45rem; }
        .login-brand-lockup { margin-bottom: 1.2rem; }
        .login-brand-icon { width: 2.85rem; height: 2.85rem; }
        .login-brand-icon img { width: 2.1rem; height: 2.1rem; }
        .login-hero-title { margin-top: .8rem; font-size: clamp(2rem, 9vw, 2.7rem); }
        .login-hero-copy { margin-top: .8rem; font-size: .86rem; line-height: 1.6; }
        .login-feature-grid { grid-template-columns: 1fr; gap: .55rem; margin-top: 1.1rem; }
        .login-feature { display: flex; align-items: center; gap: .75rem; padding: .7rem .8rem; text-align: left; }
        .login-feature-icon { flex: 0 0 auto; margin: 0; }
        .login-feature-note { margin-top: .1rem; }
        .login-brand-footer { margin-top: 1rem; }
        .login-glass { width: min(100%, 22rem); border-radius: 1.25rem; padding: 1.3rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-branding, .login-glass { animation: none; }
        .login-feature, .login-form .input, .login-form .btn-primary { transition: none; }
    }

    /* Fallback untuk browser yang tidak mendukung backdrop-filter. */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .login-glass { background: rgba(108, 132, 150, .88); }
        .login-feature { background: rgba(255, 255, 255, .13); }
    }
</style>

<main class="login-screen" @if ($latarUrl && !($latarIsVideo ?? false)) style="background-image: url('{{ $latarUrl }}')" @endif>
    @if ($latarUrl && ($latarIsVideo ?? false))
        <video class="login-background-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true" tabindex="-1">
            <source src="{{ $latarUrl }}" type="{{ $latarMimeType ?? 'video/mp4' }}">
        </video>
    @endif

    <div class="login-shell">
        <section class="login-branding" aria-labelledby="landing-heading">
            <div class="login-brand-lockup">
                <span class="login-brand-icon">
                    <img src="{{ asset('img/ikon.png') }}" alt="">
                </span>
                <div>
                    <p class="login-brand-name">SIMPAN-AWAS</p>
                    <p class="login-brand-office">Bawaslu Kabupaten Banyumas</p>
                </div>
            </div>

            <h1 id="landing-heading" class="login-hero-title">KELOLA DATA PENANGANAN PELANGGARAN DAN PENYELESAIAN SENGKETA PROSES PEMILU <span>DALAM SATU SISTEM.</span></h1>
            <p class="login-hero-copy">
                SIMPAN-AWAS membantu pengelolaan dokumen Penanganan Pelanggaran dan Penyelesaian Sengketa secara lebih tertata, terpusat, dan mudah ditelusuri.
            </p>

            <div class="login-feature-grid" aria-label="Fitur utama SIMPAN-AWAS">
                <article class="login-feature">
                    <span class="login-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <span>
                        <span class="login-feature-title">Penanganan Pelanggaran</span>
                        <span class="login-feature-note">Pengelolaan laporan dan temuan</span>
                    </span>
                </article>
                <article class="login-feature">
                    <span class="login-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 6h14M7 6l-3 7h6L7 6Zm10 0-3 7h6l-3-7ZM8 21h8"/></svg>
                    </span>
                    <span>
                        <span class="login-feature-title">Penyelesaian Sengketa</span>
                        <span class="login-feature-note">Dokumen permohonan dan verifikasi</span>
                    </span>
                </article>
                <article class="login-feature">
                    <span class="login-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6H10l2 2h7.5A1.5 1.5 0 0 1 21 9.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5v-11Z"/><path d="M3 10h18"/></svg>
                    </span>
                    <span>
                        <span class="login-feature-title">Arsip Terpusat</span>
                        <span class="login-feature-note">Dokumen tersimpan lebih tertata</span>
                    </span>
                </article>
            </div>

            <p class="login-brand-footer">
                <span>&copy; {{ date('Y') }} Bawaslu Kabupaten Banyumas</span>
                <span aria-hidden="true">•</span>
                <span>Sistem informasi internal</span>
            </p>
        </section>

        <section class="login-glass" aria-labelledby="login-heading">
            <h2 id="login-heading" class="login-title">Masuk ke akun Anda</h2>
            <p class="login-description">Gunakan username dan password yang diberikan admin untuk mengakses sistem.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="login-form space-y-5" data-once>
                @csrf
                <div>
                    <label for="username" class="label">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}"
                           class="input @error('username') border-red-400 @enderror"
                           autocomplete="username" autocapitalize="none" spellcheck="false" autofocus required>
                    @error('username') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div class="login-password">
                    <label for="password" class="label">Password</label>
                    @include('partials.password', ['id' => 'password', 'name' => 'password', 'auto' => 'current-password', 'required' => true])
                    @error('password') <p class="err">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full py-2.5">Masuk</button>
            </form>

            <p class="login-footer">Akses hanya untuk pengguna terdaftar.</p>
        </section>
    </div>
</main>

@if ($latarUrl && ($latarIsVideo ?? false))
    <script>
        (function () {
            var video = document.querySelector('.login-background-video');
            if (!video) return;

            // Hormati preferensi pengguna yang meminta gerakan lebih sedikit.
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                video.pause();
                video.removeAttribute('autoplay');
            }

            // Autoplay background video harus tetap tanpa suara dan inline.
            video.muted = true;
            video.defaultMuted = true;
            video.playsInline = true;
            var playAttempt = video.play();
            if (playAttempt && typeof playAttempt.catch === 'function') {
                playAttempt.catch(function () { /* Background tetap memiliki fallback warna. */ });
            }
        })();
    </script>
@endif
@endsection
