@php
    $tipe = $f['type'] ?? 'text';
    $wajib = in_array('required', $f['rules'], true);

    // Form baru memakai nilai bawaan (mis. Kewarganegaraan: Indonesia); form edit memakai data tersimpan.
    $awal = $arsip->inputValue($nama);
    if ($awal === null && ! $arsip->exists) {
        $awal = $f['default'] ?? null;
    }
    $nilai = old($nama, $awal);
    $lebar = $tipe === 'textarea' || ($f['wide'] ?? false);
@endphp

<div class="{{ $lebar ? 'sm:col-span-2' : '' }}">
    <label for="{{ $nama }}" class="label">{{ $f['label'] }} @if ($wajib)<span class="text-red-600">*</span>@endif</label>

    @if ($tipe === 'textarea')
        <textarea id="{{ $nama }}" name="{{ $nama }}" rows="{{ $f['rows'] ?? 3 }}"
                  @if ($wajib) required @endif
                  class="input @error($nama) border-red-400 @enderror">{{ $nilai }}</textarea>
    @elseif ($tipe === 'time')
        {{-- Jam 24 jam dengan satuan WIB (type="time" bawaan browser bisa tampil AM/PM). --}}
        <div class="relative">
            <input id="{{ $nama }}" name="{{ $nama }}" type="text" value="{{ $nilai }}"
                   inputmode="numeric" maxlength="5" placeholder="09:30" autocomplete="off"
                   pattern="([01][0-9]|2[0-3]):[0-5][0-9]" title="Format 24 jam, contoh 09:30"
                   data-time24
                   @if ($wajib) required @endif
                   class="input pr-14 @error($nama) border-red-400 @enderror">
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400" aria-hidden="true">WIB</span>
        </div>
    @elseif ($tipe === 'select')
        <select id="{{ $nama }}" name="{{ $nama }}" @if ($wajib) required @endif class="input @error($nama) border-red-400 @enderror">
            <option value="" disabled @selected(blank($nilai))>Pilih {{ strtolower($f['label']) }}</option>
            @foreach ($f['options'] as $opsi)
                <option value="{{ $opsi }}" @selected($nilai === $opsi)>{{ $opsi }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $nama }}" name="{{ $nama }}" type="{{ $tipe }}" value="{{ $nilai }}"
               @if ($wajib) required @endif
               class="input @error($nama) border-red-400 @enderror">
    @endif

    @if (! empty($f['hint']))
        <p class="hint">{{ $f['hint'] }}</p>
    @endif
    @error($nama)<p class="err">{{ $message }}</p>@enderror
</div>
