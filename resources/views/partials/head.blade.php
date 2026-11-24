<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Tema terang/gelap: dijalankan paling awal agar halaman tidak berkedip. Pilihan disimpan per browser.
     Bawaan saat pertama dibuka adalah TERANG. Mode gelap hanya aktif bila pengguna memilihnya di Pengaturan. --}}
<script>
    (function () {
        function dipilih() {
            try { return localStorage.getItem('tema') || 'terang'; } catch (e) { return 'terang'; }
        }
        window.terapkanTema = function () {
            var t = dipilih();
            var gelap = t === 'gelap' || (t === 'sistem' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', gelap);
            return gelap;
        };
        // t: 'terang' (bawaan) | 'gelap' | 'sistem' (ikuti pengaturan perangkat; harus dipilih sendiri oleh pengguna)
        window.setTema = function (t) {
            try {
                localStorage.setItem('tema', t);
            } catch (e) { /* penyimpanan diblokir: tema berlaku sementara */ }
            return window.terapkanTema();
        };
        window.temaDipilih = dipilih;
        window.terapkanTema();
        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var ubah = function () { if (dipilih() === 'sistem') window.terapkanTema(); };
            if (mq.addEventListener) mq.addEventListener('change', ubah); else if (mq.addListener) mq.addListener(ubah);
        }
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Skala default desktop setara tampilan browser 80%, tanpa mengubah zoom browser.
       Pada layar kecil ukuran normal dipertahankan agar teks tetap nyaman dibaca. */
    html { font-size: 80%; }
    @media (max-width: 767px) {
        html { font-size: 100%; }
    }
</style>

<style>
    /* Palet warna sebagai variabel (RGB) agar bisa berganti terang/gelap. */
    :root {
        --surface: 255 255 255;
        --s50: 248 249 252; --s100: 243 245 249; --s200: 229 233 240; --s300: 210 216 226; --s400: 148 156 171;
        --s500: 105 113 128; --s600: 75 83 98; --s700: 48 56 72; --s800: 30 38 53; --s900: 20 27 41;
        --b50: 255 243 239; --b500: 255 91 55; --b600: 244 76 40; --b700: 224 65 31; --b800: 199 52 23;
    }
    html.dark {
        color-scheme: dark;
        --surface: 15 23 42;
        --s50: 8 13 26; --s100: 30 41 59; --s200: 45 58 80; --s300: 71 85 105; --s400: 100 116 139;
        --s500: 148 163 184; --s600: 170 183 201; --s700: 203 213 225; --s800: 226 232 240; --s900: 241 245 249;
        --b50: 75 35 29; --b500: 255 119 91; --b600: 255 103 72; --b700: 255 91 55; --b800: 237 75 42;
    }
</style>

<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: {
                    surface: 'rgb(var(--surface) / <alpha-value>)',
                    slate: {
                        50: 'rgb(var(--s50) / <alpha-value>)', 100: 'rgb(var(--s100) / <alpha-value>)',
                        200: 'rgb(var(--s200) / <alpha-value>)', 300: 'rgb(var(--s300) / <alpha-value>)',
                        400: 'rgb(var(--s400) / <alpha-value>)', 500: 'rgb(var(--s500) / <alpha-value>)',
                        600: 'rgb(var(--s600) / <alpha-value>)', 700: 'rgb(var(--s700) / <alpha-value>)',
                        800: 'rgb(var(--s800) / <alpha-value>)', 900: 'rgb(var(--s900) / <alpha-value>)',
                    },
                    brand: {
                        50: 'rgb(var(--b50) / <alpha-value>)',
                        100: '#e1eaf4', 200: '#c3d4e8',
                        500: 'rgb(var(--b500) / <alpha-value>)', 600: 'rgb(var(--b600) / <alpha-value>)',
                        700: 'rgb(var(--b700) / <alpha-value>)', 800: 'rgb(var(--b800) / <alpha-value>)',
                        900: '#0e1f33',
                    },
                },
            },
        },
    };
</script>
<style type="text/tailwindcss">
    @layer components {
        .card { @apply rounded-xl border border-slate-200 bg-surface; }
        .btn { @apply inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60; }
        .btn-sm { @apply px-3 py-1.5 text-xs; }
        .btn-primary { @apply bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-500; }
        .btn-light { @apply border border-slate-300 bg-surface text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400; }
        .btn-danger-ghost { @apply border border-transparent text-red-600 hover:bg-red-50 focus-visible:ring-red-400; }
        .btn-icon { @apply inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-surface text-slate-600 transition hover:bg-slate-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500; }
        .btn-icon-danger { @apply hover:border-red-200 hover:bg-red-50 hover:text-red-600; }
        .input { @apply block w-full rounded-lg border border-slate-300 bg-surface px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20; }
        .label { @apply mb-2 block text-sm font-medium text-slate-700; }
        .hint { @apply mt-1.5 text-xs leading-relaxed text-slate-500; }
        .err { @apply mt-1.5 text-xs text-red-600; }
        .badge { @apply inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium bg-brand-50 text-brand-700; }
        .badge-gray { @apply bg-slate-100 text-slate-600; }
        .file-input { @apply block w-full cursor-pointer rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200; }
    }
</style>
<style>
    [x-cloak] { display: none !important; }
    body { line-height: 1.65; }
    dd, dt { line-height: 1.6; }
    .no-scroll { overflow: hidden; }

    .preview-box { display: flex; height: 26rem; align-items: center; justify-content: center; overflow: hidden; border-radius: .5rem; border: 1px dashed rgb(var(--s300)); background: rgb(var(--s50)); color: rgb(var(--s500)); font-size: .875rem; text-align: center; }
    .preview-box.preview-solid { border-style: solid; background: rgb(var(--surface)); }
    .preview-box iframe, .preview-box img { width: 100%; height: 100%; border: 0; object-fit: contain; }

    /* DataTables */
    .dt-container { font-size: .875rem; }
    .dt-container .dt-search input,
    .dt-container .dt-length select { border: 1px solid rgb(var(--s300)); border-radius: .5rem; padding: .375rem .625rem; background: rgb(var(--surface)); color: rgb(var(--s800)); outline: none; }
    .dt-container .dt-search input:focus { border-color: rgb(var(--b500)); box-shadow: 0 0 0 3px rgb(var(--b500) / .2); }
    .dt-container .dt-paging .dt-paging-button { border-radius: .5rem !important; color: rgb(var(--s700)) !important; }
    .dt-container .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) { background: rgb(var(--s100)) !important; border-color: transparent !important; color: rgb(var(--s900)) !important; }
    .dt-container .dt-paging .dt-paging-button.disabled { opacity: .45; }
    .dt-container .dt-paging .dt-paging-button.current { background: #1c3b5e !important; color: #fff !important; border-color: #1c3b5e !important; }
    html.dark .dt-container .dt-paging .dt-paging-button.current { background: rgb(var(--b700)) !important; border-color: rgb(var(--b700)) !important; }
    table.dataTable thead th, table.dataTable tbody td { padding: .9rem 1rem !important; line-height: 1.55; }
    table.dataTable thead th { font-weight: 600; color: rgb(var(--s600)); border-bottom: 1px solid rgb(var(--s200)) !important; white-space: nowrap; }
    table.dataTable tbody td { border-bottom: 1px solid rgb(var(--s100)); vertical-align: middle; }
    table.dataTable.no-footer { border-bottom: 0; }

    /* Penyesuaian khusus mode gelap */
    html.dark .text-brand-700 { color: #ff967e; }
    html.dark .badge, html.dark .btn-icon:hover { color: #ff967e; }
    html.dark .badge-gray { color: rgb(var(--s600)); }
    html.dark .text-red-600, html.dark .btn-danger-ghost { color: #f87171; }
    html.dark .text-red-700 { color: #fca5a5; }
    html.dark .bg-red-50, html.dark .btn-danger-ghost:hover, html.dark .btn-icon-danger:hover { background-color: rgb(127 29 29 / .3); }
    html.dark .border-red-200, html.dark .btn-icon-danger:hover { border-color: rgb(153 27 27 / .7); }
    html.dark .btn-icon-danger:hover, html.dark .hover\:text-red-600:hover { color: #f87171; }
</style>

<style>
    /* SIMPAN-AWAS visual refresh: presentation only; no route, form or data behavior changes. */
    :root {
        --ui-accent: #ff5b37;
        --ui-accent-strong: #ed4b28;
        --ui-accent-soft: #fff1ec;
        --ui-line: #e9edf3;
        --ui-shadow: 0 .125rem .3125rem rgba(20, 27, 41, .025), 0 .75rem 1.75rem rgba(20, 27, 41, .035);
    }

    body { background: #f7f8fb; color: #303849; }

    .card {
        border: .0625rem solid var(--ui-line) !important;
        border-radius: 1rem !important;
        background: #fff !important;
        box-shadow: var(--ui-shadow);
    }

    .btn { border-radius: .625rem; font-weight: 600; transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
    .btn-primary {
        background: var(--ui-accent) !important;
        color: #fff !important;
        border: .0625rem solid var(--ui-accent) !important;
        box-shadow: 0 .25rem .625rem rgba(255, 91, 55, .16);
    }
    .btn-primary:hover { background: var(--ui-accent-strong) !important; border-color: var(--ui-accent-strong) !important; box-shadow: 0 .375rem .875rem rgba(255, 91, 55, .22); transform: translateY(-1px); }
    .btn-primary:active { transform: translateY(0); }
    .btn-light { border-color: #e4e8ef !important; background: #fff !important; }
    .btn-light:hover { background: #f8f9fc !important; border-color: #d8deea !important; }
    .btn-icon { border-color: #e7ebf1 !important; background: #fff !important; color: #737d8d !important; }
    .btn-icon:hover { border-color: #ffd3c6 !important; background: #fff4f0 !important; color: var(--ui-accent-strong) !important; }

    .input, select.input, textarea.input {
        border-color: #dfe4ec !important;
        border-radius: .625rem !important;
        background: #fff !important;
        box-shadow: 0 .0625rem .125rem rgba(20, 27, 41, .025);
    }
    .input:focus, select.input:focus, textarea.input:focus {
        border-color: var(--ui-accent) !important;
        box-shadow: 0 0 0 .1875rem rgba(255, 91, 55, .12) !important;
    }
    .label { color: #3e4758; font-weight: 600; }
    .badge { border-radius: 999px; background: var(--ui-accent-soft) !important; color: #df512f !important; font-weight: 600; }
    .badge-gray { background: #f1f3f7 !important; color: #667085 !important; }

    /* Tables and search controls. */
    .dt-container { color: #555f70; }
    .dt-container .dt-search input, .dt-container .dt-length select {
        border: .0625rem solid #e0e5ed !important;
        border-radius: .5625rem !important;
        padding: .48rem .7rem !important;
        background: #fff !important;
        color: #303849 !important;
    }
    .dt-container .dt-search input:focus { border-color: var(--ui-accent) !important; box-shadow: 0 0 0 .1875rem rgba(255, 91, 55, .12) !important; }
    .dt-container .dt-paging .dt-paging-button { border-radius: .5rem !important; color: #626d7e !important; }
    .dt-container .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) { background: #fff1ec !important; border-color: transparent !important; color: #df512f !important; }
    .dt-container .dt-paging .dt-paging-button.current { background: var(--ui-accent) !important; color: #fff !important; border-color: var(--ui-accent) !important; box-shadow: 0 .1875rem .5rem rgba(255, 91, 55, .18); }
    table.dataTable thead th, table.dataTable tbody td { padding: .9rem 1rem !important; line-height: 1.55; }
    table.dataTable thead th { background: #f8f9fb; color: #697386 !important; font-weight: 700 !important; border-bottom: .0625rem solid #e9edf3 !important; }
    table.dataTable tbody td { border-bottom: .0625rem solid #f0f2f6 !important; vertical-align: middle; }
    table.dataTable tbody tr:hover { background: #fffaf8 !important; }
    table.dataTable.no-footer { border-bottom: 0 !important; }

    /* Dashboard summary cards and chart canvas remain the same components/data. */
    #chartDownload { max-width: 100%; }
    .preview-box { border-radius: .75rem; }

    html.dark body { background: #0c1423; color: #dbe2ed; }
    html.dark .app-topbar { background: rgba(19, 30, 48, .96) !important; border-color: #26354b !important; }
    html.dark .card { background: #131e30 !important; border-color: #26354b !important; box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .12); }
    html.dark .input, html.dark select.input, html.dark textarea.input,
    html.dark .dt-container .dt-search input, html.dark .dt-container .dt-length select,
    html.dark .btn-light, html.dark .btn-icon { background: #101a2b !important; border-color: #31415a !important; color: #e3eaf4 !important; }
    html.dark .dt-container .dt-paging .dt-paging-button.current { background: var(--ui-accent) !important; border-color: var(--ui-accent) !important; color: white !important; }
    html.dark table.dataTable thead th { background: #172338 !important; color: #bcc8d8 !important; border-bottom-color: #2a3a52 !important; }
    html.dark table.dataTable tbody td { border-bottom-color: #24334a !important; }
    html.dark table.dataTable tbody tr:hover { background: #1a293e !important; }
    html.dark .badge { background: #4b2822 !important; color: #ffab96 !important; }
    html.dark .badge-gray { background: #26344a !important; color: #c1ccda !important; }
    html.dark .label { color: #d5deea; }
    html.dark .btn-primary { background: var(--ui-accent) !important; border-color: var(--ui-accent) !important; }
    html.dark .btn-icon:hover { background: #3b2827 !important; border-color: #75463b !important; color: #ffab96 !important; }
    html.dark .dt-container .dt-paging .dt-paging-button { color: #c1ccda !important; }
    html.dark .dt-container .dt-paging .dt-paging-button:hover:not(.current):not(.disabled) { background: #352622 !important; color: #ffab96 !important; }
    html.dark .preview-box { background: #101a2b; border-color: #3a4960; }
</style>


<style>
    .dashboard-welcome-logo {
        display: block;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }
</style>

<style>
    /* Micro-interactions: subtle motion that keeps the existing layout and behavior intact. */
    :root {
        --ui-ease: cubic-bezier(.2, .75, .25, 1);
        --ui-motion-fast: 150ms;
        --ui-motion-normal: 220ms;
        --ui-card-shadow-hover: 0 .45rem 1.2rem rgba(28, 42, 64, .075);
        --ui-skeleton-base: #edf0f5;
        --ui-skeleton-glow: #f8f9fc;
    }

    html.dark {
        --ui-card-shadow-hover: 0 .5rem 1.35rem rgba(0, 0, 0, .22);
        --ui-skeleton-base: #243249;
        --ui-skeleton-glow: #31415a;
    }

    .card {
        transition: transform var(--ui-motion-normal) var(--ui-ease),
                    box-shadow var(--ui-motion-normal) var(--ui-ease),
                    border-color var(--ui-motion-normal) var(--ui-ease);
    }

    .btn, .btn-icon, button, [role="button"] {
        -webkit-tap-highlight-color: transparent;
        transition: transform var(--ui-motion-fast) var(--ui-ease),
                    box-shadow var(--ui-motion-fast) var(--ui-ease),
                    background-color var(--ui-motion-fast) ease,
                    border-color var(--ui-motion-fast) ease,
                    color var(--ui-motion-fast) ease;
    }

    .input, select, textarea, .file-input {
        transition: border-color var(--ui-motion-fast) ease,
                    box-shadow var(--ui-motion-fast) ease,
                    background-color var(--ui-motion-fast) ease;
    }

    .app-sidebar a, .app-sidebar button {
        transition: color var(--ui-motion-fast) ease,
                    background-color var(--ui-motion-fast) ease,
                    transform var(--ui-motion-fast) var(--ui-ease);
    }

    .app-sidebar a [data-lucide], .app-sidebar button [data-lucide],
    .btn [data-lucide], .btn-icon [data-lucide] {
        transition: transform var(--ui-motion-fast) var(--ui-ease);
    }

    @media (hover: hover) and (pointer: fine) {
        .card:hover {
            transform: translateY(-1px);
            box-shadow: var(--ui-card-shadow-hover);
            border-color: #e0e5ed !important;
        }

        html.dark .card:hover { border-color: #3a4a63 !important; }

        .btn:not(:disabled):hover, .btn-icon:not(:disabled):hover,
        button:not(:disabled):hover, [role="button"]:not([aria-disabled="true"]):hover {
            filter: saturate(1.035);
        }

        .btn-primary:not(:disabled):hover,
        .app-sidebar .bg-orange-500:hover {
            box-shadow: 0 .35rem .9rem rgba(255, 91, 55, .2);
        }

        .btn:hover [data-lucide], .btn-icon:hover [data-lucide] { transform: translateY(-1px); }

        table.dataTable tbody tr { transition: background-color var(--ui-motion-fast) ease; }
    }

    .btn:not(:disabled):active, .btn-icon:not(:disabled):active,
    button:not(:disabled):active, [role="button"]:not([aria-disabled="true"]):active {
        transform: scale(.975);
    }

    .app-sidebar a:focus-visible, .app-sidebar button:focus-visible,
    .btn:focus-visible, .btn-icon:focus-visible {
        outline: 2px solid rgba(255, 91, 55, .55);
        outline-offset: 2px;
    }

    @keyframes ui-card-enter {
        from { opacity: 0; transform: translateY(7px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes ui-shimmer {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }

    main.ui-content-enter .card {
        animation: ui-card-enter 380ms var(--ui-ease) both;
        animation-delay: var(--ui-enter-delay, 0ms);
    }

    /* Skeleton shimmer is shown during same-site page navigation and form submission. */
    .ui-page-loading {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: grid;
        grid-template-columns: clamp(13rem, 17vw, 16rem) minmax(0, 1fr);
        overflow: hidden;
        background: #f7f8fb;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 130ms ease, visibility 130ms ease;
    }

    .ui-page-loading.is-visible { opacity: 1; visibility: visible; }
    .ui-page-loading__sidebar { padding: 1.35rem 1rem; border-right: 1px solid #e9edf3; background: #fff; }
    .ui-page-loading__brand { width: 72%; height: 2.5rem; margin: 0 0 2rem .35rem; }
    .ui-page-loading__nav { display: grid; gap: .9rem; }
    .ui-page-loading__nav-item { height: 2.15rem; border-radius: .65rem; }
    .ui-page-loading__main { min-width: 0; }
    .ui-page-loading__topbar { height: 4.5rem; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; border-bottom: 1px solid #e9edf3; background: #fff; }
    .ui-page-loading__title { width: 9rem; height: 1.15rem; }
    .ui-page-loading__avatar { width: 2.2rem; height: 2.2rem; border-radius: 50%; }
    .ui-page-loading__content { padding: 2rem; }
    .ui-page-loading__welcome { height: 6rem; margin-bottom: 1.5rem; border-radius: 1rem; }
    .ui-page-loading__stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .ui-page-loading__stat { height: 7rem; border-radius: 1rem; }
    .ui-page-loading__columns { display: grid; grid-template-columns: minmax(0, 2fr) minmax(13rem, 1fr); gap: 1.25rem; }
    .ui-page-loading__panel { min-height: 16rem; border-radius: 1rem; }
    .ui-skeleton-shimmer {
        background-color: var(--ui-skeleton-base);
        background-image: linear-gradient(100deg, var(--ui-skeleton-base) 20%, var(--ui-skeleton-glow) 42%, var(--ui-skeleton-base) 64%);
        background-size: 220% 100%;
        animation: ui-shimmer 1.25s ease-in-out infinite;
    }

    html.dark .ui-page-loading { background: #0c1423; }
    html.dark .ui-page-loading__sidebar, html.dark .ui-page-loading__topbar { background: #131e30; border-color: #26354b; }

    @media (max-width: 900px) {
        .ui-page-loading { grid-template-columns: 1fr; }
        .ui-page-loading__sidebar { display: none; }
        .ui-page-loading__content { padding: 1.25rem; }
        .ui-page-loading__stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ui-page-loading__columns { grid-template-columns: 1fr; }
    }

    @media (max-width: 520px) {
        .ui-page-loading__topbar { padding: 0 1rem; }
        .ui-page-loading__welcome { height: 5rem; }
        .ui-page-loading__stat { height: 5.5rem; }
        .ui-page-loading__panel { min-height: 10rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            scroll-behavior: auto !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
        }
        .card:hover, .btn:active, .btn-icon:active, button:active { transform: none !important; }
        .ui-page-loading { display: none !important; }
    }
</style>
