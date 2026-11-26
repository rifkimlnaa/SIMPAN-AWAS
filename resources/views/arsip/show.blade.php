@extends('layouts.app')
@section('title', 'Detail Arsip')

@php($jenisArsip = $arsip->jenis_arsip ?: \App\Models\Arsip::JENIS_SEDERHANA)
@php($perGrup = collect(\App\Models\Arsip::fieldsFor($jenisArsip))->groupBy('group', true))
@php($barisDef = \App\Models\Arsip::barisFor($jenisArsip))

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <h2 class="break-words text-lg font-semibold text-slate-900">{{ $arsip->label() }}</h2>
        <p class="text-sm text-slate-500">Diunggah {{ $arsip->created_at->translatedFormat('d F Y, H:i') }} oleh {{ $arsip->user->name }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a class="btn btn-light" href="{{ route('arsip.index', array_filter(['kategori' => $arsip->kategori->slug, 'jenis' => $arsip->isSederhana() ? null : $arsip->jenis_arsip])) }}"><i class="ti ti-arrow-left h-4 w-4"></i> Kembali</a>
        @if ($arsip->bisaCetakFormulir())
            <a class="btn btn-primary" data-turbo="false" href="{{ route('arsip.formulir', $arsip) }}"><i class="ti ti-file-download h-4 w-4"></i> Formulir PDF</a>
            @if (in_array($arsip->jenis_arsip, ['formulir_laporan', 'formulir_temuan'], true))
                <a class="btn btn-light" href="#preview-formulir"><i class="ti ti-eye h-4 w-4"></i> Preview lengkap</a>
            @endif
            <a class="btn btn-light" data-turbo="false" href="{{ route('arsip.formulir.word', $arsip) }}"><i class="ti ti-file-text h-4 w-4"></i> Formulir Word</a>
        @endif
        @if ($arsip->punyaBerkas() && ! \App\Models\Arsip::punyaFotoBukti($arsip->jenis_arsip))
            <a class="btn {{ $arsip->isSederhana() ? 'btn-primary' : 'btn-light' }}" data-turbo="false" href="{{ route('arsip.unduh', $arsip) }}"><i class="ti ti-download h-4 w-4"></i> {{ $arsip->isSederhana() ? 'Download berkas' : 'Berkas scan' }}</a>
        @endif
        @can('update', $arsip)
            <a class="btn btn-light" href="{{ route('arsip.edit', $arsip) }}"><i class="ti ti-pencil h-4 w-4"></i> Edit</a>
        @endcan
        @can('delete', $arsip)
            <form data-turbo="false" method="POST" action="{{ route('arsip.destroy', $arsip) }}" class="js-confirm-delete"
                  data-message="Arsip {{ $arsip->label() }} beserta berkasnya akan dihapus permanen.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-ghost"><i class="ti ti-trash h-4 w-4"></i> Hapus</button>
            </form>
        @endcan
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-5">
    <div class="space-y-6 xl:col-span-3">
        @foreach (\App\Models\Arsip::groupsFor($jenisArsip) as $kunci => $judul)
            @if ($kunci === 'saksi')
                @include('arsip._show_saksi')
                @continue
            @endif

            @if ($kunci === 'foto')
                @continue
            @endif

            @if ($kunci === 'dokumen')
                @include('arsip._show_baris')
                @continue
            @endif

            @continue(! isset($perGrup[$kunci]))

            <section class="card p-6">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
                    @if ($kunci === 'umum')
                        <span class="flex flex-wrap items-center justify-end gap-1.5">
                            <span class="badge">{{ $arsip->kategori->nama }}</span>
                            @if ($arsip->jenisNama())<span class="badge badge-gray">{{ $arsip->jenisNama() }}</span>@endif
                        </span>
                    @endif
                </div>

                <dl class="mt-5 grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
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
        @endforeach

        @unless (\App\Models\Arsip::punyaFotoBukti($arsip->jenis_arsip))
        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">Berkas lampiran</h2>
            @if ($arsip->punyaBerkas())
                <dl class="mt-4 text-sm">
                    <dt class="text-slate-500">Nama berkas</dt>
                    <dd class="mt-0.5 break-all text-slate-900">{{ $arsip->file_name }} <span class="text-slate-400">({{ $arsip->humanSize() }})</span></dd>
                </dl>
            @else
                <p class="mt-3 text-sm text-slate-500">Tidak ada berkas lampiran pada arsip ini.</p>
            @endif
        </section>
        @endunless
    </div>

    @if (in_array($arsip->jenis_arsip, ['formulir_laporan', 'formulir_temuan'], true))
        <div id="preview-formulir" class="self-start xl:sticky xl:top-24 xl:col-span-2">
            <section class="card p-5">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Preview formulir lengkap</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Formulir {{ $arsip->jenis_arsip === 'formulir_laporan' ? 'B.1' : 'B.2' }}
                            @if ($arsip->fotos->isNotEmpty()) beserta {{ $arsip->fotos->count() }} foto bukti pada halaman Lampiran Bukti @else beserta halaman lanjutan dan keterangan @endif.
                        </p>
                    </div>
                    <a href="{{ route('arsip.formulir.preview', $arsip) }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 hover:underline">Buka dokumen penuh</a>
                </div>
                <div class="preview-box preview-solid" style="height:48rem">
                    <iframe src="{{ route('arsip.formulir.preview', $arsip) }}#toolbar=1&navpanes=0&view=FitH"
                            title="Preview lengkap {{ $arsip->jenis_arsip === 'formulir_laporan' ? 'Formulir B.1' : 'Formulir B.2' }}"
                            loading="lazy"></iframe>
                </div>
                @if ($arsip->fotos->isEmpty())
                    <p class="mt-3 text-xs text-slate-500">Belum ada foto bukti. Jika foto ditambahkan melalui Edit Arsip, foto akan muncul pada halaman khusus &ldquo;LAMPIRAN BUKTI&rdquo; setelah halaman formulir.</p>
                @else
                    <p class="mt-3 text-xs text-slate-500">Urutan dokumen: halaman formulir, halaman pernyataan/tanda tangan/keterangan, lalu halaman &ldquo;LAMPIRAN BUKTI&rdquo;.</p>
                @endif
            </section>
        </div>
    @else
    <section class="card self-start p-5 xl:sticky xl:top-24 xl:col-span-2">
        @if ($arsip->punyaBerkas())
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
        @else
            <h2 class="text-sm font-semibold text-slate-900">Preview berkas</h2>
            <p class="mt-3 text-sm text-slate-500">Belum ada lampiran yang diunggah.</p>
        @endif
    </section>
    @endif
</div>
@endsection
