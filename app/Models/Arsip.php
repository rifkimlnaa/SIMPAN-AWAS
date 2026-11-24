<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arsip extends Model
{
    /** Kunci jenis arsip untuk kategori tambahan (isian sederhana). */
    public const JENIS_SEDERHANA = 'sederhana';

    // ------------------------------------------------------------------ definisi jenis arsip (config/arsip.php)

    /** Semua jenis arsip formulir: [kunci => definisi]. */
    public static function semuaJenis(): array
    {
        return config('arsip.jenis_arsip', []);
    }

    /** Jenis arsip milik satu kategori utama (pelanggaran | sengketa), urut seperti di config. */
    public static function jenisUntukKategori(string $kategoriJenis): array
    {
        return array_filter(
            static::semuaJenis(),
            fn (array $j) => ($j['kategori'] ?? null) === $kategoriJenis
                && ($j['tampil'] ?? true) !== false
        );
    }

    /** Definisi lengkap sebuah jenis arsip. Tanpa kunci / kunci tidak dikenal = isian sederhana. */
    public static function definisi(?string $jenisArsip): array
    {
        return static::semuaJenis()[$jenisArsip ?? ''] ?? config('arsip.sederhana', []);
    }

    public static function fieldsFor(?string $jenisArsip): array
    {
        return static::definisi($jenisArsip)['fields'] ?? [];
    }

    public static function groupsFor(?string $jenisArsip): array
    {
        return static::definisi($jenisArsip)['groups'] ?? [];
    }

    /** Definisi tabel baris dinamis (Dokumen / Alat bukti) atau null bila jenis ini tidak memakainya. */
    public static function barisFor(?string $jenisArsip): ?array
    {
        return static::definisi($jenisArsip)['baris'] ?? null;
    }

    public static function punyaSaksi(?string $jenisArsip): bool
    {
        return array_key_exists('saksi', static::groupsFor($jenisArsip));
    }

    /** Apakah jenis arsip ini menerima foto bukti (lampiran foto pada formulir)? */
    public static function punyaFotoBukti(?string $jenisArsip): bool
    {
        return (bool) (static::definisi($jenisArsip)['foto_bukti'] ?? false);
    }

    /** Semua kolom isian dari semua jenis (untuk fillable & cast). */
    private static function semuaKolom(): array
    {
        $kolom = config('arsip.sederhana.fields', []);

        foreach (static::semuaJenis() as $jenis) {
            $kolom += $jenis['fields'] ?? [];
        }

        return $kolom;
    }

    /** Definisi satu kolom pada jenis tertentu (atau dari jenis mana pun bila jenis tidak diberikan). */
    public static function fieldDef(string $name, ?string $jenisArsip = null): ?array
    {
        return static::fieldsFor($jenisArsip)[$name] ?? static::semuaKolom()[$name] ?? null;
    }

    /**
     * Kolom isian mengikuti config/arsip.php, jadi tidak perlu diubah di sini.
     * user_id, jenis_arsip & file_* diisi manual oleh controller, bukan dari input user.
     */
    public function getFillable(): array
    {
        return array_merge(['kategori_id', 'saksi', 'dokumen'], array_keys(static::semuaKolom()));
    }

    protected function casts(): array
    {
        $casts = ['file_size' => 'integer', 'saksi' => 'array', 'dokumen' => 'array'];

        foreach (static::semuaKolom() as $name => $field) {
            if (($field['type'] ?? null) === 'date') {
                $casts[$name] = 'date';
            }
        }

        return $casts;
    }

    // ------------------------------------------------------------------ relasi & scope

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(ArsipFoto::class)->orderBy('urutan')->orderBy('id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    /** Admin melihat semua arsip; petugas hanya arsip yang ia unggah. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    // ------------------------------------------------------------------ jenis arsip

    public function isSederhana(): bool
    {
        return ($this->jenis_arsip ?? self::JENIS_SEDERHANA) === self::JENIS_SEDERHANA;
    }

    /** Nama jenis arsip: "Formulir Laporan", "Tanda Terima Permohonan", dst. Kosong untuk arsip sederhana. */
    public function jenisNama(): string
    {
        if ($this->isSederhana()) {
            return '';
        }

        $definisi = static::definisi($this->jenis_arsip);
        $nama = (string) ($definisi['label'] ?? '');

        // Bedakan Model A.1 dan B.1 saat arsip ditampilkan dalam daftar gabungan.
        if ($this->jenis_arsip === 'formulir_laporan_a1') {
            return $nama.' Model A.1';
        }

        return $nama;
    }

    /** Apakah arsip ini bisa dicetak sebagai formulir PDF (semua jenis kecuali sederhana). */
    public function bisaCetakFormulir(): bool
    {
        return ! $this->isSederhana() && isset(static::semuaJenis()[$this->jenis_arsip]);
    }

    // ------------------------------------------------------------------ nilai isian

    /** Nilai kolom untuk diisikan ke <input> (format yang dimengerti browser). */
    public function inputValue(string $name): ?string
    {
        $value = $this->{$name} ?? null;

        if ($value === null) {
            return null;
        }

        return match (static::fieldDef($name, $this->jenis_arsip)['type'] ?? null) {
            'date' => $value->format('Y-m-d'),
            'time' => substr((string) $value, 0, 5),
            default => (string) $value,
        };
    }

    /** Nilai kolom untuk ditampilkan ke pembaca (tanggal berbahasa Indonesia). */
    public function displayValue(string $name, bool $long = false): ?string
    {
        $value = $this->{$name} ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return match (static::fieldDef($name, $this->jenis_arsip)['type'] ?? null) {
            'date' => $value->translatedFormat($long ? 'l, d F Y' : 'd M Y'),
            'time' => substr((string) $value, 0, 5).' WIB',
            default => (string) $value,
        };
    }

    /** Judul singkat arsip: judul (sederhana) atau nomor (formulir). */
    public function judulTampil(): string
    {
        if ($this->isSederhana()) {
            $value = $this->judul;
        } else {
            $value = $this->{config('arsip.label_field', 'nomor_laporan')}
                ?: ($this->nama_pemohon ? 'Pemohon: '.$this->nama_pemohon : null);
        }

        return $value ?: 'Arsip #'.$this->id;
    }

    /** Ringkasan satu baris untuk daftar campuran. */
    public function ringkasan(): string
    {
        $v = fn (?string $x) => $x ?: '—';

        return match ($this->jenis_arsip) {
            'formulir_laporan', 'formulir_laporan_a1' => 'Pelapor: '.$v($this->nama_pelapor).'; Terlapor: '.$v($this->nama_terlapor),
            'formulir_temuan' => 'Pengawas: '.$v($this->nama_pengawas).'; Terlapor: '.$v($this->nama_terlapor),
            'bukti_penyampaian', 'bukti_perbaikan' => 'Pelapor: '.$v($this->nama_pelapor),
            'tanda_terima' => 'Pemohon: '.$v($this->nama_pemohon),
            'alat_bukti' => 'Pemohon: '.$v($this->nama_pemohon).'; '.count($this->dokumen ?? []).' alat bukti',
            'ba_verifikasi' => 'Hasil verifikasi: '.$v($this->hasil_verifikasi),
            default => (string) $this->keterangan,
        };
    }

    /** Tanggal utama arsip: tanggal pada formulir, atau tanggal arsip (sederhana). */
    public function tanggalTampil()
    {
        return $this->isSederhana() ? $this->tanggal_arsip : $this->tanggal_dilaporkan;
    }

    public function label(): string
    {
        $base = $this->judulTampil();
        $jenis = $this->jenisNama();

        return $jenis ? $base.' ('.$jenis.')' : ($this->kategori ? $base.' ('.$this->kategori->nama.')' : $base);
    }

    // ------------------------------------------------------------------ berkas

    public function punyaBerkas(): bool
    {
        return filled($this->file_path);
    }

    public function isPdf(): bool
    {
        return $this->punyaBerkas() && $this->file_mime === 'application/pdf';
    }

    public function isImage(): bool
    {
        return $this->punyaBerkas() && str_starts_with((string) $this->file_mime, 'image/');
    }

    /** Ukuran berkas untuk ditampilkan. Tanpa ekstensi PHP intl (Number::fileSize butuh intl). */
    public function humanSize(): string
    {
        $ukuran = (float) $this->file_size;
        $satuan = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($ukuran >= 1024 && $i < count($satuan) - 1) {
            $ukuran /= 1024;
            $i++;
        }

        return number_format($ukuran, $i === 0 ? 0 : 1, ',', '.').' '.$satuan[$i];
    }
}
