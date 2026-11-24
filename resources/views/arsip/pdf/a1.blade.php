@extends('arsip.pdf._layout')

@section('judul', 'Formulir Laporan Model A.1 '.$arsip->nomor_laporan)

@section('halaman')
    @page { margin: 30pt 56pt 48pt 62pt; }
@endsection

@section('badan')
    font-size: 9.5pt; line-height: 1.3;
@endsection

@section('css')
    @include('arsip.pdf._css_b')
    .kop-a1 { width: 100%; }
    .kop-a1 .logo { width: 62%; vertical-align: top; }
    .kop-a1 .model { width: 38%; vertical-align: top; text-align: right; }
    .kop-a1 .model span { display: inline-block; border: 1px solid #000; padding: 4pt 15pt; font-weight: bold; font-size: 9pt; }
    .kop-a1 .logo img { width: 158pt; }
    .kontak-a1 { margin-top: 3pt; font-size: 8.5pt; line-height: 1.25; }
    .kontak-a1 td.label { width: 44pt; }
    .kontak-a1 td.colon { width: 9pt; }
    .judul { margin: 19pt 0 13pt; }
    .halaman-dua { page-break-before: always; }
    .uraian-a1 { text-align: justify; margin: 0 0 14pt 24pt; }
    .uraian-a1 .petunjuk { font-style: italic; }
    .tempat-lapor { margin-top: 18pt; }
    .tempat-lapor td.l { width: 112pt; }
    .tempat-lapor td.t { width: 10pt; }
    .pernyataan-a1 { margin: 42pt 0 22pt; text-align: justify; }
    .ttd-a1 { width: 100%; page-break-inside: avoid; margin-top: 12pt; }
    .ttd-a1 td { width: 50%; text-align: center; vertical-align: top; }
    .ttd-a1 .ruang { height: 74pt; }
    .ttd-a1 .nama-ttd { font-weight: bold; text-decoration: underline; }
    .ket-a1 { margin-top: 22pt; font-size: 8pt; line-height: 1.35; page-break-inside: avoid; }
    .ket-a1 td { vertical-align: top; }
    .ket-a1 td.b { width: 24pt; }
@endsection

@section('isi')
@php
    $v = fn (string $kolom, bool $panjang = false) => $arsip->displayValue($kolom, $panjang) ?: '-';
    $saksi = $arsip->saksi ?? [];
    // Template A.1 menampilkan minimal dua baris saksi walaupun belum ada saksi yang diajukan.
    $jumlahSaksi = max(2, count($saksi));
    $kantor = $kantor ?? config('arsip.kantor', []);
    $alamatKantor = trim((string) ($kantor['alamat'] ?? ''));
    $teleponKantor = trim((string) ($kantor['telepon'] ?? ''));
    $emailKantor = trim((string) ($kantor['email'] ?? ''));
@endphp

<table class="kop-a1">
    <tr>
        <td class="logo">
            @if ($logo)<img src="{{ $logo }}" alt="Bawaslu">@endif
        </td>
        <td class="model"><span>{{ $def['kode'] }}</span></td>
    </tr>
    @if ($alamatKantor || $teleponKantor || $emailKantor)
        <tr>
            <td colspan="2">
                <table class="kontak-a1">
                    @if ($alamatKantor)
                        <tr><td class="label">Jalan</td><td class="colon">:</td><td>{{ $alamatKantor }}</td></tr>
                    @endif
                    @if ($teleponKantor)
                        <tr><td class="label">Telepon</td><td class="colon">:</td><td>{{ $teleponKantor }}</td></tr>
                    @endif
                    @if ($emailKantor)
                        <tr><td class="label">Email</td><td class="colon">:</td><td>{{ $emailKantor }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    @endif
</table>

<div class="judul">
    <div class="nama">FORMULIR LAPORAN</div>
    <div class="nomor">Nomor: {{ $v('nomor_laporan') }}</div>
</div>

@include('arsip.pdf._b_bagian', ['no' => '1.', 'judul' => 'Identitas Pelapor:', 'baris' => [
    ['Nama', $v('nama_pelapor')],
    ['Tempat/Tgl Lahir', $v('ttl_pelapor')],
    ['Jenis Kelamin', $v('jenis_kelamin_pelapor')],
    ['Pekerjaan', $v('pekerjaan_pelapor')],
    ['Kewarganegaraan', $v('kewarganegaraan_pelapor')],
    ['Alamat', $v('alamat_pelapor')],
    ['No.Telp/HP', $v('telp_pelapor')],
    ['E-Mail**', $v('email_pelapor')],
]])

@include('arsip.pdf._b_bagian', ['no' => '2.', 'judul' => 'Identitas Terlapor', 'baris' => [
    ['Nama', $v('nama_terlapor')],
    ['Alamat***', $v('alamat_terlapor')],
    ['No.Telp/HP**', $v('telp_terlapor')],
]])

@include('arsip.pdf._b_bagian', ['no' => '3.', 'judul' => 'Peristiwa yang dilaporkan:', 'baris' => [
    ['Peristiwa', $v('peristiwa')],
    ['Tempat Kejadian', $v('tempat_kejadian')],
    ['Hari dan Tanggal Kejadian', $v('tanggal_kejadian', true)],
    ['Hari dan Tanggal diketahui', $v('tanggal_diketahui', true)],
]])

<table class="bagian">
    <tr><td class="no">4.</td><td colspan="5" class="judul-bagian">Saksi –saksi:****</td></tr>
    @for ($i = 0; $i < $jumlahSaksi; $i++)
        @php($s = $saksi[$i] ?? [])
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ $i + 1 }}.</td>
            <td class="sub-label">Nama</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['nama'] ?? '..............................................' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">Alamat</td><td class="titik">:</td>
            <td class="isi">{{ $s['alamat'] ?? '..............................................' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">No.Telp/Hp</td><td class="titik">:</td>
            <td class="isi">{{ $s['telp'] ?? '..............................................' }}</td>
        </tr>
        @if ($i < $jumlahSaksi - 1)<tr class="jarak"><td colspan="5">&nbsp;</td></tr>@endif
    @endfor
</table>

<table class="bagian">
    <tr><td class="no">5.</td><td colspan="2" class="judul-bagian">Bukti-Bukti:</td></tr>
    @forelse ($bukti as $i => $b)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ \App\Support\Formulir::huruf($i) }}.</td>
            <td class="isi">{{ $b }}</td>
        </tr>
    @empty
        <tr><td class="no"></td><td class="huruf">a.</td><td class="isi">....................................................................................................</td></tr>
        <tr><td class="no"></td><td class="huruf">b.</td><td class="isi">....................................................................................................</td></tr>
        <tr><td class="no"></td><td class="huruf">c.</td><td class="isi">dst</td></tr>
    @endforelse
</table>

<div class="halaman-dua">
    <table class="bagian" style="margin-bottom:4pt">
        <tr><td class="no">6.</td><td class="judul-bagian">Uraian kejadian:</td></tr>
    </table>
    <div class="uraian-a1">
        <div class="petunjuk">(diuraikan secara kronologis peristiwa apa yang dianggap sebagai dugaan Pelanggaran Pemilihan, di mana, kapan, dan bagaimana peristiwa tersebut terjadi)</div>
        <div style="margin-top:7pt">{!! nl2br(e($v('uraian_kejadian'))) !!}</div>
    </div>

    <table class="tempat-lapor">
        <tr><td class="l">Dilaporkan di</td><td class="t">:</td><td>{{ $v('tempat_dilaporkan') }}</td></tr>
        <tr><td class="l">Hari dan Tanggal</td><td class="t">:</td><td>{{ $v('tanggal_dilaporkan', true) }}</td></tr>
        <tr><td class="l">Pukul</td><td class="t">:</td><td>{{ $v('waktu_dilaporkan') }}</td></tr>
    </table>

    <div class="pernyataan-a1">
        Saya menyatakan bahwa isi laporan ini adalah yang sebenar-benarnya dan saya bersedia mempertanggungjawabkannya di hadapan hukum.
    </div>

    <table class="ttd-a1">
        <tr>
            <td>Penerima<br>Laporan</td>
            <td>Pelapor</td>
        </tr>
        <tr><td class="ruang">&nbsp;</td><td class="ruang">&nbsp;</td></tr>
        <tr>
            <td><span class="nama-ttd">{{ $v('penerima_laporan') }}</span></td>
            <td><span class="nama-ttd">{{ $v('nama_pelapor') }}</span></td>
        </tr>
    </table>

    <table class="ket-a1">
        <tr><td colspan="2">Keterangan:</td></tr>
        <tr><td class="b">*</td><td>nomor formulir dikosongkan dan diisi dengan nomor register laporan setelah laporan dinyatakan memenuhi syarat formal dan materiel</td></tr>
        <tr><td class="b">**</td><td>tidak wajib diisi</td></tr>
        <tr><td class="b">***</td><td>jika alamat tempat tinggal Terlapor tidak lengkap/tidak diketahui, cukup disebutkan dusun/desa/kelurahan</td></tr>
        <tr><td class="b">****</td><td>diisi jika pelapor mengajukan saksi</td></tr>
    </table>
</div>

@include('arsip.pdf._lampiran_foto')
@endsection
