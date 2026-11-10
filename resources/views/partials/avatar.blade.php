@php
    $ukuran = $size ?? 'md';
    $kelas = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-20 w-20 text-2xl'][$ukuran] ?? 'h-10 w-10 text-sm';
@endphp
@if ($user->fotoUrl())
    <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $user->name }}" class="{{ $kelas }} shrink-0 rounded-full object-cover ring-1 ring-slate-200">
@else
    <span class="{{ $kelas }} flex shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-700" aria-hidden="true">{{ $user->initials() }}</span>
@endif
