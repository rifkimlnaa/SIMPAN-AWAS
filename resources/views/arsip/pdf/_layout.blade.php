<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>@yield('judul')</title>
<style>
    /* Dirender dompdf (CSS 2.1): tanpa flex/grid, tata letak memakai tabel. Kertas F4 (215 x 330 mm). */
    @yield('halaman')
    body { font-family: 'DejaVu Serif', serif; color: #000; @yield('badan') }
    table { border-collapse: collapse; }
    td, th { vertical-align: top; padding: 0; word-wrap: break-word; }
    img { border: 0; }
    .tengah { text-align: center; }
    .kanan { text-align: right; }
    .tebal { font-weight: bold; }
    .garis { text-decoration: underline; }
    .isi { word-wrap: break-word; overflow-wrap: break-word; }
    @yield('css')
</style>
</head>
<body>
@yield('isi')
</body>
</html>
