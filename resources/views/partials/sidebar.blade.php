@php
    $admin = auth()->user()->isAdmin();

    // Satu item menu. Class "is-active" menandai halaman yang sedang dibuka.
    $item = fn (bool $aktif) => 'gs-item'.($aktif ? ' is-active' : '');
    $sub = fn (bool $aktif) => 'gs-sublink'.($aktif ? ' is-active' : '');
@endphp

{{-- Terapkan status ciut/lebar sebelum sidebar digambar agar tidak berkedip saat pindah halaman. --}}
<script>
    (function () {
        try { if (localStorage.getItem('sidebarCompact') === '1') document.documentElement.classList.add('gs-compact'); } catch (e) {}
    })();
</script>

<aside id="sidebar" class="gs" :class="{ 'is-open': sidebar }" aria-label="Menu utama">
    <div class="gs-head">
        <a href="{{ route('dashboard') }}" class="gs-brand" data-tip="{{ config('arsip.nama') }}" aria-label="{{ config('arsip.nama_lengkap') }}">
            <span class="gs-logo"><img src="{{ asset('img/ikon.png') }}" alt="Logo Bawaslu"></span>
            <span class="gs-brand-copy">
                <strong>{{ config('arsip.nama') }}</strong>
                <small>Bawaslu Banyumas</small>
            </span>
        </a>
        {{-- Desktop: ciutkan / lebarkan. Layar kecil: tutup laci menu. --}}
        <button type="button" class="gs-toggle" id="gsToggle" aria-label="Ciutkan menu" aria-expanded="true">
            <i data-lucide="chevron-left" class="gs-toggle-ico"></i>
        </button>
        <button type="button" class="gs-close" @click="sidebar = false" aria-label="Tutup menu">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="gs-search">
        <label class="gs-search-box" data-tip="Cari menu">
            <button type="button" class="gs-search-btn" id="gsSearchBtn" aria-label="Cari menu"><i data-lucide="search"></i></button>
            <input type="search" id="gsSearch" placeholder="Cari menu" autocomplete="off" aria-label="Cari menu">
            <kbd>/</kbd>
        </label>
    </div>

    <nav class="gs-nav" id="gsNav">
        <span class="gs-pill" id="gsPill" aria-hidden="true"></span>

        @php($a = request()->routeIs('dashboard'))
        <a href="{{ route('dashboard') }}" class="{{ $item($a) }}" data-tip="Dashboard" @if($a) aria-current="page" @endif>
            <span class="gs-ico"><i data-lucide="layout-dashboard"></i></span><span class="gs-text">Dashboard</span>
        </a>

        <p class="gs-label"><span>Arsip</span></p>
        @php($a = request()->routeIs('arsip.index') && ! request()->filled('kategori'))
        <a href="{{ route('arsip.index') }}" class="{{ $item($a) }}" data-tip="Semua arsip" @if($a) aria-current="page" @endif>
            <span class="gs-ico"><i data-lucide="folder-open"></i></span><span class="gs-text">Semua arsip</span>
        </a>

        {{-- Kategori utama: grup yang bisa dibuka berisi jenis arsip. Kategori tambahan: tautan biasa. --}}
        @foreach ($sidebarKategori as $k)
            @php($jenisList = $k->isUtama() ? \App\Models\Arsip::jenisUntukKategori($k->jenis) : [])
            @php($kategoriTerbuka = request()->routeIs('arsip.index') && request('kategori') === $k->slug)
            @if ($jenisList)
                <div class="gs-group{{ $kategoriTerbuka ? ' is-open' : '' }}" data-group>
                    <button type="button" class="{{ $item($kategoriTerbuka) }}" data-tip="{{ $k->nama }}" data-group-toggle aria-expanded="{{ $kategoriTerbuka ? 'true' : 'false' }}">
                        <span class="gs-ico"><i data-lucide="{{ config('arsip.kategori_utama.'.$k->jenis.'.ikon', 'folder') }}"></i></span>
                        <span class="gs-text">{{ $k->nama }}</span>
                        <span class="gs-chev"><i data-lucide="chevron-down"></i></span>
                    </button>
                    <div class="gs-sub">
                        <div class="gs-sub-inner">
                            <a href="{{ route('arsip.index', ['kategori' => $k->slug]) }}"
                               class="{{ $sub($kategoriTerbuka && ! request()->filled('jenis')) }}">Semua jenis</a>
                            @foreach ($jenisList as $kunci => $j)
                                <a href="{{ route('arsip.index', ['kategori' => $k->slug, 'jenis' => $kunci]) }}"
                                   class="{{ $sub($kategoriTerbuka && request('jenis') === $kunci) }}">{{ $j['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('arsip.index', ['kategori' => $k->slug]) }}" class="{{ $item($kategoriTerbuka) }}" data-tip="{{ $k->nama }}" @if($kategoriTerbuka) aria-current="page" @endif>
                    <span class="gs-ico"><i data-lucide="folder"></i></span><span class="gs-text">{{ $k->nama }}</span>
                </a>
            @endif
        @endforeach

        @can('create', \App\Models\Arsip::class)
            @php($a = request()->routeIs('arsip.create'))
            <a href="{{ route('arsip.create') }}" class="{{ $item($a) }}" data-tip="Tambah arsip" @if($a) aria-current="page" @endif>
                <span class="gs-ico"><i data-lucide="file-plus"></i></span><span class="gs-text">Tambah arsip</span>
            </a>
        @endcan
        @php($a = request()->routeIs('riwayat.*'))
        <a href="{{ route('riwayat.index') }}" class="{{ $item($a) }}" data-tip="Riwayat download" @if($a) aria-current="page" @endif>
            <span class="gs-ico"><i data-lucide="history"></i></span><span class="gs-text">Riwayat download</span>
        </a>

        {{-- Data: hanya untuk admin. Petugas tidak melihat menu ini. --}}
        @if ($admin)
            <p class="gs-label"><span>Data</span></p>
            @php($a = request()->routeIs('kategori.*'))
            <a href="{{ route('kategori.index') }}" class="{{ $item($a) }}" data-tip="Kategori" @if($a) aria-current="page" @endif>
                <span class="gs-ico"><i data-lucide="tags"></i></span><span class="gs-text">Kategori</span>
            </a>
            @php($a = request()->routeIs('petugas.*'))
            <a href="{{ route('petugas.index') }}" class="{{ $item($a) }}" data-tip="Petugas" @if($a) aria-current="page" @endif>
                <span class="gs-ico"><i data-lucide="users"></i></span><span class="gs-text">Petugas</span>
            </a>
            @php($a = request()->routeIs('backup.*'))
            <a href="{{ route('backup.index') }}" class="{{ $item($a) }}" data-tip="Backup &amp; Pemulihan" @if($a) aria-current="page" @endif>
                <span class="gs-ico"><i data-lucide="database"></i></span><span class="gs-text">Backup &amp; Pemulihan</span>
            </a>
        @endif

        <p class="gs-label"><span>Akun</span></p>
        @php($a = request()->routeIs('profil.*'))
        <a href="{{ route('profil.edit') }}" class="{{ $item($a) }}" data-tip="Profil &amp; keamanan" @if($a) aria-current="page" @endif>
            <span class="gs-ico"><i data-lucide="user-cog"></i></span><span class="gs-text">Profil &amp; keamanan</span>
        </a>
        {{-- Pengaturan: hanya admin (isinya pengaturan yang hanya relevan untuk admin). --}}
        @if ($admin)
        @php($a = request()->routeIs('pengaturan.*'))
        <a href="{{ route('pengaturan.edit') }}" class="{{ $item($a) }}" data-tip="Pengaturan" @if($a) aria-current="page" @endif>
            <span class="gs-ico"><i data-lucide="settings"></i></span><span class="gs-text">Pengaturan</span>
        </a>
        @endif

        <p class="gs-empty" id="gsEmpty" hidden>Menu tidak ditemukan.</p>
    </nav>

    <div class="gs-foot">
        <button type="button" class="gs-theme" id="gsTheme" role="switch" aria-checked="false" data-tip="Mode gelap">
            <span class="gs-ico"><i data-lucide="moon"></i></span>
            <span class="gs-text">Mode gelap</span>
            <span class="gs-switch" aria-hidden="true"><i></i></span>
        </button>

        <div class="gs-user">
            <a href="{{ route('profil.edit') }}" class="gs-user-link" data-tip="{{ auth()->user()->name }}" aria-label="Profil saya">
                @include('partials.avatar', ['user' => auth()->user(), 'size' => 'sm'])
                <span class="gs-user-copy">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->roleLabel() }}</small>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="gs-logout">
                @csrf
                <button type="submit" class="gs-logout-btn" data-tip="Keluar" aria-label="Keluar"><i data-lucide="log-out"></i></button>
            </form>
        </div>
    </div>
</aside>

<div id="gsTip" class="gs-tip" role="tooltip" hidden></div>
