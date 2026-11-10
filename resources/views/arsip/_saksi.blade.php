@php($daftarSaksi = old('saksi', $arsip->saksi ?? []))

<section class="card p-6">
    <div class="flex items-baseline justify-between gap-3">
        <h2 class="text-base font-semibold text-slate-900">Saksi-saksi</h2>
        <span class="text-xs text-slate-500">Tidak wajib diisi, maksimal 10</span>
    </div>

    <div class="mt-4 space-y-4">
        <div id="saksiList" class="space-y-4">
            @foreach ($daftarSaksi as $i => $s)
                @include('arsip._saksi_row', ['i' => $i, 's' => $s])
            @endforeach
        </div>

        <p id="saksiEmpty" class="{{ count($daftarSaksi) ? 'hidden' : '' }} text-sm text-slate-500">Belum ada saksi. Klik tombol di bawah untuk menambahkan.</p>

        @error('saksi')<p class="err">{{ $message }}</p>@enderror

        <button type="button" class="btn btn-light btn-sm" id="saksiAdd">
            <i data-lucide="plus" class="h-4 w-4"></i> Tambah saksi
        </button>
    </div>
</section>

<template id="saksiTemplate">
    @include('arsip._saksi_row', ['i' => '__INDEX__', 's' => []])
</template>
