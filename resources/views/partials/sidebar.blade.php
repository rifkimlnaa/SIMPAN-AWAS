@php
    $admin = auth()->user()->isAdmin();
    $utama = $sidebarKategori->filter(fn ($k) => $k->isUtama());
    $lain = $sidebarKategori->reject(fn ($k) => $k->isUtama());
    $arsipAktif = request()->routeIs('arsip.*');
    $adminAktif = request()->routeIs('kategori.*', 'petugas.*', 'backup.*', 'pengaturan.*');
@endphp
<header class="tb-header">
    <div class="tb-header-top">
        <div class="container-xl">
            <div style="display:flex;align-items:center">
                <button type="button" class="tb-icon-btn tb-nav-toggle" id="tbNavToggle" aria-label="Buka menu" aria-controls="tbNav" aria-expanded="false"><i class="ti ti-menu-2"></i></button>
                <a href="{{ route('dashboard') }}" class="tb-brand" aria-label="{{ config('arsip.nama_lengkap') }}">
                    <img src="{{ asset('img/ikon.png') }}" alt="Logo Bawaslu">
                    <span><strong>{{ config('arsip.nama') }}</strong><small>Bawaslu Kabupaten Banyumas</small></span>
                </a>
            </div>
            <div class="tb-top-actions">
                <button type="button" class="tb-icon-btn" id="gsTheme" aria-label="Ganti tema" title="Ganti tema" onclick="window.setTema(window.temaDipilih() === 'gelap' ? 'terang' : 'gelap')"><i class="ti ti-moon"></i></button>
                <a href="{{ route('profil.edit') }}" class="tb-account" title="Profil saya">
                    @include('partials.avatar', ['user' => auth()->user(), 'size' => 'sm'])
                    <span class="tb-account-text"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->roleLabel() }}</small></span>
                </a>
                <form data-turbo="false" method="POST" action="{{ route('logout') }}">@csrf<button class="tb-icon-btn" type="submit" title="Keluar" aria-label="Keluar"><i class="ti ti-logout"></i></button></form>
            </div>
        </div>
    </div>

    <div class="tb-header-nav" id="tbNav">
        <div class="container-xl">
            <ul class="tb-nav">
                <li class="tb-nav-ink" id="tbInk" aria-hidden="true"></li>
                <li class="tb-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="tb-nav-link"><i class="ti ti-home"></i>Dashboard</a>
                </li>

                <li class="tb-nav-item {{ request()->routeIs('arsip.index') && ! request()->filled('kategori') ? 'active' : '' }}">
                    <a href="{{ route('arsip.index') }}" class="tb-nav-link"><i class="ti ti-folders"></i>Semua arsip</a>
                </li>

                @foreach ($utama as $k)
                    @php($aktif = request()->routeIs('arsip.index') && request('kategori') === $k->slug)
                    <li class="tb-nav-item {{ $aktif ? 'active' : '' }}" data-dropdown>
                        <button type="button" class="tb-nav-link" aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-{{ config('arsip.kategori_utama.'.$k->jenis.'.ikon', 'folder') }}"></i>{{ $k->nama }}<i class="ti ti-chevron-down tb-caret"></i>
                        </button>
                        <ul class="tb-dropdown">
                            <li><a href="{{ route('arsip.index', ['kategori' => $k->slug]) }}">Semua jenis</a></li>
                            @foreach (\App\Models\Arsip::jenisUntukKategori($k->jenis) as $kunci => $j)
                                <li><a href="{{ route('arsip.index', ['kategori' => $k->slug, 'jenis' => $kunci]) }}" class="{{ $aktif && request('jenis') === $kunci ? 'active' : '' }}">{{ $j['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach

                @if ($lain->isNotEmpty())
                    @php($lainAktif = request()->routeIs('arsip.index') && $lain->contains(fn ($k) => request('kategori') === $k->slug))
                    <li class="tb-nav-item {{ $lainAktif ? 'active' : '' }}" data-dropdown>
                        <button type="button" class="tb-nav-link" aria-haspopup="true" aria-expanded="false"><i class="ti ti-folder"></i>Kategori lain<i class="ti ti-chevron-down tb-caret"></i></button>
                        <ul class="tb-dropdown">
                            @foreach ($lain as $k)
                                <li><a href="{{ route('arsip.index', ['kategori' => $k->slug]) }}" class="{{ request('kategori') === $k->slug ? 'active' : '' }}">{{ $k->nama }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endif

                <li class="tb-nav-item {{ request()->routeIs('riwayat.*') ? 'active' : '' }}">
                    <a href="{{ route('riwayat.index') }}" class="tb-nav-link"><i class="ti ti-history"></i>Riwayat</a>
                </li>

                @if ($admin)
                    <li class="tb-nav-item {{ $adminAktif ? 'active' : '' }}" data-dropdown>
                        <button type="button" class="tb-nav-link" aria-haspopup="true" aria-expanded="false"><i class="ti ti-settings"></i>Administrasi<i class="ti ti-chevron-down tb-caret"></i></button>
                        <ul class="tb-dropdown">
                            <li><a href="{{ route('kategori.index') }}" class="{{ request()->routeIs('kategori.*') ? 'active' : '' }}"><i class="ti ti-tags"></i>Kategori</a></li>
                            <li><a href="{{ route('petugas.index') }}" class="{{ request()->routeIs('petugas.*') ? 'active' : '' }}"><i class="ti ti-users"></i>Petugas</a></li>
                            <li><a href="{{ route('backup.index') }}" class="{{ request()->routeIs('backup.*') ? 'active' : '' }}"><i class="ti ti-database"></i>Backup</a></li>
                            <li><a href="{{ route('pengaturan.edit') }}" class="{{ request()->routeIs('pengaturan.*') ? 'active' : '' }}"><i class="ti ti-adjustments"></i>Pengaturan</a></li>
                        </ul>
                    </li>
                @endif

                <li class="tb-nav-item {{ request()->routeIs('profil.*') ? 'active' : '' }}">
                    <a href="{{ route('profil.edit') }}" class="tb-nav-link"><i class="ti ti-user"></i>Profil</a>
                </li>
            </ul>
        </div>
    </div>
</header>
