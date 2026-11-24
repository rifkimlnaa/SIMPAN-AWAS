<?php

namespace App\Support;

/**
 * Pembantu cetakan formulir PDF.
 */
class Formulir
{
    /**
     * Pecah teks beberapa baris menjadi judul + butir bernomor huruf (a, b, c, ...).
     * Dipakai kolom "Jenis Dokumen" dan "Keterangan" pada Tanda Terima / Berita Acara.
     *
     *   $judulPertama = true : baris pertama = judul (tanpa huruf), baris berikutnya = butir a, b, c.
     *   $judulPertama = false: satu baris saja = teks biasa; dua baris atau lebih = butir a, b, c.
     *
     * @return array{judul: ?string, butir: list<string>}
     */
    public static function pecah(?string $teks, bool $judulPertama): array
    {
        $baris = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $teks) ?: []),
            fn ($b) => $b !== ''
        ));

        if ($judulPertama) {
            return ['judul' => array_shift($baris), 'butir' => $baris];
        }

        return count($baris) === 1
            ? ['judul' => $baris[0], 'butir' => []]
            : ['judul' => null, 'butir' => $baris];
    }

    /** Huruf butir: 0 => a, 1 => b, ... 26 => 27 (lewat z jadi angka). */
    public static function huruf(int $i): string
    {
        return $i < 26 ? chr(97 + $i) : (string) ($i + 1);
    }
}
