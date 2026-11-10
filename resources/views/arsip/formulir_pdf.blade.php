<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Formulir Laporan {{ $arsip->nomor_laporan }}</title>
<style>
    /* Dirender dompdf (CSS 2.1): tanpa flex/grid, tata letak memakai tabel. Kertas F4. */
    @page { margin: 34pt 56pt 52pt 62pt; }
    body { font-family: 'DejaVu Serif', serif; font-size: 9.5pt; line-height: 1.35; color: #000; }
    table { border-collapse: collapse; }
    td { vertical-align: top; padding: 0; word-wrap: break-word; }
    .uraian, td.isi { word-wrap: break-word; overflow-wrap: break-word; }

    .kop { width: 100%; }
    .kop .logo img { width: 165pt; }
    .kop .model { text-align: right; vertical-align: top; }
    .kop .model span { display: inline-block; border: 1px solid #000; padding: 4pt 16pt; font-weight: bold; font-size: 9pt; }

    .judul { text-align: center; margin: 22pt 0 14pt; }
    .judul .nama { font-size: 11.5pt; font-weight: bold; text-decoration: underline; }
    .judul .nomor { margin-top: 2pt; }

    table.bagian { width: 100%; margin-bottom: 9pt; }
    td.no { width: 24pt; }
    td.huruf { width: 22pt; }
    td.label { width: 148pt; }
    td.titik { width: 10pt; }
    td.isi { text-align: left; }
    td.judul-bagian { font-weight: normal; padding-bottom: 2pt; }

    td.sub-label { width: 70pt; }
    .jarak td { height: 5pt; font-size: 3pt; }

    .uraian { text-align: justify; margin-left: 24pt; }
    .tempat-lapor { margin-top: 10pt; }
    .tempat-lapor td.l { width: 92pt; }
    .tempat-lapor td.t { width: 10pt; }

    .penutup { page-break-inside: avoid; }
    .pernyataan { margin: 10pt 0 12pt; text-align: justify; }
    .ttd { width: 100%; page-break-inside: avoid; }
    .ttd td { width: 50%; text-align: center; }
    .ttd .ruang { height: 62pt; }
    .ttd .nama-ttd { font-weight: bold; text-decoration: underline; }

    .ket { margin-top: 16pt; font-size: 7.5pt; line-height: 1.3; page-break-inside: avoid; }
    .ket td { vertical-align: top; }
    .ket td.b { width: 22pt; }

    .foot { position: fixed; left: 0; right: 0; bottom: -34pt; font-size: 7pt; color: #666; text-align: center; }
</style>
</head>
<body>
@php
    $v = fn (string $kolom, bool $panjang = false) => $arsip->displayValue($kolom, $panjang) ?: '-';

    $pelapor = [
        ['Nama', 'nama_pelapor'],
        ['Tempat/Tgl Lahir', 'ttl_pelapor'],
        ['Jenis Kelamin', 'jenis_kelamin_pelapor'],
        ['Pekerjaan', 'pekerjaan_pelapor'],
        ['Kewarganegaraan', 'kewarganegaraan_pelapor'],
        ['Alamat', 'alamat_pelapor'],
        ['No.Telp/HP', 'telp_pelapor'],
        ['E-Mail***', 'email_pelapor'],
    ];
    $terlapor = [
        ['Nama', 'nama_terlapor'],
        ['Alamat**', 'alamat_terlapor'],
        ['No.Telp/HP***', 'telp_terlapor'],
    ];
    $peristiwa = [
        ['Peristiwa', 'peristiwa', false],
        ['Tempat Kejadian', 'tempat_kejadian', false],
        ['Hari dan Tanggal Kejadian', 'tanggal_kejadian', true],
        ['Hari dan Tanggal diketahui', 'tanggal_diketahui', true],
    ];
    $saksi = $arsip->saksi ?? [];
@endphp

<div class="foot">{{ config('arsip.nama') }} &middot; Bawaslu Kabupaten Banyumas &middot; Kategori {{ $arsip->kategori->nama }} &middot; Dicetak {{ $dicetak }}</div>

<table class="kop">
    <tr>
        <td class="logo">
            @if ($logo)<img src="{{ $logo }}" alt="Bawaslu Kabupaten Banyumas">@endif
        </td>
        <td class="model"><span>FORMULIR MODEL B.1</span></td>
    </tr>
</table>

<div class="judul">
    <div class="nama">FORMULIR LAPORAN</div>
    <div class="nomor">Nomor: {{ $v('nomor_laporan') }} *</div>
</div>

{{-- 1. Identitas Pelapor --}}
<table class="bagian">
    <tr><td class="no">1.</td><td colspan="4" class="judul-bagian">Identitas Pelapor:</td></tr>
    @foreach ($pelapor as $i => [$label, $kolom])
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ chr(97 + $i) }}.</td>
            <td class="label">{{ $label }}</td>
            <td class="titik">:</td>
            <td class="isi">{!! nl2br(e($v($kolom))) !!}</td>
        </tr>
    @endforeach
</table>

{{-- 2. Identitas Terlapor --}}
<table class="bagian">
    <tr><td class="no">2.</td><td colspan="4" class="judul-bagian">Identitas Terlapor</td></tr>
    @foreach ($terlapor as $i => [$label, $kolom])
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ chr(97 + $i) }}.</td>
            <td class="label">{{ $label }}</td>
            <td class="titik">:</td>
            <td class="isi">{!! nl2br(e($v($kolom))) !!}</td>
        </tr>
    @endforeach
</table>

{{-- 3. Peristiwa yang dilaporkan --}}
<table class="bagian">
    <tr><td class="no">3.</td><td colspan="4" class="judul-bagian">Peristiwa yang dilaporkan:</td></tr>
    @foreach ($peristiwa as $i => [$label, $kolom, $panjang])
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ chr(97 + $i) }}.</td>
            <td class="label">{{ $label }}</td>
            <td class="titik">:</td>
            <td class="isi">{!! nl2br(e($v($kolom, $panjang))) !!}</td>
        </tr>
    @endforeach
</table>

{{-- 4. Saksi-saksi --}}
<table class="bagian">
    <tr><td class="no">4.</td><td colspan="5" class="judul-bagian">Saksi &ndash; saksi:***</td></tr>
    @forelse ($saksi as $i => $s)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ $i + 1 }})</td>
            <td class="sub-label">Nama</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['nama'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">Alamat</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['alamat'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">No.Telp/Hp</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['telp'] ?? '-' }}</td>
        </tr>
        @if (! $loop->last)<tr class="jarak"><td colspan="5">&nbsp;</td></tr>@endif
    @empty
        <tr>
            <td class="no"></td>
            <td colspan="4">- Tidak ada saksi -</td>
        </tr>
    @endforelse
</table>

{{-- 5. Bukti-bukti --}}
<table class="bagian">
    <tr><td class="no">5.</td><td colspan="2" class="judul-bagian">Bukti-Bukti:</td></tr>
    @forelse ($bukti as $i => $b)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ $i < 26 ? chr(97 + $i) : $i + 1 }}.</td>
            <td class="isi">{{ $b }}</td>
        </tr>
    @empty
        <tr><td class="no"></td><td colspan="2">-</td></tr>
    @endforelse
</table>

{{-- 6. Uraian kejadian --}}
<table class="bagian" style="margin-bottom:4pt">
    <tr><td class="no">6.</td><td class="judul-bagian">Uraian kejadian:</td></tr>
</table>
<div class="uraian">{!! nl2br(e($v('uraian_kejadian'))) !!}</div>

<table class="tempat-lapor">
    <tr><td class="l">Dilaporkan di</td><td class="t">:</td><td>{{ $v('tempat_dilaporkan') }}</td></tr>
    <tr><td class="l">Hari dan Tanggal</td><td class="t">:</td><td>{{ $v('tanggal_dilaporkan', true) }}</td></tr>
    <tr><td class="l">Pukul</td><td class="t">:</td><td>{{ $v('waktu_dilaporkan') }}</td></tr>
</table>

<div class="penutup">
    <div class="pernyataan">
        Saya menyatakan bahwa isi laporan ini adalah yang sebenar-benarnya dan saya bersedia mempertanggungjawabkannya di hadapan hukum.
    </div>

    <table class="ttd">
        <tr>
            <td>Penerima Laporan</td>
            <td>Pelapor</td>
        </tr>
        <tr><td class="ruang">&nbsp;</td><td class="ruang">&nbsp;</td></tr>
        <tr>
            <td><span class="nama-ttd">{{ $v('penerima_laporan') }}</span></td>
            <td><span class="nama-ttd">{{ $v('nama_pelapor') }}</span></td>
        </tr>
    </table>
</div>

<table class="ket">
    <tr><td colspan="2">Keterangan:</td></tr>
    <tr><td class="b">*</td><td>diberi nomor penyampaian laporan</td></tr>
    <tr><td class="b">**</td><td>jika alamat tempat tinggal Terlapor tidak lengkap/tidak diketahui, cukup disebutkan dusun/desa/kelurahan</td></tr>
    <tr><td class="b">***</td><td>tidak wajib diisi</td></tr>
</table>
</body>
</html>
