@extends('layouts.app')
@section('title', $kategoriAktif ? 'Arsip '.$kategoriAktif->nama : 'Semua Arsip')

@section('content')
@php($admin = auth()->user()->isAdmin())
{{-- Kolom tabel: mengikuti jenis kategori yang disaring; "campur" jika menampilkan semua kategori. --}}
@php($mode = $kategoriAktif ? $kategoriAktif->jenis : 'campur')
@php($kolom = $mode === 'campur' ? collect() : collect(\App\Models\Arsip::fieldsFor($mode))->filter(fn ($f) => $f['list'] ?? false))

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500">
        {{ $kategoriAktif?->keterangan ?: ($admin ? 'Seluruh arsip yang diunggah petugas.' : 'Arsip yang Anda unggah.') }}
    </p>
    @can('create', \App\Models\Arsip::class)
        <a class="btn btn-primary" href="{{ route('arsip.create', array_filter(['kategori' => $kategoriAktif?->slug])) }}">
            <i data-lucide="plus" class="h-4 w-4"></i> Tambah arsip
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
                            <td><span class="badge">{{ $a->kategori->nama }}</span></td>
                        @endunless
                        @if ($mode === 'campur')
                            <td class="font-medium text-slate-900">{{ \Illuminate\Support\Str::limit($a->judulTampil(), 60) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($a->ringkasan(), 70) }}</td>
                            <td class="whitespace-nowrap" data-order="{{ $a->tanggalTampil()?->format('Y-m-d') }}">{{ $a->tanggalTampil()?->translatedFormat('d M Y') }}</td>
                        @else
                            @foreach ($kolom as $nama => $f)
                                @if (($f['type'] ?? '') === 'date')
                                    <td class="whitespace-nowrap" data-order="{{ $a->{$nama}?->format('Y-m-d') }}">{{ $a->displayValue($nama) }}</td>
                                @else
                                    <td>{{ \Illuminate\Support\Str::limit((string) $a->displayValue($nama), 70) }}</td>
                                @endif
                            @endforeach
                        @endif
                        @if ($admin)
                            <td>{{ $a->user->name }}</td>
                        @endif
                        <td class="whitespace-nowrap text-slate-600">
                            <i data-lucide="{{ $a->isPdf() ? 'file-text' : 'image' }}" class="mr-1 inline h-4 w-4 text-slate-400"></i>{{ $a->humanSize() }}
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a class="btn-icon" href="{{ route('arsip.show', $a) }}" title="Lihat dan preview" aria-label="Lihat dan preview arsip"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                @unless ($a->kategori->isSederhana())
                                    <a class="btn-icon" href="{{ route('arsip.formulir', $a) }}" title="Unduh Formulir (PDF)" aria-label="Unduh formulir PDF"><i data-lucide="file-down" class="h-4 w-4"></i></a>
                                @endunless
                                <a class="btn-icon" href="{{ route('arsip.unduh', $a) }}" title="Download berkas" aria-label="Download berkas"><i data-lucide="download" class="h-4 w-4"></i></a>
                                @can('update', $a)
                                    <a class="btn-icon" href="{{ route('arsip.edit', $a) }}" title="Edit" aria-label="Edit arsip"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                @endcan
                                @can('delete', $a)
                                    <form method="POST" action="{{ route('arsip.destroy', $a) }}" class="js-confirm-delete"
                                          data-message="Arsip {{ $a->label() }} beserta berkasnya akan dihapus permanen.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Hapus" aria-label="Hapus arsip"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
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
