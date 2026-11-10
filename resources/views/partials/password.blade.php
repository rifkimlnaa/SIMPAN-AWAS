{{-- Input password dengan tombol tampil/sembunyi. Variabel: $id, $name, $auto, $required (opsional) --}}
<div class="relative" x-data="{ show: false }">
    <input id="{{ $id }}" name="{{ $name }}" :type="show ? 'text' : 'password'"
           autocomplete="{{ $auto ?? 'new-password' }}"
           @if ($required ?? false) required @endif
           class="input pr-10 @error($name) border-red-400 @enderror">
    <button type="button" @click="show = ! show"
            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 hover:text-slate-600"
            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
        <svg x-show="! show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
    </button>
</div>
