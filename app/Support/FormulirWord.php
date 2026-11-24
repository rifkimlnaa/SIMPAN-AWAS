<?php

namespace App\Support;

/**
 * Membungkus HTML formulir (view yang sama dengan PDF) menjadi dokumen Word.
 * Format: MHT (MIME multipart/related) berekstensi .doc. Gambar disertakan sebagai bagian
 * terpisah sehingga tampil di Word tanpa internet. Hasilnya bisa diedit di Word/LibreOffice.
 */
class FormulirWord
{
    public static function buat(string $html): string
    {
        $bagian = [];

        // Pindahkan gambar data-URI ke bagian MIME terpisah.
        $html = preg_replace_callback(
            '#src="data:(image/[a-z0-9.+-]+);base64,([^"]+)"#i',
            function ($m) use (&$bagian) {
                $n = count($bagian) + 1;
                $ext = str_contains($m[1], 'png') ? 'png' : 'jpg';
                $cid = "gambar{$n}.{$ext}";
                $bagian[] = [$cid, $m[1], $m[2]];

                return 'src="'.$cid.'"';
            },
            $html
        );

        // Ganti pengaturan khusus dompdf dengan pengaturan halaman Word (F4, margin ~2 cm).
        $html = preg_replace('#@page\s*\{[^}]*\}#', '', $html);
        $html = str_replace("'DejaVu Serif', serif", "'Times New Roman', serif", $html);
        $html = preg_replace('#<title>.*?</title>#s', '', $html);

        // Word/LibreOffice kurang andal membaca selector CSS bertingkat: jadikan gaya inline.
        $html = (new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles)->convert($html);

        // Ukuran gambar cukup lewat atribut width (px); style pt ganda membuat Word/LibreOffice salah hitung.
        $html = preg_replace_callback('#<img\b[^>]*>#i', function ($m) {
            return preg_match('#\swidth="\d+"#', $m[0])
                ? preg_replace('#(style="[^"]*?)\s*width:\s*[\d.]+pt;?#i', '$1', $m[0])
                : $m[0];
        }, $html);

        $css = '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument></xml><![endif]-->'
            .'<style>@page Section1 { size: 21.5cm 33cm; margin: 2cm 2cm 2cm 2.2cm; mso-page-orientation: portrait; }'
            .' div.Section1 { page: Section1; } body { font-size: 10.5pt; }'
            .' p { margin: 0; } table { mso-table-lspace: 0; mso-table-rspace: 0; }</style>';

        $html = preg_replace('#</head>#i', $css.'</head>', $html, 1);
        $html = preg_replace('#<body([^>]*)>#i', '<body$1><div class="Section1">', $html, 1);
        $html = preg_replace('#</body>#i', '</div></body>', $html, 1);
        $html = preg_replace('#<html([^>]*)>#i', '<html$1 xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">', $html, 1);

        $batas = '----=_Part_'.bin2hex(random_bytes(8));
        $eol = "\r\n";

        $out = 'MIME-Version: 1.0'.$eol
            .'Content-Type: multipart/related; boundary="'.$batas.'"; type="text/html"'.$eol.$eol
            .'--'.$batas.$eol
            .'Content-Type: text/html; charset="utf-8"'.$eol
            .'Content-Transfer-Encoding: quoted-printable'.$eol
            .'Content-Location: file:///C:/formulir.htm'.$eol.$eol
            .quoted_printable_encode($html).$eol;

        foreach ($bagian as [$nama, $mime, $b64]) {
            $out .= '--'.$batas.$eol
                .'Content-Location: file:///C:/'.$nama.$eol
                .'Content-Transfer-Encoding: base64'.$eol
                .'Content-Type: '.$mime.$eol.$eol
                .chunk_split($b64, 76, $eol).$eol;
        }

        return $out.'--'.$batas.'--'.$eol;
    }
}
