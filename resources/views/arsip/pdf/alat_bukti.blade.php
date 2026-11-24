@extends('arsip.pdf._layout')

@section('judul', 'Daftar Alat Bukti Pemohon')

@section('halaman')
    @page { margin: 85pt 71pt 40pt 71pt; }
@endsection

@section('badan')
    font-size: 10.5pt; line-height: 1.3;
@endsection

@section('css')
    .kotak-ps { text-align: right; }
    .kotak-ps div { display: inline-block; width: 113pt; border: 1px solid #000; padding: 3.5pt 4pt; font-size: 7pt; line-height: 1.3; text-align: center; }
    .judul-ab { text-align: center; font-weight: bold; margin-top: 34pt; line-height: 1.7; }
    .judul-ab div + div { margin-top: 8pt; }
    .alamat-ab { margin-top: 40pt; line-height: 1.75; }
    .alinea { text-align: justify; line-height: 1.7; margin-top: 18pt; }
    table.tbl { width: 447pt; margin: 18pt 0 0 11pt; border: 1px solid #000; }
    table.tbl th, table.tbl td { border: 1px solid #000; padding: 2pt 5pt; font-size: 10pt; }
    table.tbl th { text-align: center; font-weight: bold; }
    .demikian { text-align: justify; line-height: 1.9; margin-top: 18pt; }
    .tgl { margin-top: 26pt; margin-left: 330pt; }
    .hormat { margin-top: 12pt; margin-left: 262pt; }
    .tt { margin-top: 62pt; margin-left: 236pt; width: 230pt; text-align: center; }
    .catatan { margin-top: 22pt; margin-left: 6pt; font-size: 6.5pt; font-style: italic; line-height: 1.4; }
    .catatan td { vertical-align: top; }
    .catatan td.k { width: 48pt; }
    .catatan td.t { width: 12pt; }
@endsection

@section('isi')
@php
    $tanggalSurat = $arsip->tanggal_dilaporkan ? $arsip->tanggal_dilaporkan->translatedFormat('d F Y') : '-';
@endphp

<div class="kotak-ps"><div>FORMULIR MODEL<br>PSPP-10<br>DAFTAR ALAT BUKTI</div></div>

<div class="judul-ab">
    <div>DAFTAR ALAT BUKTI PEMOHON</div>
    <div>DALAM PERMOHONAN PENYELESAIAN SENGKETA PROSES PEMILU</div>
</div>

<div class="alamat-ab">
    Kepada Yth.<br>
    Ketua {{ $arsip->tujuan_instansi ?: '-' }}***)<br>
    di-<br>
    {{ $arsip->tujuan_lokasi ?: '-' }}
</div>

<div class="alinea" style="margin-top:22pt">
    Dengan hormat,<br>
    Sehubungan dengan Permohonan Penyelesaian Sengketa, dengan ini kami selaku Pemohon mengajukan bukti sebagai berikut:
</div>

<table class="tbl">
    <tr>
        <th style="width:80pt">NO</th>
        <th style="width:140pt">NAMA ALAT BUKTI</th>
        <th>KETERANGAN</th>
    </tr>
    @foreach ($arsip->dokumen ?? [] as $i => $d)
        <tr>
            <td>P-{{ $i + 1 }}</td>
            <td>{{ $d['nama'] ?? '' }}</td>
            <td>{{ $d['keterangan'] ?? '' }}</td>
        </tr>
    @endforeach
</table>

<div class="demikian">
    Demikian daftar alat bukti <strong>Pemohon</strong> dalam Permohonan Penyelesaian Sengketa atas perkenannya diucapkan terima kasih.
</div>

<div class="tgl">{{ $arsip->tempat_dilaporkan ?: '-' }}, {{ $tanggalSurat }}****)</div>
<div class="hormat">Hormat kami,</div>
<div class="tt">({{ mb_strtoupper((string) $arsip->nama_pemohon) }})</div>

<table class="catatan">
    <tr><td colspan="3">Catatan:</td></tr>
    <tr><td class="k">*)</td><td class="t">:</td><td>Diisi sesuai dengan pihak yang mengajukan daftar alat bukti</td></tr>
    <tr><td class="k">**)</td><td class="t">:</td><td>Diisi sesuai dengan Nomor Register Permohonan yang menjadi acuan pengajuan daftar alat bukti</td></tr>
    <tr><td class="k">***)</td><td class="t">:</td><td>Diisi sesuai dengan tingkatan wilayah dan nama daerah</td></tr>
    <tr><td class="k">****)</td><td class="t">:</td><td>Diisi sesuai dengan tempat dan tanggal penyusunan daftar alat bukti</td></tr>
</table>
@endsection
