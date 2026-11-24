@extends('arsip.pdf._layout')

@section('judul', 'Berita Acara Verifikasi Permohonan '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 56pt 71pt 50pt 71pt; }
@endsection

@section('badan')
    font-size: 10.5pt; line-height: 1.3;
@endsection

@section('css')
    @include('arsip.pdf._css_ps')
    .judul { text-align: center; margin: 24pt 0 22pt; }
    .judul div { line-height: 1.6; }
    .alinea { text-align: justify; line-height: 1.7; margin: 0 0 8pt; }
    .alinea.masuk { text-indent: 36pt; }
    table.tbl { width: 467pt; margin-top: 4pt; }
    table.tbl td { font-size: 10pt; }
    .kesimpulan { margin-top: 12pt; text-align: justify; line-height: 1.9; }
    .ketua { margin-top: 20pt; margin-left: 245pt; width: 222pt; text-align: center; page-break-inside: avoid; }
    .ketua .ruang { height: 62pt; }
@endsection

@section('isi')
@php
    $lembaga = $arsip->lembaga ?: config('arsip.kantor.nama');
    $lengkap = $arsip->hasil_verifikasi !== 'Tidak lengkap';
    $waktuSql = $arsip->waktu_dilaporkan ? substr((string) $arsip->waktu_dilaporkan, 0, 5) : null;
@endphp

@include('arsip.pdf._kop_ps', ['lebarKotak' => 190, 'baris2' => 'BERITA ACARA VERIFIKASI PERMOHONAN<br>PENYELESAIAN SENGKETA PROSES PEMILU'])

<div class="judul">
    <div>BERITA ACARA</div>
    <div>VERIFIKASI PERMOHONAN PENYELESAIAN SENGKETA PROSES PEMILU</div>
</div>

<div class="alinea masuk">
    Pada hari ini {{ \App\Support\Terbilang::hariTanggal($arsip->tanggal_dilaporkan, $waktuSql) }}, {{ $lembaga }} telah melakukan verifikasi dokumen Permohonan Penyelesaian Sengketa Proses Pemilu dengan Nomor Tanda Terima {{ $arsip->nomor_laporan ?: '-' }} dengan hasil sebagai berikut,
</div>

<table class="tbl">
    <tr>
        <th rowspan="2" class="no">No</th>
        <th rowspan="2">Jenis Dokumen</th>
        <th colspan="2">Jumlah</th>
        <th rowspan="2" style="width:130pt">Hasil Pemeriksaan</th>
    </tr>
    <tr>
        <th style="width:52pt">Dokumen Cetak</th>
        <th style="width:52pt">Dokumen Digital</th>
    </tr>
    @foreach ($arsip->dokumen ?? [] as $i => $d)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>@include('arsip.pdf._butir', ['teks' => $d['jenis'] ?? '', 'judulPertama' => true])</td>
            <td class="tengah">{{ $d['cetak'] ?? '' }}</td>
            <td class="tengah">{{ $d['digital'] ?? '' }}</td>
            <td class="tengah">{{ $d['hasil'] ?? '' }}</td>
        </tr>
    @endforeach
</table>

<div class="kesimpulan">
    @if ($lengkap)
        Berdasarkan hasil verifikasi formil dan materiil terhadap dokumen permohonan yang diajukan oleh Pemohon, permohonan penyelesaian sengketa Proses Pemilu dinyatakan lengkap;
        <br><br>
        Karena Permohonan telah memenuhi syarat formil dan materiil maka dapat diterima oleh {{ $lembaga }}
    @else
        Berdasarkan hasil verifikasi formil dan materiil terhadap dokumen permohonan yang diajukan oleh Pemohon, permohonan penyelesaian sengketa Proses Pemilu dinyatakan tidak lengkap;
        <br><br>
        Karena Permohonan belum memenuhi syarat formil dan materiil maka belum dapat diterima oleh {{ $lembaga }}
    @endif
</div>

<div class="ketua">
    <div>KETUA,</div>
    <div class="ruang">&nbsp;</div>
    <div>({{ mb_strtoupper((string) $arsip->nama_penandatangan) }})</div>
</div>
@endsection
