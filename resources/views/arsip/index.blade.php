@extends('layouts.app')
@php($jenisDef = $jenisAktif ? \App\Models\Arsip::definisi($jenisAktif) : null)
@section('title', $jenisDef ? $jenisDef['label'] : ($kategoriAktif ? 'Arsip '.$kategoriAktif->nama : 'Semua Arsip'))

@section('content')
@php($admin = auth()->user()->isAdmin())
{{-- Kolom tabel: mengikuti jenis arsip yang disaring (B.1, Tanda Terima, ...) atau kategori tambahan; "campur" jika beragam. --}}
@php($mode = $jenisAktif ?: ($kategoriAktif?->isSederhana() ? \App\Models\Arsip::JENIS_SEDERHANA : 'campur'))
@php($kolom = $mode === 'campur' ? collect() : collect(\App\Models\Arsip::fieldsFor($mode))->filter(fn ($f) => $f['list'] ?? false))

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500">
        @if ($jenisDef)
            <span class="font-medium text-slate-700">{{ $kategoriAktif->nama }} &rsaquo; {{ $jenisDef['label'] }}</span>
            ({{ $jenisDef['kode'] }}). Formulir PDF dibuat otomatis dari data arsip.
        @else
            {{ $kategoriAktif?->keterangan ?: ($admin ? 'Seluruh arsip yang diunggah petugas.' : 'Arsip yang Anda unggah.') }}
        @endif
    </p>
    @can('create', \App\Models\Arsip::class)
        <a class="btn btn-primary shrink-0 whitespace-nowrap" href="{{ $jenisAktif ? route('arsip.create', ['kategori' => $kategoriAktif->slug, 'jenis' => $jenisAktif]) : route('arsip.create', array_filter(['kategori' => $kategoriAktif && $kategoriAktif->isSederhana() ? $kategoriAktif->slug : null])) }}">
            <i class="ti ti-plus h-4 w-4"></i> Tambah arsip
        </a>
    @endcan
</div>

<div class="card p-4">
    <div class="overflow-x-auto">
        <table class="js-datatable display w-full" data-numbered>
            <thead>
                <tr>
                    <th class="no-sort">No</th>
                    @unless ($kategoriAktif)
                        <th>Kategori</th>
                    @endunless
                    @if ($mode === 'campur')
                        <th>Jenis arsip</th>
                        <th>Judul / nomor</th>
                        <th>Keterangan</th>
                        <th>Tanggal</th>
                    @else
                        @foreach ($kolom as $f)
                            <th>{{ $f['label'] }}</th>
                        @endforeach
                    @endif
                    @if ($admin)
                        <th>Diunggah oleh</th>
                    @endif
                    <th>Berkas</th>
                    <th class="no-sort">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($arsips as $a)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        @unless ($kategoriAktif)
                            <td class="whitespace-nowrap">{{ $a->kategori->nama }}</td>
                        @endunless
                        @if ($mode === 'campur')
                            <td>{{ $a->jenisNama() ?: '—' }}</td>
                            <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('arsip.show', $a) }}">{{ \Illuminate\Support\Str::limit($a->judulTampil(), 60) }}</a></td>
                            <td>{{ \Illuminate\Support\Str::limit($a->ringkasan(), 70) }}</td>
                            <td class="whitespace-nowrap" data-order="{{ $a->tanggalTampil()?->format('Y-m-d') }}">{{ $a->tanggalTampil()?->translatedFormat('d M Y') }}</td>
                        @else
                            @foreach ($kolom as $nama => $f)
                                @if (($f['type'] ?? '') === 'date')
                                    <td class="whitespace-nowrap" data-order="{{ $a->{$nama}?->format('Y-m-d') }}">{{ $a->displayValue($nama) }}</td>
                                @elseif ($loop->first)
                                    <td><a class="font-medium text-brand-700 hover:underline" href="{{ route('arsip.show', $a) }}">{{ \Illuminate\Support\Str::limit((string) $a->displayValue($nama), 70) }}</a></td>
                                @else
                                    <td>{{ \Illuminate\Support\Str::limit((string) $a->displayValue($nama), 70) }}</td>
                                @endif
                            @endforeach
                        @endif
                        @if ($admin)
                            <td>{{ $a->user->name }}</td>
                        @endif
                        <td class="whitespace-nowrap text-slate-600">
                            @if ($a->punyaBerkas())
                                <i class="ti ti-{{ $a->isPdf() ? 'file-text' : 'photo' }} mr-1 inline h-4 w-4 text-slate-400"></i>{{ $a->humanSize() }}
                            @else
                                <span class="text-slate-400">Tanpa lampiran</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a class="btn-icon" href="{{ route('arsip.show', $a) }}" title="Lihat dan preview" aria-label="Lihat dan preview arsip"><i class="ti ti-eye h-4 w-4"></i></a>
                                @if ($a->bisaCetakFormulir())
                                    <a class="btn-icon" data-turbo="false" href="{{ route('arsip.formulir', $a) }}" title="Unduh Formulir (PDF)" aria-label="Unduh formulir PDF"><i class="ti ti-file-download h-4 w-4"></i></a>
                                    <a class="btn-icon" data-turbo="false" href="{{ route('arsip.formulir.word', $a) }}" title="Unduh Formulir (Word)" aria-label="Unduh formulir Word"><i class="ti ti-file-text h-4 w-4"></i></a>
                                @endif
                                @if ($a->punyaBerkas())
                                    <a class="btn-icon" data-turbo="false" href="{{ route('arsip.unduh', $a) }}" title="Download berkas" aria-label="Download berkas"><i class="ti ti-download h-4 w-4"></i></a>
                                @endif
                                @can('update', $a)
                                    <a class="btn-icon" href="{{ route('arsip.edit', $a) }}" title="Edit" aria-label="Edit arsip"><i class="ti ti-pencil h-4 w-4"></i></a>
                                @endcan
                                @can('delete', $a)
                                    <form data-turbo="false" method="POST" action="{{ route('arsip.destroy', $a) }}" class="js-confirm-delete"
                                          data-message="Arsip {{ $a->label() }} beserta berkasnya akan dihapus permanen.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Hapus" aria-label="Hapus arsip"><i class="ti ti-trash h-4 w-4"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
