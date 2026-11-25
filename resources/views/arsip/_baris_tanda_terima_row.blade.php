@php
    $nilaiJenis = $jenis ?? ($r['jenis'] ?? '');
    $nilaiCetak = $r['cetak'] ?? 0;
    $nilaiDigital = $r['digital'] ?? 0;
    $nilaiKeterangan = $r['keterangan'] ?? '';
@endphp
<div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4" data-pspp03-row data-preset-key="{{ $presetKey }}" data-custom="{{ $custom ? '1' : '0' }}">
    <div class="mb-3 flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            @if ($custom)
                <label class="label" for="dokumen_{{ $i }}_jenis">Nama dokumen tambahan <span class="text-red-600">*</span></label>
                <input id="dokumen_{{ $i }}_jenis" name="dokumen[{{ $i }}][jenis]" type="text" required maxlength="500"
                       value="{{ $nilaiJenis }}" placeholder="Tuliskan nama dokumen tambahan"
                       data-pspp03-kind-input class="input">
                @error('dokumen.'.$i.'.jenis')<p class="err mt-1">{{ $message }}</p>@enderror
            @else
                <strong class="block text-sm leading-5 text-slate-800" data-pspp03-row-title>{{ $nilaiJenis }}</strong>
                <input type="hidden" name="dokumen[{{ $i }}][jenis]" value="{{ $nilaiJenis }}" data-pspp03-kind-input>
            @endif
        </div>
        <button type="button" class="btn-icon btn-icon-danger shrink-0" data-pspp03-remove aria-label="Hapus dokumen ini" title="Hapus dokumen ini">
            <i class="ti ti-x h-4 w-4"></i>
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([['key' => 'cetak', 'label' => 'Jumlah Dokumen Cetak', 'value' => $nilaiCetak], ['key' => 'digital', 'label' => 'Jumlah Dokumen Digital', 'value' => $nilaiDigital]] as $qty)
            <div>
                <label class="label">{{ $qty['label'] }}</label>
                <div class="inline-flex h-11 items-stretch overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" data-pspp03-stepper>
                    <input type="text" name="dokumen[{{ $i }}][{{ $qty['key'] }}]" value="{{ is_numeric($qty['value']) ? max(0, (int) $qty['value']) : 0 }}"
                           inputmode="numeric" readonly aria-live="polite" aria-label="{{ $qty['label'] }}"
                           class="w-16 border-0 bg-transparent p-0 text-center text-sm font-semibold text-slate-800 focus:ring-0">
                    <div class="flex w-9 flex-col border-l border-slate-200">
                        <button type="button" class="grid flex-1 place-items-center text-slate-600 transition hover:bg-orange-50 hover:text-orange-700 disabled:opacity-35" data-pspp03-step="1" aria-label="Tambah {{ strtolower($qty['label']) }}">
                            <i class="ti ti-plus h-3.5 w-3.5"></i>
                        </button>
                        <span class="border-t border-slate-200"></span>
                        <button type="button" class="grid flex-1 place-items-center text-slate-600 transition hover:bg-slate-100 disabled:opacity-35" data-pspp03-step="-1" aria-label="Kurangi {{ strtolower($qty['label']) }}">
                            <i class="ti ti-minus h-3.5 w-3.5"></i>
                        </button>
                    </div>
                </div>
                @error('dokumen.'.$i.'.'.$qty['key'])<p class="err">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        <label class="label" for="dokumen_{{ $i }}_keterangan">Keterangan <span class="text-slate-400">(opsional)</span></label>
        <textarea id="dokumen_{{ $i }}_keterangan" name="dokumen[{{ $i }}][keterangan]" rows="2" maxlength="2000"
                  class="input">{{ $nilaiKeterangan }}</textarea>
        @error('dokumen.'.$i.'.keterangan')<p class="err">{{ $message }}</p>@enderror
    </div>
</div>
