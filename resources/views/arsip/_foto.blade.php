@php
    $batas = config('arsip.foto.maks_jumlah');
    $fotoAda = isset($arsip) && $arsip->exists ? $arsip->fotos : collect();
@endphp
<section class="card p-6" x-data="fotoBukti({{ $batas }}, {{ $fotoAda->count() }})">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
        <span class="text-xs text-slate-500">Maks. {{ $batas }} foto &middot; JPG/PNG</span>
    </div>

    @if ($fotoAda->isNotEmpty())
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($fotoAda as $f)
                <label class="relative block overflow-hidden rounded-xl border border-slate-200" :class="hapus.includes({{ $f->id }}) ? 'opacity-40' : ''">
                    <img src="{{ route('arsip.foto', [$arsip, $f]) }}" alt="{{ $f->nama_asli }}" class="h-28 w-full object-cover" loading="lazy">
                    <span class="flex items-center gap-2 bg-white/80 px-2 py-1 text-xs text-slate-700">
                        <input type="checkbox" name="hapus_foto[]" value="{{ $f->id }}" x-model.number="hapus" @change="sisa()">
                        Hapus
                    </span>
                </label>
            @endforeach
        </div>
    @endif

    <p class="mt-2 text-xs text-slate-500">Foto otomatis jadi halaman &ldquo;Lampiran Bukti&rdquo; di bawah formulir pada hasil cetak PDF dan Word.</p>
    <div class="mt-4">
        <input type="file" name="foto[]" id="foto-input" accept="image/jpeg,image/png" multiple class="input" @change="pilih($event)">
        @error('foto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('foto.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-red-600" x-show="pesan" x-text="pesan" x-cloak></p>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" x-show="pratinjau.length" x-cloak>
        <p class="col-span-full text-xs font-medium text-slate-600">Pratinjau foto baru</p>
        <template x-for="(p, i) in pratinjau" :key="i">
            <img :src="p" class="h-28 w-full rounded-xl border border-slate-200 object-cover" alt="Pratinjau foto">
        </template>
    </div>
</section>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('fotoBukti', (maks, ada) => ({
                maks, ada, hapus: [], pratinjau: [], pesan: '',
                sisa() { return this.ada - this.hapus.length; },
                pilih(e) {
                    const files = Array.from(e.target.files || []);
                    this.pesan = '';
                    if (this.sisa() + files.length > this.maks) {
                        this.pesan = 'Terlalu banyak foto: maksimal ' + this.maks + ' per arsip.';
                    }
                    this.pratinjau.forEach(u => URL.revokeObjectURL(u));
                    this.pratinjau = files.map(f => URL.createObjectURL(f));
                },
            }));
        });
    </script>
@endonce
