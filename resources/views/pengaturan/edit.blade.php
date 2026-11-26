@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    @if (auth()->user()->isAdmin())
        <section class="card p-6">
            <h2 class="text-base font-semibold text-slate-900">Media latar halaman login</h2>
            <p class="mt-1 text-sm text-slate-500">
                Unggah gambar atau video untuk latar penuh halaman login. Format JFIF/JPG, PNG, WEBP, MP4, dan WEBM didukung.
            </p>

            <form data-turbo="false" method="POST" action="{{ route('pengaturan.latar') }}" enctype="multipart/form-data" data-once class="mt-5">
                @csrf
                <label for="file" class="label">Pilih gambar atau video</label>
                <input id="file" name="latar" type="file"
                       accept=".jfif,.jpg,.jpeg,.png,.webp,.mp4,.webm,image/jpeg,image/png,image/webp,video/mp4,video/webm"
                       data-max-image-mb="10" data-max-video-mb="100" required
                       class="file-input @error('latar') border-red-400 @enderror">
                <p class="hint">Gambar: JFIF, JPG, PNG, atau WEBP maksimal 10 MB. Video: MP4 atau WEBM maksimal 100 MB. Video akan diputar otomatis tanpa suara dan berulang di halaman login.</p>
                @error('latar')<p class="err">{{ $message }}</p>@enderror

                <div class="preview-box mt-4 {{ $latarUrl ? 'preview-solid' : '' }}" id="filePreview">
                    @if ($latarUrl)
                        @if ($latarIsVideo)
                            <video src="{{ $latarUrl }}" controls muted playsinline preload="metadata"
                                   class="h-full w-full object-contain" aria-label="Video latar login saat ini"></video>
                        @else
                            <img src="{{ $latarUrl }}" alt="Gambar latar login saat ini">
                        @endif
                    @else
                        <p class="px-6">Belum ada media latar. Pratinjau akan tampil di sini setelah Anda memilih file.</p>
                    @endif
                </div>
                <p class="mt-2 break-all text-xs text-slate-500" id="fileInfo">
                    @if ($latarUrl)
                        {{ $latarIsVideo ? 'Video yang sedang dipakai.' : 'Gambar yang sedang dipakai.' }}
                    @endif
                </p>

                <div class="mt-4 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-upload h-4 w-4"></i> Simpan media latar</button>
                </div>
            </form>

            @if ($latarUrl)
                <form data-turbo="false" method="POST" action="{{ route('pengaturan.latar.destroy') }}" class="js-confirm-delete mt-3 border-t border-slate-100 pt-4"
                      data-message="Media latar akan dihapus dan halaman login kembali menggunakan latar bawaan.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger-ghost btn-sm"><i class="ti ti-trash h-4 w-4"></i> Hapus media latar</button>
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
            status.textContent = mengikuti ? 'Saat ini mengikuti pengaturan perangkat.' : 'Bawaan: tampilan terang.';
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
