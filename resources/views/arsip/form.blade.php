@extends('layouts.app')

@php($edit = $arsip->exists)
@php($perGrup = collect(\App\Models\Arsip::fieldsFor($jenis))->groupBy('group', true))

@section('title', $edit ? 'Edit Arsip' : 'Tambah Arsip')

@section('content')
<p class="mb-5 text-sm text-slate-500">
    @if ($jenis === \App\Models\Kategori::JENIS_SEDERHANA)
        Isi judul, keterangan, dan tanggal, lalu lampirkan berkas yang ingin diarsipkan.
    @else
        Isi data sesuai Formulir Laporan, lalu unggah berkas scan (PDF atau gambar).
    @endif
</p>

<form method="POST" action="{{ $edit ? route('arsip.update', $arsip) : route('arsip.store') }}" enctype="multipart/form-data" data-once>
    @csrf
    @if ($edit)
        @method('PUT')
    @endif

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-3">
            @foreach (\App\Models\Arsip::groupsFor($jenis) as $kunci => $judul)
                @continue(! isset($perGrup[$kunci]))

                <section class="card p-6">
                    <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        @if ($kunci === 'umum')
                            <div>
                                <label for="kategori_id" class="label">Kategori <span class="text-red-600">*</span></label>
                                <select id="kategori_id" name="kategori_id" required class="input @error('kategori_id') border-red-400 @enderror">
                                    <option value="" disabled @selected(! old('kategori_id', $kategoriDipilih))>Pilih kategori</option>
                                    @foreach ($kategoris as $k)
                                        <option value="{{ $k->id }}" @selected((int) old('kategori_id', $kategoriDipilih) === $k->id)>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                                @error('kategori_id')<p class="err">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        @foreach ($perGrup[$kunci] as $nama => $f)
                            @include('arsip._field', ['nama' => $nama, 'f' => $f, 'arsip' => $arsip])
                        @endforeach
                    </div>
                </section>

                @if ($kunci === 'peristiwa')
                    @include('arsip._saksi')
                @endif
            @endforeach
        </div>

        <section class="card self-start p-6 xl:sticky xl:top-24 xl:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Berkas arsip</h2>

            <div class="mt-4">
                <label for="file" class="label">
                    {{ $edit ? 'Ganti berkas (opsional)' : 'Pilih berkas' }}
                    @unless ($edit)<span class="text-red-600">*</span>@endunless
                </label>
                <input id="file" name="file" type="file"
                       accept=".{{ implode(',.', config('arsip.allowed_mimes')) }}"
                       data-max-kb="{{ config('arsip.max_upload_kb') }}"
                       @unless ($edit) required @endunless
                       class="file-input @error('file') border-red-400 @enderror">
                <p class="hint">
                    Format {{ strtoupper(implode(', ', config('arsip.allowed_mimes'))) }}, maksimal {{ number_format(config('arsip.max_upload_kb') / 1024, 0) }} MB.
                    {{ $jenis === \App\Models\Kategori::JENIS_SEDERHANA ? '' : 'Gabungkan formulir beserta lampirannya dalam satu file PDF.' }}
                </p>
                @error('file')<p class="err">{{ $message }}</p>@enderror
            </div>

            <div class="preview-box mt-4 {{ $edit ? 'preview-solid' : '' }}" id="filePreview">
                @if ($edit && $arsip->isPdf())
                    <iframe src="{{ route('arsip.berkas', $arsip) }}" title="Pratinjau PDF"></iframe>
                @elseif ($edit && $arsip->isImage())
                    <img src="{{ route('arsip.berkas', $arsip) }}" alt="Pratinjau berkas arsip">
                @else
                    <p class="px-6">Pratinjau berkas akan tampil di sini setelah Anda memilih file.</p>
                @endif
            </div>
            <p class="mt-2 break-all text-xs text-slate-500" id="fileInfo">@if ($edit){{ $arsip->file_name }} &middot; {{ $arsip->humanSize() }}@endif</p>
        </section>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn btn-primary"><i data-lucide="check" class="h-4 w-4"></i> Simpan arsip</button>
        <a class="btn btn-light" href="{{ $edit ? route('arsip.show', $arsip) : route('arsip.index') }}">Batal</a>
    </div>
</form>
@endsection
