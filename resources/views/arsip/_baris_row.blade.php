<div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4" data-baris-row>
    <div class="mb-3 flex items-center justify-between">
        <strong class="text-sm text-slate-800" data-baris-title>{{ $butir }}</strong>
        <button type="button" class="btn-icon btn-icon-danger" data-baris-remove aria-label="Hapus baris ini" title="Hapus baris ini">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($baris['kolom'] as $kunci => $k)
            @php($wajib = in_array('required', $k['rules'], true))
            {{-- Baris baru (r = null) memakai nilai bawaan kolom, mis. "Lengkap". --}}
            @php($nilai = $r === null ? ($k['default'] ?? '') : ($r[$kunci] ?? ''))
            @php($lebar = ($k['type'] ?? 'text') === 'textarea' || ($k['wide'] ?? false))
            <div class="{{ $lebar ? 'sm:col-span-2' : '' }}">
                <label for="dokumen_{{ $i }}_{{ $kunci }}" class="label">{{ $k['label'] }} @if ($wajib)<span class="text-red-600">*</span>@endif</label>
                @if (($k['type'] ?? 'text') === 'textarea')
                    <textarea id="dokumen_{{ $i }}_{{ $kunci }}" name="dokumen[{{ $i }}][{{ $kunci }}]" rows="{{ $k['rows'] ?? 3 }}"
                              class="input @error('dokumen.'.$i.'.'.$kunci) border-red-400 @enderror">{{ $nilai }}</textarea>
                @else
                    <input id="dokumen_{{ $i }}_{{ $kunci }}" name="dokumen[{{ $i }}][{{ $kunci }}]" type="text"
                           value="{{ $nilai }}" class="input @error('dokumen.'.$i.'.'.$kunci) border-red-400 @enderror">
                @endif
                @if (! empty($k['hint']))<p class="hint">{{ $k['hint'] }}</p>@endif
                @error('dokumen.'.$i.'.'.$kunci)<p class="err">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
