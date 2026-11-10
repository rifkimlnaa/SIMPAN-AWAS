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
    <img src="{{ asset('img/logo.png') }}" alt="Logo Bawaslu Kabupaten Banyumas" class="h-12 w-auto shrink-0 self-start dark:rounded-lg dark:bg-white dark:p-1.5 sm:self-auto">
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
        <h2 class="text-sm font-semibold text-slate-900">Download per hari</h2>
        <p class="text-xs text-slate-500">{{ $chartDays }} hari terakhir</p>
        <div class="mt-4 h-64">
            <canvas id="chartDownload"
                    data-labels="{{ json_encode($labels) }}"
                    data-values="{{ json_encode($values) }}"
                    role="img"
                    aria-label="Grafik jumlah download arsip per hari selama {{ $chartDays }} hari terakhir"></canvas>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('chartDownload');
        if (!canvas || !window.Chart) return;
        window.Chart.defaults.font.family = '"Plus Jakarta Sans", sans-serif';
        var gelap = document.documentElement.classList.contains('dark');
        window.Chart.defaults.color = gelap ? '#94a3b8' : '#64748b';
        new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(canvas.dataset.labels || '[]'),
                datasets: [{ label: 'Download', data: JSON.parse(canvas.dataset.values || '[]'), backgroundColor: gelap ? '#6096d6' : '#2f5a8a', borderRadius: 4, maxBarThickness: 18 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return c.parsed.y + ' download'; } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gelap ? 'rgba(148,163,184,.15)' : '#f1f5f9' } },
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }
                }
            }
        });
    })();
</script>
@endpush
