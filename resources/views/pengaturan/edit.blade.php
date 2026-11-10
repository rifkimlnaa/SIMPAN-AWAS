@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <p class="text-sm text-slate-500">Pengaturan tampilan aplikasi.</p>

    <section class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Tampilan</h2>
        <p class="mt-1 text-sm text-slate-500">Pilihan ini tersimpan di browser yang Anda pakai dan tidak mengubah tampilan pengguna lain.</p>

        <div class="mt-5 flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
            <div class="min-w-0">
                <label for="switchGelap" class="block text-sm font-medium text-slate-900">Mode gelap</label>
                <p class="text-xs text-slate-500">Tampilan gelap untuk mengurangi silau, cocok saat bekerja di ruang redup.</p>
            </div>
            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" id="switchGelap" role="switch" class="peer sr-only">
                <span class="h-6 w-11 rounded-full bg-slate-300 transition-colors peer-checked:bg-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:content-[''] peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
            <span id="temaStatus"></span>
            <button type="button" id="temaSistem" class="font-medium text-brand-700 hover:underline">Ikuti pengaturan perangkat</button>
        </div>
    </section>

    @if (auth()->user()->isAdmin())
        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">Foto latar halaman login</h2>
            <p class="mt-1 text-sm text-slate-500">
                Foto tampil di panel biru pada halaman login (layar laptop dan PC). Jika tidak ada foto, panel berwarna biru polos.
            </p>

            <form method="POST" action="{{ route('pengaturan.latar') }}" enctype="multipart/form-data" data-once class="mt-5">
                @csrf
                <label for="file" class="label">Pilih foto</label>
                <input id="file" name="latar" type="file" accept=".jpg,.jpeg,.png,.webp" data-max-kb="4096" required
                       class="file-input @error('latar') border-red-400 @enderror">
                <p class="hint">JPG, PNG, atau WEBP, maksimal 4 MB. Foto berorientasi potret atau persegi dengan objek di tengah paling cocok.</p>
                @error('latar')<p class="err">{{ $message }}</p>@enderror

                <div class="preview-box mt-4 {{ $latarUrl ? 'preview-solid' : '' }}" id="filePreview">
                    @if ($latarUrl)
                        <img src="{{ $latarUrl }}" alt="Foto latar login saat ini">
                    @else
                        <p class="px-6">Belum ada foto. Pratinjau akan tampil di sini setelah Anda memilih file.</p>
                    @endif
                </div>
                <p class="mt-2 break-all text-xs text-slate-500" id="fileInfo">@if ($latarUrl)Foto yang sedang dipakai.@endif</p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary"><i data-lucide="upload" class="h-4 w-4"></i> Simpan foto</button>
                </div>
            </form>

            @if ($latarUrl)
                <form method="POST" action="{{ route('pengaturan.latar.destroy') }}" class="js-confirm-delete mt-3 border-t border-slate-100 pt-4"
                      data-message="Foto latar akan dihapus dan halaman login kembali berlatar biru polos.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger-ghost btn-sm"><i data-lucide="trash-2" class="h-4 w-4"></i> Hapus foto latar</button>
                </form>
            @endif
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var sakelar = document.getElementById('switchGelap');
        var status = document.getElementById('temaStatus');
        var sistem = document.getElementById('temaSistem');
        if (!sakelar || !window.setTema) return;

        function segarkan() {
            sakelar.checked = document.documentElement.classList.contains('dark');
            sakelar.setAttribute('aria-checked', sakelar.checked ? 'true' : 'false');
            var mengikuti = window.temaDipilih() === 'sistem';
            status.textContent = mengikuti ? 'Saat ini mengikuti pengaturan perangkat.' : '';
            sistem.classList.toggle('hidden', mengikuti);
        }

        sakelar.addEventListener('change', function () {
            window.setTema(sakelar.checked ? 'gelap' : 'terang');
            segarkan();
        });
        sistem.addEventListener('click', function () {
            window.setTema('sistem');
            segarkan();
        });

        segarkan();
    })();
</script>
@endpush
