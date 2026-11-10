<div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4" data-saksi-row>
    <div class="mb-3 flex items-center justify-between">
        <strong class="text-sm text-slate-800" data-saksi-title>Saksi</strong>
        <button type="button" class="btn-icon btn-icon-danger" data-saksi-remove aria-label="Hapus saksi ini" title="Hapus saksi ini">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="saksi_{{ $i }}_nama" class="label">Nama <span class="text-red-600">*</span></label>
            <input id="saksi_{{ $i }}_nama" name="saksi[{{ $i }}][nama]" type="text" maxlength="150"
                   value="{{ $s['nama'] ?? '' }}" class="input @error('saksi.'.$i.'.nama') border-red-400 @enderror">
            @error('saksi.'.$i.'.nama')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="saksi_{{ $i }}_telp" class="label">No. Telp/HP</label>
            <input id="saksi_{{ $i }}_telp" name="saksi[{{ $i }}][telp]" type="text" maxlength="30"
                   value="{{ $s['telp'] ?? '' }}" class="input @error('saksi.'.$i.'.telp') border-red-400 @enderror">
            @error('saksi.'.$i.'.telp')<p class="err">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="saksi_{{ $i }}_alamat" class="label">Alamat</label>
            <input id="saksi_{{ $i }}_alamat" name="saksi[{{ $i }}][alamat]" type="text" maxlength="255"
                   value="{{ $s['alamat'] ?? '' }}" class="input @error('saksi.'.$i.'.alamat') border-red-400 @enderror">
            @error('saksi.'.$i.'.alamat')<p class="err">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
