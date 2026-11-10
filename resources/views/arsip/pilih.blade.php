@extends('layouts.app')
@section('title', 'Tambah Arsip')

@section('content')
<p class="mb-5 text-sm text-slate-500">Pilih kategori arsip. Isian formulir menyesuaikan kategori yang dipilih.</p>

@if ($kategoris->isEmpty())
    <div class="card p-10 text-center text-sm text-slate-500">Belum ada kategori. Hubungi admin untuk menambahkannya.</div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kategoris as $k)
            <a href="{{ route('arsip.create', ['kategori' => $k->slug]) }}"
               class="card block p-5 transition hover:border-brand-500 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <div class="flex items-center justify-between gap-3">
                    <span class="badge">{{ $k->nama }}</span>
                    <span class="badge badge-gray">{{ $k->jenisLabel() }}</span>
                </div>
                <p class="mt-3 min-h-10 text-sm text-slate-600">{{ $k->keterangan ?: 'Tanpa keterangan.' }}</p>
                <p class="mt-3 text-xs text-slate-400">{{ number_format($k->arsips_count, 0, ',', '.') }} arsip</p>
            </a>
        @endforeach
    </div>
@endif
@endsection
