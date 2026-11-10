<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\DownloadLog;
use App\Models\Kategori;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // Urut dari yang terbaru: tanggal dilaporkan (formulir) atau tanggal arsip (sederhana).
        $kolomUrut = config('arsip.sort_field', 'id');
        $urut = array_key_exists($kolomUrut, config('arsip.fields', []))
            ? "COALESCE(`{$kolomUrut}`, `tanggal_arsip`) DESC"
            : 'id DESC';

        $arsips = Arsip::query()
            ->visibleTo($request->user())
            ->when($kategoriAktif, fn ($q) => $q->where('kategori_id', $kategoriAktif->id))
            ->with(['kategori:id,nama,slug,jenis', 'user:id,name'])
            ->orderByRaw($urut)
            ->orderByDesc('id')
            ->get();

        return view('arsip.index', compact('arsips', 'kategoriAktif'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Arsip::class);

        // Tanpa ?kategori=..., tampilkan dulu pilihan kategori (isian formulir berbeda per jenis).
        if (! $request->filled('kategori')) {
            return view('arsip.pilih', ['kategoris' => Kategori::withCount('arsips')->orderBy('nama')->get()]);
        }

        $kategori = Kategori::where('slug', $request->query('kategori'))->firstOrFail();

        return view('arsip.form', [
            'arsip' => new Arsip,
            'jenis' => $kategori->jenis,
            // Pilihan kategori dibatasi pada jenis yang sama agar isian formulir tetap cocok.
            'kategoris' => Kategori::where('jenis', $kategori->jenis)->orderBy('nama')->get(),
            'kategoriDipilih' => $kategori->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Arsip::class);

        $kategori = $this->kategoriDariRequest($request);

        if ($kategori->jenis === Kategori::JENIS_FORMULIR) {
            $this->bersihkanSaksi($request);
        }

        $data = $request->validate($this->rules(true, $kategori->jenis), [], $this->attributes($kategori->jenis));
        $file = $request->file('file');

        $meta = $this->metaBerkas($file);
        $path = $file->store('arsip', self::DISK);

        try {
            $arsip = new Arsip(Arr::except($data, ['file']));
            $arsip->user_id = $request->user()->id;
            $arsip->forceFill($meta + ['file_path' => $path])->save();
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($path);
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
            'jenis' => $arsip->kategori->jenis,
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

        if ($kategori->jenis === Kategori::JENIS_FORMULIR) {
            $this->bersihkanSaksi($request);
        }

        $data = $request->validate($this->rules(false, $kategori->jenis), [], $this->attributes($kategori->jenis));

        $arsip->fill(Arr::except($data, ['file']));

        $pathLama = null;
        $pathBaru = null;

        if ($file = $request->file('file')) {
            $meta = $this->metaBerkas($file);
            $pathBaru = $file->store('arsip', self::DISK);
            $pathLama = $arsip->file_path;
            $arsip->forceFill($meta + ['file_path' => $pathBaru]);
        }

        try {
            $arsip->save();
        } catch (\Throwable $e) {
            if ($pathBaru) {
                Storage::disk(self::DISK)->delete($pathBaru);
            }
            throw $e;
        }

        if ($pathLama) {
            Storage::disk(self::DISK)->delete($pathLama);
        }

        return redirect()->route('arsip.show', $arsip)->with('success', 'Arsip berhasil diperbarui.');
    }

    public function destroy(Arsip $arsip): RedirectResponse
    {
        Gate::authorize('delete', $arsip);

        $path = $arsip->file_path;
        $arsip->delete();
        Storage::disk(self::DISK)->delete($path);

        return redirect()->route('arsip.index')->with('success', 'Arsip berhasil dihapus.');
    }

    /** Tampil di browser (preview PDF/gambar). Tidak dicatat sebagai download. */
    public function berkas(Arsip $arsip): Response
    {
        Gate::authorize('view', $arsip);
        abort_unless(Storage::disk(self::DISK)->exists($arsip->file_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk(self::DISK)->response($arsip->file_path, $arsip->file_name, [
            'Content-Type' => $arsip->file_mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Unduh berkas dan catat otomatis ke riwayat download. */
    public function unduh(Request $request, Arsip $arsip): Response
    {
        Gate::authorize('download', $arsip);
        abort_unless(Storage::disk(self::DISK)->exists($arsip->file_path), 404, 'Berkas tidak ditemukan.');

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

    /**
     * Cetak Formulir Laporan (Model B.1) sebagai PDF, terisi otomatis dari data arsip.
     * Dicatat ke riwayat download seperti unduhan berkas.
     */
    public function formulir(Request $request, Arsip $arsip): Response
    {
        Gate::authorize('download', $arsip);

        if (! class_exists(Pdf::class)) {
            return back()->with('error', 'Fitur cetak PDF belum aktif. Jalankan "composer require barryvdh/laravel-dompdf" di folder proyek, lalu coba lagi.');
        }

        $arsip->loadMissing(['kategori', 'user']);

        // Formulir Laporan hanya ada untuk kategori berjenis formulir (PSAP/PSPP).
        abort_if($arsip->kategori->isSederhana(), 404);

        $logoPath = public_path('img/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        $bukti = collect(preg_split('/\R/u', (string) $arsip->bukti) ?: [])
            ->map(fn ($b) => trim($b))
            ->filter()
            ->values()
            ->all();

        // F4 = 215 x 330 mm (dalam poin).
        $konten = Pdf::loadView('arsip.formulir_pdf', [
            'arsip' => $arsip,
            'bukti' => $bukti,
            'logo' => $logo,
            'dicetak' => now()->translatedFormat('d F Y, H:i'),
        ])->setPaper([0, 0, 609.4488, 935.4331])->output();

        DownloadLog::create([
            'arsip_id' => $arsip->id,
            'user_id' => $request->user()->id,
            'arsip_label' => 'Formulir Laporan: '.$arsip->label(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $nama = 'Formulir-Laporan-'.(Str::slug((string) $arsip->nomor_laporan) ?: $arsip->id).'.pdf';

        return response($konten, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ]);
    }

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

    private function rules(bool $membuat, string $jenis): array
    {
        $rules = ['kategori_id' => ['required', 'integer', 'exists:kategoris,id']];

        foreach (Arsip::fieldsFor($jenis) as $nama => $field) {
            $rules[$nama] = $field['rules'];
        }

        if ($jenis === Kategori::JENIS_FORMULIR) {
            // Saksi: tidak wajib, maksimal 10 orang; jika baris diisi maka nama wajib.
            $rules['saksi'] = ['nullable', 'array', 'max:10'];
            $rules['saksi.*.nama'] = ['required', 'string', 'max:150'];
            $rules['saksi.*.alamat'] = ['nullable', 'string', 'max:255'];
            $rules['saksi.*.telp'] = ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'];
        }

        $rules['file'] = [
            $membuat ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', config('arsip.allowed_mimes')),
            'max:'.config('arsip.max_upload_kb'),
        ];

        return $rules;
    }

    private function attributes(string $jenis): array
    {
        $labels = [
            'kategori_id' => 'kategori',
            'file' => 'berkas arsip',
            'saksi' => 'saksi',
            'saksi.*.nama' => 'nama saksi',
            'saksi.*.alamat' => 'alamat saksi',
            'saksi.*.telp' => 'no. telp saksi',
        ];

        foreach (Arsip::fieldsFor($jenis) as $nama => $field) {
            $labels[$nama] = strtolower($field['label']);
        }

        return $labels;
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
