@php
    $link = fn (bool $aktif) => 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition '
        .($aktif ? 'bg-white/10 text-white' : 'text-brand-200 hover:bg-white/5 hover:text-white');
    $sub = fn (bool $aktif) => 'flex items-center gap-3 rounded-lg py-1.5 pl-10 pr-3 text-sm transition '
        .($aktif ? 'bg-white/10 text-white' : 'text-brand-200 hover:bg-white/5 hover:text-white');
    $label = 'px-3 pb-1 pt-5 text-xs font-medium text-brand-200/70';
@endphp

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-brand-900 text-brand-100 transition-transform lg:translate-x-0"
       :class="{ 'translate-x-0': sidebar }" aria-label="Menu utama">
    <a href="{{ route('dashboard') }}" title="{{ config('arsip.nama_lengkap') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white p-1"><img src="{{ asset('img/ikon.png') }}" alt="Logo Bawaslu" class="h-full w-full object-contain"></span>
        <span class="leading-tight">
            <span class="block text-sm font-semibold text-white">{{ config('arsip.nama') }}</span>
            <span class="block text-xs text-brand-200">Bawaslu Banyumas</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <a href="{{ route('dashboard') }}" class="{{ $link(request()->routeIs('dashboard')) }}">
            <i data-lucide="layout-dashboard" class="h-[18px] w-[18px]"></i> Dashboard
        </a>

        <p class="{{ $label }}">Arsip</p>
        <a href="{{ route('arsip.index') }}" class="{{ $link(request()->routeIs('arsip.index') && ! request()->filled('kategori')) }}">
            <i data-lucide="folder-open" class="h-[18px] w-[18px]"></i> Semua arsip
        </a>
        @foreach ($sidebarKategori as $k)
            <a href="{{ route('arsip.index', ['kategori' => $k->slug]) }}"
               class="{{ $sub(request()->routeIs('arsip.index') && request('kategori') === $k->slug) }}">{{ $k->nama }}</a>
        @endforeach
        @can('create', \App\Models\Arsip::class)
            <a href="{{ route('arsip.create') }}" class="{{ $link(request()->routeIs('arsip.create')) }}">
                <i data-lucide="file-plus" class="h-[18px] w-[18px]"></i> Tambah arsip
            </a>
        @endcan
        <a href="{{ route('riwayat.index') }}" class="{{ $link(request()->routeIs('riwayat.*')) }}">
            <i data-lucide="history" class="h-[18px] w-[18px]"></i> Riwayat download
        </a>

        <p class="{{ $label }}">Data</p>
        <a href="{{ route('kategori.index') }}" class="{{ $link(request()->routeIs('kategori.*')) }}">
            <i data-lucide="tags" class="h-[18px] w-[18px]"></i> Kategori
        </a>
        <a href="{{ route('petugas.index') }}" class="{{ $link(request()->routeIs('petugas.*')) }}">
            <i data-lucide="users" class="h-[18px] w-[18px]"></i> Petugas
        </a>

        <p class="{{ $label }}">Akun</p>
        <a href="{{ route('profil.edit') }}" class="{{ $link(request()->routeIs('profil.*')) }}">
            <i data-lucide="user-cog" class="h-[18px] w-[18px]"></i> Profil &amp; keamanan
        </a>
        <a href="{{ route('pengaturan.edit') }}" class="{{ $link(request()->routeIs('pengaturan.*')) }}">
            <i data-lucide="settings" class="h-[18px] w-[18px]"></i> Pengaturan
        </a>
    </nav>

    <div class="shrink-0 border-t border-white/10 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-brand-200 transition hover:bg-white/5 hover:text-white">
                <i data-lucide="log-out" class="h-[18px] w-[18px]"></i> Keluar
            </button>
        </form>
    </div>
</aside>
