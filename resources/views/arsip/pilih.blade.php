@extends('layouts.app')
@section('title', 'Tambah Arsip')

@section('content')
<p class="mb-5 text-sm text-slate-500">Pilih kategori arsip. Pada kategori utama, pilih juga jenis arsipnya. Isian formulir menyesuaikan pilihan Anda.</p>

@if ($kategoris->isEmpty())
    <div class="card p-10 text-center text-sm text-slate-500">Belum ada kategori. Hubungi admin untuk menambahkannya.</div>
@else
    @php($utama = $kategoris->filter->isUtama())
    @php($tambahan = $kategoris->reject->isUtama())

    {{-- Dua kategori utama: tiap kartu berisi daftar jenis arsip --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($utama as $k)
            <section class="card category-panel p-5">
                <div class="flex items-center justify-between gap-3">
                    <span class="badge category-label">{{ $k->nama }}</span>
                    <span class="text-xs text-slate-400">{{ number_format($k->arsips_count, 0, ',', '.') }} arsip</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $k->keterangan ?: 'Tanpa keterangan.' }}</p>

                <p class="mt-4 text-xs font-medium text-slate-500">Pilih jenis arsip</p>
                <div class="mt-2">
                    @foreach ($k->jenisArsipList() as $kunci => $j)
                        <a href="{{ route('arsip.create', ['kategori' => $k->slug, 'jenis' => $kunci]) }}"
                           class="archive-type-row flex items-center gap-3 border-b border-slate-200 px-1 py-3 text-sm text-slate-800 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            <i class="ti ti-{{ $j['ikon'] ?? 'file-text' }} h-4 w-4 shrink-0 text-brand-700"></i>
                            <span class="flex-1 font-medium">{{ $j['label'] }}</span>
                            @if (! empty($j['singkat']))<span class="badge badge-gray">{{ $j['singkat'] }}</span>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    {{-- Kategori tambahan buatan admin: langsung ke isian sederhana --}}
    @if ($tambahan->isNotEmpty())
        <h2 class="mb-3 mt-8 text-sm font-semibold text-slate-900">Kategori lainnya</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($tambahan as $k)
                <a href="{{ route('arsip.create', ['kategori' => $k->slug]) }}"
                   class="card block p-5 transition hover:border-brand-500 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <span class="badge category-label">{{ $k->nama }}</span>
                    <p class="mt-3 min-h-10 text-sm text-slate-600">{{ $k->keterangan ?: 'Tanpa keterangan.' }}</p>
                    <p class="mt-3 text-xs text-slate-400">{{ number_format($k->arsips_count, 0, ',', '.') }} arsip</p>
                </a>
            @endforeach
        </div>
    @endif
@endif
@endsection
