@if ($jenisArsip === 'tanda_terima')
    @include('arsip._baris_tanda_terima')
@else
{{-- Tabel baris dinamis (Dokumen / Alat bukti). Definisi kolom ada di config/arsip.php -> 'baris'. --}}
@php($baris = \App\Models\Arsip::barisFor($jenisArsip))
@php($daftar = old('dokumen', $arsip->dokumen ?? []))
@php($butir = $baris['butir'] ?? 'Dokumen')

<section class="card p-6" id="barisWrap"
         data-maks="{{ $baris['max'] ?? 30 }}" data-butir="{{ $butir }}" data-awalan="{{ $baris['nomor_awalan'] ?? '' }}">
    <div class="flex items-baseline justify-between gap-3">
        <h2 class="text-base font-semibold text-slate-900">{{ $baris['label'] }}</h2>
        <span class="text-xs text-slate-500">Maksimal {{ $baris['max'] ?? 30 }}</span>
    </div>

    <div class="mt-4 space-y-4">
        <div id="barisList" class="space-y-4">
            @foreach ($daftar as $i => $r)
                @include('arsip._baris_row', ['i' => $i, 'r' => $r, 'baris' => $baris, 'butir' => $butir])
            @endforeach
        </div>

        <p id="barisEmpty" class="{{ count($daftar) ? 'hidden' : '' }} text-sm text-slate-500">{{ $baris['kosong'] ?? 'Belum ada data.' }}</p>

        @error('dokumen')<p class="err">{{ $message }}</p>@enderror

        <button type="button" class="btn btn-light btn-sm" id="barisAdd">
            <i data-lucide="plus" class="h-4 w-4"></i> {{ $baris['tambah'] ?? 'Tambah baris' }}
        </button>
    </div>
</section>

<template id="barisTemplate">
    @include('arsip._baris_row', ['i' => '__INDEX__', 'r' => null, 'baris' => $baris, 'butir' => $butir])
</template>

@endif
