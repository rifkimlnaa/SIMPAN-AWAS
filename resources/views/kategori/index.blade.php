@extends('layouts.app')
@section('title', 'Kategori Arsip')

@section('content')
@php($admin = auth()->user()->isAdmin())

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500">Jenis permohonan yang dikelompokkan dalam arsip.</p>
    @if ($admin)
        <a class="btn btn-primary" href="{{ route('kategori.create') }}"><i data-lucide="plus" class="h-4 w-4"></i> Tambah kategori</a>
    @endif
</div>

<div class="card p-4">
    <div class="overflow-x-auto">
        <table class="js-datatable display w-full" data-numbered>
            <thead>
                <tr>
                    <th class="no-sort">No</th>
                    <th>Nama</th>
                    <th>Keterangan</th>
                    <th>Jenis isian</th>
                    <th>Jumlah arsip</th>
                    @if ($admin)<th class="no-sort">Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach ($kategoris as $k)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><span class="badge">{{ $k->nama }}</span></td>
                        <td>{{ $k->keterangan ?: '—' }}</td>
                        <td><span class="badge badge-gray">{{ $k->jenisLabel() }}</span></td>
                        <td>{{ number_format($k->arsips_count, 0, ',', '.') }}</td>
                        @if ($admin)
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <a class="btn-icon" href="{{ route('kategori.edit', $k) }}" title="Edit" aria-label="Edit kategori"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                    <form method="POST" action="{{ route('kategori.destroy', $k) }}" class="js-confirm-delete"
                                          data-message="Kategori {{ $k->nama }} akan dihapus permanen.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Hapus" aria-label="Hapus kategori"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
