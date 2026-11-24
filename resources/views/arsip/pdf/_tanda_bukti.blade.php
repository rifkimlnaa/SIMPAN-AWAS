{{-- Isi bersama Tanda Bukti Penyampaian (B.3) dan Perbaikan (B.3.1): hanya judul dan keterangan * yang berbeda. --}}
@php
    $v = fn (string $kolom, bool $panjang = false) => $arsip->displayValue($kolom, $panjang) ?: '-';
    $tanggalSurat = $arsip->tanggal_dilaporkan ? $arsip->tanggal_dilaporkan->translatedFormat('d F Y') : '-';
    $dokumen = $arsip->dokumen ?? [];
    $jumlahBarisDokumen = max(3, count($dokumen));
@endphp

@include('arsip.pdf._kop_b')

<div class="judul">
    <div class="nama">{{ $judulFormulir }}</div>
    <div class="nomor">NOMOR: {{ $v('nomor_laporan') }} *</div>
</div>

<div class="badan-tb">
    <div class="telah">Telah diterima dari</div>

    <table class="data">
        <tr><td class="l">Nama</td><td class="t">:</td><td>{{ $v('nama_pelapor') }}</td></tr>
        <tr><td class="l">Tempat Tanggal Lahir</td><td class="t">:</td><td>{{ $v('ttl_pelapor') }}</td></tr>
        <tr><td class="l">Alamat</td><td class="t">:</td><td>{!! nl2br(e($v('alamat_pelapor'))) !!}</td></tr>
        <tr><td class="l">No.Telp/HP</td><td class="t">:</td><td>{{ $v('telp_pelapor') }}</td></tr>
        <tr><td class="l">Hari dan Tanggal</td><td class="t">:</td><td>{{ $v('tanggal_dilaporkan', true) }}</td></tr>
        <tr><td class="l">Waktu</td><td class="t">:</td><td>{{ $v('waktu_dilaporkan') }}</td></tr>
    </table>

    <div class="dok-judul">Dokumen:</div>
    <table class="dok">
        <tr>
            <th class="c-no">No</th>
            <th class="c-bentuk">Bentuk Dokumen</th>
            <th class="c-jumlah">Jumlah</th>
        </tr>
        @for ($i = 0; $i < $jumlahBarisDokumen; $i++)
            @php($d = $dokumen[$i] ?? [])
            <tr>
                <td class="tengah">{{ $i + 1 }}</td>
                <td>{{ $d['bentuk'] ?? '' }}</td>
                <td class="tengah">{{ $d['jumlah'] ?? '' }}</td>
            </tr>
        @endfor
    </table>

    <div class="terkait">Terkait dengan Laporan dugaan pelanggaran Pemilu {{ $v('jenis_pemilu') }}.**</div>

    <div class="tempat">{{ $v('tempat_dilaporkan') }}, {{ $tanggalSurat }}***</div>

    <div class="diterima">Diterima oleh,</div>

    <table class="ttd-tb">
        <tr>
            <td class="kiri">Penerima Laporan</td>
            <td class="kanan-t">Pelapor</td>
        </tr>
        <tr><td class="ruang">&nbsp;</td><td class="ruang">&nbsp;</td></tr>
        <tr>
            <td class="kiri"><span class="garis">{{ $v('penerima_laporan') }}</span></td>
            <td class="kanan-t"><span class="garis">{{ $v('nama_pelapor') }}</span></td>
        </tr>
    </table>
</div>

<table class="ket">
    <tr><td colspan="2">Keterangan:</td></tr>
    <tr><td class="b">*</td><td>{{ $keteranganNomor }}</td></tr>
    <tr><td class="b">**</td><td>diisi sesuai dengan jenis pemilunya</td></tr>
    <tr><td class="b">***</td><td>diisi tempat, tanggal, bulan dan tahun</td></tr>
</table>
