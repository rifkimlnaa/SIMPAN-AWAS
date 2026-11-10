<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arsip extends Model
{
    /** Daftar kolom isian sesuai jenis kategori (lihat config/arsip.php). */
    public static function fieldsFor(?string $jenis): array
    {
        return $jenis === Kategori::JENIS_SEDERHANA
            ? config('arsip.fields_sederhana', [])
            : config('arsip.fields', []);
    }

    public static function groupsFor(?string $jenis): array
    {
        return $jenis === Kategori::JENIS_SEDERHANA
            ? config('arsip.groups_sederhana', [])
            : config('arsip.groups', []);
    }

    /** Definisi satu kolom, dari jenis mana pun. */
    public static function fieldDef(string $name): ?array
    {
        return config("arsip.fields.$name") ?? config("arsip.fields_sederhana.$name");
    }

    /**
     * Kolom isian mengikuti config/arsip.php, jadi tidak perlu diubah di sini.
     * user_id & file_* diisi manual oleh controller, bukan dari input user.
     */
    public function getFillable(): array
    {
        return array_merge(
            ['kategori_id', 'saksi'],
            array_keys(config('arsip.fields', [])),
            array_keys(config('arsip.fields_sederhana', []))
        );
    }

    protected function casts(): array
    {
        $casts = ['file_size' => 'integer', 'saksi' => 'array'];

        foreach ([...config('arsip.fields', []), ...config('arsip.fields_sederhana', [])] as $name => $field) {
            if (($field['type'] ?? null) === 'date') {
                $casts[$name] = 'date';
            }
        }

        return $casts;
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function isSederhana(): bool
    {
        return (bool) $this->kategori?->isSederhana();
    }

    /** Nilai kolom untuk diisikan ke <input> (format yang dimengerti browser). */
    public function inputValue(string $name): ?string
    {
        $value = $this->{$name} ?? null;

        if ($value === null) {
            return null;
        }

        return match (static::fieldDef($name)['type'] ?? null) {
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

        return match (static::fieldDef($name)['type'] ?? null) {
            'date' => $value->translatedFormat($long ? 'l, d F Y' : 'd M Y'),
            'time' => substr((string) $value, 0, 5).' WIB',
            default => (string) $value,
        };
    }

    /** Judul singkat arsip: judul (sederhana) atau nomor laporan (formulir). */
    public function judulTampil(): string
    {
        $value = $this->isSederhana()
            ? $this->judul
            : ($this->{config('arsip.label_field', 'nomor_laporan')} ?? null);

        return $value ?: 'Arsip #'.$this->id;
    }

    /** Ringkasan satu baris untuk daftar campuran. */
    public function ringkasan(): string
    {
        if ($this->isSederhana()) {
            return (string) $this->keterangan;
        }

        return trim('Pelapor: '.($this->nama_pelapor ?? '—').'; Terlapor: '.($this->nama_terlapor ?? '—'));
    }

    /** Tanggal utama arsip: tanggal dilaporkan (formulir) atau tanggal arsip (sederhana). */
    public function tanggalTampil()
    {
        return $this->isSederhana() ? $this->tanggal_arsip : $this->tanggal_dilaporkan;
    }

    public function label(): string
    {
        $base = $this->judulTampil();

        return $this->kategori ? $base.' ('.$this->kategori->nama.')' : $base;
    }

    public function isPdf(): bool
    {
        return $this->file_mime === 'application/pdf';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_mime, 'image/');
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
