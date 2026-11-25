<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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

<script>if (!('onpagereveal' in window)) document.documentElement.classList.add('no-vt');</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css">

<style>
    /* Palet Tailwind dibuat dari variabel agar ikut tema terang/gelap. Nilai mengikuti skala abu-abu Tabler. */
    :root {
        --surface: 255 255 255;
        --s50: 246 248 251; --s100: 238 241 245; --s200: 230 231 233; --s300: 208 213 220; --s400: 154 164 178;
        --s500: 102 115 130; --s600: 75 88 104; --s700: 49 63 80; --s800: 31 44 59; --s900: 24 36 51;
        --b50: 230 241 251; --b500: 6 111 209; --b600: 6 111 209; --b700: 6 111 209; --b800: 5 96 182;
    }
    html.dark {
        color-scheme: dark;
        --surface: 24 36 51;
        --s50: 21 31 44; --s100: 33 47 66; --s200: 44 58 77; --s300: 62 78 100; --s400: 120 135 155;
        --s500: 154 168 184; --s600: 180 192 206; --s700: 205 214 224; --s800: 220 225 231; --s900: 238 241 245;
        --b50: 14 42 74; --b500: 66 153 225; --b600: 66 153 225; --b700: 96 170 232; --b800: 130 188 238;
    }
</style>

<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            borderRadius: { none: '0', sm: '2px', DEFAULT: '4px', md: '4px', lg: '4px', xl: '4px', '2xl': '4px', '3xl': '4px', full: '9999px' },
            extend: {
                fontFamily: { sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
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
                        50: 'rgb(var(--b50) / <alpha-value>)', 100: '#cce2f6', 200: '#99c5ed',
                        500: 'rgb(var(--b500) / <alpha-value>)', 600: 'rgb(var(--b600) / <alpha-value>)',
                        700: 'rgb(var(--b700) / <alpha-value>)', 800: 'rgb(var(--b800) / <alpha-value>)',
                        900: '#0e2a4a',
                    },
                    /* Warna status Tabler: merah #d63939, hijau #2fb344, kuning #f59f00 */
                    red: { 50: '#fbeaea', 100: '#f7d5d5', 200: '#efabab', 300: '#e68282', 400: '#de5858', 500: '#d63939', 600: '#d63939', 700: '#ab2e2e', 800: '#802222', 900: '#561717' },
                    rose: { 50: '#fbeaea', 100: '#f7d5d5', 200: '#efabab', 300: '#e68282', 400: '#de5858', 500: '#d63939', 600: '#d63939', 700: '#ab2e2e', 800: '#802222', 900: '#561717' },
                    emerald: { 50: '#eaf7ec', 100: '#d5f0d9', 200: '#abe1b4', 300: '#82d18e', 400: '#58c269', 500: '#2fb344', 600: '#2fb344', 700: '#268f36', 800: '#1c6b29', 900: '#13471b' },
                    orange: { 50: '#e6f1fb', 100: '#cce2f6', 200: '#99c5ed', 300: '#66a8e4', 400: '#338bdb', 500: '#066fd1', 600: '#066fd1', 700: '#0560b6', 800: '#044a8d', 900: '#033464' },
                    amber: { 50: '#fef5e5', 100: '#fdebcc', 200: '#fcd799', 300: '#fac366', 400: '#f9af33', 500: '#f59f00', 600: '#c47f00', 700: '#935f00', 800: '#623f00', 900: '#312000' },
                },
            },
        },
    };
</script>
<link rel="stylesheet" href="{{ asset('css/tabler.css') }}?v={{ @filemtime(public_path('css/tabler.css')) }}">
