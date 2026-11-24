@extends('arsip.pdf._layout')

@section('judul', 'Tanda Terima Dokumen '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 60pt 71pt 50pt 71pt; }
@endsection

@section('badan')
    font-size: 10.5pt; line-height: 1.3;
@endsection

@section('css')
    @include('arsip.pdf._css_ps')
    .judul { text-align: center; margin: 26pt 0 18pt; }
    .judul div { line-height: 1.6; }
    .alinea { text-align: justify; line-height: 1.5; margin: 0 0 6pt; }
    table.tbl { margin-left: 0; width: 100%; }
    table.tbl td { font-size: 10pt; }
    table.tbl td.c-ket { width: 118pt; }
    .penutup-ps { margin-top: 12pt; text-align: justify; font-size: 9pt; line-height: 1.4; page-break-inside: avoid; }
    .ttd-ps { margin-top: 12pt; font-size: 9.5pt; }
    .ttd-ps .ruang { height: 52pt; }
@endsection

@section('isi')
@php
    $nama = mb_strtoupper((string) $arsip->nama_pemohon);
    $penyerah = mb_strtoupper((string) $arsip->nama_penyerah);
    $waktuSql = $arsip->waktu_dilaporkan ? substr((string) $arsip->waktu_dilaporkan, 0, 5) : null;
@endphp

@include('arsip.pdf._kop_ps', ['lebarKotak' => 150])

<div class="judul">
    <div>TANDA TERIMA DOKUMEN</div>
    <div>Nomor : {{ $arsip->nomor_laporan ?: '-' }}</div>
</div>

<div class="alinea">
    Pada hari ini {{ \App\Support\Terbilang::hariTanggal($arsip->tanggal_dilaporkan, $waktuSql) }}, telah diterima dokumen permohonan Pemohon atas nama:
</div>
<div class="alinea">1.&nbsp; {{ $nama }}</div>
<div class="alinea">
    sebagai Pemohon dalam Penyelesaian Sengketa Proses Pemilu yang diserahkan oleh {{ $penyerah }} dengan rincian sebagai berikut,
</div>

<table class="tbl">
    <tr>
        <th rowspan="2" class="no">No</th>
        <th rowspan="2">Jenis Dokumen</th>
        <th colspan="2">Jumlah</th>
        <th rowspan="2" class="c-ket">Keterangan</th>
    </tr>
    <tr>
        <th style="width:58pt">Dokumen Cetak</th>
        <th style="width:58pt">Dokumen Digital</th>
    </tr>
    @foreach ($arsip->dokumen ?? [] as $i => $d)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>@include('arsip.pdf._butir', ['teks' => $d['jenis'] ?? '', 'judulPertama' => true])</td>
            <td class="tengah">{{ $d['cetak'] ?? '' }}</td>
            <td class="tengah">{{ $d['digital'] ?? '' }}</td>
            <td>@include('arsip.pdf._butir', ['teks' => $d['keterangan'] ?? '', 'judulPertama' => false])</td>
        </tr>
    @endforeach
</table>

<div class="penutup-ps">
    yang diserahkan saat pengajuan permohonan secara langsung oleh Pemohon, Petugas Penerima permohonan telah menerima, memeriksa dan mencatat dokumen di atas.
</div>

<table class="ttd-ps">
    <tr><td style="width:50%">Yang menyerahkan,</td><td style="width:50%">Petugas Penerima,</td></tr>
    <tr><td class="ruang">&nbsp;</td><td class="ruang">&nbsp;</td></tr>
    <tr><td>({{ $penyerah }})</td><td>({{ mb_strtoupper((string) $arsip->penerima_laporan) }})</td></tr>
</table>
@endsection
