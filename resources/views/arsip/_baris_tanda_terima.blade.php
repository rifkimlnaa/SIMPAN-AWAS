@php
    $baris = \App\Models\Arsip::barisFor($jenisArsip);
    $pilihan = $baris['pilihan'] ?? [];
    $daftar = old('dokumen', $arsip->dokumen ?? []);
    $daftar = is_array($daftar) ? $daftar : [];
    $tersimpan = [];
    $tambahan = [];

    foreach ($daftar as $item) {
        if (! is_array($item)) continue;
        $nama = trim((string) ($item['jenis'] ?? ''));
        $kunci = array_search($nama, $pilihan, true);
        if ($kunci !== false && ! isset($tersimpan[$kunci])) {
            $tersimpan[$kunci] = $item;
        } else {
            $tambahan[] = $item;
        }
    }

    $renderRows = [];
    $renderIndex = 0;
    foreach ($pilihan as $kunci => $nama) {
        if (isset($tersimpan[$kunci])) {
            $renderRows[] = [
                'index' => $renderIndex++, 'item' => $tersimpan[$kunci],
                'presetKey' => $kunci, 'jenis' => $nama, 'custom' => false,
            ];
        }
    }
    foreach ($tambahan as $item) {
        $renderRows[] = [
            'index' => $renderIndex++, 'item' => $item,
            'presetKey' => '', 'jenis' => (string) ($item['jenis'] ?? ''), 'custom' => true,
        ];
    }
@endphp

<section class="card p-6" id="pspp03DocumentPicker" data-maks="{{ $baris['max'] ?? 30 }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Rincian dokumen</h2>
            <p class="hint mt-1">Pilih dokumen yang dibawa oleh Pemohon. Jumlah cetak dan digital diatur menggunakan tombol tambah/kurang.</p>
        </div>
        <span class="text-xs text-slate-500">Maksimal {{ $baris['max'] ?? 30 }} dokumen</span>
    </div>

    <div class="mt-5">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">Pemohon menyiapkan:</h3>
        <div class="grid gap-2 sm:grid-cols-2" data-pspp03-options>
            @foreach ($pilihan as $key => $label)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white/70 p-3 transition hover:border-orange-300 hover:bg-orange-50/60">
                    <input type="checkbox" value="{{ $key }}" data-pspp03-choice
                           @checked(isset($tersimpan[$key]))
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <span class="text-sm leading-5 text-slate-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-6 border-t border-slate-200 pt-5">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-slate-800">Jumlah dan keterangan dokumen terpilih</h3>
            <span class="text-xs text-slate-500" data-pspp03-count>{{ count($renderRows) }} dipilih</span>
        </div>

        <div class="space-y-3" data-pspp03-rows>
            @foreach ($renderRows as $row)
                @include('arsip._baris_tanda_terima_row', [
                    'i' => $row['index'], 'r' => $row['item'], 'jenis' => $row['jenis'],
                    'presetKey' => $row['presetKey'], 'custom' => $row['custom'],
                ])
            @endforeach
        </div>

        <p class="{{ count($renderRows) ? 'hidden' : '' }} mt-3 rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500" data-pspp03-empty>
            Belum ada dokumen dipilih. Centang dokumen di atas untuk menambahkannya.
        </p>

        @error('dokumen')<p class="err mt-2">{{ $message }}</p>@enderror

        <button type="button" class="btn btn-light btn-sm mt-4" data-pspp03-add-custom>
            <i class="ti ti-plus h-4 w-4"></i> Tambah dokumen lainnya
        </button>
        <p class="hint mt-2">Gunakan pilihan ini jika Pemohon membawa dokumen tambahan di luar enam jenis dokumen di atas.</p>
    </div>
</section>

<template id="pspp03PresetRowTemplate">
    @include('arsip._baris_tanda_terima_row', ['i' => '__INDEX__', 'r' => null, 'jenis' => '', 'presetKey' => '__PRESET_KEY__', 'custom' => false])
</template>

<template id="pspp03CustomRowTemplate">
    @include('arsip._baris_tanda_terima_row', ['i' => '__INDEX__', 'r' => null, 'jenis' => '', 'presetKey' => '', 'custom' => true])
</template>
