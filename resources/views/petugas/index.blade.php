@extends('layouts.app')
@section('title', 'Data Petugas')

@section('content')
@php($admin = auth()->user()->isAdmin())

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500">Akun petugas yang dapat mengunggah dan mengelola arsip.</p>
    @if ($admin)
        <a class="btn btn-primary" href="{{ route('petugas.create') }}"><i data-lucide="plus" class="h-4 w-4"></i> Tambah petugas</a>
    @endif
</div>

<div class="card p-4">
    <div class="overflow-x-auto">
        <table class="js-datatable display w-full" data-numbered>
            <thead>
                <tr>
                    <th class="no-sort">No</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Jumlah arsip</th>
                    <th>Terdaftar</th>
                    @if ($admin)<th class="no-sort">Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach ($petugas as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span class="flex items-center gap-2.5">
                                @include('partials.avatar', ['user' => $p, 'size' => 'sm'])
                                <span class="font-medium text-slate-900">{{ $p->name }}</span>
                            </span>
                        </td>
                        <td>{{ $p->username }}</td>
                        <td>{{ number_format($p->arsips_count, 0, ',', '.') }}</td>
                        <td data-order="{{ $p->created_at?->format('Y-m-d') }}">{{ $p->created_at?->translatedFormat('d M Y') }}</td>
                        @if ($admin)
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <a class="btn-icon" href="{{ route('petugas.edit', $p) }}" title="Edit" aria-label="Edit petugas"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                    <form method="POST" action="{{ route('petugas.destroy', $p) }}" class="js-confirm-delete"
                                          data-message="Akun petugas {{ $p->name }} akan dihapus permanen.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Hapus" aria-label="Hapus petugas"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
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
