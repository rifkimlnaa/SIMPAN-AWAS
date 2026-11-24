@extends('arsip.pdf._layout')

@section('judul', 'Lampiran foto bukti')

@section('halaman')
    @page { margin: 34pt 56pt 52pt 62pt; }
@endsection

@section('badan')
    font-size: 9.5pt; line-height: 1.35;
@endsection

@section('css')
    @include('arsip.pdf._css_b')
@endsection

@section('isi')
    @include('arsip.pdf._lampiran_foto', ['tanpaJeda' => true])
@endsection
