@extends('arsip.pdf._layout')

@section('judul', 'Formulir Laporan '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 34pt 56pt 52pt 62pt; }
@endsection

@section('badan')
    font-size: 9.5pt; line-height: 1.35;
@endsection

@section('css')
    @include('arsip.pdf._css_b')
    .uraian .nilai { margin-top: 6pt; }
    .tempat-lapor { margin-top: 10pt; }
    .tempat-lapor td.l { width: 92pt; }
    .tempat-lapor td.t { width: 10pt; }
    .pernyataan { margin: 10pt 0 12pt; text-align: justify; }
    .penutup { page-break-before: always; break-before: page; }
@endsection

@section('isi')
@php
    $v = fn (string $kolom, bool $panjang = false) => $arsip->displayValue($kolom, $panjang) ?: '-';
@endphp

@include('arsip.pdf._kop_b')

<div class="judul">
    <div class="nama">FORMULIR LAPORAN</div>
    <div class="nomor">Nomor: {{ $v('nomor_laporan') }} *</div>
</div>

@include('arsip.pdf._b_bagian', ['no' => '1.', 'judul' => 'Identitas Pelapor:', 'baris' => [
    ['Nama', $v('nama_pelapor')],
    ['Tempat/Tgl Lahir', $v('ttl_pelapor')],
    ['Jenis Kelamin', $v('jenis_kelamin_pelapor')],
    ['Pekerjaan', $v('pekerjaan_pelapor')],
    ['Kewarganegaraan', $v('kewarganegaraan_pelapor')],
    ['Alamat', $v('alamat_pelapor')],
    ['No.Telp/HP', $v('telp_pelapor')],
    ['E-Mail***', $v('email_pelapor')],
]])

@include('arsip.pdf._b_bagian', ['no' => '2.', 'judul' => 'Identitas Terlapor', 'baris' => [
    ['Nama', $v('nama_terlapor')],
    ['Alamat**', $v('alamat_terlapor')],
    ['No.Telp/HP***', $v('telp_terlapor')],
]])

@include('arsip.pdf._b_bagian', ['no' => '3.', 'judul' => 'Peristiwa yang dilaporkan:', 'baris' => [
    ['Peristiwa', $v('peristiwa')],
    ['Tempat Kejadian', $v('tempat_kejadian')],
    ['Hari dan Tanggal Kejadian', $v('tanggal_kejadian', true)],
    ['Hari dan Tanggal diketahui', $v('tanggal_diketahui', true)],
]])

@include('arsip.pdf._b_saksi', ['no' => '4.', 'judul' => 'Saksi –saksi:***'])

<table class="bagian">
    <tr><td class="no">5.</td><td colspan="2" class="judul-bagian">Bukti-Bukti:</td></tr>
    @forelse ($bukti as $i => $b)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ \App\Support\Formulir::huruf($i) }}.</td>
            <td class="isi">{{ $b }}</td>
        </tr>
    @empty
        <tr><td class="no"></td><td colspan="2">-</td></tr>
    @endforelse
</table>

<table class="bagian" style="margin-bottom:4pt">
    <tr><td class="no">6.</td><td class="judul-bagian">Uraian kejadian:</td></tr>
</table>
<div class="uraian">
    <div>(diuraikan secara kronologis peristiwa apa yang dianggap sebagai dugaan pelanggaran pemilu, di mana, kapan, dan bagaimana peristiwa tersebut terjadi)</div>
    <div class="nilai">{!! nl2br(e($v('uraian_kejadian'))) !!}</div>
</div>

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

@include('arsip.pdf._lampiran_foto')
@endsection
