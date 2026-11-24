<?php

namespace App\Support;

use App\Models\Arsip;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

/**
 * Menggabungkan berkas scan arsip + lampiran foto bukti menjadi SATU file PDF
 * (scan di depan, foto bukti sebagai lampiran di belakang). Memerlukan setasign/fpdi.
 */
class BerkasGabungan
{
    public static function tersedia(): bool
    {
        return class_exists(Fpdi::class);
    }

    /** @param string $pdfLampiran PDF lampiran foto (hasil dompdf) @return string|null isi PDF, null bila gagal */
    public static function buat(Arsip $arsip, string $pdfLampiran, string $disk = 'local'): ?string
    {
        if (! self::tersedia()) {
            return null;
        }

        $tmp = [];
        try {
            $pdf = new Fpdi('P', 'pt', [612.48, 936.48]);
            $pdf->SetAutoPageBreak(false);

            if ($arsip->punyaBerkas()) {
                $isi = Storage::disk($disk)->get($arsip->file_path);
                if ($arsip->isPdf()) {
                    self::impor($pdf, $isi, $tmp);
                } elseif ($arsip->isImage()) {
                    self::halamanGambar($pdf, $isi, $arsip->file_mime, $tmp);
                }
            }

            self::impor($pdf, $pdfLampiran, $tmp);

            return $pdf->Output('S');
        } catch (\Throwable $e) {
            report($e);

            return null;
        } finally {
            foreach ($tmp as $f) {
                @unlink($f);
            }
        }
    }

    private static function impor(Fpdi $pdf, string $isi, array &$tmp): void
    {
        $f = tempnam(sys_get_temp_dir(), 'pdf');
        $tmp[] = $f;
        file_put_contents($f, $isi);

        $n = $pdf->setSourceFile($f);
        for ($i = 1; $i <= $n; $i++) {
            $id = $pdf->importPage($i);
            $s = $pdf->getTemplateSize($id);
            $pdf->AddPage($s['width'] > $s['height'] ? 'L' : 'P', [$s['width'], $s['height']]);
            $pdf->useTemplate($id);
        }
    }

    private static function halamanGambar(Fpdi $pdf, string $isi, ?string $mime, array &$tmp): void
    {
        $png = str_contains((string) $mime, 'png');
        $f = tempnam(sys_get_temp_dir(), 'img').($png ? '.png' : '.jpg');
        $tmp[] = $f;
        file_put_contents($f, $isi);

        [$w, $h] = getimagesize($f);
        $maxW = 612.48 - 60;
        $maxH = 936.48 - 60;
        $k = min($maxW / $w, $maxH / $h);
        $tw = $w * $k;
        $th = $h * $k;

        $pdf->AddPage('P', [612.48, 936.48]);
        $pdf->Image($f, (612.48 - $tw) / 2, 30, $tw, $th);
    }
}
