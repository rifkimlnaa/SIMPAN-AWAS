@extends('layouts.app')
@section('title', 'Detail Arsip')

@php($jenis = $arsip->kategori->jenis)
@php($perGrup = collect(\App\Models\Arsip::fieldsFor($jenis))->groupBy('group', true))

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <h2 class="break-words text-lg font-semibold text-slate-900">{{ $arsip->label() }}</h2>
        <p class="text-sm text-slate-500">Diunggah {{ $arsip->created_at->translatedFormat('d F Y, H:i') }} oleh {{ $arsip->user->name }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a class="btn btn-light" href="{{ route('arsip.index', ['kategori' => $arsip->kategori->slug]) }}"><i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali</a>
        @unless ($arsip->kategori->isSederhana())
            <a class="btn btn-primary" href="{{ route('arsip.formulir', $arsip) }}"><i data-lucide="file-down" class="h-4 w-4"></i> Formulir PDF</a>
        @endunless
        <a class="btn {{ $arsip->kategori->isSederhana() ? 'btn-primary' : 'btn-light' }}" href="{{ route('arsip.unduh', $arsip) }}"><i data-lucide="download" class="h-4 w-4"></i> {{ $arsip->kategori->isSederhana() ? 'Download berkas' : 'Berkas scan' }}</a>
        @can('update', $arsip)
            <a class="btn btn-light" href="{{ route('arsip.edit', $arsip) }}"><i data-lucide="pencil" class="h-4 w-4"></i> Edit</a>
        @endcan
        @can('delete', $arsip)
            <form method="POST" action="{{ route('arsip.destroy', $arsip) }}" class="js-confirm-delete"
                  data-message="Arsip {{ $arsip->label() }} beserta berkasnya akan dihapus permanen.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-ghost"><i data-lucide="trash-2" class="h-4 w-4"></i> Hapus</button>
            </form>
        @endcan
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-5">
    <div class="space-y-6 xl:col-span-3">
        @foreach (\App\Models\Arsip::groupsFor($jenis) as $kunci => $judul)
            @continue(! isset($perGrup[$kunci]))

            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
                    @if ($kunci === 'umum')<span class="badge">{{ $arsip->kategori->nama }}</span>@endif
                </div>

                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    @foreach ($perGrup[$kunci] as $nama => $f)
                        @php($nilai = $arsip->displayValue($nama, true))
                        @php($lebar = ($f['type'] ?? '') === 'textarea' || ($f['wide'] ?? false))
                        <div class="{{ $lebar ? 'sm:col-span-2' : '' }}">
                            <dt class="text-slate-500">{{ $f['label'] }}</dt>
                            <dd class="mt-0.5 text-slate-900">@if (filled($nilai)){!! nl2br(e($nilai)) !!}@else<span class="text-slate-400">&mdash;</span>@endif</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($kunci === 'peristiwa')
                <section class="card p-6">
                    <h2 class="text-base font-semibold text-slate-900">Saksi-saksi</h2>
                    @if (empty($arsip->saksi))
                        <p class="mt-3 text-sm text-slate-500">Tidak ada saksi yang dicatat.</p>
                    @else
                        <ul class="mt-3 divide-y divide-slate-100">
                            @foreach ($arsip->saksi as $i => $s)
                                <li class="py-3 text-sm">
                                    <p class="font-medium text-slate-900">{{ $i + 1 }}. {{ $s['nama'] ?? '—' }}</p>
                                    <p class="text-slate-500">
                                        {{ $s['alamat'] ?? 'Alamat tidak dicatat' }}@if (! empty($s['telp'])), {{ $s['telp'] }}@endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        @endforeach

        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">Berkas</h2>
            <dl class="mt-4 text-sm">
                <dt class="text-slate-500">Nama berkas</dt>
                <dd class="mt-0.5 break-all text-slate-900">{{ $arsip->file_name }} <span class="text-slate-400">({{ $arsip->humanSize() }})</span></dd>
            </dl>
        </section>
    </div>

    <section class="card self-start p-5 xl:sticky xl:top-24 xl:col-span-2">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Preview berkas</h2>
            <a href="{{ route('arsip.berkas', $arsip) }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 hover:underline">Buka di tab baru</a>
        </div>
        <div class="preview-box preview-solid" style="height:34rem">
            @if ($arsip->isPdf())
                <iframe src="{{ route('arsip.berkas', $arsip) }}" title="Pratinjau PDF {{ $arsip->file_name }}"></iframe>
            @elseif ($arsip->isImage())
                <img src="{{ route('arsip.berkas', $arsip) }}" alt="Pratinjau {{ $arsip->file_name }}">
            @else
                <p class="px-6">Jenis berkas ini tidak dapat dipratinjau. Silakan download.</p>
            @endif
        </div>
    </section>
</div>
@endsection
