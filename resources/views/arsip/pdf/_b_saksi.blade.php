<table class="bagian">
    <tr><td class="no">{{ $no }}</td><td colspan="5" class="judul-bagian">{{ $judul }}</td></tr>
    @forelse ($arsip->saksi ?? [] as $i => $s)
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ $i + 1 }})</td>
            <td class="sub-label">Nama</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['nama'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">Alamat</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['alamat'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="no"></td><td class="huruf"></td>
            <td class="sub-label">No.Telp/Hp</td>
            <td class="titik">:</td>
            <td class="isi">{{ $s['telp'] ?? '-' }}</td>
        </tr>
        @if (! $loop->last)<tr class="jarak"><td colspan="5">&nbsp;</td></tr>@endif
    @empty
        <tr>
            <td class="no"></td>
            <td colspan="4">- Tidak ada saksi -</td>
        </tr>
    @endforelse
</table>
