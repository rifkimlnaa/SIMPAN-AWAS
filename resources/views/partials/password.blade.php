{{-- Input password dengan tombol tampil/sembunyi. Variabel: $id, $name, $auto, $required (opsional) --}}
<div class="relative" x-data="{ show: false }">
    <input id="{{ $id }}" name="{{ $name }}" :type="show ? 'text' : 'password'"
           autocomplete="{{ $auto ?? 'new-password' }}"
           @if ($required ?? false) required @endif
           class="input pr-10 @error($name) border-red-400 @enderror">
    <button type="button" @click="show = ! show"
            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 hover:text-slate-600"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
        <i x-show="! show" class="ti ti-eye"></i>
        <i x-show="show" x-cloak class="ti ti-eye-off"></i>
    </button>
</div>
