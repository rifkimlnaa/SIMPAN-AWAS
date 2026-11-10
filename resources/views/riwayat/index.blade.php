@extends('layouts.app')
@section('title', 'Riwayat Download')

@section('content')
@php($admin = auth()->user()->isAdmin())

<p class="mb-5 text-sm text-slate-500">
    {{ $admin ? 'Tercatat otomatis setiap kali arsip diunduh oleh admin atau petugas.' : 'Tercatat otomatis setiap kali Anda mengunduh arsip.' }}
</p>

<div class="card p-4">
    <div class="overflow-x-auto">
        <table class="js-datatable display w-full" data-numbered>
            <thead>
                <tr>
                    <th class="no-sort">No</th>
                    <th>Waktu</th>
                    <th>Arsip</th>
                    @if ($admin)
                        <th>Diunduh oleh</th>
                        <th>Alamat IP</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($riwayat as $log)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="whitespace-nowrap" data-order="{{ $log->created_at?->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td>
                            @if ($log->arsip && auth()->user()->can('view', $log->arsip))
                                <a class="font-medium text-brand-700 hover:underline" href="{{ route('arsip.show', $log->arsip_id) }}">{{ $log->arsip_label }}</a>
                            @else
                                {{ $log->arsip_label }}
                                @unless ($log->arsip)<span class="badge badge-gray ml-1">Arsip dihapus</span>@endunless
                            @endif
                        </td>
                        @if ($admin)
                            <td>{{ $log->user?->name ?? 'Pengguna dihapus' }}</td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
