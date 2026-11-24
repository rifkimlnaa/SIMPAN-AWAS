{{-- Satu bagian bernomor: "1. Identitas Pelapor:" dengan butir a, b, c ... ($baris = [[label, nilai], ...]). --}}
<table class="bagian">
    <tr><td class="no">{{ $no }}</td><td colspan="4" class="judul-bagian">{{ $judul }}</td></tr>
    @foreach ($baris as $i => [$label, $nilai])
        <tr>
            <td class="no"></td>
            <td class="huruf">{{ chr(97 + $i) }}.</td>
            <td class="label">{{ $label }}</td>
            <td class="titik">:</td>
            <td class="isi">{!! nl2br(e($nilai)) !!}</td>
        </tr>
    @endforeach
</table>
