<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\DownloadLog;
use App\Models\ArsipFoto;
use App\Models\Kategori;
use App\Support\FormulirWord;
use App\Support\FotoBukti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ArsipController extends Controller
{
    private const DISK = 'local'; // privat: storage/app/private, tidak bisa diakses lewat URL langsung

    public function index(Request $request): View
    {
        $kategoriAktif = null;
        if ($request->filled('kategori')) {
            $kategoriAktif = Kategori::where('slug', $request->query('kategori'))->firstOrFail();
        }

        // Filter jenis arsip (B.1, B.2, Tanda Terima, ...) hanya berlaku di dalam kategori utama.
        $jenisAktif = null;
        if ($kategoriAktif && $request->filled('jenis')) {
            $jenisAktif = (string) $request->query('jenis');
            abort_unless(array_key_exists($jenisAktif, $kategoriAktif->jenisArsipList()), 404);
        }

        $arsips = Arsip::query()
            ->visibleTo($request->user())
            ->when($kategoriAktif, fn ($q) => $q->where('kategori_id', $kategoriAktif->id))
            ->when($jenisAktif, fn ($q) => $q->where('jenis_arsip', $jenisAktif))
            ->with(['kategori:id,nama,slug,jenis', 'user:id,name'])
            // Terbaru di atas: tanggal pada formulir, atau tanggal arsip (kategori tambahan).
            ->orderByRaw('COALESCE(tanggal_dilaporkan, tanggal_arsip) DESC')
            ->orderByDesc('id')
            ->get();

        return view('arsip.index', compact('arsips', 'kategoriAktif', 'jenisAktif'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Arsip::class);

        // Tanpa ?kategori=..., tampilkan dulu pilihan kategori (dan jenis arsipnya untuk kategori utama).
        if (! $request->filled('kategori')) {
            return view('arsip.pilih', ['kategoris' => Kategori::withCount('arsips')->urut()->get()]);
        }

        $kategori = Kategori::where('slug', $request->query('kategori'))->firstOrFail();
        $jenisArsip = $this->jenisArsipDariQuery($kategori, $request->query('jenis'));

        // Kategori utama wajib memilih jenis arsip dulu.
        if ($jenisArsip === null) {
            return redirect()->route('arsip.create')->with('error', 'Pilih jenis arsip terlebih dahulu.');
        }

        $arsipBaru = new Arsip;
        $arsipBaru->jenis_arsip = $jenisArsip;

        return view('arsip.form', [
            'arsip' => $arsipBaru,
            'jenisArsip' => $jenisArsip,
            'kategori' => $kategori,
            // Pilihan kategori dibatasi pada jenis yang sama agar isian formulir tetap cocok.
            'kategoris' => Kategori::where('jenis', $kategori->jenis)->orderBy('nama')->get(),
            'kategoriDipilih' => $kategori->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Arsip::class);

        $kategori = $this->kategoriDariRequest($request);
        $jenisArsip = $this->jenisArsipDariRequest($request, $kategori);

        $this->rapikan($request, $jenisArsip);

        $data = $request->validate($this->rules(true, $jenisArsip), [], $this->attributes($jenisArsip));
        // Lampiran boleh kosong untuk dua kategori utama. Jika ada file, simpan seperti biasa.
        $file = $request->file('file');
        $path = null;
        $jalurFoto = [];

        try {
            $arsip = new Arsip(Arr::except($data, ['file', 'foto', 'hapus_foto']));
            $arsip->user_id = $request->user()->id;
            $arsip->jenis_arsip = $jenisArsip;

            if ($file) {
                $meta = $this->metaBerkas($file);
                $path = $file->store('arsip', self::DISK);
                $arsip->forceFill($meta + ['file_path' => $path]);
            }

            DB::transaction(function () use ($arsip, $request, $jenisArsip, &$jalurFoto) {
                $arsip->save();

                if (Arsip::punyaFotoBukti($jenisArsip)) {
                    $jalurFoto = FotoBukti::simpan($arsip, (array) $request->file('foto', []));
                }
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk(self::DISK)->delete($path);
            }
            FotoBukti::hapusBerkas($jalurFoto);
            throw $e;
        }

        return redirect()->route('arsip.show', $arsip)->with('success', 'Arsip berhasil ditambahkan.');
    }

    public function show(Arsip $arsip): View
    {
        Gate::authorize('view', $arsip);

        return view('arsip.show', ['arsip' => $arsip->load(['kategori', 'user'])]);
    }

    public function edit(Arsip $arsip): View
    {
        Gate::authorize('update', $arsip);

        $arsip->loadMissing('kategori');

        return view('arsip.form', [
            'arsip' => $arsip,
            'jenisArsip' => $this->jenisArsipTersimpan($arsip),
            'kategori' => $arsip->kategori,
            'kategoris' => Kategori::where('jenis', $arsip->kategori->jenis)->orderBy('nama')->get(),
            'kategoriDipilih' => $arsip->kategori_id,
        ]);
    }

    public function update(Request $request, Arsip $arsip): RedirectResponse
    {
        Gate::authorize('update', $arsip);

        $arsip->loadMissing('kategori');
        $kategori = $this->kategoriDariRequest($request);

        // Arsip tidak boleh dipindah ke kategori dengan jenis isian yang berbeda.
        if ($kategori->jenis !== $arsip->kategori->jenis) {
            throw ValidationException::withMessages([
                'kategori_id' => 'Kategori harus memiliki jenis isian yang sama dengan arsip ini.',
            ]);
        }

        // Jenis arsip tidak bisa diubah setelah arsip dibuat (isian formulirnya berbeda).
        $jenisArsip = $this->jenisArsipTersimpan($arsip);

        $this->rapikan($request, $jenisArsip);

        $data = $request->validate($this->rules(false, $jenisArsip), [], $this->attributes($jenisArsip));

        $this->periksaJumlahFoto($request, $arsip, $jenisArsip);

        $arsip->fill(Arr::except($data, ['file', 'foto', 'hapus_foto']));

        $pathLama = null;
        $pathBaru = null;
        $jalurFotoBaru = [];
        $jalurFotoDihapus = [];

        if ($file = $request->file('file')) {
            $meta = $this->metaBerkas($file);
            $pathBaru = $file->store('arsip', self::DISK);
            $pathLama = $arsip->file_path;
            $arsip->forceFill($meta + ['file_path' => $pathBaru]);
        }

        try {
            DB::transaction(function () use ($arsip, $request, $jenisArsip, &$jalurFotoBaru, &$jalurFotoDihapus) {
                $arsip->save();

                if (Arsip::punyaFotoBukti($jenisArsip)) {
                    $hapus = array_map('intval', (array) $request->input('hapus_foto', []));
                    if ($hapus) {
                        $jalurFotoDihapus = $arsip->fotos()->whereIn('id', $hapus)->pluck('path')->all();
                        $arsip->fotos()->whereIn('id', $hapus)->delete();
                    }

                    $jalurFotoBaru = FotoBukti::simpan($arsip, (array) $request->file('foto', []));
                }
            });
        } catch (\Throwable $e) {
            if ($pathBaru) {
                Storage::disk(self::DISK)->delete($pathBaru);
            }
            FotoBukti::hapusBerkas($jalurFotoBaru);
            throw $e;
        }

        if ($pathLama) {
            Storage::disk(self::DISK)->delete($pathLama);
        }
        FotoBukti::hapusBerkas($jalurFotoDihapus);

        return redirect()->route('arsip.show', $arsip)->with('success', 'Arsip berhasil diperbarui.');
    }

    public function destroy(Arsip $arsip): RedirectResponse
    {
        Gate::authorize('delete', $arsip);

        $path = $arsip->file_path;
        $jalurFoto = FotoBukti::daftarJalur($arsip);
        $arsip->delete();
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
        FotoBukti::hapusBerkas($jalurFoto);

        return redirect()->route('arsip.index')->with('success', 'Arsip berhasil dihapus.');
    }

    /** Tampil di browser (preview PDF/gambar). Tidak dicatat sebagai download. */
    public function berkas(Arsip $arsip): Response
    {
        Gate::authorize('view', $arsip);
        abort_unless($arsip->punyaBerkas() && Storage::disk(self::DISK)->exists($arsip->file_path), 404, 'Arsip ini tidak memiliki berkas lampiran.');

        return Storage::disk(self::DISK)->response($arsip->file_path, $arsip->file_name, [
            'Content-Type' => $arsip->file_mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Unduh berkas dan catat otomatis ke riwayat download. */
    public function unduh(Request $request, Arsip $arsip): Response
    {
        Gate::authorize('download', $arsip);
        abort_unless($arsip->punyaBerkas() && Storage::disk(self::DISK)->exists($arsip->file_path), 404, 'Arsip ini tidak memiliki berkas lampiran.');

        $arsip->loadMissing('kategori');

        DownloadLog::create([
            'arsip_id' => $arsip->id,
            'user_id' => $request->user()->id,
            'arsip_label' => $arsip->label(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return Storage::disk(self::DISK)->download($arsip->file_path, $arsip->file_name);
    }

    /** Tampilkan satu foto bukti di browser (miniatur/preview). Tidak dicatat sebagai download. */
    public function foto(Arsip $arsip, ArsipFoto $foto): Response
    {
        Gate::authorize('view', $arsip);
        abort_unless($foto->arsip_id === $arsip->id && Storage::disk(self::DISK)->exists($foto->path), 404);

        return Storage::disk(self::DISK)->response($foto->path, $foto->nama_asli ?: 'foto', [
            'Content-Type' => $foto->mime ?: 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Cetak formulir (B.1, B.2, B.3, B.3.1, Tanda Terima, Daftar Alat Bukti, Berita Acara Verifikasi)
     * sebagai PDF, terisi otomatis dari data arsip dan mengikuti bentuk formulir aslinya.
     * Dicatat ke riwayat download seperti unduhan berkas.
     */
    public function formulir(Request $request, Arsip $arsip): Response
    {
        Gate::authorize('download', $arsip);

        if (! class_exists(Pdf::class)) {
            return back()->with('error', 'Fitur cetak PDF belum aktif. Jalankan "composer require barryvdh/laravel-dompdf" di folder proyek, lalu coba lagi.');
        }

        [$def, $data] = $this->dataFormulir($arsip);

        // Ukuran halaman mengikuti PDF formulir referensi: 612.48 x 936.48 pt.
        $konten = Pdf::loadView($def['pdf'], $data)->setPaper([0, 0, 612.48, 936.48])->output();

        $this->catatCetak($request, $arsip, $def, 'Formulir ');

        return response($konten, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->namaBerkasFormulir($arsip, $def, 'pdf').'"',
        ]);
    }

    /** Formulir yang sama dalam bentuk dokumen Word (.doc) yang bisa diedit. */
    public function formulirWord(Request $request, Arsip $arsip): Response
    {
        Gate::authorize('download', $arsip);

        [$def, $data] = $this->dataFormulir($arsip);

        $konten = FormulirWord::buat(view($def['pdf'], $data + ['word' => true])->render());

        $this->catatCetak($request, $arsip, $def, 'Formulir Word ');

        return response($konten, 200, [
            'Content-Type' => 'application/msword',
            'Content-Disposition' => 'attachment; filename="'.$this->namaBerkasFormulir($arsip, $def, 'doc').'"',
        ]);
    }

    /** Data yang dipakai view cetak (PDF maupun Word). @return array{0:array,1:array} */
    private function dataFormulir(Arsip $arsip): array
    {
        $arsip->loadMissing(['kategori', 'user', 'fotos']);

        // Kategori tambahan (isian sederhana) tidak punya formulir.
        abort_unless($arsip->bisaCetakFormulir(), 404);

        $def = Arsip::definisi($arsip->jenis_arsip);

        $logoPath = public_path('img/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        $bukti = collect(preg_split('/\R/u', (string) $arsip->bukti) ?: [])
            ->map(fn ($b) => trim($b))
            ->filter()
            ->values()
            ->all();

        return [$def, [
            'arsip' => $arsip,
            'def' => $def,
            'bukti' => $bukti,
            'fotos' => FotoBukti::untukCetak($arsip),
            'logo' => $logo,
            'kantor' => config('arsip.kantor'),
            'dicetak' => now()->translatedFormat('d F Y, H:i'),
        ]];
    }

    private function catatCetak(Request $request, Arsip $arsip, array $def, string $awalan): void
    {
        DownloadLog::create([
            'arsip_id' => $arsip->id,
            'user_id' => $request->user()->id,
            'arsip_label' => $awalan.$def['label'].': '.$arsip->label(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    private function namaBerkasFormulir(Arsip $arsip, array $def, string $ekstensi): string
    {
        return $def['pdf_nama'].'-'.(Str::slug((string) ($arsip->nomor_laporan ?: $arsip->nama_pemohon)) ?: $arsip->id).'.'.$ekstensi;
    }

    // ---------------------------------------------------------------------------------------------
    // Kategori & jenis arsip
    // ---------------------------------------------------------------------------------------------

    /** Kategori yang dipilih di form; menentukan kolom mana yang divalidasi. */
    private function kategoriDariRequest(Request $request): Kategori
    {
        $request->validate(
            ['kategori_id' => ['required', 'integer', 'exists:kategoris,id']],
            [],
            ['kategori_id' => 'kategori']
        );

        return Kategori::findOrFail($request->integer('kategori_id'));
    }

    /**
     * Jenis arsip dari URL form tambah. Kategori tambahan selalu "sederhana";
     * kategori utama harus memilih salah satu jenisnya (null bila belum/salah pilih).
     */
    private function jenisArsipDariQuery(Kategori $kategori, mixed $jenis): ?string
    {
        if ($kategori->isSederhana()) {
            return Arsip::JENIS_SEDERHANA;
        }

        return is_string($jenis) && array_key_exists($jenis, $kategori->jenisArsipList()) ? $jenis : null;
    }

    /** Jenis arsip dari form yang dikirim, divalidasi terhadap kategorinya. */
    private function jenisArsipDariRequest(Request $request, Kategori $kategori): string
    {
        if ($kategori->isSederhana()) {
            return Arsip::JENIS_SEDERHANA;
        }

        $request->validate(
            ['jenis_arsip' => ['required', Rule::in(array_keys($kategori->jenisArsipList()))]],
            [],
            ['jenis_arsip' => 'jenis arsip']
        );

        return (string) $request->input('jenis_arsip');
    }

    private function jenisArsipTersimpan(Arsip $arsip): string
    {
        return $arsip->jenis_arsip ?: Arsip::JENIS_SEDERHANA;
    }

    // ---------------------------------------------------------------------------------------------
    // Validasi
    // ---------------------------------------------------------------------------------------------

    private function rules(bool $membuat, string $jenisArsip): array
    {
        $rules = ['kategori_id' => ['required', 'integer', 'exists:kategoris,id']];

        foreach (Arsip::fieldsFor($jenisArsip) as $nama => $field) {
            $rules[$nama] = $field['rules'];
        }

        if (Arsip::punyaSaksi($jenisArsip)) {
            // Saksi: tidak wajib, maksimal 10 orang; jika baris diisi maka nama wajib.
            $rules['saksi'] = ['nullable', 'array', 'max:10'];
            $rules['saksi.*.nama'] = ['required', 'string', 'max:150'];
            $rules['saksi.*.alamat'] = ['nullable', 'string', 'max:255'];
            $rules['saksi.*.telp'] = ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'];
        }

        if ($baris = Arsip::barisFor($jenisArsip)) {
            $rules['dokumen'] = ['required', 'array', 'min:'.($baris['min'] ?? 1), 'max:'.($baris['max'] ?? 30)];
            foreach ($baris['kolom'] as $kunci => $kolom) {
                $rules['dokumen.*.'.$kunci] = $kolom['rules'];
            }
        }

        if (Arsip::punyaFotoBukti($jenisArsip)) {
            $rules['foto'] = ['nullable', 'array', 'max:'.config('arsip.foto.maks_jumlah')];
            $rules['foto.*'] = ['file', 'mimes:'.implode(',', config('arsip.foto.mimes')), 'max:'.config('arsip.foto.maks_kb')];
            $rules['hapus_foto'] = ['nullable', 'array'];
            $rules['hapus_foto.*'] = ['integer'];
        }

        $rules['file'] = [
            // Kategori tambahan (isian sederhana) tetap meminta berkas saat arsip baru dibuat.
            // Kedua kategori utama selalu boleh disimpan tanpa lampiran.
            ($membuat && $jenisArsip === Arsip::JENIS_SEDERHANA) ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', config('arsip.allowed_mimes')),
            'max:'.config('arsip.max_upload_kb'),
        ];

        return $rules;
    }

    private function attributes(string $jenisArsip): array
    {
        $labels = [
            'kategori_id' => 'kategori',
            'file' => 'berkas arsip',
            'saksi' => 'saksi',
            'foto' => 'foto bukti',
            'foto.*' => 'foto bukti',
            'saksi.*.nama' => 'nama saksi',
            'saksi.*.alamat' => 'alamat saksi',
            'saksi.*.telp' => 'no. telp saksi',
        ];

        foreach (Arsip::fieldsFor($jenisArsip) as $nama => $field) {
            $labels[$nama] = strtolower($field['label']);
        }

        if ($baris = Arsip::barisFor($jenisArsip)) {
            $labels['dokumen'] = strtolower($baris['label']);
            foreach ($baris['kolom'] as $kunci => $kolom) {
                $labels['dokumen.*.'.$kunci] = strtolower($kolom['label']);
            }
        }

        return $labels;
    }

    // ---------------------------------------------------------------------------------------------
    // Pembersihan input
    // ---------------------------------------------------------------------------------------------

    /** Rapikan isian dinamis (saksi, baris dokumen) sebelum divalidasi. */
    private function rapikan(Request $request, string $jenisArsip): void
    {
        if (Arsip::punyaSaksi($jenisArsip)) {
            $this->bersihkanSaksi($request);
        }

        if ($baris = Arsip::barisFor($jenisArsip)) {
            $this->bersihkanBaris($request, array_keys($baris['kolom']), $jenisArsip === 'tanda_terima');
        }
    }

    /** Buang baris saksi yang kosong semua, rapikan spasi, dan indeks ulang dari 0. */
    private function bersihkanSaksi(Request $request): void
    {
        $saksi = collect($request->input('saksi', []))
            ->filter(fn ($s) => is_array($s))
            ->map(fn ($s) => [
                'nama' => trim((string) ($s['nama'] ?? '')) ?: null,
                'alamat' => trim((string) ($s['alamat'] ?? '')) ?: null,
                'telp' => trim((string) ($s['telp'] ?? '')) ?: null,
            ])
            ->filter(fn ($s) => $s['nama'] || $s['alamat'] || $s['telp'])
            ->values()
            ->all();

        $request->merge(['saksi' => $saksi]);
    }

    /** Buang baris dokumen yang kosong semua, rapikan spasi, dan indeks ulang dari 0. */
    private function bersihkanBaris(Request $request, array $kunci, bool $pertahankanNol = false): void
    {
        $baris = collect($request->input('dokumen', []))
            ->filter(fn ($b) => is_array($b))
            ->map(function ($b) use ($kunci, $pertahankanNol) {
                $bersih = [];
                foreach ($kunci as $k) {
                    $nilai = trim(str_replace("\r\n", "\n", (string) ($b[$k] ?? '')));
                    // PSPP-03 memakai penghitung angka; nilai 0 harus tetap tersimpan, bukan dianggap kosong.
                    $bersih[$k] = $nilai === '' || (! $pertahankanNol && ! $nilai) ? null : $nilai;
                }

                return $bersih;
            })
            // Baris yang semua kolomnya kosong dibuang (kolom bernilai bawaan, mis. "Lengkap", tidak dihitung).
            ->filter(fn ($b) => collect($b)->filter()->isNotEmpty())
            ->values()
            ->all();

        $request->merge(['dokumen' => $baris]);
    }

    /** Jumlah foto setelah penghapusan + unggahan baru tidak boleh melebihi batas. */
    private function periksaJumlahFoto(Request $request, Arsip $arsip, string $jenisArsip): void
    {
        if (! Arsip::punyaFotoBukti($jenisArsip)) {
            return;
        }

        $maks = (int) config('arsip.foto.maks_jumlah');
        $hapus = array_map('intval', (array) $request->input('hapus_foto', []));
        $sisa = $arsip->fotos()->whereNotIn('id', $hapus ?: [0])->count();
        $baru = count((array) $request->file('foto', []));

        if ($sisa + $baru > $maks) {
            throw ValidationException::withMessages([
                'foto' => 'Foto bukti maksimal '.$maks.' foto per arsip (saat ini '.$sisa.' foto tersisa).',
            ]);
        }
    }

    private function metaBerkas(UploadedFile $file): array
    {
        // Nama asli hanya untuk tampilan/unduhan; berkas di disk memakai nama acak.
        $nama = preg_replace('/[\x00-\x1F\x7F\/\\\\]/', '', $file->getClientOriginalName()) ?: 'arsip';

        return [
            'file_name' => mb_substr($nama, 0, 200),
            'file_mime' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
        ];
    }
}
