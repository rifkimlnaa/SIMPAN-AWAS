@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php($admin = auth()->user()->isAdmin())
@php($kartu = 'card flex items-center gap-4 p-5')
@php($ikon = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700')

<section class="card mb-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <h2 class="text-lg font-semibold text-slate-900">Selamat datang, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            {{ $admin ? 'Ringkasan arsip, petugas, dan aktivitas download.' : 'Ringkasan arsip yang Anda unggah dan aktivitas download Anda.' }}
        </p>
    </div>
    <img src="{{ asset('img/logo.png') }}" alt="Logo Bawaslu Kabupaten Banyumas" class="dashboard-welcome-logo h-12 w-auto shrink-0 self-start sm:self-auto">
</section>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="{{ $kartu }}">
        <span class="{{ $ikon }}"><i data-lucide="folder-archive" class="h-5 w-5"></i></span>
        <div>
            <p class="text-sm text-slate-500">{{ $admin ? 'Total arsip' : 'Arsip saya' }}</p>
            <p class="text-2xl font-semibold text-slate-900">{{ number_format($totalArsip, 0, ',', '.') }}</p>
        </div>
    </div>

    @foreach ($perKategori as $k)
        <div class="{{ $kartu }}">
            <span class="{{ $ikon }}"><i data-lucide="tag" class="h-5 w-5"></i></span>
            <div>
                <p class="text-sm text-slate-500">Arsip {{ $k->nama }}</p>
                <p class="text-2xl font-semibold text-slate-900">{{ number_format($k->arsips_count, 0, ',', '.') }}</p>
            </div>
        </div>
    @endforeach

    @if ($admin)
        <div class="{{ $kartu }}">
            <span class="{{ $ikon }}"><i data-lucide="users" class="h-5 w-5"></i></span>
            <div>
                <p class="text-sm text-slate-500">Petugas</p>
                <p class="text-2xl font-semibold text-slate-900">{{ number_format($totalPetugas, 0, ',', '.') }}</p>
            </div>
        </div>
    @endif

    <div class="{{ $kartu }}">
        <span class="{{ $ikon }}"><i data-lucide="download" class="h-5 w-5"></i></span>
        <div>
            <p class="text-sm text-slate-500">{{ $admin ? 'Total download' : 'Download saya' }}</p>
            <p class="text-2xl font-semibold text-slate-900">{{ number_format($totalDownload, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card p-5 xl:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Download per hari</h2>
                <p class="text-xs text-slate-500">{{ $chartDays }} hari terakhir</p>
            </div>
            <div class="gc-stats" id="chartStats">
                <div class="gc-stat"><small>Total</small><strong data-stat="total">0</strong></div>
                <div class="gc-stat"><small>Rata-rata/hari</small><strong data-stat="avg">0</strong></div>
                <div class="gc-stat"><small>Puncak</small><strong data-stat="peak">–</strong></div>
            </div>
        </div>
        <div class="mt-4 h-64">
            <div id="chartDownload"
                 data-labels="{{ json_encode($labels) }}"
                 data-values="{{ json_encode($values) }}"
                 role="img"
                 aria-label="Grafik jumlah download arsip per hari selama {{ $chartDays }} hari terakhir"></div>
        </div>
    </section>

    <section class="card p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Download terbaru</h2>
            <a href="{{ route('riwayat.index') }}" class="text-xs font-medium text-brand-700 hover:underline">Lihat semua</a>
        </div>

        @if ($terbaru->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada aktivitas download.</p>
        @else
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($terbaru as $log)
                    <li class="py-3">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $log->arsip_label }}</p>
                        <p class="truncate text-xs text-slate-500">
                            @if ($admin){{ $log->user?->name ?? 'Pengguna dihapus' }}, @endif{{ $log->created_at->translatedFormat('d M Y, H:i') }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/chart-download.js') }}?v={{ @filemtime(public_path('js/chart-download.js')) }}"></script>
@endpush
