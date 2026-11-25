@extends('layouts.app')
@section('title', 'Dashboard')
@section('pretitle', 'Ringkasan')

@section('actions')
    <a href="{{ route('arsip.index') }}" class="btn">Semua arsip</a>
    @can('create', \App\Models\Arsip::class)
        <a href="{{ route('arsip.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i>Tambah arsip</a>
    @endcan
@endsection

@section('content')
@php
    $d = $chart['downloads'];
    $u = $chart['uploads'];
    $n = count($d);
    $a = array_map(fn ($x, $y) => $x + $y, $d, $u);

    $jumlah = fn (array $s) => array_sum($s);
    $tren = function (array $s) {
        $c = count($s);
        $akhir = array_sum(array_slice($s, max(0, $c - 7)));
        $awal = array_sum(array_slice($s, max(0, $c - 14), min(7, max(0, $c - 7))));
        if ($awal === 0) { $pct = $akhir > 0 ? 100 : 0; } else { $pct = (int) round(($akhir - $awal) / $awal * 100); }
        return ['pct' => abs($pct), 'arah' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat')];
    };
    $ikonTren = ['up' => 'trending-up', 'down' => 'trending-down', 'flat' => 'minus'];

    $dl30 = $jumlah($d);
    $up30 = $jumlah($u);
    $akt30 = $dl30 + $up30;
    $dlHariIni = $d[$n - 1] ?? 0;
    $upHariIni = $u[$n - 1] ?? 0;
    $dlKemarin = $d[$n - 2] ?? 0;
    $puncak = max($d ?: [0]);
    $hariAktif = count(array_filter($a, fn ($v) => $v > 0));
    $persenBaru = $totalArsip > 0 ? min(100, (int) round($up30 / $totalArsip * 100)) : 0;
    $selisihKemarin = $dlKemarin > 0 ? (int) round(($dlHariIni - $dlKemarin) / $dlKemarin * 100) : ($dlHariIni > 0 ? 100 : 0);
    $cincin = $puncak > 0 ? min(100, (int) round($dlHariIni / $puncak * 100)) : 0;
    $warna = ['#066fd1', '#4299e1', '#2fb344', '#f59f00', '#ae3ec9', '#d63939', '#667382'];
    $fmt = fn ($v) => number_format($v, 0, ',', '.');
@endphp

{{-- Baris 1: angka ringkas dengan grafik mini --}}
<div class="tb-cards c4">
    <div class="card tb-stat-card">
        <div class="card-body">
            <div class="tb-stat-top"><span class="tb-subheader">{{ $admin ? 'Total arsip' : 'Arsip saya' }}</span><span class="tb-stat-period">Seluruhnya</span></div>
            <div class="tb-stat-value">{{ $fmt($totalArsip) }}</div>
            <div class="tb-stat-row"><span>Arsip baru {{ $chartDays }} hari</span><span class="tb-trend {{ $persenBaru > 0 ? 'up' : 'flat' }}">{{ $persenBaru }}% <i class="ti ti-{{ $persenBaru > 0 ? 'trending-up' : 'minus' }}"></i></span></div>
            <div class="tb-progress" role="progressbar" aria-valuenow="{{ $persenBaru }}" aria-valuemin="0" aria-valuemax="100" aria-label="Arsip baru dibanding total"><span style="width: {{ $persenBaru }}%"></span></div>
        </div>
    </div>

    @php($t = $tren($u))
    <div class="card tb-stat-card tb-clip">
        <div class="card-body">
            <div class="tb-stat-top"><span class="tb-subheader">Unggahan</span><span class="tb-stat-period">{{ $chartDays }} hari terakhir</span></div>
            <div class="tb-stat-value">{{ $fmt($up30) }} <span class="tb-trend {{ $t['arah'] }}">{{ $t['pct'] }}% <i class="ti ti-{{ $ikonTren[$t['arah']] }}"></i></span></div>
        </div>
        <div class="tb-spark-full"><svg class="tb-spark" data-spark="area" data-series="uploads" role="img" aria-label="Grafik unggahan arsip per hari"></svg></div>
    </div>

    @php($t = $tren($d))
    <div class="card tb-stat-card tb-clip">
        <div class="card-body">
            <div class="tb-stat-top"><span class="tb-subheader">{{ $admin ? 'Download' : 'Download saya' }}</span><span class="tb-stat-period">{{ $chartDays }} hari terakhir</span></div>
            <div class="tb-stat-value">{{ $fmt($dl30) }} <span class="tb-trend {{ $t['arah'] }}">{{ $t['pct'] }}% <i class="ti ti-{{ $ikonTren[$t['arah']] }}"></i></span></div>
        </div>
        <div class="tb-spark-full"><svg class="tb-spark" data-spark="line" data-series="downloads" role="img" aria-label="Grafik download per hari"></svg></div>
    </div>

    @php($t = $tren($a))
    <div class="card tb-stat-card tb-clip">
        <div class="card-body">
            <div class="tb-stat-top"><span class="tb-subheader">Aktivitas</span><span class="tb-stat-period">{{ $chartDays }} hari terakhir</span></div>
            <div class="tb-stat-value">{{ $fmt($akt30) }} <span class="tb-trend {{ $t['arah'] }}">{{ $t['pct'] }}% <i class="ti ti-{{ $ikonTren[$t['arah']] }}"></i></span></div>
        </div>
        <div class="tb-spark-full" style="padding: 0 1.25rem 1.25rem"><svg class="tb-spark" data-spark="bar" data-series="activity" role="img" aria-label="Grafik aktivitas unggah dan download per hari"></svg></div>
    </div>
</div>

{{-- Baris 2: kartu dengan kotak ikon --}}
<div class="tb-cards c4" style="margin-top: 1rem">
    <div class="card tb-mini">
        <span class="tb-avatar bg-primary"><i class="ti ti-folders"></i></span>
        <div><div class="tb-mini-title">{{ $fmt($totalArsip) }} Arsip</div><div class="tb-mini-sub">{{ $fmt($upHariIni) }} unggahan hari ini</div></div>
    </div>
    <div class="card tb-mini">
        <span class="tb-avatar bg-green"><i class="ti ti-download"></i></span>
        <div><div class="tb-mini-title">{{ $fmt($totalDownload) }} Download</div><div class="tb-mini-sub">{{ $fmt($dlHariIni) }} hari ini</div></div>
    </div>
    <div class="card tb-mini">
        <span class="tb-avatar bg-dark"><i class="ti ti-tags"></i></span>
        <div><div class="tb-mini-title">{{ $fmt($jumlahKategori) }} Kategori</div><div class="tb-mini-sub">{{ $jumlahKategori - $kategoriUtama > 0 ? ($jumlahKategori - $kategoriUtama).' kategori tambahan' : $kategoriUtama.' kategori utama' }}</div></div>
    </div>
    <div class="card tb-mini">
        <span class="tb-avatar bg-azure"><i class="ti ti-{{ $admin ? 'users' : 'calendar-event' }}"></i></span>
        @if ($admin)
            <div><div class="tb-mini-title">{{ $fmt($totalPetugas) }} Petugas</div><div class="tb-mini-sub">{{ $fmt($totalAdmin) }} admin</div></div>
        @else
            <div><div class="tb-mini-title">{{ $fmt($hariAktif) }} Hari aktif</div><div class="tb-mini-sub">dari {{ $chartDays }} hari terakhir</div></div>
        @endif
    </div>
</div>

{{-- Baris 3: grafik dan aktivitas --}}
<div class="tb-cards c2" style="margin-top: 1rem">
    <div class="tb-col">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title" style="margin-bottom: 1rem">Aktivitas harian</h3>
                <div class="tb-chart" id="chartDaily" role="img" aria-label="Grafik batang download dan unggahan arsip per hari selama {{ $chartDays }} hari terakhir"></div>
                <div class="tb-legend">
                    <span><i style="background:#066fd1"></i>Download</span>
                    <span><i style="background:#2fb344"></i>Unggahan</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="margin-bottom: .75rem">Arsip per kategori: <strong>{{ $fmt($totalArsip) }}</strong> arsip</div>
                @php($totalK = max(1, $perKategori->sum('arsips_count')))
                <div class="tb-segbar">
                    @foreach ($perKategori as $i => $k)
                        @if ($k->arsips_count > 0)
                            <span style="width: {{ $k->arsips_count / $totalK * 100 }}%; background: {{ $warna[$i % count($warna)] }}" title="{{ $k->nama }}: {{ $k->arsips_count }}"></span>
                        @endif
                    @endforeach
                </div>
                <div class="tb-legend">
                    @foreach ($perKategori as $i => $k)
                        <span><i style="background: {{ $warna[$i % count($warna)] }}"></i>{{ $k->nama }} <small>{{ $fmt($k->arsips_count) }}</small></span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card grow">
            <div class="card-header">
                <h3 class="card-title">Download terbaru</h3>
                <a href="{{ route('riwayat.index') }}" style="margin-left: auto; color: var(--tb-primary-text)">Lihat semua</a>
            </div>
            @if ($terbaru->isEmpty())
                <div class="tb-empty">Belum ada aktivitas download.</div>
            @else
                <ul class="tb-feed">
                    @foreach ($terbaru as $log)
                        @php($nama = $log->user?->name ?? 'Pengguna dihapus')
                        @php($inisial = \Illuminate\Support\Str::of($nama)->explode(' ')->filter()->take(2)->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode(''))
                        <li>
                            <span class="tb-avatar sm" aria-hidden="true">{{ $admin ? $inisial : 'A' }}</span>
                            <div class="tb-feed-text">
                                <div>@if ($admin)<strong>{{ $nama }}</strong> mengunduh @else Anda mengunduh @endif<strong>{{ $log->arsip_label }}</strong></div>
                                <small>{{ $log->created_at->diffForHumans() }}</small>
                            </div>
                            <span class="tb-dot" aria-hidden="true"></span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="tb-col">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title" style="margin-bottom: 1rem">Arsip per jenis</h3>
                @if (empty($chart['jenis']))
                    <div class="tb-empty">Belum ada arsip.</div>
                @else
                    <div class="tb-donut-wrap">
                        <div class="tb-chart" id="chartJenis" style="width: 13rem; flex: 0 0 auto" role="img" aria-label="Diagram lingkaran jumlah arsip per jenis"></div>
                        <div class="tb-legend" id="legendJenis"></div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card grow tb-clip" style="display: flex">
            <div class="card-body" style="flex: 0 0 auto">
                <h3 class="card-title" style="margin-bottom: 1rem">Tren download</h3>
                <div class="tb-ring-row">
                    <svg class="tb-ring" viewBox="0 0 36 36" role="img" aria-label="Download hari ini dibanding puncak: {{ $cincin }}%">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--tb-track)" stroke-width="3"></circle>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--tb-primary)" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ round($cincin * 97.39 / 100, 2) }} 97.39" transform="rotate(-90 18 18)"></circle>
                    </svg>
                    <div>
                        <div>Hari ini: {{ $fmt($dlHariIni) }} download</div>
                        <div class="tb-trend {{ $selisihKemarin > 0 ? 'up' : ($selisihKemarin < 0 ? 'down' : 'flat') }}" style="color: {{ $selisihKemarin === 0 ? 'var(--tb-muted)' : '' }}"><i class="ti ti-{{ $selisihKemarin > 0 ? 'trending-up' : ($selisihKemarin < 0 ? 'trending-down' : 'minus') }}"></i> {{ $selisihKemarin > 0 ? '+' : '' }}{{ $selisihKemarin }}% dibanding kemarin</div>
                    </div>
                </div>
            </div>
            <div class="tb-chart" id="chartTren" style="flex: 1 1 auto; min-height: 12rem; margin-top: .5rem" role="img" aria-label="Grafik kumulatif download {{ $chartDays }} hari terakhir"></div>
        </div>
    </div>
</div>

<script type="application/json" id="dashData">@json($chart)</script>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard-charts.js') }}?v={{ @filemtime(public_path('js/dashboard-charts.js')) }}"></script>
@endpush
