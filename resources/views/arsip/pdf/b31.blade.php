@extends('arsip.pdf._layout')

@section('judul', 'Tanda Bukti Perbaikan Laporan '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 34pt 56pt 52pt 62pt; }
@endsection

@section('badan')
    font-size: 9.5pt; line-height: 1.35;
@endsection

@section('css')
    @include('arsip.pdf._css_tanda_bukti')
@endsection

@section('isi')
    @include('arsip.pdf._tanda_bukti', [
        'judulFormulir' => 'TANDA BUKTI PERBAIKAN LAPORAN',
        'keteranganNomor' => 'diberi nomor yang sama dengan Tanda Bukti Penyampaian Laporan',
    ])
@endsection
