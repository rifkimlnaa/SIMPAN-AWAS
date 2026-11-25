@extends('layouts.app')

@php($edit = $arsip->exists)
@php($perGrup = collect(\App\Models\Arsip::fieldsFor($jenisArsip))->groupBy('group', true))
@php($defJenis = \App\Models\Arsip::definisi($jenisArsip))
@php($sederhana = $jenisArsip === \App\Models\Arsip::JENIS_SEDERHANA)

@section('title', $edit ? 'Edit Arsip' : 'Tambah Arsip')

@section('content')
<p class="mb-5 text-sm text-slate-500">
    @if ($sederhana)
        Isi judul, keterangan, dan tanggal, lalu lampirkan berkas yang ingin diarsipkan.
    @else
        Isi data sesuai <strong>{{ $defJenis['label'] }}</strong> ({{ $defJenis['kode'] }}).
        Lampiran scan bersifat opsional; formulir PDF tetap dapat dibuat otomatis dari data yang diisi.
    @endif
</p>

<form method="POST" action="{{ $edit ? route('arsip.update', $arsip) : route('arsip.store') }}" enctype="multipart/form-data" data-once>
    @csrf
    @if ($edit)
        @method('PUT')
    @endif
    @unless ($sederhana)
        <input type="hidden" name="jenis_arsip" value="{{ $jenisArsip }}">
    @endunless

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-3">
            @foreach (\App\Models\Arsip::groupsFor($jenisArsip) as $kunci => $judul)
                @if ($kunci === 'saksi')
                    @include('arsip._saksi')
                    @continue
                @endif

                @if ($kunci === 'foto')
                    @continue
                @endif

                @if ($kunci === 'dokumen')
                    @include('arsip._baris')
                    @continue
                @endif

                @continue(! isset($perGrup[$kunci]) && $kunci !== 'umum')

                <section class="card p-6">
                    <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2">
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

                            @unless ($sederhana)
                                {{-- Dropdown jenis arsip. Saat menambah, mengganti pilihan memuat ulang form sesuai jenisnya. --}}
                                <div>
                                    <label for="jenis_arsip_pilih" class="label">Jenis arsip <span class="text-red-600">*</span></label>
                                    @if ($edit)
                                        <input id="jenis_arsip_pilih" type="text" value="{{ $defJenis['label'] }}" disabled class="input bg-slate-50 text-slate-500">
                                        <p class="hint">Jenis arsip tidak dapat diubah setelah arsip dibuat.</p>
                                    @else
                                        <select id="jenis_arsip_pilih" class="input" onchange="if (this.value) window.location.href = this.value">
                                            @foreach ($kategori->jenisArsipList() as $kj => $j)
                                                <option value="{{ route('arsip.create', ['kategori' => $kategori->slug, 'jenis' => $kj]) }}" @selected($kj === $jenisArsip)>
                                                    {{ $j['label'] }}{{ ! empty($j['singkat']) ? ' ('.$j['singkat'].')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endunless
                        @endif

                        @foreach ($perGrup[$kunci] ?? [] as $nama => $f)
                            @include('arsip._field', ['nama' => $nama, 'f' => $f, 'arsip' => $arsip])
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        @php($fotoJenis = \App\Models\Arsip::punyaFotoBukti($jenisArsip))
        @if ($fotoJenis)
            <div class="self-start xl:sticky xl:top-24 xl:col-span-2">
                @include('arsip._foto', ['judul' => \App\Models\Arsip::groupsFor($jenisArsip)['foto'] ?? 'Lampiran bukti foto'])
            </div>
        @else
        @php($berkasWajib = $sederhana && ! $edit)
        <section class="card self-start p-6 xl:sticky xl:top-24 xl:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Berkas arsip</h2>

            <div class="mt-4">
                <label for="file" class="label">
                    @if ($edit)
                        Ganti berkas (opsional)
                    @elseif ($berkasWajib)
                        Pilih berkas
                    @else
                        Pilih lampiran (opsional)
                    @endif
                    @if ($berkasWajib)<span class="text-red-600">*</span>@endif
                </label>
                <input id="file" name="file" type="file"
                       accept=".{{ implode(',.', config('arsip.allowed_mimes')) }}"
                       data-max-kb="{{ config('arsip.max_upload_kb') }}"
                       @if ($berkasWajib) required @endif
                       class="file-input @error('file') border-red-400 @enderror">
                <p class="hint">
                    Format {{ strtoupper(implode(', ', config('arsip.allowed_mimes'))) }}, maksimal {{ number_format(config('arsip.max_upload_kb') / 1024, 0) }} MB.
                    @if (! $sederhana)
                        Lampiran tidak wajib. Data tetap bisa disimpan dan formulir PDF tetap bisa dicetak tanpa mengunggah file.
                    @endif
                </p>
                @error('file')<p class="err">{{ $message }}</p>@enderror
            </div>

            <div class="preview-box mt-4 {{ $edit && $arsip->punyaBerkas() ? 'preview-solid' : '' }}" id="filePreview">
                @if ($edit && $arsip->punyaBerkas() && $arsip->isPdf())
                    <iframe src="{{ route('arsip.berkas', $arsip) }}" title="Pratinjau PDF"></iframe>
                @elseif ($edit && $arsip->punyaBerkas() && $arsip->isImage())
                    <img src="{{ route('arsip.berkas', $arsip) }}" alt="Pratinjau berkas arsip">
                @else
                    <p class="px-6">Pratinjau berkas akan tampil di sini setelah Anda memilih file.</p>
                @endif
            </div>
            <p class="mt-2 break-all text-xs text-slate-500" id="fileInfo">@if ($edit && $arsip->punyaBerkas()){{ $arsip->file_name }} &middot; {{ $arsip->humanSize() }}@endif</p>
        </section>
        @endif
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="btn btn-primary"><i class="ti ti-check h-4 w-4"></i> Simpan arsip</button>
        <a class="btn btn-light" href="{{ $edit ? route('arsip.show', $arsip) : route('arsip.index') }}">Batal</a>
    </div>
</form>
@endsection
