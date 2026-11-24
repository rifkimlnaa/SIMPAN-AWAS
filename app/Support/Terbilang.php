<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Penulisan tanggal dan angka dalam kata (Bahasa Indonesia), untuk kalimat pembuka
 * formulir: "Senin tanggal Sepuluh bulan April tahun Dua Ribu Dua Puluh Tiga pukul 11.59 WIB".
 */
class Terbilang
{
    private const SATUAN = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    private const BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /** Angka bulat (0 - 999 triliun) dalam kata, tiap kata berhuruf awal besar. */
    public static function angka(int $n): string
    {
        if ($n < 0) {
            return 'Minus '.self::angka(-$n);
        }

        if ($n === 0) {
            return 'Nol';
        }

        return trim(self::ejaan($n));
    }

    private static function ejaan(int $n): string
    {
        if ($n < 12) {
            return self::SATUAN[$n];
        }
        if ($n < 20) {
            return self::SATUAN[$n - 10].' Belas';
        }
        if ($n < 100) {
            return trim(self::SATUAN[intdiv($n, 10)].' Puluh '.self::ejaan($n % 10));
        }
        if ($n < 200) {
            return trim('Seratus '.self::ejaan($n - 100));
        }
        if ($n < 1000) {
            return trim(self::SATUAN[intdiv($n, 100)].' Ratus '.self::ejaan($n % 100));
        }
        if ($n < 2000) {
            return trim('Seribu '.self::ejaan($n - 1000));
        }
        if ($n < 1_000_000) {
            return trim(self::ejaan(intdiv($n, 1000)).' Ribu '.self::ejaan($n % 1000));
        }
        if ($n < 1_000_000_000) {
            return trim(self::ejaan(intdiv($n, 1_000_000)).' Juta '.self::ejaan($n % 1_000_000));
        }
        if ($n < 1_000_000_000_000) {
            return trim(self::ejaan(intdiv($n, 1_000_000_000)).' Miliar '.self::ejaan($n % 1_000_000_000));
        }

        return trim(self::ejaan(intdiv($n, 1_000_000_000_000)).' Triliun '.self::ejaan($n % 1_000_000_000_000));
    }

    public static function hari(CarbonInterface $tanggal): string
    {
        return self::HARI[$tanggal->dayOfWeek];
    }

    public static function bulan(CarbonInterface $tanggal): string
    {
        return self::BULAN[$tanggal->month];
    }

    /**
     * "Senin tanggal Sepuluh bulan April tahun Dua Ribu Dua Puluh Tiga".
     * Dengan $dengan = 'pukul', jam ditambahkan: "... pukul 11.59 WIB".
     */
    public static function hariTanggal(?CarbonInterface $tanggal, ?string $waktu = null, string $zona = 'WIB'): string
    {
        if (! $tanggal) {
            return '...................';
        }

        $kalimat = self::hari($tanggal)
            .' tanggal '.self::angka($tanggal->day)
            .' bulan '.self::bulan($tanggal)
            .' tahun '.self::angka($tanggal->year);

        if ($waktu) {
            $kalimat .= ' pukul '.str_replace(':', '.', substr($waktu, 0, 5)).' '.$zona;
        }

        return $kalimat;
    }
}
