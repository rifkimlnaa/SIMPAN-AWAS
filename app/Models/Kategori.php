<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    /** Kategori utama 1: Penanganan Pelanggaran (B.1, B.2, B.3, B.3.1). */
    public const JENIS_PELANGGARAN = 'pelanggaran';

    /** Kategori utama 2: Penyelesaian Sengketa (Tanda Terima, Daftar Alat Bukti, Berita Acara). */
    public const JENIS_SENGKETA = 'sengketa';

    /** Kategori tambahan buatan admin: hanya judul, keterangan, tanggal, dan berkas. */
    public const JENIS_SEDERHANA = 'sederhana';

    protected $fillable = ['nama', 'slug', 'keterangan', 'jenis'];

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    /** Dua kategori utama di atas, lalu kategori tambahan menurut abjad. */
    public function scopeUrut($query)
    {
        return $query
            ->orderByRaw("CASE jenis WHEN 'pelanggaran' THEN 0 WHEN 'sengketa' THEN 1 ELSE 2 END")
            ->orderBy('nama');
    }

    public function isSederhana(): bool
    {
        return $this->jenis === self::JENIS_SEDERHANA;
    }

    /** Kategori utama (Penanganan Pelanggaran / Penyelesaian Sengketa): tidak bisa dihapus. */
    public function isUtama(): bool
    {
        return in_array($this->jenis, [self::JENIS_PELANGGARAN, self::JENIS_SENGKETA], true);
    }

    /**
     * Pilihan jenis arsip untuk kategori ini: [kunci => definisi]. Kosong untuk kategori tambahan.
     */
    public function jenisArsipList(): array
    {
        return $this->isUtama() ? Arsip::jenisUntukKategori($this->jenis) : [];
    }

    public function jenisLabel(): string
    {
        return $this->isUtama() ? 'Kategori utama' : 'Kategori tambahan';
    }
}
