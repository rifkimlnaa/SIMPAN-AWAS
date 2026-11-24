<?php

namespace App\Support;

use App\Models\Arsip;
use App\Models\ArsipFoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pengolahan foto bukti: simpan (diperkecil), hapus, dan siapkan untuk dicetak ke PDF/Word.
 */
class FotoBukti
{
    private const DISK = 'local';

    /** Simpan foto-foto baru untuk sebuah arsip. Mengembalikan jalur berkas yang dibuat (untuk dibersihkan bila gagal). */
    public static function simpan(Arsip $arsip, array $files): array
    {
        $dibuat = [];
        $urutan = (int) $arsip->fotos()->max('urutan');

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            [$isi, $ext, $mime] = self::perkecil($file);
            $path = 'arsip-foto/'.Str::uuid().'.'.$ext;
            Storage::disk(self::DISK)->put($path, $isi);
            $dibuat[] = $path;

            $nama = preg_replace('/[\x00-\x1F\x7F\/\\\\]/', '', $file->getClientOriginalName()) ?: 'foto';

            ArsipFoto::create([
                'arsip_id' => $arsip->id,
                'path' => $path,
                'nama_asli' => mb_substr($nama, 0, 200),
                'mime' => $mime,
                'ukuran' => strlen($isi),
                'urutan' => ++$urutan,
            ]);
        }

        return $dibuat;
    }

    /** Hapus beberapa foto (data + berkas) milik arsip tertentu. */
    public static function hapus(Arsip $arsip, array $idFoto): void
    {
        $arsip->fotos()->whereIn('id', $idFoto)->get()->each(function (ArsipFoto $f) {
            Storage::disk(self::DISK)->delete($f->path);
            $f->delete();
        });
    }

    /** Hapus semua berkas foto sebuah arsip dari disk (dipanggil sebelum arsip dihapus). */
    public static function daftarJalur(Arsip $arsip): array
    {
        return $arsip->fotos()->pluck('path')->all();
    }

    public static function hapusBerkas(array $jalur): void
    {
        foreach ($jalur as $p) {
            Storage::disk(self::DISK)->delete($p);
        }
    }

    /**
     * Perkecil foto: putar sesuai EXIF, batasi sisi terpanjang, lalu simpan ulang.
     * JPEG tetap JPEG; PNG tetap PNG. Tanpa ekstensi GD, berkas disimpan apa adanya.
     *
     * @return array{0:string,1:string,2:string} [isi, ekstensi, mime]
     */
    private static function perkecil(UploadedFile $file): array
    {
        $mentah = (string) file_get_contents($file->getRealPath());
        $png = str_starts_with($mentah, "\x89PNG");
        $ext = $png ? 'png' : 'jpg';
        $mime = $png ? 'image/png' : 'image/jpeg';

        if (! function_exists('imagecreatefromstring')) {
            return [$mentah, $ext, $mime];
        }

        $img = @imagecreatefromstring($mentah);
        if (! $img) {
            return [$mentah, $ext, $mime];
        }

        if (! $png && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            $putar = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($putar && ($baru = imagerotate($img, $putar, 0))) {
                $img = $baru;
            }
        }

        $maks = (int) config('arsip.foto.sisi_maks_px', 1600);
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $maks) {
            $skala = $maks / max($w, $h);
            $baru = imagescale($img, max(1, (int) round($w * $skala)), max(1, (int) round($h * $skala)));
            if ($baru) {
                $img = $baru;
            }
        }

        ob_start();
        if ($png) {
            imagesavealpha($img, true);
            imagepng($img, null, 6);
        } else {
            imagejpeg($img, null, (int) config('arsip.foto.kualitas_jpeg', 82));
        }
        $hasil = (string) ob_get_clean();

        return [$hasil !== '' ? $hasil : $mentah, $ext, $mime];
    }

    /**
     * Foto siap cetak untuk PDF/Word: [['src' => data URI, 'w' => px, 'h' => px, 'nama' => ...], ...]
     * Ukuran tampil dihitung agar muat dalam kotak maksimal (poin) tanpa mengubah rasio.
     */
    public static function untukCetak(Arsip $arsip, float $lebarMaks = 470, float $tinggiMaks = 600): array
    {
        if (! Arsip::punyaFotoBukti($arsip->jenis_arsip)) {
            return [];
        }

        $hasil = [];
        foreach ($arsip->fotos as $foto) {
            if (! Storage::disk(self::DISK)->exists($foto->path)) {
                continue;
            }

            $isi = Storage::disk(self::DISK)->get($foto->path);
            $info = @getimagesizefromstring($isi);
            $w = $info[0] ?? 800;
            $h = $info[1] ?? 600;
            $skala = min($lebarMaks / $w, $tinggiMaks / $h, 1.0);
            // Foto kecil tetap dibesarkan sampai lebar sekitar 60% kotak agar terbaca.
            $skala = max($skala, min($lebarMaks * 0.6 / $w, $lebarMaks / $w, $tinggiMaks / $h));

            $hasil[] = [
                'src' => 'data:'.($foto->mime ?: 'image/jpeg').';base64,'.base64_encode($isi),
                'w' => round($w * $skala),
                'h' => round($h * $skala),
                'nama' => $foto->nama_asli,
            ];
        }

        return $hasil;
    }
}
