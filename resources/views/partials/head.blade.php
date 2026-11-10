<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Tema terang/gelap: dijalankan paling awal agar halaman tidak berkedip. Pilihan disimpan per browser. --}}
<script>
    (function () {
        function dipilih() {
            try { return localStorage.getItem('tema') || 'sistem'; } catch (e) { return 'sistem'; }
        }
        window.terapkanTema = function () {
            var t = dipilih();
            var gelap = t === 'gelap' || (t === 'sistem' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', gelap);
            return gelap;
        };
        // t: 'terang' | 'gelap' | 'sistem' (ikuti pengaturan perangkat)
        window.setTema = function (t) {
            try {
                if (t === 'sistem') localStorage.removeItem('tema'); else localStorage.setItem('tema', t);
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
    /* Palet warna sebagai variabel (RGB) agar bisa berganti terang/gelap. */
    :root {
        --surface: 255 255 255;
        --s50: 248 250 252; --s100: 241 245 249; --s200: 226 232 240; --s300: 203 213 225; --s400: 148 163 184;
        --s500: 100 116 139; --s600: 71 85 105; --s700: 51 65 85; --s800: 30 41 59; --s900: 15 23 42;
        --b50: 241 245 250; --b500: 47 90 138; --b600: 36 74 117; --b700: 28 59 94; --b800: 21 44 71;
    }
    html.dark {
        color-scheme: dark;
        --surface: 15 23 42;
        --s50: 8 13 26; --s100: 30 41 59; --s200: 45 58 80; --s300: 71 85 105; --s400: 100 116 139;
        --s500: 148 163 184; --s600: 170 183 201; --s700: 203 213 225; --s800: 226 232 240; --s900: 241 245 249;
        --b50: 22 38 60; --b500: 96 150 214; --b600: 70 122 184; --b700: 59 110 168; --b800: 47 90 138;
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
        .btn { @apply inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60; }
        .btn-sm { @apply px-3 py-1.5 text-xs; }
        .btn-primary { @apply bg-brand-700 text-white hover:bg-brand-800 focus-visible:ring-brand-500; }
        .btn-light { @apply border border-slate-300 bg-surface text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-400; }
        .btn-danger-ghost { @apply border border-transparent text-red-600 hover:bg-red-50 focus-visible:ring-red-400; }
        .btn-icon { @apply inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-surface text-slate-600 transition hover:bg-slate-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500; }
        .btn-icon-danger { @apply hover:border-red-200 hover:bg-red-50 hover:text-red-600; }
        .input { @apply block w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20; }
        .label { @apply mb-1.5 block text-sm font-medium text-slate-700; }
        .hint { @apply mt-1 text-xs text-slate-500; }
        .err { @apply mt-1 text-xs text-red-600; }
        .badge { @apply inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-brand-50 text-brand-700; }
        .badge-gray { @apply bg-slate-100 text-slate-600; }
        .file-input { @apply block w-full cursor-pointer rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200; }
    }
</style>
<style>
    [x-cloak] { display: none !important; }
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
    table.dataTable thead th { font-weight: 600; color: rgb(var(--s600)); border-bottom: 1px solid rgb(var(--s200)) !important; white-space: nowrap; }
    table.dataTable tbody td { border-bottom: 1px solid rgb(var(--s100)); vertical-align: middle; }
    table.dataTable.no-footer { border-bottom: 0; }

    /* Penyesuaian khusus mode gelap */
    html.dark .text-brand-700, html.dark .badge, html.dark .btn-icon:hover { color: #9dbfe6; }
    html.dark .badge-gray { color: rgb(var(--s600)); }
    html.dark .text-red-600, html.dark .btn-danger-ghost { color: #f87171; }
    html.dark .text-red-700 { color: #fca5a5; }
    html.dark .bg-red-50, html.dark .btn-danger-ghost:hover, html.dark .btn-icon-danger:hover { background-color: rgb(127 29 29 / .3); }
    html.dark .border-red-200, html.dark .btn-icon-danger:hover { border-color: rgb(153 27 27 / .7); }
    html.dark .btn-icon-danger:hover, html.dark .hover\:text-red-600:hover { color: #f87171; }
</style>
