<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    /** Formulir Laporan (Model B.1) dengan semua kolomnya: PSAP dan PSPP. */
    public const JENIS_FORMULIR = 'formulir';

    /** Isian sederhana: judul, keterangan, tanggal, dan berkas. Dipakai kategori buatan admin. */
    public const JENIS_SEDERHANA = 'sederhana';

    protected $fillable = ['nama', 'slug', 'keterangan', 'jenis'];

    public function arsips(): HasMany
    {
        return $this->hasMany(Arsip::class);
    }

    public function isSederhana(): bool
    {
        return $this->jenis === self::JENIS_SEDERHANA;
    }

    public function jenisLabel(): string
    {
        return $this->isSederhana() ? 'Sederhana' : 'Formulir lengkap';
    }
}
