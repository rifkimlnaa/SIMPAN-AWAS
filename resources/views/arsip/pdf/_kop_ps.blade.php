{{-- Kop formulir Penyelesaian Sengketa: logo + identitas kantor di kiri, kotak kode formulir di kanan. --}}
<table class="kop">
    <tr>
        <td class="logo">
            @if ($logo)<img src="{{ $logo }}" alt="Bawaslu" width="296">@endif
            <div class="instansi">BADAN PENGAWAS PEMILIHAN UMUM {{ mb_strtoupper(preg_replace('/^Bawaslu\s+/i', '', (string) ($kantor['nama'] ?? ''))) }}</div>
            <div class="alamat">
                @if (! empty($kantor['alamat'])){{ $kantor['alamat'] }}<br>@endif
                @if (! empty($kantor['email']))Email : {{ $kantor['email'] }}<br>@endif
                @if (! empty($kantor['website']))Website : {{ $kantor['website'] }}@endif
            </div>
        </td>
        <td class="model">
            <div class="kotak" style="width: {{ $lebarKotak ?? 150 }}pt">
                {{ $def['kode'] }}<br>{!! $baris2 ?? e($def['kode_judul'] ?? '') !!}
            </div>
        </td>
    </tr>
</table>
