@extends('arsip.pdf._layout')

@section('judul', 'Formulir Temuan '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 34pt 56pt 52pt 62pt; }
@endsection

@section('badan')
    font-size: 9.5pt; line-height: 1.35;
@endsection

@section('css')
    @include('arsip.pdf._css_b')
    .uraian .nilai { margin-top: 6pt; }
    .penutup-b2 { margin-top: 12pt; page-break-before: always; page-break-inside: avoid; }
    .penutup-b2 .lembaga { margin-top: 12pt; }
    .penutup-b2 .blok-ttd { width: 210pt; margin-top: 12pt; page-break-inside: avoid; }
    .penutup-b2 .label-ttd { font-size: 9pt; margin-bottom: 4pt; }
    .penutup-b2 .ruang-ttd { width: 205pt; height: 84pt; border-bottom: 0.8pt solid #333; }
    .penutup-b2 .nama-penandatangan { margin-top: 5pt; font-weight: bold; text-decoration: underline; }
@endsection

@section('isi')
@php
    $v = fn (string $kolom, bool $panjang = false) => $arsip->displayValue($kolom, $panjang) ?: '-';

    // Bukti: a = Surat Tugas, b = Laporan Hasil Pengawasan, lalu bukti lain (c, d, dst).
    $daftarBukti = array_merge(
        ['Surat Tugas Pengawasan Nomor: '.$v('nomor_surat_tugas'), 'Laporan Hasil Pengawasan Nomor: '.$v('nomor_lhp')],
        $bukti
    );
@endphp

@include('arsip.pdf._kop_b')

<div class="judul">
    <div class="nama">FORMULIR TEMUAN</div>
    <div class="nomor">Nomor: {{ $v('nomor_laporan') }} *</div>
</div>

@include('arsip.pdf._b_bagian', ['no' => '1.', 'judul' => 'Data Pengawas yang menemukan:**', 'baris' => [
    ['Nama', $v('nama_pengawas')],
    ['Jabatan', $v('jabatan_pengawas')],
    ['Alamat', $v('alamat_pengawas')],
]])

@include('arsip.pdf._b_bagian', ['no' => '2.', 'judul' => 'Identitas Terlapor', 'baris' => [
    ['Nama', $v('nama_terlapor')],
    ['Alamat***', $v('alamat_terlapor')],
    ['No.Telp/HP****', $v('telp_terlapor')],
]])

@include('arsip.pdf._b_bagian', ['no' => '3.', 'judul' => 'Peristiwa yang ditemukan:', 'baris' => [
    ['Peristiwa', $v('peristiwa')],
    ['Tempat Kejadian', $v('tempat_kejadian')],
    ['Hari dan Tanggal Kejadian', $v('tanggal_kejadian', true)],
    ['Hari dan Tanggal diketahui', $v('tanggal_diketahui', true)],
]])

@include('arsip.pdf._b_saksi', ['no' => '4.', 'judul' => 'Saksi –saksi:****'])

<table class="bagian">
    <tr><td class="no">5.</td><td colspan="2" class="judul-bagian">Bukti-Bukti:</td></tr>
    @foreach ($daftarBukti as $i => $b)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ \App\Support\Formulir::huruf($i) }}.</td>
            <td class="isi">{{ $b }}</td>
        </tr>
    @endforeach
</table>

<table class="bagian" style="margin-bottom:4pt">
    <tr><td class="no">6.</td><td class="judul-bagian">Uraian singkat kejadian:</td></tr>
</table>
<div class="uraian">
    <div>(diuraikan secara kronologis peristiwa apa yang dianggap sebagai dugaan pelanggaran pemilu, di mana, kapan, dan bagaimana peristiwa tersebut terjadi)</div>
    <div class="nilai">{!! nl2br(e($v('uraian_kejadian'))) !!}</div>
</div>

<div class="penutup-b2">
    <div>{{ $v('tempat_dilaporkan') }}, tanggal {{ $arsip->tanggal_dilaporkan ? $arsip->tanggal_dilaporkan->translatedFormat('d F Y') : '-' }}</div>
    <div class="lembaga">{{ $v('lembaga') }}*****</div>
    <div class="blok-ttd">
        <div class="label-ttd">Tanda tangan:</div>
        <div class="ruang-ttd">&nbsp;</div>
        <div class="nama-penandatangan">{{ $v('nama_penandatangan') }}</div>
    </div>
</div>

<table class="ket">
    <tr><td class="b">*</td><td>diberi nomor register Temuan</td></tr>
    <tr><td class="b">**</td><td>diisi nama Pengawas Pemilu yang menemukan dugaan pelanggaran</td></tr>
    <tr><td class="b">***</td><td>jika alamat tempat tinggal Terlapor tidak lengkap/tidak diketahui, cukup disebutkan dusun/desa/kelurahan</td></tr>
    <tr><td class="b">****</td><td>tidak wajib diisi</td></tr>
    <tr><td class="b">*****</td><td>diisi sesuai dengan nama lembaganya</td></tr>
</table>

@include('arsip.pdf._lampiran_foto')
@endsection
